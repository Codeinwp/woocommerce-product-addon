<?php
/**
 * PHPUnit bootstrap file
 *
 * @package ppom-pro
 */

$_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $_tests_dir ) {
	$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
	echo "Could not find $_tests_dir/includes/functions.php, have you run bin/install-wp-tests.sh ?" . PHP_EOL;
	exit( 1 );
}

// Give access to tests_add_filter() function.
require_once $_tests_dir . '/includes/functions.php';

/**
 * Ensure WooCommerce is active for plugin unit tests.
 *
 * @return array
 */
function _activate_woocommerce() {
	return array( 'woocommerce/woocommerce.php' );
}

/**
 * Registers theme
 */
function _register_module() {
	require_once dirname( dirname( dirname( __FILE__ ) ) ) . '/woocommerce-product-addon.php';
	include_once PPOM_PATH . '/classes/admin.class.php';

	$ppom_admin = new NM_PersonalizedProduct_Admin();
}

tests_add_filter( 'pre_option_active_plugins', '_activate_woocommerce' );
tests_add_filter( 'muplugins_loaded', '_register_module' );

// Start up the WP testing environment.
require $_tests_dir . '/includes/bootstrap.php';

require_once __DIR__ . '/class-ppom-test-case.php';
