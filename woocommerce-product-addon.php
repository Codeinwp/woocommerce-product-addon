<?php
/*
 * Plugin Name: PPOM for WooCommerce
 * Plugin URI: http://themeisle.com
 * Description: PPOM (Personalized Product Meta Manager) plugin allow WooCommerce Store Admin to create unlimited input fields and files to attach with Product Page
 * Version: 30.1.1
 * Author: Themeisle
 * Text Domain: ppom
 * Domain Path: /languages
 * Author URI: https://www.themeisle.com/
 *
 * WC requires at least: 3.0.0
 * WC tested up to: 5.3.0
 *
 * WordPress Available:  no
 * Requires License:     no
 */

// @since 6.1
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'PPOM_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
define( 'PPOM_URL', untrailingslashit( plugin_dir_url( __FILE__ ) ) );
define( 'PPOM_WP_PLUGIN_DIR', untrailingslashit( plugin_dir_path( __DIR__ ) ) );
define( 'PPOM_VERSION', '30.1.1' );
define( 'PPOM_DB_VERSION', '30.1.0' );
define( "PPOM_PRODUCT_META_KEY", '_product_meta_id' );
define( 'PPOM_TABLE_META', 'nm_personalized' );
define( 'PPOM_UPLOAD_DIR_NAME', 'ppom_files' );

require PPOM_PATH . '/vendor/autoload.php';

add_filter(
	'themeisle_sdk_products',
	function ( $products ) {
		$products[] = __FILE__;

		return $products;
	}
);

/*
 * plugin localization being initiated here
 */
add_action( 'init', 'ppom_i18n_setup' );
function ppom_i18n_setup() {
	load_plugin_textdomain( 'ppom', false, basename( dirname( __FILE__ ) ) . '/languages' );
}


include_once PPOM_PATH . "/inc/functions.php";
include_once PPOM_PATH . "/inc/validation.php";
include_once PPOM_PATH . "/inc/deprecated.php";
include_once PPOM_PATH . "/inc/arrays.php";
include_once PPOM_PATH . "/inc/hooks.php";
include_once PPOM_PATH . "/inc/woocommerce.php";
include_once PPOM_PATH . "/inc/admin.php";
include_once PPOM_PATH . "/inc/files.php";
include_once PPOM_PATH . "/inc/nmInput.class.php";
include_once PPOM_PATH . "/inc/prices.php";


/* ======= For now we are including class file, we will replace  =========== */
// include_once PPOM_PATH . "/classes/nm-framework.php";
include_once PPOM_PATH . "/classes/input.class.php";
include_once PPOM_PATH . "/classes/fields.class.php";
// include_once PPOM_PATH . "/classes/field.class.php";	// Fronend PPOM Fields
include_once PPOM_PATH . "/classes/ppom.class.php";
include_once PPOM_PATH . "/classes/plugin.class.php";
include_once PPOM_PATH . "/classes/scripts.class.php";
include_once PPOM_PATH . "/classes/frontend-scripts.class.php";
include_once PPOM_PATH . "/backend/settings-panel.class.php";
include_once PPOM_PATH . "/backend/options.php";
include_once PPOM_PATH . "/inc/rest.class.php";

// New Files Inlcude
include_once PPOM_PATH . "/classes/form.class.php";
include_once PPOM_PATH . "/classes/input-meta.class.php";
include_once PPOM_PATH . "/classes/legacy-meta.class.php";
include_once PPOM_PATH . "/classes/integrations/elementor/elementor.class.php";

if ( is_admin() ) {

	include_once PPOM_PATH . "/classes/admin.class.php";

	$ppom_admin    = new NM_PersonalizedProduct_Admin();
	$ppom_basename = plugin_basename( __FILE__ );
	add_filter( "plugin_action_links_{$ppom_basename}", 'ppom_settings_link', 10 );
}

function PPOM() {
	return NM_PersonalizedProduct::get_instance();
}

add_action( 'woocommerce_init', 'PPOM' );

register_activation_hook( __FILE__, array( 'NM_PersonalizedProduct', 'activate_plugin' ) );
register_deactivation_hook( __FILE__, array( 'NM_PersonalizedProduct', 'deactivate_plugin' ) );