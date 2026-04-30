<?php
/**
 * Class Test_Template_Library
 *
 * Coverage for the curated template registry, the server-side Pro gate, and
 * the AJAX import handler.
 *
 * @package ppom-pro
 */

require_once __DIR__ . '/class-ppom-test-case.php';

class Test_Template_Library extends PPOM_Test_Case {

	/**
	 * Counter incremented every time `ppom_meta_data_saving` runs during a test.
	 *
	 * @var int
	 */
	private $filter_calls = 0;

	/**
	 * IDs collected by the `ppom_meta_data_saving` filter while it runs.
	 *
	 * @var array<int, string>
	 */
	private $filter_ids = array();

	/**
	 * Reset shared counters between tests.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();
		$this->filter_calls = 0;
		$this->filter_ids   = array();
	}

	/**
	 * Invoke the import handler and capture the wp_send_json payload.
	 *
	 * @return array<string, mixed>
	 */
	private function invoke_import() {
		ob_start();
		try {
			\PPOM\Admin\Manager::import_template();
		} catch ( \WPDieException $e ) {
			// Expected: wp_send_json terminates via wp_die.
		}

		$output  = ob_get_clean();
		$decoded = json_decode( $output, true );

		return is_array( $decoded ) ? $decoded : array( 'raw' => $output );
	}

	/**
	 * Set up a valid nonce + admin capability and a template slug in the request superglobals.
	 *
	 * @param string $slug       Template slug.
	 * @param int    $product_id Optional product ID to attach.
	 *
	 * @return void
	 */
	private function setup_valid_request( $slug, $product_id = 0 ) {
		wp_set_current_user( $this->factory->user->create( array( 'role' => 'administrator' ) ) );

		$_POST                                = array();
		$_POST['ppom_import_template_nonce']  = wp_create_nonce( 'ppom_import_template_action' );
		$_POST['template']                    = $slug;
		if ( $product_id ) {
			$_POST['product_id'] = (string) $product_id;
		}
	}

	public function test_registry_has_eight_templates_with_required_keys() {
		$templates = PPOM_Template_Library::get_templates();

		$this->assertCount( 8, $templates, 'Expected exactly 8 curated templates.' );

		foreach ( $templates as $slug => $template ) {
			$this->assertSame( $slug, $template['slug'] );
			$this->assertNotEmpty( $template['title'] );
			$this->assertNotEmpty( $template['description'] );
			$this->assertNotEmpty( $template['icon'] );
			$this->assertNotEmpty( $template['group_name'] );
			$this->assertIsArray( $template['fields'] );
			$this->assertNotEmpty( $template['fields'] );

			foreach ( $template['fields'] as $field ) {
				$this->assertArrayHasKey( 'type', $field );
				$this->assertArrayHasKey( 'data_name', $field );
				$this->assertNotEmpty( $field['type'] );
				$this->assertNotEmpty( $field['data_name'] );
			}
		}
	}

	public function test_is_pro_type_classifies_known_types() {
		$this->assertFalse( PPOM_Template_Library::is_pro_type( 'text' ) );
		$this->assertFalse( PPOM_Template_Library::is_pro_type( 'select' ) );
		$this->assertFalse( PPOM_Template_Library::is_pro_type( 'checkbox' ) );
		$this->assertFalse( PPOM_Template_Library::is_pro_type( 'date' ) );

		$this->assertTrue( PPOM_Template_Library::is_pro_type( 'file' ) );
		$this->assertTrue( PPOM_Template_Library::is_pro_type( 'measure' ) );
		$this->assertTrue( PPOM_Template_Library::is_pro_type( 'cropper' ) );
	}

	public function test_derive_template_plan_returns_free_for_only_free_types() {
		$template = PPOM_Template_Library::get_template( 'food-customiser' );
		$this->assertNotNull( $template );

		$this->assertSame(
			NM_PersonalizedProduct::LICENSE_PLAN_FREE,
			PPOM_Template_Library::derive_template_plan( $template )
		);
	}

	public function test_derive_template_plan_returns_plan_1_when_pro_type_present() {
		$template = PPOM_Template_Library::get_template( 'made-to-measure' );
		$this->assertNotNull( $template );

		$this->assertSame(
			NM_PersonalizedProduct::LICENSE_PLAN_1,
			PPOM_Template_Library::derive_template_plan( $template )
		);
	}

	public function test_user_can_use_allows_free_template_for_anyone() {
		$template = PPOM_Template_Library::get_template( 'food-customiser' );

		$this->assertTrue( PPOM_Template_Library::user_can_use( $template ) );
	}

	public function test_user_can_use_blocks_pro_template_for_unlicensed_user() {
		$template = PPOM_Template_Library::get_template( 'made-to-measure' );

		$this->with_ppom_license_filters( 'invalid' );
		$this->assertFalse( PPOM_Template_Library::user_can_use( $template ) );
	}

	public function test_user_can_use_blocks_pro_template_for_expired_license() {
		$template = PPOM_Template_Library::get_template( 'made-to-measure' );

		$this->with_ppom_license_filters( 'invalid', 3 );
		$this->assertFalse( PPOM_Template_Library::user_can_use( $template ) );
	}

	public function test_user_can_use_allows_pro_template_for_valid_license() {
		$template = PPOM_Template_Library::get_template( 'made-to-measure' );

		$this->with_ppom_license_filters( 'valid', 1 );
		$this->assertTrue( PPOM_Template_Library::user_can_use( $template ) );
	}

	public function test_import_creates_group_with_expected_field_count() {
		$this->setup_valid_request( 'food-customiser' );

		$rows_before = $this->ppom_meta_row_count();
		$response    = $this->invoke_import();

		$this->assertSame( 'success', $response['status'] );
		$this->assertGreaterThan( 0, $response['productmeta_id'] );
		$this->assertSame( $rows_before + 1, $this->ppom_meta_row_count() );

		$row = $this->get_ppom_meta_row( (int) $response['productmeta_id'] );
		$this->assertNotNull( $row );

		$decoded  = json_decode( $row['the_meta'], true );
		$template = PPOM_Template_Library::get_template( 'food-customiser' );

		$this->assertSame( count( $template['fields'] ), count( $decoded ) );
	}

	public function test_import_attaches_to_product_when_product_id_posted() {
		$product = $this->create_simple_product();
		$this->setup_valid_request( 'personalised-gift', $product->get_id() );

		$response = $this->invoke_import();

		$this->assertSame( 'success', $response['status'] );

		$attached = get_post_meta( $product->get_id(), PPOM_PRODUCT_META_KEY, true );
		$this->assertNotEmpty( $attached );
	}

	public function test_import_runs_meta_saving_filter_twice() {
		$this->setup_valid_request( 'food-customiser' );

		$tracker = function ( $fields, $id ) {
			++$this->filter_calls;
			$this->filter_ids[] = (string) $id;
			return $fields;
		};
		add_filter( 'ppom_meta_data_saving', $tracker, 10, 2 );

		$response = $this->invoke_import();

		remove_filter( 'ppom_meta_data_saving', $tracker, 10 );

		$this->assertSame( 'success', $response['status'] );
		$this->assertSame( 2, $this->filter_calls, 'Filter must run twice — once before insert, once after.' );
		$this->assertSame( '', $this->filter_ids[0], 'First filter call must use empty id.' );
		$this->assertSame( (string) $response['productmeta_id'], $this->filter_ids[1], 'Second filter call must use the resolved productmeta_id.' );
	}

	public function test_import_rejects_pro_template_for_free_user() {
		$this->setup_valid_request( 'made-to-measure' );
		$this->with_ppom_license_filters( 'invalid' );

		$rows_before = $this->ppom_meta_row_count();
		$response    = $this->invoke_import();

		$this->assertSame( 'error', $response['status'] );
		$this->assertSame( $rows_before, $this->ppom_meta_row_count() );
	}

	public function test_import_rejects_pro_template_for_expired_license() {
		$this->setup_valid_request( 'jewellery-engraving' );
		$this->with_ppom_license_filters( 'invalid', 3 );

		$rows_before = $this->ppom_meta_row_count();
		$response    = $this->invoke_import();

		$this->assertSame( 'error', $response['status'] );
		$this->assertSame( $rows_before, $this->ppom_meta_row_count() );
	}

	public function test_import_rejects_invalid_nonce() {
		wp_set_current_user( $this->factory->user->create( array( 'role' => 'administrator' ) ) );

		$_POST                               = array();
		$_POST['ppom_import_template_nonce'] = 'bogus-nonce';
		$_POST['template']                   = 'food-customiser';

		$rows_before = $this->ppom_meta_row_count();
		$response    = $this->invoke_import();

		$this->assertSame( 'error', $response['status'] );
		$this->assertSame( $rows_before, $this->ppom_meta_row_count() );
	}

	public function test_import_rejects_unprivileged_user() {
		wp_set_current_user( $this->factory->user->create( array( 'role' => 'subscriber' ) ) );

		$_POST                               = array();
		$_POST['ppom_import_template_nonce'] = wp_create_nonce( 'ppom_import_template_action' );
		$_POST['template']                   = 'food-customiser';

		$rows_before = $this->ppom_meta_row_count();
		$response    = $this->invoke_import();

		$this->assertSame( 'error', $response['status'] );
		$this->assertSame( $rows_before, $this->ppom_meta_row_count() );
	}

	public function test_import_rejects_unknown_slug() {
		$this->setup_valid_request( 'does-not-exist' );

		$rows_before = $this->ppom_meta_row_count();
		$response    = $this->invoke_import();

		$this->assertSame( 'error', $response['status'] );
		$this->assertSame( $rows_before, $this->ppom_meta_row_count() );
	}
}
