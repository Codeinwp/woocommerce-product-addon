<?php
/**
 * Unit tests for PPOM\Pricing\ModernLineItemPricing::apply_to_cart_item.
 *
 * Modern path: recompute the cart line price from posted PPOM fields rather
 * than from the client-sent option-price JSON (the legacy path).
 *
 * @package ppom-pro
 */

require_once dirname( __DIR__, 2 ) . '/class-ppom-test-case.php';

use PPOM\Pricing\Engine;
use PPOM\Pricing\ModernLineItemPricing;

/**
 * @covers \PPOM\Pricing\ModernLineItemPricing
 */
class Test_Pricing_ModernLineItemPricing extends PPOM_Test_Case {

	/**
	 * Build a cart-item array that matches what WC passes through the session restore filter.
	 *
	 * @return array<string, mixed>
	 */
	private function build_cart_item( $product, array $fields, int $quantity = 1 ): array {
		return array(
			'data'         => $product,
			'product_id'   => $product->get_id(),
			'variation_id' => 0,
			'quantity'     => $quantity,
			'ppom'         => array(
				'fields'               => $fields,
				'conditionally_hidden' => '',
			),
		);
	}

	/**
	 * Empty cart_items is returned unchanged.
	 *
	 * @return void
	 */
	public function test_empty_cart_item_is_returned_unchanged() {
		$result = ModernLineItemPricing::apply_to_cart_item( array(), array() );

		$this->assertSame( array(), $result );
	}

	/**
	 * When the session values don't include ppom fields, the cart item is untouched.
	 *
	 * @return void
	 */
	public function test_missing_fields_payload_does_not_change_price() {
		$product = $this->create_simple_product( array( 'regular_price' => '15' ) );
		$item    = $this->build_cart_item( $product, array() );

		ModernLineItemPricing::apply_to_cart_item( $item, array( 'ppom' => array() ) );

		$this->assertEqualsWithDelta( 15.0, (float) $product->get_price(), 0.0001 );
	}

	/**
	 * A select addon with a priced option sets the line price to base + addon.
	 *
	 * @return void
	 */
	public function test_select_addon_adds_to_product_base_price() {
		$product = $this->create_simple_product( array( 'regular_price' => '10' ) );

		$this->insert_ppom_meta(
			array(
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

		$item = $this->build_cart_item( $product, array( 'plan' => 'Premium' ) );

		ModernLineItemPricing::apply_to_cart_item( $item, $item );

		$this->assertEqualsWithDelta( 15.0, (float) $product->get_price(), 0.0001 );
	}

	/**
	 * The ppom_cart_line_total filter wins over the computed value.
	 *
	 * @return void
	 */
	public function test_cart_line_total_filter_overrides_total() {
		$product = $this->create_simple_product( array( 'regular_price' => '10' ) );

		$this->insert_ppom_meta(
			array(
				$this->build_select_field(
					'plan',
					'Plan',
					array( array( 'option' => 'Premium', 'price' => '5' ) )
				),
			),
			$product->get_id()
		);

		$item = $this->build_cart_item( $product, array( 'plan' => 'Premium' ) );

		$filter = static function () {
			return 777.0;
		};
		add_filter( 'ppom_cart_line_total', $filter );

		try {
			ModernLineItemPricing::apply_to_cart_item( $item, $item );
		} finally {
			remove_filter( 'ppom_cart_line_total', $filter );
		}

		$this->assertEqualsWithDelta( 777.0, (float) $product->get_price(), 0.0001 );
	}

	/**
	 * The ppom_before_calculate_cart_total action fires with the computed price rows.
	 *
	 * @return void
	 */
	public function test_before_calculate_cart_total_action_fires_with_price_rows() {
		$product = $this->create_simple_product( array( 'regular_price' => '10' ) );

		$this->insert_ppom_meta(
			array(
				$this->build_select_field(
					'plan',
					'Plan',
					array( array( 'option' => 'Premium', 'price' => '5' ) )
				),
			),
			$product->get_id()
		);

		$item       = $this->build_cart_item( $product, array( 'plan' => 'Premium' ) );
		$call_count = 0;
		$captured   = null;

		$action = static function ( $field_prices ) use ( &$call_count, &$captured ) {
			++$call_count;
			$captured = $field_prices;
		};
		add_action( 'ppom_before_calculate_cart_total', $action, 10, 1 );

		try {
			ModernLineItemPricing::apply_to_cart_item( $item, $item );
		} finally {
			remove_action( 'ppom_before_calculate_cart_total', $action, 10 );
		}

		$this->assertSame( 1, $call_count );
		$this->assertIsArray( $captured );
		$this->assertNotEmpty( $captured );
		$this->assertSame( 'plan', $captured[0]['data_name'] );
	}

	/**
	 * The pre-existing `price_matrix_found` payload is consumed (when present) so a
	 * later cart-restore round-trip doesn't double-apply matrix pricing.
	 *
	 * @return void
	 */
	public function test_existing_price_matrix_found_is_honored_in_base_price_calc() {
		$product = $this->create_simple_product( array( 'regular_price' => '10' ) );

		$matrix_field = $this->build_price_matrix_field(
			'matrix_a',
			array( array( 'option' => '1-10', 'price' => '4' ) )
		);
		$this->insert_ppom_meta( array( $matrix_field ), $product->get_id() );

		$item                                  = $this->build_cart_item( $product, array(), 1 );
		$item['ppom']['price_matrix_found']    = $matrix_field;

		ModernLineItemPricing::apply_to_cart_item( $item, $item );

		// matrix_price 4 is honored as base price.
		$this->assertEqualsWithDelta( 4.0, (float) $product->get_price(), 0.0001 );
	}

	/**
	 * A matrix row priced at zero is authoritative: it replaces the catalog price
	 * rather than being discarded in favour of it.
	 *
	 * @return void
	 */
	public function test_zero_price_matrix_row_is_honored_as_base_price() {
		$product = $this->create_simple_product( array( 'regular_price' => '10' ) );

		$matrix_field = $this->build_price_matrix_field(
			'matrix_zero',
			array( array( 'option' => '1-10', 'price' => '0' ) )
		);
		$this->insert_ppom_meta( array( $matrix_field ), $product->get_id() );

		$item                               = $this->build_cart_item( $product, array(), 1 );
		$item['ppom']['price_matrix_found'] = $matrix_field;

		ModernLineItemPricing::apply_to_cart_item( $item, $item );

		$this->assertEqualsWithDelta( 0.0, (float) $product->get_price(), 0.0001 );
	}

	/**
	 * A discount matrix supplies no base price of its own, so the catalog price
	 * must survive.
	 *
	 * @return void
	 */
	public function test_discount_matrix_leaves_catalog_base_price_intact() {
		$product = $this->create_simple_product( array( 'regular_price' => '10' ) );

		$matrix_field = $this->build_price_matrix_field(
			'matrix_pct',
			array( array( 'option' => '1-10', 'price' => '10%' ) ),
			array( 'discount' => 'on', 'discount_type' => 'base' )
		);
		$this->insert_ppom_meta( array( $matrix_field ), $product->get_id() );

		$item                               = $this->build_cart_item( $product, array(), 1 );
		$item['ppom']['price_matrix_found'] = $matrix_field;

		ModernLineItemPricing::apply_to_cart_item( $item, $item );

		$this->assertEqualsWithDelta( 10.0, (float) $product->get_price(), 0.0001 );
	}

	/**
	 * A matrix row left blank is not a price of zero. The catalog price stands.
	 *
	 * @return void
	 */
	public function test_blank_matrix_row_price_leaves_catalog_base_price_intact() {
		$product = $this->create_simple_product( array( 'regular_price' => '10' ) );

		$matrix_field = $this->build_price_matrix_field(
			'matrix_blank',
			array( array( 'option' => '1-10', 'price' => '' ) )
		);
		$this->insert_ppom_meta( array( $matrix_field ), $product->get_id() );

		$item                               = $this->build_cart_item( $product, array(), 1 );
		$item['ppom']['price_matrix_found'] = $matrix_field;

		ModernLineItemPricing::apply_to_cart_item( $item, $item );

		$this->assertEqualsWithDelta( 10.0, (float) $product->get_price(), 0.0001 );
	}

	/**
	 * A fixed matrix row left blank is not a total to split across the quantity.
	 * The catalog price stands.
	 *
	 * @return void
	 */
	public function test_blank_fixed_matrix_row_price_leaves_catalog_base_price_intact() {
		$product = $this->create_simple_product( array( 'regular_price' => '10' ) );

		$matrix_field = $this->build_price_matrix_field(
			'matrix_blank_fixed',
			array( array( 'option' => '1-10', 'price' => '', 'isfixed' => 'on' ) )
		);
		$this->insert_ppom_meta( array( $matrix_field ), $product->get_id() );

		$item                               = $this->build_cart_item( $product, array(), 2 );
		$item['ppom']['price_matrix_found'] = $matrix_field;

		ModernLineItemPricing::apply_to_cart_item( $item, $item );

		$this->assertEqualsWithDelta( 10.0, (float) $product->get_price(), 0.0001 );
	}

	/**
	 * A discount matrix returns no matrix base price, so callers keep the catalog price.
	 *
	 * @return void
	 */
	public function test_discount_matrix_returns_null_matrix_price() {
		$product = $this->create_simple_product( array( 'regular_price' => '10' ) );

		$matrix_field = $this->build_price_matrix_field(
			'matrix_pct',
			array( array( 'option' => '1-10', 'price' => '10%' ) ),
			array( 'discount' => 'on', 'discount_type' => 'base' )
		);
		$this->insert_ppom_meta( array( $matrix_field ), $product->get_id() );

		$parsed = Engine::parse_price_matrix( $matrix_field, $product, 1, 10.0, 0, 0 );
		$found  = Engine::price_is_matrix_found( $product, 1, 10.0, 0, 0 );

		$this->assertNull( $parsed['matrix_price'] );
		$this->assertNull( $found['matrix_price'] );
	}

	/**
	 * A blank matrix row returns the base price, not an empty string.
	 *
	 * @return void
	 */
	public function test_blank_matrix_row_returns_base_price() {
		$product = $this->create_simple_product( array( 'regular_price' => '10' ) );

		$matrix_field = $this->build_price_matrix_field(
			'matrix_blank',
			array( array( 'option' => '1-10', 'price' => '' ) )
		);
		$this->insert_ppom_meta( array( $matrix_field ), $product->get_id() );

		$parsed = Engine::parse_price_matrix( $matrix_field, $product, 1, 10.0, 0, 0 );
		$found  = Engine::price_is_matrix_found( $product, 1, 10.0, 0, 0 );

		$this->assertSame( 10.0, $parsed['matrix_price'] );
		$this->assertSame( 10.0, $found['matrix_price'] );
	}

	/**
	 * Restores a cart item through the real session filter chain, with a
	 * shopper-supplied `price_matrix_found` payload attached.
	 *
	 * `CartHandler::add_cart_item_data()` stores the whole posted `ppom` array,
	 * so anything a shopper posts under that key reaches the pricing pipeline.
	 *
	 * @param WC_Product           $product  Product.
	 * @param array<string, mixed> $injected Injected matrix payload.
	 *
	 * @return array<string, mixed>
	 */
	private function restore_with_injected_matrix( WC_Product $product, array $injected ): array {
		$cart_item = array(
			'data'         => $product,
			'product_id'   => $product->get_id(),
			'variation_id' => 0,
			'quantity'     => 1,
			'ppom'         => array(
				'fields'               => array(),
				'conditionally_hidden' => '',
				'price_matrix_found'   => $injected,
			),
		);

		return apply_filters( 'woocommerce_get_cart_item_from_session', $cart_item, $cart_item );
	}

	/**
	 * A posted matrix cannot price a product that has no matrix field.
	 *
	 * @return void
	 */
	public function test_injected_positive_matrix_cannot_price_a_product_without_a_matrix_field() {
		$product = $this->create_simple_product( array( 'regular_price' => '10' ) );
		$this->insert_ppom_meta( array( $this->build_text_field( 'note' ) ), $product->get_id() );

		$this->restore_with_injected_matrix(
			$product,
			$this->build_price_matrix_field(
				'injected',
				array( array( 'option' => '1-99999', 'price' => '0.01' ) )
			)
		);

		$this->assertEqualsWithDelta( 10.0, (float) $product->get_price(), 0.0001 );
	}

	/**
	 * A zero row is still honoured when the product really has a matrix field,
	 * so the guard does not undo the fix this branch exists for.
	 *
	 * @return void
	 */
	public function test_configured_zero_matrix_still_applies_through_the_session_filter() {
		$product      = $this->create_simple_product( array( 'regular_price' => '10' ) );
		$matrix_field = $this->build_price_matrix_field(
			'matrix_zero',
			array( array( 'option' => '1-10', 'price' => '0' ) )
		);
		$this->insert_ppom_meta( array( $matrix_field ), $product->get_id() );

		$this->restore_with_injected_matrix( $product, $matrix_field );

		$this->assertEqualsWithDelta( 0.0, (float) $product->get_price(), 0.0001 );
	}

	/**
	 * A posted matrix cannot override the saved one on the add-to-cart request.
	 *
	 * The session-restore filter does not run on the request that adds the line,
	 * so `woocommerce_before_calculate_totals` prices from whatever the cart item
	 * carries. On a product that legitimately has a matrix field, checking only
	 * that a matrix exists is not enough.
	 *
	 * @return void
	 */
	public function test_posted_matrix_cannot_override_the_saved_matrix_on_add_to_cart() {
		$this->initialize_woocommerce_checkout_context();
		WC()->cart->empty_cart();

		$product      = $this->create_simple_product( array( 'regular_price' => '10' ) );
		$matrix_field = $this->build_price_matrix_field(
			'tier',
			array( array( 'option' => '1-10', 'price' => '5' ) )
		);
		$ppom_id = $this->insert_ppom_meta( array( $matrix_field ), $product->get_id() );

		$injected            = $matrix_field;
		$injected['options'] = array( array( 'option' => '1-99999', 'price' => '-1000' ) );

		$key = $this->add_product_to_real_cart(
			$product->get_id(),
			array(
				'fields'             => array( 'id' => (string) $ppom_id ),
				'price_matrix_found' => $injected,
			)
		);
		$this->assertNotFalse( $key, 'the product must reach the cart' );

		WC()->cart->calculate_totals();

		$item = WC()->cart->get_cart_item( $key );
		$this->assertEqualsWithDelta( 5.0, (float) $item['data']->get_price(), 0.0001 );

		WC()->cart->empty_cart();
	}
}
