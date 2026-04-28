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
	 * Test product instance.
	 *
	 * @var WC_Product_Simple
	 */
	private $product;

	/**
	 * Original WooCommerce tax settings.
	 *
	 * @var array
	 */
	private $original_tax_settings = array();

	/**
	 * Set up test fixtures before each test.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->original_tax_settings = array(
			'woocommerce_calc_taxes'          => get_option( 'woocommerce_calc_taxes' ),
			'woocommerce_tax_display_shop'    => get_option( 'woocommerce_tax_display_shop' ),
			'woocommerce_tax_display_cart'    => get_option( 'woocommerce_tax_display_cart' ),
			'woocommerce_prices_include_tax'  => get_option( 'woocommerce_prices_include_tax' ),
		);

		if ( class_exists( 'WC_Product_Simple' ) ) {
			$this->product = new WC_Product_Simple();
			$this->product->set_regular_price( 100 );
			$this->product->set_tax_class( 'standard' );
			$this->product->set_tax_status( 'taxable' );
			$this->product->save();
		}
	}

	/**
	 * Set up a tax rate for testing.
	 *
	 * @param float $rate Tax rate percentage.
	 */
	private function setup_tax_rate( $rate ) {
		global $wpdb;

		$wpdb->insert(
			$wpdb->prefix . 'woocommerce_tax_rates',
			array(
				'tax_rate_country'  => '',
				'tax_rate_state'    => '',
				'tax_rate'          => $rate,
				'tax_rate_name'     => 'VAT',
				'tax_rate_priority' => 1,
				'tax_rate_compound' => 0,
				'tax_rate_shipping' => 1,
				'tax_rate_order'    => 1,
				'tax_rate_class'    => '',
			)
		);
	}

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

	/**
	 * Test that addon price does not include tax when ppom_taxable_option_price
	 * setting has never been saved and WC tax is disabled.
	 *
	 * @return void
	 */
	public function test_option_price_handle_vat_no_tax_when_setting_unset_and_wc_tax_disabled() {
		if ( ! $this->product ) {
			$this->markTestSkipped( 'WooCommerce not available.' );
		}

		delete_option( 'ppom_taxable_option_price' );
		update_option( 'woocommerce_calc_taxes', 'no' );

		$base_addon_price = 10.00;

		$result_price = Engine::option_price_handle_vat( $base_addon_price, $this->product );

		$this->assertEquals(
			$base_addon_price,
			$result_price
		);
	}

	/**
	 * Test that addon price includes tax when WC tax is enabled.
	 *
	 * @return void
	 */
	public function test_option_price_handle_vat_includes_tax_when_wc_tax_enabled() {
		if ( ! $this->product ) {
			$this->markTestSkipped( 'WooCommerce not available.' );
		}

		// Enable WooCommerce tax calculation
		update_option( 'woocommerce_calc_taxes', 'yes' );
		update_option( 'woocommerce_tax_display_shop', 'incl' );
		update_option( 'woocommerce_prices_include_tax', 'no' );

		$this->setup_tax_rate( 20 );

		$base_addon_price = 10.00;
		$expected_tax_rate = 0.20;
		$expected_price_with_tax = $base_addon_price * ( 1 + $expected_tax_rate );

		$this->go_to( get_permalink( $this->product->get_id() ) );

		$result_price = Engine::option_price_handle_vat( $base_addon_price, $this->product );

		$this->assertGreaterThan(
			$base_addon_price,
			$result_price
		);

		$delta = 0.01;
		$this->assertEqualsWithDelta(
			$expected_price_with_tax,
			$result_price,
			$delta
		);
	}

	/**
	 * Test that tax display is context-aware (product page vs cart).
	 *
	 * Verifies that tax handling respects woocommerce_tax_display_cart setting in cart,
	 * and woocommerce_tax_display_shop on product pages.
	 *
	 * @return void
	 */
	public function test_option_price_handle_vat_context_aware_display() {
		if ( ! $this->product ) {
			$this->markTestSkipped( 'WooCommerce not available.' );
		}

		// Enable WooCommerce tax calculation
		update_option( 'woocommerce_calc_taxes', 'yes' );
		update_option( 'woocommerce_prices_include_tax', 'no' );
		update_option( 'ppom_taxable_option_price', 'yes' );

		$this->setup_tax_rate( 20 );

		$base_addon_price = 10.00;

		update_option( 'woocommerce_tax_display_shop', 'incl' );
		$this->go_to( get_permalink( $this->product->get_id() ) );
		$product_page_price = Engine::option_price_handle_vat( $base_addon_price, $this->product );

		$this->assertGreaterThan(
			$base_addon_price,
			$product_page_price
		);

		update_option( 'woocommerce_tax_display_cart', 'excl' );
		$this->go_to( wc_get_cart_url() );
		$cart_price = Engine::option_price_handle_vat( $base_addon_price, $this->product );

		$this->assertEquals(
			$base_addon_price,
			$cart_price
		);

		update_option( 'woocommerce_tax_display_cart', 'incl' );
		$cart_price_incl = Engine::option_price_handle_vat( $base_addon_price, $this->product );

		$this->assertGreaterThan(
			$base_addon_price,
			$cart_price_incl
		);
	}
}
