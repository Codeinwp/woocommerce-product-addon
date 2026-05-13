<?php
/**
 * PPOM filter/action callbacks — compatibility wrappers.
 *
 * @package PPOM
 */
function ppom_hooks_save_cropped_image( $ppom_fields, $posted_data ) {
	return \PPOM\Hooks\Callbacks::save_cropped_image( $ppom_fields, $posted_data );
}

function ppom_hooks_convert_price( $option_price ) {
	return \PPOM\Hooks\Callbacks::convert_price( $option_price );
}

function ppom_hooks_convert_price_back( $price ) {
	return \PPOM\Hooks\Callbacks::convert_price_back( $price );
}

function ppom_hooks_format_order_value( $display_value, $meta, $item ) {
	return \PPOM\Hooks\Callbacks::format_order_value( $display_value, $meta, $item );
}

function ppom_hooks_set_attributes( $field_meta, $type ) {
	return \PPOM\Hooks\Callbacks::set_attributes( $field_meta, $type );
}

function ppom_hooks_load_input_scripts( $product, $ppom_id = null ) {
	return \PPOM\Hooks\Callbacks::load_input_scripts( $product, $ppom_id );
}

function ppom_hooks_input_args( $field_setting, $field_meta, $product ) {
	return \PPOM\Hooks\Callbacks::input_args( $field_setting, $field_meta, $product );
}

function ppom_hooks_checkbox_valided( $has_value, $posted_fields, $field ) {
	return \PPOM\Hooks\Callbacks::checkbox_valided( $has_value, $posted_fields, $field );
}

function ppom_hooks_color_to_text_type( $attr_value, $attr, $args ) {
	return \PPOM\Hooks\Callbacks::color_to_text_type( $attr_value, $attr, $args );
}

function ppom_hooks_show_option_price_pricematrix( $show_price, $meta ) {
	return \PPOM\Hooks\Callbacks::show_option_price_pricematrix( $show_price, $meta );
}

function ppom_hooks_register_wpml( $meta_data, $ppom_id ) {
	return \PPOM\Hooks\Callbacks::register_wpml( $meta_data, $ppom_id );
}

function ppom_hooks_input_wrapper_class( $input_wrapper_class, $field_meta ) {
	return \PPOM\Hooks\Callbacks::input_wrapper_class( $input_wrapper_class, $field_meta );
}

function ppom_hooks_input_wrapper_class_new( $input_wrapper_class, $field_meta ) {
	return \PPOM\Hooks\Callbacks::input_wrapper_class_new( $input_wrapper_class, $field_meta );
}

function ppom_hooks_input_main_wrapper_class( $wrapper_class, $classes_array, $field_meta ) {
	return \PPOM\Hooks\Callbacks::input_main_wrapper_class( $wrapper_class, $classes_array, $field_meta );
}

function ppom_hooks_convert_option_json_to_string( $row, $order ) {
	return \PPOM\Hooks\Callbacks::convert_option_json_to_string( $row, $order );
}

function ppom_hooks_update_cart_weight( $ppom_field_prices, $ppom_fields_post, $cart_items ) {
	return \PPOM\Hooks\Callbacks::update_cart_weight( $ppom_field_prices, $ppom_fields_post, $cart_items );
}

function ppom_hooks_dom_option_id( $option_id, $args ) {
	return \PPOM\Hooks\Callbacks::dom_option_id( $option_id, $args );
}

function ppom_hooks_hide_cart_quantity( $classes, $product ) {
	return \PPOM\Hooks\Callbacks::hide_cart_quantity( $classes, $product );
}

function ppom_hooks_weekly_cron_schedule( $schedules ) {
	return \PPOM\Hooks\Callbacks::weekly_cron_schedule( $schedules );
}

function ppom_hooks_set_option_operator( $operator, $price, $meta ) {
	return \PPOM\Hooks\Callbacks::set_option_operator( $operator, $price, $meta );
}

function ppom_hooks_render_shortcode( $attr ) {
	return \PPOM\Hooks\Callbacks::render_shortcode( $attr );
}

function ppom_hooks_redirect_to_cart_if_shortcode( $url ) {
	return \PPOM\Hooks\Callbacks::redirect_to_cart_if_shortcode( $url );
}

function ppom_hooks_check_theme_path( $full_path, $template_path, $vars ) {
	return \PPOM\Hooks\Callbacks::check_theme_path( $full_path, $template_path, $vars );
}

function ppom_hooks_remove_admin_notices() {
	return \PPOM\Hooks\Callbacks::remove_admin_notices();
}

function update_converted_option_keys( $new_option, $option_key, $option, $meta, $product ) {
	return \PPOM\Hooks\Callbacks::update_converted_option_keys( $new_option, $option_key, $option, $meta, $product );
}

function ppom_hooks_search_in_order( $search_fields ) {
	return \PPOM\Hooks\Callbacks::search_in_order( $search_fields );
}
