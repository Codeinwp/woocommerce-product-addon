<?php
/**
 * Tier-2 gap coverage for PPOM\Pricing\Engine helpers that the existing pricing
 * tests don't exercise: generate_field_price, parse_price_matrix,
 * price_has_discount_matrix, price_matrix_chunk, price_fixedprice_chunk,
 * price_check_price_matrix, price_get_product_base.
 *
 * @package ppom-pro
 */

require_once dirname( __DIR__, 2 ) . '/class-ppom-test-case.php';

use PPOM\Pricing\Engine;

/**
 * @covers \PPOM\Pricing\Engine
 */
class Test_Pricing_Engine_Gaps extends PPOM_Test_Case {

	/**
	 * generate_field_price returns the canonical price-row shape; all expected keys
	 * are present and the `apply` slot reflects the caller's value verbatim.
	 *
	 * @return void
	 */
	public function test_generate_field_price_returns_canonical_row_shape() {
		$row = Engine::generate_field_price(
			'12.50',
			array(
				'type'      => 'select',
				'title'     => 'Plan',
				'data_name' => 'plan',
			),
			'addon',
			array(
				'option_id' => '7',
				'raw'       => 'Premium',
			),
			3
		);

		$this->assertSame( 'select', $row['type'] );
		$this->assertSame( 'Plan', $row['label'] );
		$this->assertSame( 'addon', $row['apply'] );
		$this->assertSame( 'plan', $row['data_name'] );
		$this->assertSame( '7', $row['option_id'] );
		$this->assertSame( 'Premium', $row['option_label'] );
		$this->assertSame( 3, $row['quantity'] );
		$this->assertEqualsWithDelta( 12.5, (float) $row['price'], 0.0001 );
		$this->assertFalse( $row['taxable'] );
	}

	/**
	 * The `onetime_taxable=on` flag flips `taxable` to true.
	 *
	 * @return void
	 */
	public function test_generate_field_price_marks_taxable_when_onetime_taxable_flag_set() {
		$row = Engine::generate_field_price(
			'5',
			array(
				'type'             => 'select',
				'title'            => 'Plan',
				'data_name'        => 'plan',
				'onetime_taxable'  => 'on',
			),
			'cart_fee',
			array( 'raw' => 'Premium' )
		);

		$this->assertTrue( $row['taxable'] );
		$this->assertSame( 'cart_fee', $row['apply'] );
	}

	/**
	 * File fields synthesize the option_label as `{count} Files` from the file array.
	 *
	 * @return void
	 */
	public function test_generate_field_price_file_field_emits_count_label() {
		$row = Engine::generate_field_price(
			'2',
			array(
				'type'      => 'file',
				'title'     => 'Attachments',
				'data_name' => 'attachments',
			),
			'addon',
			array(
				array( 'name' => 'a.png' ),
				array( 'name' => 'b.png' ),
				array( 'name' => 'c.png' ),
			)
		);

		$this->assertSame( '3 Files', $row['option_label'] );
	}

	/**
	 * Bulkquantity rows preserve `Base Price` into the row's `base_price` slot.
	 *
	 * @return void
	 */
	public function test_generate_field_price_carries_base_price_from_option() {
		$row = Engine::generate_field_price(
			'4',
			array(
				'type'      => 'bulkquantity',
				'title'     => 'Bulk',
				'data_name' => 'bulk',
			),
			'addon',
			array(
				'option_id'  => '11',
				'Base Price' => '7',
			),
			10
		);

		$this->assertSame( '7', $row['base_price'] );
		$this->assertSame( '11', $row['option_id'] );
	}

	/**
	 * price_get_total_quantities sums quantities for `quantities`/`vm`/`vqmatrix`
	 * field types and is filterable via ppom_prices_total_quantities.
	 *
	 * @return void
	 */
	public function test_price_get_total_quantities_sums_quantities_field_values() {
		$product = $this->create_simple_product();

		$this->insert_ppom_meta(
			array(
				$this->build_quantities_field(
					'tickets',
					'Tickets',
					array(
						array( 'option' => 'Adult', 'price' => '5' ),
						array( 'option' => 'Child', 'price' => '3' ),
					)
				),
			),
			$product->get_id()
		);

		$total = Engine::price_get_total_quantities(
			array(
				'tickets' => array(
					'Adult' => '2',
					'Child' => '3',
				),
				'id'      => 'whatever',
			),
			$product->get_id()
		);

		$this->assertSame( 5, $total );
	}

	/**
	 * price_get_total_quantities short-circuits with 0 for an empty payload.
	 *
	 * @return void
	 */
	public function test_price_get_total_quantities_returns_zero_for_empty_payload() {
		$product = $this->create_simple_product();
		$total   = Engine::price_get_total_quantities( array(), $product->get_id() );

		$this->assertSame( 0, $total );
	}

	/**
	 * price_get_total_fixedprice ignores non-fixedprice rows.
	 *
	 * @return void
	 */
	public function test_price_get_total_fixedprice_ignores_other_row_types() {
		$data = Engine::price_get_total_fixedprice(
			array(
				array( 'type' => 'text', 'price' => 100, 'quantity' => 2 ),
				array( 'type' => 'fixedprice', 'price' => 9, 'quantity' => 1 ),
				array( 'type' => 'fixedprice', 'price' => 4, 'quantity' => 1 ),
			)
		);

		$this->assertSame( 2, $data['quantity'] );
		$this->assertSame( 13, $data['base_price'] );
	}

	/**
	 * price_get_total_measure returns 0 when no measure rows are present.
	 *
	 * @return void
	 */
	public function test_price_get_total_measure_returns_zero_when_no_measure_rows() {
		$total = Engine::price_get_total_measure(
			array(
				array( 'type' => 'addon', 'quantity' => 5 ),
			)
		);

		$this->assertSame( 0, $total );
	}

	/**
	 * price_get_total_measure multiplies rows together (not adds), so a single
	 * row keeps its value but multiple rows compound.
	 *
	 * @return void
	 */
	public function test_price_get_total_measure_multiplies_rows_together() {
		$total = Engine::price_get_total_measure(
			array(
				array( 'type' => 'measure', 'quantity' => 4 ),
				array( 'type' => 'measure', 'quantity' => 5 ),
			)
		);

		$this->assertSame( 20, $total );
	}

	/**
	 * price_bulkquantity_chunk returns an empty array when none of the configured
	 * ranges include the given product quantity.
	 *
	 * @return void
	 */
	public function test_price_bulkquantity_chunk_returns_empty_when_no_range_matches() {
		$product = $this->create_simple_product();

		$rows = array(
			array(
				'Quantity Range' => '1-5',
				'Base Price'     => '10',
				'ID'             => 'a',
			),
		);

		$this->assertSame( array(), Engine::price_bulkquantity_chunk( $product, $rows, 99 ) );
	}

	/**
	 * price_has_discount_matrix returns false when the product has no pricematrix field.
	 *
	 * @return void
	 */
	public function test_price_has_discount_matrix_returns_false_for_product_without_matrix() {
		$product = $this->create_simple_product();
		$this->insert_ppom_meta(
			array( $this->build_text_field( 'engraving', 'Engraving' ) ),
			$product->get_id()
		);

		$this->assertFalse( Engine::price_has_discount_matrix( $product, 1 ) );
	}

	/**
	 * Even when a pricematrix field exists, if it doesn't carry a discount flag,
	 * price_has_discount_matrix still returns false (matrix vs discount matrix
	 * are different concepts).
	 *
	 * @return void
	 */
	public function test_price_has_discount_matrix_returns_false_for_non_discount_matrix() {
		$product = $this->create_simple_product();
		$this->insert_ppom_meta(
			array(
				$this->build_price_matrix_field(
					'matrix_a',
					array(
						array( 'option' => '1-10', 'price' => '5' ),
					)
				),
			),
			$product->get_id()
		);

		$this->assertFalse( Engine::price_has_discount_matrix( $product, 5 ) );
	}

	/**
	 * parse_price_matrix returns the raw row price as matrix_price when the
	 * matrix does not carry a `discount` flag.
	 *
	 * @return void
	 */
	public function test_parse_price_matrix_returns_raw_price_when_not_discount() {
		$product = $this->create_simple_product();
		$field   = $this->build_price_matrix_field(
			'matrix_a',
			array(
				array( 'option' => '1-10', 'price' => '8' ),
				array( 'option' => '11-20', 'price' => '6' ),
			)
		);

		$matrix = Engine::parse_price_matrix( $field, $product, 5, 100.0, 0.0, 0.0 );

		$this->assertEqualsWithDelta( 8.0, (float) $matrix['matrix_price'], 0.0001 );
		$this->assertSame( 0.0, (float) $matrix['matrix_discount'] );
	}

	/**
	 * parse_price_matrix falls back to base_price when no row matches the quantity.
	 *
	 * @return void
	 */
	public function test_parse_price_matrix_uses_base_price_when_no_match() {
		$product = $this->create_simple_product();
		$field   = $this->build_price_matrix_field(
			'matrix_a',
			array( array( 'option' => '1-5', 'price' => '8' ) )
		);

		$matrix = Engine::parse_price_matrix( $field, $product, 999, 17.5, 0.0, 0.0 );

		$this->assertEqualsWithDelta( 17.5, (float) $matrix['matrix_price'], 0.0001 );
	}

	/**
	 * price_fixedprice_chunk matches by string `raw` field equal to quantity.
	 *
	 * @return void
	 */
	public function test_price_fixedprice_chunk_matches_row_by_raw_equal_to_quantity() {
		$product = $this->create_simple_product();

		$rows = array(
			array(
				'data_name' => 'fp',
				'raw'       => '5',
				'price'     => 100,
			),
			array(
				'data_name' => 'fp',
				'raw'       => '10',
				'price'     => 180,
			),
		);

		$match = Engine::price_fixedprice_chunk( $product, $rows, 10 );

		$this->assertIsArray( $match );
		$this->assertSame( '10', $match['raw'] );
		$this->assertSame( 180, $match['price'] );
	}

	/**
	 * price_fixedprice_chunk returns an empty array when none match.
	 *
	 * @return void
	 */
	public function test_price_fixedprice_chunk_returns_empty_when_no_match() {
		$product = $this->create_simple_product();
		$rows    = array(
			array( 'data_name' => 'fp', 'raw' => '5', 'price' => 100 ),
		);

		$this->assertSame( array(), Engine::price_fixedprice_chunk( $product, $rows, 999 ) );
	}

	/**
	 * price_check_price_matrix attaches the first non-hidden pricematrix field to
	 * the cart item under `ppom.price_matrix_found`.
	 *
	 * @return void
	 */
	public function test_price_check_price_matrix_attaches_matrix_field_to_cart_item() {
		$product = $this->create_simple_product();
		$this->insert_ppom_meta(
			array( $this->build_price_matrix_field( 'matrix_a', array( array( 'option' => '1-10', 'price' => '5' ) ) ) ),
			$product->get_id()
		);

		$item = array(
			'data'       => $product,
			'product_id' => $product->get_id(),
			'quantity'   => 1,
			'ppom'       => array(
				'fields'               => array(),
				'conditionally_hidden' => '',
			),
		);

		$result = Engine::price_check_price_matrix( $item, $item );

		$this->assertArrayHasKey( 'price_matrix_found', $result['ppom'] );
		$this->assertSame( 'matrix_a', $result['ppom']['price_matrix_found']['data_name'] );
	}

	/**
	 * price_check_price_matrix returns the cart item unchanged when the product
	 * carries no pricematrix field.
	 *
	 * @return void
	 */
	public function test_price_check_price_matrix_passes_through_when_no_matrix_field() {
		$product = $this->create_simple_product();
		$this->insert_ppom_meta(
			array( $this->build_text_field( 'engraving', 'Engraving' ) ),
			$product->get_id()
		);

		$item = array(
			'data'       => $product,
			'product_id' => $product->get_id(),
			'quantity'   => 1,
			'ppom'       => array(
				'fields'               => array(),
				'conditionally_hidden' => '',
			),
		);

		$result = Engine::price_check_price_matrix( $item, $item );

		$this->assertSame( $item, $result );
	}

	/**
	 * price_check_price_matrix is a no-op when the item is not a PPOM item.
	 *
	 * @return void
	 */
	public function test_price_check_price_matrix_returns_item_unchanged_without_ppom_key() {
		$item = array( 'data' => $this->create_simple_product() );

		$this->assertSame( $item, Engine::price_check_price_matrix( $item, $item ) );
	}

	/**
	 * price_get_product_base returns the base price unchanged when no matrix,
	 * no bulkquantities, no fixedprice, and no measure apply.
	 *
	 * @return void
	 */
	public function test_price_get_product_base_returns_base_when_no_modifiers_apply() {
		$product = $this->create_simple_product( array( 'regular_price' => '15' ) );
		$this->insert_ppom_meta(
			array( $this->build_text_field( 'engraving', 'Engraving' ) ),
			$product->get_id()
		);

		$discount = 0;

		$info = Engine::price_get_product_base(
			15.0,
			$product,
			array( 'engraving' => 'Hello' ),
			1,
			array(),
			$discount,
			null
		);

		$this->assertSame( 'product', $info['source'] );
		$this->assertEqualsWithDelta( 15.0, (float) $info['price'], 0.0001 );
	}

	/**
	 * price_get_product_base zeroes the base when a fixedprice row was already
	 * computed (the fixed price replaces the catalog price downstream).
	 *
	 * @return void
	 */
	public function test_price_get_product_base_zeroes_base_for_fixedprice_rows() {
		$product = $this->create_simple_product( array( 'regular_price' => '99' ) );
		$this->insert_ppom_meta(
			array( $this->build_text_field( 'engraving', 'Engraving' ) ),
			$product->get_id()
		);

		$discount = 0;

		$info = Engine::price_get_product_base(
			99.0,
			$product,
			array(),
			1,
			array(
				array(
					'type'       => 'fixedprice',
					'apply'      => 'addon',
					'price'      => 25,
					'quantity'   => 1,
					'base_price' => 0,
				),
			),
			$discount,
			null
		);

		$this->assertSame( 'fixedprice', $info['source'] );
		$this->assertEqualsWithDelta( 0.0, (float) $info['price'], 0.0001 );
	}

	/**
	 * price_get_product_base multiplies the base price by the measure factor.
	 *
	 * @return void
	 */
	public function test_price_get_product_base_multiplies_by_measure() {
		$product = $this->create_simple_product( array( 'regular_price' => '5' ) );
		$this->insert_ppom_meta(
			array( $this->build_text_field( 'engraving', 'Engraving' ) ),
			$product->get_id()
		);

		$discount = 0;

		$info = Engine::price_get_product_base(
			5.0,
			$product,
			array(),
			1,
			array(
				array(
					'type'             => 'measure',
					'apply'            => 'addon',
					'price'            => 0,
					'quantity'         => 4,
					'price-multiplier' => 1,
					'base_price'       => 0,
				),
			),
			$discount,
			null
		);

		$this->assertSame( 'measure', $info['source'] );
		$this->assertEqualsWithDelta( 20.0, (float) $info['price'], 0.0001 );
	}

	/**
	 * The ppom_price_info filter can override the computed base price+source pair.
	 *
	 * @return void
	 */
	public function test_price_get_product_base_filter_can_override_result() {
		$product = $this->create_simple_product( array( 'regular_price' => '10' ) );
		$this->insert_ppom_meta(
			array( $this->build_text_field( 'engraving', 'Engraving' ) ),
			$product->get_id()
		);

		$discount = 0;

		$filter = static function () {
			return array(
				'price'  => 123.45,
				'source' => 'filter',
			);
		};
		add_filter( 'ppom_price_info', $filter );

		try {
			$info = Engine::price_get_product_base( 10.0, $product, array(), 1, array(), $discount, null );
		} finally {
			remove_filter( 'ppom_price_info', $filter );
		}

		$this->assertSame( 'filter', $info['source'] );
		$this->assertEqualsWithDelta( 123.45, (float) $info['price'], 0.0001 );
	}
}
