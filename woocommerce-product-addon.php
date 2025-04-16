<?php
/*
 * Plugin Name: PPOM for WooCommerce
 * Plugin URI: https://themeisle.com/plugins/ppom-pro/
 * Description: PPOM (Personalized Product Meta Manager) plugin allow WooCommerce Store Admin to create unlimited input fields and files to attach with Product Pages.
 * Version: 33.0.11
 * Author: Themeisle
 * Text Domain: woocommerce-product-addon
 * Domain Path: /languages
 * Author URI: https://themeisle.com/
 * Requires PHP: 7.2
 *
 * WC requires at least: 6.5
 * WC tested up to: 8.0
 *
 * WordPress Available:  yes
 * Requires License:     no
 * Requires Plugins: woocommerce
 */

// @since 6.1
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'PPOM_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
define( 'PPOM_URL', untrailingslashit( plugin_dir_url( __FILE__ ) ) );
define( 'PPOM_WP_PLUGIN_DIR', untrailingslashit( plugin_dir_path( __DIR__ ) ) );
define( 'PPOM_BASENAME', basename( PPOM_WP_PLUGIN_DIR ) );
define( 'PPOM_PRODUCT_SLUG', PPOM_BASENAME );
define( 'PPOM_VERSION', '33.0.11' );
define( 'PPOM_DB_VERSION', '32.0.0' );
define( 'PPOM_PRODUCT_META_KEY', '_product_meta_id' );
define( 'PPOM_TABLE_META', 'nm_personalized' );
define( 'PPOM_UPLOAD_DIR_NAME', 'ppom_files' );
define( 'PPOM_GROUPS_COUNT_CACHE_KEY', 'ppom_groups_count_cache' );
define( 'PPOM_COMPATIBILITY_FEATURES', [
	'pro_cond_field_repeat' => true, // Compatibility for Conditional Field Repeater feature
	'pgfbdfm_wp_filter_param_fix' => true // Fix for the wrong params of the ppom_get_field_by_dataname__field_meta WP filter .
] );

require PPOM_PATH . '/vendor/autoload.php';

define( 'PPOM_UPGRADE_URL', 'https://themeisle.com/plugins/ppom-pro/upgrade/' );
define( 'PPOM_STORE_URL', 'https://store.themeisle.com' );
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
	load_plugin_textdomain( 'woocommerce-product-addon', false, basename( dirname( __FILE__ ) ) . '/languages' );
}

require_once PPOM_PATH . '/inc/functions.php';
require_once PPOM_PATH . '/inc/validation.php';
require_once PPOM_PATH . '/inc/deprecated.php';
require_once PPOM_PATH . '/inc/arrays.php';
require_once PPOM_PATH . '/inc/hooks.php';
require_once PPOM_PATH . '/inc/woocommerce.php';
require_once PPOM_PATH . '/inc/admin.php';
require_once PPOM_PATH . '/inc/files.php';
require_once PPOM_PATH . '/inc/nmInput.class.php';
require_once PPOM_PATH . '/inc/prices.php';

if( is_admin() ) {
	require_once PPOM_PATH . '/classes/freemium.class.php';
	PPOM_Freemium::get_instance();
}


/*
 ======= For now we are including class file, we will replace  =========== */
require_once PPOM_PATH . '/classes/attach-popup/container-item.class.php';
require_once PPOM_PATH . '/classes/attach-popup/container-view.class.php';
require_once PPOM_PATH . '/classes/attach-popup/select-component.class.php';

// include_once PPOM_PATH . "/classes/nm-framework.php";
require_once PPOM_PATH . '/classes/input.class.php';
require_once PPOM_PATH . '/classes/fields.class.php';
// include_once PPOM_PATH . "/classes/field.class.php";	// Fronend PPOM Fields
require_once PPOM_PATH . '/classes/ppom.class.php';
require_once PPOM_PATH . '/classes/plugin.class.php';
require_once PPOM_PATH . '/classes/scripts.class.php';
require_once PPOM_PATH . '/classes/frontend-scripts.class.php';
require_once PPOM_PATH . '/backend/settings-panel.class.php';
require_once PPOM_PATH . '/backend/options.php';
require_once PPOM_PATH . '/inc/rest.class.php';

// New Files Inlcude
require_once PPOM_PATH . '/classes/form.class.php';
require_once PPOM_PATH . '/classes/input-meta.class.php';
require_once PPOM_PATH . '/classes/legacy-meta.class.php';
require_once PPOM_PATH . '/classes/integrations/elementor/elementor.class.php';

if ( is_admin() ) {

	include_once PPOM_PATH . '/classes/admin.class.php';

	$ppom_admin    = new NM_PersonalizedProduct_Admin();
	$ppom_basename = plugin_basename( __FILE__ );
	add_filter( "plugin_action_links_{$ppom_basename}", 'ppom_settings_link', 10 );
	add_filter( "plugin_action_links_{$ppom_basename}", [ $ppom_admin, 'upgrade_to_pro_plugin_action' ], 10, 2 );
}

function PPOM() {
	return NM_PersonalizedProduct::get_instance();
}

add_filter(
	'themeisle_sdk_compatibilities/' . PPOM_BASENAME,
	function ( $compatibilities ) {
		$compatibilities['ppompro'] = [
			'basefile'  => defined( 'PPOM_PRO_PATH' ) ? PPOM_PRO_PATH . '/ppom.php' : '',
			'required'  => '25.0',
			'tested_up' => '26.0',
		];

		return $compatibilities;
	}
);
add_filter( 'woocommerce_product_addon_about_us_metadata', function () {
	return [
		'location' => 'ppom',
		'logo'     => PPOM_URL . '/images/logo.jpg',
	];
} );
add_filter('woocommerce_product_addon_float_widget_metadata', function(){
	return [
		'logo'                 => PPOM_URL . '/images/help.svg',
		'nice_name'            => 'PPOM',
		'primary_color'        => '#313350', // optional
		'pages'                => [ 'woocommerce_page_ppom' ], //pages where the float widget should be displayed
		'has_upgrade_menu'     => ! defined( 'PPOM_PRO_PATH' ),
		'upgrade_link'         => tsdk_utmify( tsdk_translate_link( PPOM_UPGRADE_URL ), 'float_widget' ),
		'documentation_link'   => 'https://rviv.ly/C1cmSQ',
		'premium_support_link' => defined( 'PPOM_PRO_PATH' ) ? tsdk_translate_link( tsdk_support_link( PPOM_PRO_PATH . '/ppom.php' ) ) : '',
		'feature_request_link' => tsdk_translate_link( 'https://store.themeisle.com/suggest-a-feature/' ),
	];
});
add_filter( 'woocommerce_product_addon_welcome_metadata', function () {
	return [
		'is_enabled' => ! defined( 'PPOM_PRO_PATH' ),
		'pro_name'   => 'PPOM PRO',
		'logo'       => PPOM_URL . '/images/logo.jpg',
		'cta_link'   => 'https://rviv.ly/fJhjZN'
	];
} );
add_filter(
	'woocommerce_product_addon_welcome_upsell_message',
	function() {
		return '<p>You\'ve been using <b>{product}</b> for 7 days now and we appreciate your loyalty! We also want to make sure you\'re getting the most out of our product. That\'s why we\'re offering you a special deal - upgrade to <b>{pro_product}</b> in the next 5 days and receive a discount of <b>up to 55%</b>. <a href="{cta_link}" target="_blank">Upgrade now</a> and unlock all the amazing features of <b>{pro_product}</b>!</p>';
	}
);
add_action( 'woocommerce_init', 'PPOM' );

add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
	}
);

register_activation_hook( __FILE__, array( 'NM_PersonalizedProduct', 'activate_plugin' ) );
register_deactivation_hook( __FILE__, array( 'NM_PersonalizedProduct', 'deactivate_plugin' ) );