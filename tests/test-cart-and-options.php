<?php
/**
 * Class Test_Cart_And_Options
 *
 * @package ppom-pro
 */

require_once __DIR__ . '/class-ppom-test-case.php';

class Test_Cart_And_Options extends PPOM_Test_Case {

	/**
	 * Ensure cart meta generation sanitizes known fields and skips hidden/deleted ones.
	 *
	 * @return void
	 */
	public function testMakeMetaDataSanitizesRegisteredFieldsAndSkipsHiddenAndDeletedValues() {
		$product = $this->create_simple_product();
		$meta_id = $this->insert_ppom_meta(
			array(
				array(
					'type'      => 'text',
					'title'     => 'Engraving',
					'data_name' => 'engraving',
				),
				array(
					'type'      => 'text',
					'title'     => 'Hidden Note',
					'data_name' => 'hidden_note',
				),
			),
			$product->get_id()
		);

		$meta = ppom_make_meta_data(
			array(
				'data' => $product,
				'ppom' => array(
					'fields' => array(
						'id'           => (string) $meta_id,
						'engraving'    => '<h1>Hello</h1>',
						'hidden_note'  => '<script>Secret</script>',
						'removed_field'=> 'Should not render',
					),
					'conditionally_hidden' => 'hidden_note',
				),
			),
			'cart'
		);

		$this->assertArrayHasKey( 'engraving', $meta );
		$this->assertSame( 'Engraving', $meta['engraving']['name'] );
		$this->assertSame( 'Hello', $meta['engraving']['value'] );
		$this->assertArrayNotHasKey( 'id', $meta );
		$this->assertArrayNotHasKey( 'hidden_note', $meta );
		$this->assertArrayNotHasKey( 'removed_field', $meta );
	}

	/**
	 * Ensure invalid conditional rules are filtered before HTML attributes are rendered.
	 *
	 * @return void
	 */
	public function testGetConditionalDataAttributesFiltersInvalidRules() {
		$html = ppom_get_conditional_data_attributes(
			array(
				'type'  => 'text',
				'logic' => 'on',
				'conditions' => array(
					'bound'      => 'All',
					'visibility' => 'Show',
					'rules'      => array(
						array(
							'elements'       => 'field_a',
							'operators'      => 'is',
							'element_values' => 'yes',
						),
						array(
							'elements'       => 'field_b',
							'operators'      => 'is',
							'element_values' => '',
						),
						array(
							'elements'              => 'field_c',
							'operators'             => 'between',
							'cond-between-interval' => array(
								'from' => '1',
								'to'   => '3',
							),
						),
						array(
							'elements'              => 'field_d',
							'operators'             => 'between',
							'cond-between-interval' => array(
								'from' => '1',
							),
						),
					),
				),
			)
		);

		$this->assertStringContainsString( 'data-cond="1"', $html );
		$this->assertStringContainsString( 'data-cond-total="2"', $html );
		$this->assertStringContainsString( 'data-cond-operator1="is"', $html );
		$this->assertStringContainsString( 'data-cond-operator2="between"', $html );
		$this->assertStringNotContainsString( 'data-cond-operator3', $html );
	}

	/**
	 * Ensure option conversion handles placeholder options, stock filtering, and percentage pricing.
	 *
	 * @return void
	 */
	public function testConvertOptionsToKeyValAddsFirstOptionSkipsOutOfStockAndConvertsPercent() {
		$product = $this->create_simple_product(
			array(
				'regular_price' => '100',
			)
		);

		$options = ppom_convert_options_to_key_val(
			array(
				array(
					'option' => 'Sold Out',
					'price'  => '5',
					'stock'  => '0',
				),
				array(
					'option' => 'Premium',
					'price'  => '10%',
				),
			),
			array(
				'type'         => 'select',
				'data_name'    => 'plan',
				'first_option' => 'Choose one',
			),
			$product
		);

		$this->assertArrayHasKey( '', $options );
		$this->assertSame( 'Choose one', $options['']['label'] );
		$this->assertArrayHasKey( 'Premium', $options );
		$this->assertArrayNotHasKey( 'Sold Out', $options );
		$this->assertSame( '10%', $options['Premium']['percent'] );
		$this->assertSame( '10%', $options['Premium']['raw_price'] );
		$this->assertSame( 'plan_premium', $options['Premium']['option_id'] );
		$this->assertSame( 10.0, (float) $options['Premium']['price'] );
	}

	/**
	 * Ensure legacy option arrays without persisted IDs still resolve price by generated option ID.
	 *
	 * @return void
	 */
	public function testGetFieldOptionPriceByIdSupportsLegacyOptionsWithoutIds() {
		$product = $this->create_simple_product(
			array(
				'regular_price' => '100',
			)
		);

		$meta_id = $this->insert_ppom_meta(
			array(
				array(
					'type'      => 'select',
					'title'     => 'Plan',
					'data_name' => 'plan',
					'options'   => array(
						array(
							'option' => 'Gold',
							'price'  => '12.5',
						),
					),
				),
			),
			$product->get_id()
		);

		$field_meta      = ppom_get_field_meta_by_dataname( null, 'plan', $meta_id );
		$legacy_options  = ppom_convert_options_to_key_val( $field_meta['options'], $field_meta, $product );
		$generated_id    = $legacy_options['Gold']['id'];
		$price = ppom_get_field_option_price_by_id(
			array(
				'data_name' => 'plan',
				'option_id' => $generated_id,
			),
			$product,
			$meta_id
		);

		$this->assertSame( 12.5, (float) $price );
	}
}
