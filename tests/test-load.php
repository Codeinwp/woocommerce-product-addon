<?php
/**
 * Class Test_Loading
 *
 * @package ppom-pro
 */

class Test_Loading extends WP_UnitTestCase {
	/**
	 * Test Constants.
	 */
	public function testConstants() {
		$this->assertTrue( defined( 'PPOM_PATH' ) );
		$this->assertTrue( defined( 'PPOM_URL' ) );
		$this->assertTrue( defined( 'PPOM_WP_PLUGIN_DIR' ) );
		$this->assertTrue( defined( 'PPOM_VERSION' ) );
		$this->assertTrue( defined( 'PPOM_DB_VERSION' ) );
		$this->assertTrue( defined( 'PPOM_PRODUCT_META_KEY' ) );
		$this->assertTrue( defined( 'PPOM_TABLE_META' ) );
		$this->assertTrue( defined( 'PPOM_UPLOAD_DIR_NAME' ) );
	}


	/**
	 * Make sure Core is loaded.
	 *
	 * @return void
	 */
	public function testCoreLoaded() {
		$this->assertTrue( class_exists( 'NM_PersonalizedProduct', false ) );
	}
}