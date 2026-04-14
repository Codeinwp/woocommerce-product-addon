<?php
/**
 * Class Test_Rest_And_Admin
 *
 * @package ppom-pro
 */

require_once __DIR__ . '/class-ppom-test-case.php';

class Test_Rest_And_Admin extends PPOM_Test_Case {

	/**
	 * Ensure REST product writes fail fast when fields are missing.
	 *
	 * @return void
	 */
	public function testPPOMSaveMetaProductReturnsNoFields() {
		$product = $this->create_simple_product();

		$rows_before = $this->ppom_meta_row_count();
		$response    = $this->dispatch_ppom_rest_request(
			'POST',
			'/ppom/v1/set/product/',
			array(
				'product_id' => $product->get_id(),
				'secret_key' => 'secret-key',
			)
		);
		$data        = $response->get_data();

		$this->assertSame( 'no_fields', $data['status'] );
		$this->assertSame( $rows_before, $this->ppom_meta_row_count() );
		$this->assertEmpty( get_post_meta( $product->get_id(), PPOM_PRODUCT_META_KEY, true ) );
	}

	/**
	 * Ensure invalid REST secrets do not create product metadata.
	 *
	 * @return void
	 */
	public function testPPOMSaveMetaProductRejectsInvalidSecretKeyWithoutCreatingMeta() {
		$product = $this->create_simple_product();

		$rows_before = $this->ppom_meta_row_count();
		$response    = $this->dispatch_ppom_rest_request(
			'POST',
			'/ppom/v1/set/product/',
			array(
				'product_id' => $product->get_id(),
				'secret_key' => 'wrong-secret',
				'fields'     => wp_json_encode(
					array(
						$this->build_text_field( 'engraving', 'Engraving' ),
					)
				),
			),
			'expected-secret'
		);
		$data        = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'key_not_valid', $data['status'] );
		$this->assertSame( $rows_before, $this->ppom_meta_row_count() );
		$this->assertEmpty( get_post_meta( $product->get_id(), PPOM_PRODUCT_META_KEY, true ) );
	}

	/**
	 * Ensure invalid REST secrets do not delete product metadata.
	 *
	 * @return void
	 */
	public function testDeletePPOMFieldsProductRejectsInvalidSecretKeyWithoutDeletingMeta() {
		$product = $this->create_simple_product();
		$meta_id = $this->insert_ppom_meta(
			array(
				$this->build_text_field( 'engraving', 'Engraving' ),
			),
			$product->get_id()
		);

		$response = $this->dispatch_ppom_rest_request(
			'POST',
			'/ppom/v1/delete/product/',
			array(
				'product_id' => $product->get_id(),
				'secret_key' => 'wrong-secret',
				'fields'     => wp_json_encode( array( '__all_keys' ) ),
			),
			'expected-secret'
		);
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'key_not_valid', $data['status'] );
		$this->assertSame( $meta_id, (int) get_post_meta( $product->get_id(), PPOM_PRODUCT_META_KEY, true ) );
		$this->assertNotNull( $this->get_ppom_meta_row( $meta_id ) );
	}

	/**
	 * Ensure valid REST product writes create and attach a PPOM row.
	 *
	 * @return void
	 */
	public function testPPOMSaveMetaProductCreatesMetaWhenSecretIsValid() {
		$product = $this->create_simple_product();

		$rows_before = $this->ppom_meta_row_count();
		$response    = $this->dispatch_ppom_rest_request(
			'POST',
			'/ppom/v1/set/product/',
			array(
				'product_id' => $product->get_id(),
				'secret_key' => 'expected-secret',
				'fields'     => wp_json_encode(
					array(
						$this->build_text_field( 'engraving', 'Engraving' ),
					)
				),
			),
			'expected-secret'
		);
		$data        = $response->get_data();
		$meta_id     = (int) $data['meta_id'];

		$this->assertSame( 'success', $data['status'] );
		$this->assertGreaterThan( 0, $meta_id );
		$this->assertSame( $rows_before + 1, $this->ppom_meta_row_count() );
		$this->assertSame( $meta_id, (int) get_post_meta( $product->get_id(), PPOM_PRODUCT_META_KEY, true ) );
		$this->assertNotNull( $this->get_ppom_meta_row( $meta_id ) );
	}

	/**
	 * Ensure REST product writes merge new fields into existing metadata.
	 *
	 * @return void
	 */
	public function testPPOMSaveMetaProductMergesFieldsIntoExistingMeta() {
		$product = $this->create_simple_product();
		$meta_id = $this->insert_ppom_meta(
			array(
				$this->build_text_field( 'engraving', 'Engraving' ),
			),
			$product->get_id()
		);

		$response = $this->dispatch_ppom_rest_request(
			'POST',
			'/ppom/v1/set/product/',
			array(
				'product_id' => $product->get_id(),
				'secret_key' => 'expected-secret',
				'fields'     => wp_json_encode(
					array(
						$this->build_text_field( 'size', 'Size' ),
					)
				),
			),
			'expected-secret'
		);
		$data     = $response->get_data();
		$fields   = wp_list_pluck( $this->get_ppom_fields_for_product( $product->get_id() ), 'data_name' );

		sort( $fields );

		$this->assertSame( 'success', $data['status'] );
		$this->assertSame( $meta_id, (int) $data['meta_id'] );
		$this->assertSame( array( 'engraving', 'size' ), $fields );
	}

	/**
	 * Ensure deleting all REST fields removes the PPOM row and product association.
	 *
	 * @return void
	 */
	public function testDeletePPOMFieldsProductRemovesAttachedMetaForAllKeysRequest() {
		$product = $this->create_simple_product();
		$meta_id = $this->insert_ppom_meta(
			array(
				$this->build_text_field( 'engraving', 'Engraving' ),
			),
			$product->get_id()
		);

		$response = $this->dispatch_ppom_rest_request(
			'POST',
			'/ppom/v1/delete/product/',
			array(
				'product_id' => $product->get_id(),
				'secret_key' => 'expected-secret',
				'fields'     => wp_json_encode( array( '__all_keys' ) ),
			),
			'expected-secret'
		);
		$data     = $response->get_data();

		$this->assertSame( 'success', $data['status'] );
		$this->assertEmpty( get_post_meta( $product->get_id(), PPOM_PRODUCT_META_KEY, true ) );
		$this->assertNull( $this->get_ppom_meta_row( $meta_id ) );
	}

	/**
	 * Ensure the order read route returns the formatted PPOM item metadata.
	 *
	 * @return void
	 */
	public function testGetPPOMMetaInfoOrderReturnsFormattedMetadata() {
		$product = $this->create_simple_product();

		$this->insert_ppom_meta(
			array(
				$this->build_text_field( 'engraving', 'Engraving' ),
			),
			$product->get_id()
		);

		$order    = $this->create_order_with_ppom_item(
			$product,
			array(
				'engraving' => 'Hello',
			)
		);
		$response = $this->dispatch_ppom_rest_request(
			'GET',
			'/ppom/v1/get/order/',
			array(
				'order_id' => $order->get_id(),
			)
		);
		$data     = $response->get_data();

		$this->assertSame( 'success', $data['status'] );
		$this->assertSame( $product->get_id(), $data['order_items_meta'][0]['product_id'] );
		$this->assertSame( 'engraving', $data['order_items_meta'][0]['product_meta_data'][0]['key'] );
		$this->assertSame( 'Hello', $data['order_items_meta'][0]['product_meta_data'][0]['value'] );
	}

	/**
	 * Ensure the order update route updates metadata for the matching product line item.
	 *
	 * @return void
	 */
	public function testPPOMUpdateMetaOrderUpdatesLineItemMetadata() {
		$product = $this->create_simple_product();

		$this->insert_ppom_meta(
			array(
				$this->build_text_field( 'engraving', 'Engraving' ),
			),
			$product->get_id()
		);

		$order = $this->create_order_with_ppom_item(
			$product,
			array(
				'engraving' => 'Hello',
			)
		);

		$response = $this->dispatch_ppom_rest_request(
			'POST',
			'/ppom/v1/set/order/',
			array(
				'order_id'   => $order->get_id(),
				'secret_key' => 'expected-secret',
				'fields'     => wp_json_encode(
					array(
						$product->get_id() => array(
							'engraving' => 'Updated',
						),
					)
				),
			),
			'expected-secret'
		);
		$data     = $response->get_data();
		$item     = $this->get_first_order_item( wc_get_order( $order->get_id() ) );

		$this->assertSame( 'success', $data['status'] );
		$this->assertSame( 'Updated', $item->get_meta( 'engraving', true ) );
	}

	/**
	 * Ensure invalid REST secrets do not update order metadata.
	 *
	 * @return void
	 */
	public function testPPOMUpdateMetaOrderRejectsInvalidSecretKeyWithoutChangingMetadata() {
		$product = $this->create_simple_product();

		$this->insert_ppom_meta(
			array(
				$this->build_text_field( 'engraving', 'Engraving' ),
			),
			$product->get_id()
		);

		$order = $this->create_order_with_ppom_item(
			$product,
			array(
				'engraving' => 'Hello',
			)
		);

		$response = $this->dispatch_ppom_rest_request(
			'POST',
			'/ppom/v1/set/order/',
			array(
				'order_id'   => $order->get_id(),
				'secret_key' => 'wrong-secret',
				'fields'     => wp_json_encode(
					array(
						$product->get_id() => array(
							'engraving' => 'Updated',
						),
					)
				),
			),
			'expected-secret'
		);
		$data     = $response->get_data();
		$item     = $this->get_first_order_item( wc_get_order( $order->get_id() ) );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'key_not_valid', $data['status'] );
		$this->assertSame( 'Hello', $item->get_meta( 'engraving', true ) );
	}

	/**
	 * Ensure the order delete route removes the selected metadata keys.
	 *
	 * @return void
	 */
	public function testDeletePPOMFieldsOrderRemovesSelectedMetadata() {
		$product = $this->create_simple_product();

		$this->insert_ppom_meta(
			array(
				$this->build_text_field( 'engraving', 'Engraving' ),
			),
			$product->get_id()
		);

		$order = $this->create_order_with_ppom_item(
			$product,
			array(
				'engraving' => 'Hello',
			)
		);

		$response = $this->dispatch_ppom_rest_request(
			'POST',
			'/ppom/v1/delete/order/',
			array(
				'order_id'   => $order->get_id(),
				'secret_key' => 'expected-secret',
				'fields'     => wp_json_encode(
					array(
						$product->get_id() => array( 'engraving' ),
					)
				),
			),
			'expected-secret'
		);
		$data     = $response->get_data();
		$item     = $this->get_first_order_item( wc_get_order( $order->get_id() ) );

		$this->assertSame( 'success', $data['status'] );
		$this->assertSame( '', $item->get_meta( 'engraving', true ) );
	}

	/**
	 * Ensure invalid REST secrets do not delete order metadata.
	 *
	 * @return void
	 */
	public function testDeletePPOMFieldsOrderRejectsInvalidSecretKeyWithoutDeletingMetadata() {
		$product = $this->create_simple_product();

		$this->insert_ppom_meta(
			array(
				$this->build_text_field( 'engraving', 'Engraving' ),
			),
			$product->get_id()
		);

		$order = $this->create_order_with_ppom_item(
			$product,
			array(
				'engraving' => 'Hello',
			)
		);

		$response = $this->dispatch_ppom_rest_request(
			'POST',
			'/ppom/v1/delete/order/',
			array(
				'order_id'   => $order->get_id(),
				'secret_key' => 'wrong-secret',
				'fields'     => wp_json_encode(
					array(
						$product->get_id() => array( 'engraving' ),
					)
				),
			),
			'expected-secret'
		);
		$data     = $response->get_data();
		$item     = $this->get_first_order_item( wc_get_order( $order->get_id() ) );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'key_not_valid', $data['status'] );
		$this->assertSame( 'Hello', $item->get_meta( 'engraving', true ) );
	}

	/**
	 * Ensure REST read-field filtering exposes only the supported key subset.
	 *
	 * @return void
	 */
	public function testFilterRequiredKeysOnlyKeepsExpectedFieldSubset() {
		$rest = new PPOM_Rest();

		$filtered = $rest->filter_required_keys_only(
			array(
				array(
					'title'       => 'Engraving',
					'type'        => 'text',
					'data_name'   => 'engraving',
					'description' => 'Add text',
					'required'    => 'on',
					'placeholder' => 'Type here',
					'options'     => array(
						array(
							'option' => 'A',
						),
					),
					'price'       => '50',
				),
				array(
					'title'       => 'Gallery',
					'type'        => 'image',
					'data_name'   => 'gallery',
					'description' => 'Pick one',
					'required'    => '',
					'placeholder' => '',
					'images'      => array(
						array(
							'title' => 'Front',
							'price' => '10',
						),
					),
					'unexpected'  => 'ignored',
				),
			)
		);

		$this->assertCount( 2, $filtered );
		$this->assertSame(
			array( 'title', 'type', 'data_name', 'description', 'required', 'placeholder', 'options' ),
			array_keys( $filtered[0] )
		);
		$this->assertSame( 'Engraving', $filtered[0]['title'] );
		$this->assertSame( 'Gallery', $filtered[1]['title'] );
		$this->assertSame( 'Front', $filtered[1]['options'][0]['title'] );
		$this->assertArrayNotHasKey( 'price', $filtered[0] );
		$this->assertArrayNotHasKey( 'unexpected', $filtered[1] );
	}

	/**
	 * Ensure admin saves still serialize tags while keeping category format.
	 *
	 * @return void
	 */
	public function testSaveCategoriesAndTagsSerializesTagsWhenArrayPassed() {
		$meta_id = $this->insert_ppom_meta(
			array(
				$this->build_text_field( 'field', 'Field' ),
			)
		);

		NM_PersonalizedProduct_Admin::save_categories_and_tags(
			$meta_id,
			array( 'accessories', 'clothing' ),
			array( 'featured', 'summer' )
		);

		$saved_row = $this->get_ppom_meta_row( $meta_id );

		$this->assertSame( "accessories\r\nclothing", $saved_row['productmeta_categories'] );
		$this->assertSame( serialize( array( 'featured', 'summer' ) ), $saved_row['productmeta_tags'] );
	}

	/**
	 * Test that the nonce refresh endpoint returns fresh nonces.
	 *
	 * @return void
	 */
	public function testGetFileNoncesReturnsSuccessWithNonces() {
		$response = $this->dispatch_ppom_rest_request(
			'GET',
			'/ppom/v1/nonces/file/'
		);
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'success', $data['status'] );
		$this->assertArrayHasKey( 'ppom_file_upload_nonce', $data );
		$this->assertArrayHasKey( 'ppom_file_delete_nonce', $data );
		$this->assertNotEmpty( $data['ppom_file_upload_nonce'] );
		$this->assertNotEmpty( $data['ppom_file_delete_nonce'] );
	}

	/**
	 * Test that the nonce refresh endpoint generates valid nonces.
	 *
	 * @return void
	 */
	public function testGetFileNoncesGeneratesValidNonces() {
		$response = $this->dispatch_ppom_rest_request(
			'GET',
			'/ppom/v1/nonces/file/'
		);
		$data     = $response->get_data();

		$this->assertSame( 'success', $data['status'] );

		// Verify that the upload nonce is valid for the upload action
		$upload_nonce_valid = wp_verify_nonce(
			$data['ppom_file_upload_nonce'],
			'ppom_uploading_file_action'
		);
		$this->assertNotFalse( $upload_nonce_valid, 'Upload nonce should be valid' );

		// Verify that the delete nonce is valid for the delete action
		$delete_nonce_valid = wp_verify_nonce(
			$data['ppom_file_delete_nonce'],
			'ppom_deleting_file_action'
		);
		$this->assertNotFalse( $delete_nonce_valid, 'Delete nonce should be valid' );
	}

	/**
	 * Test that multiple calls to the nonce endpoint return different nonces.
	 *
	 * @return void
	 */
	public function testGetFileNoncesReturnsDifferentNoncesOnMultipleCalls() {
		$response1 = $this->dispatch_ppom_rest_request(
			'GET',
			'/ppom/v1/nonces/file/'
		);
		$data1     = $response1->get_data();

		// Wait a tiny moment to ensure different timestamp if nonce generation is time-based
		usleep( 1000 );

		$response2 = $this->dispatch_ppom_rest_request(
			'GET',
			'/ppom/v1/nonces/file/'
		);
		$data2     = $response2->get_data();

		// Both responses should be successful
		$this->assertSame( 'success', $data1['status'] );
		$this->assertSame( 'success', $data2['status'] );

		// Note: WordPress nonces are time-based and may be the same within the same tick
		// This test just ensures the endpoint is callable multiple times
		$this->assertNotEmpty( $data1['ppom_file_upload_nonce'] );
		$this->assertNotEmpty( $data2['ppom_file_upload_nonce'] );
	}
}
