<?php
/**
 * Product page rendering and validation — compatibility wrappers.
 *
 * @package PPOM
 * @subpackage WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not Allowed.' );
}

function ppom_woocommerce_show_fields() {
	\PPOM\WooCommerce\Product\ProductHandler::show_fields();
}

function ppom_woocommerce_show_fields_on_product( $product_id, $args = null ) {
	\PPOM\WooCommerce\Product\ProductHandler::show_fields_on_product( $product_id, $args );
}

function ppom_woocommerce_inputs_template_base() {
	\PPOM\WooCommerce\Product\ProductHandler::inputs_template_base();
}

function ppom_woocommerce_template_base_inputs_rendering( $product_id, $args = null ) {
	\PPOM\WooCommerce\Product\ProductHandler::template_base_inputs_rendering( $product_id, $args );
}

function ppom_woocommerce_load_scripts() {
	\PPOM\WooCommerce\Product\ProductHandler::load_scripts();
}

function ppom_woocommerce_validate_product( $passed, $product_id, $qty ) {
	return \PPOM\WooCommerce\Product\ProductHandler::validate_product( $passed, $product_id, $qty );
}

function ppom_woocommerce_ajax_validate() {
	\PPOM\WooCommerce\Product\ProductHandler::ajax_validate();
}

function ppom_check_validation( $product_id, $post_data, $passed = true ) {
	return \PPOM\WooCommerce\Product\ProductHandler::check_validation( $product_id, $post_data, $passed );
}
