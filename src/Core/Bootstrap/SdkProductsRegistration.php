<?php
/**
 * Registers the Themeisle SDK product entry for this plugin.
 *
 * @package PPOM
 */

namespace PPOM\Core\Bootstrap;

use PPOM\Core\RegisterHooks;

/**
 * @since 33.0.19
 */
final class SdkProductsRegistration implements RegisterHooks {

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
		add_filter(
			'themeisle_sdk_products',
			function ( $products ) {
				$products[] = $this->plugin_file;

				return $products;
			}
		);
	}
}
