<?php
/**
 * Unit tests for PPOM\Data\FieldGroupRepository — direct SQL repository for nm_personalized.
 *
 * Each test inserts its own rows so it doesn't depend on the bootstrap state of
 * the install. We never assume the table is empty (other suites may seed it).
 *
 * @package ppom-pro
 */

require_once dirname( __DIR__, 2 ) . '/class-ppom-test-case.php';

use PPOM\Data\FieldGroupRepository;

/**
 * @covers \PPOM\Data\FieldGroupRepository
 */
class Test_Data_FieldGroupRepository extends PPOM_Test_Case {

	/**
	 * IDs created via insert_row, removed in tearDown so the suite stays self-contained.
	 *
	 * @var array<int, int>
	 */
	private $created_ids = array();

	public function tearDown(): void {
		$repo = FieldGroupRepository::instance();
		foreach ( $this->created_ids as $id ) {
			$repo->delete_by_productmeta_id( $id );
		}
		$this->created_ids = array();

		parent::tearDown();
	}

	/**
	 * @return array<string, mixed>
	 */
	private function row_payload( array $overrides = array() ) {
		return array_merge(
			array(
				'productmeta_name'       => 'Repo Test ' . wp_generate_password( 6, false ),
				'productmeta_validation' => 'no',
				'dynamic_price_display'  => 'no',
				'send_file_attachment'   => '',
				'show_cart_thumb'        => 'no',
				'aviary_api_key'         => '',
				'productmeta_style'      => '',
				'productmeta_categories' => '',
				'productmeta_tags'       => '',
				'productmeta_js'         => '',
				'the_meta'               => wp_json_encode( array() ),
				'productmeta_created'    => current_time( 'mysql' ),
			),
			$overrides
		);
	}

	private function insert( array $overrides = array() ): int {
		$data   = $this->row_payload( $overrides );
		$format = array_fill( 0, count( $data ), '%s' );

		$id = FieldGroupRepository::instance()->insert_row( $data, $format );
		$this->assertGreaterThan( 0, $id );
		$this->created_ids[] = $id;

		return $id;
	}

	/**
	 * table_name resolves against the active wpdb prefix.
	 *
	 * @return void
	 */
	public function test_table_name_uses_active_wpdb_prefix() {
		global $wpdb;

		$this->assertSame( $wpdb->prefix . PPOM_TABLE_META, FieldGroupRepository::instance()->table_name() );
	}

	/**
	 * instance() returns the same singleton on repeat calls.
	 *
	 * @return void
	 */
	public function test_instance_returns_singleton() {
		$a = FieldGroupRepository::instance();
		$b = FieldGroupRepository::instance();

		$this->assertSame( $a, $b );
	}

	/**
	 * insert_row stores values and returns a positive insert id.
	 *
	 * @return void
	 */
	public function test_insert_row_returns_insert_id_and_persists_values() {
		$id = $this->insert( array( 'productmeta_name' => 'Inserted name' ) );

		$row = FieldGroupRepository::instance()->get_row_by_productmeta_id( $id );

		$this->assertNotNull( $row );
		$this->assertSame( 'Inserted name', $row->productmeta_name );
	}

	/**
	 * get_row_by_productmeta_id returns null for an unknown id rather than false/0.
	 *
	 * @return void
	 */
	public function test_get_row_by_productmeta_id_returns_null_for_missing_row() {
		$row = FieldGroupRepository::instance()->get_row_by_productmeta_id( 999999 );

		$this->assertNull( $row );
	}

	/**
	 * get_the_meta_json_by_productmeta_id returns the stored JSON string, or null when missing.
	 *
	 * @return void
	 */
	public function test_get_the_meta_json_returns_stored_json_or_null() {
		$payload = wp_json_encode( array( array( 'type' => 'text', 'data_name' => 'foo' ) ) );
		$id      = $this->insert( array( 'the_meta' => $payload ) );

		$this->assertSame( $payload, FieldGroupRepository::instance()->get_the_meta_json_by_productmeta_id( $id ) );
		$this->assertNull( FieldGroupRepository::instance()->get_the_meta_json_by_productmeta_id( 999999 ) );
	}

	/**
	 * update_row writes the new column values for the matching row.
	 *
	 * @return void
	 */
	public function test_update_row_updates_existing_row() {
		$id = $this->insert( array( 'productmeta_name' => 'Before' ) );

		$rows_affected = FieldGroupRepository::instance()->update_row(
			array( 'productmeta_name' => 'After' ),
			array( 'productmeta_id' => $id ),
			array( '%s' ),
			array( '%d' )
		);

		$this->assertNotFalse( $rows_affected );
		$row = FieldGroupRepository::instance()->get_row_by_productmeta_id( $id );
		$this->assertSame( 'After', $row->productmeta_name );
	}

	/**
	 * get_rows_by_productmeta_ids returns rows for known ids, drops zeros, and short-circuits on empty input.
	 *
	 * @return void
	 */
	public function test_get_rows_by_productmeta_ids_handles_known_unknown_and_empty() {
		$id_a = $this->insert();
		$id_b = $this->insert();

		$repo = FieldGroupRepository::instance();

		$this->assertSame( array(), $repo->get_rows_by_productmeta_ids( array() ) );
		$this->assertSame( array(), $repo->get_rows_by_productmeta_ids( array( 0, '', null ) ) );

		$rows  = $repo->get_rows_by_productmeta_ids( array( $id_a, $id_b, 999999 ) );
		$found = array_map( static fn( $r ) => (int) $r->productmeta_id, $rows );
		sort( $found );

		$expected = array( $id_a, $id_b );
		sort( $expected );

		$this->assertSame( $expected, $found );
	}

	/**
	 * find_rows_with_categories_or_tags returns only the rows that populate either column.
	 *
	 * @return void
	 */
	public function test_find_rows_with_categories_or_tags_returns_only_matching_rows() {
		$only_category = $this->insert( array( 'productmeta_categories' => '5' ) );
		$only_tag      = $this->insert( array( 'productmeta_tags' => '11' ) );
		$neither       = $this->insert();

		$rows = FieldGroupRepository::instance()->find_rows_with_categories_or_tags();
		$ids  = array_map( static fn( $r ) => (int) $r->productmeta_id, $rows );

		$this->assertContains( $only_category, $ids );
		$this->assertContains( $only_tag, $ids );
		$this->assertNotContains( $neither, $ids );
	}

	/**
	 * get_categories_tags_columns_by_id returns the columns as an associative array,
	 * and an empty array for a missing row.
	 *
	 * @return void
	 */
	public function test_get_categories_tags_columns_by_id_returns_associative_or_empty() {
		$id = $this->insert( array( 'productmeta_categories' => '7,8', 'productmeta_tags' => 'tag' ) );

		$cols = FieldGroupRepository::instance()->get_categories_tags_columns_by_id( $id );

		$this->assertSame( '7,8', $cols['productmeta_categories'] );
		$this->assertSame( 'tag', $cols['productmeta_tags'] );

		$this->assertSame( array(), FieldGroupRepository::instance()->get_categories_tags_columns_by_id( 999999 ) );
	}

	/**
	 * find_rows_with_inline_js_or_style picks up any row with non-empty productmeta_js or productmeta_style.
	 *
	 * @return void
	 */
	public function test_find_rows_with_inline_js_or_style_returns_only_legacy_rows() {
		$with_js     = $this->insert( array( 'productmeta_js' => 'console.log(1);' ) );
		$with_style  = $this->insert( array( 'productmeta_style' => '.x{color:red}' ) );
		$plain       = $this->insert();

		$rows = FieldGroupRepository::instance()->find_rows_with_inline_js_or_style();
		$ids  = array_map( static fn( $r ) => (int) $r->productmeta_id, $rows );

		$this->assertContains( $with_js, $ids );
		$this->assertContains( $with_style, $ids );
		$this->assertNotContains( $plain, $ids );
	}

	/**
	 * delete_by_productmeta_id removes the matching row only.
	 *
	 * @return void
	 */
	public function test_delete_by_productmeta_id_removes_only_targeted_row() {
		$id_a = $this->insert();
		$id_b = $this->insert();

		$repo = FieldGroupRepository::instance();
		$repo->delete_by_productmeta_id( $id_a );

		$this->assertNull( $repo->get_row_by_productmeta_id( $id_a ) );
		$this->assertNotNull( $repo->get_row_by_productmeta_id( $id_b ) );

		// Already removed — drop from tracking so tearDown doesn't double-delete.
		$this->created_ids = array_values(
			array_filter(
				$this->created_ids,
				static fn( $tracked ) => $tracked !== $id_a
			)
		);
	}

	/**
	 * delete_by_productmeta_ids returns false for empty input (no SQL issued)
	 * and deletes all matching rows for a non-empty list.
	 *
	 * @return void
	 */
	public function test_delete_by_productmeta_ids_returns_false_for_empty_and_deletes_list() {
		$repo = FieldGroupRepository::instance();

		$this->assertFalse( $repo->delete_by_productmeta_ids( array() ) );
		$this->assertFalse( $repo->delete_by_productmeta_ids( array( 0, '', null ) ) );

		$id_a = $this->insert();
		$id_b = $this->insert();
		$id_c = $this->insert();

		$rows_affected = $repo->delete_by_productmeta_ids( array( $id_a, $id_b ) );

		$this->assertNotFalse( $rows_affected );
		$this->assertNull( $repo->get_row_by_productmeta_id( $id_a ) );
		$this->assertNull( $repo->get_row_by_productmeta_id( $id_b ) );
		$this->assertNotNull( $repo->get_row_by_productmeta_id( $id_c ) );

		$this->created_ids = array_values(
			array_filter(
				$this->created_ids,
				static fn( $tracked ) => ! in_array( $tracked, array( $id_a, $id_b ), true )
			)
		);
	}
}
