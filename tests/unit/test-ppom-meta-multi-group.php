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
	 * get_fields() skips stale meta_id entries for the current request while
	 * preserving the surviving group — and never rewrites the stored
	 * assignment, because $this->meta_id is the display-time resolution
	 * (direct + category + ppom_product_meta_id filter), not the stored
	 * value (#686).
	 *
	 * @return void
	 */
	public function test_get_fields_skips_stale_reference_without_persisting() {
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

		$fields = (array) $ppom->get_fields();
		$data_names = array_map(
			static function ( $field ) {
				return isset( $field['data_name'] ) ? (string) $field['data_name'] : '';
			},
			$fields
		);
		$this->assertContains( 'keep_field', $data_names );

		// The stale id is dropped from the in-memory resolution only.
		$this->assertSame( array( $valid_meta_id ), array_values( (array) $ppom->meta_id ) );

		// The stored assignment is untouched — front-end reads must not write.
		$stored = get_post_meta( $product->get_id(), PPOM_PRODUCT_META_KEY, true );
		$this->assertSame(
			array( $valid_meta_id, $stale_meta_id ),
			array_values( array_map( 'intval', (array) $stored ) )
		);
	}

	/**
	 * A display-time override may replace the product's stored assignment.
	 * Resolving its fields must not persist that temporary set.
	 *
	 * @return void
	 */
	public function test_get_fields_does_not_persist_runtime_override() {
		$product          = $this->create_simple_product();
		$stored_meta_id   = $this->insert_ppom_meta(
			array( $this->build_text_field( 'stored_field', 'Stored' ) )
		);
		$override_meta_id = $this->insert_ppom_meta(
			array( $this->build_text_field( 'override_field', 'Override' ) )
		);

		update_post_meta( $product->get_id(), PPOM_PRODUCT_META_KEY, array( $stored_meta_id ) );

		$filter = static function () use ( $override_meta_id ) {
			return array( $override_meta_id, 0 );
		};
		add_filter( 'ppom_product_meta_id', $filter, 99 );

		try {
			$ppom = new PPOM_Meta( $product->get_id() );
		} finally {
			remove_filter( 'ppom_product_meta_id', $filter, 99 );
		}

		$this->assertSame( array( $override_meta_id ), array_values( (array) $ppom->meta_id ) );
		$this->assertSame(
			array( $stored_meta_id ),
			array_values( array_map( 'intval', (array) get_post_meta( $product->get_id(), PPOM_PRODUCT_META_KEY, true ) ) )
		);
		$this->assertContains( 'override_field', array_column( (array) $ppom->fields, 'data_name' ) );
	}

	/**
	 * A category group merged with a stale direct assignment remains
	 * display-only and must not become the product's direct assignment.
	 *
	 * @return void
	 */
	public function test_get_fields_does_not_persist_category_group_over_stale_assignment() {
		$term = wp_insert_term( 'PPOM Category ' . wp_generate_password( 6, false ), 'product_cat' );
		$this->assertIsArray( $term );

		$product   = $this->create_simple_product();
		$term_slug = get_term( $term['term_id'], 'product_cat' )->slug;
		wp_set_object_terms( $product->get_id(), array( (int) $term['term_id'] ), 'product_cat' );

		$category_meta_id = $this->insert_ppom_meta(
			array( $this->build_text_field( 'category_field', 'Category' ) ),
			0,
			array( 'productmeta_categories' => $term_slug )
		);
		$stale_meta_id = 999999;
		update_post_meta( $product->get_id(), PPOM_PRODUCT_META_KEY, array( $stale_meta_id ) );

		$ppom = new PPOM_Meta( $product->get_id() );

		$this->assertSame( array( $category_meta_id ), array_values( (array) $ppom->meta_id ) );
		$this->assertContains( 'category_field', array_column( (array) $ppom->fields, 'data_name' ) );
		$this->assertSame(
			array( $stale_meta_id ),
			array_values( array_map( 'intval', (array) get_post_meta( $product->get_id(), PPOM_PRODUCT_META_KEY, true ) ) )
		);
	}

	/**
	 * get_fields() must not delete the product's post meta when every attached
	 * meta_id fails to resolve: an empty read is indistinguishable from a
	 * transient DB failure (#679), and the resolved set may not be the stored
	 * assignment at all (#686). It renders nothing for the request instead.
	 *
	 * @return void
	 */
	public function test_get_fields_keeps_post_meta_when_all_references_are_stale() {
		$product = $this->create_simple_product();

		update_post_meta(
			$product->get_id(),
			PPOM_PRODUCT_META_KEY,
			array( 9999991, 9999992 )
		);

		$ppom   = new PPOM_Meta( $product->get_id() );
		$fields = $ppom->get_fields();

		$this->assertSame( array(), (array) $fields );

		$this->assertTrue(
			metadata_exists( 'post', $product->get_id(), PPOM_PRODUCT_META_KEY )
		);
		$this->assertSame(
			array( 9999991, 9999992 ),
			array_values( array_map( 'intval', (array) get_post_meta( $product->get_id(), PPOM_PRODUCT_META_KEY, true ) ) )
		);
	}

	/**
	 * get_fields() must NOT delete the product's assignment when the field-group
	 * read fails transiently (DB under load, lock/timeout, dropped connection).
	 * A failed read returns the same empty result as "rows deleted", and the
	 * cleanup used to treat both as "group gone" and permanently drop the
	 * product↔group link on a single front-end page load.
	 *
	 * @see https://github.com/Codeinwp/woocommerce-product-addon/issues/679
	 *
	 * @return void
	 */
	public function test_get_fields_keeps_assignment_when_group_read_fails_transiently() {
		global $wpdb;

		$product = $this->create_simple_product();

		$meta_a = $this->insert_ppom_meta(
			array( $this->build_text_field( 'a_field', 'A' ) )
		);
		$meta_b = $this->insert_ppom_meta(
			array( $this->build_text_field( 'b_field', 'B' ) )
		);

		update_post_meta(
			$product->get_id(),
			PPOM_PRODUCT_META_KEY,
			array( $meta_a, $meta_b )
		);

		$ppom_table  = \PPOM\Data\FieldGroupRepository::instance()->table_name();
		$break_reads = static function ( $query ) use ( $ppom_table ) {
			// Point SELECTs on the PPOM table at a table that does not exist so
			// they error out, mimicking a transient read failure.
			if ( 0 === stripos( ltrim( $query ), 'SELECT' ) && false !== strpos( $query, $ppom_table ) ) {
				return str_replace( $ppom_table, $ppom_table . '_missing_for_test', $query );
			}

			return $query;
		};

		add_filter( 'query', $break_reads );
		$suppress = $wpdb->suppress_errors( true );

		try {
			$ppom = new PPOM_Meta( $product->get_id() );
			// The form cannot render this request…
			$this->assertSame( array(), (array) $ppom->fields );
		} finally {
			remove_filter( 'query', $break_reads );
			$wpdb->suppress_errors( $suppress );
		}

		// …but the assignment must survive the failed read.
		$stored = get_post_meta( $product->get_id(), PPOM_PRODUCT_META_KEY, true );
		$this->assertIsArray( $stored );
		$this->assertSame(
			array( $meta_a, $meta_b ),
			array_values( array_map( 'intval', $stored ) )
		);

		// Once reads recover, the form comes back on its own.
		$ppom       = new PPOM_Meta( $product->get_id() );
		$data_names = array_column( (array) $ppom->fields, 'data_name' );
		$this->assertContains( 'a_field', $data_names );
		$this->assertContains( 'b_field', $data_names );
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
