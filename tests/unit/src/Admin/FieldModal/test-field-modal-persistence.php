<?php
/**
 * Unit tests for PPOM\Admin\FieldModal\FieldModalPersistence.
 *
 * Covers the write path the React admin modal uses to save field groups,
 * independent of the REST surface (test-field-modal-rest.php).
 *
 * @package ppom-pro
 */

require_once dirname( __DIR__, 3 ) . '/class-ppom-test-case.php';

use PPOM\Admin\FieldModal\FieldModalPersistence;
use PPOM\Meta\MetaRepositoryAccessor;

/**
 * @covers \PPOM\Admin\FieldModal\FieldModalPersistence
 */
class Test_Admin_FieldModal_FieldModalPersistence extends PPOM_Test_Case {

	/**
	 * Track group IDs to remove in tearDown.
	 *
	 * @var array<int, int>
	 */
	private $created_ids = array();

	public function tearDown(): void {
		$repo = MetaRepositoryAccessor::instance();
		foreach ( $this->created_ids as $id ) {
			$repo->delete_by_id( $id );
		}
		$this->created_ids = array();

		parent::tearDown();
	}

	private function track( int $id ): int {
		if ( $id > 0 ) {
			$this->created_ids[] = $id;
		}
		return $id;
	}

	private function instance(): FieldModalPersistence {
		return new FieldModalPersistence();
	}

	/**
	 * Non-array input is rejected with WP_Error and status 400.
	 *
	 * @return void
	 */
	public function test_normalize_fields_rejects_non_array_payload() {
		$result = $this->instance()->normalize_fields( 'not an array', 0 );

		$this->assertWPError( $result );
		$this->assertSame( 'ppom_invalid_fields', $result->get_error_code() );
		$this->assertSame( 400, $result->get_error_data()['status'] );
	}

	/**
	 * Each row missing a non-blank data_name produces a validation error per legacy modal contract.
	 *
	 * @return void
	 */
	public function test_normalize_fields_rejects_blank_data_name() {
		$result = $this->instance()->normalize_fields(
			array(
				array(
					'type'      => 'text',
					'title'     => 'Engraving',
					'data_name' => '   ',
				),
			),
			'1'
		);

		$this->assertWPError( $result );
		$this->assertSame( 'ppom_field_validation', $result->get_error_code() );
		$this->assertContains( 'Data Name must be required', $result->get_error_data()['errors'] );
	}

	/**
	 * Non-array rows in the payload are silently skipped instead of crashing the pipeline.
	 *
	 * @return void
	 */
	public function test_normalize_fields_skips_non_array_rows() {
		$result = $this->instance()->normalize_fields(
			array(
				'garbage row',
				array(
					'type'      => 'text',
					'title'     => 'Engraving',
					'data_name' => 'engraving',
				),
				42,
			),
			0
		);

		$this->assertIsArray( $result );
		$this->assertCount( 1, $result );
		$this->assertSame( 'engraving', $result[0]['data_name'] );
	}

	/**
	 * Rows the ppom_admin_field_modal_normalize_field filter rewrites into a non-array are dropped.
	 *
	 * @return void
	 */
	public function test_normalize_fields_drops_rows_that_filter_rewrites_to_non_array() {
		$filter = static function ( $row, $index ) {
			return 0 === $index ? null : $row;
		};
		add_filter( 'ppom_admin_field_modal_normalize_field', $filter, 10, 2 );

		try {
			$result = $this->instance()->normalize_fields(
				array(
					array(
						'type'      => 'text',
						'title'     => 'A',
						'data_name' => 'first',
					),
					array(
						'type'      => 'text',
						'title'     => 'B',
						'data_name' => 'second',
					),
				),
				0
			);
		} finally {
			remove_filter( 'ppom_admin_field_modal_normalize_field', $filter, 10 );
		}

		$this->assertIsArray( $result );
		$this->assertCount( 1, $result );
		$this->assertSame( 'second', $result[0]['data_name'] );
	}

	/**
	 * The ppom_admin_field_modal_validate_field filter can inject extra errors that abort the save.
	 *
	 * @return void
	 */
	public function test_normalize_fields_external_validator_can_short_circuit() {
		$filter = static function ( $errors ) {
			$errors[] = 'External validator rejected the field';
			return $errors;
		};
		add_filter( 'ppom_admin_field_modal_validate_field', $filter, 10, 1 );

		try {
			$result = $this->instance()->normalize_fields(
				array(
					array(
						'type'      => 'text',
						'title'     => 'A',
						'data_name' => 'first',
					),
				),
				0
			);
		} finally {
			remove_filter( 'ppom_admin_field_modal_validate_field', $filter, 10 );
		}

		$this->assertWPError( $result );
		$this->assertContains( 'External validator rejected the field', $result->get_error_data()['errors'] );
	}

	/**
	 * After normalize, rows missing both `type` and `data_name` are filtered out.
	 *
	 * Note: a row with `title` but no data_name survives because the WPML
	 * callback synthesizes `data_name` from `title` via sanitize_key(). The
	 * truly empty row exercised here has neither title nor type, so nothing
	 * downstream can rescue it.
	 *
	 * @return void
	 */
	public function test_normalize_fields_drops_rows_with_neither_type_nor_data_name() {
		$result = $this->instance()->normalize_fields(
			array(
				array(
					'type'      => 'text',
					'title'     => 'Keep me',
					'data_name' => 'keepme',
				),
				array(
					// No type, no data_name, no title — nothing for WPML/Pro hooks to fill in.
					'description' => 'orphan',
				),
			),
			0
		);

		$this->assertIsArray( $result );
		$this->assertCount( 1, $result );
		$this->assertSame( 'keepme', $result[0]['data_name'] );
	}

	/**
	 * create_group rejects titles over 50 chars before touching the DB.
	 *
	 * @return void
	 */
	public function test_create_group_rejects_overlong_title() {
		$result = $this->instance()->create_group(
			array(
				'productmeta_name'      => str_repeat( 'a', 51 ),
				'dynamic_price_display' => 'no',
			),
			array(
				array(
					'type'      => 'text',
					'title'     => 'A',
					'data_name' => 'a',
				),
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'ppom_title_length', $result->get_error_code() );
	}

	/**
	 * create_group propagates normalize_fields errors back to the caller.
	 *
	 * @return void
	 */
	public function test_create_group_propagates_validation_error_without_inserting() {
		$initial = MetaRepositoryAccessor::instance()->count_rows();

		$result = $this->instance()->create_group(
			array( 'productmeta_name' => 'Bad fields' ),
			array(
				array(
					'type'      => 'text',
					'data_name' => '',
				),
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'ppom_field_validation', $result->get_error_code() );
		$this->assertSame(
			$initial,
			MetaRepositoryAccessor::instance()->count_rows(),
			'No row should be inserted when validation fails.'
		);
	}

	/**
	 * create_group happy path: inserts a row, returns the new id, redirect URL,
	 * and the validated field rows.
	 *
	 * @return void
	 */
	public function test_create_group_inserts_row_and_returns_payload() {
		$result = $this->instance()->create_group(
			array(
				'productmeta_name'      => 'Group via React modal',
				'dynamic_price_display' => 'no',
				'show_cart_thumb'       => 'no',
				'productmeta_style'     => '',
				'productmeta_js'        => '',
			),
			array(
				array(
					'type'      => 'text',
					'title'     => 'Engraving',
					'data_name' => 'engraving',
				),
				array(
					'type'      => 'select',
					'title'     => 'Plan',
					'data_name' => 'plan',
					'options'   => array(
						array( 'option' => 'A', 'price' => '5' ),
					),
				),
			)
		);

		$this->assertIsArray( $result );
		$this->assertGreaterThan( 0, $result['productmeta_id'] );
		$this->track( (int) $result['productmeta_id'] );

		$row = MetaRepositoryAccessor::instance()->get_row_by_id( (int) $result['productmeta_id'] );
		$this->assertNotNull( $row );
		$this->assertSame( 'Group via React modal', $row->productmeta_name );

		$this->assertStringContainsString( 'productmeta_id=' . $result['productmeta_id'], $result['redirect_to'] );
		$this->assertStringContainsString( 'do_meta=edit', $result['redirect_to'] );
		$this->assertNotEmpty( $result['message'] );

		$fields = $result['fields'];
		$this->assertCount( 2, $fields );
		$this->assertSame( 'engraving', $fields[0]['data_name'] );
		$this->assertSame( 'plan', $fields[1]['data_name'] );
	}

	/**
	 * update_group rejects non-positive ids.
	 *
	 * @return void
	 */
	public function test_update_group_rejects_invalid_id() {
		$result = $this->instance()->update_group(
			0,
			array( 'productmeta_name' => 'X' ),
			array( array( 'type' => 'text', 'title' => 'A', 'data_name' => 'a' ) )
		);

		$this->assertWPError( $result );
		$this->assertSame( 'ppom_invalid_id', $result->get_error_code() );
	}

	/**
	 * update_group returns ppom_not_found for an id that doesn't exist.
	 *
	 * @return void
	 */
	public function test_update_group_returns_not_found_for_unknown_id() {
		$result = $this->instance()->update_group(
			999999,
			array( 'productmeta_name' => 'X' ),
			array( array( 'type' => 'text', 'title' => 'A', 'data_name' => 'a' ) )
		);

		$this->assertWPError( $result );
		$this->assertSame( 'ppom_not_found', $result->get_error_code() );
		$this->assertSame( 404, $result->get_error_data()['status'] );
	}

	/**
	 * update_group writes the new name + fields to an existing row.
	 *
	 * @return void
	 */
	public function test_update_group_updates_existing_row() {
		$create = $this->instance()->create_group(
			array( 'productmeta_name' => 'Before' ),
			array( array( 'type' => 'text', 'title' => 'A', 'data_name' => 'a' ) )
		);
		$this->assertIsArray( $create );
		$id = (int) $create['productmeta_id'];
		$this->track( $id );

		$result = $this->instance()->update_group(
			$id,
			array( 'productmeta_name' => 'After' ),
			array(
				array( 'type' => 'text', 'title' => 'A2', 'data_name' => 'a2' ),
				array( 'type' => 'text', 'title' => 'B', 'data_name' => 'b' ),
			)
		);

		$this->assertIsArray( $result );
		$this->assertSame( $id, $result['productmeta_id'] );
		$this->assertCount( 2, $result['fields'] );

		$row = MetaRepositoryAccessor::instance()->get_row_by_id( $id );
		$this->assertSame( 'After', $row->productmeta_name );

		$stored = json_decode( $row->the_meta, true );
		$this->assertIsArray( $stored );
		$this->assertSame( 'a2', $stored[0]['data_name'] );
		$this->assertSame( 'b', $stored[1]['data_name'] );
	}
}
