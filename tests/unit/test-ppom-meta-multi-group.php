<?php
/**
 * Class Test_PPOM_Meta_Multi_Group
 *
 *
 * @package ppom-pro
 */

require_once __DIR__ . '/class-ppom-test-case.php';

class Test_PPOM_Meta_Multi_Group extends PPOM_Test_Case {

	/**
	 * inline_css() loops every attached group and emits each row's own
	 * stylesheet keyed by `.ppom-id-<row id>`.
	 *
	 * @return void
	 */
	public function test_inline_css_aggregates_from_all_attached_groups() {
		$product = $this->create_simple_product();

		$meta_a = $this->insert_ppom_meta(
			array( $this->build_text_field( 'a_field', 'A' ) ),
			0,
			array( 'productmeta_style' => 'selector .title { color: red; }' )
		);
		$meta_b = $this->insert_ppom_meta(
			array( $this->build_text_field( 'b_field', 'B' ) ),
			0,
			array( 'productmeta_style' => 'selector .title { color: blue; }' )
		);

		update_post_meta(
			$product->get_id(),
			PPOM_PRODUCT_META_KEY,
			array( $meta_a, $meta_b )
		);

		$ppom = new PPOM_Meta( $product->get_id() );
		$this->assertTrue( $ppom->has_multiple_meta() );

		$css = $ppom->inline_css();

		$this->assertStringContainsString( '.ppom-id-' . $meta_a . ' .title { color: red; }', $css );
		$this->assertStringContainsString( '.ppom-id-' . $meta_b . ' .title { color: blue; }', $css );
		// No legacy combined-where selector should be emitted.
		$this->assertStringNotContainsString( ':where(', $css );
	}

	/**
	 * inline_css() with a single attached group uses the row's own productmeta_id
	 * (not the post-meta value), guarding against post-meta drift.
	 *
	 * @return void
	 */
	public function test_inline_css_uses_row_productmeta_id_in_single_meta_mode() {
		$product = $this->create_simple_product();
		$meta_id = $this->insert_ppom_meta(
			array( $this->build_text_field( 'only_field', 'Only' ) ),
			$product->get_id(),
			array( 'productmeta_style' => 'selector .wrap { display: block; }' )
		);

		$ppom = new PPOM_Meta( $product->get_id() );
		$this->assertFalse( $ppom->has_multiple_meta() );

		$css = $ppom->inline_css();

		$this->assertSame(
			'.ppom-id-' . $meta_id . ' .wrap { display: block; }',
			$css
		);
	}

	/**
	 * inline_js() loops every attached group and concatenates each row's JS,
	 * stripping slashes (previously only the primary row was emitted).
	 *
	 * @return void
	 */
	public function test_inline_js_aggregates_from_all_attached_groups() {
		$product = $this->create_simple_product();

		$meta_a = $this->insert_ppom_meta(
			array( $this->build_text_field( 'a_field', 'A' ) ),
			0,
			array( 'productmeta_js' => "console.log('a');" )
		);
		$meta_b = $this->insert_ppom_meta(
			array( $this->build_text_field( 'b_field', 'B' ) ),
			0,
			array( 'productmeta_js' => "console.log('b');" )
		);

		update_post_meta(
			$product->get_id(),
			PPOM_PRODUCT_META_KEY,
			array( $meta_a, $meta_b )
		);

		$ppom = new PPOM_Meta( $product->get_id() );

		$js = $ppom->inline_js();

		$this->assertStringContainsString( "console.log('a');", $js );
		$this->assertStringContainsString( "console.log('b');", $js );
	}

	/**
	 * get_fields() removes stale meta_id entries from the product's post meta
	 * when the underlying row is gone, while preserving the surviving group.
	 *
	 * @return void
	 */
	public function test_get_fields_cleans_up_stale_meta_id_reference() {
		$product = $this->create_simple_product();

		$valid_meta_id  = $this->insert_ppom_meta(
			array( $this->build_text_field( 'keep_field', 'Keep' ) )
		);
		$stale_meta_id  = 999999; // No row with this id exists.

		update_post_meta(
			$product->get_id(),
			PPOM_PRODUCT_META_KEY,
			array( $valid_meta_id, $stale_meta_id )
		);

		$ppom = new PPOM_Meta( $product->get_id() );
		$this->assertTrue( $ppom->has_multiple_meta() );

		// Triggering the field resolver runs the cleanup.
		$fields = (array) $ppom->get_fields();
		$data_names = array_map(
			static function ( $field ) {
				return isset( $field['data_name'] ) ? (string) $field['data_name'] : '';
			},
			$fields
		);
		$this->assertContains( 'keep_field', $data_names );

		$stored = get_post_meta( $product->get_id(), PPOM_PRODUCT_META_KEY, true );
		$this->assertIsArray( $stored );
		$this->assertSame( array( $valid_meta_id ), array_values( array_map( 'intval', $stored ) ) );
	}

	/**
	 * get_fields() deletes the product's post meta entirely when every
	 * attached meta_id references a row that no longer exists.
	 *
	 * @return void
	 */
	public function test_get_fields_deletes_post_meta_when_all_references_are_stale() {
		$product = $this->create_simple_product();

		update_post_meta(
			$product->get_id(),
			PPOM_PRODUCT_META_KEY,
			array( 9999991, 9999992 )
		);

		$ppom = new PPOM_Meta( $product->get_id() );
		$ppom->get_fields();

		$this->assertSame(
			'',
			(string) get_post_meta( $product->get_id(), PPOM_PRODUCT_META_KEY, true )
		);
	}

	/**
	 * single_meta_id() must not crash when meta_id ends up as an associative
	 * array (e.g. preserved keys from a Pro filter on `ppom_product_meta_id`).
	 * The legacy `$arr[0]` access raised a PHP notice / fatal under strict
	 * configs when the array had no zero key.
	 *
	 * @return void
	 */
	public function test_single_meta_id_uses_reset_for_associative_arrays() {
		$product = $this->create_simple_product();
		$meta_id = $this->insert_ppom_meta(
			array( $this->build_text_field( 'only_field', 'Only' ) ),
			$product->get_id()
		);

		$filter = static function ( $value ) use ( $meta_id ) {
			// Return an associative array with no zero key — the shape the fix targets.
			return array(
				'category_assigned_a' => $meta_id,
				'category_assigned_b' => $meta_id,
			);
		};
		add_filter( 'ppom_product_meta_id', $filter, 99 );

		try {
			$ppom = new PPOM_Meta( $product->get_id() );
			$this->assertSame( $meta_id, (int) $ppom->single_meta_id() );
		} finally {
			remove_filter( 'ppom_product_meta_id', $filter, 99 );
		}
	}

	/**
	 * PPOM_Form::ppom_fields_render() must keep fields that lack a `ppom_id`
	 * key — they originate from groups imported (or saved) before the field
	 * stamper added `ppom_id` to every entry. Fields that DO carry a ppom_id
	 * must still match the rendered group strictly.
	 *
	 * The branch fix adds an `isset($field['ppom_id'])` short-circuit in the
	 * array_filter so legacy entries are not accidentally hidden when a
	 * product has multiple groups attached.
	 *
	 * @return void
	 */
	public function test_form_render_keeps_imported_fields_without_ppom_id() {
		$product = $this->create_simple_product();

		$meta_a = $this->insert_ppom_meta(
			array( $this->build_text_field( 'a_field', 'Modern A' ) )
		);
		$meta_b = $this->insert_ppom_meta(
			array( $this->build_text_field( 'b_field', 'Modern B' ) )
		);

		// Group A normally carries `ppom_id` on every field (stamped at save).
		// Inject a legacy field into A's the_meta directly so the row contains
		// one entry with `ppom_id` and one without — mirroring an imported group.
		$row_a    = ppom_meta_repository()->get_row_by_id( $meta_a );
		$fields_a = json_decode( $row_a->the_meta, true );
		$this->assertIsArray( $fields_a );
		$this->assertArrayHasKey( 'ppom_id', $fields_a[0] );

		$fields_a[] = array(
			'type'      => 'text',
			'title'     => 'Legacy Imported',
			'data_name' => 'legacy_imported_field',
			// Intentionally NO `ppom_id` key.
		);
		ppom_meta_repository()->update_the_meta_only( $meta_a, wp_json_encode( $fields_a ) );

		update_post_meta(
			$product->get_id(),
			PPOM_PRODUCT_META_KEY,
			array( $meta_a, $meta_b )
		);

		$form = new PPOM_Form( $product, array() );
		ob_start();
		$form->ppom_fields_render( $meta_a );
		$output = ob_get_clean();

		// Modern A field belongs to the rendered group → kept.
		$this->assertStringContainsString( 'data-data_name=a_field', $output );
		// Legacy field has no ppom_id → kept under the backwards-compat branch.
		$this->assertStringContainsString( 'data-data_name=legacy_imported_field', $output );
		// Modern B field belongs to a different group → must be filtered out.
		$this->assertStringNotContainsString( 'data-data_name=b_field', $output );
	}
}
