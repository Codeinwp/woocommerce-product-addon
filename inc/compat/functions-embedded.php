<?php
/**
 * Embedded global helpers (WPML/WC shims). Loaded before support wrappers.
 *
 * @package PPOM
 */

/**
 * some WC functions wrapper
 * */


if ( ! function_exists( 'ppom_wc_add_notice' ) ) {
	function ppom_wc_add_notice( $string, $type = 'error' ) {

		global $woocommerce;
		if ( version_compare( $woocommerce->version, 2.1, '>=' ) ) {
			wc_add_notice( $string, $type );
			// Use new, updated functions
		} else {
			$woocommerce->add_error( $string );
		}
	}
}

if ( ! function_exists( 'ppom_add_order_item_meta' ) ) {

	function ppom_add_order_item_meta( $item_id, $key, $val ) {

		wc_add_order_item_meta( $item_id, $key, $val );
	}
}

/**
 * WPML
 * registering and translating strings input by users
 */
if ( ! function_exists( 'nm_wpml_register' ) ) {


	function nm_wpml_register( $field_value, $domain ) {

		if ( ! function_exists( 'icl_register_string' ) ) {
			return $field_value;
		}

		$field_name = $domain . ' - ' . sanitize_key( $field_value );
		// WMPL
		/**
		 * register strings for translation
		 * source: https://wpml.org/wpml-hook/wpml_register_single_string/
		 */

		do_action( 'wpml_register_single_string', $domain, $field_name, $field_value );

		// Polylang
		if ( function_exists( 'pll_register_string' ) ) {
			pll_register_string( $field_name, $field_value );
		}
	}
}

if ( ! function_exists( 'ppom_wpml_translate' ) ) {


	function ppom_wpml_translate( $field_value, $domain ) {

		// $field_value is array then return
		if ( is_array( $field_value ) ) {
			return $field_value;
		}

		$field_name  = $domain . ' - ' . sanitize_key( $field_value );
		$field_value = stripslashes( $field_value );

		// WMPL
		/**
		 * register strings for translation
		 * source: https://wpml.org/wpml-hook/wpml_translate_single_string/
		 */
		if ( has_filter( 'wpml_translate_single_string' ) ) {
			$field_value = apply_filters( 'wpml_translate_single_string', $field_value, $domain, $field_name );
		}


		// Polylang
		if ( function_exists( 'pll__' ) ) {
			$field_value = pll__( $field_value );
		}

		return $field_value;
	}
}

/**
 * returning order id
 *
 * @since 7.9
 */
if ( ! function_exists( 'nm_get_order_id' ) ) {
	function nm_get_order_id( $order ) {

		$class_name = get_class( $order );
		if ( $class_name != 'WC_Order' ) {
			return $order->ID;
		}

		if ( version_compare( WC_VERSION, '2.7', '<' ) ) {

			// vesion less then 2.7
			return $order->id;
		} else {

			return $order->get_id();
		}
	}
}
