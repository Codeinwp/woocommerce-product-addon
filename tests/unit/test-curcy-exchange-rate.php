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

	/**
	 * WOOCS-style switcher: converts on read and exposes back_convert(). The engine
	 * now reads the stored (store currency) price, so it must not back-convert it again.
	 */
	public function test_cart_line_with_woocs_style_switcher_is_converted_once() {
		global $WOOCS;
		$WOOCS = new class() {
			public $current_currency   = 'EUR';
			public $default_currency   = 'RON';
			public $is_multiple_allowed = true;
			public function get_currencies() {
				return array( 'EUR' => array( 'rate' => 10 ), 'RON' => array( 'rate' => 1 ) );
			}
			public function back_convert( $price, $rate ) {
				return $price / $rate;
			}
		};
		$exchange  = static function ( $price ) {
			return (float) $price * self::RATE;
		};
		$get_price = static function ( $price ) {
			return '' === $price ? $price : (float) $price * self::RATE;
		};
		add_filter( 'woocs_exchange_value', $exchange );
		add_filter( 'woocommerce_product_get_price', $get_price, 99 );

		$product = $this->create_simple_product( array( 'regular_price' => '10' ) );
		$this->insert_ppom_meta(
			array(
				array(
					'type'      => 'select',
					'title'     => 'Wrap',
					'data_name' => 'wrap',
					'options'   => array( array( 'option' => 'Premium', 'price' => '5', 'id' => 'premium' ) ),
				),
			),
			$product->get_id()
		);

		$this->initialize_woocommerce_checkout_context();
		$cart_key = $this->add_product_to_real_cart( $product->get_id(), array( 'fields' => array( 'wrap' => 'Premium' ) ) );
		$this->assertNotFalse( $cart_key );
		$this->reload_real_cart_from_session();

		WC()->cart->calculate_totals();
		$this->assertEqualsWithDelta( 150.0, (float) WC()->cart->get_total( 'edit' ), 0.001, '(10 + 5) x rate, base not back-converted twice.' );
		WC()->cart->calculate_totals();
		$this->assertEqualsWithDelta( 150.0, (float) WC()->cart->get_total( 'edit' ), 0.001 );

		remove_filter( 'woocs_exchange_value', $exchange );
		remove_filter( 'woocommerce_product_get_price', $get_price, 99 );
		$WOOCS = null;
	}

	/**
	 * The price matrix client payload feeds the product-page total, so its `price`
	 * carries one conversion, while `raw_price` stays raw for server-side cart pricing.
	 */
	public function test_pricematrix_client_payload_converts_price_once_and_keeps_raw_price() {
		$registry = new \PPOM\FieldMarkup\InputRendererRegistry( new \PPOM\FieldMarkup\FormAttributeContext() );
		$product  = $this->create_simple_product( array( 'regular_price' => '100' ) );
		$meta     = array( 'type' => 'pricematrix', 'title' => 'Matrix', 'data_name' => 'matrix' );
		$ranges   = Helpers::convert_options_to_key_val(
			array( array( 'option' => '1-5', 'price' => '50', 'id' => 'r1' ) ),
			$meta,
			$product
		);

		$html = $registry->render(
			'pricematrix',
			array(
				'id'                  => 'matrix',
				'type'                => 'pricematrix',
				'name'                => 'ppom[fields][matrix]',
				'label'               => 'Matrix',
				'ranges'              => $ranges,
				'discount'            => '',
				'show_slider'         => '',
				'qty_step'            => '1',
				'hide_matrix'         => '',
				'show_price_per_unit' => '',
			),
			null
		);

		$this->assertSame( 1, preg_match( '/name="ppom\[ppom_pricematrix\]"[^>]*value="([^"]*)"/', $html, $m ) );
		$payload = json_decode( html_entity_decode( $m[1], ENT_QUOTES ), true );
		$range   = reset( $payload );

		$this->assertEqualsWithDelta( 500.0, (float) $range['price'], 0.0001 );
		$this->assertEqualsWithDelta( 50.0, (float) $range['raw_price'], 0.0001 );
	}

	/**
	 * The shop-loop price range of a price-matrix product is display and converts once.
	 */
	public function test_catalog_price_matrix_range_is_converted_once() {
		$product = $this->create_simple_product( array( 'regular_price' => '100' ) );
		$this->insert_ppom_meta(
			array(
				$this->build_price_matrix_field(
					'matrix',
					array(
						array( 'option' => '1-5', 'price' => '50', 'id' => 'r1' ),
						array( 'option' => '6-10', 'price' => '40', 'id' => 'r2' ),
					)
				),
			),
			$product->get_id()
		);

		$html = \PPOM\WooCommerce\Catalog\CatalogHandler::alter_price( $product->get_price_html(), $product );

		$this->assertStringContainsString( '400.00', $html );
		$this->assertStringContainsString( '500.00', $html );
		$this->assertStringNotContainsString( '40.00', $html );
	}
}
