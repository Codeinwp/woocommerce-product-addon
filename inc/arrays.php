<?php
/**
 * Arrays containing settings/meta detail — compatibility wrappers.
 *
 * @package PPOM
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not Allowed.' );
}

/**
 * Get plugin meta
 */
function ppom_get_plugin_meta() {
	return \PPOM\Arrays\Settings::get_plugin_meta();
}

/**
 * Return cols for inputs
 */
function ppom_get_input_cols() {
	return \PPOM\Arrays\Settings::get_input_cols();
}

/**
 * Return visibility options for inputs
 */
function ppom_field_visibility_options() {
	return \PPOM\Arrays\Settings::field_visibility_options();
}

/**
 * Get timezone list
 *
 * @param string $selected_regions Regions.
 * @param string $show_time Show time flag.
 * @return array<string, string>
 */
function ppom_array_get_timezone_list( $selected_regions, $show_time ) {
	return \PPOM\Arrays\Settings::get_timezone_list( $selected_regions, $show_time );
}

/**
 * PPOM WC settings array
 *
 * @return array<int, array<string, mixed>>
 */
function ppom_array_settings() {
	return \PPOM\Arrays\Settings::settings();
}

/**
 * PPOM Scripts Vars
 * It only used for addon via this function "ppom_hooks_load_input_scripts"
 * cart-edit-addon
 * svg-addon
 *
 * @param \WC_Product $product Product.
 * @param array|null  $args Arguments.
 * @return array<string, mixed>
 */
function ppom_array_get_js_input_vars( $product, $args = null ) {
	return \PPOM\Arrays\Settings::get_js_input_vars( $product, $args );
}

/**
 * Showing Tax prefix
 *
 * @since 20.5
 * @return string|void
 */
function ppom_tax_label_display() {
	return \PPOM\Arrays\Settings::tax_label_display();
}

/**
 * Get PPOM inputs
 *
 * @return array<string, array<int, string>>
 */
function ppom_array_all_inputs() {
	return \PPOM\Arrays\Settings::all_inputs();
}

/**
 * Get all PPOM addons details
 *
 * @return array<int, array<string, mixed>>
 */
function ppom_array_get_addons_details() {
	return \PPOM\Arrays\Settings::get_addons_details();
}
