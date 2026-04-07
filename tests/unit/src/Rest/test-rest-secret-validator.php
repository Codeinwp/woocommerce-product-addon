<?php
/**
 * Tests for PPOM\Rest\RestSecretValidator.
 *
 * @package ppom-pro
 */

use PPOM\Rest\RestSecretValidator;

/**
 * @covers \PPOM\Rest\RestSecretValidator
 */
class Test_Rest_Secret_Validator extends PPOM_Test_Case {

	/**
	 * @return void
	 */
	public function test_is_secret_key_valid_matches_configured_key() {
		$this->set_ppom_option( 'ppom_rest_secret_key', 'my-api-secret' );

		$validator = new RestSecretValidator();

		$this->assertTrue( $validator->is_secret_key_valid( 'my-api-secret' ) );
		$this->assertFalse( $validator->is_secret_key_valid( 'wrong' ) );
		$this->assertFalse( $validator->is_secret_key_valid( '' ) );
	}

	/**
	 * @return void
	 */
	public function test_is_secret_key_valid_trims_both_sides() {
		$this->set_ppom_option( 'ppom_rest_secret_key', "  spaced-key  \n" );

		$validator = new RestSecretValidator();

		$this->assertTrue( $validator->is_secret_key_valid( 'spaced-key' ) );
		$this->assertTrue( $validator->is_secret_key_valid( '  spaced-key  ' ) );
	}
}
