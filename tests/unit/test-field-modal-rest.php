<?php
/**
 * Admin field modal REST routes.
 *
 * @package woocommerce-product-addon
 */

require_once __DIR__ . '/class-ppom-test-case.php';

/**
 * @coversNothing
 */
class Test_Field_Modal_Rest extends PPOM_Test_Case {

	/**
	 * @return void
	 */
	public function test_context_forbidden_when_logged_out() {
		wp_set_current_user( 0 );
		$this->reset_rest_server();

		$request  = new WP_REST_Request( 'GET', '/ppom/v1/admin/field-groups/context' );
		$request->set_query_params( array( 'productmeta_id' => 0 ) );
		$response = rest_get_server()->dispatch( $request );

		$this->assertSame( 403, $response->get_status(), 'Logged-out users must not load editor context.' );
	}

	/**
	 * @return void
	 */
	public function test_context_ok_for_administrator() {
		$admin_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );
		$this->reset_rest_server();

		$request  = new WP_REST_Request( 'GET', '/ppom/v1/admin/field-groups/context' );
		$request->set_query_params( array( 'productmeta_id' => 0 ) );
		$response = rest_get_server()->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'catalog', $data );
		$this->assertArrayHasKey( 'fields', $data );
		$this->assertArrayHasKey( 'group', $data );
		$this->assertArrayHasKey( 'type_schemas', $data );
		$this->assertIsArray( $data['type_schemas'] );
		$this->assertArrayHasKey( 'catalog_groups', $data );
		$this->assertIsArray( $data['catalog_groups'] );
		$this->assertNotEmpty( $data['catalog_groups'], 'catalog_groups must mirror admin field picker groups.' );
		$this->assertArrayHasKey( 'license', $data );
		$this->assertArrayHasKey( 'upsell', $data );
		$this->assertArrayHasKey( 'links', $data );
		$this->assertIsArray( $data['links'] );
		$this->assertArrayHasKey( 'cfrDocsUrl', $data['links'] );
		$this->assertArrayHasKey( 'cfrUpgradeUrl', $data['links'] );
		$this->assertArrayHasKey( 'cfrViewDemoUrl', $data['links'] );
		$this->assertArrayHasKey( 'conditionUpgradeUrl', $data['links'] );
		$this->assertArrayNotHasKey( 'i18n', $data, 'Static React modal copy should not be shipped in REST context.' );
		$this->assertArrayHasKey( 'conditions_pro_enabled', $data );
		$this->assertIsBool( $data['conditions_pro_enabled'] );
		$this->assertArrayHasKey( 'conditional_repeater_unlocked', $data );
		$this->assertIsBool( $data['conditional_repeater_unlocked'] );
		$this->assertArrayHasKey( 'conditional_repeater_show_upsell', $data );
		$this->assertIsBool( $data['conditional_repeater_show_upsell'] );
		$this->assertSame(
			! $data['conditional_repeater_unlocked'],
			$data['conditional_repeater_show_upsell'],
			'CFR upsell flag must mirror unlocked state.'
		);
		$this->assertNotEmpty( $data['catalog'], 'Flat catalog must list field types for the modal.' );

		$first_group = $data['catalog_groups'][0];
		$this->assertArrayHasKey( 'id', $first_group );
		$this->assertArrayHasKey( 'label', $first_group );
		$this->assertIsString( $first_group['label'] );
		$this->assertNotSame( '', $first_group['label'], 'Field group labels must remain PHP/provider-owned.' );
		$this->assertArrayHasKey( 'fields', $first_group );
		$this->assertNotEmpty( $first_group['fields'] );
		$field0 = $first_group['fields'][0];
		$this->assertArrayHasKey( 'slug', $field0 );
		$this->assertArrayHasKey( 'locked', $field0 );
	}

	/**
	 * Lazy schema route returns settings for a free core type.
	 *
	 * @return void
	 */
	public function test_schema_route_returns_text_settings() {
		$admin_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );
		$this->reset_rest_server();

		$request  = new WP_REST_Request( 'GET', '/ppom/v1/admin/field-groups/schema/text' );
		$response = rest_get_server()->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );
		$body = $response->get_data();
		$this->assertIsArray( $body );
		$this->assertArrayHasKey( 'schema', $body );
		$this->assertArrayHasKey( 'settings', $body['schema'] );
	}

	/**
	 * Boot filter can force CFR unlocked for tests without PPOM Pro loaded.
	 *
	 * @return void
	 */
	public function test_conditional_repeater_unlocked_respects_boot_filter() {
		$admin_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );
		$this->reset_rest_server();

		add_filter( 'ppom_field_modal_conditional_repeater_unlocked', '__return_true' );

		$request  = new WP_REST_Request( 'GET', '/ppom/v1/admin/field-groups/context' );
		$request->set_query_params( array( 'productmeta_id' => 0 ) );
		$response = rest_get_server()->dispatch( $request );

		remove_filter( 'ppom_field_modal_conditional_repeater_unlocked', '__return_true' );

		$this->assertSame( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertTrue( $data['conditional_repeater_unlocked'] );
		$this->assertFalse( $data['conditional_repeater_show_upsell'] );
	}

	/**
	 * The shared save pipeline should normalize a minimal image field without
	 * depending on legacy global helper load order.
	 *
	 * @return void
	 */
	public function test_normalize_fields_allows_minimal_image_field_payload() {
		$persistence = new \PPOM\Admin\FieldModal\FieldModalPersistence();
		$result      = $persistence->normalize_fields(
			array(
				array(
					'type'      => 'radio',
					'title'     => 'sgdsfg',
					'data_name' => 'sgdsfg',
					'options'   => array(
						array(
							'option' => '',
							'price'  => '',
							'weight' => '',
							'stock'  => '',
							'id'     => '_',
						),
					),
					'status'    => 'on',
				),
				array(
					'type'      => 'textarea',
					'title'     => 'Textarea Input',
					'data_name' => 'textareainput',
					'status'    => 'on',
				),
				array(
					'type'      => 'text',
					'title'     => 'Text Input',
					'data_name' => 'textinput',
					'status'    => 'on',
				),
				array(
					'type'      => 'image',
					'title'     => 'Images',
					'data_name' => 'images',
					'status'    => 'on',
				),
			),
			'1'
		);

		$this->assertIsArray( $result );
		$this->assertCount( 4, $result );
		$this->assertSame( 'image', $result[3]['type'] );
		$this->assertSame( 'images', $result[3]['data_name'] );
	}

	/**
	 * React modal should keep classic editor behavior and reject blank data_name.
	 *
	 * @return void
	 */
	public function test_normalize_fields_rejects_blank_data_name() {
		$persistence = new \PPOM\Admin\FieldModal\FieldModalPersistence();
		$result      = $persistence->normalize_fields(
			array(
				array(
					'type'      => 'image',
					'title'     => 'Images',
					'data_name' => '',
					'status'    => 'on',
				),
			),
			'1'
		);

		$this->assertWPError( $result );
		$this->assertSame( 'ppom_field_validation', $result->get_error_code() );
		$this->assertSame( 'Field validation failed.', $result->get_error_message() );
		$this->assertSame(
			array( 'Data Name must be required' ),
			$result->get_error_data()['errors']
		);
	}

	/**
	 * Reset REST server routes (FieldModal registers on rest_api_init).
	 *
	 * @return void
	 */
	private function reset_rest_server() {
		global $wp_rest_server;
		$wp_rest_server = null;
		$server         = rest_get_server();
		do_action( 'rest_api_init', $server );
	}
}
