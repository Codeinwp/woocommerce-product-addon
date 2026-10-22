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

	/**
	 * When ppom_option_price is missing but the field selections carry a price,
	 * validate_product() must compute prices server-side and allow the cart.
	 *
	 * @return void
	 */
	public function test_validate_product_legacy_mode_computes_price_server_side_when_js_price_missing() {
		$product    = $this->create_simple_product();
		$product_id = $product->get_id();

		$meta_id = $this->insert_ppom_meta(
			array(
				$this->build_select_field(
					'size',
					'Size',
					array(
						array( 'option' => 'Small', 'price' => '' ),
						array( 'option' => 'Large', 'price' => '5.00' ),
					)
				),
			),
			$product_id
		);

		$this->set_ppom_option( 'ppom_legacy_price', 'yes' );

		$_POST['ppom'] = array(
			'fields' => array(
				'id'   => (string) $meta_id,
				'size' => 'Large',
			),
		);

		$result = ProductHandler::validate_product( true, $product_id, 1 );

		$this->assertTrue( $result );
		$this->assertNotEmpty( $_POST['ppom']['ppom_option_price'] );

		$decoded = json_decode( $_POST['ppom']['ppom_option_price'], true );
		$this->assertIsArray( $decoded );
		$this->assertCount( 1, $decoded );
		$this->assertSame( 'variable', $decoded[0]['apply'] );
		$this->assertEquals( '5.00', $decoded[0]['price'] );
		$this->assertSame( 'size', $decoded[0]['data_name'] );
	}

	/**
	 * The server-built payload takes a percentage option of the selected variation's
	 * price, not of the parent's, which is the cheapest variation.
	 */
	public function test_validate_product_legacy_mode_prices_percent_option_from_selected_variation(): void {
		$created = $this->create_variable_product_with_variation( array(), array( 'regular_price' => '200' ) );
		$parent  = $created['product'];
		$cheaper = new WC_Product_Variation();
		$cheaper->set_parent_id( $parent->get_id() );
		$cheaper->set_status( 'publish' );
		$cheaper->set_regular_price( '100' );
		$cheaper->save();
		WC_Product_Variable::sync( $parent->get_id() );

		$meta_id = $this->insert_ppom_meta(
			array( $this->build_select_field( 'upgrade', 'Upgrade', array( array( 'option' => 'Yes', 'price' => '10%' ) ) ) ),
			$parent->get_id()
		);

		$this->set_ppom_option( 'ppom_legacy_price', 'yes' );

		$_POST['ppom'] = array(
			'fields' => array(
				'id'      => (string) $meta_id,
				'upgrade' => 'Yes',
			),
		);

		$this->assertTrue( ProductHandler::validate_product( true, $parent->get_id(), 1, $created['variation']->get_id() ) );

		$decoded = json_decode( $_POST['ppom']['ppom_option_price'], true );
		$this->assertEqualsWithDelta( 20.0, (float) $decoded[0]['price'], 0.001, '10% of the 200 variation, not of the 100 one.' );
	}

	/**
	 * When ppom_option_price is already populated, validate_product() must
	 * leave it untouched and pass validation normally.
	 *
	 * @return void
	 */
	public function test_validate_product_legacy_mode_leaves_existing_option_price_intact() {
		$product    = $this->create_simple_product();
		$product_id = $product->get_id();

		$meta_id = $this->insert_ppom_meta(
			array(
				$this->build_select_field(
					'size',
					'Size',
					array(
						array( 'option' => 'Large', 'price' => '5.00' ),
					)
				),
			),
			$product_id
		);

		$this->set_ppom_option( 'ppom_legacy_price', 'yes' );

		$existing_price = wp_json_encode( array( array( 'apply' => 'variable', 'price' => '5.00', 'data_name' => 'size' ) ) );

		$_POST['ppom'] = array(
			'fields'           => array(
				'id'   => (string) $meta_id,
				'size' => 'Large',
			),
			'ppom_option_price' => $existing_price,
		);

		$result = ProductHandler::validate_product( true, $product_id, 1 );

		$this->assertTrue( $result );
		$this->assertSame( $existing_price, $_POST['ppom']['ppom_option_price'] );
	}
	/**
	 * Builds a product with one file field and posts a single row for it.
	 *
	 * @param bool   $required Whether the field is required.
	 * @param string $org      Posted file reference.
	 *
	 * @return int Product ID.
	 */
	private function post_file_row( bool $required, string $org ): int {
		$product_id = $this->create_simple_product()->get_id();
		$meta_id    = $this->insert_ppom_meta(
			array( $this->build_file_field( 'design_file', 'Design', array( 'required' => $required ? 'on' : '' ) ) ),
			$product_id
		);

		$_POST['ppom'] = array(
			'fields' => array(
				'id'          => (string) $meta_id,
				'design_file' => array( 0 => array( 'org' => $org ) ),
			),
		);

		return $product_id;
	}

	/**
	 * Required-upload validation uses the rows that survive the ownership
	 * check: a required field whose only row is not the visitor's own blocks
	 * add-to-cart, the visitor's own upload passes, and an optional field is
	 * never a reason to reject.
	 *
	 * @return void
	 */
	public function test_required_upload_validation_uses_owned_rows() {
		WC()->session = new WC_Session_Handler();
		WC()->session->init();
		WC()->session->set( 'ppom_uploaded_files', array( 'mine.aaa111.png' ) );

		$product_id = $this->post_file_row( true, 'someone-else.bbb222.png' );
		$this->assertFalse( ProductHandler::validate_product( true, $product_id, 1 ) );
		$this->assertStringContainsString( 'Design is a required field', wp_strip_all_tags( wc_print_notices( true ) ) );

		$product_id = $this->post_file_row( true, 'mine.aaa111.png' );
		$this->assertTrue( ProductHandler::validate_product( true, $product_id, 1 ), 'The visitor\'s own upload passes.' );

		$product_id = $this->post_file_row( false, 'someone-else.bbb222.png' );
		$this->assertTrue( ProductHandler::validate_product( true, $product_id, 1 ), 'An optional field is not rejected.' );
	}

	/**
	 * @return void
	 */
	public function test_woopayments_express_buttons_unsupported_for_ppom_products() {
		$ppom_product = $this->create_simple_product();
		$this->insert_ppom_meta( array( $this->build_text_field( 'engraving', 'Engraving' ) ), $ppom_product->get_id() );
		$plain_product = $this->create_simple_product();

		foreach ( array( 'wcpay_payment_request_is_product_supported', 'wcpay_woopay_button_is_product_supported' ) as $hook ) {
			$this->assertFalse( apply_filters( $hook, true, $ppom_product ), $hook . ': PPOM product' );
			$this->assertTrue( apply_filters( $hook, true, $plain_product ), $hook . ': product without PPOM' );
			$this->assertFalse( apply_filters( $hook, false, $plain_product ), $hook . ': gateway verdict kept' );
			$this->assertFalse( apply_filters( $hook, false, null ), $hook . ': no product' );
		}
	}

	/**
	 * @return void
	 */
	public function test_stripe_express_buttons_hidden_on_ppom_product_page() {
		$ppom_product = $this->create_simple_product();
		$this->insert_ppom_meta( array( $this->build_text_field( 'engraving', 'Engraving' ) ), $ppom_product->get_id() );
		$plain_product = $this->create_simple_product();
		$ppom_post     = get_post( $ppom_product->get_id() );

		$this->go_to( get_permalink( $ppom_product->get_id() ) );
		$this->assertTrue( apply_filters( 'wc_stripe_hide_payment_request_on_product_page', false, $ppom_post ) );

		$this->go_to( get_permalink( $plain_product->get_id() ) );
		$this->assertFalse( apply_filters( 'wc_stripe_hide_payment_request_on_product_page', false, get_post( $plain_product->get_id() ) ) );

		// Stripe's one-off location migration runs this filter outside product pages.
		$this->go_to( home_url( '/' ) );
		$this->assertFalse( apply_filters( 'wc_stripe_hide_payment_request_on_product_page', false, $ppom_post ) );
	}

	/**
	 * @return void
	 */
	public function test_store_api_add_item_rejects_missing_required_ppom_field() {
		$product = $this->create_simple_product();
		$this->insert_ppom_meta( array( $this->build_text_field( 'engraving', 'Engraving', array( 'required' => 'on' ) ) ), $product->get_id() );

		$response = $this->store_api_add_item( $product->get_id() );

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'ppom_required_field', $response->get_data()['code'] );
		$this->assertSame( 'Engraving is a required field', $response->get_data()['message'] );
	}

	/**
	 * @return void
	 */
	public function test_store_api_add_item_allows_products_without_unconditional_required_fields() {
		$optional = $this->create_simple_product();
		$this->insert_ppom_meta( array( $this->build_text_field( 'note', 'Note' ) ), $optional->get_id() );

		$conditional = $this->create_simple_product();
		$this->insert_ppom_meta(
			array(
				$this->build_text_field(
					'engraving',
					'Engraving',
					array(
						'required' => 'on',
						'logic'    => 'on',
					)
				),
			),
			$conditional->get_id()
		);

		$plain = $this->create_simple_product();

		foreach ( array( $optional, $conditional, $plain ) as $product ) {
			$this->assertSame( 201, $this->store_api_add_item( $product->get_id() )->get_status(), $product->get_name() );
		}
	}

	/**
	 * Adds a product through the Store API the way express checkout buttons do.
	 *
	 * @param int $product_id Product to add.
	 *
	 * @return WP_REST_Response
	 */
	private function store_api_add_item( int $product_id ): WP_REST_Response {
		add_filter( 'woocommerce_store_api_disable_nonce_check', '__return_true' );

		$request = new WP_REST_Request( 'POST', '/wc/store/v1/cart/add-item' );
		$request->set_param( 'id', $product_id );
		$request->set_param( 'quantity', 1 );
		$response = rest_do_request( $request );

		remove_filter( 'woocommerce_store_api_disable_nonce_check', '__return_true' );
		WC()->cart->empty_cart();

		return $response;
	}
}
