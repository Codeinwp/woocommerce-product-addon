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

/**
 * Filter callback for woocommerce_add_to_cart_validation.
 *
 * @param bool      $passed       Validation state from earlier callbacks.
 * @param int       $product_id   Product ID.
 * @param int|float $qty          Requested quantity.
 * @param int       $variation_id Selected variation ID, or 0 for simple products.
 * @param array     $variations   Selected variation attributes.
 *
 * @return bool
 */
function ppom_woocommerce_validate_product( $passed, $product_id, $qty, $variation_id = 0, $variations = array() ) {
	return \PPOM\WooCommerce\Product\ProductHandler::validate_product( $passed, $product_id, $qty, $variation_id, $variations );
}

function ppom_woocommerce_ajax_validate() {
	\PPOM\WooCommerce\Product\ProductHandler::ajax_validate();
}

/**
 * Validates posted PPOM fields against the product field schema.
 *
 * @param int   $product_id   Product ID.
 * @param array $post_data    Posted request payload.
 * @param bool  $passed       Validation state from earlier checks.
 * @param int   $variation_id Selected variation ID, or 0 for simple products.
 *
 * @return bool
 */
function ppom_check_validation( $product_id, $post_data, $passed = true, $variation_id = 0 ) {
	return \PPOM\WooCommerce\Product\ProductHandler::check_validation( $product_id, $post_data, $passed, $variation_id );
}
