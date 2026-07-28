<?php
/**
 * Class Test_Third_Party_Price_Mutation
 *
 * Regression for issue #680: a third party (e.g. Kadence Shop Kit Variation
 * Swatches) that calls set_price() during woocommerce_before_calculate_totals
 * must not erase the PPOM addon price from the cart line total.
 *
 * @package ppom-pro
 */

require_once __DIR__ . '/class-ppom-test-case.php';

class Test_Third_Party_Price_Mutation extends PPOM_Test_Case {

	/**
	 * A priced radio addon survives a competing set_price() during totals calculation.
	 *
	 * @return void
	 */
	public function testAddonPriceSurvivesThirdPartySetPriceDuringTotals() {
		$product = $this->create_simple_product( array( 'regular_price' => '10' ) );

		$this->insert_ppom_meta(
			array(
				array(
					'type'      => 'radio',
					'title'     => 'Gift wrap',
					'data_name' => 'giftwrap',
					'options'   => array(
						array( 'option' => 'None', 'price' => '' ),
						array( 'option' => 'Premium wrap', 'price' => '5' ),
					),
				),
			),
			$product->get_id()
		);

		$this->initialize_woocommerce_checkout_context();

		$cart_key = $this->add_product_to_real_cart(
			$product->get_id(),
			array(
				'fields' => array(
					'giftwrap' => 'Premium wrap',
				),
			)
		);

		$this->assertNotFalse( $cart_key );

		$reloaded = $this->reload_real_cart_from_session();

		$this->assertSame( 15.0, (float) $reloaded[ $cart_key ]['data']->get_price() );

		// Third-party plugin resets every line to the catalog price during totals.
		add_action(
			'woocommerce_before_calculate_totals',
			function ( $cart ) {
				foreach ( $cart->get_cart() as $cart_item ) {
					$cart_item['data']->set_price( (float) $cart_item['data']->get_regular_price() );
				}
			},
			20
		);

		WC()->cart->calculate_totals();

		$this->assertSame( 15.0, (float) WC()->cart->get_total( 'edit' ) );
	}

	/**
	 * The ppom_cart_line_total filter receives the cart item as second arg on the
	 * totals-recalc path, same contract as the session-restore path.
	 *
	 * @return void
	 */
	public function testCartLineTotalFilterReceivesCartItemDuringTotalsRecalc() {
		$product = $this->create_simple_product( array( 'regular_price' => '10' ) );

		$this->insert_ppom_meta(
			array(
				array(
					'type'      => 'radio',
					'title'     => 'Gift wrap',
					'data_name' => 'giftwrap',
					'options'   => array(
						array( 'option' => 'Premium wrap', 'price' => '5' ),
					),
				),
			),
			$product->get_id()
		);

		$this->initialize_woocommerce_checkout_context();

		$cart_key = $this->add_product_to_real_cart(
			$product->get_id(),
			array(
				'fields' => array(
					'giftwrap' => 'Premium wrap',
				),
			)
		);

		$this->assertNotFalse( $cart_key );
		$this->reload_real_cart_from_session();

		$received = array();
		add_filter(
			'ppom_cart_line_total',
			function ( $total, $cart_item ) use ( &$received ) {
				$received[] = $cart_item;
				return $total;
			},
			10,
			2
		);

		WC()->cart->calculate_totals();

		$this->assertNotEmpty( $received );
		foreach ( $received as $cart_item ) {
			$this->assertIsArray( $cart_item );
			$this->assertSame( 'Premium wrap', $cart_item['ppom']['fields']['giftwrap'] );
		}
	}

	/**
	 * A variation line also survives a competing set_price() during totals,
	 * recomputing from the variation's own catalog price.
	 *
	 * @return void
	 */
	public function testVariationAddonPriceSurvivesThirdPartySetPriceDuringTotals() {
		$created   = $this->create_variable_product_with_variation( array(), array( 'regular_price' => '20' ) );
		$product   = $created['product'];
		$variation = $created['variation'];

		$this->insert_ppom_meta(
			array(
				array(
					'type'      => 'radio',
					'title'     => 'Gift wrap',
					'data_name' => 'giftwrap',
					'options'   => array(
						array( 'option' => 'Premium wrap', 'price' => '5' ),
					),
				),
			),
			$product->get_id()
		);

		$this->initialize_woocommerce_checkout_context();

		$cart_key = $this->add_product_to_real_cart(
			$product->get_id(),
			array(
				'fields' => array(
					'giftwrap' => 'Premium wrap',
				),
			),
			1,
			$variation->get_id()
		);

		$this->assertNotFalse( $cart_key );

		$reloaded = $this->reload_real_cart_from_session();

		$this->assertSame( 25.0, (float) $reloaded[ $cart_key ]['data']->get_price() );

		add_action(
			'woocommerce_before_calculate_totals',
			function ( $cart ) {
				foreach ( $cart->get_cart() as $cart_item ) {
					$cart_item['data']->set_price( (float) $cart_item['data']->get_regular_price() );
				}
			},
			20
		);

		WC()->cart->calculate_totals();

		$this->assertSame( 25.0, (float) WC()->cart->get_total( 'edit' ) );
	}

	/**
	 * The addon price is included in totals within the same request as add-to-cart,
	 * before any session restore — the wc-ajax=add_to_cart flow (issue #623).
	 *
	 * @return void
	 */
	public function testAddonPriceAppliesInSameRequestAsAddToCart() {
		$product = $this->create_simple_product( array( 'regular_price' => '29' ) );

		$this->insert_ppom_meta(
			array(
				array(
					'type'      => 'radio',
					'title'     => 'Gift wrap',
					'data_name' => 'giftwrap',
					'options'   => array(
						array( 'option' => 'Premium wrap', 'price' => '10' ),
					),
				),
			),
			$product->get_id()
		);

		$this->initialize_woocommerce_checkout_context();

		$cart_key = $this->add_product_to_real_cart(
			$product->get_id(),
			array(
				'fields' => array(
					'giftwrap' => 'Premium wrap',
				),
			)
		);

		$this->assertNotFalse( $cart_key );

		// No session reload: AJAX fragments calculate totals before the session-restore filter runs.
		WC()->cart->calculate_totals();

		$this->assertSame( 39.0, (float) WC()->cart->get_total( 'edit' ) );
	}

	/**
	 * Repeated totals passes must not stack the addon price (no double-count).
	 *
	 * @return void
	 */
	public function testRepeatedTotalsPassesDoNotDoubleCountAddonPrice() {
		$product = $this->create_simple_product( array( 'regular_price' => '10' ) );

		$this->insert_ppom_meta(
			array(
				array(
					'type'      => 'radio',
					'title'     => 'Gift wrap',
					'data_name' => 'giftwrap',
					'options'   => array(
						array( 'option' => 'Premium wrap', 'price' => '5' ),
					),
				),
			),
			$product->get_id()
		);

		$this->initialize_woocommerce_checkout_context();

		$cart_key = $this->add_product_to_real_cart(
			$product->get_id(),
			array(
				'fields' => array(
					'giftwrap' => 'Premium wrap',
				),
			)
		);

		$this->assertNotFalse( $cart_key );
		$this->reload_real_cart_from_session();

		WC()->cart->calculate_totals();
		WC()->cart->calculate_totals();

		$this->assertSame( 15.0, (float) WC()->cart->get_total( 'edit' ) );
	}
}
