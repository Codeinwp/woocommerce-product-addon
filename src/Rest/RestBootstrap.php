<?php
/**
 * Boots the PPOM REST API (v1) on rest_api_init.
 *
 * @package PPOM
 */

namespace PPOM\Rest;

use PPOM\Core\RegisterHooks;

/**
 * @since 33.0.19
 */
final class RestBootstrap implements RegisterHooks {

	/**
	 * @return void
	 */
	public function register(): void {
		new Routes();
	}
}
