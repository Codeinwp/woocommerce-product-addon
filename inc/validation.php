<?php
/**
 * Resolves PPOM sanitization rules and WooCommerce quantity limits — compatibility wrappers.
 *
 * @package PPOM
 * @subpackage Validation
 */

/**
 * Sanitizes field-builder content while preserving the HTML keys PPOM stores.
 *
 * @param string $content Raw field-builder content.
 * @return string
 */
function ppom_esc_html( $content ) {
	return \PPOM\Validation\Validator::esc_html( $content );
}

/**
 * Recursively sanitizes PPOM field definitions before they are stored.
 *
 * @param array $data Untrusted field-definition array.
 * @return array
 */
function ppom_sanitize_array_data( $data ) {
	return \PPOM\Validation\Validator::sanitize_array_data( $data );
}

/**
 * Returns field-definition keys that store sanitized HTML instead of plain text.
 *
 * @return array
 */
function ppom_fields_with_html() {
	return \PPOM\Validation\Validator::fields_with_html();
}

/**
 * Applies PPOM quantity limits to WooCommerce quantity input arguments.
 *
 * @param array       $data List of data to update.
 * @param \WC_Product $product Product object.
 * @return array
 */
function ppom_validation_product_limits( $data, $product ) {
	return \PPOM\Validation\Validator::validation_product_limits( $data, $product );
}

/**
 * Adds PPOM quantity limits to the variation data passed to the frontend.
 *
 * @param array                $data Available variation data.
 * @param \WC_Product          $product Product object.
 * @param \WC_Product_Variable $variation Variation object.
 * @return array
 */
function ppom_validation_variation_limits( $data, $product, $variation ) {
	return \PPOM\Validation\Validator::validation_variation_limits( $data, $product, $variation );
}

/**
 * Derives quantity limits from PPOM quantity and price-matrix fields.
 *
 * @param int $product_id   Parent product ID.
 * @param int $variation_id Variation ID when resolving variation-specific data.
 * @return array
 */
function ppom_get_product_limits( $product_id, $variation_id ) {
	return \PPOM\Validation\Validator::get_product_limits( $product_id, $variation_id );
}

/**
 * Allows CSS declarations containing `rgb()` and `rgba()` values.
 *
 * @param bool   $allow_css  Whether the CSS in the string is considered safe.
 * @param string $css_string The full CSS declaration.
 * @return bool
 */
function ppom_safecss_filter_attr( $allow_css, $css_string ) {
	return \PPOM\Validation\Validator::safecss_filter_attr( $allow_css, $css_string );
}

add_filter( 'safecss_filter_attr_allow_css', 'ppom_safecss_filter_attr', 10, 2 );
