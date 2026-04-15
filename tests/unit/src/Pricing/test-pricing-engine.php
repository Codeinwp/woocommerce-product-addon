<?php
/**
 * Unit tests for PPOM\Pricing\Engine pure static helpers.
 *
 * @package ppom-pro
 */

use PPOM\Pricing\Engine;

/**
 * @covers \PPOM\Pricing\Engine
 */
class Test_Pricing_Engine extends WP_UnitTestCase {

	/**
	 * @return void
	 */
	public function test_price_get_addon_total_sums_addon_rows_with_quantity() {
		$total = Engine::price_get_addon_total(
			array(
				array(
					'apply'    => 'addon',
					'price'    => 5,
					'quantity' => 2,
				),
				array(
					'apply'    => 'addon',
					'price'    => '3.5',
					'quantity' => 1,
				),
			)
		);

		$this->assertEquals( 13.5, $total );
	}

	/**
	 * @return void
	 */
	public function test_price_get_addon_total_skips_non_addon_and_missing_price() {
		$total = Engine::price_get_addon_total(
			array(
				array(
					'apply'    => 'cart_fee',
					'price'    => 99,
					'quantity' => 1,
				),
				array(
					'apply'    => 'addon',
					'quantity' => 5,
				),
			)
		);

		$this->assertEquals( 0, $total );
	}

	/**
	 * @return void
	 */
	public function test_price_get_addon_total_empty_input() {
		$this->assertEquals( 0, Engine::price_get_addon_total( array() ) );
		$this->assertEquals( 0, Engine::price_get_addon_total( null ) );
	}

	/**
	 * @return void
	 */
	public function test_price_get_cart_fee_total_only_cart_fee_apply() {
		$total = Engine::price_get_cart_fee_total(
			array(
				array( 'apply' => 'cart_fee', 'price' => 2.5 ),
				array( 'apply' => 'addon', 'price' => 100 ),
			)
		);

		$this->assertSame( 2.5, $total );
	}

	/**
	 * @return void
	 */
	public function test_price_get_cart_fee_total_empty() {
		$this->assertSame( 0, Engine::price_get_cart_fee_total( array() ) );
	}

	/**
	 * @return void
	 */
	public function test_get_amount_after_percentage_strips_percent_suffix() {
		$amount = Engine::get_amount_after_percentage( 200, '15%' );
		$expect = wc_format_decimal( 30, wc_get_price_decimals() );
		$this->assertEquals( $expect, $amount );
	}

	/**
	 * @return void
	 */
	public function test_is_field_has_price_true_when_option_price_set() {
		$has = Engine::is_field_has_price(
			array(
				'type'    => 'select',
				'options' => array(
					array( 'option' => 'A', 'price' => '5' ),
				),
			)
		);

		$this->assertTrue( $has );
	}

	/**
	 * @return void
	 */
	public function test_is_field_has_price_false_when_no_prices() {
		$has = Engine::is_field_has_price(
			array(
				'type'    => 'select',
				'options' => array(
					array( 'option' => 'A', 'price' => '' ),
				),
			)
		);

		$this->assertFalse( $has );
	}

	/**
	 * @return void
	 */
	public function test_is_field_has_price_file_uses_file_cost() {
		$this->assertTrue(
			Engine::is_field_has_price(
				array(
					'type'      => 'file',
					'file_cost' => '10',
				)
			)
		);
		$this->assertFalse(
			Engine::is_field_has_price(
				array(
					'type'      => 'file',
					'file_cost' => '',
				)
			)
		);
	}

	/**
	 * @return void
	 */
	public function test_is_field_has_price_quantities_default_price() {
		$this->assertTrue(
			Engine::is_field_has_price(
				array(
					'type'           => 'quantities',
					'default_price'  => '1',
					'options'        => array(),
				)
			)
		);
	}

	/**
	 * @return void
	 */
	public function test_price_get_total_fixedprice_aggregates_type() {
		$data = Engine::price_get_total_fixedprice(
			array(
				array(
					'type'     => 'fixedprice',
					'price'    => 12,
					'quantity' => 2,
				),
				array(
					'type'     => 'text',
					'price'    => 50,
					'quantity' => 9,
				),
			)
		);

		$this->assertSame( 2, $data['quantity'] );
		$this->assertSame( 12, $data['base_price'] );
	}

	/**
	 * @return void
	 */
	public function test_price_get_total_measure_multiplies_rows() {
		$total = Engine::price_get_total_measure(
			array(
				array(
					'type'     => 'measure',
					'quantity' => 2,
				),
				array(
					'type'     => 'measure',
					'quantity' => 3,
				),
			)
		);

		$this->assertEquals( 6, $total );
	}

	/**
	 * @return void
	 */
	public function test_price_get_total_measure_applies_price_multiplier() {
		$total = Engine::price_get_total_measure(
			array(
				array(
					'type'             => 'measure',
					'quantity'         => 4,
					'price-multiplier' => '0.5',
				),
			)
		);

		$this->assertSame( 2.0, $total );
	}
}
