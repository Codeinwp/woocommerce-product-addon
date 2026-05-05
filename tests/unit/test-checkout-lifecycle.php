<?php
/**
 * Class Test_Checkout_Lifecycle
 *
 * @package ppom-pro
 */

require_once __DIR__ . '/class-ppom-test-case.php';

class Test_Checkout_Lifecycle extends PPOM_Test_Case {

	/**
	 * Ensure add-to-cart validation fails when a required field is missing.
	 *
	 * @return void
	 */
	public function testWooCommerceValidateProductAddsNoticeWhenRequiredFieldIsMissing() {
		$product = $this->create_simple_product();

		$this->insert_ppom_meta(
			array(
				$this->build_text_field(
					'engraving',
					'Engraving',
					array(
						'required' => 'on',
					)
				),
			),
			$product->get_id()
		);

		$_POST['ppom'] = array(
			'fields' => array(
				'unrelated_field' => 'keep-validation-running',
			),
		);

		$passed = ppom_woocommerce_validate_product( true, $product->get_id(), 1 );

		$this->assertFalse( $passed );
		$this->assertSame( 1, wc_notice_count( 'error' ) );
	}

	/**
	 * Ensure hidden required fields are skipped during add-to-cart validation.
	 *
	 * @return void
	 */
	public function testWooCommerceValidateProductSkipsConditionallyHiddenRequiredField() {
		$product = $this->create_simple_product();

		$this->insert_ppom_meta(
			array(
				$this->build_text_field(
					'engraving',
					'Engraving',
					array(
						'required' => 'on',
					)
				),
			),
			$product->get_id()
		);

		$_POST['ppom'] = array(
			'fields'               => array(
				'unrelated_field' => 'keep-validation-running',
			),
			'conditionally_hidden' => 'engraving',
		);

		$passed = ppom_woocommerce_validate_product( true, $product->get_id(), 1 );

		$this->assertTrue( $passed );
		$this->assertSame( 0, wc_notice_count( 'error' ) );
	}

	/**
	 * Ensure the add-cart hook stores the posted PPOM payload on the cart item.
	 *
	 * @return void
	 */
	public function testWooCommerceAddCartItemDataStoresPostedPPOMPayload() {
		$product = $this->create_simple_product();

		$this->insert_ppom_meta(
			array(
				$this->build_text_field( 'engraving', 'Engraving' ),
			),
			$product->get_id()
		);

		$cart_stub = new class() {
			public $removed_keys = array();

			public function remove_cart_item( $cart_item_key ) {
				$this->removed_keys[] = $cart_item_key;
			}
		};

		WC()->cart = $cart_stub;

		$_POST['ppom_cart_key'] = 'existing-cart-key';
		$_POST['ppom']          = array(
			'fields'               => array(
				'engraving' => 'Hello',
			),
			'conditionally_hidden' => 'hidden_note',
		);

		$cart_item = ppom_woocommerce_add_cart_item_data( array(), $product->get_id() );

		$this->assertSame( array( 'existing-cart-key' ), $cart_stub->removed_keys );
		$this->assertSame( $_POST['ppom'], $cart_item['ppom'] );
	}

	/**
	 * Ensure add-cart logic does not fatal when WooCommerce cart is missing.
	 *
	 * @return void
	 */
	public function testWooCommerceAddCartItemDataSkipsRemovalWhenCartMissing() {
		$product = $this->create_simple_product();

		$this->insert_ppom_meta(
			array(
				$this->build_text_field( 'engraving', 'Engraving' ),
			),
			$product->get_id()
		);

		WC()->cart = null;

		$_POST['ppom_cart_key'] = 'existing-cart-key';
		$_POST['ppom']          = array(
			'fields' => array(
				'engraving' => 'Hello',
			),
		);

		$cart_item = ppom_woocommerce_add_cart_item_data( array(), $product->get_id() );

		$this->assertArrayHasKey( 'ppom', $cart_item );
		$this->assertSame( $_POST['ppom'], $cart_item['ppom'] );
	}

	/**
	 * Ensure add-cart logic skips removal when the cart key is missing.
	 *
	 * @return void
	 */
	public function testWooCommerceAddCartItemDataSkipsRemovalWithoutCartKey() {
		$product = $this->create_simple_product();

		$this->insert_ppom_meta(
			array(
				$this->build_text_field( 'engraving', 'Engraving' ),
			),
			$product->get_id()
		);

		$cart_stub = new class() {
			public $removed_keys = array();

			public function remove_cart_item( $cart_item_key ) {
				$this->removed_keys[] = $cart_item_key;
			}
		};

		WC()->cart = $cart_stub;

		$_POST['ppom'] = array(
			'fields' => array(
				'engraving' => 'Hello',
			),
		);

		$cart_item = ppom_woocommerce_add_cart_item_data( array(), $product->get_id() );

		$this->assertSame( array(), $cart_stub->removed_keys );
		$this->assertSame( $_POST['ppom'], $cart_item['ppom'] );
	}

	/**
	 * Ensure add-cart logic sanitizes the cart key before removal.
	 *
	 * @return void
	 */
	public function testWooCommerceAddCartItemDataSanitizesCartKeyBeforeRemoval() {
		$product = $this->create_simple_product();

		$this->insert_ppom_meta(
			array(
				$this->build_text_field( 'engraving', 'Engraving' ),
			),
			$product->get_id()
		);

		$cart_stub = new class() {
			public $removed_keys = array();

			public function remove_cart_item( $cart_item_key ) {
				$this->removed_keys[] = $cart_item_key;
			}
		};

		WC()->cart = $cart_stub;

		$raw_cart_key           = ' Existing_Cart Key ';
		$_POST['ppom_cart_key'] = $raw_cart_key;
		$_POST['ppom']          = array(
			'fields' => array(
				'engraving' => 'Hello',
			),
		);

		ppom_woocommerce_add_cart_item_data( array(), $product->get_id() );

		$this->assertSame( array( sanitize_key( $raw_cart_key ) ), $cart_stub->removed_keys );
	}

	/**
	 * Ensure session restore recalculates addon pricing for simple products.
	 *
	 * @return void
	 */
	public function testWooCommerceGetCartItemFromSessionRepricesSimpleProductWithAddons() {
		$product = $this->create_simple_product(
			array(
				'regular_price' => '10',
			)
		);

		$this->insert_ppom_meta(
			array(
				$this->build_select_field(
					'plan',
					'Plan',
					array(
						array(
							'option' => 'Premium',
							'price'  => '5',
						),
					)
				),
			),
			$product->get_id()
		);

		$restored = $this->restore_cart_item_from_session(
			$product,
			array(
				'plan' => 'Premium',
			)
		);

		$this->assertSame( 15.0, (float) $restored['data']->get_price() );
	}

	/**
	 * Ensure repeated restores of the same session payload keep a stable addon-adjusted price.
	 *
	 * @return void
	 */
	public function testWooCommerceGetCartItemFromSessionKeepsStablePriceAcrossRepeatedRestores() {
		$product = $this->create_simple_product(
			array(
				'regular_price' => '10',
			)
		);

		$this->insert_ppom_meta(
			array(
				$this->build_select_field(
					'plan',
					'Plan',
					array(
						array(
							'option' => 'Premium',
							'price'  => '5',
						),
					)
				),
			),
			$product->get_id()
		);

		$first_restore  = $this->restore_cart_item_from_session(
			wc_get_product( $product->get_id() ),
			array(
				'plan' => 'Premium',
			)
		);
		$second_restore = $this->restore_cart_item_from_session(
			wc_get_product( $product->get_id() ),
			array(
				'plan' => 'Premium',
			)
		);

		$this->assertSame( 15.0, (float) $first_restore['data']->get_price() );
		$this->assertSame( 15.0, (float) $second_restore['data']->get_price() );
	}

	/**
	 * Ensure session restore recalculates addon pricing for variations using parent PPOM fields.
	 *
	 * @return void
	 */
	public function testWooCommerceGetCartItemFromSessionRepricesVariableProductWithAddons() {
		$products  = $this->create_variable_product_with_variation(
			array(),
			array(
				'regular_price' => '12',
			)
		);
		$product   = $products['product'];
		$variation = $products['variation'];

		$this->insert_ppom_meta(
			array(
				$this->build_select_field(
					'plan',
					'Plan',
					array(
						array(
							'option' => 'Premium',
							'price'  => '3',
						),
					)
				),
			),
			$product->get_id()
		);

		$restored = $this->restore_cart_item_from_session(
			$variation,
			array(
				'plan' => 'Premium',
			),
			1,
			'',
			$variation->get_id()
		);

		$this->assertSame( $product->get_id(), (int) $restored['product_id'] );
		$this->assertSame( $variation->get_id(), (int) $restored['variation_id'] );
		$this->assertSame( 15.0, (float) $restored['data']->get_price() );
	}

	/**
	 * Ensure recalculation responds to quantity changes when matrix pricing is active.
	 *
	 * @return void
	 */
	public function testWooCommerceGetCartItemFromSessionRecalculatesMatrixPriceWhenQuantityChanges() {
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
							'option' => '5',
							'price'  => '12',
							'label'  => 'Low quantity',
							'id'     => 'low_qty',
						),
						array(
							'option' => '5-10',
							'price'  => '10',
							'label'  => 'Range quantity',
							'id'     => 'range_qty',
						),
						array(
							'option' => '11',
							'price'  => '8',
							'label'  => 'High quantity',
							'id'     => 'high_qty',
						),
					)
				),
			),
			$product->get_id()
		);

		$low_quantity   = $this->restore_cart_item_from_session( wc_get_product( $product->get_id() ), array(), 3 );
		$range_quantity = $this->restore_cart_item_from_session( wc_get_product( $product->get_id() ), array(), 7 );
		$high_quantity  = $this->restore_cart_item_from_session( wc_get_product( $product->get_id() ), array(), 15 );

		$this->assertSame( 12.0, (float) $low_quantity['data']->get_price() );
		$this->assertSame( 10.0, (float) $range_quantity['data']->get_price() );
		$this->assertSame( 8.0, (float) $high_quantity['data']->get_price() );
	}

	/**
	 * Ensure hidden price-matrix fields are ignored during session restore repricing.
	 *
	 * @return void
	 */
	public function testWooCommerceGetCartItemFromSessionIgnoresHiddenPriceMatrix() {
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
							'option' => '1-2',
							'price'  => '8',
							'label'  => 'Hidden range',
							'id'     => 'hidden_range',
						),
					)
				),
			),
			$product->get_id()
		);

		$restored = $this->restore_cart_item_from_session( wc_get_product( $product->get_id() ), array(), 1, 'price_matrix' );

		$this->assertSame( 10.0, (float) $restored['data']->get_price() );
		$this->assertSame( array(), $restored['ppom']['price_matrix_found'] );
	}

	/**
	 * Ensure unknown option values do not affect restored product pricing.
	 *
	 * @return void
	 */
	public function testWooCommerceGetCartItemFromSessionIgnoresUnknownOptionValue() {
		$product = $this->create_simple_product(
			array(
				'regular_price' => '10',
			)
		);

		$this->insert_ppom_meta(
			array(
				$this->build_select_field(
					'plan',
					'Plan',
					array(
						array(
							'option' => 'Premium',
							'price'  => '5',
						),
					)
				),
			),
			$product->get_id()
		);

		$restored = $this->restore_cart_item_from_session(
			wc_get_product( $product->get_id() ),
			array(
				'plan' => 'Unknown',
			)
		);

		$this->assertSame( 10.0, (float) $restored['data']->get_price() );
	}

	/**
	 * Ensure variation restores without a parent PPOM schema keep the base variation price.
	 *
	 * @return void
	 */
	public function testWooCommerceGetCartItemFromSessionLeavesVariationBasePriceWithoutParentMeta() {
		$products  = $this->create_variable_product_with_variation(
			array(),
			array(
				'regular_price' => '12',
			)
		);
		$variation = $products['variation'];

		$restored = $this->restore_cart_item_from_session(
			$variation,
			array(
				'plan' => 'Premium',
			),
			1,
			'',
			$variation->get_id()
		);

		$this->assertSame( 12.0, (float) $restored['data']->get_price() );
	}

	/**
	 * Ensure order line item metadata stores formatted display values and raw PPOM payload.
	 *
	 * @return void
	 */
	public function testWooCommerceOrderItemMetaStoresDisplayAndRawPayload() {
		$product = $this->create_simple_product();

		$this->insert_ppom_meta(
			array(
				$this->build_text_field( 'engraving', 'Engraving' ),
			),
			$product->get_id()
		);

		$order = $this->create_order_with_product( $product );
		$item  = $this->get_first_order_item( $order );

		ppom_woocommerce_order_item_meta(
			$item,
			'ppom-test-cart-key',
			array(
				'data' => $product,
				'ppom' => array(
					'fields' => array(
						'engraving' => '<strong>Hello</strong>',
					),
				),
			),
			$order
		);

		$item->save();

		$stored_payload = $item->get_meta( '_ppom_fields', true );

		$this->assertSame( 'Hello', $item->get_meta( 'engraving', true ) );
		$this->assertIsArray( $stored_payload );
		$this->assertSame( '<strong>Hello</strong>', $stored_payload['fields']['engraving'] );
	}

	/**
	 * Ensure variation order items persist PPOM metadata using the parent field schema.
	 *
	 * @return void
	 */
	public function testWooCommerceOrderItemMetaStoresVariationPayload() {
		$products  = $this->create_variable_product_with_variation(
			array(),
			array(
				'regular_price' => '12',
			)
		);
		$product   = $products['product'];
		$variation = $products['variation'];

		$this->insert_ppom_meta(
			array(
				$this->build_text_field( 'engraving', 'Engraving' ),
			),
			$product->get_id()
		);

		$order = $this->create_order_with_product( $variation );
		$item  = $this->get_first_order_item( $order );

		ppom_woocommerce_order_item_meta(
			$item,
			'ppom-test-cart-key',
			array(
				'data'         => $variation,
				'variation_id' => $variation->get_id(),
				'ppom'         => array(
					'fields' => array(
						'engraving' => 'Variation Hello',
					),
				),
			),
			$order
		);

		$item->save();

		$this->assertSame( 'Variation Hello', $item->get_meta( 'engraving', true ) );
		$this->assertSame( 'Variation Hello', $item->get_meta( '_ppom_fields', true )['fields']['engraving'] );
	}

	/**
	 * Ensure checkbox selections persist as display text and raw array payloads on order items.
	 *
	 * @return void
	 */
	public function testWooCommerceOrderItemMetaStoresCheckboxSelections() {
		$product = $this->create_simple_product();

		$this->insert_ppom_meta(
			array(
				$this->build_checkbox_field(
					'extras',
					'Extras',
					array(
						array(
							'option' => 'Red',
						),
						array(
							'option' => 'Blue',
						),
					)
				),
			),
			$product->get_id()
		);

		$order = $this->create_order_with_product( $product );
		$item  = $this->get_first_order_item( $order );

		ppom_woocommerce_order_item_meta(
			$item,
			'ppom-test-cart-key',
			array(
				'data' => $product,
				'ppom' => array(
					'fields' => array(
						'extras' => array( 'Red', 'Blue' ),
					),
				),
			),
			$order
		);

		$item->save();

		$stored_payload = $item->get_meta( '_ppom_fields', true );

		$this->assertSame( 'Red, Blue', $item->get_meta( 'extras', true ) );
		$this->assertSame( array( 'Red', 'Blue' ), $stored_payload['fields']['extras'] );
	}
}
