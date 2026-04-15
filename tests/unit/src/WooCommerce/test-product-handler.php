<?php
/**
 * Unit tests for PPOM\WooCommerce\Product\ProductHandler helpers.
 *
 * @package ppom-pro
 */

use PPOM\WooCommerce\Product\ProductHandler;

/**
 * @covers \PPOM\WooCommerce\Product\ProductHandler
 */
class Test_Product_Handler extends PPOM_Test_Case {

	/**
	 * @return void
	 */
	public function test_field_requires_add_to_cart_schema_checks_false_without_data_name() {
		$this->assertFalse(
			ProductHandler::field_requires_add_to_cart_schema_checks(
				array(
					'type'    => 'text',
					'required' => 'on',
				)
			)
		);
	}

	/**
	 * @return void
	 */
	public function test_field_requires_add_to_cart_schema_checks_false_when_optional_without_min_max() {
		$this->assertFalse(
			ProductHandler::field_requires_add_to_cart_schema_checks(
				array(
					'data_name' => 'size',
					'type'      => 'select',
				)
			)
		);
	}

	/**
	 * @return void
	 */
	public function test_field_requires_add_to_cart_schema_checks_true_when_required() {
		$this->assertTrue(
			ProductHandler::field_requires_add_to_cart_schema_checks(
				array(
					'data_name' => 'size',
					'required'  => 'on',
				)
			)
		);
	}

	/**
	 * @return void
	 */
	public function test_field_requires_add_to_cart_schema_checks_true_when_min_or_max_checked() {
		$this->assertTrue(
			ProductHandler::field_requires_add_to_cart_schema_checks(
				array(
					'data_name'   => 'opts',
					'min_checked' => '1',
				)
			)
		);

		$this->assertTrue(
			ProductHandler::field_requires_add_to_cart_schema_checks(
				array(
					'data_name'   => 'opts',
					'max_checked' => '3',
				)
			)
		);
	}
}
