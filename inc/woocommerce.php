<?php
/**
 * WooCommerce integration: loads PPOM lifecycle by phase (product -> catalog -> cart -> order).
 *
 * @package PPOM
 * @subpackage WooCommerce
 *
 * @see inc/woocommerce/product.php
 * @see inc/woocommerce/catalog.php
 * @see inc/woocommerce/cart.php
 * @see inc/woocommerce/order.php
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not Allowed.' );
}

require_once __DIR__ . '/woocommerce/product.php';
require_once __DIR__ . '/woocommerce/catalog.php';
require_once __DIR__ . '/woocommerce/cart.php';
require_once __DIR__ . '/woocommerce/order.php';
