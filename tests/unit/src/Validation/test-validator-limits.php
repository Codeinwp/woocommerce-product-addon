<?php
/**
 * Unit tests for PPOM\Validation\Validator quantity limits (WooCommerce integration).
 *
 * @package ppom-pro
 */

use PPOM\Validation\Validator;

/**
 * @covers \PPOM\Validation\Validator::get_product_limits
 * @covers \PPOM\Validation\Validator::validation_product_limits
 * @covers \PPOM\Validation\Validator::validation_variation_limits
 */
class Test_Validator_Limits extends PPOM_Test_Case {

	/**
	 * @var callable|null
	 */
	private $client_validation_filter;

	/**
	 * @return void
	 */
	public function tearDown(): void {
		if ( null !== $this->client_validation_filter ) {
			remove_filter( 'ppom_is_client_validation_enabled', $this->client_validation_filter );
			$this->client_validation_filter = null;
		}
		parent::tearDown();
	}

	/**
	 * @return void
	 */
	public function test_get_product_limits_reads_price_matrix_quantities_and_step() {
		$product = $this->create_simple_product( array( 'regular_price' => '10' ) );

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
				$this->build_quantities_field(
					'seat_quantity',
					'Seats',
					array(
						array(
							'option' => 'seat',
							'price'  => '',
							'stock'  => '',
						),
					),
					array(
						'view_control' => 'simple_view',
						'min_qty'      => '3',
						'max_qty'      => '4',
					)
				),
			),
			$product->get_id()
		);

		$limits = Validator::get_product_limits( $product->get_id(), 0 );

		$this->assertSame( 3, $limits['min_qty'] );
		$this->assertSame( 4, $limits['max_qty'] );
		$this->assertSame( 2, $limits['step'] );
		$this->assertSame( 2, (int) $limits['input_value'] );
	}

	/**
	 * @return void
	 */
	public function test_get_product_limits_defaults_when_no_ppom_attached() {
		$product = $this->create_simple_product();

		$limits = Validator::get_product_limits( $product->get_id(), 0 );

		$this->assertSame( 0, $limits['min_qty'] );
		$this->assertSame( 0, $limits['max_qty'] );
		$this->assertSame( 1, $limits['step'] );
		$this->assertSame( -1, $limits['input_value'] );
	}

	/**
	 * @return void
	 */
	public function test_validation_product_limits_clamps_max_to_stock_without_backorders() {
		$product = $this->create_simple_product(
			array(
				'regular_price'  => '10',
				'manage_stock'   => true,
				'stock_quantity' => 3,
				'backorders'     => false,
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

		$validated = Validator::validation_product_limits(
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
		$this->assertSame( 2, $validated['input_value'] );
	}

	/**
	 * @return void
	 */
	public function test_validation_product_limits_noop_when_client_validation_enabled() {
		$this->client_validation_filter = static function () {
			return true;
		};
		add_filter( 'ppom_is_client_validation_enabled', $this->client_validation_filter, 9999 );

		$product = $this->create_simple_product();
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
					),
					array( 'qty_step' => '2' )
				),
			),
			$product->get_id()
		);

		$input = array(
			'min_value' => 7,
			'max_value' => 9,
			'step'      => 3,
		);

		$this->assertSame( $input, Validator::validation_product_limits( $input, $product ) );
	}

	/**
	 * @return void
	 */
	public function test_validation_variation_limits_clamps_max_to_variation_stock() {
		$pair = $this->create_variable_product_with_variation(
			array(),
			array(
				'manage_stock'   => true,
				'stock_quantity' => 3,
				'backorders'     => false,
			)
		);
		$parent = $pair['product'];
		$variation = $pair['variation'];

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

		$validated = Validator::validation_variation_limits(
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
}
