<?php
/**
 * Hotpath: PPOM\Pricing\Engine — addon resolution and summation helpers
 * that run on every cart total recalculation.
 *
 * @package ppom-pro
 */

require_once __DIR__ . '/class-ppom-test-case.php';

class Test_Hotpath_Pricing extends PPOM_Test_Case {

	/**
	 * Tracer: a select field with a flat addon price resolves to a single
	 * 'addon' line through get_field_prices().
	 *
	 * @return void
	 */
	public function testGetFieldPricesResolvesSelectOptionToSingleAddonLine() {
		$product = $this->create_simple_product( array( 'regular_price' => '10' ) );

		$this->insert_ppom_meta(
			array(
				$this->build_select_field(
					'plan',
					'Plan',
					array(
						array(
							'option' => 'Premium',
							'price'  => '5',
						),
					)
				),
			),
			$product->get_id()
		);

		$posted   = array( 'plan' => 'Premium' );
		$quantity = 1.0;

		$prices = \PPOM\Pricing\Engine::get_field_prices( $posted, $product->get_id(), $quantity, 0 );

		$this->assertIsArray( $prices );
		$this->assertCount( 1, $prices );
		$this->assertSame( 'select', $prices[0]['type'] );
		$this->assertSame( 'plan', $prices[0]['data_name'] );
		$this->assertSame( 'addon', $prices[0]['apply'] );
		$this->assertEqualsWithDelta( 5.0, (float) $prices[0]['price'], 0.0001 );
	}

	/**
	 * price_get_addon_total sums price × quantity only for entries flagged 'addon'
	 * (cart_fee and weight lines must be ignored).
	 *
	 * @return void
	 */
	public function testPriceGetAddonTotalSumsAddonEntriesAndIgnoresOtherApplyModes() {
		$price_array = array(
			array(
				'price'    => '5',
				'quantity' => 2,
				'apply'    => 'addon',
			),
			array(
				'price'    => '7',
				'quantity' => 1,
				'apply'    => 'cart_fee',
			),
			array(
				'price'    => '3.5',
				'quantity' => 4,
				'apply'    => 'addon',
			),
			array(
				'price'    => '99',
				'apply'    => 'weight',
			),
		);

		$total = \PPOM\Pricing\Engine::price_get_addon_total( $price_array );

		$this->assertEqualsWithDelta( 5 * 2 + 3.5 * 4, (float) $total, 0.0001 );
	}

	/**
	 * price_get_cart_fee_total sums the price column for cart_fee entries only.
	 * Quantity does NOT multiply cart_fee lines (this mirrors how fees are applied
	 * once per cart instead of once per unit).
	 *
	 * @return void
	 */
	public function testPriceGetCartFeeTotalSumsOnlyCartFeeEntriesWithoutMultiplyingByQuantity() {
		$price_array = array(
			array(
				'price'    => '7',
				'quantity' => 3,
				'apply'    => 'cart_fee',
			),
			array(
				'price'    => '5',
				'quantity' => 2,
				'apply'    => 'addon',
			),
			array(
				'price'    => '2.25',
				'quantity' => 10,
				'apply'    => 'cart_fee',
			),
		);

		$total = \PPOM\Pricing\Engine::price_get_cart_fee_total( $price_array );

		$this->assertEqualsWithDelta( 7 + 2.25, (float) $total, 0.0001 );
	}

	/**
	 * get_amount_after_percentage strips the trailing '%' and returns the correct
	 * fraction of the base amount. Many option types route through this helper for
	 * percent-based pricing.
	 *
	 * @return void
	 */
	public function testGetAmountAfterPercentageReturnsBaseAmountFractionFromPercentString() {
		$this->assertEqualsWithDelta(
			15.0,
			(float) \PPOM\Pricing\Engine::get_amount_after_percentage( 100, '15%' ),
			0.0001
		);

		$this->assertEqualsWithDelta(
			2.5,
			(float) \PPOM\Pricing\Engine::get_amount_after_percentage( 50, '5%' ),
			0.0001
		);
	}

	/**
	 * price_is_matrix_found short-circuits with null when the product has no
	 * pricematrix field, so callers can skip the matrix path entirely.
	 *
	 * @return void
	 */
	public function testPriceIsMatrixFoundReturnsNullWhenProductHasNoPriceMatrix() {
		$product = $this->create_simple_product( array( 'regular_price' => '20' ) );

		$this->insert_ppom_meta(
			array(
				$this->build_text_field( 'engraving', 'Engraving' ),
			),
			$product->get_id()
		);

		$result = \PPOM\Pricing\Engine::price_is_matrix_found( $product, 1, 20.0, 0.0, 0.0 );

		$this->assertNull( $result );
	}

	/**
	 * is_field_has_price reports true when at least one select option carries a
	 * non-empty price, false when every option is priceless. This gate is the
	 * cheap pre-check that skips the price loop for catalog-only fields.
	 *
	 * @return void
	 */
	public function testIsFieldHasPriceDetectsAtLeastOneOptionWithPrice() {
		$with_price = array(
			'type'    => 'select',
			'options' => array(
				array( 'option' => 'A', 'price' => '' ),
				array( 'option' => 'B', 'price' => '5' ),
			),
		);

		$without_price = array(
			'type'    => 'select',
			'options' => array(
				array( 'option' => 'A', 'price' => '' ),
				array( 'option' => 'B', 'price' => '' ),
			),
		);

		$this->assertTrue( \PPOM\Pricing\Engine::is_field_has_price( $with_price ) );
		$this->assertFalse( \PPOM\Pricing\Engine::is_field_has_price( $without_price ) );
	}
}
