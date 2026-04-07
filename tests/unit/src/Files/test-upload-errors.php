<?php
/**
 * Tests for PPOM\Files\UploadErrors.
 *
 * @package ppom-pro
 */

use PPOM\Files\UploadErrors;

/**
 * @covers \PPOM\Files\UploadErrors
 */
class Test_Files_Upload_Errors extends WP_UnitTestCase {

	/**
	 * @return void
	 */
	public function test_get_message_response_returns_json_for_known_slugs() {
		$this->assertSame(
			'{"jsonrpc" : "2.0", "error" : {"code": 101, "message": "Failed to open input stream."}, "id" : "id"}',
			UploadErrors::get_message_response( UploadErrors::OPEN_INPUT )
		);
		$this->assertSame(
			'{"jsonrpc" : "2.0", "error" : {"code": 102, "message": "Failed to open output stream."}, "id" : "id"}',
			UploadErrors::get_message_response( UploadErrors::OPEN_OUTPUT )
		);
		$this->assertSame(
			'{"jsonrpc" : "2.0", "error" : {"code": 103, "message": "Failed to move uploaded file."}, "id" : "id"}',
			UploadErrors::get_message_response( UploadErrors::MISSING_TEMP_FILE )
		);
		$this->assertSame(
			'{"jsonrpc" : "2.0", "error" : {"code": 100, "message": "Failed to open temp directory."}, "id" : "id"}',
			UploadErrors::get_message_response( UploadErrors::OPEN_DIR )
		);
	}

	/**
	 * @return void
	 */
	public function test_get_message_response_returns_false_for_unknown_slug() {
		$this->assertFalse( UploadErrors::get_message_response( 'not_a_real_error' ) );
		$this->assertFalse( UploadErrors::get_message_response( '' ) );
	}
}
