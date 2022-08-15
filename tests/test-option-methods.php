<?php
/**
 * Class Test_Option_Methods
 *
 * @package ppom-pro
 */

class Test_Option_Methods extends WP_UnitTestCase {
	/**
	 * Make sure default arg of ppom_get_option function works well.
	 *
	 * @return void
	 */
	public function testPPOMGetOptionDefaultValue() {
		// default 'default' arg value is false.
		$this->assertFalse( ppom_get_option('non-exist-option-key' ) );

		$this->assertEmpty( ppom_get_option('non-exist-option-key', '' ) );
		$this->assertEquals( array(), ppom_get_option('non-exist-option-key', array() ) );
		$this->assertNull( ppom_get_option('non-exist-option-key', null ) );
		$this->assertFalse( ppom_get_option('non-exist-option-key', false ) );
	}

	/**
	 * Make sure return value of arg of ppom_get_option function works well.
	 *
	 * @return void
	 */
	public function testPPOMGetOptionValue() {
		update_option('sample-option', ['a'=>1, 'b'=>2]);

		$this->assertEquals( ['a'=>1, 'b'=>2], ppom_get_option('sample-option' ) );
		$this->assertEquals( ['a'=>1, 'b'=>2], ppom_get_option('sample-option', false ) );
	}

	/**
	 * Make sure default arg of PPOM_SettingsFramework::get_saved_settings function works well.
	 *
	 * @return void
	 */
	public function testGetSavedSettingsDefaultValue() {
		// default 'default' arg value is false.
		$this->assertNull( PPOM_SettingsFramework::get_saved_settings('non-exist-option-key' ) );

		$this->assertEmpty( PPOM_SettingsFramework::get_saved_settings('non-exist-option-key', '' ) );
		$this->assertEquals( array(), PPOM_SettingsFramework::get_saved_settings('non-exist-option-key', array() ) );
		$this->assertNull( PPOM_SettingsFramework::get_saved_settings('non-exist-option-key', null ) );
		$this->assertFalse( PPOM_SettingsFramework::get_saved_settings('non-exist-option-key', false ) );
	}

	/**
	 * Make sure return value of PPOM_SettingsFramework::get_saved_settings function works well.
	 *
	 * @return void
	 */
	public function testGetSavedSettings() {
		$reflector_settings_framework = new ReflectionClass( 'PPOM_SettingsFramework' );
		$save_key_prop = $reflector_settings_framework->getProperty('save_key');
		$save_key_prop->setAccessible(true);
		$save_key_prop->setValue('sample-option-group-984319821');

		update_option( 'sample-option-group-984319821', [
			'sample-option-key-20'=>4984
		] );

		$this->assertEquals( 4984, PPOM_SettingsFramework::get_saved_settings('sample-option-key-20' ) );
		$this->assertEquals( 4984, PPOM_SettingsFramework::get_saved_settings('sample-option-key-20', false ) );
		$this->assertFalse( PPOM_SettingsFramework::get_saved_settings('sample-option-key-non-exists', false ) );

		// check default bevavior of the 'default argument' of the PPOM_SettingsFramework::get_saved_settings method.
		$this->assertNull( PPOM_SettingsFramework::get_saved_settings('sample-option-key-non-exists' ) );
	}

	/**
	 * Make sure return value of PPOM_SettingsFramework::get_saved_settings function works well.
	 *
	 * @return void
	 */
	public function testGetSavedSettingsNotExistsGroup() {
		$reflector_settings_framework = new ReflectionClass( 'PPOM_SettingsFramework' );
		$save_key_prop = $reflector_settings_framework->getProperty('save_key');
		$save_key_prop->setAccessible(true);
		$save_key_prop->setValue('sample-option-group');

		$this->assertFalse( PPOM_SettingsFramework::get_saved_settings('sample-option-key-100-non-exists', false ) );

		// check default bevavior of the 'default argument' of the PPOM_SettingsFramework::get_saved_settings method.
		$this->assertNull( PPOM_SettingsFramework::get_saved_settings('sample-option-key-100-non-exists' ) );
		$this->assertNull( PPOM_SettingsFramework::get_saved_settings('sample-option-key-200-non-exists' ) );
	}
}