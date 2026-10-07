<?php
/**
 * Class Test_Checkout_Lifecycle
 *
 * @package ppom-pro
 */

require_once __DIR__ . '/class-ppom-test-case.php';

use PPOM\Files\Handler;
use PPOM\WooCommerce\Product\ProductHandler;

class Test_Checkout_Lifecycle extends PPOM_Test_Case {

	/**
	 * Create a focused cart double for add-cart item data tests.
	 *
	 * @param array $cart_contents Cart contents keyed by cart item key.
	 *
	 * @return object
	 */
	private function create_add_cart_item_data_cart_stub( $cart_contents = array() ) {
		return new class( $cart_contents ) {
			public $cart_contents = array();
			public $removed_keys  = array();

			public function __construct( $cart_contents ) {
				$this->cart_contents = $cart_contents;
			}

			public function get_cart_item( $cart_item_key ) {
				return isset( $this->cart_contents[ $cart_item_key ] ) ? $this->cart_contents[ $cart_item_key ] : array();
			}

			public function remove_cart_item( $cart_item_key ) {
				if ( ! isset( $this->cart_contents[ $cart_item_key ] ) ) {
					return false;
				}

				$this->removed_keys[] = $cart_item_key;
				unset( $this->cart_contents[ $cart_item_key ] );

				return true;
			}
		};
	}

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

		$cart_stub = $this->create_add_cart_item_data_cart_stub(
			array(
				'existing-cart-key' => array( 'product_id' => 1 ),
			)
		);

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
	 * Ensure unknown or missing ppom_cart_key does not invoke remove_cart_item (W3).
	 *
	 * @return void
	 */
	public function testAddCartItemDataDoesNotRemoveLineWhenCartKeyMissingOrInvalid() {
		$product = $this->create_simple_product();

		$this->insert_ppom_meta(
			array(
				$this->build_text_field( 'engraving', 'Engraving' ),
			),
			$product->get_id()
		);

		$cart_stub = $this->create_add_cart_item_data_cart_stub();

		WC()->cart = $cart_stub;

		$_POST['ppom'] = array(
			'fields' => array(
				'engraving' => 'Hello',
			),
		);

		ppom_woocommerce_add_cart_item_data( array(), $product->get_id() );
		$this->assertSame( array(), $cart_stub->removed_keys );

		$_POST['ppom_cart_key'] = 'not-a-real-cart-key';
		ppom_woocommerce_add_cart_item_data( array(), $product->get_id() );
		$this->assertSame( array(), $cart_stub->removed_keys );
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

		$cart_stub = $this->create_add_cart_item_data_cart_stub();

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

		$raw_cart_key       = ' Existing_Cart Key ';
		$sanitized_cart_key = sanitize_text_field( wp_unslash( $raw_cart_key ) );
		$cart_stub          = $this->create_add_cart_item_data_cart_stub(
			array(
				$sanitized_cart_key => array( 'product_id' => $product->get_id() ),
			)
		);

		WC()->cart = $cart_stub;

		$_POST['ppom_cart_key'] = $raw_cart_key;
		$_POST['ppom']          = array(
			'fields' => array(
				'engraving' => 'Hello',
			),
		);

		ppom_woocommerce_add_cart_item_data( array(), $product->get_id() );

		$this->assertSame( array( $sanitized_cart_key ), $cart_stub->removed_keys );
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
	/**
	 * Puts pool uploads in the real cart for a product with one file field.
	 * Every name starts out owned by this session.
	 *
	 * @param list<string> $file_names Upload names in the pool.
	 * @param bool         $required   Whether the file field is required.
	 *
	 * @return array{product_id: int, meta_id: int, cart_key: string}
	 */
	private function cart_with_pool_uploads( array $file_names, bool $required = false ): array {
		$this->initialize_woocommerce_checkout_context();

		$product = $this->create_simple_product( array( 'virtual' => true ) );
		$meta_id = $this->insert_ppom_meta(
			array( $this->build_file_field( 'design_file', 'Design', array( 'required' => $required ? 'on' : '' ) ) ),
			$product->get_id()
		);

		foreach ( $file_names as $index => $file_name ) {
			$path = ppom_get_dir_path() . $file_name;
			file_put_contents( $path, 'upload ' . $index );
			$this->artifacts[] = $path;
		}

		WC()->session->set( 'ppom_uploaded_files', $file_names );

		$cart_key = $this->add_product_to_real_cart( $product->get_id(), $this->file_payload( (int) $meta_id, $file_names ) );
		$this->assertIsString( $cart_key );

		return array(
			'product_id' => $product->get_id(),
			'meta_id'    => (int) $meta_id,
			'cart_key'   => $cart_key,
		);
	}

	/**
	 * Posted PPOM payload with one design_file row per name.
	 *
	 * @param int          $meta_id    PPOM group ID.
	 * @param list<string> $file_names Upload names.
	 *
	 * @return array{fields: array{id: string, design_file: list<array{org: string}>}}
	 */
	private function file_payload( int $meta_id, array $file_names ): array {
		$rows = array();
		foreach ( $file_names as $file_name ) {
			$rows[] = array( 'org' => $file_name );
		}

		return array(
			'fields' => array(
				'id'          => (string) $meta_id,
				'design_file' => $rows,
			),
		);
	}

	/**
	 * Places an order from a cart holding the given pool uploads.
	 *
	 * @param list<string> $file_names Upload names in the pool.
	 *
	 * @return array{order: WC_Order, product_id: int}
	 */
	private function order_with_pool_uploads( array $file_names ): array {
		$cart = $this->cart_with_pool_uploads( $file_names );

		return array(
			'order'      => $this->create_order_from_real_cart(),
			'product_id' => $cart['product_id'],
		);
	}

	/**
	 * Path an upload is confirmed to for an order.
	 *
	 * @param WC_Order $order      Order.
	 * @param int      $product_id Product ID.
	 * @param string   $file_name  Upload name.
	 *
	 * @return string
	 */
	private function confirmed_path( WC_Order $order, int $product_id, string $file_name ): string {
		$path              = ppom_get_dir_path( 'confirmed/' . $order->get_id() ) . $product_id . '-' . $file_name;
		$this->artifacts[] = $path;

		return $path;
	}

	/**
	 * Store API (block) checkout confirms the shopper's own upload into the
	 * order's directory.
	 *
	 * @return void
	 */
	public function test_store_api_checkout_confirms_owned_upload() {
		$placed = $this->order_with_pool_uploads( array( 'design.abc123.txt' ) );

		do_action( 'woocommerce_store_api_checkout_order_processed', $placed['order'] );

		$this->assertFileExists( $this->confirmed_path( $placed['order'], $placed['product_id'], 'design.abc123.txt' ) );
		$this->assertFileDoesNotExist( ppom_get_dir_path() . 'design.abc123.txt' );
	}

	/**
	 * A cart reference that is neither owned by the session nor verified on the
	 * cart item (stale or pre-patch data) stays in the pool, while the shopper's
	 * own upload in the same item is confirmed.
	 *
	 * @return void
	 */
	public function test_store_api_checkout_leaves_unowned_pool_file() {
		$placed = $this->order_with_pool_uploads( array( 'mine.abc123.txt', 'other.def456.txt' ) );

		$contents = WC()->cart->get_cart();
		foreach ( $contents as $key => $item ) {
			$contents[ $key ][ Handler::VERIFIED_FILES_KEY ] = array( 'mine.abc123.txt' );
		}
		WC()->cart->set_cart_contents( $contents );
		WC()->session->set( 'ppom_uploaded_files', array( 'mine.abc123.txt' ) );

		do_action( 'woocommerce_store_api_checkout_order_processed', $placed['order'] );

		$this->assertFileExists( $this->confirmed_path( $placed['order'], $placed['product_id'], 'mine.abc123.txt' ) );
		$this->assertFileExists( ppom_get_dir_path() . 'other.def456.txt', 'An unowned pool file must stay where it is.' );
		$this->assertFileDoesNotExist( $this->confirmed_path( $placed['order'], $placed['product_id'], 'other.def456.txt' ) );
	}

	/**
	 * A persistent cart restored in a session without the ownership list (the
	 * shopper switched devices) still confirms the uploads verified when the item
	 * was added.
	 *
	 * @return void
	 */
	public function test_checkout_confirms_verified_upload_without_session_ownership() {
		$placed = $this->order_with_pool_uploads( array( 'design.abc123.txt' ) );

		WC()->session->set( 'ppom_uploaded_files', array() );

		do_action( 'woocommerce_store_api_checkout_order_processed', $placed['order'] );

		$this->assertFileExists( $this->confirmed_path( $placed['order'], $placed['product_id'], 'design.abc123.txt' ) );
	}
	/**
	 * Editing a cart item on a device whose session never owned its upload keeps
	 * the uploads verified on the replaced item, passes required validation,
	 * removes the old item, and still drops a name outside that list.
	 *
	 * @return void
	 */
	public function test_cart_edit_reuses_replaced_item_verified_uploads() {
		$cart = $this->cart_with_pool_uploads( array( 'design.abc123.txt' ), true );

		WC()->session->set( 'ppom_uploaded_files', array() );

		$_POST['ppom_cart_key'] = $cart['cart_key'];
		$_POST['ppom']          = $this->file_payload( $cart['meta_id'], array( 'design.abc123.txt', 'other.def456.txt' ) );

		$this->assertTrue( ProductHandler::validate_product( true, $cart['product_id'], 1 ), 'The required upload must still count as present.' );

		$item = ppom_woocommerce_add_cart_item_data( array(), $cart['product_id'] );

		$this->assertSame( array( array( 'org' => 'design.abc123.txt' ) ), $item['ppom']['fields']['design_file'] );
		$this->assertSame( array( 'design.abc123.txt' ), $item[ Handler::VERIFIED_FILES_KEY ] );
		$this->assertEmpty( WC()->cart->get_cart_item( $cart['cart_key'] ), 'The replaced item must be removed.' );
	}
}
