<?php
/**
 * Regression test for issue #641: cart quantity input must show the actual
 * cart quantity, not the price-matrix minimum, when a customer has already
 * added a higher quantity to the classic WooCommerce cart shortcode.
 *
 * Root cause: Validator::validation_product_limits() unconditionally overwrote
 * input_value with limits['input_value'] (the first price-matrix range) even
 * when WooCommerce passed a higher existing cart quantity in that field.
 *
 * @package PPOM
 */

use PPOM\Validation\Validator;

class Test_Cart_Quantity_Input_Reset extends PPOM_Test_Case {

	/**
	 * When a product has a price matrix whose first range starts at 12 and the
	 * customer already has 55 units in the cart, the quantity input must display
	 * 55 — not reset to 12.
	 *
	 * This is the primary regression from issue #641.
	 */
	public function test_cart_quantity_input_preserves_higher_cart_quantity_over_price_matrix_minimum() {
		$product = $this->create_simple_product( array( 'regular_price' => '10' ) );

		$this->insert_ppom_meta(
			array(
				$this->build_price_matrix_field(
					'price_matrix',
					array(
						array(
							'option' => '12-24',
							'price'  => '9',
							'label'  => '12–24 units',
							'id'     => 'range_12_24',
						),
						array(
							'option' => '25-100',
							'price'  => '8',
							'label'  => '25–100 units',
							'id'     => 'range_25_100',
						),
					)
				),
			),
			$product->get_id()
		);

		// WooCommerce passes the current cart quantity as input_value when
		// rendering the cart-page quantity input.
		$cart_quantity = 55;

		$result = Validator::validation_product_limits(
			array(
				'min_value'   => 1,
				'max_value'   => 0,
				'step'        => 1,
				'input_value' => $cart_quantity,
			),
			$product
		);

		$this->assertSame(
			$cart_quantity,
			(int) $result['input_value'],
			'Cart quantity input should display the actual cart quantity (55), not the price-matrix minimum (12).'
		);
	}

	/**
	 * On the product page (no existing cart quantity), input_value should still
	 * be initialised to the price-matrix first-range minimum so customers start
	 * at a valid quantity.
	 */
	public function test_product_page_input_value_defaults_to_price_matrix_minimum() {
		$product = $this->create_simple_product( array( 'regular_price' => '10' ) );

		$this->insert_ppom_meta(
			array(
				$this->build_price_matrix_field(
					'price_matrix',
					array(
						array(
							'option' => '12-24',
							'price'  => '9',
							'label'  => '12–24 units',
							'id'     => 'range_12_24',
						),
					)
				),
			),
			$product->get_id()
		);

		// Product page: WooCommerce passes input_value = 1 (the default).
		$result = Validator::validation_product_limits(
			array(
				'min_value'   => 1,
				'max_value'   => 0,
				'step'        => 1,
				'input_value' => 1,
			),
			$product
		);

		$this->assertSame(
			12,
			(int) $result['input_value'],
			'Product-page quantity input should default to the price-matrix minimum (12) when no higher cart quantity exists.'
		);
	}
}
