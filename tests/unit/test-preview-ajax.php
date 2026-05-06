<?php
/**
 * AJAX handler tests for the field-group preview modal.
 *
 * @package ppom-pro
 */

class Test_Preview_Ajax extends WP_Ajax_UnitTestCase {

	/**
	 * Admin user ID for capability-passing requests.
	 *
	 * @var int
	 */
	protected $admin_user_id;

	public function setUp(): void {
		parent::setUp();

		$this->admin_user_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $this->admin_user_id );

		$_POST    = array();
		$_GET     = array();
		$_REQUEST = array();
	}

	public function tearDown(): void {
		$_POST    = array();
		$_GET     = array();
		$_REQUEST = array();
		wp_set_current_user( 0 );
		parent::tearDown();
	}

	/**
	 * Set a request param so handlers reading $_REQUEST see it regardless of POST/GET.
	 *
	 * @param string $key   Param name.
	 * @param mixed  $value Value.
	 *
	 * @return void
	 */
	protected function set_request_param( $key, $value ) {
		$_POST[ $key ]    = $value;
		$_REQUEST[ $key ] = $value;
	}

	/**
	 * Build a published WooCommerce product.
	 *
	 * @param string $name Optional product name.
	 *
	 * @return int
	 */
	protected function create_published_product( $name = '' ) {
		$product = new WC_Product_Simple();
		$product->set_name( '' === $name ? 'Preview Product ' . wp_generate_password( 6, false ) : $name );
		$product->set_status( 'publish' );
		$product->set_catalog_visibility( 'visible' );
		$product->set_regular_price( '10' );

		return (int) $product->save();
	}

	/**
	 * Insert a minimal PPOM field-group row.
	 *
	 * @return int
	 */
	protected function insert_ppom_group() {
		$row = array(
			'productmeta_name'       => 'Preview Group ' . wp_generate_password( 6, false ),
			'productmeta_validation' => 'no',
			'dynamic_price_display'  => 'no',
			'send_file_attachment'   => '',
			'show_cart_thumb'        => 'no',
			'aviary_api_key'         => '',
			'productmeta_style'      => '',
			'productmeta_categories' => '',
			'the_meta'               => wp_json_encode( array() ),
			'productmeta_created'    => current_time( 'mysql' ),
		);

		$format  = array_fill( 0, count( $row ), '%s' );
		$meta_id = ppom_meta_repository()->insert_group( $row, $format );
		$this->assertGreaterThan( 0, $meta_id );

		return (int) $meta_id;
	}

	/**
	 * Dispatch an admin-ajax action and decode the JSON it emits.
	 *
	 * @param string $action AJAX action name.
	 *
	 * @return array
	 */
	protected function dispatch_and_decode( $action ) {
		try {
			$this->_handleAjax( $action );
		} catch ( WPAjaxDieContinueException $e ) {
			// Expected: wp_send_json terminates via wp_die.
		} catch ( WPAjaxDieStopException $e ) {
			// Also acceptable.
		}

		$decoded = json_decode( $this->_last_response, true );
		$this->assertIsArray( $decoded, 'AJAX response was not valid JSON: ' . $this->_last_response );

		return $decoded;
	}

	public function test_get_preview_url_rejects_missing_nonce() {
		$this->set_request_param( 'action', 'ppom_get_preview_url' );
		$this->set_request_param( 'ppom_id', 42 );

		$response = $this->dispatch_and_decode( 'ppom_get_preview_url' );

		$this->assertSame( 'error', $response['status'] );
		$this->assertArrayNotHasKey( 'preview_url', $response );
	}

	public function test_get_preview_url_rejects_invalid_nonce() {
		$this->set_request_param( 'action', 'ppom_get_preview_url' );
		$this->set_request_param( 'ppom_id', 42 );
		$this->set_request_param( 'nonce', 'not-a-real-nonce' );

		$response = $this->dispatch_and_decode( 'ppom_get_preview_url' );

		$this->assertSame( 'error', $response['status'] );
		$this->assertArrayNotHasKey( 'preview_url', $response );
	}

	public function test_get_preview_url_returns_no_product_when_unattached() {
		$ppom_id = $this->insert_ppom_group();

		$this->set_request_param( 'action', 'ppom_get_preview_url' );
		$this->set_request_param( 'ppom_id', $ppom_id );
		$this->set_request_param( 'nonce', wp_create_nonce( 'ppom_preview_nonce_action' ) );

		$response = $this->dispatch_and_decode( 'ppom_get_preview_url' );

		$this->assertSame( 'error', $response['status'] );
		$this->assertSame( 'ppom_preview_no_product', $response['code'] );
	}

	public function test_get_preview_url_succeeds_for_attached_product() {
		$ppom_id    = $this->insert_ppom_group();
		$product_id = $this->create_published_product();
		update_post_meta( $product_id, PPOM_PRODUCT_META_KEY, array( $ppom_id ) );

		$this->set_request_param( 'action', 'ppom_get_preview_url' );
		$this->set_request_param( 'ppom_id', $ppom_id );
		$this->set_request_param( 'nonce', wp_create_nonce( 'ppom_preview_nonce_action' ) );

		$response = $this->dispatch_and_decode( 'ppom_get_preview_url' );

		$this->assertSame( 'success', $response['status'] );
		$this->assertNotEmpty( $response['preview_url'] );
		$this->assertIsArray( $response['product'] );
		$this->assertSame( $product_id, (int) $response['product']['id'] );
	}

	public function test_search_products_rejects_missing_nonce() {
		$this->set_request_param( 'action', 'ppom_search_products' );
		$this->set_request_param( 'term', 'shirt' );

		$response = $this->dispatch_and_decode( 'ppom_search_products' );

		$this->assertSame( 'error', $response['status'] );
		$this->assertSame( array(), $response['results'] );
	}

	public function test_search_products_returns_empty_for_blank_term() {
		$this->set_request_param( 'action', 'ppom_search_products' );
		$this->set_request_param( 'nonce', wp_create_nonce( 'ppom_preview_nonce_action' ) );
		$this->set_request_param( 'term', '' );

		$response = $this->dispatch_and_decode( 'ppom_search_products' );

		$this->assertSame( 'success', $response['status'] );
		$this->assertSame( array(), $response['results'] );
	}

	public function test_search_products_returns_matching_product() {
		$product_id = $this->create_published_product( 'Unique Test Garment Alpha' );

		$this->set_request_param( 'action', 'ppom_search_products' );
		$this->set_request_param( 'nonce', wp_create_nonce( 'ppom_preview_nonce_action' ) );
		$this->set_request_param( 'term', 'Garment Alpha' );

		$response = $this->dispatch_and_decode( 'ppom_search_products' );

		$this->assertSame( 'success', $response['status'] );
		$this->assertNotEmpty( $response['results'] );

		$ids = array_map( 'intval', array_column( $response['results'], 'id' ) );
		$this->assertContains( $product_id, $ids );
	}
}
