<?php
/**
 * With WPML Multicurrency (WCML) and no PPOM Pro running,
 * cart option prices were added in store currency to a base WCML had
 * already converted.
 *
 * WCML converts product prices when the product meta is read, so the cart line
 * product already carries the client-currency base. These tests create the
 * product at that converted amount and convert option rows with a fake rate.
 *
 * @package PPOM
 */

require_once __DIR__ . '/class-ppom-test-case.php';

class Test_Wcml_Cart_Option_Conversion extends PPOM_Test_Case {

	const RATE = 10;

	/** @var callable(float|int|string): float */
	private $wcml_converter;

	/** @var object|null */
	private $previous_woocommerce_wpml;

	public function setUp(): void {
		parent::setUp();

		$this->wcml_converter = static function ( $price ): float {
			return (float) $price * self::RATE;
		};
		add_filter( 'wcml_raw_price_amount', $this->wcml_converter );

		$this->previous_woocommerce_wpml = $GLOBALS['woocommerce_wpml'] ?? null;
		$this->set_wcml_loads_product_prices( true );
	}

	public function tearDown(): void {
		remove_filter( 'wcml_raw_price_amount', $this->wcml_converter );
		remove_filter( 'ppom_option_price', 'ppom_hooks_convert_price', 99 );

		if ( null === $this->previous_woocommerce_wpml ) {
			unset( $GLOBALS['woocommerce_wpml'] );
		} else {
			$GLOBALS['woocommerce_wpml'] = $this->previous_woocommerce_wpml;
		}

		// Restore the registration the plugin made at boot (PPOM Pro is not loaded in tests).
		PPOM()->maybe_register_option_price_hook();

		parent::tearDown();
	}

	/**
	 * Stands in for WCML's multi-currency object, which reports whether product
	 * prices are converted in the current request.
	 */
	private function set_wcml_loads_product_prices( bool $loads ): void {
		$GLOBALS['woocommerce_wpml'] = (object) array(
			'multi_currency' => new class( $loads ) {
				/** @var bool */
				public $loads_prices;

				public function __construct( bool $loads_prices ) {
					$this->loads_prices = $loads_prices;
				}

				public function are_filters_need_loading(): bool {
					return $this->loads_prices;
				}
			},
		);
	}

	/**
	 * Adds a product whose catalog price is the WCML-converted base, with one
	 * priced option, and returns the cart total with that option chosen.
	 *
	 * @param array{price: string, discount?: string} $premium Pricing of the chosen option.
	 */
	private function cart_total_for_option( array $premium, string $type = 'select' ): float {
		$product = $this->create_simple_product( array( 'regular_price' => (string) ( 10 * self::RATE ) ) );
		$this->insert_ppom_meta(
			array(
				array(
					'type'      => $type,
					'title'     => 'Wrap',
					'data_name' => 'wrap',
					'options'   => array(
						array( 'option' => 'None', 'price' => '', 'id' => 'none' ),
						array( 'option' => 'Premium', 'id' => 'premium' ) + $premium,
					),
				),
			),
			$product->get_id()
		);

		$this->initialize_woocommerce_checkout_context();
		$cart_key = $this->add_product_to_real_cart( $product->get_id(), array( 'fields' => array( 'wrap' => 'checkbox' === $type ? array( 'Premium' ) : 'Premium' ) ) );
		$this->assertNotFalse( $cart_key );
		$this->reload_real_cart_from_session();

		WC()->cart->calculate_totals();
		$first_pass = (float) WC()->cart->get_total( 'edit' );

		WC()->cart->calculate_totals();
		$this->assertEqualsWithDelta( $first_pass, (float) WC()->cart->get_total( 'edit' ), 0.001, 'A second totals pass must not change the line.' );

		return $first_pass;
	}

	public function test_cart_line_adds_option_in_client_currency_without_pro() {
		$this->assertEqualsWithDelta( 150.0, $this->cart_total_for_option( array( 'price' => '5' ) ), 0.001, 'Converted base 100 + option 5 x rate.' );
	}

	public function test_percent_option_keeps_amount_taken_from_converted_base() {
		$this->assertEqualsWithDelta( 110.0, $this->cart_total_for_option( array( 'price' => '10%' ) ), 0.001, '10% of the converted base 100, not converted again.' );
	}

	public function test_checkbox_percent_discount_keeps_amount_taken_from_converted_base() {
		$total = $this->cart_total_for_option( array( 'price' => '20', 'discount' => '10%' ), 'checkbox' );

		$this->assertEqualsWithDelta( 110.0, $total, 0.001, 'The 10% discount price comes from the converted base, not converted again.' );
	}

	public function test_one_time_fee_row_is_left_to_the_fee_path() {
		$rows = PPOM()->convert_cart_rows_wcml(
			array(
				array( 'apply' => 'addon', 'price' => 5, 'is_percent' => false ),
				array( 'apply' => 'cart_fee', 'price' => 5, 'is_percent' => false ),
			)
		);

		$this->assertEqualsWithDelta( 50.0, (float) $rows[0]['price'], 0.0001 );
		$this->assertEqualsWithDelta( 5.0, (float) $rows[1]['price'], 0.0001, 'One-time fees are converted when the fee is added.' );
	}

	public function test_rows_stay_in_store_currency_when_wcml_does_not_convert_product_prices() {
		$this->set_wcml_loads_product_prices( false );

		$rows = PPOM()->convert_cart_rows_wcml( array( array( 'apply' => 'addon', 'price' => 5, 'is_percent' => false ) ) );

		$this->assertEqualsWithDelta( 5.0, (float) $rows[0]['price'], 0.0001, 'The base stays in store currency on this request, so the option must too.' );
	}

	public function test_rows_untouched_without_wcml() {
		unset( $GLOBALS['woocommerce_wpml'] );

		$rows = PPOM()->convert_cart_rows_wcml( array( array( 'apply' => 'addon', 'price' => 5, 'is_percent' => false ) ) );

		$this->assertEqualsWithDelta( 5.0, (float) $rows[0]['price'], 0.0001 );
	}

	public function test_cart_pass_not_registered_when_pro_converts_the_rows() {
		$ppom = PPOM();
		remove_filter( 'ppom_fields_prices', array( $ppom, 'convert_cart_rows_wcml' ) );
		add_filter( 'ppom_option_price', 'ppom_hooks_convert_price', 99, 1 );

		$ppom->maybe_register_option_price_hook();

		$this->assertFalse( has_filter( 'ppom_fields_prices', array( $ppom, 'convert_cart_rows_wcml' ) ), 'PPOM Pro converts the rows itself; converting here too would apply the rate twice.' );
	}
}
