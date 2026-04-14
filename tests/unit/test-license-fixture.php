<?php
/**
 * License filter helpers on PPOM_Test_Case.
 *
 * @package ppom-pro
 */

require_once __DIR__ . '/class-ppom-test-case.php';

class Test_License_Fixture extends PPOM_Test_Case {

	/**
	 * @return void
	 */
	public function test_is_license_of_type_respects_fixture_filters() {
		$this->with_ppom_license_filters( 'invalid', 1 );
		$this->assertFalse( PPOM()->is_license_of_type( 'pro' ) );

		$this->with_ppom_license_filters( 'valid', 1 );
		$this->assertTrue( PPOM()->is_license_of_type( 'pro' ) );
		$this->assertFalse( PPOM()->is_license_of_type( 'plus' ) );

		$this->with_ppom_license_filters( 'valid', 2 );
		$this->assertTrue( PPOM()->is_license_of_type( 'plus' ) );
	}
}
