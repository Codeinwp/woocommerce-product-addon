<?php
/**
 * Plugin Admin Settings
 *
 */

/* 
**========== Block direct access =========== 
*/
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*** Check if class exist ***/
if ( ! class_exists( 'PPOM_SettingsFramework' ) ) {
	return;
}

/**
 * Register Panel Fieds Against Panel ID
 * --------------------------------------
 **/
$core_settings = array(
	'ppom_general_section1'               => array(
		'type'  => 'section',
		'title' => __( 'Basic Settings', 'ppom' ),
	),
	'ppom_disable_bootstrap'              => array(
		'type'  => 'checkbox',
		'title' => __( 'Disable Bootstrap', 'ppom' ),
		'desc'  => __( 'Bootstrap JS is being loaded from CDN, it will disable if your site already loading it.', 'ppom' ),
		'label' => __( 'Yes', 'ppom' ),
	),
	'ppom_enable_legacy_inputs_rendering' => array(
		'type'  => 'checkbox',
		'title' => __( 'Legacy Inputs Rendering', 'ppom' ),
		'desc'  => __( "PPOM Version 22.0 is major update, if some issues occur you can revert back to old version by this.", 'ppom' ),
	),
	'ppom_new_conditions'                 => array(
		'type'  => 'checkbox',
		'title' => __( 'Legacy Conditions Script', 'ppom' ),
		'desc'  => __( 'If you found any issue in conditions you may enable this option.', 'ppom' ),
	),
	'ppom_legacy_price'                   => array(
		'type'      => 'checkbox',
		'title'     => __( 'Enable Legacy Price Calculations', 'ppom' ),
//		'reference' => array(
//			'ref_title' => __( 'See reference', 'ppom' ),
//			'ref_link'  => 'https://najeebmedia.com/blog/ppom-version-18-0-better-price-manipulation-currency-switcher/',
			// 'ref_video_title' => __( 'Quick Video', 'ppom' ),
			// 'ref_video_link'  => 'https://www.youtube.com/watch?v=0wCC3aLXdOw',
//		),
	),
	'ppom_permission_mfields'             => array(
		'type'        => 'select',
		'title'       => __( 'PPOM Permissions', 'ppom' ),
		'desc'        => __( 'You can set permissions here so PPOM fields can be managed by different roles', 'ppom' ),
		'default'     => 'administrator',
		'placeholder' => __( 'choose role', 'ppom' ),
		'options'     => ppom_get_all_editable_roles(),
		'style'       => 'multiselect'
	),
	'ppom_restricted_file_type'           => array(
		'type'    => 'text',
		'title'   => __( 'Restricted file types here', 'ppom' ),
		'default' => __( 'php,php4,php5,php6,php7,phtml,exe,shtml', 'ppom' ),
	),
	'ppom_general_section2'               => array(
		'type'  => 'section',
		'title' => __( 'Label Settings', 'ppom' ),
	),
	'ppom_label_option_total'             => array(
		'type'    => 'text',
		'title'   => __( 'Option Total Label inside Price Table', 'ppom' ),
		'default' => __( 'Option Total', 'ppom' ),
	),
	'ppom_label_product_price'            => array(
		'type'    => 'text',
		'title'   => __( 'Product Price Label inside Price Table', 'ppom' ),
		'default' => __( 'Product Price', 'ppom' ),
	),
	'ppom_label_total'                    => array(
		'type'    => 'text',
		'title'   => __( 'Total Label inside Price Table', 'ppom' ),
		'default' => __( 'Total', 'ppom' ),
	),
	'ppom_label_total_discount'           => array(
		'type'    => 'text',
		'title'   => __( 'Total Discount Label inside Price Table', 'ppom' ),
		'default' => __( 'Total Discount', 'ppom' ),
	),
	'ppom_label_option_total_suffex'      => array(
		'type'  => 'text',
		'title' => __( 'Option Total Suffix', 'ppom' ),
		'desc'  => __( 'E.g for Tax/Va info like. Vat included', 'ppom' ),
	),
);

/**
 * Register Panel Against Tab ID
 * -------------------------------
 **/
$panel_meta = array(
	'ppom_admin_core_settings' => array(
		'id'          => 'ppom_admin_core_settings',
		'title'       => 'General Settings',
		'desc'        => __( 'It will render the general settings.', 'ppom' ),
		'is_sabpanel' => true,
		'active'      => 'yes',
	),
);


/**
 * Register Main Tabs
 * --------------------------
 **/
$register_tabs = array(
	'ppom_general_tab' => array(
		'tab_id'  => 'ppom_general_tab',
		'title'   => 'PPOM',
		'icon'    => '',
		'classes' => array( 'active' ),
		'enable'  => true,
	),
);


/**
 * Register Settings Panel
 * --------------------------
 **/
PPOMSETTINGS()->register_tabs( $register_tabs )->register_panel( 'ppom_general_tab', $panel_meta );
PPOMSETTINGS()->register_setting( 'ppom_admin_core_settings', $core_settings );