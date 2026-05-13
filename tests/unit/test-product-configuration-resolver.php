<?php
/**
 * Tests for PPOM product configuration resolution (data layer).
 *
 * @package PPOM
 */

/**
 * @covers PPOM\Data\ProductConfigurationResolver
 */
class Test_Product_Configuration_Resolver extends PPOM_Test_Case {

	/**
	 * @return void
	 */
	public function test_match_categories_with_empty_rows_returns_empty_array(): void {
		$resolver = new \PPOM\Data\ProductConfigurationResolver();
		$this->assertSame( array(), $resolver->match_categories_for_product( 1, array() ) );
	}

	/**
	 * @return void
	 */
	public function test_resolve_meta_id_bundle_matches_legacy_ppom_meta_for_simple_product(): void {
		$product_id = self::factory()->post->create(
			array(
				'post_type' => 'product',
			)
		);

		$resolver = new \PPOM\Data\ProductConfigurationResolver();
		$bundle   = $resolver->resolve_meta_id_bundle( $product_id, array() );

		$legacy = new PPOM_Meta( $product_id );

		$this->assertSame( $legacy->meta_id, $bundle['meta_id'] );
		$this->assertSame( $legacy->category_meta, $bundle['category_meta'] );
	}
}
