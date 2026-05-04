<?php
/**
 * Unit tests for PPOM\Hooks\Callbacks::register_wpml().
 *
 * Regression coverage for the case where a stored field row lacks a "title"
 * key (e.g. a Pro field type whose settings UI isn't loaded because Pro is
 * deactivated). The legacy implementation read $data['title'] unconditionally,
 * raising an "Undefined array key" warning and overwriting data_name with ''.
 *
 * @package ppom-pro
 */

use PPOM\Hooks\Callbacks;

/**
 * @covers \PPOM\Hooks\Callbacks::register_wpml
 */
class Test_Callbacks_Register_Wpml extends WP_UnitTestCase {

	/**
	 * Both keys present: data_name must not be regenerated from title.
	 *
	 * @return void
	 */
	public function test_keeps_existing_data_name_when_both_keys_present() {
		$result = Callbacks::register_wpml(
			array(
				array(
					'type'      => 'text',
					'title'     => 'Engraving Text',
					'data_name' => 'engraving_text',
				),
			),
			123
		);

		$this->assertSame( 'engraving_text', $result[0]['data_name'] );
		$this->assertSame( 'Engraving Text', $result[0]['title'] );
		$this->assertSame( 123, $result[0]['ppom_id'] );
	}

	/**
	 * Title present, data_name empty: data_name should be derived via sanitize_key().
	 *
	 * @return void
	 */
	public function test_derives_data_name_from_title_when_data_name_empty() {
		$result = Callbacks::register_wpml(
			array(
				array(
					'type'      => 'text',
					'title'     => 'Engraving Text!',
					'data_name' => '',
				),
			),
			0
		);

		// sanitize_key() lowercases and strips characters that aren't [a-z0-9_-];
		// spaces and "!" are dropped (no underscore substitution).
		$this->assertSame( 'engravingtext', $result[0]['data_name'] );
	}

	/**
	 * Title absent, data_name present: data_name preserved, no PHP warning.
	 *
	 * Note: phpunit.xml has convertWarningsToExceptions="true", so the legacy
	 * unguarded $data['title'] read would fail this test.
	 *
	 * @return void
	 */
	public function test_preserves_data_name_when_title_absent() {
		$result = Callbacks::register_wpml(
			array(
				array(
					'type'      => 'cropper',
					'data_name' => 'product_image',
				),
			),
			0
		);

		$this->assertSame( 'product_image', $result[0]['data_name'] );
		$this->assertArrayNotHasKey( 'title', $result[0] );
	}

	/**
	 * Both title and data_name absent: no warning, data_name not invented.
	 *
	 * Reproduces the bug path where a Pro-only field row round-tripped through
	 * the editor with Pro deactivated lost its title and data_name. The fix
	 * must leave data_name unset rather than overwriting with ''.
	 *
	 * @return void
	 */
	public function test_does_not_warn_when_title_and_data_name_both_absent() {
		$result = Callbacks::register_wpml(
			array(
				array(
					'type' => 'palettes',
				),
			),
			0
		);

		$this->assertArrayNotHasKey( 'title', $result[0] );
		$this->assertSame( 'palettes', $result[0]['type'] );
		$this->assertTrue(
			! isset( $result[0]['data_name'] ) || '' === $result[0]['data_name'],
			'data_name should remain unset (or stay empty) instead of being overwritten with a sanitized empty string.'
		);
	}

	/**
	 * Title present but empty string: data_name stays empty (sanitize_key('') === '').
	 *
	 * @return void
	 */
	public function test_does_not_derive_data_name_from_empty_title() {
		$result = Callbacks::register_wpml(
			array(
				array(
					'type'      => 'text',
					'title'     => '',
					'data_name' => '',
				),
			),
			0
		);

		$this->assertSame( '', $result[0]['data_name'] );
	}

	/**
	 * Multiple rows in one call: each is normalized independently and ppom_id
	 * is propagated to every row.
	 *
	 * @return void
	 */
	public function test_processes_multiple_rows_independently() {
		$result = Callbacks::register_wpml(
			array(
				array(
					'type'  => 'text',
					'title' => 'A Title',
				),
				array(
					'type'      => 'cropper',
					'data_name' => 'cropper_field',
				),
			),
			42
		);

		$this->assertSame( 'atitle', $result[0]['data_name'] );
		$this->assertSame( 'cropper_field', $result[1]['data_name'] );
		$this->assertSame( 42, $result[0]['ppom_id'] );
		$this->assertSame( 42, $result[1]['ppom_id'] );
	}
}
