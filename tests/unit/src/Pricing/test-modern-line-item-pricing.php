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
}
