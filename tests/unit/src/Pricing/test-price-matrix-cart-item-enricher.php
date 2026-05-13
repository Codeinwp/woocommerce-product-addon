<?php
/**
 * Unit tests for PPOM\Pricing\PriceMatrixCartItemEnricher::enrich_from_session.
 *
 * The enricher attaches `ppom.price_matrix_found` to the cart item on session
 * restore. It's the bridge between PPOM field meta and ModernLineItemPricing.
 *
 * @package ppom-pro
 */

require_once dirname( __DIR__, 2 ) . '/class-ppom-test-case.php';

use PPOM\Pricing\PriceMatrixCartItemEnricher;

/**
 * @covers \PPOM\Pricing\PriceMatrixCartItemEnricher
 */
class Test_Pricing_PriceMatrixCartItemEnricher extends PPOM_Test_Case {

	/**
	 * Cart items without a `ppom` key short-circuit and pass through unchanged.
	 *
	 * @return void
	 */
	public function test_cart_items_without_ppom_key_are_returned_unchanged() {
		$product = $this->create_simple_product();
		$item    = array(
			'data'       => $product,
			'product_id' => $product->get_id(),
			'quantity'   => 1,
		);

		$result = PriceMatrixCartItemEnricher::enrich_from_session( $item, $item );

		$this->assertSame( $item, $result );
		$this->assertArrayNotHasKey( 'ppom', $result );
	}

	/**
	 * When the product has no pricematrix field, the item is returned unchanged
	 * (no `price_matrix_found` injected).
	 *
	 * @return void
	 */
	public function test_no_pricematrix_field_does_not_inject_match() {
		$product = $this->create_simple_product();
		$this->insert_ppom_meta(
			array(
				$this->build_text_field( 'engraving', 'Engraving' ),
			),
			$product->get_id()
		);

		$item = array(
			'data'       => $product,
			'product_id' => $product->get_id(),
			'quantity'   => 1,
			'ppom'       => array(
				'fields'               => array(),
				'conditionally_hidden' => '',
			),
		);

		$result = PriceMatrixCartItemEnricher::enrich_from_session( $item, $item );

		$this->assertArrayNotHasKey( 'price_matrix_found', $result['ppom'] );
	}

	/**
	 * With a pricematrix field present, the matched field row is attached to
	 * `ppom.price_matrix_found`. Default value when nothing is hidden is the
	 * first pricematrix row.
	 *
	 * @return void
	 */
	public function test_pricematrix_field_is_attached_to_cart_item() {
		$product = $this->create_simple_product();

		$matrix_field = $this->build_price_matrix_field(
			'matrix_a',
			array(
				array( 'option' => '1-10', 'price' => '5' ),
				array( 'option' => '11-20', 'price' => '4' ),
			)
		);

		$this->insert_ppom_meta( array( $matrix_field ), $product->get_id() );

		$item = array(
			'data'       => $product,
			'product_id' => $product->get_id(),
			'quantity'   => 1,
			'ppom'       => array(
				'fields'               => array(),
				'conditionally_hidden' => '',
			),
		);

		$result = PriceMatrixCartItemEnricher::enrich_from_session( $item, $item );

		$this->assertArrayHasKey( 'price_matrix_found', $result['ppom'] );
		$this->assertSame( 'matrix_a', $result['ppom']['price_matrix_found']['data_name'] );
	}

	/**
	 * If the matrix field is in the conditionally_hidden list, it is skipped
	 * and no match is recorded.
	 *
	 * @return void
	 */
	public function test_conditionally_hidden_matrix_field_is_skipped() {
		$product = $this->create_simple_product();

		$matrix_field = $this->build_price_matrix_field(
			'matrix_a',
			array( array( 'option' => '1-10', 'price' => '5' ) )
		);

		$this->insert_ppom_meta( array( $matrix_field ), $product->get_id() );

		$item = array(
			'data'       => $product,
			'product_id' => $product->get_id(),
			'quantity'   => 1,
			'ppom'       => array(
				'fields'               => array(),
				'conditionally_hidden' => 'matrix_a',
			),
		);

		$result = PriceMatrixCartItemEnricher::enrich_from_session( $item, $item );

		$this->assertArrayHasKey( 'price_matrix_found', $result['ppom'] );
		$this->assertSame( array(), $result['ppom']['price_matrix_found'] );
	}

	/**
	 * The ppom_price_marix_found filter can override the matched value.
	 *
	 * (Yes, the upstream filter name has the misspelling — pinned here so we
	 * don't accidentally "fix" the constant and break Pro extensions.)
	 *
	 * @return void
	 */
	public function test_filter_can_override_attached_match() {
		$product = $this->create_simple_product();

		$this->insert_ppom_meta(
			array( $this->build_price_matrix_field( 'matrix_a', array() ) ),
			$product->get_id()
		);

		$item = array(
			'data'       => $product,
			'product_id' => $product->get_id(),
			'quantity'   => 1,
			'ppom'       => array(
				'fields'               => array(),
				'conditionally_hidden' => '',
			),
		);

		$override = array( 'data_name' => 'filter-injected' );
		$filter   = static function () use ( $override ) {
			return $override;
		};
		add_filter( 'ppom_price_marix_found', $filter );

		try {
			$result = PriceMatrixCartItemEnricher::enrich_from_session( $item, $item );
		} finally {
			remove_filter( 'ppom_price_marix_found', $filter );
		}

		$this->assertSame( $override, $result['ppom']['price_matrix_found'] );
	}
}
