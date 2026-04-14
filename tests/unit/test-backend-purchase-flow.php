<?php
/**
 * Class Test_Backend_Purchase_Flow
 *
 * @package ppom-pro
 */

require_once __DIR__ . '/class-ppom-test-case.php';

class Test_Backend_Purchase_Flow extends PPOM_Test_Case {

	/**
	 * Ensure a simple PPOM product can complete the backend cart-to-order flow with addon pricing.
	 *
	 * @return void
	 */
	public function testSimpleProductCompletesBackendCheckoutFlowWithAddonPricing() {
		$product = $this->create_simple_product(
			array(
				'regular_price' => '10',
				'virtual'       => true,
			)
		);

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

		$this->initialize_woocommerce_checkout_context();

		$cart_key = $this->add_product_to_real_cart(
			$product->get_id(),
			array(
				'fields' => array(
					'plan' => 'Premium',
				),
			)
		);

		$this->assertNotFalse( $cart_key );
		$this->assertSame( 'Premium', WC()->cart->get_cart_item( $cart_key )['ppom']['fields']['plan'] );

		$reloaded = $this->reload_real_cart_from_session();

		$this->assertSame( 15.0, (float) $reloaded[ $cart_key ]['data']->get_price() );

		WC()->cart->calculate_totals();

		$this->assertSame( 15.0, (float) WC()->cart->get_total( 'edit' ) );

		$order = $this->create_order_from_real_cart();
		$item  = $this->get_first_order_item( $order );

		$this->assertSame( 15.0, (float) $order->get_total() );
		$this->assertStringContainsString( 'Premium', $item->get_meta( 'plan', true ) );
		$this->assertStringContainsString( '5.00', $item->get_meta( 'plan', true ) );
		$this->assertSame( 'Premium', $item->get_meta( '_ppom_fields', true )['fields']['plan'] );
	}

	/**
	 * Ensure checkbox addon selections survive backend cart, totals, and order creation.
	 *
	 * @return void
	 */
	public function testCheckboxSelectionsPersistThroughBackendCheckoutFlow() {
		$product = $this->create_simple_product(
			array(
				'regular_price' => '10',
				'virtual'       => true,
			)
		);

		$this->insert_ppom_meta(
			array(
				$this->build_checkbox_field(
					'extras',
					'Extras',
					array(
						array(
							'option' => 'Red',
							'price'  => '2',
						),
						array(
							'option' => 'Blue',
							'price'  => '3',
						),
					)
				),
			),
			$product->get_id()
		);

		$this->initialize_woocommerce_checkout_context();

		$cart_key = $this->add_product_to_real_cart(
			$product->get_id(),
			array(
				'fields' => array(
					'extras' => array( 'Red', 'Blue' ),
				),
			)
		);

		$this->assertNotFalse( $cart_key );
		$this->assertSame( array( 'Red', 'Blue' ), WC()->cart->get_cart_item( $cart_key )['ppom']['fields']['extras'] );

		$reloaded = $this->reload_real_cart_from_session();

		$this->assertSame( 15.0, (float) $reloaded[ $cart_key ]['data']->get_price() );

		WC()->cart->calculate_totals();

		$this->assertSame( 15.0, (float) WC()->cart->get_total( 'edit' ) );

		$order = $this->create_order_from_real_cart();
		$item  = $this->get_first_order_item( $order );

		$this->assertSame( 15.0, (float) $order->get_total() );
		$this->assertStringContainsString( 'Red', $item->get_meta( 'extras', true ) );
		$this->assertStringContainsString( 'Blue', $item->get_meta( 'extras', true ) );
		$this->assertStringContainsString( '2.00', $item->get_meta( 'extras', true ) );
		$this->assertStringContainsString( '3.00', $item->get_meta( 'extras', true ) );
		$this->assertSame( array( 'Red', 'Blue' ), $item->get_meta( '_ppom_fields', true )['fields']['extras'] );
	}
}
