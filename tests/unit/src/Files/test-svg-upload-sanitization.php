<?php
/**
 * Regression tests for Codeinwp/ppom-pro#505 — SVG uploads through the File
 * upload field were always rejected, because wp_check_filetype_and_ext()
 * only compares extensions/mimes (it never sniffs content when the
 * destination file doesn't exist yet, which is the case at that point in
 * the chunked-upload flow) and SVG isn't in WordPress's default allowed
 * mime list. Once the extension is allowed, the endpoint has to validate
 * the actual bytes instead, since SVG — unlike jpg/pdf/zip — is markup a
 * browser will execute.
 *
 * @package ppom-pro
 */

use PPOM\Files\Handler;

/**
 * @covers \PPOM\Files\Handler::sanitize_svg_file
 */
class Test_Svg_Upload_Sanitization extends WP_UnitTestCase {

	/**
	 * @param string $file_path Path to the file to sanitize.
	 *
	 * @return bool
	 */
	private function sanitize( $file_path ) {

		$method = new ReflectionMethod( Handler::class, 'sanitize_svg_file' );
		$method->setAccessible( true );

		return $method->invoke( null, $file_path );
	}

	/**
	 * A benign SVG must still be accepted — this is the bug in #505.
	 */
	public function test_accepts_a_clean_svg() {

		$file = tempnam( sys_get_temp_dir(), 'svg' );
		file_put_contents( $file, '<svg xmlns="http://www.w3.org/2000/svg" width="10" height="10"><circle cx="5" cy="5" r="4"/></svg>' );

		$this->assertTrue( $this->sanitize( $file ) );

		$content = file_get_contents( $file );
		$this->assertStringContainsString( '<circle', $content );

		unlink( $file );
	}

	/**
	 * Allowing the extension through means the content is the only thing left
	 * guarding this endpoint, so a <script> tag must not survive.
	 */
	public function test_strips_script_tag() {

		$file = tempnam( sys_get_temp_dir(), 'svg' );
		file_put_contents( $file, '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script><rect width="10" height="10"/></svg>' );

		$this->assertTrue( $this->sanitize( $file ) );

		$content = file_get_contents( $file );
		$this->assertStringNotContainsString( '<script', $content );
		$this->assertStringContainsString( '<rect', $content );

		unlink( $file );
	}

	/**
	 * Event-handler attributes are as executable as a <script> tag.
	 */
	public function test_strips_event_handler_attribute() {

		$file = tempnam( sys_get_temp_dir(), 'svg' );
		file_put_contents( $file, '<svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)"><rect width="10" height="10"/></svg>' );

		$this->assertTrue( $this->sanitize( $file ) );

		$content = file_get_contents( $file );
		$this->assertStringNotContainsString( 'onload', $content );

		unlink( $file );
	}

	/**
	 * A javascript: URI is another route to script execution from a link/href.
	 */
	public function test_strips_javascript_uri() {

		$file = tempnam( sys_get_temp_dir(), 'svg' );
		file_put_contents( $file, '<svg xmlns="http://www.w3.org/2000/svg"><a xlink:href="javascript:alert(1)"><rect width="10" height="10"/></a></svg>' );

		$this->assertTrue( $this->sanitize( $file ) );

		$content = file_get_contents( $file );
		$this->assertStringNotContainsString( 'javascript:', $content );

		unlink( $file );
	}

	/**
	 * Renaming an arbitrary file to .svg must not be enough to pass, now that
	 * the extension alone is allowed through the mime allow-list.
	 */
	public function test_rejects_non_xml_content() {

		$file = tempnam( sys_get_temp_dir(), 'svg' );
		file_put_contents( $file, "\xFF\xD8\xFF\xE0not really xml" );

		$this->assertFalse( $this->sanitize( $file ) );

		unlink( $file );
	}

	/**
	 * Valid XML that isn't SVG (e.g. some other renamed XML document) must
	 * also be rejected — the root element has to be <svg>.
	 */
	public function test_rejects_xml_with_wrong_root_element() {

		$file = tempnam( sys_get_temp_dir(), 'svg' );
		file_put_contents( $file, '<html><body>hi</body></html>' );

		$this->assertFalse( $this->sanitize( $file ) );

		unlink( $file );
	}

	/**
	 * On PHP 8+, DOMDocument::loadXML('') throws a ValueError rather than
	 * returning false, so a zero-byte .svg has to be rejected before that
	 * call — otherwise the public upload endpoint would fatal instead of
	 * returning its normal invalid-file JSON response.
	 */
	public function test_rejects_empty_file() {

		$file = tempnam( sys_get_temp_dir(), 'svg' );
		file_put_contents( $file, '' );

		$this->assertFalse( $this->sanitize( $file ) );

		unlink( $file );
	}

	/**
	 * XML tag names are case-sensitive, so a denylist entry of "foreignobject"
	 * never matches the real element name and the embedded-HTML attack
	 * surface it exists to close survives untouched.
	 */
	public function test_strips_foreignobject_in_canonical_case() {

		$file = tempnam( sys_get_temp_dir(), 'svg' );
		file_put_contents( $file, '<svg xmlns="http://www.w3.org/2000/svg"><foreignObject><div xmlns="http://www.w3.org/1999/xhtml">hi</div></foreignObject><rect width="10" height="10"/></svg>' );

		$this->assertTrue( $this->sanitize( $file ) );

		$content = file_get_contents( $file );
		$this->assertStringNotContainsString( 'foreignObject', $content );
		$this->assertStringContainsString( '<rect', $content );

		unlink( $file );
	}

	/**
	 * Same case-sensitivity gap for <animateTransform>.
	 */
	public function test_strips_animatetransform_in_canonical_case() {

		$file = tempnam( sys_get_temp_dir(), 'svg' );
		file_put_contents( $file, '<svg xmlns="http://www.w3.org/2000/svg"><rect width="10" height="10"><animateTransform attributeName="transform" type="rotate"/></rect></svg>' );

		$this->assertTrue( $this->sanitize( $file ) );

		$content = file_get_contents( $file );
		$this->assertStringNotContainsString( 'animateTransform', $content );

		unlink( $file );
	}

	/**
	 * A browser strips tab/newline/CR from anywhere in a URL before reading
	 * its scheme, so "java&#x09;script:" (an embedded tab, not leading
	 * whitespace) still executes as javascript: once opened — even though a
	 * naive `^\s*javascript:` match against the raw decoded value misses it.
	 */
	public function test_strips_javascript_uri_obfuscated_with_a_tab() {

		$file = tempnam( sys_get_temp_dir(), 'svg' );
		file_put_contents( $file, "<svg xmlns=\"http://www.w3.org/2000/svg\"><a xlink:href=\"java\tscript:alert(1)\"><rect width=\"10\" height=\"10\"/></a></svg>" );

		$this->assertTrue( $this->sanitize( $file ) );

		$content = file_get_contents( $file );
		$this->assertStringNotContainsString( 'javascript:', $content );
		$this->assertStringNotContainsString( "java\tscript", $content );

		unlink( $file );
	}

	/**
	 * A DOCTYPE can declare an internal entity whose expansion is never an
	 * inspectable element node without LIBXML_NOENT, so the <script> denylist
	 * above never sees it — yet saveXML() writes the declaration and
	 * reference back unchanged, and a browser rendering the file expands and
	 * runs it. The whole file has to be rejected, not sanitized around.
	 */
	public function test_rejects_svg_with_doctype_entity_smuggling_script() {

		$file = tempnam( sys_get_temp_dir(), 'svg' );
		file_put_contents( $file, '<?xml version="1.0"?><!DOCTYPE svg [ <!ENTITY xss "<script>alert(1)</script>"> ]><svg xmlns="http://www.w3.org/2000/svg">&xss;<rect width="10" height="10"/></svg>' );

		$this->assertFalse( $this->sanitize( $file ) );

		unlink( $file );
	}

	/**
	 * Even a DOCTYPE with no custom entity is rejected — SVGs have no
	 * legitimate need for one, so there's no reason to parse further to
	 * check whether a given DOCTYPE happens to be harmless.
	 */
	public function test_rejects_svg_with_benign_doctype() {

		$file = tempnam( sys_get_temp_dir(), 'svg' );
		file_put_contents( $file, '<?xml version="1.0"?><!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd"><svg xmlns="http://www.w3.org/2000/svg"><rect width="10" height="10"/></svg>' );

		$this->assertFalse( $this->sanitize( $file ) );

		unlink( $file );
	}
}
