<?php
/**
 * Hotpath: PPOM\Data\ProductConfigurationResolver — resolves which PPOM
 * field-group IDs apply to a product. Runs on every PPOM_Meta construction
 * (product page render, validation, pricing, cart restore).
 *
 * @package ppom-pro
 */

require_once __DIR__ . '/class-ppom-test-case.php';

/**
 * @covers PPOM\Data\ProductConfigurationResolver
 */
class Test_Hotpath_Resolver extends PPOM_Test_Case {

	/**
	 * Tracer: a product attached directly via _product_meta_id surfaces that
	 * ID through resolve_meta_id_bundle() with no category rows.
	 *
	 * @return void
	 */
	public function testResolveMetaIdBundleReturnsDirectlyAttachedFieldGroupId() {
		$product = $this->create_simple_product();
		$meta_id = $this->insert_ppom_meta(
			array( $this->build_text_field( 'engraving', 'Engraving' ) ),
			$product->get_id()
		);

		$resolver = new \PPOM\Data\ProductConfigurationResolver();
		$bundle   = $resolver->resolve_meta_id_bundle( $product->get_id(), array() );

		$this->assertSame( (string) $meta_id, (string) $bundle['meta_id'] );
		$this->assertSame( array(), $bundle['category_meta'] );
	}

	/**
	 * match_categories_for_product picks up rows whose productmeta_categories
	 * column lists a slug present on the product.
	 *
	 * @return void
	 */
	public function testMatchCategoriesForProductReturnsRowsWhoseCategorySlugMatchesProduct() {
		$term = wp_insert_term( 'PPOM Hotpath Slug ' . wp_generate_password( 6, false ), 'product_cat' );
		$this->assertIsArray( $term );

		$product   = $this->create_simple_product();
		$term_slug = get_term( $term['term_id'], 'product_cat' )->slug;
		wp_set_object_terms( $product->get_id(), array( (int) $term['term_id'] ), 'product_cat' );

		$rows = array(
			(object) array(
				'productmeta_id'         => 7001,
				'productmeta_categories' => $term_slug,
				'productmeta_tags'       => '',
			),
			(object) array(
				'productmeta_id'         => 7002,
				'productmeta_categories' => 'unrelated-slug',
				'productmeta_tags'       => '',
			),
		);

		$resolver = new \PPOM\Data\ProductConfigurationResolver();
		$matched  = $resolver->match_categories_for_product( $product->get_id(), $rows );

		$this->assertSame( array( 7001 ), $matched );
	}

	/**
	 * The literal string 'All' in productmeta_categories matches regardless of
	 * which category slug the product is assigned to.
	 *
	 * @return void
	 */
	public function testMatchCategoriesForProductMatchesAllWildcardRegardlessOfProductCategorySlug() {
		$term = wp_insert_term( 'PPOM Hotpath All ' . wp_generate_password( 6, false ), 'product_cat' );
		$this->assertIsArray( $term );

		$product = $this->create_simple_product();
		wp_set_object_terms( $product->get_id(), array( (int) $term['term_id'] ), 'product_cat' );

		$rows = array(
			(object) array(
				'productmeta_id'         => 8001,
				'productmeta_categories' => 'All',
				'productmeta_tags'       => '',
			),
		);

		$resolver = new \PPOM\Data\ProductConfigurationResolver();
		$matched  = $resolver->match_categories_for_product( $product->get_id(), $rows );

		$this->assertSame( array( 8001 ), $matched );
	}

	/**
	 * When a product has no _product_meta_id, the category-linked IDs become
	 * the resolved meta_id list.
	 *
	 * @return void
	 */
	public function testMergeMetaIdsFallsBackToCategoryIdsWhenProductHasNoDirectAttachment() {
		$product = $this->create_simple_product();

		$resolver  = new \PPOM\Data\ProductConfigurationResolver();
		$resolved  = $resolver->merge_meta_ids_from_product_and_categories( $product->get_id(), array( 9101, 9102 ) );

		$this->assertSame( array( 9101, 9102 ), $resolved );
	}

	/**
	 * The ppom_meta_overrides='category_override' filter replaces direct meta
	 * with the category-linked IDs even when the product has _product_meta_id.
	 *
	 * @return void
	 */
	public function testCategoryOverrideFilterReplacesDirectMetaIdsWithCategoryIds() {
		$product = $this->create_simple_product();
		update_post_meta( $product->get_id(), PPOM_PRODUCT_META_KEY, '5500' );

		$override = function () {
			return 'category_override';
		};
		add_filter( 'ppom_meta_overrides', $override );

		try {
			$resolver = new \PPOM\Data\ProductConfigurationResolver();
			$resolved = $resolver->merge_meta_ids_from_product_and_categories( $product->get_id(), array( 9201 ) );
		} finally {
			remove_filter( 'ppom_meta_overrides', $override );
		}

		$this->assertSame( array( 9201 ), $resolved );
	}

	/**
	 * With ppom_meta_overrides='individual_override', a directly-attached meta
	 * ID wins over category matches.
	 *
	 * @return void
	 */
	public function testIndividualOverrideFilterPreservesDirectMetaIdsWhenAttached() {
		$product = $this->create_simple_product();
		update_post_meta( $product->get_id(), PPOM_PRODUCT_META_KEY, '5600' );

		$override = function () {
			return 'individual_override';
		};
		add_filter( 'ppom_meta_overrides', $override );

		try {
			$resolver = new \PPOM\Data\ProductConfigurationResolver();
			$resolved = $resolver->merge_meta_ids_from_product_and_categories( $product->get_id(), array( 9301 ) );
		} finally {
			remove_filter( 'ppom_meta_overrides', $override );
		}

		$this->assertSame( '5600', (string) $resolved );
	}
}
