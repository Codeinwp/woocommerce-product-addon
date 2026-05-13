<?php
/**
 * Unit tests for PPOM\Arrays\Settings defaults and filters.
 *
 * @package ppom-pro
 */

use PPOM\Arrays\Settings;

/**
 * @covers \PPOM\Arrays\Settings
 */
class Test_Settings extends WP_UnitTestCase {

	/**
	 * @var array<string, callable>
	 */
	private $filters = array();

	/**
	 * @return void
	 */
	public function tearDown(): void {
		foreach ( $this->filters as $tag => $callback ) {
			remove_filter( $tag, $callback );
		}
		$this->filters = array();
		parent::tearDown();
	}

	/**
	 * @param string   $tag
	 * @param callable $callback
	 * @return void
	 */
	private function add_test_filter( $tag, $callback ) {
		add_filter( $tag, $callback );
		$this->filters[ $tag ] = $callback;
	}

	/**
	 * @return void
	 */
	public function test_get_plugin_meta_has_core_keys() {
		$meta = Settings::get_plugin_meta();

		$this->assertArrayHasKey( 'name', $meta );
		$this->assertArrayHasKey( 'path', $meta );
		$this->assertArrayHasKey( 'url', $meta );
		$this->assertSame( 'PPOM', $meta['name'] );
	}

	/**
	 * @return void
	 */
	public function test_get_plugin_meta_apply_filters_ppom_plugin_meta() {
		$this->add_test_filter(
			'ppom_plugin_meta',
			static function ( $ppom_meta ) {
				$ppom_meta['unit_test_flag'] = 1;
				return $ppom_meta;
			}
		);

		$this->assertSame( 1, Settings::get_plugin_meta()['unit_test_flag'] );
	}

	/**
	 * @return void
	 */
	public function test_get_input_cols_default_range() {
		$cols = Settings::get_input_cols();

		$this->assertArrayHasKey( 2, $cols );
		$this->assertArrayHasKey( 12, $cols );
	}

	/**
	 * @return void
	 */
	public function test_field_visibility_options_has_expected_keys() {
		$opts = Settings::field_visibility_options();

		$this->assertArrayHasKey( 'everyone', $opts );
		$this->assertArrayHasKey( 'guests', $opts );
		$this->assertArrayHasKey( 'members', $opts );
		$this->assertArrayHasKey( 'roles', $opts );
	}

	/**
	 * @return void
	 */
	public function test_get_timezone_list_europe_non_empty() {
		$list = Settings::get_timezone_list( 'EUROPE', 'no' );

		$this->assertNotEmpty( $list );
		$this->assertArrayHasKey( 'Europe/London', $list );
	}

	/**
	 * @return void
	 */
	public function test_get_timezone_list_all_includes_multiple_regions() {
		$list = Settings::get_timezone_list( 'All', 'no' );

		$this->assertArrayHasKey( 'Europe/London', $list );
		$this->assertArrayHasKey( 'America/New_York', $list );
	}

	/**
	 * Regression: identifiers returned by listIdentifiers() that the bundled tzdata cannot
	 * construct (e.g. America/Ciudad_Juarez on outdated PHP) must not bubble an exception.
	 *
	 * @return void
	 */
	public function test_get_timezone_list_does_not_throw_for_any_region() {
		$regions = array( 'All', 'AFRICA', 'AMERICA', 'ANTARCTICA', 'ASIA', 'ATLANTIC', 'AUSTRALIA', 'EUROPE', 'INDIAN', 'PACIFIC' );

		foreach ( $regions as $region ) {
			try {
				$list = Settings::get_timezone_list( $region, 'no' );
			} catch ( \Throwable $e ) {
				$this->fail( "get_timezone_list( '$region', 'no' ) threw: " . $e->getMessage() );
			}

			$this->assertIsArray( $list );
			$this->assertNotEmpty( $list, "Expected non-empty timezone list for region '$region'" );
		}
	}

	/**
	 * Every identifier returned must be constructable — the fix filters out any that aren't.
	 *
	 * @return void
	 */
	public function test_get_timezone_list_only_returns_constructable_identifiers() {
		$list = Settings::get_timezone_list( 'All', 'no' );

		foreach ( array_keys( $list ) as $identifier ) {
			try {
				new \DateTimeZone( $identifier );
			} catch ( \Exception $e ) {
				$this->fail( "Returned identifier '$identifier' is not constructable: " . $e->getMessage() );
			}
		}
	}

	/**
	 * @return void
	 */
	public function test_get_timezone_list_with_comma_separated_regions() {
		$list = Settings::get_timezone_list( 'EUROPE,ASIA', 'no' );

		$this->assertArrayHasKey( 'Europe/London', $list );
		$this->assertArrayHasKey( 'Asia/Tokyo', $list );
		$this->assertArrayNotHasKey( 'America/New_York', $list );
	}

	/**
	 * @return void
	 */
	public function test_get_timezone_list_show_time_toggles_current_time() {
		$with_time    = Settings::get_timezone_list( 'EUROPE', 'on' );
		$without_time = Settings::get_timezone_list( 'EUROPE', 'no' );

		$this->assertMatchesRegularExpression( '/\d{1,2}:\d{2}\s?(AM|PM)/', $with_time['Europe/London'] );
		$this->assertDoesNotMatchRegularExpression( '/\d{1,2}:\d{2}\s?(AM|PM)/', $without_time['Europe/London'] );
	}

	/**
	 * @return void
	 */
	public function test_get_timezone_list_entry_format_includes_utc_offset() {
		$list = Settings::get_timezone_list( 'EUROPE', 'no' );

		$this->assertStringContainsString( '(UTC', $list['Europe/London'] );
		$this->assertStringContainsString( 'Europe/London', $list['Europe/London'] );
	}
}
