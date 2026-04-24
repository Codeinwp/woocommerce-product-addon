<?php
/**
 * Declares WooCommerce HPOS (custom order tables) compatibility.
 *
 * @package PPOM
 */

namespace PPOM\Core\Bootstrap;

use PPOM\Core\RegisterHooks;

/**
 * @since 33.0.19
 */
final class HposCompatibilityRegistration implements RegisterHooks {

	/**
	 * Absolute path to the main plugin file.
	 *
	 * @var string
	 */
	private $plugin_file;

	/**
	 * @param string $plugin_file Main plugin file path.
	 */
	public function __construct( string $plugin_file ) {
		$this->plugin_file = $plugin_file;
	}

	/**
	 * @return void
	 */
	public function register(): void {
		add_action(
			'before_woocommerce_init',
			function () {
				if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
					\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', $this->plugin_file, true );
				}
			}
		);
	}
}
