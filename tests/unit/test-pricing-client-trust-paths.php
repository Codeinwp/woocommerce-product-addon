<?php
/**
 * Characterization tests for pricing paths that still trust client-supplied amounts.
 *
 * WARNING: these are NOT desired-behavior tests. They lock in CURRENTLY KNOWN insecure
 * behavior so that any incidental change (intentional or not) trips a visible signal.
 * Each test runs its assertion (so behavior drift is detected) and then calls
 * `markTestIncomplete()` so PHPUnit reports the test in the "incomplete" bucket — that
 * way the harness shows up in CI as outstanding work, never as a green passing test
 * that could lull a reviewer into treating the underlying trust as desired.
 *
 * When the underlying server-side hardening lands, the corresponding test here should
 * be DELETED (not flipped) so it cannot be silently restored later.
 *
 * @group security-known-issue
 * @group ppom-pricing-client-trust
 *
 * @package ppom-pro
 */

require_once __DIR__ . '/class-ppom-test-case.php';

class Test_Pricing_Client_Trust_Paths extends PPOM_Test_Case {

	/**
	 * KNOWN ISSUE (W1): fancycropper addon total is taken verbatim from the JSON
	 * `cartPrice` posted by the client, with no server-side recomputation against the
	 * admin-defined unit rate. A buyer can submit any amount, including 0 or pennies.
	 *
	 * Fix direction: recompute price = admin_unit_rate × server-validated dimensions
	 * inside Engine::get_field_prices() for the `fancycropper` case, ignoring posted
	 * `cartPrice`. When that lands, delete this test.
	 *
	 * @return void
	 */
	public function testFancycropperPricingUsesPostedCartPriceJson() {
		$product = $this->create_simple_product(
			array(
				'regular_price' => '20',
			)
		);

		$this->insert_ppom_meta(
			array(
				$this->build_fancycropper_field( 'fancy_crop', 'Crop' ),
			),
			$product->get_id()
		);

		$posted = array(
			'fancy_crop' => array(
				'popup_1' => wp_json_encode(
					array(
						'cartPrice' => 7500,
					)
				),
			),
		);

		$quantity = 1;
		$prices   = \PPOM\Pricing\Engine::get_field_prices( $posted, $product->get_id(), $quantity, 0 );

		$this->assertNotEmpty( $prices );
		$this->assertSame( 'fancycropper', $prices[0]['type'] );
		$this->assertSame( 7500.0, floatval( $prices[0]['price'] ) );

		$this->markTestIncomplete(
			'fancycropper price is read directly from posted JSON cartPrice with no server-side recomputation.'
		);
	}

	/**
	 * KNOWN ISSUE (W2): the cart line subtotal display reads the `ppom_option_price`
	 * JSON blob stored in the cart item meta and adds it on top of the product price.
	 * This is display-only (the authoritative line price is set elsewhere), but a
	 * compromised session that mutates the blob produces a misleading subtotal.
	 *
	 * Fix direction: derive the displayed addon component from the server-resolved
	 * Engine output instead of decoding `ppom_option_price` again at display time.
	 * When that lands, delete this test.
	 *
	 * @return void
	 */
	public function testItemSubtotalReflectsDecodedPpomOptionPriceForOnetimeAddons() {
		$product = $this->create_simple_product(
			array(
				'regular_price' => '10',
			)
		);

		$cart_item = array(
			'product_id' => $product->get_id(),
			'quantity'   => 2,
			'ppom'       => array(
				'ppom_option_price' => wp_json_encode(
					array(
						array(
							'price'    => 42,
							'apply'    => 'onetime',
							'discount' => 0,
						),
					)
				),
			),
		);

		$initial_subtotal = '<span class="wc-amount">0</span>';
		$result           = \PPOM\WooCommerce\Cart\CartHandler::item_subtotal( $initial_subtotal, $cart_item, 'cart-key-test' );

		$this->assertIsString( $result );
		$this->assertStringContainsString( '62', strip_tags( $result ) );

		$this->markTestIncomplete(
			'item_subtotal display path decodes ppom_option_price from cart meta instead of using the server-resolved Engine output.'
		);
	}
}
