<?php
/**
 * PPOM application entry and composition root.
 *
 * @package PPOM
 */

namespace PPOM;

use PPOM\Core\BootstrapKernel;
use PPOM\Core\ServiceRegistry;

/**
 * Boots the plugin shell and exposes the service registry.
 *
 * @since 33.0.19
 */
final class Plugin {

	/**
	 * Singleton application instance.
	 *
	 * @var self|null
	 */
	private static $instance;

	/**
	 * Registry of shared services.
	 *
	 * @var ServiceRegistry
	 */
	private $registry;

	/**
	 * Creates the registry for a new application instance.
	 */
	private function __construct() {
		$this->registry = new ServiceRegistry();
	}

	/**
	 * Boot the plugin from the main plugin file.
	 *
	 * @param string $plugin_file Absolute path to woocommerce-product-addon.php.
	 * @return void
	 */
	public static function boot( string $plugin_file ): void {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}

		BootstrapKernel::run( $plugin_file, self::$instance );
	}

	/**
	 * Returns the shared service registry.
	 *
	 * @return ServiceRegistry
	 */
	public function get_registry(): ServiceRegistry {
		return $this->registry;
	}

	/**
	 * Returns the booted application instance, if any.
	 *
	 * @return self|null
	 */
	public static function get_application(): ?self {
		return self::$instance;
	}
}
