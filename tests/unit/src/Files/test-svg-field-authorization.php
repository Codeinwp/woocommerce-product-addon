<?php
/**
 * Regression tests for a Copilot review finding on PR #741 (Codeinwp/ppom-pro#505):
 * the mime/extension allow-list in Handler::upload_file() is shared by every
 * upload-capable field on a site, so adding "svg" to it for one field's sake
 * let *every* field accept an SVG — including a field still left at its
 * default "jpg,pdf,zip", or an image-only cropper field. Each field's own
 * "File types" setting is the actual authorization and has to be checked too.
 *
 * @package ppom-pro
 */

use PPOM\Files\Handler;

/**
 * @covers \PPOM\Files\Handler::field_allows_extension
 */
class Test_Svg_Field_Authorization extends WP_UnitTestCase {

	/**
	 * @param mixed  $file_meta Field definition.
	 * @param string $extension Extension to check.
	 *
	 * @return bool
	 */
	private function allows( $file_meta, $extension ) {

		$method = new ReflectionMethod( Handler::class, 'field_allows_extension' );
		$method->setAccessible( true );

		return $method->invoke( null, $file_meta, $extension );
	}

	/**
	 * A file field left at its default setting must not suddenly accept svg
	 * just because the shared mime allow-list now permits the extension.
	 */
	public function test_file_field_without_explicit_file_types_rejects_svg() {

		$file_meta = array( 'type' => 'file' );

		$this->assertFalse( $this->allows( $file_meta, 'svg' ) );
		$this->assertTrue( $this->allows( $file_meta, 'jpg' ) );
	}

	/**
	 * A cropper field only ever wants images; it must not accept svg either.
	 */
	public function test_cropper_field_without_explicit_file_types_rejects_svg() {

		$file_meta = array( 'type' => 'cropper' );

		$this->assertFalse( $this->allows( $file_meta, 'svg' ) );
		$this->assertTrue( $this->allows( $file_meta, 'png' ) );
	}

	/**
	 * A store owner who explicitly adds svg to a field's "File types" setting
	 * must still be able to accept it — that's the bug this PR fixes.
	 */
	public function test_field_with_svg_in_its_own_file_types_allows_it() {

		$file_meta = array(
			'type'       => 'file',
			'file_types' => 'jpg, svg, pdf',
		);

		$this->assertTrue( $this->allows( $file_meta, 'svg' ) );
	}

	/**
	 * Matching must be case- and whitespace-insensitive, since that's how the
	 * setting is free-typed by a store owner.
	 */
	public function test_matching_is_case_and_whitespace_insensitive() {

		$file_meta = array(
			'type'       => 'file',
			'file_types' => ' JPG , SVG ',
		);

		$this->assertTrue( $this->allows( $file_meta, 'SVG' ) );
	}
}
