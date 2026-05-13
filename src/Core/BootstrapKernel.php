<?php
/**
 * Orchestrates bootstrap-time registration and legacy runtime loading.
 *
 * @package PPOM
 */

namespace PPOM\Core;

use PPOM\Core\Bootstrap\HposCompatibilityRegistration;
use PPOM\Core\Bootstrap\I18nRegistration;
use PPOM\Core\Bootstrap\LegacyRuntimeLoader;
use PPOM\Core\Bootstrap\SdkCompatAndMetadataRegistration;
use PPOM\Core\Bootstrap\SdkProductsRegistration;
use PPOM\Core\Bootstrap\WooCommerceRuntimeRegistration;
use PPOM\Data\FieldGroupRepository;
use PPOM\Plugin;
use PPOM\Rest\RestBootstrap;

/**
 * @since 33.0.19
 */
final class BootstrapKernel {

	/**
	 * Run bootstrap: early hooks, legacy includes, admin wiring, lifecycle hooks.
	 *
	 * @param string $plugin_file Absolute main plugin file path.
	 * @param Plugin $plugin      Application instance.
	 * @return void
	 */
	public static function run( string $plugin_file, Plugin $plugin ): void {
		$early_modules = array(
			new SdkProductsRegistration( $plugin_file ),
			new I18nRegistration(),
		);

		foreach ( $early_modules as $module ) {
			$module->register();
		}

		LegacyRuntimeLoader::load();

		( new RestBootstrap() )->register();
		( new \PPOM\Admin\FieldModal\FieldModalRegistrar() )->register();

		$plugin->get_registry()->set( 'field_group.repository', FieldGroupRepository::instance() );

		if ( is_admin() ) {
			require_once PPOM_PATH . '/classes/freemium.class.php';
			\PPOM_Freemium::get_instance();
		}

		if ( is_admin() ) {
			include_once PPOM_PATH . '/classes/admin.class.php';

			$ppom_admin    = new \NM_PersonalizedProduct_Admin();
			$ppom_basename = plugin_basename( $plugin_file );
			add_filter( "plugin_action_links_{$ppom_basename}", 'ppom_settings_link', 10 );
			add_filter( "plugin_action_links_{$ppom_basename}", array( $ppom_admin, 'upgrade_to_pro_plugin_action' ), 10, 2 );
		}

		$post_load_modules = array(
			new SdkCompatAndMetadataRegistration(),
			new WooCommerceRuntimeRegistration(),
			new HposCompatibilityRegistration( $plugin_file ),
		);

		foreach ( $post_load_modules as $module ) {
			$module->register();
		}

		register_activation_hook( $plugin_file, array( 'NM_PersonalizedProduct', 'activate_plugin' ) );
		register_deactivation_hook( $plugin_file, array( 'NM_PersonalizedProduct', 'deactivate_plugin' ) );

		$plugin->get_registry()->set( 'plugin', $plugin );
	}
}
