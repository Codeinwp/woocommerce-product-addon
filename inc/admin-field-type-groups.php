<?php
/**
 * Admin field-type picker groups (legacy modal + React field modal).
 *
 * @package PPOM
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * License tier and status for Pro-locked field types in the admin picker.
 *
 * @return array{plan_category: int, license_status: string, show_upsell: bool}
 */
function ppom_get_admin_field_modal_license_context() {
	$license_raw      = get_option( 'ppom_pro_license_data', array() );
	$plan_category    = NM_PersonalizedProduct::LICENSE_PLAN_FREE;
	$is_pro_installed = function_exists( 'ppom_pro_is_installed' ) && ppom_pro_is_installed();
	$license_status   = 'invalid';

	if ( $is_pro_installed && is_object( $license_raw ) && ! empty( $license_raw->license ) ) {
		$license_status = (string) $license_raw->license;
	}

	if ( $is_pro_installed && is_object( $license_raw ) && isset( $license_raw->plan ) && is_numeric( $license_raw->plan ) ) {
		$plan_category = NM_PersonalizedProduct::get_license_category( (int) $license_raw->plan );
	}

	return array(
		'plan_category'  => (int) $plan_category,
		'license_status' => $license_status,
		'show_upsell'    => 'valid' !== $license_status,
	);
}

/**
 * Upsell payload for React modal (mirrors legacy sidebar when license is not valid).
 *
 * @return array<string, mixed>|null
 */
function ppom_get_admin_field_modal_upsell_for_rest() {
	$ctx = ppom_get_admin_field_modal_license_context();
	if ( ! $ctx['show_upsell'] ) {
		return null;
	}

	$url = defined( 'PPOM_UPGRADE_URL' ) ? PPOM_UPGRADE_URL : '';
	if ( $url && function_exists( 'tsdk_translate_link' ) && function_exists( 'tsdk_utmify' ) ) {
		$url = tsdk_utmify( tsdk_translate_link( PPOM_UPGRADE_URL ), 'react-field-modal', 'ppompage' );
	}

	return array(
		'title'     => __( 'Unlock all Features!', 'woocommerce-product-addon' ),
		'intro'     => __( 'Upgrade to the Pro plan to unlock all features and enhance your product fields management capabilities:', 'woocommerce-product-addon' ),
		'features'  => array(
			__( 'Unlock 30+ input fields', 'woocommerce-product-addon' ),
			__( 'Meta Fields Repeater', 'woocommerce-product-addon' ),
			__( 'Cart Edit', 'woocommerce-product-addon' ),
			__( 'Quantities Pack', 'woocommerce-product-addon' ),
		),
		'cta_label' => __( 'Get started!', 'woocommerce-product-addon' ),
		'cta_url'   => esc_url_raw( $url ),
	);
}

/**
 * Field type groups for the admin “Select field type” UI (legacy + REST).
 *
 * @return array<string, array{label: string, fields: array<int, array<string, mixed>>}>
 */
function ppom_get_admin_field_type_groups() {
	$fields_groups = array(
		'text-group'     => array(
			'label'  => __( 'Text', 'woocommerce-product-addon' ),
			'fields' => array(
				array(
					'slug'        => 'text',
					'title'       => __( 'Text Input', 'woocommerce-product-addon' ),
					'description' => __( 'Simple text field', 'woocommerce-product-addon' ),
					'icon'        => 'fa-pencil-square-o',
				),
				array(
					'slug'        => 'textarea',
					'title'       => __( 'Textarea Input', 'woocommerce-product-addon' ),
					'description' => __( 'Simple area field', 'woocommerce-product-addon' ),
					'icon'        => 'fa-file-text-o',
				),
				array(
					'slug'        => 'email',
					'title'       => __( 'Email Input', 'woocommerce-product-addon' ),
					'description' => __( 'Simple email field', 'woocommerce-product-addon' ),
					'icon'        => 'fa-user-plus',
				),
				array(
					'slug'        => 'number',
					'title'       => __( 'Number', 'woocommerce-product-addon' ),
					'description' => __( 'Number Input', 'woocommerce-product-addon' ),
					'icon'        => 'fa-hashtag',
				),
				array(
					'slug'        => 'hidden',
					'title'       => __( 'Hidden Input', 'woocommerce-product-addon' ),
					'description' => __( 'Simple hidden field', 'woocommerce-product-addon' ),
					'icon'        => 'fa-hashtag',
				),
				array(
					'slug'        => 'phone',
					'title'       => __( 'Phone Input', 'woocommerce-product-addon' ),
					'description' => __( 'Simple Phone field', 'woocommerce-product-addon' ),
					'plan'        => NM_PersonalizedProduct::LICENSE_PLAN_1,
					'icon'        => 'fa-check',
				),
			),
		),
		'choices'        => array(
			'label'  => __( 'Choices', 'woocommerce-product-addon' ),
			'fields' => array(
				array(
					'slug'        => 'checkbox',
					'title'       => __( 'Checkbox', 'woocommerce-product-addon' ),
					'description' => __( 'Checkbox Input', 'woocommerce-product-addon' ),
					'icon'        => 'fa-check-square-o',
				),
				array(
					'slug'        => 'radio',
					'title'       => __( 'Radio', 'woocommerce-product-addon' ),
					'description' => __( 'Radio Input', 'woocommerce-product-addon' ),
					'icon'        => 'fa-dot-circle-o',
				),
				array(
					'slug'        => 'select',
					'title'       => __( 'Select', 'woocommerce-product-addon' ),
					'description' => __( 'Select Input', 'woocommerce-product-addon' ),
					'icon'        => 'fa-check',
				),
				array(
					'slug'        => 'switcher',
					'title'       => __( 'Radio Switcher', 'woocommerce-product-addon' ),
					'description' => __( 'Radio button switcher', 'woocommerce-product-addon' ),
					'plan'        => NM_PersonalizedProduct::LICENSE_PLAN_1,
					'icon'        => 'fa-dot-circle-o',
				),
				array(
					'slug'        => 'superlist',
					'title'       => __( 'Super List', 'woocommerce-product-addon' ),
					'description' => __( 'Advanced list input', 'woocommerce-product-addon' ),
					'plan'        => NM_PersonalizedProduct::LICENSE_PLAN_1,
					'icon'        => 'fa-check',
				),
				array(
					'slug'        => 'chained',
					'title'       => __( 'Chained Input', 'woocommerce-product-addon' ),
					'description' => __( 'Linked dropdown selections where choices in one field depend on previous selections.', 'woocommerce-product-addon' ),
					'plan'        => NM_PersonalizedProduct::LICENSE_PLAN_1,
					'icon'        => 'fa-check',
				),
			),
		),
		'visual-group'   => array(
			'label'  => __( 'Media', 'woocommerce-product-addon' ),
			'fields' => array(
				array(
					'slug'        => 'file',
					'title'       => __( 'File Input', 'woocommerce-product-addon' ),
					'description' => __( 'File upload field', 'woocommerce-product-addon' ),
					'plan'        => NM_PersonalizedProduct::LICENSE_PLAN_1,
					'icon'        => 'fa-file',
				),
				array(
					'slug'        => 'emojis',
					'title'       => __( 'Emojis', 'woocommerce-product-addon' ),
					'description' => __( 'Emoji picker for input fields', 'woocommerce-product-addon' ),
					'plan'        => NM_PersonalizedProduct::LICENSE_PLAN_1,
					'icon'        => 'fa-user-plus',
				),
				array(
					'slug'        => 'conditional_meta',
					'title'       => __( 'Conditional Images', 'woocommerce-product-addon' ),
					'description' => __( 'Images that change based on input conditions', 'woocommerce-product-addon' ),
					'plan'        => NM_PersonalizedProduct::LICENSE_PLAN_1,
					'icon'        => 'fa-picture-o',
				),
				array(
					'slug'        => 'image',
					'title'       => __( 'Images', 'woocommerce-product-addon' ),
					'description' => __( 'Select images.', 'woocommerce-product-addon' ),
					'plan'        => NM_PersonalizedProduct::LICENSE_PLAN_1,
					'icon'        => 'fa-picture-o',
				),
				array(
					'slug'        => 'imageselect',
					'title'       => __( 'Image DropDown', 'woocommerce-product-addon' ),
					'description' => __( 'Dropdown with image selections', 'woocommerce-product-addon' ),
					'plan'        => NM_PersonalizedProduct::LICENSE_PLAN_1,
					'icon'        => 'fa-file-image-o',
				),
				array(
					'slug'        => 'audio',
					'title'       => __( 'Audio / Video', 'woocommerce-product-addon' ),
					'description' => __( 'Audio and video input field', 'woocommerce-product-addon' ),
					'plan'        => NM_PersonalizedProduct::LICENSE_PLAN_1,
					'icon'        => 'fa-file-video-o',
				),
				array(
					'slug'        => 'cropper',
					'title'       => __( 'Image Cropper', 'woocommerce-product-addon' ),
					'description' => __( 'Image cropping tool', 'woocommerce-product-addon' ),
					'plan'        => NM_PersonalizedProduct::LICENSE_PLAN_1,
					'icon'        => 'fa-crop',
				),
				array(
					'slug'        => 'texter',
					'title'       => __( 'Personalization Preview', 'woocommerce-product-addon' ),
					'description' => __( 'Preview for personalized products', 'woocommerce-product-addon' ),
					'plan'        => NM_PersonalizedProduct::LICENSE_PLAN_1,
					'icon'        => 'fa-keyboard-o',
				),
			),
		),
		'advanced-group' => array(
			'label'  => __( 'Special Format', 'woocommerce-product-addon' ),
			'fields' => array(
				array(
					'slug'        => 'date',
					'title'       => __( 'Date', 'woocommerce-product-addon' ),
					'description' => __( 'Date input field', 'woocommerce-product-addon' ),
					'icon'        => 'fa-calendar',
				),
				array(
					'slug'        => 'timezone',
					'title'       => __( 'Timezone Input', 'woocommerce-product-addon' ),
					'description' => __( 'Timezone selector for input fields', 'woocommerce-product-addon' ),
					'plan'        => NM_PersonalizedProduct::LICENSE_PLAN_1,
					'icon'        => 'fa-clock-o',
				),
				array(
					'slug'        => 'daterange',
					'title'       => __( 'DateRange Input', 'woocommerce-product-addon' ),
					'description' => __( 'Date range input field', 'woocommerce-product-addon' ),
					'plan'        => NM_PersonalizedProduct::LICENSE_PLAN_1,
					'icon'        => 'fa-table',
				),
				array(
					'slug'        => 'section',
					'title'       => __( 'HTML', 'woocommerce-product-addon' ),
					'description' => __( 'Custom HTML input field', 'woocommerce-product-addon' ),
					'plan'        => NM_PersonalizedProduct::LICENSE_PLAN_1,
					'icon'        => 'fa-code',
				),
				array(
					'slug'        => 'color',
					'title'       => __( 'Color Picker', 'woocommerce-product-addon' ),
					'description' => __( 'Color picker input field', 'woocommerce-product-addon' ),
					'plan'        => NM_PersonalizedProduct::LICENSE_PLAN_1,
					'icon'        => 'fa-modx',
				),
				array(
					'slug'        => 'palettes',
					'title'       => __( 'Color Palettes', 'woocommerce-product-addon' ),
					'description' => __( 'Color palette selection', 'woocommerce-product-addon' ),
					'plan'        => NM_PersonalizedProduct::LICENSE_PLAN_1,
					'icon'        => 'fa-user-plus',
				),
				array(
					'slug'        => 'fonts',
					'title'       => __( 'Fonts Picker', 'woocommerce-product-addon' ),
					'description' => __( 'Font selection tool', 'woocommerce-product-addon' ),
					'plan'        => NM_PersonalizedProduct::LICENSE_PLAN_1,
					'icon'        => 'fa-font',
				),
				array(
					'slug'        => 'domain',
					'title'       => __( 'Domain', 'woocommerce-product-addon' ),
					'description' => __( 'Domain input field', 'woocommerce-product-addon' ),
					'plan'        => NM_PersonalizedProduct::LICENSE_PLAN_3,
					'icon'        => 'fa-server',
				),
				array(
					'slug'        => 'textcounter',
					'title'       => __( 'Text Counter', 'woocommerce-product-addon' ),
					'description' => __( 'Character count for input fields', 'woocommerce-product-addon' ),
					'plan'        => NM_PersonalizedProduct::LICENSE_PLAN_1,
					'icon'        => 'fa-comments-o',
				),
				array(
					'slug'        => 'collapse',
					'title'       => __( 'Collapse', 'woocommerce-product-addon' ),
					'description' => __( 'Group and toggle other input fields within a collapsible section, helping to organize long forms more efficiently.', 'woocommerce-product-addon' ),
					'plan'        => NM_PersonalizedProduct::LICENSE_PLAN_1,
					'icon'        => 'fa-money',
				),
				array(
					'slug'        => 'divider',
					'title'       => __( 'Divider', 'woocommerce-product-addon' ),
					'description' => __( 'Add a visual separator.', 'woocommerce-product-addon' ),
					'plan'        => NM_PersonalizedProduct::LICENSE_PLAN_1,
					'icon'        => 'fa-pencil-square-o',
				),
			),
		),
		'pricing-group'  => array(
			'label'  => __( 'Pricing & Quantity', 'woocommerce-product-addon' ),
			'fields' => array(
				array(
					'slug'        => 'fixedprice',
					'title'       => __( 'Fixed Price', 'woocommerce-product-addon' ),
					'description' => __( 'Fixed pricing options', 'woocommerce-product-addon' ),
					'plan'        => NM_PersonalizedProduct::LICENSE_PLAN_1,
					'icon'        => 'fa-money',
				),
				array(
					'slug'        => 'quantities',
					'title'       => __( 'Variation Quantity ', 'woocommerce-product-addon' ),
					'description' => __( 'Quantity selection with options', 'woocommerce-product-addon' ),
					'plan'        => NM_PersonalizedProduct::LICENSE_PLAN_2,
					'icon'        => 'fa-list-ol',
				),
				array(
					'slug'        => 'pricematrix',
					'title'       => __( 'Price Matrix', 'woocommerce-product-addon' ),
					'description' => __( 'Matrix-based pricing', 'woocommerce-product-addon' ),
					'plan'        => NM_PersonalizedProduct::LICENSE_PLAN_1,
					'icon'        => 'fa-usd',
				),
				array(
					'slug'        => 'qtypack',
					'title'       => __( 'Quantities Pack', 'woocommerce-product-addon' ),
					'description' => __( 'Pack-based quantity options', 'woocommerce-product-addon' ),
					'plan'        => NM_PersonalizedProduct::LICENSE_PLAN_1,
					'icon'        => 'fa-list-alt',
				),
				array(
					'slug'        => 'vqmatrix',
					'title'       => __( 'Variation Matrix', 'woocommerce-product-addon' ),
					'description' => __( 'Quantity selector for variations', 'woocommerce-product-addon' ),
					'plan'        => NM_PersonalizedProduct::LICENSE_PLAN_3,
					'icon'        => 'fa-list-ol',
				),
				array(
					'slug'        => 'measure',
					'title'       => __( 'Measure Input', 'woocommerce-product-addon' ),
					'description' => __( 'Measurement input field', 'woocommerce-product-addon' ),
					'plan'        => NM_PersonalizedProduct::LICENSE_PLAN_1,
					'icon'        => 'fa-building-o',
				),
				array(
					'slug'        => 'quantityoption',
					'title'       => __( 'Quantity Option', 'woocommerce-product-addon' ),
					'description' => __( 'Quantity selection options', 'woocommerce-product-addon' ),
					'plan'        => NM_PersonalizedProduct::LICENSE_PLAN_1,
					'icon'        => 'fa-money',
				),
				array(
					'slug'        => 'bulkquantity',
					'title'       => __( 'Bulk Quantity', 'woocommerce-product-addon' ),
					'description' => __( 'Bulk quantity selection for products', 'woocommerce-product-addon' ),
					'plan'        => NM_PersonalizedProduct::LICENSE_PLAN_1,
					'icon'        => 'fa-columns',
				),
				array(
					'slug'        => 'selectqty',
					'title'       => __( 'Select Option Quantity', 'woocommerce-product-addon' ),
					'description' => __( 'Select option\'s quantity', 'woocommerce-product-addon' ),
					'plan'        => NM_PersonalizedProduct::LICENSE_PLAN_1,
					'icon'        => 'fa-list-ol',
				),
			),
		),
	);

	/**
	 * Filter grouped field types for the admin picker (legacy HTML + React modal).
	 *
	 * @param array<string, array{label: string, fields: array<int, array<string, mixed>>}> $fields_groups Groups.
	 */
	return apply_filters( 'ppom_admin_field_type_groups', $fields_groups );
}
