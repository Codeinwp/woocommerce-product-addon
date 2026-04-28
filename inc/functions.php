<?php
/**
 * Shared PPOM helpers — compatibility wrappers.
 *
 * @package PPOM
 * @subpackage Helpers
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not Allowed.' );
}
function ppom_direct_access_not_allowed( ...$args ) {
	return \PPOM\Support\Helpers::direct_access_not_allowed( ...$args );
}

function ppom_pa( ...$args ) {
	return \PPOM\Support\Helpers::pa( ...$args );
}

function ppom_get_field_colum( ...$args ) {
	return \PPOM\Support\Helpers::get_field_colum( ...$args );
}

function ppom_translation_options( ...$args ) {
	return \PPOM\Support\Helpers::translation_options( ...$args );
}

function ppom_get_product_id( ...$args ) {
	return \PPOM\Support\Helpers::get_product_id( ...$args );
}

function ppom_get_product_price( ...$args ) {
	return \PPOM\Support\Helpers::get_product_price( ...$args );
}

function ppom_get_product_regular_price( ...$args ) {
	return \PPOM\Support\Helpers::get_product_regular_price( ...$args );
}

function ppom_recursive_sanitization( ...$args ) {
	return \PPOM\Support\Helpers::recursive_sanitization( ...$args );
}

function ppom_make_meta_data( ...$args ) {
	return \PPOM\Support\Helpers::make_meta_data( ...$args );
}

function ppom_generate_cart_meta( ...$args ) {
	return \PPOM\Support\Helpers::generate_cart_meta( ...$args );
}

function ppom_meta_priced_options( ...$args ) {
	return \PPOM\Support\Helpers::meta_priced_options( ...$args );
}

function ppom_if_browser_is_ie( ...$args ) {
	return \PPOM\Support\Helpers::if_browser_is_ie( ...$args );
}

function ppom_get_editing_tools( ...$args ) {
	return \PPOM\Support\Helpers::get_editing_tools( ...$args );
}

function ppom_has_posted_field_value( ...$args ) {
	return \PPOM\Support\Helpers::has_posted_field_value( ...$args );
}

function ppom_is_aviary_installed( ...$args ) {
	return \PPOM\Support\Helpers::is_aviary_installed( ...$args );
}

function ppom_settings_link( ...$args ) {
	return \PPOM\Support\Helpers::settings_link( ...$args );
}

function ppom_get_field_meta_by_dataname( ...$args ) {
	return \PPOM\Support\Helpers::get_field_meta_by_dataname( ...$args );
}

function ppom_has_field_by_type( ...$args ) {
	return \PPOM\Support\Helpers::has_field_by_type( ...$args );
}

function ppom_load_template( ...$args ) {
	return \PPOM\Support\Helpers::load_template( ...$args );
}

function ppom_load_input_templates( ...$args ) {
	return \PPOM\Support\Helpers::load_input_templates( ...$args );
}

function ppom_load_file( ...$args ) {
	return \PPOM\Support\Helpers::load_file( ...$args );
}

function ppom_load_bootstrap_css( ...$args ) {
	return \PPOM\Support\Helpers::load_bootstrap_css( ...$args );
}

function ppom_load_fontawesome( ...$args ) {
	return \PPOM\Support\Helpers::load_fontawesome( ...$args );
}

function ppom_convert_options_to_key_val( ...$args ) {
	return \PPOM\Support\Helpers::convert_options_to_key_val( ...$args );
}

function ppom_generate_option_label( ...$args ) {
	return \PPOM\Support\Helpers::generate_option_label( ...$args );
}

function ppom_get_option_id( ...$args ) {
	return \PPOM\Support\Helpers::get_option_id( ...$args );
}

function ppom_get_price_including_tax( ...$args ) {
	return \PPOM\Support\Helpers::get_price_including_tax( ...$args );
}

function ppom_is_field_hidden_by_condition( ...$args ) {
	return \PPOM\Support\Helpers::is_field_hidden_by_condition( ...$args );
}

/**
 * Returns variation display rules for a parent product.
 *
 * @param mixed ...$args Forwarded arguments.
 * @return array<int, int[]>
 */
function ppom_get_variation_rule_map( ...$args ) {
	return \PPOM\Support\Helpers::get_variation_rule_map( ...$args );
}

/**
 * Returns the selected variation IDs for one PPOM group on one product.
 *
 * @param mixed ...$args Forwarded arguments.
 * @return int[]
 */
function ppom_get_variation_ids_for_group( ...$args ) {
	return \PPOM\Support\Helpers::get_variation_ids_for_group( ...$args );
}

/**
 * Determines whether a PPOM group is active for the selected variation.
 *
 * @param mixed ...$args Forwarded arguments.
 * @return bool
 */
function ppom_is_meta_group_active_for_variation( ...$args ) {
	return \PPOM\Support\Helpers::is_meta_group_active_for_variation( ...$args );
}

/**
 * Filters a posted PPOM payload to the groups active for a variation.
 *
 * @param mixed ...$args Forwarded arguments.
 * @return array<string, mixed>
 */
function ppom_filter_ppom_payload_by_active_variation( ...$args ) {
	return \PPOM\Support\Helpers::filter_ppom_payload_by_active_variation( ...$args );
}

function ppom_get_cart_item_max_quantity( ...$args ) {
	return \PPOM\Support\Helpers::get_cart_item_max_quantity( ...$args );
}

function ppom_extract_matrix_by_quantity( ...$args ) {
	return \PPOM\Support\Helpers::extract_matrix_by_quantity( ...$args );
}

function ppom_get_thumbs_size( ...$args ) {
	return \PPOM\Support\Helpers::get_thumbs_size( ...$args );
}

function ppom_get_filesize_in_kb( ...$args ) {
	return \PPOM\Support\Helpers::get_filesize_in_kb( ...$args );
}

function ppom_generate_html_for_files( ...$args ) {
	return \PPOM\Support\Helpers::generate_html_for_files( ...$args );
}

function ppom_generate_html_for_images( ...$args ) {
	return \PPOM\Support\Helpers::generate_html_for_images( ...$args );
}

function ppom_get_field_option_price( ...$args ) {
	return \PPOM\Support\Helpers::get_field_option_price( ...$args );
}

function ppom_get_field_option_price_by_id( ...$args ) {
	return \PPOM\Support\Helpers::get_field_option_price_by_id( ...$args );
}

function ppom_get_field_option_weight_by_id( ...$args ) {
	return \PPOM\Support\Helpers::get_field_option_weight_by_id( ...$args );
}

function ppom_field_has_stock( ...$args ) {
	return \PPOM\Support\Helpers::field_has_stock( ...$args );
}

function ppom_option_has_stock( ...$args ) {
	return \PPOM\Support\Helpers::option_has_stock( ...$args );
}

function ppom_pro_is_installed( ...$args ) {
	return \PPOM\Support\Helpers::pro_is_installed( ...$args );
}

function ppom_pro_is_valid_license( ...$args ) {
	return \PPOM\Support\Helpers::pro_is_valid_license( ...$args );
}

function ppom_is_api_enable( ...$args ) {
	return \PPOM\Support\Helpers::is_api_enable( ...$args );
}

function ppom_is_field_visible( ...$args ) {
	return \PPOM\Support\Helpers::is_field_visible( ...$args );
}

function ppom_get_current_user_role( ...$args ) {
	return \PPOM\Support\Helpers::get_current_user_role( ...$args );
}

function ppom_price( ...$args ) {
	return \PPOM\Support\Helpers::price( ...$args );
}

function ppom_get_price_matrix_chunk( ...$args ) {
	return \PPOM\Support\Helpers::get_price_matrix_chunk( ...$args );
}

function ppom_get_date_formats( ...$args ) {
	return \PPOM\Support\Helpers::get_date_formats( ...$args );
}

function ppom_is_price_attached_with_fields( ...$args ) {
	return \PPOM\Support\Helpers::is_price_attached_with_fields( ...$args );
}

function ppom_get_option( ...$args ) {
	return \PPOM\Support\Helpers::get_option( ...$args );
}

function ppom_get_version( ...$args ) {
	return \PPOM\Support\Helpers::get_version( ...$args );
}

function ppom_get_pro_version( ...$args ) {
	return \PPOM\Support\Helpers::get_pro_version( ...$args );
}

function ppom_is_mobile( ...$args ) {
	return \PPOM\Support\Helpers::is_mobile( ...$args );
}

function ppom_get_price_mode( ...$args ) {
	return \PPOM\Support\Helpers::get_price_mode( ...$args );
}

function ppom_is_client_validation_enabled( ...$args ) {
	return \PPOM\Support\Helpers::is_client_validation_enabled( ...$args );
}

function ppom_get_conditions_mode( ...$args ) {
	return \PPOM\Support\Helpers::get_conditions_mode( ...$args );
}

function ppom_get_price_table_calculation( ...$args ) {
	return \PPOM\Support\Helpers::get_price_table_calculation( ...$args );
}

function ppom_get_price_table_js_dependencies( ...$args ) {
	return \PPOM\Support\Helpers::get_price_table_js_dependencies( ...$args );
}

function ppom_get_price_table_location( ...$args ) {
	return \PPOM\Support\Helpers::get_price_table_location( ...$args );
}

function ppom_is_cart_quantity_updatable( ...$args ) {
	return \PPOM\Support\Helpers::is_cart_quantity_updatable( ...$args );
}

function ppom_reset_cart_quantity_to_one( ...$args ) {
	return \PPOM\Support\Helpers::reset_cart_quantity_to_one( ...$args );
}

function ppom_attach_fields_to_product( ...$args ) {
	return \PPOM\Support\Helpers::attach_fields_to_product( ...$args );
}

function ppom_get_confirmed_dir_thumbs( ...$args ) {
	return \PPOM\Support\Helpers::get_confirmed_dir_thumbs( ...$args );
}

function ppom_get_all_editable_roles( ...$args ) {
	return \PPOM\Support\Helpers::get_all_editable_roles( ...$args );
}

function ppom_security_role( ...$args ) {
	return \PPOM\Support\Helpers::security_role( ...$args );
}

function ppom_get_conditional_data_attributes( ...$args ) {
	return \PPOM\Support\Helpers::get_conditional_data_attributes( ...$args );
}

function ppom_is_field_addon( ...$args ) {
	return \PPOM\Support\Helpers::is_field_addon( ...$args );
}

function ppom_is_legacy_mode( ...$args ) {
	return \PPOM\Support\Helpers::is_legacy_mode( ...$args );
}

function ppom_settings_migrated( ...$args ) {
	return \PPOM\Support\Helpers::settings_migrated( ...$args );
}

function ppom_check_pro_compatibility( ...$args ) {
	return \PPOM\Support\Helpers::check_pro_compatibility( ...$args );
}

function ppom_is_legacy_user( ...$args ) {
	return \PPOM\Support\Helpers::is_legacy_user( ...$args );
}

function ppom_posted_field_max_min_value_validation( ...$args ) {
	return \PPOM\Support\Helpers::posted_field_max_min_value_validation( ...$args );
}

function ppom_get_image_name( ...$args ) {
	return \PPOM\Support\Helpers::get_image_name( ...$args );
}
