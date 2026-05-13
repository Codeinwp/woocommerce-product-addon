<?php
/**
 * Unit tests for PPOM\Cart\LegacyCartLinePricing::adjust_line_from_session.
 *
 * The "legacy" cart line pricer reads pricing decisions from the client-sent
 * `ppom_option_price` JSON payload (instead of recomputing from field meta).
 * It still runs for stores that haven't migrated to the modern pipeline, and
 * it's the entry point for session restore on those installs.
 *
 * @package ppom-pro
 */

require_once dirname( __DIR__, 2 ) . '/class-ppom-test-case.php';

use PPOM\Cart\LegacyCartLinePricing;

/**
 * @covers \PPOM\Cart\LegacyCartLinePricing
 */
class Test_Cart_LegacyCartLinePricing extends PPOM_Test_Case {

	/**
	 * Build a cart-item-shaped array around a real WC_Product so set_price can hit it.
	 *
	 * @return array<string, mixed>
	 */
	private function build_cart_item( $product, int $quantity, ?string $option_price_json, array $extra_fields = array() ): array {
		$ppom = array(
			'fields' => $extra_fields,
		);
		if ( null !== $option_price_json ) {
			$ppom['ppom_option_price'] = $option_price_json;
		}

		return array(
			'data'       => $product,
			'product_id' => $product->get_id(),
			'quantity'   => $quantity,
			'ppom'       => $ppom,
		);
	}

	/**
	 * No mutation happens for empty cart_items input.
	 *
	 * @return void
	 */
	public function test_empty_cart_items_returned_unchanged() {
		$result = LegacyCartLinePricing::adjust_line_from_session( array(), array( 'ppom' => array() ) );

		$this->assertSame( array(), $result );
	}

	/**
	 * If the session has no `ppom_option_price` payload, the cart line is untouched.
	 *
	 * @return void
	 */
	public function test_no_option_price_payload_does_not_change_product_price() {
		$product = $this->create_simple_product( array( 'regular_price' => '20' ) );
		$item    = $this->build_cart_item( $product, 1, null );

		LegacyCartLinePricing::adjust_line_from_session( $item, array( 'ppom' => array() ) );

		$this->assertEquals( 20.0, (float) $product->get_price() );
	}

	/**
	 * A variable option adds to the base — and falls back to the option's own price
	 * when there's no data_name/option_id (so server-side trust path is skipped).
	 *
	 * @return void
	 */
	public function test_variable_option_adds_to_base_price() {
		$product = $this->create_simple_product( array( 'regular_price' => '20' ) );

		$option_price = wp_json_encode(
			array(
				array(
					'apply' => 'variable',
					'price' => 5,
				),
				array(
					'apply' => 'variable',
					'price' => '2.50',
				),
			)
		);

		$item = $this->build_cart_item( $product, 1, $option_price );

		LegacyCartLinePricing::adjust_line_from_session( $item, $item );

		$this->assertEqualsWithDelta( 27.5, (float) $product->get_price(), 0.0001 );
	}

	/**
	 * Onetime rows are collected but NOT folded into the line total — they are
	 * surfaced as separate cart fees by ModernCartFeeApplicator / price_cart_fee
	 * on the cart layer. Pin this behavior: a onetime row on its own must leave
	 * the line price unchanged.
	 *
	 * @return void
	 */
	public function test_onetime_option_does_not_alter_line_price() {
		$product = $this->create_simple_product( array( 'regular_price' => '10' ) );

		$option_price = wp_json_encode(
			array(
				array(
					'apply' => 'onetime',
					'price' => 4,
				),
			)
		);

		$item = $this->build_cart_item( $product, 3, $option_price );

		LegacyCartLinePricing::adjust_line_from_session( $item, $item );

		$this->assertEqualsWithDelta( 10.0, (float) $product->get_price(), 0.0001 );
	}

	/**
	 * Fixed-price addons override the base price entirely and force qty=1.
	 *
	 * @return void
	 */
	public function test_fixedprice_option_overrides_base_price() {
		$product = $this->create_simple_product( array( 'regular_price' => '99' ) );

		$option_price = wp_json_encode(
			array(
				array(
					'apply'     => 'fixedprice',
					'price'     => 0,
					'unitprice' => 25,
				),
			)
		);

		$item = $this->build_cart_item( $product, 4, $option_price );

		LegacyCartLinePricing::adjust_line_from_session( $item, $item );

		$this->assertEqualsWithDelta( 25.0, (float) $product->get_price(), 0.0001 );
	}

	/**
	 * Measure rows multiply the base price (per the legacy `ppomm_measures`
	 * accumulator). 10 base × (qty 2 × multiplier 1.5) = 30.
	 *
	 * @return void
	 */
	public function test_measure_option_multiplies_base_price() {
		$product = $this->create_simple_product( array( 'regular_price' => '10' ) );

		$option_price = wp_json_encode(
			array(
				array(
					'apply'            => 'measure',
					'price'            => 0,
					'qty'              => 2,
					'price_multiplier' => '1.5',
				),
			)
		);

		$item = $this->build_cart_item( $product, 1, $option_price );

		LegacyCartLinePricing::adjust_line_from_session( $item, $item );

		$this->assertEqualsWithDelta( 30.0, (float) $product->get_price(), 0.0001 );
	}

	/**
	 * Quantities option uses each row's quantity to multiply per-unit price.
	 * Without `include=on`, the base price is zeroed out (legacy behavior).
	 *
	 * @return void
	 */
	public function test_quantities_option_zeroes_base_when_not_included() {
		$product = $this->create_simple_product( array( 'regular_price' => '15' ) );

		$option_price = wp_json_encode(
			array(
				array(
					'apply'    => 'quantities',
					'price'    => 7,
					'quantity' => 3,
				),
			)
		);

		$item = $this->build_cart_item( $product, 1, $option_price );

		LegacyCartLinePricing::adjust_line_from_session( $item, $item );

		// Base 15 → 0 (no `include`), quantities total 7*3 = 21.
		$this->assertEqualsWithDelta( 21.0, (float) $product->get_price(), 0.0001 );
	}

	/**
	 * When `include=on` is set on a quantities row, the base price is preserved.
	 *
	 * @return void
	 */
	public function test_quantities_option_preserves_base_when_include_flag_on() {
		$product = $this->create_simple_product( array( 'regular_price' => '15' ) );

		$option_price = wp_json_encode(
			array(
				array(
					'apply'    => 'quantities',
					'price'    => 7,
					'quantity' => 3,
					'include'  => 'on',
				),
			)
		);

		$item = $this->build_cart_item( $product, 1, $option_price );

		LegacyCartLinePricing::adjust_line_from_session( $item, $item );

		// Base 15 (kept) + 7*3 = 36.
		$this->assertEqualsWithDelta( 36.0, (float) $product->get_price(), 0.0001 );
	}

	/**
	 * The ppom_cart_line_total filter is the public hook callers extend — verify
	 * it runs with the computed total and can override it.
	 *
	 * @return void
	 */
	public function test_cart_line_total_filter_can_override_final_amount() {
		$product = $this->create_simple_product( array( 'regular_price' => '50' ) );

		$option_price = wp_json_encode(
			array(
				array(
					'apply' => 'variable',
					'price' => 5,
				),
			)
		);

		$item = $this->build_cart_item( $product, 1, $option_price );

		$filter = static function () {
			return 999.0;
		};
		add_filter( 'ppom_cart_line_total', $filter );

		try {
			LegacyCartLinePricing::adjust_line_from_session( $item, $item );
		} finally {
			remove_filter( 'ppom_cart_line_total', $filter );
		}

		$this->assertEqualsWithDelta( 999.0, (float) $product->get_price(), 0.0001 );
	}

	/**
	 * Bulkquantity rows multiply quantity × price and add the per-row base
	 * (used for batch pricing tiers). Without matrix, base stays.
	 *
	 * Legacy contract: `ppom_quantities_price = (price * qty) + base`,
	 * and product base price is zeroed because `ppom_quantities_include_base`
	 * is only flipped by the `quantities` apply mode (not bulkquantity).
	 *
	 * @return void
	 */
	public function test_bulkquantity_option_uses_price_times_quantity_plus_base() {
		$product = $this->create_simple_product( array( 'regular_price' => '100' ) );

		$option_price = wp_json_encode(
			array(
				array(
					'apply'    => 'bulkquantity',
					'price'    => 4,
					'quantity' => 5,
					'base'     => '3',
				),
			)
		);

		$item = $this->build_cart_item( $product, 5, $option_price );

		LegacyCartLinePricing::adjust_line_from_session( $item, $item );

		// 4*5 + 3 = 23. Base zeroed (no include flag).
		$this->assertEqualsWithDelta( 23.0, (float) $product->get_price(), 0.0001 );
	}
}
