<?php
/**
 * Regression coverage for Bulk Quantity option payload compatibility.
 *
 * @package ppom-pro
 */

class Test_Bulkquantity_Options_Regression extends PPOM_Test_Case {

	/**
	 * @return array<int, array<string, string>>
	 */
	private function bulkquantity_rows() {
		return array(
			array(
				'Quantity Range' => '1-10',
				'Base Price'     => '5',
				'ID'             => '7',
			),
		);
	}

	/**
	 * @param mixed $options Bulk Quantity options payload.
	 * @return array<string, mixed>
	 */
	private function bulkquantity_field( $options ) {
		return array(
			'type'       => 'bulkquantity',
			'title'      => 'Bulk Quantity',
			'data_name'  => 'bulk_quantity',
			'options'    => $options,
			'visibility' => 'everyone',
			'status'     => 'on',
		);
	}

	/**
	 * @return void
	 */
	public function test_frontend_loader_preserves_legacy_json_string_bulkquantity_options() {
		$product = $this->create_simple_product();
		$this->insert_ppom_meta( array( $this->bulkquantity_field( wp_json_encode( $this->bulkquantity_rows() ) ) ), $product->get_id() );

		PPOM_FRONTEND_SCRIPTS::init();
		$reflection = new ReflectionClass( 'PPOM_FRONTEND_SCRIPTS' );

		$get_scripts = $reflection->getMethod( 'get_scripts' );
		$get_scripts->setAccessible( true );
		PPOM_SCRIPTS::register_scripts( $get_scripts->invoke( null ) );

		$get_styles = $reflection->getMethod( 'get_styles' );
		$get_styles->setAccessible( true );
		PPOM_SCRIPTS::register_styles( $get_styles->invoke( null ) );

		$captured = null;
		$filter   = static function ( $vars ) use ( &$captured ) {
			$captured = $vars;
			return $vars;
		};
		add_filter( 'ppom_input_vars', $filter, 10, 1 );

		try {
			PPOM_FRONTEND_SCRIPTS::load_scripts_by_product_id( $product->get_id() );
		} finally {
			remove_filter( 'ppom_input_vars', $filter, 10 );
		}

		$this->assertIsArray( $captured );
		$this->assertSame( 'bulkquantity', $captured['field_meta'][0]['type'] );
		$this->assertIsString( $captured['field_meta'][0]['options'] );

		$decoded = json_decode( $captured['field_meta'][0]['options'], true );
		$this->assertIsArray( $decoded );
		$this->assertSame( '7', $decoded[0]['ID'] );
	}

	/**
	 * @return void
	 */
	public function test_price_bulkquantity_chunk_returns_row_matching_in_range_quantity() {
		$product = $this->create_simple_product();
		$rows    = array(
			array(
				'Quantity Range' => '1-10',
				'Base Price'     => '5',
				'ID'             => '7',
			),
			array(
				'Quantity Range' => '11-20',
				'Base Price'     => '4',
				'ID'             => '8',
			),
		);

		$match = \PPOM\Pricing\Engine::price_bulkquantity_chunk( $product, $rows, 12 );

		$this->assertIsArray( $match );
		$this->assertSame( '11-20', $match['Quantity Range'] );
		$this->assertSame( '4', $match['Base Price'] );
		$this->assertSame( '8', $match['ID'] );
	}

	/**
	 * @return void
	 */
	public function test_price_bulkquantity_chunk_returns_empty_when_quantity_outside_all_ranges() {
		$product = $this->create_simple_product();
		$rows    = array(
			array(
				'Quantity Range' => '1-10',
				'Base Price'     => '5',
				'ID'             => '7',
			),
		);

		$match = \PPOM\Pricing\Engine::price_bulkquantity_chunk( $product, $rows, 999 );

		$this->assertSame( array(), $match );
	}

	/**
	 * @return void
	 */
	public function test_get_field_prices_decodes_legacy_json_string_bulkquantity_options() {
		$product    = $this->create_simple_product( array( 'regular_price' => '10' ) );
		$product_id = $product->get_id();

		$this->insert_ppom_meta(
			array(
				$this->bulkquantity_field( wp_json_encode( $this->bulkquantity_rows() ) ),
			),
			$product_id
		);

		$posted = array(
			'bulk_quantity' => array(
				'qty'    => 3,
				'option' => 'Base Price',
			),
		);

		$product_quantity = 3.0;
		$field_prices     = \PPOM\Pricing\Engine::get_field_prices(
			$posted,
			$product_id,
			$product_quantity,
			0
		);

		$this->assertIsArray( $field_prices );
		$this->assertCount( 1, $field_prices );
		$this->assertSame( 'bulkquantity', $field_prices[0]['type'] );
		$this->assertSame( 'bulk_quantity', $field_prices[0]['data_name'] );
		$this->assertEqualsWithDelta( 5.0, (float) $field_prices[0]['price'], 0.0001 );
		$this->assertSame( '5', $field_prices[0]['base_price'] );
	}
}
