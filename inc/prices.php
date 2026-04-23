<?php
/**
 * Pricing engine — compatibility wrappers.
 *
 * @package PPOM
 */
function ppom_price_controller( ...$args ) {
	return \PPOM\Pricing\Engine::price_controller( ...$args );
}

function ppom_before_calculate_totals( ...$args ) {
	return \PPOM\Pricing\Engine::before_calculate_totals( ...$args );
}

function ppom_get_field_prices( $ppom_fields_post, $product_id, &$product_quantity, $variation_id, $item = null ) {
	return \PPOM\Pricing\Engine::get_field_prices( $ppom_fields_post, $product_id, $product_quantity, $variation_id, $item );
}

function ppom_generate_field_price( ...$args ) {
	return \PPOM\Pricing\Engine::generate_field_price( ...$args );
}

function ppom_price_get_addon_total( ...$args ) {
	return \PPOM\Pricing\Engine::price_get_addon_total( ...$args );
}

function ppom_price_get_cart_fee_total( ...$args ) {
	return \PPOM\Pricing\Engine::price_get_cart_fee_total( ...$args );
}

function ppom_price_get_total_quantities( ...$args ) {
	return \PPOM\Pricing\Engine::price_get_total_quantities( ...$args );
}

function ppom_price_get_total_bulkquantities( ...$args ) {
	return \PPOM\Pricing\Engine::price_get_total_bulkquantities( ...$args );
}

function ppom_price_get_total_fixedprice( ...$args ) {
	return \PPOM\Pricing\Engine::price_get_total_fixedprice( ...$args );
}

function ppom_price_get_total_measure( ...$args ) {
	return \PPOM\Pricing\Engine::price_get_total_measure( ...$args );
}

function ppom_price_get_product_base( ...$args ) {
	return \PPOM\Pricing\Engine::price_get_product_base( ...$args );
}

function ppom_price_matrix_chunk( ...$args ) {
	return \PPOM\Pricing\Engine::price_matrix_chunk( ...$args );
}

function ppom_price_bulkquantity_chunk( ...$args ) {
	return \PPOM\Pricing\Engine::price_bulkquantity_chunk( ...$args );
}

function ppom_price_fixedprice_chunk( ...$args ) {
	return \PPOM\Pricing\Engine::price_fixedprice_chunk( ...$args );
}

function ppom_price_cart_fee( ...$args ) {
	return \PPOM\Pricing\Engine::price_cart_fee( ...$args );
}

function ppom_price_is_matrix_found( ...$args ) {
	return \PPOM\Pricing\Engine::price_is_matrix_found( ...$args );
}

function ppom_parse_price_matrix( ...$args ) {
	return \PPOM\Pricing\Engine::parse_price_matrix( ...$args );
}

function ppom_is_field_has_price( ...$args ) {
	return \PPOM\Pricing\Engine::is_field_has_price( ...$args );
}

function ppom_price_has_discount_matrix( ...$args ) {
	return \PPOM\Pricing\Engine::price_has_discount_matrix( ...$args );
}

function ppom_get_amount_after_percentage( ...$args ) {
	return \PPOM\Pricing\Engine::get_amount_after_percentage( ...$args );
}

function ppom_price_check_price_matrix( ...$args ) {
	return \PPOM\Pricing\Engine::price_check_price_matrix( ...$args );
}

function ppom_option_price_handle_vat( ...$args ) {
	return \PPOM\Pricing\Engine::option_price_handle_vat( ...$args );
}

function ppom_wwp_product_cart_price( ...$args ) {
	return \PPOM\Pricing\Engine::wwp_product_cart_price( ...$args );
}

add_filter( 'ppom_option_price_vat', 'ppom_option_price_handle_vat', 9, 2 );
add_filter( 'ppom_product_price_on_cart', 'ppom_wwp_product_cart_price', 11, 2 );
