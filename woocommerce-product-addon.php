<?php
/**
 * Plugin Name: PPOM for WooCommerce
 * Plugin URI: https://themeisle.com/plugins/ppom-pro/
 * Description: PPOM (Personalized Product Option Manager) plugin allow WooCommerce Store Admin to create unlimited input fields and files to attach with Product Pages.
 * Version: 33.0.18
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
 *
 * Bootstraps PPOM and registers top-level plugin hooks.
 *
 * @package PPOM
 * @subpackage Bootstrap
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'PPOM_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
define( 'PPOM_URL', untrailingslashit( plugin_dir_url( __FILE__ ) ) );
define( 'PPOM_WP_PLUGIN_DIR', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
define( 'PPOM_BASENAME', basename( PPOM_WP_PLUGIN_DIR ) );
define( 'PPOM_PRODUCT_SLUG', PPOM_BASENAME );
define( 'PPOM_VERSION', '33.0.18' );
define( 'PPOM_DB_VERSION', '32.0.0' );
define( 'PPOM_PRODUCT_META_KEY', '_product_meta_id' );
define( 'PPOM_TABLE_META', 'nm_personalized' );
define( 'PPOM_UPLOAD_DIR_NAME', 'ppom_files' );
define( 'PPOM_GROUPS_COUNT_CACHE_KEY', 'ppom_groups_count_cache' );
define( 'PPOM_USE_REACT_FIELD_MODAL', true );
define(
	'PPOM_COMPATIBILITY_FEATURES',
	array(
		'pro_cond_field_repeat'       => true,
		'pgfbdfm_wp_filter_param_fix' => true,
	)
);

require PPOM_PATH . '/vendor/autoload.php';

define( 'PPOM_UPGRADE_URL', 'https://themeisle.com/plugins/ppom-pro/upgrade/' );
define( 'PPOM_STORE_URL', 'https://store.themeisle.com' );

\PPOM\Plugin::boot( __FILE__ );

// phpcs:disable WordPress.NamingConventions.ValidFunctionName.FunctionNameInvalid -- Established bootstrap accessor.
/**
 * Returns the PPOM runtime singleton.
 *
 * @return NM_PersonalizedProduct
 *
 * @see NM_PersonalizedProduct::get_instance()
 */
function PPOM() {
	return NM_PersonalizedProduct::get_instance();
}
// phpcs:enable WordPress.NamingConventions.ValidFunctionName.FunctionNameInvalid
