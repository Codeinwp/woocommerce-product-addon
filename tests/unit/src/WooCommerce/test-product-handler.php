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
	 * A required upload whose only row is not the visitor's own must block
	 * add-to-cart instead of being silently dropped later.
	 *
	 * @return void
	 */
	public function test_required_upload_rejected_when_row_is_not_owned() {
		WC()->session = new WC_Session_Handler();
		WC()->session->init();

		$product_id = $this->post_file_row( true, 'someone-else.bbb222.png' );

		$this->assertFalse( ProductHandler::validate_product( true, $product_id, 1 ) );
		$this->assertStringContainsString(
			'Design is a required field',
			wp_strip_all_tags( wc_print_notices( true ) )
		);
	}

	/**
	 * The visitor's own upload with an allowed extension still passes.
	 *
	 * @return void
	 */
	public function test_required_upload_passes_with_owned_row() {
		WC()->session = new WC_Session_Handler();
		WC()->session->init();
		WC()->session->set( 'ppom_uploaded_files', array( 'mine.aaa111.png' ) );

		$product_id = $this->post_file_row( true, 'mine.aaa111.png' );

		$this->assertTrue( ProductHandler::validate_product( true, $product_id, 1 ) );
	}

	/**
	 * An optional upload field is not a reason to reject; its unusable row is
	 * just dropped when the item is stored.
	 *
	 * @return void
	 */
	public function test_optional_upload_not_rejected_when_row_is_dropped() {
		WC()->session = new WC_Session_Handler();
		WC()->session->init();

		$product_id = $this->post_file_row( false, 'someone-else.bbb222.png' );

		$this->assertTrue( ProductHandler::validate_product( true, $product_id, 1 ) );
	}
}
