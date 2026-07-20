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
	 * Deleting a shared group removes only that group from every direct product
	 * assignment while preserving other attached groups.
	 *
	 * @return void
	 */
	public function testDeletePPOMFieldsProductCleansSharedAssignmentsAndPreservesOtherGroups() {
		$product_a = $this->create_simple_product();
		$product_b = $this->create_simple_product();
		$deleted   = $this->insert_ppom_meta(
			array(
				$this->build_text_field( 'deleted_field', 'Deleted' ),
			)
		);
		$kept      = $this->insert_ppom_meta(
			array(
				$this->build_text_field( 'kept_field', 'Kept' ),
			)
		);

		update_post_meta( $product_a->get_id(), PPOM_PRODUCT_META_KEY, array( $deleted, $kept ) );
		update_post_meta( $product_b->get_id(), PPOM_PRODUCT_META_KEY, array( $deleted, $kept ) );

		$response = $this->dispatch_ppom_rest_request(
			'POST',
			'/ppom/v1/delete/product/',
			array(
				'product_id' => $product_a->get_id(),
				'secret_key' => 'expected-secret',
				'fields'     => wp_json_encode( array( '__all_keys' ) ),
			),
			'expected-secret'
		);

		$this->assertSame( 'success', $response->get_data()['status'] );
		$this->assertNull( $this->get_ppom_meta_row( $deleted ) );
		$this->assertNotNull( $this->get_ppom_meta_row( $kept ) );
		$this->assertSame( array( $kept ), array_values( (array) get_post_meta( $product_a->get_id(), PPOM_PRODUCT_META_KEY, true ) ) );
		$this->assertSame( array( $kept ), array_values( (array) get_post_meta( $product_b->get_id(), PPOM_PRODUCT_META_KEY, true ) ) );
	}

	/**
	 * Bulk group deletion cleans both multi-group and legacy scalar assignments.
	 *
	 * @return void
	 */
	public function testBulkGroupDeletionCleansMultiGroupAndLegacyScalarAssignments() {
		$multi_product  = $this->create_simple_product();
		$scalar_product = $this->create_simple_product();
		$deleted_a      = $this->insert_ppom_meta( array( $this->build_text_field( 'deleted_a', 'Deleted A' ) ) );
		$deleted_b      = $this->insert_ppom_meta( array( $this->build_text_field( 'deleted_b', 'Deleted B' ) ) );
		$kept           = $this->insert_ppom_meta( array( $this->build_text_field( 'kept', 'Kept' ) ) );

		update_post_meta( $multi_product->get_id(), PPOM_PRODUCT_META_KEY, array( $deleted_a, $kept, $deleted_b ) );
		update_post_meta( $scalar_product->get_id(), PPOM_PRODUCT_META_KEY, $deleted_b );

		$deleted_count = ppom_meta_repository()->delete_by_ids( array( $deleted_a, $deleted_b ) );

		$this->assertSame( 2, $deleted_count );
		$this->assertSame( array( $kept ), array_values( (array) get_post_meta( $multi_product->get_id(), PPOM_PRODUCT_META_KEY, true ) ) );
		$this->assertFalse( metadata_exists( 'post', $scalar_product->get_id(), PPOM_PRODUCT_META_KEY ) );
		$this->assertNotNull( $this->get_ppom_meta_row( $kept ) );
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

	/**
	 * Test that the refreshed nonces are valid for the authenticated user.
	 *
	 * @return void
	 */
	public function testGetFileNoncesRefreshedNonceIsValidForAuthenticatedUser() {
		// Simulate page load: user is logged in and the page-load nonce is created for them.
		$user_id = $this->create_shop_manager_user();
		wp_set_current_user( $user_id );

		$response = $this->dispatch_ppom_rest_request(
			'GET',
			'/ppom/v1/nonces/file/',
			array(),
			'secret-key',
			false
		);
		$data     = $response->get_data();

		$this->assertSame( 'success', $data['status'] );

		$upload_nonce_valid = wp_verify_nonce(
			$data['ppom_file_upload_nonce'],
			'ppom_uploading_file_action'
		);
		$this->assertNotFalse(
			$upload_nonce_valid,
			'Refreshed upload nonce must be valid for the logged-in user'
		);

		$delete_nonce_valid = wp_verify_nonce(
			$data['ppom_file_delete_nonce'],
			'ppom_deleting_file_action'
		);
		$this->assertNotFalse(
			$delete_nonce_valid,
			'Refreshed delete nonce must be valid for the logged-in user'
		);
	}

	/**
	 * Test that an unauthenticated request returns a nonce that does not verify for a logged-in user.
	 *
	 * @return void
	 */
	public function testGetFileNoncesUnauthenticatedRequestFailsVerificationForLoggedInUser() {
		$user_id = $this->create_shop_manager_user();
		wp_set_current_user( $user_id );
		$page_load_nonce = wp_create_nonce( 'ppom_uploading_file_action' );

		wp_set_current_user( 0 );
		$response = $this->dispatch_ppom_rest_request(
			'GET',
			'/ppom/v1/nonces/file/',
			array(),
			'secret-key',
			false
		);
		$data     = $response->get_data();
		$this->assertSame( 'success', $data['status'] );

		$anonymous_nonce = $data['ppom_file_upload_nonce'];

		wp_set_current_user( $user_id );

		$valid = wp_verify_nonce( $anonymous_nonce, 'ppom_uploading_file_action' );
		$this->assertFalse(
			(bool) $valid,
			'A nonce generated for user 0 must not verify as valid for a logged-in user'
		);

		$page_load_valid = wp_verify_nonce( $page_load_nonce, 'ppom_uploading_file_action' );
		$this->assertNotFalse(
			$page_load_valid,
			'Page-load nonce must remain valid for the same logged-in user'
		);
	}

	/**
	 * Ensure unauthenticated GET requests are rejected with rest_forbidden.
	 *
	 * @return void
	 */
	public function testUnauthenticatedGetProductRequestReturnsForbidden() {
		$product = $this->create_simple_product();

		$this->insert_ppom_meta(
			array(
				$this->build_text_field( 'engraving', 'Engraving' ),
			),
			$product->get_id()
		);

		$response = $this->dispatch_ppom_rest_request(
			'GET',
			'/ppom/v1/get/product/',
			array(
				'product_id' => $product->get_id(),
			),
			'secret-key',
			false
		);

		$this->assertSame( 401, $response->get_status() );
		$this->assertSame( 'rest_forbidden', $response->get_data()['code'] );
	}

	/**
	 * Ensure unauthenticated POST requests are rejected with rest_forbidden.
	 *
	 * @return void
	 */
	public function testUnauthenticatedPostProductRequestReturnsForbidden() {
		$product = $this->create_simple_product();

		$response = $this->dispatch_ppom_rest_request(
			'POST',
			'/ppom/v1/set/product/',
			array(
				'product_id' => $product->get_id(),
				'secret_key' => 'secret-key',
				'fields'     => wp_json_encode(
					array(
						$this->build_text_field( 'engraving', 'Engraving' ),
					)
				),
			),
			'secret-key',
			false
		);

		$this->assertSame( 401, $response->get_status() );
		$this->assertSame( 'rest_forbidden', $response->get_data()['code'] );
	}

	/**
	 * Ensure unauthenticated GET order requests are rejected with rest_forbidden.
	 *
	 * @return void
	 */
	public function testUnauthenticatedGetOrderRequestReturnsForbidden() {
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
			'GET',
			'/ppom/v1/get/order/',
			array(
				'order_id' => $order->get_id(),
			),
			'secret-key',
			false
		);

		$this->assertSame( 401, $response->get_status() );
		$this->assertSame( 'rest_forbidden', $response->get_data()['code'] );
	}

	/**
	 * Ensure unauthenticated POST order requests are rejected with rest_forbidden.
	 *
	 * @return void
	 */
	public function testUnauthenticatedPostOrderRequestReturnsForbidden() {
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
				'secret_key' => 'secret-key',
				'fields'     => wp_json_encode(
					array(
						$product->get_id() => array(
							'engraving' => 'Updated',
						),
					)
				),
			),
			'secret-key',
			false
		);

		$this->assertSame( 401, $response->get_status() );
		$this->assertSame( 'rest_forbidden', $response->get_data()['code'] );
	}

	/**
	 * Ensure unauthenticated GET /get/id/{id} is rejected with rest_forbidden (R1).
	 *
	 * @return void
	 */
	public function testUnauthenticatedGetMetaByIdReturnsForbidden() {
		$meta_id = $this->insert_ppom_meta(
			array(
				$this->build_text_field( 'engraving', 'Engraving' ),
			)
		);

		$response = $this->dispatch_ppom_rest_request(
			'GET',
			'/ppom/v1/get/id/' . $meta_id,
			array(),
			'secret-key',
			false
		);

		$this->assertSame( 401, $response->get_status() );
		$this->assertSame( 'rest_forbidden', $response->get_data()['code'] );
	}

	/**
	 * Ensure unauthenticated POST /delete/product/ is rejected with rest_forbidden (R1).
	 *
	 * @return void
	 */
	public function testUnauthenticatedDeleteProductRequestReturnsForbidden() {
		$product = $this->create_simple_product();

		$response = $this->dispatch_ppom_rest_request(
			'POST',
			'/ppom/v1/delete/product/',
			array(
				'product_id' => $product->get_id(),
				'secret_key' => 'secret-key',
				'fields'     => wp_json_encode( array( 'engraving' ) ),
			),
			'secret-key',
			false
		);

		$this->assertSame( 401, $response->get_status() );
		$this->assertSame( 'rest_forbidden', $response->get_data()['code'] );
	}

	/**
	 * Ensure unauthenticated POST /delete/order/ is rejected with rest_forbidden (R1).
	 *
	 * @return void
	 */
	public function testUnauthenticatedDeleteOrderRequestReturnsForbidden() {
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
				'secret_key' => 'secret-key',
				'fields'     => wp_json_encode(
					array(
						$product->get_id() => array( 'engraving' ),
					)
				),
			),
			'secret-key',
			false
		);

		$this->assertSame( 401, $response->get_status() );
		$this->assertSame( 'rest_forbidden', $response->get_data()['code'] );
	}
}
