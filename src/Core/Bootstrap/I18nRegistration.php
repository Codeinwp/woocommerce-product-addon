<?php
/**
 * Text domain loading on init.
 *
 * @package PPOM
 */

namespace PPOM\Core\Bootstrap;

use PPOM\Core\RegisterHooks;

/**
 * @since 33.0.19
 */
final class I18nRegistration implements RegisterHooks {

	/**
	 * @return void
	 */
	public function register(): void {
		add_action( 'init', array( self::class, 'load_textdomain' ) );
	}

	/**
	 * @return void
	 */
	public static function load_textdomain(): void {
		load_plugin_textdomain( 'woocommerce-product-addon', false, basename( PPOM_PATH ) . '/languages' );
	}
}
