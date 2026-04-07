<?php
/**
 * Unit tests for PPOM\WooCommerce\Cart\CartHandler helpers.
 *
 * @package ppom-pro
 */

use PPOM\WooCommerce\Cart\CartHandler;

/**
 * @covers \PPOM\WooCommerce\Cart\CartHandler
 */
class Test_Cart_Handler extends PPOM_Test_Case {

	/**
	 * @return void
	 */
	public function test_legacy_option_prices_aggregate_display_quantity_sums_when_include_empty() {
		$qty = CartHandler::legacy_option_prices_aggregate_display_quantity(
			array(
				array( 'include' => '', 'quantity' => 2 ),
				array( 'include' => '', 'quantity' => 3 ),
			)
		);

		$this->assertSame( 5, $qty );
	}

	/**
	 * @return void
	 */
	public function test_legacy_option_prices_aggregate_display_quantity_resets_to_one_when_include_on() {
		$qty = CartHandler::legacy_option_prices_aggregate_display_quantity(
			array(
				array( 'include' => '', 'quantity' => 4 ),
				array( 'include' => 'on', 'quantity' => 99 ),
			)
		);

		$this->assertSame( 1, $qty );
	}

	/**
	 * @return void
	 */
	public function test_should_skip_cart_meta_for_empty_display_text_types_only_strict_empty_string() {
		$this->assertTrue( CartHandler::should_skip_cart_meta_for_empty_display( 'text', '' ) );
		$this->assertFalse( CartHandler::should_skip_cart_meta_for_empty_display( 'text', '0' ) );
		$this->assertFalse( CartHandler::should_skip_cart_meta_for_empty_display( 'number', 0 ) );
	}

	/**
	 * @return void
	 */
	public function test_should_skip_cart_meta_for_empty_display_other_types_use_empty() {
		$this->assertTrue( CartHandler::should_skip_cart_meta_for_empty_display( 'select', '' ) );
		$this->assertTrue( CartHandler::should_skip_cart_meta_for_empty_display( 'select', '0' ) );
		$this->assertFalse( CartHandler::should_skip_cart_meta_for_empty_display( 'select', 'ok' ) );
	}

	/**
	 * @return void
	 */
	public function test_legacy_option_prices_matrix_context_starts_from_cart_quantity() {
		$ctx = CartHandler::legacy_option_prices_matrix_context( array(), 3 );

		$this->assertSame( 0, $ctx['total_quantities'] );
		$this->assertSame( 3.0, $ctx['matrix_order_qty'] );
	}

	/**
	 * @return void
	 */
	public function test_legacy_option_prices_matrix_context_accumulates_quantities_apply_rows() {
		$ctx = CartHandler::legacy_option_prices_matrix_context(
			array(
				array( 'apply' => 'quantities', 'quantity' => 2 ),
				array( 'apply' => 'quantities', 'quantity' => 5 ),
			),
			1
		);

		$this->assertSame( 7, $ctx['total_quantities'] );
		$this->assertSame( 7, $ctx['matrix_order_qty'] );
	}

	/**
	 * @return void
	 */
	public function test_apply_price_matrix_chunk_replaces_org_when_not_discount_matrix() {
		$out = CartHandler::apply_price_matrix_chunk_to_line_components(
			array( 'price' => '42.00' ),
			100,
			0,
			0,
			0,
			false,
			2
		);

		$this->assertTrue( $out['replace_org_price'] );
		$this->assertSame( '42.00', $out['org_price'] );
		$this->assertSame( 0, $out['discount_delta'] );
	}

	/**
	 * @return void
	 */
	public function test_apply_price_matrix_chunk_percent_discount_on_base_only() {
		$out = CartHandler::apply_price_matrix_chunk_to_line_components(
			array(
				'discount' => 'base',
				'percent'  => '10%',
			),
			100,
			50,
			0,
			25,
			false,
			1
		);

		$this->assertFalse( $out['replace_org_price'] );
		$this->assertEqualsWithDelta( 12.5, (float) $out['discount_delta'], 0.0001 );
	}

	/**
	 * @return void
	 */
	public function test_apply_price_matrix_chunk_percent_discount_on_both_includes_options_and_quantities() {
		$out = CartHandler::apply_price_matrix_chunk_to_line_components(
			array(
				'discount' => 'both',
				'percent'  => '10%',
			),
			100,
			20,
			5,
			15,
			false,
			1
		);

		$this->assertFalse( $out['replace_org_price'] );
		$this->assertEqualsWithDelta( 14.0, (float) $out['discount_delta'], 0.0001 );
	}

	/**
	 * @return void
	 */
	public function test_apply_price_matrix_chunk_fixed_discount_without_usebaseprice() {
		$out = CartHandler::apply_price_matrix_chunk_to_line_components(
			array(
				'discount' => 'yes',
				'price'    => 8,
			),
			100,
			0,
			0,
			0,
			false,
			4
		);

		$this->assertFalse( $out['replace_org_price'] );
		$this->assertSame( 8, $out['discount_delta'] );
	}

	/**
	 * @return void
	 */
	public function test_apply_price_matrix_chunk_fixed_discount_divides_by_order_qty_with_usebaseprice() {
		$out = CartHandler::apply_price_matrix_chunk_to_line_components(
			array(
				'discount' => 'yes',
				'price'    => 10,
			),
			100,
			0,
			0,
			0,
			true,
			2
		);

		$this->assertFalse( $out['replace_org_price'] );
		$this->assertSame( 5.0, (float) $out['discount_delta'] );
	}

	/**
	 * @return void
	 */
	public function test_apply_legacy_quantities_exclude_base_adjustment_zeros_base_and_scales_options() {
		$adj = CartHandler::apply_legacy_quantities_exclude_base_adjustment( 10, false, 50, 3, 4 );

		$this->assertSame( 0, $adj['ppom_item_org_price'] );
		$this->assertSame( 12, $adj['total_option_price'] );
	}

	/**
	 * @return void
	 */
	public function test_apply_legacy_quantities_exclude_base_adjustment_noop_when_base_included() {
		$adj = CartHandler::apply_legacy_quantities_exclude_base_adjustment( 10, true, 50, 3, 4 );

		$this->assertSame( 50, $adj['ppom_item_org_price'] );
		$this->assertSame( 3, $adj['total_option_price'] );
	}

	/**
	 * @return void
	 */
	public function test_apply_legacy_measure_multiplier_to_org_price() {
		$this->assertSame( 20, CartHandler::apply_legacy_measure_multiplier_to_org_price( 10, 2 ) );
		$this->assertSame( 10, CartHandler::apply_legacy_measure_multiplier_to_org_price( 10, 1 ) );
	}

	/**
	 * @return void
	 */
	public function test_legacy_cart_line_total_from_components() {
		$this->assertEquals(
			115,
			CartHandler::legacy_cart_line_total_from_components( 100, 20, 5, 10 )
		);
	}

	/**
	 * @param array<string, mixed> $overrides State overrides.
	 *
	 * @return array<string, mixed>
	 */
	private function legacy_pricing_state( array $overrides = array() ) {
		return array_merge(
			array(
				'ppom_item_org_price'          => 100,
				'ppom_item_order_qty'          => 2,
				'total_option_price'           => 0,
				'ppon_onetime_cost'            => 0,
				'ppom_quantities_price'        => 0,
				'ppom_quantities_usebaseprice' => false,
				'ppom_quantities_include_base' => false,
				'ppomm_measures'               => 1,
			),
			$overrides
		);
	}

	/**
	 * @return void
	 */
	public function test_accumulate_legacy_option_price_row_variable_adds_resolved_price() {
		$ctx = array(
			'matrix_found'        => array(),
			'option_prices'       => array(),
			'resolved_line_price' => 4.5,
		);

		$next = CartHandler::accumulate_legacy_option_price_row(
			array( 'apply' => 'variable', 'price' => 999 ),
			$this->legacy_pricing_state(),
			$ctx
		);

		$this->assertEqualsWithDelta( 4.5, (float) $next['total_option_price'], 0.0001 );
	}

	/**
	 * @return void
	 */
	public function test_accumulate_legacy_option_price_row_onetime_adds_resolved_price() {
		$next = CartHandler::accumulate_legacy_option_price_row(
			array( 'apply' => 'onetime', 'price' => 0 ),
			$this->legacy_pricing_state(),
			array(
				'option_prices'       => array(),
				'resolved_line_price' => 12,
			)
		);

		$this->assertEqualsWithDelta( 12.0, (float) $next['ppon_onetime_cost'], 0.0001 );
	}

	/**
	 * @return void
	 */
	public function test_accumulate_legacy_option_price_row_quantities_uses_matrix_price_when_non_discount_matrix() {
		$next = CartHandler::accumulate_legacy_option_price_row(
			array(
				'apply'    => 'quantities',
				'price'    => 1,
				'quantity' => 3,
			),
			$this->legacy_pricing_state(),
			array(
				'matrix_found'  => array( 'price' => 10 ),
				'option_prices' => array(),
			)
		);

		$this->assertEqualsWithDelta( 30.0, (float) $next['ppom_quantities_price'], 0.0001 );
	}

	/**
	 * @return void
	 */
	public function test_accumulate_legacy_option_price_row_quantities_ignores_matrix_when_discount_matrix() {
		$next = CartHandler::accumulate_legacy_option_price_row(
			array(
				'apply'    => 'quantities',
				'price'    => 4,
				'quantity' => 2,
			),
			$this->legacy_pricing_state(),
			array(
				'matrix_found'  => array(
					'price'    => 99,
					'discount' => 'both',
				),
				'option_prices' => array(),
			)
		);

		$this->assertEqualsWithDelta( 8.0, (float) $next['ppom_quantities_price'], 0.0001 );
	}

	/**
	 * @return void
	 */
	public function test_accumulate_legacy_option_price_row_quantities_respects_filter_to_skip_option_price() {
		add_filter( 'ppom_quantities_use_option_price', '__return_false' );

		$next = CartHandler::accumulate_legacy_option_price_row(
			array(
				'apply'    => 'quantities',
				'price'    => 99,
				'quantity' => 5,
			),
			$this->legacy_pricing_state(),
			array(
				'matrix_found'  => array(),
				'option_prices' => array( array( 'apply' => 'quantities' ) ),
			)
		);

		remove_filter( 'ppom_quantities_use_option_price', '__return_false' );

		$this->assertSame( 0, (int) $next['ppom_quantities_price'] );
	}

	/**
	 * @return void
	 */
	public function test_accumulate_legacy_option_price_row_quantities_sets_include_base_flag() {
		$next = CartHandler::accumulate_legacy_option_price_row(
			array(
				'apply'    => 'quantities',
				'price'    => 1,
				'quantity' => 1,
				'include'  => 'on',
			),
			$this->legacy_pricing_state(),
			array(
				'option_prices' => array(),
			)
		);

		$this->assertTrue( $next['ppom_quantities_include_base'] );
	}

	/**
	 * @return void
	 */
	public function test_accumulate_legacy_option_price_row_bulkquantity_adds_base_and_usebase_flag() {
		$next = CartHandler::accumulate_legacy_option_price_row(
			array(
				'apply'          => 'bulkquantity',
				'price'          => 2,
				'quantity'       => 3,
				'base'           => 1.5,
				'usebase_price'  => 'yes',
			),
			$this->legacy_pricing_state(),
			array( 'option_prices' => array() )
		);

		$this->assertEqualsWithDelta( 7.5, (float) $next['ppom_quantities_price'], 0.0001 );
		$this->assertTrue( $next['ppom_quantities_usebaseprice'] );
	}

	/**
	 * @return void
	 */
	public function test_accumulate_legacy_option_price_row_fixedprice_overrides_org_and_order_qty() {
		$next = CartHandler::accumulate_legacy_option_price_row(
			array(
				'apply'      => 'fixedprice',
				'unitprice'  => 55,
			),
			$this->legacy_pricing_state( array( 'ppom_item_org_price' => 20 ) ),
			array( 'option_prices' => array() )
		);

		$this->assertSame( 55, (int) $next['ppom_item_org_price'] );
		$this->assertSame( 1, (int) $next['ppom_item_order_qty'] );
	}

	/**
	 * @return void
	 */
	public function test_accumulate_legacy_option_price_row_measure_multiplies_accumulator() {
		$next = CartHandler::accumulate_legacy_option_price_row(
			array(
				'apply'             => 'measure',
				'qty'               => 2,
				'price_multiplier'  => 3,
			),
			$this->legacy_pricing_state(),
			array( 'option_prices' => array() )
		);

		$this->assertSame( 6.0, (float) $next['ppomm_measures'] );
	}

	/**
	 * @return void
	 */
	public function test_accumulate_legacy_option_price_row_noop_for_unknown_apply() {
		$before = $this->legacy_pricing_state( array( 'total_option_price' => 9 ) );
		$next   = CartHandler::accumulate_legacy_option_price_row(
			array( 'apply' => 'custom_future_type' ),
			$before,
			array( 'option_prices' => array() )
		);

		$this->assertSame( $before, $next );
	}

	/**
	 * @return void
	 */
	public function test_accumulate_legacy_option_price_row_noop_when_apply_missing() {
		$before = $this->legacy_pricing_state();
		$next   = CartHandler::accumulate_legacy_option_price_row(
			array( 'price' => 5 ),
			$before,
			array( 'option_prices' => array() )
		);

		$this->assertSame( $before, $next );
	}
}
