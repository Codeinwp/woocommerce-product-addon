<?php
/**
 * Hotpath: cart payload normalization — add_cart_item_data and the
 * session-restore filter chain. Runs on every add-to-cart submission and
 * on every cart page load (session rehydration).
 *
 * @package ppom-pro
 */

require_once __DIR__ . '/class-ppom-test-case.php';

class Test_Hotpath_Cart_Payload extends PPOM_Test_Case {

	/**
	 * Tracer: the full posted PPOM payload (fields + conditionally_hidden +
	 * ppom_option_price) is attached verbatim to the cart item by
	 * ppom_woocommerce_add_cart_item_data().
	 *
	 * @return void
	 */
	public function testAddCartItemDataStoresFullPostedPayloadIncludingOptionPriceAndHiddenList() {
		$product = $this->create_simple_product();

		$this->insert_ppom_meta(
			array(
				$this->build_text_field( 'engraving', 'Engraving' ),
				$this->build_select_field(
					'plan',
					'Plan',
					array(
						array( 'option' => 'Premium', 'price' => '5' ),
					)
				),
			),
			$product->get_id()
		);

		WC()->cart = null;

		$_POST['ppom'] = array(
			'fields'               => array(
				'engraving' => 'Hello world',
				'plan'      => 'Premium',
			),
			'conditionally_hidden' => 'engraving',
			'ppom_option_price'    => wp_json_encode(
				array(
					array(
						'price'     => 5,
						'apply'     => 'addon',
						'data_name' => 'plan',
					),
				)
			),
		);

		$cart_item = ppom_woocommerce_add_cart_item_data( array(), $product->get_id() );

		$this->assertArrayHasKey( 'ppom', $cart_item );
		$this->assertSame( $_POST['ppom']['fields'], $cart_item['ppom']['fields'] );
		$this->assertSame( 'engraving', $cart_item['ppom']['conditionally_hidden'] );
		$this->assertSame( $_POST['ppom']['ppom_option_price'], $cart_item['ppom']['ppom_option_price'] );
	}

	/**
	 * Without $_POST['ppom'], add_cart_item_data must return the input cart
	 * untouched — this is the path every non-PPOM add-to-cart submission takes.
	 *
	 * @return void
	 */
	public function testAddCartItemDataReturnsInputUnchangedWhenPpomPayloadAbsent() {
		$product = $this->create_simple_product();
		$this->insert_ppom_meta(
			array( $this->build_text_field( 'engraving', 'Engraving' ) ),
			$product->get_id()
		);

		$input = array( 'product_id' => $product->get_id(), 'sentinel' => 'untouched' );

		$result = ppom_woocommerce_add_cart_item_data( $input, $product->get_id() );

		$this->assertSame( $input, $result );
		$this->assertArrayNotHasKey( 'ppom', $result );
	}

	/**
	 * A product without PPOM settings short-circuits add_cart_item_data, even
	 * if a stray $_POST['ppom'] arrives from another page. This is the path
	 * every non-PPOM product hits when a PPOM-active site has $_POST data.
	 *
	 * @return void
	 */
	public function testAddCartItemDataShortCircuitsForProductsWithoutPpomSettings() {
		$product = $this->create_simple_product();

		$_POST['ppom'] = array(
			'fields' => array( 'engraving' => 'Hello' ),
		);

		$input  = array( 'sentinel' => 'untouched' );
		$result = ppom_woocommerce_add_cart_item_data( $input, $product->get_id() );

		$this->assertSame( $input, $result );
		$this->assertArrayNotHasKey( 'ppom', $result );
	}

	/**
	 * The ppom_add_cart_item_data filter must run, letting add-ons mutate the
	 * payload (e.g. injecting cropped-image data) before it is stored on the
	 * cart item. Breaking this filter would silently break every PPOM add-on.
	 *
	 * @return void
	 */
	public function testAddCartItemDataAppliesPpomAddCartItemDataFilterBeforeStorage() {
		$product = $this->create_simple_product();
		$this->insert_ppom_meta(
			array( $this->build_text_field( 'engraving', 'Engraving' ) ),
			$product->get_id()
		);

		WC()->cart = null;

		$_POST['ppom'] = array(
			'fields' => array( 'engraving' => 'original' ),
		);

		$inject = function ( $payload ) {
			$payload['fields']['engraving'] = 'mutated by filter';
			$payload['injected_by_addon']   = 'sentinel';

			return $payload;
		};

		add_filter( 'ppom_add_cart_item_data', $inject );

		try {
			$cart_item = ppom_woocommerce_add_cart_item_data( array(), $product->get_id() );
		} finally {
			remove_filter( 'ppom_add_cart_item_data', $inject );
		}

		$this->assertSame( 'mutated by filter', $cart_item['ppom']['fields']['engraving'] );
		$this->assertSame( 'sentinel', $cart_item['ppom']['injected_by_addon'] );
	}

	/**
	 * make_meta_data is the cart-display + order-meta builder. When a cart
	 * item lacks the ppom.fields key (e.g. a plain WooCommerce line) the
	 * function must return the cart item untouched.
	 *
	 * @return void
	 */
	public function testMakeMetaDataReturnsCartItemUntouchedWhenPpomFieldsKeyMissing() {
		$product = $this->create_simple_product();

		$cart_item = array(
			'data'       => $product,
			'product_id' => $product->get_id(),
			'quantity'   => 1,
			'sentinel'   => 'plain-line',
		);

		$result = ppom_make_meta_data( $cart_item, 'cart' );

		$this->assertSame( $cart_item, $result );
	}

	/**
	 * The session-restore filter chain (price_check_price_matrix → price_controller)
	 * must leave a non-PPOM cart line alone — every cart-page render passes every
	 * line through these filters, so corrupting plain lines would break stores
	 * even when shoppers never touched a PPOM product.
	 *
	 * @return void
	 */
	public function testSessionRestoreFilterChainLeavesNonPpomCartLineUnchanged() {
		$product = $this->create_simple_product( array( 'regular_price' => '10' ) );

		$plain_cart_item = array(
			'data'         => $product,
			'product_id'   => $product->get_id(),
			'variation_id' => 0,
			'quantity'     => 1,
		);

		$restored = apply_filters(
			'woocommerce_get_cart_item_from_session',
			$plain_cart_item,
			$plain_cart_item
		);

		$this->assertIsArray( $restored );
		$this->assertSame( $product->get_id(), $restored['product_id'] );
		$this->assertSame( 1, $restored['quantity'] );
		$this->assertArrayNotHasKey( 'ppom', $restored );
		$this->assertEqualsWithDelta( 10.0, (float) $restored['data']->get_price(), 0.0001 );
	}
}
