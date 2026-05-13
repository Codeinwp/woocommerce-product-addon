<?php
/**
 * Compares incoming REST requests against the configured PPOM secret key.
 *
 * @package PPOM
 * @subpackage REST
 */

namespace PPOM\Rest;

/**
 * Shared-secret check for PPOM REST write operations.
 *
 * @internal
 */
final class RestSecretValidator {

	/**
	 * Validates the shared PPOM REST secret key.
	 *
	 * @param string $secretkey Secret key submitted by the API client.
	 *
	 * @return bool
	 */
	public function is_secret_key_valid( $secretkey ) {

		$api_key = ppom_get_option( 'ppom_rest_secret_key', true );
		$api_str = is_string( $api_key ) ? $api_key : '';

		return trim( $api_str ) === trim( $secretkey );
	}
}
