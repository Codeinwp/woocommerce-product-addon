<?php
/**
 * Unit tests for small PPOM\Hooks\Callbacks helpers.
 *
 * @package ppom-pro
 */

use PPOM\Hooks\Callbacks;

/**
 * @covers \PPOM\Hooks\Callbacks::convert_price
 * @covers \PPOM\Hooks\Callbacks::convert_price_back
 * @covers \PPOM\Hooks\Callbacks::register_wpml
 */
class Test_Callbacks_Src extends WP_UnitTestCase {

	/**
	 * @var callable|null
	 */
	private $woocs_filter;

	/**
	 * @var array<int, array{errno:int,errstr:string}>
	 */
	private $captured_errors = array();

	/**
	 * @var bool
	 */
	private $error_handler_active = false;

	/**
	 * @return void
	 */
	public function tearDown(): void {
		if ( null !== $this->woocs_filter ) {
			remove_filter( 'woocs_exchange_value', $this->woocs_filter );
			$this->woocs_filter = null;
		}
		$this->release_warnings();
		parent::tearDown();
	}

	/**
	 * Capture E_WARNING / E_NOTICE emitted during the test body.
	 *
	 * @return void
	 */
	private function capture_warnings() {
		$this->captured_errors      = array();
		$this->error_handler_active = true;
		set_error_handler(
			function ( $errno, $errstr ) {
				if ( in_array( $errno, array( E_WARNING, E_NOTICE, E_USER_WARNING, E_USER_NOTICE ), true ) ) {
					$this->captured_errors[] = array(
						'errno'  => $errno,
						'errstr' => $errstr,
					);
				}
				return true;
			}
		);
	}

	/**
	 * @return void
	 */
	private function release_warnings() {
		if ( $this->error_handler_active ) {
			restore_error_handler();
			$this->error_handler_active = false;
		}
	}

	/**
	 * @return void
	 */
	public function test_convert_price_applies_woocs_exchange_value_filter() {
		$this->woocs_filter = static function ( $price ) {
			return (float) $price * 2;
		};
		add_filter( 'woocs_exchange_value', $this->woocs_filter, 10, 1 );

		$this->assertSame( 20.0, Callbacks::convert_price( 10 ) );
	}

	/**
	 * @return void
	 */
	public function test_convert_price_passes_through_when_filter_absent() {
		$this->assertSame( 12.5, Callbacks::convert_price( 12.5 ) );
	}

	/**
	 * @return void
	 */
	public function test_convert_price_back_returns_input_when_woocs_filter_not_registered() {
		$this->assertSame( 99.0, Callbacks::convert_price_back( 99.0 ) );
	}

	/**
	 * @return void
	 */
	public function test_register_wpml_does_not_warn_when_meta_row_has_no_title() {
		$this->capture_warnings();

		$result = Callbacks::register_wpml(
			array(
				array(
					'type'      => 'text',
					'data_name' => 'foo',
				),
			),
			123
		);

		$this->release_warnings();

		$this->assertSame(
			array(),
			$this->captured_errors,
			sprintf( 'register_wpml emitted warnings: %s', wp_json_encode( $this->captured_errors ) )
		);
		$this->assertSame( 'foo', $result[0]['data_name'] );
		$this->assertSame( 123, $result[0]['ppom_id'] );
	}

	/**
	 * @return void
	 */
	public function test_register_wpml_populates_data_name_when_neither_title_nor_data_name_present() {
		$this->capture_warnings();

		$result = Callbacks::register_wpml(
			array(
				array( 'type' => 'text' ),
			),
			123
		);

		$this->release_warnings();

		$this->assertSame( array(), $this->captured_errors );
		$this->assertArrayHasKey( 'data_name', $result[0] );
		$this->assertSame( '', $result[0]['data_name'] );
		$this->assertSame( 123, $result[0]['ppom_id'] );
	}

	/**
	 * @return void
	 */
	public function test_register_wpml_adds_ppom_id_to_every_row_regardless_of_title() {
		$this->capture_warnings();

		$result = Callbacks::register_wpml(
			array(
				array( 'data_name' => 'a' ),
				array( 'title' => 'Beta' ),
			),
			99
		);

		$this->release_warnings();

		$this->assertSame( array(), $this->captured_errors );
		$this->assertCount( 2, $result );
		$this->assertSame( 99, $result[0]['ppom_id'] );
		$this->assertSame( 99, $result[1]['ppom_id'] );
	}

	/**
	 * @return void
	 */
	public function test_register_wpml_calls_nm_wpml_register_when_title_present() {
		if ( ! function_exists( 'icl_register_string' ) ) {
			$this->markTestSkipped( 'WPML icl_register_string is not available; nm_wpml_register early-returns and the action is not fired.' );
		}

		$captured = array();
		$spy      = function ( $domain, $field_name, $field_value ) use ( &$captured ) {
			$captured[] = array(
				'domain'      => $domain,
				'field_name'  => $field_name,
				'field_value' => $field_value,
			);
		};
		add_action( 'wpml_register_single_string', $spy, 10, 3 );

		Callbacks::register_wpml(
			array(
				array(
					'type'      => 'text',
					'data_name' => 'foo',
					'title'     => 'Foo Title',
				),
			),
			321
		);

		remove_action( 'wpml_register_single_string', $spy, 10 );

		$this->assertNotEmpty( $captured, 'Expected wpml_register_single_string action to fire for the row title.' );
		$values = wp_list_pluck( $captured, 'field_value' );
		$this->assertContains( 'Foo Title', $values );
	}
}
