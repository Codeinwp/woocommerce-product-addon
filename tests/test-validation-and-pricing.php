<?php
/**
 * Class Test_Validation_And_Pricing
 *
 * @package ppom-pro
 */

require_once __DIR__ . '/class-ppom-test-case.php';

class Test_Validation_And_Pricing extends PPOM_Test_Case {

	/**
	 * Ensure price-matrix and quantity fields combine into canonical limits.
	 *
	 * @return void
	 */
	public function testGetProductLimitsReadsPriceMatrixAndQuantityConstraints() {
		$product = $this->create_simple_product(
			array(
				'regular_price' => '10',
			)
		);

		$this->insert_ppom_meta(
			array(
				$this->build_price_matrix_field(
					'price_matrix',
					array(
						array(
							'option' => '2-2',
							'price'  => '8',
							'label'  => 'Qty 2',
							'id'     => 'qty_2',
						),
						array(
							'option' => '4-4',
							'price'  => '7',
							'label'  => 'Qty 4',
							'id'     => 'qty_4',
						),
					),
					array(
						'qty_step' => '2',
					)
				),
				array(
					'type'         => 'quantities',
					'title'        => 'Seats',
					'data_name'    => 'seat_quantity',
					'view_control' => 'simple_view',
					'min_qty'      => '3',
					'max_qty'      => '4',
					'options'      => array(
						array(
							'option' => 'seat',
							'price'  => '',
							'stock'  => '',
						),
					),
				),
			),
			$product->get_id()
		);

		$limits = ppom_get_product_limits( $product->get_id(), 0 );

		$this->assertSame( 3, $limits['min_qty'] );
		$this->assertSame( 4, $limits['max_qty'] );
		$this->assertSame( 2, $limits['step'] );
		$this->assertSame( 2, (int) $limits['input_value'] );
	}

	/**
	 * Ensure product quantity limits respect available stock.
	 *
	 * @return void
	 */
	public function testValidationProductLimitsClampsMaxToStockWithoutBackorders() {
		$product = $this->create_simple_product(
			array(
				'regular_price' => '10',
				'manage_stock'  => true,
				'stock_quantity'=> 3,
				'backorders'    => false,
			)
		);

		$this->insert_ppom_meta(
			array(
				$this->build_price_matrix_field(
					'price_matrix',
					array(
						array(
							'option' => '2-2',
							'price'  => '8',
							'label'  => 'Qty 2',
							'id'     => 'qty_2',
						),
						array(
							'option' => '4-4',
							'price'  => '7',
							'label'  => 'Qty 4',
							'id'     => 'qty_4',
						),
					),
					array(
						'qty_step' => '2',
					)
				),
			),
			$product->get_id()
		);

		$validated = ppom_validation_product_limits(
			array(
				'min_value' => 1,
				'max_value' => 0,
				'step'      => 1,
			),
			$product
		);

		$this->assertSame( 2, $validated['min_value'] );
		$this->assertSame( 3, $validated['max_value'] );
		$this->assertSame( 2, $validated['step'] );
	}

	/**
	 * Ensure variation validation uses parent PPOM limits and variation stock.
	 *
	 * @return void
	 */
	public function testValidationVariationLimitsUsesParentMetaAndVariationStock() {
		$parent = $this->create_simple_product(
			array(
				'regular_price' => '10',
			)
		);

		$this->insert_ppom_meta(
			array(
				$this->build_price_matrix_field(
					'price_matrix',
					array(
						array(
							'option' => '2-2',
							'price'  => '8',
							'label'  => 'Qty 2',
							'id'     => 'qty_2',
						),
						array(
							'option' => '4-4',
							'price'  => '7',
							'label'  => 'Qty 4',
							'id'     => 'qty_4',
						),
					),
					array(
						'qty_step' => '2',
					)
				),
			),
			$parent->get_id()
		);

		$variation = new class( $parent->get_id() ) {
			private $parent_id;

			public function __construct( $parent_id ) {
				$this->parent_id = $parent_id;
			}

			public function get_id() {
				return 999999;
			}

			public function get_parent_id() {
				return $this->parent_id;
			}

			public function is_type( $type ) {
				return 'variation' === $type;
			}

			public function managing_stock() {
				return true;
			}

			public function backorders_allowed() {
				return false;
			}

			public function get_stock_quantity() {
				return 3;
			}
		};

		$validated = ppom_validation_variation_limits(
			array(
				'min_qty'     => 1,
				'max_qty'     => 0,
				'step'        => 1,
				'input_value' => 1,
			),
			$variation,
			$variation
		);

		$this->assertSame( 2, $validated['min_qty'] );
		$this->assertSame( 3, $validated['max_qty'] );
		$this->assertSame( 2, $validated['step'] );
		$this->assertSame( 2, (int) $validated['input_value'] );
	}

	/**
	 * Ensure matrix discounts can target base price plus addons/cart fees.
	 *
	 * @return void
	 */
	public function testParsePriceMatrixAppliesPercentDiscountToBaseAndAddons() {
		$product = $this->create_simple_product(
			array(
				'regular_price' => '100',
			)
		);

		$parsed = ppom_parse_price_matrix(
			$this->build_price_matrix_field(
				'price_matrix',
				array(
					array(
						'option' => '2-4',
						'price'  => '10%',
						'label'  => 'Discount range',
						'id'     => 'discount_range',
					),
				),
				array(
					'discount'      => 'on',
					'discount_type' => 'both',
				)
			),
			$product,
			3,
			100,
			20,
			10
		);

		$this->assertSame( 0.0, (float) $parsed['matrix_price'] );
		$this->assertSame( 13.0, (float) $parsed['matrix_discount'] );
	}

	/**
	 * Ensure fixed matrix totals are normalized per quantity.
	 *
	 * @return void
	 */
	public function testParsePriceMatrixDividesFixedPriceAcrossQuantity() {
		$product = $this->create_simple_product(
			array(
				'regular_price' => '100',
			)
		);

		$parsed = ppom_parse_price_matrix(
			$this->build_price_matrix_field(
				'price_matrix',
				array(
					array(
						'option'  => '4-4',
						'price'   => '40',
						'label'   => 'Fixed range',
						'id'      => 'fixed_range',
						'isfixed' => 'on',
					),
				)
			),
			$product,
			4,
			100,
			0,
			0
		);

		$this->assertSame( 10.0, (float) $parsed['matrix_price'] );
		$this->assertSame( 0.0, (float) $parsed['matrix_discount'] );
	}

	/**
	 * Build a price matrix field definition.
	 *
	 * @param string $data_name Field data name.
	 * @param array  $options   Matrix options.
	 * @param array  $overrides Field overrides.
	 *
	 * @return array
	 */
	private function build_price_matrix_field( $data_name, $options, $overrides = array() ) {
		return array_merge(
			array(
				'type'      => 'pricematrix',
				'title'     => 'Matrix Pricing',
				'data_name' => $data_name,
				'qty_step'  => '1',
				'options'   => $options,
			),
			$overrides
		);
	}
}
