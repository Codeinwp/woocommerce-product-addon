<?php
/**
 * Cart and session integration — compatibility wrappers.
 *
 * @package PPOM
 * @subpackage WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not Allowed.' );
}

/**
 * Filter callback for woocommerce_add_cart_item_data.
 *
 * @param array<string, mixed> $cart         Cart item data.
 * @param int                  $product_id   Product ID.
 * @param int                  $variation_id Selected variation ID, or 0 for simple products.
 * @param int                  $quantity     Requested quantity.
 *
 * @return array<string, mixed>
 */
function ppom_woocommerce_add_cart_item_data( $cart, $product_id, $variation_id = 0, $quantity = 1 ) {
	return \PPOM\WooCommerce\Cart\CartHandler::add_cart_item_data( $cart, $product_id, $variation_id, $quantity );
}

function ppom_woocommerce_update_cart_fees( $cart_items, $values ) {
	return \PPOM\WooCommerce\Cart\CartHandler::update_cart_fees( $cart_items, $values );
}

function ppom_calculate_totals_from_session( $cart ) {
	\PPOM\WooCommerce\Cart\CartHandler::calculate_totals_from_session( $cart );
}

function ppom_woocommerce_add_fixed_fee( $cart ) {
	\PPOM\WooCommerce\Cart\CartHandler::add_fixed_fee( $cart );
}

function ppom_woocommerce_mini_cart_fixed_fee() {
	\PPOM\WooCommerce\Cart\CartHandler::mini_cart_fixed_fee();
}

function ppom_woocommerce_add_item_meta( $item_meta, $cart_item ) {
	return \PPOM\WooCommerce\Cart\CartHandler::add_item_meta( $item_meta, $cart_item );
}

function ppom_woocommerce_add_to_cart_quantity( $quantity, $product_id ) {
	return \PPOM\WooCommerce\Cart\CartHandler::add_to_cart_quantity( $quantity, $product_id );
}

function ppom_woocommerce_control_cart_quantity_legacy( $quantity, $cart_item_key ) {
	return \PPOM\WooCommerce\Cart\CartHandler::control_cart_quantity_legacy( $quantity, $cart_item_key );
}

function ppom_woocommerce_control_cart_quantity( $quantity, $cart_item_key ) {
	return \PPOM\WooCommerce\Cart\CartHandler::control_cart_quantity( $quantity, $cart_item_key );
}

function ppom_woocommerce_item_subtotal( $item_subtotal, $cart_item, $cart_item_key ) {
	return \PPOM\WooCommerce\Cart\CartHandler::item_subtotal( $item_subtotal, $cart_item, $cart_item_key );
}

function ppom_woocommerce_control_checkout_quantity( $quantity, $cart_item, $cart_item_key ) {
	return \PPOM\WooCommerce\Cart\CartHandler::control_checkout_quantity( $quantity, $cart_item, $cart_item_key );
}

function ppom_woocommerce_control_oder_item_quantity( $quantity, $item ) {
	return \PPOM\WooCommerce\Cart\CartHandler::control_oder_item_quantity( $quantity, $item );
}

function ppom_woocommerce_control_email_item_quantity( $quantity, $item ) {
	return \PPOM\WooCommerce\Cart\CartHandler::control_email_item_quantity( $quantity, $item );
}

function ppom_woocommerce_control_order_item_quantity( $quantity, $item ) {
	return \PPOM\WooCommerce\Cart\CartHandler::control_order_item_quantity( $quantity, $item );
}

function ppom_woocommerce_cart_update_validate( $cart_validated, $cart_item_key, $values, $quantity ) {
	return \PPOM\WooCommerce\Cart\CartHandler::cart_update_validate( $cart_validated, $cart_item_key, $values, $quantity );
}
