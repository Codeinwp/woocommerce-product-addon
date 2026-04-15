<?php
/**
 * JSON-RPC style error payloads for chunked uploads.
 *
 * @package PPOM
 */

namespace PPOM\Files;

/**
 * @internal
 */
final class UploadErrors {

	public const OPEN_INPUT        = 'open_input';
	public const OPEN_OUTPUT       = 'open_output';
	public const MISSING_TEMP_FILE = 'missing_temp_file';
	public const OPEN_DIR          = 'open_dir';

	/**
	 * @param string $error_slug Error slug.
	 * @return string|false
	 */
	public static function get_message_response( $error_slug ) {
		$msg = array(
			self::OPEN_INPUT        => '{"jsonrpc" : "2.0", "error" : {"code": 101, "message": "Failed to open input stream."}, "id" : "id"}',
			self::OPEN_OUTPUT       => '{"jsonrpc" : "2.0", "error" : {"code": 102, "message": "Failed to open output stream."}, "id" : "id"}',
			self::MISSING_TEMP_FILE => '{"jsonrpc" : "2.0", "error" : {"code": 103, "message": "Failed to move uploaded file."}, "id" : "id"}',
			self::OPEN_DIR          => '{"jsonrpc" : "2.0", "error" : {"code": 100, "message": "Failed to open temp directory."}, "id" : "id"}',
		);

		return isset( $msg[ $error_slug ] ) ? $msg[ $error_slug ] : false;
	}
}
