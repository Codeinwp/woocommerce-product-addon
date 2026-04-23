<?php
/**
 * Admin callbacks — compatibility wrappers.
 *
 * @package PPOM
 * @subpackage Admin
 */
function ppom_admin_show_product_meta( $columns ) {
	return \PPOM\Admin\Manager::show_product_meta( $columns );
}

function ppom_admin_product_meta_column( $column, $post_id ) {
	\PPOM\Admin\Manager::product_meta_column( $column, $post_id );
}

function ppom_admin_product_meta_metabox() {
	\PPOM\Admin\Manager::product_meta_metabox();
}

function ppom_meta_list( $post ) {
	\PPOM\Admin\Manager::meta_list( $post );
}

function ppom_admin_process_product_meta( $post_id ) {
	\PPOM\Admin\Manager::process_product_meta( $post_id );
}

function ppom_admin_show_notices() {
	\PPOM\Admin\Manager::show_notices();
}

function ppom_admin_save_form_meta() {
	\PPOM\Admin\Manager::save_form_meta();
}

function ppom_admin_update_form_meta() {
	\PPOM\Admin\Manager::update_form_meta();
}

function ppom_admin_update_ppom_meta_only( $ppom_id, $ppom_meta ) {
	\PPOM\Admin\Manager::update_ppom_meta_only( $ppom_id, $ppom_meta );
}

function ppom_admin_delete_meta() {
	\PPOM\Admin\Manager::delete_meta();
}

function ppom_admin_delete_selected_meta() {
	\PPOM\Admin\Manager::delete_selected_meta();
}

function ppom_admin_simplify_meta( $meta ) {
	return \PPOM\Admin\Manager::simplify_meta( $meta );
}

function ppom_admin_bar_menu() {
	\PPOM\Admin\Manager::bar_menu();
}

function ppom_add_black_friday_data( $configs ) {
	return \PPOM\Admin\Manager::add_black_friday_data( $configs );
}

add_filter( 'themeisle_sdk_blackfriday_data', 'ppom_add_black_friday_data' );
