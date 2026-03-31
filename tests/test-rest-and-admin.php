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
		$this->set_ppom_option( 'ppom_rest_secret_key', 'secret-key' );

		$rows_before = $this->ppom_meta_row_count();
		$request     = new WP_REST_Request( 'POST', '/ppom/v1/set/product/' );
		$request->set_param( 'product_id', $product->get_id() );
		$request->set_param( 'secret_key', 'secret-key' );

		$response = ( new PPOM_Rest() )->ppom_save_meta_product( $request );
		$data     = $response->get_data();

		$this->assertSame( 'no_fields', $data['status'] );
		$this->assertSame( $rows_before, $this->ppom_meta_row_count() );
		$this->assertEmpty( get_post_meta( $product->get_id(), PPOM_PRODUCT_META_KEY, true ) );
	}

	/**
	 * Ensure invalid REST secrets do not create product PPOM rows.
	 *
	 * @return void
	 */
	public function testPPOMSaveMetaProductRejectsInvalidSecretKeyWithoutCreatingMeta() {
		$product = $this->create_simple_product();
		$this->set_ppom_option( 'ppom_rest_secret_key', 'expected-secret' );

		$rows_before = $this->ppom_meta_row_count();
		$request     = new WP_REST_Request( 'POST', '/ppom/v1/set/product/' );
		$request->set_param( 'product_id', $product->get_id() );
		$request->set_param( 'secret_key', 'wrong-secret' );
		$request->set_param(
			'fields',
			wp_json_encode(
				array(
					array(
						'type'      => 'text',
						'title'     => 'Engraving',
						'data_name' => 'engraving',
					),
				)
			)
		);

		$response = ( new PPOM_Rest() )->ppom_save_meta_product( $request );
		$data     = $response->get_data();

		$this->assertSame( 'key_not_valid', $data['status'] );
		$this->assertSame( $rows_before, $this->ppom_meta_row_count() );
		$this->assertEmpty( get_post_meta( $product->get_id(), PPOM_PRODUCT_META_KEY, true ) );
	}

	/**
	 * Ensure admin saves still serialize tags while keeping category format.
	 *
	 * @return void
	 */
	public function testSaveCategoriesAndTagsSerializesTagsWhenArrayPassed() {
		$meta_id = $this->insert_ppom_meta(
			array(
				array(
					'type'      => 'text',
					'title'     => 'Field',
					'data_name' => 'field',
				),
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
}
