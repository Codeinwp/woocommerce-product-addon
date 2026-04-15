<?php
/**
 * Catalog price and quantity args — compatibility wrappers.
 *
 * @package PPOM
 * @subpackage WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not Allowed.' );
}

function ppom_woocommerce_alter_price( $price, $product ) {
	return \PPOM\WooCommerce\Catalog\CatalogHandler::alter_price( $price, $product );
}

function ppom_hide_variation_price_html( $show, $parent, $variation ) {
	return \PPOM\WooCommerce\Catalog\CatalogHandler::hide_variation_price_html( $show, $parent, $variation );
}

function ppom_woocommerce_product_default_quantity( $args, $product ) {
	return \PPOM\WooCommerce\Catalog\CatalogHandler::product_default_quantity( $args, $product );
}

function ppom_woocommerce_set_min_quantity( $min_quantity, $product ) {
	return \PPOM\WooCommerce\Catalog\CatalogHandler::set_min_quantity( $min_quantity, $product );
}

function ppom_woocommerce_set_max_quantity( $max_quantity, $product ) {
	return \PPOM\WooCommerce\Catalog\CatalogHandler::set_max_quantity( $max_quantity, $product );
}

function ppom_woocommerce_set_quantity_step( $quantity_step, $product ) {
	return \PPOM\WooCommerce\Catalog\CatalogHandler::set_quantity_step( $quantity_step, $product );
}
