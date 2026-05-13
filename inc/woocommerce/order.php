<?php
/**
 * Order item meta and files — compatibility wrappers.
 *
 * @package PPOM
 * @subpackage WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not Allowed.' );
}

function ppom_woocommerce_order_item_meta( $item, $cart_item_key, $values, $order ) {
	\PPOM\WooCommerce\Order\OrderHandler::order_item_meta( $item, $cart_item_key, $values, $order );
}

function ppom_woocommerce_order_key( $display_key, $meta, $item ) {
	return \PPOM\WooCommerce\Order\OrderHandler::order_key( $display_key, $meta, $item );
}

function ppom_woocommerce_order_value( $display_value, $meta = null, $item = null ) {
	return \PPOM\WooCommerce\Order\OrderHandler::order_value( $display_value, $meta, $item );
}

function ppom_woocommerce_hide_order_meta( $formatted_meta, $order_item ) {
	return \PPOM\WooCommerce\Order\OrderHandler::hide_order_meta( $formatted_meta, $order_item );
}

function ppom_woocommerce_rename_files( $order_id, $posted_data, $order ) {
	\PPOM\WooCommerce\Order\OrderHandler::rename_files( $order_id, $posted_data, $order );
}

function ppom_wc_order_again_compatibility( $cart_item_data, $item, $order ) {
	return \PPOM\WooCommerce\Order\OrderHandler::wc_order_again_compatibility( $cart_item_data, $item, $order );
}

function ppom_woocommerce_order_item_meta_html( $item_id, $item ) {
	\PPOM\WooCommerce\Order\OrderHandler::order_item_meta_html( $item_id, $item );
}

function ppom_wc_email_improvements_enabled() {
	return \PPOM\WooCommerce\Order\OrderHandler::wc_email_improvements_enabled();
}

function ppom_invoice_packing_slips_html( $html, $item, $args = array() ) {
	return \PPOM\WooCommerce\Order\OrderHandler::invoice_packing_slips_html( $html, $item, $args );
}
