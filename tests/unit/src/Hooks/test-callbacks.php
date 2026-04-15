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
 */
class Test_Callbacks_Src extends WP_UnitTestCase {

	/**
	 * @var callable|null
	 */
	private $woocs_filter;

	/**
	 * @return void
	 */
	public function tearDown(): void {
		if ( null !== $this->woocs_filter ) {
			remove_filter( 'woocs_exchange_value', $this->woocs_filter );
			$this->woocs_filter = null;
		}
		parent::tearDown();
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
}
