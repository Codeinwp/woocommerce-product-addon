<?php
/**
 * Regression tests for issue #755: with a currency switcher active, PPOM applied
 * the exchange rate to option prices more than once.
 *
 * @package PPOM
 */

require_once __DIR__ . '/class-ppom-test-case.php';

use PPOM\Support\Helpers;

class Test_Curcy_Exchange_Rate extends PPOM_Test_Case {

	const RATE = 10;

	/** @var callable */
	private $converter;

	public function setUp(): void {
		parent::setUp();
		// A CURCY-style integration: converts on ppom_option_price at priority 10.
		$this->converter = static function ( $price ) {
			return is_numeric( $price ) && '' !== $price ? (float) $price * self::RATE : $price;
		};
		add_filter( 'ppom_option_price', $this->converter, 10 );
	}

	public function tearDown(): void {
		remove_filter( 'ppom_option_price', $this->converter, 10 );
		parent::tearDown();
	}

	/**
	 * Normalized options keep the stored amount; consumers convert once themselves.
	 * The label is display and is converted exactly once.
	 */
	public function test_normalized_option_price_stays_unconverted_while_label_converts_once() {
		$product = $this->create_simple_product( array( 'regular_price' => '100' ) );
		$meta    = array( 'type' => 'select', 'title' => 'Wrap', 'data_name' => 'wrap' );

		$options = Helpers::convert_options_to_key_val(
			array( array( 'option' => 'Premium', 'price' => '50', 'id' => 'premium' ) ),
			$meta,
			$product
		);

		$this->assertEqualsWithDelta( 50.0, (float) $options['Premium']['price'], 0.0001 );
		$this->assertStringContainsString( '500.00', $options['Premium']['label'] );
		$this->assertStringNotContainsString( '5,000.00', $options['Premium']['label'] );
	}

	/**
	 * CURCY 2.2.16+ ships its own PPOM integration on ppom_option_price. Core's
	 * converter must then leave the price alone instead of converting a second time.
	 */
	public function test_core_converter_yields_to_curcy_integration_hook() {
		if ( ! class_exists( 'WOOMULTI_CURRENCY_F_Plugin_Woocommerce_Product_Addon' ) ) {
			eval( 'class WOOMULTI_CURRENCY_F_Plugin_Woocommerce_Product_Addon {}' );
		}
		if ( ! function_exists( 'wmc_get_price' ) ) {
			eval( 'function wmc_get_price( $price ) { return (float) $price * ' . self::RATE . '; }' );
		}

		$ppom = PPOM();
		remove_filter( 'ppom_option_price', array( $ppom, 'ppom_convert_price' ), 99 );
		$ppom->maybe_register_option_price_hook();

		$result = apply_filters( 'ppom_option_price', 50 );

		remove_filter( 'ppom_option_price', array( $ppom, 'ppom_convert_price' ), 99 );

		$this->assertEqualsWithDelta( 500.0, (float) $result, 0.0001, 'Rate must be applied once, not by CURCY and core both.' );
	}

	/**
	 * Cart with CURCY: PPOM must write the line in store currency (base + raw option),
	 * because the switcher converts the whole line when WooCommerce reads it, and the
	 * total must not grow on repeated totals passes.
	 */
	public function test_cart_line_is_converted_once_and_stable_across_totals_passes() {
		// CURCY converts every product price on read and hands PPOM the raw base.
		$get_price = static function ( $price ) {
			return '' === $price ? $price : (float) $price * self::RATE;
		};
		add_filter( 'woocommerce_product_get_price', $get_price, 99 );
		add_filter(
			'ppom_product_price_on_cart',
			static function ( $value, $cart_item ) {
				return $cart_item['data']->get_price( 'edit' );
			},
			10,
			2
		);

		$product = $this->create_simple_product( array( 'regular_price' => '10' ) );
		$this->insert_ppom_meta(
			array(
				array(
					'type'      => 'select',
					'title'     => 'Wrap',
					'data_name' => 'wrap',
					'options'   => array(
						array( 'option' => 'None', 'price' => '', 'id' => 'none' ),
						array( 'option' => 'Premium', 'price' => '5', 'id' => 'premium' ),
					),
				),
			),
			$product->get_id()
		);

		$this->initialize_woocommerce_checkout_context();
		$cart_key = $this->add_product_to_real_cart( $product->get_id(), array( 'fields' => array( 'wrap' => 'Premium' ) ) );
		$this->assertNotFalse( $cart_key );
		$this->reload_real_cart_from_session();

		WC()->cart->calculate_totals();
		$this->assertEqualsWithDelta( 150.0, (float) WC()->cart->get_total( 'edit' ), 0.001, 'First pass: (10 + 5) x rate.' );

		WC()->cart->calculate_totals();
		$this->assertEqualsWithDelta( 150.0, (float) WC()->cart->get_total( 'edit' ), 0.001, 'Second pass must not add the option again.' );

		remove_filter( 'woocommerce_product_get_price', $get_price, 99 );
	}

	/**
	 * Legacy input renderers (ppom_enable_legacy_inputs_rendering) receive the same
	 * normalized options and must emit a data-price converted exactly once.
	 */
	public function test_legacy_renderers_emit_data_price_converted_once() {
		$registry = new \PPOM\FieldMarkup\InputRendererRegistry( new \PPOM\FieldMarkup\FormAttributeContext() );
		$product  = $this->create_simple_product( array( 'regular_price' => '100' ) );

		foreach ( array( 'select', 'radio', 'checkbox', 'palettes' ) as $type ) {
			$meta    = array( 'type' => $type, 'title' => 'Wrap', 'data_name' => 'wrap' );
			$options = Helpers::convert_options_to_key_val(
				array( array( 'option' => 'Premium', 'price' => '50', 'id' => 'premium', 'color' => '#fff' ) ),
				$meta,
				$product
			);

			$html = $registry->render(
				$type,
				array(
					'id'         => 'wrap',
					'type'       => $type,
					'name'       => 'ppom[fields][wrap]',
					'title'      => 'Wrap',
					'data_name'  => 'wrap',
					'onetime'    => '',
					'taxable'    => '',
					'classes'    => '',
					'attributes' => array(),
					'color_width' => '30',
					'color_height' => '30',
					'options'    => $options,
					'product_id' => $product->get_id(),
				),
				null
			);

			$this->assertStringContainsString( 'data-price="500"', $html, "{$type}: data-price must carry one conversion." );
		}
	}
}
