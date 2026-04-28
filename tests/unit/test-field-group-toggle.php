<?php
/**
 * Class Test_Field_Group_Toggle
 *
 * Coverage for the field-group enable/disable feature: repository CRUD, the
 * frontend filter that hides disabled groups in PPOM_Meta, and the guarantee
 * that attachments + the_meta JSON survive a disable/enable round trip.
 *
 * @package ppom-pro
 */

require_once __DIR__ . '/class-ppom-test-case.php';

class Test_Field_Group_Toggle extends PPOM_Test_Case {

	/**
	 * Repository: set_disabled flips the column and the cached row reflects it.
	 *
	 * @return void
	 */
	public function test_set_disabled_persists_and_invalidates_cache() {
		$meta_id = $this->insert_ppom_meta(
			array(
				$this->build_text_field( 'toggle_a', 'Toggle A' ),
			)
		);

		$repo = ppom_meta_repository();

		// Newly-inserted row defaults to enabled (empty string).
		$row = $repo->get_row_by_id( $meta_id );
		$this->assertNotNull( $row );
		$this->assertSame( '', (string) ( $row->productmeta_disabled ?? '' ) );

		$this->assertNotFalse( $repo->set_disabled( $meta_id, true ) );
		$row = $repo->get_row_by_id( $meta_id );
		$this->assertSame( 'on', (string) $row->productmeta_disabled );

		$this->assertNotFalse( $repo->set_disabled( $meta_id, false ) );
		$row = $repo->get_row_by_id( $meta_id );
		$this->assertSame( '', (string) $row->productmeta_disabled );
	}

	/**
	 * Repository: bulk path flips many rows in one statement.
	 *
	 * @return void
	 */
	public function test_set_disabled_for_ids_handles_bulk() {
		$id_a = $this->insert_ppom_meta( array( $this->build_text_field( 'bulk_a' ) ) );
		$id_b = $this->insert_ppom_meta( array( $this->build_text_field( 'bulk_b' ) ) );
		$id_c = $this->insert_ppom_meta( array( $this->build_text_field( 'bulk_c' ) ) );

		$repo = ppom_meta_repository();

		$result = $repo->set_disabled_for_ids( array( $id_a, $id_b, $id_c ), true );
		$this->assertNotFalse( $result );
		$this->assertSame( 3, (int) $result );

		foreach ( array( $id_a, $id_b, $id_c ) as $id ) {
			$row = $repo->get_row_by_id( $id );
			$this->assertSame( 'on', (string) $row->productmeta_disabled, "Row {$id} should be disabled" );
		}

		// Re-enable just two of them; the third should stay disabled.
		$result = $repo->set_disabled_for_ids( array( $id_a, $id_c ), false );
		$this->assertNotFalse( $result );

		$this->assertSame( '', (string) $repo->get_row_by_id( $id_a )->productmeta_disabled );
		$this->assertSame( 'on', (string) $repo->get_row_by_id( $id_b )->productmeta_disabled );
		$this->assertSame( '', (string) $repo->get_row_by_id( $id_c )->productmeta_disabled );
	}

	/**
	 * Repository: empty / non-positive id arrays are rejected without touching
	 * the table.
	 *
	 * @return void
	 */
	public function test_set_disabled_for_ids_rejects_invalid_input() {
		$repo = ppom_meta_repository();

		// Empty payload → no-op.
		$this->assertFalse( $repo->set_disabled_for_ids( array(), true ) );

		// Inputs that absint() collapses to 0 ('abc' → 0, '' → 0, '0' → 0)
		// must short-circuit; the helper coerces with absint(), so anything
		// that resolves to 0 is rejected.
		$this->assertFalse( $repo->set_disabled_for_ids( array( 0, 'abc', '' ), true ) );
	}

	/**
	 * Repository: malformed nested arrays must not be coerced to ID 1.
	 *
	 * @return void
	 */
	public function test_set_disabled_for_ids_ignores_non_scalar_ids() {
		$repo    = ppom_meta_repository();
		$meta_id = $this->insert_ppom_meta( array( $this->build_text_field( 'nested_array_id' ) ) );

		$this->assertFalse( $repo->set_disabled_for_ids( array( array( $meta_id ) ), true ) );
		$this->assertSame( '', (string) $repo->get_row_by_id( $meta_id )->productmeta_disabled );
	}

	/**
	 * Frontend: PPOM_Meta::settings() and ::get_fields() return null/empty when
	 * the attached group is disabled, and recover when re-enabled. Attachments
	 * and the_meta JSON must survive the toggle.
	 *
	 * @return void
	 */
	public function test_disabled_group_is_hidden_from_frontend_resolver() {
		$product = $this->create_simple_product();
		$meta_id = $this->insert_ppom_meta(
			array(
				$this->build_text_field( 'shown_field', 'Shown' ),
			),
			$product->get_id()
		);

		// Sanity: enabled group resolves both settings and fields.
		$ppom = new PPOM_Meta( $product->get_id() );
		$this->assertTrue( $ppom->is_exists() );
		$this->assertNotNull( $ppom->settings() );
		$fields = $ppom->get_fields();
		$this->assertIsArray( $fields );
		$this->assertNotEmpty( $fields );

		// Disable: settings + fields go quiet, but the underlying row + product
		// attachment + the_meta JSON are untouched.
		ppom_meta_repository()->set_disabled( $meta_id, true );

		$ppom_disabled = new PPOM_Meta( $product->get_id() );
		$this->assertNull( $ppom_disabled->settings() );
		$this->assertEmpty( $ppom_disabled->get_fields() );

		$attached = get_post_meta( $product->get_id(), PPOM_PRODUCT_META_KEY, true );
		$this->assertNotEmpty( $attached, 'Disabling must not clear product attachment' );

		$row = ppom_meta_repository()->get_row_by_id( $meta_id );
		$this->assertNotEmpty( $row->the_meta, 'Disabling must not clear the_meta schema' );
		$decoded = json_decode( $row->the_meta, true );
		$this->assertIsArray( $decoded );
		$this->assertSame( 'shown_field', $decoded[0]['data_name'] ?? null );

		// Re-enable: same product → form comes back, identical to step 1.
		ppom_meta_repository()->set_disabled( $meta_id, false );

		$ppom_re_enabled = new PPOM_Meta( $product->get_id() );
		$this->assertNotNull( $ppom_re_enabled->settings() );
		$this->assertNotEmpty( $ppom_re_enabled->get_fields() );
	}

	/**
	 * Frontend: in the multi-meta path, disabling one of two attached groups
	 * removes its fields but keeps the other group's fields intact.
	 *
	 * @return void
	 */
	public function test_multi_meta_path_filters_only_disabled_rows() {
		$product = $this->create_simple_product();

		$meta_keep = $this->insert_ppom_meta(
			array( $this->build_text_field( 'keep_field', 'Keep' ) )
		);
		$meta_drop = $this->insert_ppom_meta(
			array( $this->build_text_field( 'drop_field', 'Drop' ) )
		);

		// Attach both groups via the multi-meta array shape used by Pro/by the
		// attach handler when more than one group is associated.
		update_post_meta(
			$product->get_id(),
			PPOM_PRODUCT_META_KEY,
			array( $meta_keep, $meta_drop )
		);

		ppom_meta_repository()->set_disabled( $meta_drop, true );

		$ppom = new PPOM_Meta( $product->get_id() );
		$this->assertTrue( $ppom->has_multiple_meta() );

		$data_names = array_map(
			static function ( $field ) {
				return isset( $field['data_name'] ) ? (string) $field['data_name'] : '';
			},
			(array) $ppom->get_fields()
		);

		$this->assertContains( 'keep_field', $data_names, 'Enabled group must still render' );
		$this->assertNotContains( 'drop_field', $data_names, 'Disabled group must be filtered out' );
	}
}
