<?php
/**
 * Shared helpers for PPOM PHPUnit coverage.
 *
 * @package ppom-pro
 */

abstract class PPOM_Test_Case extends WP_UnitTestCase {

	/**
	 * Original WooCommerce cart instance.
	 *
	 * @var mixed
	 */
	protected $original_wc_cart;

	/**
	 * Original WooCommerce session instance.
	 *
	 * @var mixed
	 */
	protected $original_wc_session;

	/**
	 * Original WooCommerce customer instance.
	 *
	 * @var mixed
	 */
	protected $original_wc_customer;

	/**
	 * Test user with edit_products capability for REST read routes.
	 *
	 * @var int|null
	 */
	protected $ppom_test_read_user;

	/**
	 * Test user with manage_woocommerce capability for REST write routes.
	 *
	 * @var int|null
	 */
	protected $ppom_test_write_user;

	/**
	 * Whether test license filters are registered.
	 *
	 * @var bool
	 */
	protected $ppom_test_license_filters_active = false;

	/**
	 * @var bool
	 */
	protected $ppom_test_license_want_valid = true;

	/**
	 * @var int
	 */
	protected $ppom_test_license_plan_value = 1;

	/**
	 * Reset globals and settings used by the helper-heavy tests.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$_POST    = array();
		$_GET     = array();
		$_REQUEST = array();

		$this->unset_ppom_option( 'ppom_rest_secret_key' );
		$this->unset_ppom_option( 'ppom_api_enable' );
		$this->unset_ppom_option( 'ppom_enable_client_validation' );
		$this->unset_ppom_option( 'ppom_taxable_option_price' );

		$this->original_wc_cart     = function_exists( 'WC' ) && isset( WC()->cart ) ? WC()->cart : null;
		$this->original_wc_session  = function_exists( 'WC' ) && isset( WC()->session ) ? WC()->session : null;
		$this->original_wc_customer = function_exists( 'WC' ) && isset( WC()->customer ) ? WC()->customer : null;
		wc_clear_notices();
	}

	/**
	 * Clean globals after each test.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		$_POST    = array();
		$_GET     = array();
		$_REQUEST = array();
		wc_clear_notices();

		if ( function_exists( 'WC' ) ) {
			WC()->cart     = $this->original_wc_cart;
			WC()->session  = $this->original_wc_session;
			WC()->customer = $this->original_wc_customer;
		}

		$this->remove_ppom_license_filters();
		wp_set_current_user( 0 );

		parent::tearDown();
	}

	/**
	 * Stub Themeisle-style license filters for the current test.
	 *
	 * @param string $status Use 'valid' or 'invalid'.
	 * @param int    $plan   Plan id 1–3 when valid (Essential / Plus / VIP tiers via get_license_category).
	 *
	 * @return void
	 */
	protected function with_ppom_license_filters( $status, $plan = 1 ) {
		$this->remove_ppom_license_filters();
		$this->ppom_test_license_want_valid = ( 'valid' === $status );
		$this->ppom_test_license_plan_value = max( 1, min( 3, (int) $plan ) );
		$priority                           = PHP_INT_MAX - 10;

		add_filter( 'product_ppom_license_status', array( $this, 'ppom_test_filter_license_status' ), $priority, 1 );
		add_filter( 'product_ppom_license_plan', array( $this, 'ppom_test_filter_license_plan' ), $priority, 1 );
		$this->ppom_test_license_filters_active = true;
	}

	/**
	 * Remove license filters registered by with_ppom_license_filters().
	 *
	 * @return void
	 */
	protected function remove_ppom_license_filters() {
		if ( ! $this->ppom_test_license_filters_active ) {
			return;
		}
		$priority = PHP_INT_MAX - 10;
		remove_filter( 'product_ppom_license_status', array( $this, 'ppom_test_filter_license_status' ), $priority );
		remove_filter( 'product_ppom_license_plan', array( $this, 'ppom_test_filter_license_plan' ), $priority );
		$this->ppom_test_license_filters_active = false;
	}

	/**
	 * @param mixed $value Prior filter value.
	 *
	 * @return string
	 */
	public function ppom_test_filter_license_status( $value ) {
		return $this->ppom_test_license_want_valid ? 'valid' : '';
	}

	/**
	 * @param mixed $value Prior filter value.
	 *
	 * @return int
	 */
	public function ppom_test_filter_license_plan( $value ) {
		return $this->ppom_test_license_want_valid ? $this->ppom_test_license_plan_value : 0;
	}

	/**
	 * Create and persist a simple WooCommerce product.
	 *
	 * @param array $args Product overrides.
	 *
	 * @return WC_Product_Simple
	 */
	protected function create_simple_product( $args = array() ) {
		$product = new WC_Product_Simple();

		$name          = isset( $args['name'] ) ? $args['name'] : 'PPOM Test Product ' . wp_generate_password( 6, false );
		$regular_price = isset( $args['regular_price'] ) ? (string) $args['regular_price'] : '10';

		$product->set_name( $name );
		$product->set_status( 'publish' );
		$product->set_catalog_visibility( 'visible' );
		$product->set_regular_price( $regular_price );
		$product->set_price( $regular_price );

		if ( ! empty( $args['virtual'] ) ) {
			$product->set_virtual( true );
		}

		if ( ! empty( $args['manage_stock'] ) ) {
			$product->set_manage_stock( true );
		}

		if ( isset( $args['stock_quantity'] ) ) {
			$product->set_stock_quantity( (int) $args['stock_quantity'] );
		}

		if ( isset( $args['backorders'] ) ) {
			$product->set_backorders( $args['backorders'] ? 'yes' : 'no' );
		}

		$product_id = $product->save();

		return wc_get_product( $product_id );
	}

	/**
	 * Create and persist a variable product with one variation.
	 *
	 * @param array $product_args   Parent product overrides.
	 * @param array $variation_args Variation overrides.
	 *
	 * @return array{product: WC_Product_Variable, variation: WC_Product_Variation}
	 */
	protected function create_variable_product_with_variation( $product_args = array(), $variation_args = array() ) {
		$product = new WC_Product_Variable();

		$name = isset( $product_args['name'] ) ? $product_args['name'] : 'PPOM Variable Product ' . wp_generate_password( 6, false );

		$product->set_name( $name );
		$product->set_status( 'publish' );
		$product->set_catalog_visibility( 'visible' );
		$product->save();

		$variation = new WC_Product_Variation();

		$regular_price = isset( $variation_args['regular_price'] ) ? (string) $variation_args['regular_price'] : '12';

		$variation->set_parent_id( $product->get_id() );
		$variation->set_status( 'publish' );
		$variation->set_regular_price( $regular_price );
		$variation->set_price( $regular_price );
		$variation->set_attributes( isset( $variation_args['attributes'] ) ? $variation_args['attributes'] : array() );

		if ( ! empty( $variation_args['virtual'] ) ) {
			$variation->set_virtual( true );
		}

		if ( ! empty( $variation_args['manage_stock'] ) ) {
			$variation->set_manage_stock( true );
		}

		if ( isset( $variation_args['stock_quantity'] ) ) {
			$variation->set_stock_quantity( (int) $variation_args['stock_quantity'] );
		}

		if ( isset( $variation_args['backorders'] ) ) {
			$variation->set_backorders( $variation_args['backorders'] ? 'yes' : 'no' );
		}

		$variation->save();

		return array(
			'product'   => wc_get_product( $product->get_id() ),
			'variation' => wc_get_product( $variation->get_id() ),
		);
	}

	/**
	 * Create and persist an order containing the given product.
	 *
	 * @param WC_Product $product  Product to add.
	 * @param int        $quantity Item quantity.
	 *
	 * @return WC_Order
	 */
	protected function create_order_with_product( $product, $quantity = 1 ) {
		$order   = wc_create_order();
		$item_id = $order->add_product( $product, $quantity );

		$this->assertNotFalse( $item_id );

		$order->save();

		return wc_get_order( $order->get_id() );
	}

	/**
	 * Restore a cart item through the WooCommerce session filters.
	 *
	 * @param WC_Product $product         Product object.
	 * @param array      $ppom_fields     PPOM posted fields.
	 * @param int        $quantity        Product quantity.
	 * @param string     $hidden_fields   Conditionally hidden fields.
	 * @param int        $variation_id    Variation ID.
	 *
	 * @return array
	 */
	protected function restore_cart_item_from_session( $product, $ppom_fields = array(), $quantity = 1, $hidden_fields = '', $variation_id = 0 ) {
		$cart_item = array(
			'data'         => $product,
			'product_id'   => ppom_get_product_id( $product ),
			'variation_id' => $variation_id,
			'quantity'     => $quantity,
			'ppom'         => array(
				'fields'               => $ppom_fields,
				'conditionally_hidden' => $hidden_fields,
			),
		);

		return apply_filters( 'woocommerce_get_cart_item_from_session', $cart_item, $cart_item );
	}

	/**
	 * Initialize a real WooCommerce session, customer, and cart for integration-style checkout tests.
	 *
	 * @return void
	 */
	protected function initialize_woocommerce_checkout_context() {
		WC()->initialize_session();
		WC()->initialize_cart();
		WC()->payment_gateways();

		WC()->customer->set_billing_country( 'US' );
		WC()->customer->set_shipping_country( 'US' );

		if ( method_exists( WC()->cart, 'empty_cart' ) ) {
			WC()->cart->empty_cart( true );
		}
	}

	/**
	 * Add a product to the real WooCommerce cart with posted PPOM payload.
	 *
	 * @param int   $product_id      Product ID.
	 * @param array $ppom_payload    Posted PPOM payload.
	 * @param int   $quantity        Quantity.
	 * @param int   $variation_id    Variation ID.
	 * @param array $variation_attrs Variation attributes.
	 *
	 * @return string|false
	 */
	protected function add_product_to_real_cart( $product_id, $ppom_payload, $quantity = 1, $variation_id = 0, $variation_attrs = array() ) {
		$_POST['ppom_cart_key'] = '';
		$_POST['ppom']          = $ppom_payload;

		return WC()->cart->add_to_cart( $product_id, $quantity, $variation_id, $variation_attrs );
	}

	/**
	 * Simulate WooCommerce session reload for current cart contents.
	 *
	 * @return array
	 */
	protected function reload_real_cart_from_session() {
		$reloaded_contents = array();

		foreach ( WC()->cart->get_cart() as $cart_item_key => $values ) {
			$session_data                 = $values;
			$session_data['data']         = wc_get_product( $values['variation_id'] ? $values['variation_id'] : $values['product_id'] );
			$reloaded_contents[ $cart_item_key ] = apply_filters( 'woocommerce_get_cart_item_from_session', $session_data, $values, $cart_item_key );
		}

		WC()->cart->set_cart_contents( $reloaded_contents );

		return $reloaded_contents;
	}

	/**
	 * Create an order from the current WooCommerce cart.
	 *
	 * @param array $overrides Checkout data overrides.
	 *
	 * @return WC_Order
	 */
	protected function create_order_from_real_cart( $overrides = array() ) {
		$checkout_data = array_merge(
			array(
				'payment_method'   => 'cod',
				'billing_email'    => 'buyer@example.com',
				'billing_first_name' => 'Test',
				'billing_last_name'  => 'Buyer',
				'billing_country'    => 'US',
				'billing_address_1'  => '123 Test Street',
				'billing_city'       => 'Testville',
				'billing_state'      => 'CA',
				'billing_postcode'   => '90210',
				'billing_phone'      => '5551234567',
			),
			$overrides
		);

		$order_id = WC()->checkout()->create_order( $checkout_data );

		$this->assertNotWPError( $order_id );
		$this->assertGreaterThan( 0, $order_id );

		return wc_get_order( $order_id );
	}

	/**
	 * Return the first order item for an order.
	 *
	 * @param WC_Order $order Order instance.
	 *
	 * @return WC_Order_Item_Product
	 */
	protected function get_first_order_item( $order ) {
		$items = $order->get_items();

		$this->assertNotEmpty( $items );

		return reset( $items );
	}

	/**
	 * Create a PPOM-backed order item with stored line-item metadata.
	 *
	 * @param WC_Product $product Product to add.
	 * @param array      $fields  PPOM field values.
	 * @param int        $quantity Item quantity.
	 *
	 * @return WC_Order
	 */
	protected function create_order_with_ppom_item( $product, $fields, $quantity = 1 ) {
		$order = $this->create_order_with_product( $product, $quantity );
		$item  = $this->get_first_order_item( $order );

		ppom_woocommerce_order_item_meta(
			$item,
			'ppom-test-cart-key',
			array(
				'data' => $product,
				'ppom' => array(
					'fields' => $fields,
				),
			),
			$order
		);

		$item->save();
		$order->save();

		return wc_get_order( $order->get_id() );
	}

	/**
	 * Insert a PPOM field-group row and optionally attach it to a product.
	 *
	 * @param array $fields     PPOM field definitions.
	 * @param int   $product_id Optional product ID.
	 * @param array $overrides  Row overrides.
	 *
	 * @return int
	 */
	protected function insert_ppom_meta( $fields, $product_id = 0, $overrides = array() ) {
		$row = array_merge(
			array(
				'productmeta_name'       => $product_id ? get_the_title( $product_id ) : 'PPOM Test Meta ' . wp_generate_password( 6, false ),
				'productmeta_validation' => 'no',
				'dynamic_price_display'  => 'no',
				'send_file_attachment'   => '',
				'show_cart_thumb'        => 'no',
				'aviary_api_key'         => '',
				'productmeta_style'      => '',
				'productmeta_categories' => '',
				'the_meta'               => wp_json_encode( $fields ),
				'productmeta_created'    => current_time( 'mysql' ),
			),
			$overrides
		);

		$format  = array_fill( 0, count( $row ), '%s' );
		$meta_id = ppom_meta_repository()->insert_group( $row, $format );
		$this->assertGreaterThan( 0, $meta_id );

		$fields = apply_filters( 'ppom_meta_data_saving', $fields, $meta_id );
		ppom_admin_update_ppom_meta_only( $meta_id, $fields );

		if ( $product_id ) {
			update_post_meta( $product_id, PPOM_PRODUCT_META_KEY, $meta_id );
		}

		return $meta_id;
	}

	/**
	 * Build a basic text field definition.
	 *
	 * @param string $data_name Field data name.
	 * @param string $title     Field title.
	 * @param array  $overrides Field overrides.
	 *
	 * @return array
	 */
	protected function build_text_field( $data_name, $title = 'Text', $overrides = array() ) {
		return array_merge(
			array(
				'type'      => 'text',
				'title'     => $title,
				'data_name' => $data_name,
			),
			$overrides
		);
	}

	/**
	 * Build a basic select field definition.
	 *
	 * @param string $data_name Field data name.
	 * @param string $title     Field title.
	 * @param array  $options   Field options.
	 * @param array  $overrides Field overrides.
	 *
	 * @return array
	 */
	protected function build_select_field( $data_name, $title = 'Select', $options = array(), $overrides = array() ) {
		return array_merge(
			array(
				'type'      => 'select',
				'title'     => $title,
				'data_name' => $data_name,
				'options'   => $options,
			),
			$overrides
		);
	}

	/**
	 * Build a basic checkbox field definition.
	 *
	 * @param string $data_name Field data name.
	 * @param string $title     Field title.
	 * @param array  $options   Field options.
	 * @param array  $overrides Field overrides.
	 *
	 * @return array
	 */
	protected function build_checkbox_field( $data_name, $title = 'Checkbox', $options = array(), $overrides = array() ) {
		return array_merge(
			array(
				'type'      => 'checkbox',
				'title'     => $title,
				'data_name' => $data_name,
				'options'   => $options,
			),
			$overrides
		);
	}

	/**
	 * Build a basic file field definition.
	 *
	 * @param string $data_name Field data name.
	 * @param string $title     Field title.
	 * @param array  $overrides Field overrides.
	 *
	 * @return array
	 */
	protected function build_file_field( $data_name, $title = 'File', $overrides = array() ) {
		return array_merge(
			array(
				'type'       => 'file',
				'title'      => $title,
				'data_name'  => $data_name,
				'file_types' => 'txt,jpg,png,pdf',
				'file_size'  => '1mb',
			),
			$overrides
		);
	}

	/**
	 * Build a basic cropper field definition.
	 *
	 * @param string $data_name Field data name.
	 * @param string $title     Field title.
	 * @param array  $options   Field options.
	 * @param array  $overrides Field overrides.
	 *
	 * @return array
	 */
	protected function build_cropper_field( $data_name, $title = 'Cropper', $options = array(), $overrides = array() ) {
		return array_merge(
			array(
				'type'      => 'cropper',
				'title'     => $title,
				'data_name' => $data_name,
				'options'   => $options,
			),
			$overrides
		);
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
	protected function build_price_matrix_field( $data_name, $options, $overrides = array() ) {
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

	/**
	 * Build a quantities field definition.
	 *
	 * @param string $data_name Field data name.
	 * @param string $title     Field title.
	 * @param array  $options   Field options.
	 * @param array  $overrides Field overrides.
	 *
	 * @return array
	 */
	protected function build_quantities_field( $data_name, $title = 'Quantities', $options = array(), $overrides = array() ) {
		return array_merge(
			array(
				'type'      => 'quantities',
				'title'     => $title,
				'data_name' => $data_name,
				'options'   => $options,
			),
			$overrides
		);
	}

	/**
	 * Count PPOM field-group rows.
	 *
	 * @return int
	 */
	protected function ppom_meta_row_count() {

		return ppom_meta_repository()->count_rows();
	}

	/**
	 * Read a single PPOM row.
	 *
	 * @param int $meta_id PPOM row ID.
	 *
	 * @return array|null
	 */
	protected function get_ppom_meta_row( $meta_id ) {
		$row = ppom_meta_repository()->get_row_by_id( (int) $meta_id );

		return $row ? get_object_vars( $row ) : null;
	}

	/**
	 * Return the field definitions attached to a product.
	 *
	 * @param int $product_id Product ID.
	 *
	 * @return array
	 */
	protected function get_ppom_fields_for_product( $product_id ) {
		$ppom = new PPOM_Meta( $product_id );

		return is_array( $ppom->fields ) ? $ppom->fields : array();
	}

	/**
	 * Delete PPOM upload artifacts for a file name.
	 *
	 * @param string $file_name File name.
	 *
	 * @return void
	 */
	protected function remove_ppom_upload_artifacts( $file_name ) {
		$paths = array(
			ppom_get_dir_path() . $file_name,
			ppom_get_dir_path( 'thumbs' ) . $file_name,
			ppom_get_dir_path( 'cropped' ) . $file_name,
			ppom_get_dir_path( 'edits' ) . $file_name,
			ppom_get_dir_path( 'edits/thumbs' ) . $file_name,
		);

		foreach ( $paths as $path ) {
			if ( $path && file_exists( $path ) ) {
				unlink( $path );
			}
		}
	}

	/**
	 * Persist a PPOM option for both legacy and migrated storage.
	 *
	 * @param string $key   Option key.
	 * @param mixed  $value Option value.
	 *
	 * @return void
	 */
	protected function set_ppom_option( $key, $value ) {
		update_option( $key, $value );

		$saved_settings = get_option( 'ppom-settings_panel', array() );
		if ( ! is_array( $saved_settings ) ) {
			$saved_settings = array();
		}

		$saved_settings[ $key ] = $value;
		update_option( 'ppom-settings_panel', $saved_settings );
	}

	/**
	 * Remove a PPOM option from both legacy and migrated storage.
	 *
	 * @param string $key Option key.
	 *
	 * @return void
	 */
	protected function unset_ppom_option( $key ) {
		delete_option( $key );

		$saved_settings = get_option( 'ppom-settings_panel', array() );
		if ( ! is_array( $saved_settings ) ) {
			return;
		}

		if ( array_key_exists( $key, $saved_settings ) ) {
			unset( $saved_settings[ $key ] );
			update_option( 'ppom-settings_panel', $saved_settings );
		}
	}

	/**
	 * Create a test user with shop_manager role.
	 *
	 * @return int User ID.
	 */
	protected function create_shop_manager_user() {
		if ( null === $this->ppom_test_read_user ) {
			$this->ppom_test_read_user = $this->factory->user->create(
				array(
					'role' => 'shop_manager',
				)
			);
		}

		return $this->ppom_test_read_user;
	}

	/**
	 * Register the PPOM REST routes for the current test.
	 *
	 * @param string $secret_key REST secret key.
	 *
	 * @return PPOM_Rest
	 */
	protected function register_ppom_rest_routes( $secret_key = 'secret-key' ) {
		$this->set_ppom_option( 'ppom_api_enable', 'yes' );
		$this->set_ppom_option( 'ppom_rest_secret_key', $secret_key );

		$rest        = new PPOM_Rest();
		$rest_server = rest_get_server();

		do_action( 'rest_api_init', $rest_server );

		return $rest;
	}

	/**
	 * Dispatch a PPOM REST request through the registered route table.
	 *
	 * @param string $method          HTTP method.
	 * @param string $route           Route path.
	 * @param array  $params          Request params.
	 * @param string $secret_key      Expected secret key.
	 * @param bool   $authenticate    Whether to authenticate as appropriate user (default true).
	 *
	 * @return WP_REST_Response
	 */
	protected function dispatch_ppom_rest_request( $method, $route, $params = array(), $secret_key = 'secret-key', $authenticate = true ) {
		$this->register_ppom_rest_routes( $secret_key );

		if ( $authenticate ) {
			wp_set_current_user( $this->create_shop_manager_user() );
		}

		$response = $this->dispatch_rest_request_to_route( $method, $route, $params );
		$data     = $response->get_data();

		if ( isset( $data['code'] ) && 'rest_no_route' === $data['code'] ) {
			$alternate_route = untrailingslashit( $route );

			if ( $alternate_route === $route ) {
				$alternate_route = trailingslashit( $route );
			}

			$response = $this->dispatch_rest_request_to_route( $method, $alternate_route, $params );
		}

		return $response;
	}

	/**
	 * Dispatch a REST request to a specific route string.
	 *
	 * @param string $method HTTP method.
	 * @param string $route  Route path.
	 * @param array  $params Request params.
	 *
	 * @return WP_REST_Response
	 */
	protected function dispatch_rest_request_to_route( $method, $route, $params = array() ) {
		$request = new WP_REST_Request( $method, $route );
		foreach ( $params as $key => $value ) {
			$request->set_param( $key, $value );
		}

		return rest_get_server()->dispatch( $request );
	}
}
