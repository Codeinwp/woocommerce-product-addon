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

use PPOM\WooCommerce\Cart\CartHandler;

class Test_Wcml_Cart_Option_Conversion extends PPOM_Test_Case {

	const RATE = 10;

	/** @var callable(float|int|string): float */
	private $wcml_converter;

	private ?object $previous_woocommerce_wpml = null;

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
	 * prices are converted in the current request and converts them back.
	 */
	private function set_wcml_loads_product_prices( bool $loads ): void {
		$GLOBALS['woocommerce_wpml'] = (object) array(
			'multi_currency' => new class( $loads ) {
				public bool $loads_prices;

				public object $prices;

				public function __construct( bool $loads_prices ) {
					$this->loads_prices = $loads_prices;
					$this->prices       = new class() {
						/**
						 * @param float $amount Client-currency amount.
						 */
						public function unconvert_price_amount( float $amount ): float {
							return $amount / Test_Wcml_Cart_Option_Conversion::RATE;
						}
					};
				}

				public function are_filters_need_loading(): bool {
					return $this->loads_prices;
				}
			},
		);
	}

	/**
	 * Adds a product whose catalog price is the WCML-converted base, with the
	 * option under test and a flat 5 gift option, and returns the cart total with
	 * both chosen. The gift adds 50 only when option rows are converted.
	 *
	 * @param array{price: string, discount?: string} $premium Pricing of the chosen option.
	 * @param array{onetime?: 'on'}                  $field   Extra field settings.
	 */
	private function cart_total_for_option( array $premium, string $type = 'select', array $field = array() ): float {
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
				) + $field,
				array(
					'type'      => 'select',
					'title'     => 'Gift',
					'data_name' => 'gift',
					'options'   => array( array( 'option' => 'Card', 'price' => '5', 'id' => 'card' ) ),
				),
			),
			$product->get_id()
		);

		$this->initialize_woocommerce_checkout_context();
		$posted   = array(
			'wrap' => 'checkbox' === $type ? array( 'Premium' ) : 'Premium',
			'gift' => 'Card',
		);
		$cart_key = $this->add_product_to_real_cart( $product->get_id(), array( 'fields' => $posted ) );
		$this->assertNotFalse( $cart_key );
		$this->reload_real_cart_from_session();

		WC()->cart->calculate_totals();
		$first_pass = (float) WC()->cart->get_total( 'edit' );

		WC()->cart->calculate_totals();
		$this->assertEqualsWithDelta( $first_pass, (float) WC()->cart->get_total( 'edit' ), 0.001, 'A second totals pass must not change the line.' );

		return $first_pass;
	}

	public function test_cart_line_adds_option_in_client_currency_without_pro(): void {
		$this->assertEqualsWithDelta( 200.0, $this->cart_total_for_option( array( 'price' => '5' ) ), 0.001, 'Converted base 100 + option 5 x rate + gift 5 x rate.' );
	}

	public function test_percent_option_keeps_amount_taken_from_converted_base(): void {
		$this->assertEqualsWithDelta( 160.0, $this->cart_total_for_option( array( 'price' => '10%' ) ), 0.001, 'Base 100 + 10% of it, not converted again + gift 50.' );
	}

	public function test_checkbox_percent_discount_keeps_amount_taken_from_converted_base(): void {
		$total = $this->cart_total_for_option( array( 'price' => '20', 'discount' => '10%' ), 'checkbox' );

		$this->assertEqualsWithDelta( 160.0, $total, 0.001, 'Base 100 + the 10% discount price of it, not converted again + gift 50.' );
	}

	public function test_one_time_fee_is_converted_once(): void {
		$total = $this->cart_total_for_option( array( 'price' => '5' ), 'select', array( 'onetime' => 'on' ) );

		$this->assertEqualsWithDelta( 200.0, $total, 0.001, 'Base 100 + fee 5 x rate + gift 50.' );
	}

	public function test_percent_one_time_fee_keeps_amount_taken_from_converted_base(): void {
		$total = $this->cart_total_for_option( array( 'price' => '10%' ), 'select', array( 'onetime' => 'on' ) );

		$this->assertEqualsWithDelta( 160.0, $total, 0.001, 'Base 100 + fee 10% of it, not converted again + gift 50.' );
	}

	public function test_matrix_discount_base_keeps_percent_one_time_fee_from_converted_base(): void {
		$product = $this->create_simple_product( array( 'regular_price' => (string) ( 10 * self::RATE ) ) );
		$this->insert_ppom_meta(
			array(
				array(
					'type'      => 'select',
					'title'     => 'Setup',
					'data_name' => 'setup',
					'onetime'   => 'on',
					'options'   => array( array( 'option' => 'Yes', 'price' => '10%', 'id' => 'yes' ) ),
				),
				$this->build_price_matrix_field(
					'matrix',
					array( array( 'option' => '2-4', 'price' => '10%', 'label' => 'Bulk', 'id' => 'bulk' ) ),
					array( 'discount' => 'on', 'discount_type' => 'both' )
				),
			),
			$product->get_id()
		);

		$this->initialize_woocommerce_checkout_context();
		$cart_key = $this->add_product_to_real_cart( $product->get_id(), array( 'fields' => array( 'setup' => 'Yes' ) ), 2 );
		$this->assertNotFalse( $cart_key );
		$this->reload_real_cart_from_session();
		WC()->cart->calculate_totals();

		$discount = null;
		foreach ( WC()->cart->get_fees() as $fee ) {
			if ( 0 === strpos( $fee->id, 'ppom_matrix_fee' ) ) {
				$discount = (float) $fee->amount;
			}
		}

		// 10% of (2 x converted base 100 + fee 10% of 100).
		$this->assertNotNull( $discount, 'Matrix discount fee must be added.' );
		$this->assertEqualsWithDelta( -21.0, $discount, 0.001 );
	}

	public function test_posted_text_value_cannot_mark_its_fee_as_percent(): void {
		$product = $this->create_simple_product( array( 'regular_price' => (string) ( 10 * self::RATE ) ) );
		$this->insert_ppom_meta(
			array(
				array(
					'type'      => 'text',
					'title'     => 'Engraving',
					'data_name' => 'engraving',
					'price'     => '5',
					'onetime'   => 'on',
				),
			),
			$product->get_id()
		);

		$this->initialize_woocommerce_checkout_context();
		$cart_key = $this->add_product_to_real_cart( $product->get_id(), array( 'fields' => array( 'engraving' => array( 'raw_price' => '10%' ) ) ) );
		$this->assertNotFalse( $cart_key );
		$this->reload_real_cart_from_session();
		WC()->cart->calculate_totals();

		$this->assertEqualsWithDelta( 150.0, (float) WC()->cart->get_total( 'edit' ), 0.001, 'Base 100 + the flat fee 5 x rate; the posted value cannot skip conversion.' );
	}

	public function test_one_time_fee_row_is_left_to_the_fee_path(): void {
		$rows = PPOM()->convert_cart_rows_wcml(
			array(
				array( 'apply' => 'addon', 'price' => 5, 'is_percent' => false ),
				array( 'apply' => 'cart_fee', 'price' => 5, 'is_percent' => false ),
			)
		);

		$this->assertEqualsWithDelta( 50.0, (float) $rows[0]['price'], 0.0001 );
		$this->assertEqualsWithDelta( 5.0, (float) $rows[1]['price'], 0.0001, 'One-time fees are converted when the fee is added.' );
	}

	public function test_rows_stay_in_store_currency_when_wcml_does_not_convert_product_prices(): void {
		$this->set_wcml_loads_product_prices( false );

		$rows = PPOM()->convert_cart_rows_wcml( array( array( 'apply' => 'addon', 'price' => 5, 'is_percent' => false ) ) );

		$this->assertEqualsWithDelta( 5.0, (float) $rows[0]['price'], 0.0001, 'The base stays in store currency on this request, so the option must too.' );
	}

	public function test_rows_untouched_without_wcml(): void {
		unset( $GLOBALS['woocommerce_wpml'] );

		$rows = PPOM()->convert_cart_rows_wcml( array( array( 'apply' => 'addon', 'price' => 5, 'is_percent' => false ) ) );

		$this->assertEqualsWithDelta( 5.0, (float) $rows[0]['price'], 0.0001 );
	}

	public function test_cart_pass_not_registered_when_pro_converts_the_rows(): void {
		$ppom = PPOM();
		remove_filter( 'ppom_fields_prices', array( $ppom, 'convert_cart_rows_wcml' ) );
		$ppom->maybe_register_option_price_hook();
		$this->assertSame( 10, has_filter( 'ppom_fields_prices', array( $ppom, 'convert_cart_rows_wcml' ) ), 'Without Pro the free plugin converts the rows.' );

		remove_filter( 'ppom_fields_prices', array( $ppom, 'convert_cart_rows_wcml' ) );
		add_filter( 'ppom_option_price', 'ppom_hooks_convert_price', 99, 1 );
		$ppom->maybe_register_option_price_hook();

		$this->assertFalse( has_filter( 'ppom_fields_prices', array( $ppom, 'convert_cart_rows_wcml' ) ), 'PPOM Pro converts the rows itself; converting here too would apply the rate twice.' );
	}

	/**
	 * Prices a legacy-mode (ppom_legacy_price) cart line the way its cart hooks do.
	 * Legacy hooks are registered at plugin load, so the handlers are called directly.
	 *
	 * @param list<array<string, mixed>>              $fields  Field definitions.
	 * @param array<string, string>                   $posted  Submitted field values.
	 * @param list<array<string, string>>|null        $payload Browser-built option rows, or null for the server fallback.
	 * @param float                                   $base    Product price, already in the WCML client currency.
	 * @return array{line: float, fees: list<float>, subtotal: string} Fees in ascending order.
	 */
	private function legacy_cart( array $fields, array $posted, ?array $payload, float $base = 10 * self::RATE ): array {
		$product = $this->create_simple_product( array( 'regular_price' => (string) $base ) );
		$meta_id = $this->insert_ppom_meta( $fields, $product->get_id() );

		$this->initialize_woocommerce_checkout_context();
		$key  = WC()->cart->add_to_cart( $product->get_id(), 1 );
		$ppom = array( 'fields' => array( 'id' => $meta_id ) + $posted );
		if ( null !== $payload ) {
			$ppom['ppom_option_price'] = wp_json_encode( $payload );
		}
		WC()->cart->cart_contents[ $key ]['ppom'] = $ppom;

		$item = CartHandler::update_cart_fees( WC()->cart->cart_contents[ $key ], WC()->cart->cart_contents[ $key ] );

		WC()->cart->cart_contents[ $key ]['data'] = $item['data'];
		WC()->cart->fees_api()->remove_all_fees();
		CartHandler::add_fixed_fee( WC()->cart );

		$fees = array();
		foreach ( WC()->cart->fees_api()->get_fees() as $fee ) {
			$fees[] = (float) $fee->amount;
		}
		sort( $fees );

		return array(
			'line'     => (float) $item['data']->get_price( 'edit' ),
			'fees'     => $fees,
			'subtotal' => wp_strip_all_tags( (string) CartHandler::item_subtotal( '', WC()->cart->cart_contents[ $key ], $key ) ),
		);
	}

	/**
	 * Select field with one priced option.
	 *
	 * @return array{type: 'select', title: string, data_name: string, options: list<array{option: string, price: string, id: string}>, onetime?: 'on'}
	 */
	private function priced_select( string $data_name, string $price, bool $onetime = false ): array {
		$field = array(
			'type'      => 'select',
			'title'     => ucfirst( $data_name ),
			'data_name' => $data_name,
			'options'   => array( array( 'option' => 'Yes', 'price' => $price, 'id' => 'yes' ) ),
		);

		return $onetime ? $field + array( 'onetime' => 'on' ) : $field;
	}

	/**
	 * Option row as the product page posts it; the server prices it from saved settings.
	 *
	 * @return array{label: string, price: '0', apply: string, product_title: string, taxable: '', without_tax: '', option_id: 'yes', data_name: string}
	 */
	private function browser_row( string $data_name, string $apply ): array {
		return array(
			'label'         => $data_name,
			'price'         => '0',
			'apply'         => $apply,
			'product_title' => 'Product',
			'taxable'       => '',
			'without_tax'   => '',
			'option_id'     => 'yes',
			'data_name'     => $data_name,
		);
	}

	public function test_legacy_line_adds_flat_option_in_client_currency(): void {
		$cart = $this->legacy_cart( array( $this->priced_select( 'upgrade', '5' ) ), array( 'upgrade' => 'Yes' ), array( $this->browser_row( 'upgrade', 'variable' ) ) );

		$this->assertEqualsWithDelta( 150.0, $cart['line'], 0.001, 'Converted base 100 + option 5 x rate.' );
	}

	public function test_legacy_line_keeps_percent_option_taken_from_converted_base(): void {
		$cart = $this->legacy_cart(
			array( $this->priced_select( 'upgrade', '10%' ), $this->priced_select( 'gift', '5' ) ),
			array( 'upgrade' => 'Yes', 'gift' => 'Yes' ),
			array( $this->browser_row( 'upgrade', 'variable' ), $this->browser_row( 'gift', 'variable' ) )
		);

		$this->assertEqualsWithDelta( 160.0, $cart['line'], 0.001, 'Base 100 + 10% of it, not converted again + gift 5 x rate.' );
	}

	public function test_legacy_server_built_payload_adds_flat_option_in_client_currency(): void {
		$cart = $this->legacy_cart( array( $this->priced_select( 'upgrade', '5' ) ), array( 'upgrade' => 'Yes' ), null );

		$this->assertEqualsWithDelta( 150.0, $cart['line'], 0.001, 'Without a posted payload the server builds one; base 100 + option 5 x rate.' );
	}

	public function test_legacy_server_built_payload_takes_percent_option_from_converted_base(): void {
		$cart = $this->legacy_cart( array( $this->priced_select( 'upgrade', '10%' ) ), array( 'upgrade' => 'Yes' ), null, 200.0 );

		$this->assertEqualsWithDelta( 220.0, $cart['line'], 0.001, 'Base 200 + 10% of it; the bare number 10 gave 210.' );
	}

	public function test_legacy_percent_one_time_fee_is_converted_once(): void {
		$cart = $this->legacy_cart(
			array( $this->priced_select( 'setup', '10%', true ), $this->priced_select( 'wrap', '5', true ) ),
			array( 'setup' => 'Yes', 'wrap' => 'Yes' ),
			array( $this->browser_row( 'setup', 'onetime' ), $this->browser_row( 'wrap', 'onetime' ) )
		);

		$this->assertEqualsWithDelta( array( 10.0, 50.0 ), $cart['fees'], 0.001, 'Fees: 10% of the converted base 100, and 5 x rate.' );
		$this->assertEqualsWithDelta( 100.0, $cart['line'], 0.001, 'One-time fees are not added to the line.' );
	}

	public function test_legacy_percent_one_time_fee_row_subtotal_is_converted_once(): void {
		$cart = $this->legacy_cart( array( $this->priced_select( 'setup', '10%', true ) ), array( 'setup' => 'Yes' ), array( $this->browser_row( 'setup', 'onetime' ) ) );

		$this->assertStringContainsString( '110.00', $cart['subtotal'], 'Row subtotal: base 100 + fee 10% of it.' );
	}
}
