<?php
/**
 * WooCommerce init hook that boots the legacy main runtime singleton.
 *
 * @package PPOM
 */

namespace PPOM\Core\Bootstrap;

use PPOM\Core\RegisterHooks;

/**
 * @since 33.0.19
 */
final class WooCommerceRuntimeRegistration implements RegisterHooks {

	/**
	 * @return void
	 */
	public function register(): void {
		add_action(
			'woocommerce_init',
			static function (): void {
				PPOM();
			}
		);
	}
}
