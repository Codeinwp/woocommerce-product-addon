<?php
/**
 * Arrays contining settings/meta detail
 **/

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not Allowed.' );
}


/**
 * Get plugin meta
 */
function ppom_get_plugin_meta() {

	$ppom_meta = array(
		'name'               => 'PPOM',
		'dir_name'           => '',
		'shortname'          => 'nm_personalizedproduct',
		'path'               => PPOM_PATH,
		'url'                => PPOM_URL,
		'db_version'         => 3.12,
		'logo'               => PPOM_URL . '/images/logo.png',
		'menu_position'      => 90,
		'ppom_bulkquantity'  => PPOM_WP_PLUGIN_DIR . '/ppom-addon-bulkquantity/classes/input.bulkquantity.php',
		'ppom_eventcalendar' => PPOM_WP_PLUGIN_DIR . '/ppom-addon-eventcalendar/classes/input.eventcalendar.php',
		'ppom_fixedprice'    => PPOM_WP_PLUGIN_DIR . '/ppom-addon-fixedprice/classes/input.fixedprice.php',
	);

	return apply_filters( 'ppom_plugin_meta', $ppom_meta );
}

/**
 * Return cols for inputs
 */
function ppom_get_input_cols() {

	$ppom_cols = array(
		2  => '2 Col',
		3  => '3 Col',
		4  => '4 Col',
		5  => '5 Col',
		6  => '6 Col',
		7  => '7 Col',
		8  => '8 Col',
		9  => '9 Col',
		10 => '10 Col',
		11 => '11 Col',
		12 => '12 Col'
	);

	return apply_filters( 'ppom_field_cols', $ppom_cols );
}

/**
 * Return visibility options for inputs
 */
function ppom_field_visibility_options() {

	$visibility_options = array(
		'everyone' => __( 'Everyone', 'ppom' ),
		'guests'   => __( 'Only Guests', 'ppom' ),
		'members'  => __( 'Only Members', 'ppom' ),
		'roles'    => __( 'By Role', 'ppom' )
	);

	return apply_filters( 'ppom_field_visibility_options', $visibility_options );
}

/**
 * Get timezone list
 */
function ppom_array_get_timezone_list( $selected_regions, $show_time ) {

	if ( $selected_regions == 'All' ) {
		$regions = array(
			DateTimeZone::AFRICA,
			DateTimeZone::AMERICA,
			DateTimeZone::ANTARCTICA,
			DateTimeZone::ASIA,
			DateTimeZone::ATLANTIC,
			DateTimeZone::AUSTRALIA,
			DateTimeZone::EUROPE,
			DateTimeZone::INDIAN,
			DateTimeZone::PACIFIC,
		);
	} else {
		$selected_regions = explode( ",", $selected_regions );
		$tz_regions       = array();

		foreach ( $selected_regions as $region ) {

			switch ( $region ) {
				case 'AFRICA':
					$tz_regions[] = DateTimeZone::AFRICA;
					break;
				case 'AMERICA':
					$tz_regions[] = DateTimeZone::AMERICA;
					break;
				case 'ANTARCTICA':
					$tz_regions[] = DateTimeZone::ANTARCTICA;
					break;
				case 'ASIA':
					$tz_regions[] = DateTimeZone::ASIA;
					break;
				case 'ATLANTIC':
					$tz_regions[] = DateTimeZone::ATLANTIC;
					break;
				case 'AUSTRALIA':
					$tz_regions[] = DateTimeZone::AUSTRALIA;
					break;
				case 'EUROPE':
					$tz_regions[] = DateTimeZone::EUROPE;
					break;
				case 'INDIAN':
					$tz_regions[] = DateTimeZone::INDIAN;
					break;
				case 'PACIFIC':
					$tz_regions[] = DateTimeZone::PACIFIC;
					break;
			}
		}
		$regions = $tz_regions;
	}

	$timezones = array();
	foreach ( $regions as $region ) {
		$timezones = array_merge( $timezones, DateTimeZone::listIdentifiers( $region ) );
	}

	$timezone_offsets = array();
	foreach ( $timezones as $timezone ) {
		$tz                            = new DateTimeZone( $timezone );
		$timezone_offsets[ $timezone ] = $tz->getOffset( new DateTime );
	}

	// sort timezone by timezone name
	ksort( $timezone_offsets );

	$timezone_list = array();
	foreach ( $timezone_offsets as $timezone => $offset ) {
		$offset_prefix    = $offset < 0 ? '-' : '+';
		$offset_formatted = gmdate( 'H:i', abs( $offset ) );

		$pretty_offset = "UTC${offset_prefix}${offset_formatted}";

		$t            = new DateTimeZone( $timezone );
		$c            = new DateTime( null, $t );
		$current_time = $c->format( 'g:i A' );

		if ( $show_time == 'on' ) {
			$timezone_list[ $timezone ] = "(${pretty_offset}) $timezone - $current_time";
		} else {
			$timezone_list[ $timezone ] = "(${pretty_offset}) $timezone";
		}
	}

	return $timezone_list;
}

/**
 * PPOM WC settings array
 */
function ppom_array_settings() {
	$ppom_fields = $ppom_settings_url = admin_url( 'admin.php?page=ppom' );

	$ppom_admin_url   = admin_url( 'admin-post.php' );
	$ppom_migrate_url = add_query_arg( array( 'action' => 'ppom_migrate_settings_panel' ), $ppom_admin_url );
	$ppom_migrate_url = wp_nonce_url( $ppom_migrate_url, 'ppom_migrate_nonce_action', 'ppom_migrate_nonce' );

	$ppom_settings = array(

		array(
			'title' => 'Settings Panel Migration',
			'type'  => 'title',
			'desc'  => __( 'Please migrate settings to new settings panel framework. <a href="' . esc_url( $ppom_migrate_url ) . '" class="page-title-action">Start Migration</a>', 'ppom' ),
			'id'    => 'ppom_settings_migration',
		),

		array(
			'title' => 'PPOM Labels',
			'type'  => 'title',
			'desc'  => __( 'Following settings help you the control and customize plugin as per your need. <a href="' . esc_url( $ppom_fields ) . '">PPOM Fields Manager</a>', 'ppom' ),
			'id'    => 'ppom_labels_settings',
		),

		array(
			'title'    => __( 'Option Total', 'ppom' ),
			'type'     => 'text',
			'desc'     => __( 'Label For Price Table', 'ppom' ),
			'default'  => __( 'Option Total', 'ppom' ),
			'id'       => 'ppom_label_option_total',
			'css'      => 'min-width:300px;',
			'desc_tip' => true,
		),

		array(
			'title'    => __( 'Option Total Suffix', 'ppom' ),
			'type'     => 'text',
			'desc'     => __( 'E.g for Tax/Va info like. Vat included', 'ppom' ),
			'default'  => __( '', 'ppom' ),
			'id'       => 'ppom_label_option_total_suffex',
			'css'      => 'min-width:300px;',
			'desc_tip' => true,
		),
		array(
			'title'    => __( 'Product Price', 'ppom' ),
			'type'     => 'text',
			'desc'     => __( 'Label For Price Table', 'ppom' ),
			'default'  => __( 'Product Price', 'ppom' ),
			'id'       => 'ppom_label_product_price',
			'css'      => 'min-width:300px;',
			'desc_tip' => true,
		),
		array(
			'title'    => __( 'Total', 'ppom' ),
			'type'     => 'text',
			'desc'     => __( 'Label For Price Table', 'ppom' ),
			'default'  => __( 'Total', 'ppom' ),
			'id'       => 'ppom_label_total',
			'css'      => 'min-width:300px;',
			'desc_tip' => true,
		),
		array(
			'title'    => __( 'Fixed Fee', 'ppom' ),
			'type'     => 'text',
			'desc'     => __( 'Label For Price Table', 'ppom' ),
			'default'  => __( 'Fixed Fee', 'ppom' ),
			'id'       => 'ppom_label_fixed_fee',
			'css'      => 'min-width:300px;',
			'desc_tip' => true,
		),
		array(
			'title'    => __( 'Discount Price', 'ppom' ),
			'type'     => 'text',
			'desc'     => __( 'Label For Price Table', 'ppom' ),
			'default'  => __( 'Discount Price', 'ppom' ),
			'id'       => 'ppom_label_discount_price',
			'css'      => 'min-width:300px;',
			'desc_tip' => true,
		),
		array(
			'title'    => __( 'Total Discount', 'ppom' ),
			'type'     => 'text',
			'desc'     => __( 'Label For Price Table', 'ppom' ),
			'default'  => __( 'Total Discount', 'ppom' ),
			'id'       => 'ppom_label_total_discount',
			'css'      => 'min-width:300px;',
			'desc_tip' => true,
		),

		array(
			'title'   => __( 'Disable Bootstrap', 'ppom' ),
			'type'    => 'checkbox',
			'label'   => __( 'Yes', 'ppom' ),
			'default' => 'no',
			'id'      => 'ppom_disable_bootstrap',
			'desc'    => __( 'Bootstrap JS is being loaded from CDN, it will disable if your site already loading it.', 'ppom' ),
		),

		array(
			'title'   => __( 'Enable Legacy Inputs Rendering', 'ppom' ),
			'type'    => 'checkbox',
			'label'   => __( 'Yes', 'ppom' ),
			'default' => 'no',
			'id'      => 'ppom_enable_legacy_inputs_rendering',
			'desc'    => __( "PPOM Version 22.0 is major update, if some issues occur you can revert back to old version by this.", 'ppom' )
		),

		array(
			'title'   => __( 'Disable FontAwesome', 'ppom' ),
			'type'    => 'checkbox',
			'label'   => __( 'Yes', 'ppom' ),
			'default' => 'no',
			'id'      => 'ppom_disable_fontawesome',
			'desc'    => __( 'FontAwesome are being loaded from CDN, it will disable if your site already loading it.', 'ppom' ),
		),
		array(
			'title'   => __( 'Enable Legacy Price Calculations', 'ppom' ),
			'type'    => 'checkbox',
			'label'   => __( 'Yes', 'ppom' ),
			'default' => 'no',
			'id'      => 'ppom_legacy_price',
		),
		array(
			'title'       => __( 'PPOM Permissions', 'ppom' ),
			'type'        => 'ppom_multi_select',
			'label'       => __( 'Button', 'ppom' ),
			'default'     => 'administrator',
			'placeholder' => 'choose role',
			'options'     => ppom_get_all_editable_roles(),
			'id'          => 'ppom_permission_mfields',
			'desc'        => __( 'You can set permissions here so PPOM fields can be managed by different roles', 'ppom' ),
			'desc_tip'    => true,
		),

		array(
			'type' => 'sectionend',
			'id'   => 'ppom_labels_settings',
		),

		array(
			'name' => __( 'Advance Features (PRO)', 'ppom' ),
			'type' => 'title',
			'desc' => __( 'These options will work when PRO version is installed', 'ppom' ),
			'id'   => 'ppom_pro_features'
		),

		array(
			'title'   => __( 'Hide Product Price?', 'ppom' ),
			'type'    => 'checkbox',
			'label'   => __( 'Yes', 'ppom' ),
			'default' => 'no',
			'id'      => 'ppom_hide_product_price',
			'desc'    => __( 'Hides Product core price under price Title (When PPOM Fields attached)', 'ppom' ),

		),

		array(
			'title'   => __( 'Hide Variable Product Price?', 'ppom' ),
			'type'    => 'checkbox',
			'label'   => __( 'Yes', 'ppom' ),
			'default' => 'no',
			'id'      => 'ppom_hide_variable_product_price',
			'desc'    => __( 'Hides Variable Product core price under price Title (When PPOM Fields attached)', 'ppom' ),

		),


		array(
			'title'   => __( 'Hide Options Price?', 'ppom' ),
			'type'    => 'checkbox',
			'label'   => __( 'Yes', 'ppom' ),
			'default' => 'no',
			'id'      => 'ppom_hide_option_price',
			'desc'    => __( 'Hides options price in Selec/Radio/Checkbox/Image display prices with label', 'ppom' ),

		),

		array(
			'title'   => __( 'Taxable Options Price?', 'ppom' ),
			'type'    => 'checkbox',
			'label'   => __( 'Yes', 'ppom' ),
			'default' => 'no',
			'id'      => 'ppom_taxable_option_price',
			'desc'    => __( 'Apply tax settings on option prices from WooCommerce->Tax', 'ppom' ),

		),

		array(
			'title'   => __( 'Clear Fields after Add to Cart?', 'ppom' ),
			'type'    => 'checkbox',
			'label'   => __( 'Yes', 'ppom' ),
			'default' => 'no',
			'id'      => 'ppom_hide_clear_fields',
			'desc'    => __( 'Empty all fields on Product page after to cart.', 'ppom' ),

		),

		array(
			'title'   => __( 'Enable PPOM REST API?', 'ppom' ),
			'type'    => 'checkbox',
			'label'   => __( 'Yes', 'ppom' ),
			'default' => 'no',
			'id'      => 'ppom_api_enable',
			'desc'    => __( 'Check this option to enable PPOM REST API', 'ppom' ),
		),

		array(
			'title'   => __( 'Use optimized Price Table Caculation (BETA)', 'ppom' ),
			'type'    => 'checkbox',
			'label'   => __( 'Yes', 'ppom' ),
			'default' => 'no',
			'id'      => 'ppom_price_table_v2',
			'desc'    => __( 'A Fast and Optimized script to caculate price on product page in Table.', 'ppom' ),
		),

		array(
			'title'   => __( 'Enable New Conditional Logic Script', 'ppom' ),
			'type'    => 'checkbox',
			'label'   => __( 'Yes', 'ppom' ),
			'default' => 'no',
			'id'      => 'ppom_new_conditions',
			'desc'    => __( 'A faster approach to load conditional fields. Beta version, please report bug in new conditional script.', 'ppom' ),
		),

		array(
			'title'   => __( 'Do not send Product Meta to PayPal Invoice', 'ppom' ),
			'type'    => 'checkbox',
			'label'   => __( 'Yes', 'ppom' ),
			'default' => 'no',
			'id'      => 'ppom_disable_meta_paypal_invoice',
			'desc'    => __( 'Product meta will not be sent to PayPal invoice, only the Item name will be sent to invoice', 'ppom' ),
		),

		array(
			'title'    => __( 'Select Option Label', 'ppom' ),
			'type'     => 'text',
			'desc'     => __( 'Label For Price Table', 'ppom' ),
			'default'  => __( 'Select Options', 'ppom' ),
			'id'       => 'ppom_label_select_option',
			'css'      => 'min-width:300px;',
			'desc_tip' => true,
		),


		array(
			'title'    => __( 'PPOM API Secret Key', 'ppom' ),
			'type'     => 'text',
			'desc'     => __( 'Enter any characters to create a secret key. This key must be set while requesting to API', 'ppom' ),
			'id'       => 'ppom_rest_secret_key',
			'css'      => 'min-width:300px;',
			'desc_tip' => true,
		),

		array(
			'title'    => __( 'Delete Un-used images', 'ppom' ),
			'type'     => 'select',
			'label'    => __( 'Button', 'ppom' ),
			'default'  => 'daily',
			'options'  => array(
				'daily'   => __( 'Daily', 'ppom' ),
				'weekly'  => __( 'Weekly', 'ppom' ),
				'monthly' => __( 'Monthly', 'ppom' ),
			),
			'id'       => 'ppom_remove_unused_images_schedule',
			'desc'     => __( 'Set duration to uploaded images of abandoned cart. Re-activate plugin to when update this option', 'ppom' ),
			'desc_tip' => true,
		),

		array(
			'title'    => __( 'Meta Group Overrides', 'ppom' ),
			'type'     => 'select',
			'label'    => __( 'Button', 'ppom' ),
			'default'  => 'default',
			'options'  => array(
				'default'             => __( 'Default', 'ppom' ),
				'category_override'   => __( 'Category Overrides Individual Assignment', 'ppom' ),
				'individual_override' => __( 'Individual Overrides Category Assignment', 'ppom' ),
			),
			'id'       => 'ppom_meta_overrides',
			'desc'     => __( 'Leave if default if not sure.', 'ppom' ),
			'desc_tip' => true,
		),

		array(
			'title'    => __( 'Meta Group Priority', 'ppom' ),
			'type'     => 'select',
			'label'    => __( 'Button', 'ppom' ),
			'default'  => 'default',
			'options'  => array(
				'category_first'   => __( 'Category First', 'ppom' ),
				'individual_first' => __( 'Individual First', 'ppom' ),
			),
			'id'       => 'ppom_meta_priority',
			'desc'     => __( 'Leave if default if not sure.', 'ppom' ),
			'desc_tip' => true,
		),

		array(
			'title'    => __( 'Price Table Position', 'ppom' ),
			'type'     => 'select',
			'label'    => __( 'Button', 'ppom' ),
			'default'  => 'after',
			'options'  => array(
				'after'  => __( 'After PPOM Fields', 'ppom' ),
				'before' => __( 'Before  PPOM Fields', 'ppom' ),
			),
			'id'       => 'ppom_price_table_location',
			'desc'     => __( 'Set the location to render Price Table on Front-end', 'ppom' ),
			'desc_tip' => true,
		),

		array(
			'type' => 'sectionend',
			'id'   => 'ppom_pro_features',
		),

	);

	return apply_filters( 'ppom_settings_data', $ppom_settings );
}

/**
 * PPOM Scripts Vars
 * It only used for addon via this function "ppom_hooks_load_input_scripts"
 * cart-edit-addon
 * svg-addon
 */
function ppom_array_get_js_input_vars( $product, $args = null ) {

	// ppom_pa($args);
	$defaults = array(
		'wc_no_decimal'       => wc_get_price_decimals(),
		'show_price_per_unit' => false,
	);

	// Parse incoming $args into an array and merge it with $defaults
	$args                = wp_parse_args( $args, $defaults );
	$decimal_palces      = $args['wc_no_decimal'];
	$show_price_per_unit = $args['show_price_per_unit'];

	$product_id         = ppom_get_product_id( $product );
	$ppom               = new PPOM_Meta( $product_id );
	$ppom_meta_settings = $ppom->ppom_settings;
	$ppom_meta_fields   = $ppom->fields;

	if ( ! empty( $ppom_id ) ) {
		$ppom_meta_fields   = $ppom->get_fields_by_id( $ppom_id );
		$ppom_meta_settings = $ppom->get_settings_by_id( $ppom_id );
	}

	$ppom_meta_fields_updated = array();
	foreach ( $ppom_meta_fields as $index => $fields_meta ) {

		$type      = isset( $fields_meta['type'] ) ? $fields_meta['type'] : '';
		$title     = ( isset( $fields_meta['title'] ) ? $fields_meta ['title'] : '' );
		$data_name = ( isset( $fields_meta['data_name'] ) ? $fields_meta ['data_name'] : $title );

		$fields_meta['data_name'] = sanitize_key( $data_name );
		$fields_meta['title']     = stripslashes( $title );

		$fields_meta['field_type'] = apply_filters( 'ppom_js_fields', $type, $fields_meta );

		// Some field specific settings
		switch ( $type ) {

			case 'daterange':
				// Check if value is in GET 
				if ( ! empty( $_GET[ $data_name ] ) ) {

					$value                     = sanitize_text_field( $_GET[ $data_name ] );
					$to_dates                  = explode( ' - ', $value );
					$fields_meta['start_date'] = $to_dates[0];
					$fields_meta['end_date']   = $to_dates[0];
				}
				break;

			case 'color':
				// Check if value is in GET
				if ( ! empty( $_GET[ $data_name ] ) ) {

					$fields_meta['default_color'] = sanitize_text_field( $_GET[ $data_name ] );
				}
				break;

			case 'bulkquantity':

				$fields_meta['options'] = stripslashes( $fields_meta['options'] );

				// To make bulkquantity option WOOCS ready
				$bulkquantities_options     = json_decode( $fields_meta['options'], true );
				$bulkquantities_new_options = array();
				foreach ( $bulkquantities_options as $bq_opt ) {
					$bq_array = array();
					foreach ( $bq_opt as $key => $value ) {

						if ( $key != 'Quantity Range' ) {
							$bq_array[ $key ] = apply_filters( 'ppom_option_price', $value );
						} else {
							$bq_array[ $key ] = $value;
						}
					}
					$bulkquantities_new_options[] = $bq_array;
				}

				$fields_meta['options'] = json_encode( $bulkquantities_new_options );
				break;
		}

		$ppom_meta_fields_updated[] = $fields_meta;

	}


	$js_vars                              = array();
	$js_vars['ajaxurl']                   = admin_url( 'admin-ajax.php', ( is_ssl() ? 'https' : 'http' ) );
	$js_vars['ppom_inputs']               = $ppom_meta_fields_updated;
	$js_vars['field_meta']                = $ppom_meta_fields_updated;
	$js_vars['ppom_validate_nonce']       = wp_create_nonce( 'ppom_validating_action' );
	$js_vars['wc_thousand_sep']           = wc_get_price_thousand_separator();
	$js_vars['wc_currency_pos']           = get_option( 'woocommerce_currency_pos' );
	$js_vars['wc_decimal_sep']            = get_option( 'woocommerce_price_decimal_sep' );
	$js_vars['wc_no_decimal']             = $decimal_palces;
	$variation_id                         = '';
	$context                              = 'product';
	$js_vars['wc_product_price']          = ppom_get_product_price( $product, $variation_id, $context );
	$js_vars['wc_product_regular_price']  = ppom_get_product_regular_price( $product );
	$ppom_label_discount_price            = ppom_get_option( 'ppom_label_discount_price', __( 'Discount Price', 'ppom' ) );
	$ppom_label_product_price             = ppom_get_option( 'ppom_label_product_price', __( 'Product Price', 'ppom' ) );
	$ppom_label_option_total              = ppom_get_option( 'ppom_label_option_total', __( 'Option Total', 'ppom' ) );
	$ppom_label_fixed_fee                 = ppom_get_option( 'ppom_label_fixed_fee', __( 'Fixed Fee', 'ppom' ) );
	$ppom_label_total_discount            = ppom_get_option( 'ppom_label_total_discount', __( 'Total Discount', 'ppom' ) );
	$ppom_label_total                     = ppom_get_option( 'ppom_label_total', __( 'Total', 'ppom' ) );
	$js_vars['total_discount_label']      = sprintf( __( "%s", 'ppom' ), $ppom_label_total_discount );
	$js_vars['price_matrix_heading']      = sprintf( __( "%s", 'ppom' ), $ppom_label_discount_price );
	$js_vars['product_base_label']        = sprintf( __( "%s", 'ppom' ), $ppom_label_product_price );
	$js_vars['option_total_label']        = sprintf( __( "%s", 'ppom' ), $ppom_label_option_total );
	$js_vars['fixed_fee_heading']         = sprintf( __( "%s", 'ppom' ), $ppom_label_fixed_fee );
	$js_vars['total_without_fixed_label'] = sprintf( __( "%s", 'ppom' ), $ppom_label_total );
	$js_vars['product_quantity_label']    = __( "Product Quantity", "ppom" );
	$js_vars['product_title']             = sprintf( __( "%s", "ppom" ), $product->get_title() );
	$js_vars['per_unit_label']            = __( "unit", "ppom" );
	$js_vars['show_price_per_unit']       = $show_price_per_unit;
	$js_vars['text_quantity']             = __( "Quantity", "ppom" );
	$js_vars['show_option_price']         = $ppom->price_display;
	$js_vars['is_shortcode']              = 'no';
	$js_vars['plugin_url']                = PPOM_URL;
	$js_vars['is_mobile']                 = ppom_is_mobile();
	$js_vars['product_id']                = $product_id;
	$js_vars['tax_prefix']                = ppom_tax_label_display();

	return apply_filters( 'ppom_input_vars', $js_vars, $product );
}

/**
 * Showing Tax prefix
 * @since 20.5
 */
function ppom_tax_label_display() {
	/*if ( wc_tax_enabled() && 'excl' === get_option( 'woocommerce_tax_display_shop' ) &&  get_option( 'woocommerce_price_display_suffix' ) !== '' ) {
		return sprintf(__("%s", 'ppom'), get_option( 'woocommerce_price_display_suffix' ));
	}*/

	$suffex = ppom_get_option( 'ppom_label_option_total_suffex' );
	if ( wc_tax_enabled() && $suffex !== '' ) {
		return sprintf( __( "%s", 'ppom' ), $suffex );
	}
}

/**
 * Get PPOM inputs
 */
function ppom_array_all_inputs() {

	$all_inputs = array(
		'core' => array(
			'text',
			'textarea',
			'select',
			'radio',
			'checkbox',
			'email',
			'date',
			'number',
			'hidden',
			'daterange',
			'color',
			'file',
			'image',
			'timezone',
			'quantities',
			'cropper',
			'pricematrix',
			'section',
			'palettes',
			'audio',
			'measure',
			'divider'
		)
	);

	return apply_filters( 'ppom_all_inputs_array', $all_inputs );
}
