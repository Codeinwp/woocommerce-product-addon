<?php
/**
 * Plugin Admin Settings
 */

/* 
**========== Block direct access =========== 
*/
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*** Check if class exist */
if ( ! class_exists( 'PPOM_SettingsFramework' ) ) {
	return;
}

/**
 * Register Panel Fieds Against Panel ID
 * --------------------------------------
 */
$core_settings = array(
	'ppom_general_section1'               => array(
		'type'  => 'section',
		'title' => __( 'Basic Settings', 'woocommerce-product-addon' ),
	),
	'ppom_disable_bootstrap'              => array(
		'type'  => 'checkbox',
		'title' => __( 'Disable Bootstrap', 'woocommerce-product-addon' ),
		'desc'  => __( 'Bootstrap JS is loaded from a CDN by default. You can disable it if your site is already loading it.', 'woocommerce-product-addon' ),
		'label' => __( 'Yes', 'woocommerce-product-addon' ),
	),
	'ppom_enable_legacy_inputs_rendering' => array(
		'type'  => 'checkbox',
		'title' => __( 'Legacy Inputs Rendering', 'woocommerce-product-addon' ),
		'desc'  => __( 'PPOM Version 22.0 is a major update. If any issues occur, you can revert to the previous version using this option.', 'woocommerce-product-addon' ),
	),
	'ppom_new_conditions'                 => array(
		'type'  => 'checkbox',
		'title' => __( 'Legacy Conditions Script', 'woocommerce-product-addon' ),
		'desc'  => __( 'If you found any issue in conditions you may enable this option.', 'woocommerce-product-addon' ),
	),
	'ppom_legacy_price'                   => array(
		'type'  => 'checkbox',
		'title' => __( 'Enable Legacy Price Calculations', 'woocommerce-product-addon' ),
		'desc'	=> __( 'Enable this option to use the legacy method for price calculations.', 'woocommerce-product-addon' ),
	),
	'ppom_permission_mfields'             => array(
		'type'        => 'select',
		'title'       => __( 'PPOM Permissions', 'woocommerce-product-addon' ),
		'desc'        => __( 'You can set permissions here to allow different roles to manage PPOM fields.', 'woocommerce-product-addon' ),
		'default'     => 'administrator',
		'placeholder' => __( 'choose role', 'woocommerce-product-addon' ),
		'options'     => ppom_get_all_editable_roles(),
		'style'       => 'multiselect',
	),
	'ppom_restricted_file_type'           => array(
		'type'    => 'text',
		'title'   => __( 'Restricted File Types', 'woocommerce-product-addon' ),
		'desc'    => __( 'Specify the file types that are restricted from being uploaded in this section.', 'woocommerce-product-addon' ),
		'default' => __( 'php,php4,php5,php6,php7,phtml,exe,shtml', 'woocommerce-product-addon' ),
	),
	'ppom_general_section2'               => array(
		'type'  => 'section',
		'title' => __( 'Label Settings', 'woocommerce-product-addon' ),
	),
	'ppom_label_option_total'             => array(
		'type'    => 'text',
		'title'   => __( 'Option Total Label Inside Price Table', 'woocommerce-product-addon' ),
		'default' => __( 'Option Total', 'woocommerce-product-addon' ),
	),
	'ppom_label_product_price'            => array(
		'type'    => 'text',
		'title'   => __( 'Product Price Label inside Price Table', 'woocommerce-product-addon' ),
		'default' => __( 'Product Price', 'woocommerce-product-addon' ),
	),
	'ppom_label_total'                    => array(
		'type'    => 'text',
		'title'   => __( 'Total Label inside Price Table', 'woocommerce-product-addon' ),
		'default' => __( 'Total', 'woocommerce-product-addon' ),
	),
	'ppom_label_total_discount'           => array(
		'type'    => 'text',
		'title'   => __( 'Total Discount Label Inside Price Table', 'woocommerce-product-addon' ),
		'default' => __( 'Total Discount', 'woocommerce-product-addon' ),
	),
	'ppom_label_option_total_suffex'      => array(
		'type'  => 'text',
		'title' => __( 'Option Total Suffix', 'woocommerce-product-addon' ),
		'desc'  => __( 'Specify the label to display tax or VAT information, such as \'VAT Included\' or \'Tax Applied,\' inside the price table.', 'woocommerce-product-addon' ),
	),
);

/**
 * Register Panel Against Tab ID
 * -------------------------------
 */
$panel_meta = array(
	'ppom_admin_core_settings' => array(
		'id'          => 'ppom_admin_core_settings',
		'title'       => 'General Settings',
		'desc'        => '',
		'is_sabpanel' => true,
		'active'      => 'yes',
	),
);


/**
 * Register Main Tabs
 * --------------------------
 */
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
 */
PPOMSETTINGS()->register_tabs( $register_tabs )->register_panel( 'ppom_general_tab', $panel_meta );
PPOMSETTINGS()->register_setting( 'ppom_admin_core_settings', $core_settings );
function ppom_load_pro_options() {
	$pro_settings = array(
		'ppom_pro_basics'                    => array(
			'type'  => 'section',
			'title' => __( 'Basic Settings', 'woocommerce-product-addon' ),
		),
		'ppom_hide_image_cart'               => array(
			'type'  => 'checkbox',
			'title' => __( 'Hide Images in Cart', 'woocommerce-product-addon' ),
			'desc'  => __( 'When using image-type input, the selected images will not be displayed in the cart.', 'woocommerce-product-addon' ),
			'label' => __( 'Yes', 'woocommerce-product-addon' ),
		),
		'ppom_disable_crop_thumbnail'        => array(
			'type'  => 'checkbox',
			'title' => __( 'Uploaded Image Resize Proportionally', 'woocommerce-product-addon' ),
			'desc'  => __( 'By default, images are resized into a square. Enable this option to resize uploaded images proportionally.', 'woocommerce-product-addon' ),
			'label' => __( 'Yes', 'woocommerce-product-addon' ),
		),
		'ppom_hide_clear_fields'             => array(
			'type'  => 'checkbox',
			'title' => __( 'Clear Fields After Add to Cart', 'woocommerce-product-addon' ),
			'desc'  => __( 'Clear all fields on the product page after adding the item to the cart.', 'woocommerce-product-addon' ),
			'label' => __( 'Yes', 'woocommerce-product-addon' ),
		),
		'ppom_enable_client_validation'      => array(
			'type'  => 'checkbox',
			'title' => __( 'Enable Client-Side Validation', 'woocommerce-product-addon' ),
			'desc'  => __( 'Enable this option for faster and more efficient validation of fields directly on the client side.', 'woocommerce-product-addon' ),
			'label' => __( 'Yes', 'woocommerce-product-addon' ),
		),
		'ppom_disable_meta_paypal_invoice'   => array(
			'type'  => 'checkbox',
			'title' => __( 'Do Not Send Product Meta to PayPal Invoice', 'woocommerce-product-addon' ),
			'desc'  => __( 'Product meta will not be included in the PayPal invoice; only the item name will be sent to the invoice.', 'woocommerce-product-addon' ),
		),
		'ppom_label_select_option'           => array(
			'type'    => 'text',
			'title'   => __( 'Shop Add to Cart Label', 'woocommerce-product-addon' ),
			'desc'    => __( 'You can set the \'Add to Cart\' button label here when PPOM is attached.', 'woocommerce-product-addon' ),
			'default' => __( 'Select Options', 'woocommerce-product-addon' ),
		),
		'ppom_pro_pricing'                   => array(
			'type'  => 'section',
			'title' => __( 'Price Settings', 'woocommerce-product-addon' ),
		),
		'ppom_override_product_price'        => array(
			'type'  => 'checkbox',
			'title' => __( 'Override Product Price', 'woocommerce-product-addon' ),
			'desc'  => __( 'Override the core product price on archive and product pages if PPOM field options include a price.', 'woocommerce-product-addon' ),
			'label' => __( 'Yes', 'woocommerce-product-addon' ),
		),
		'ppom_hide_variable_product_price'   => array(
			'type'  => 'checkbox',
			'title' => __( 'Hide Variable Product Price', 'woocommerce-product-addon' ),
			'desc'  => __( 'Hides the core price of variable products under the price title when PPOM fields are attached.', 'woocommerce-product-addon' ),
			'label' => __( 'Yes', 'woocommerce-product-addon' ),
		),
		'ppom_hide_option_price'             => array(
			'type'  => 'checkbox',
			'title' => __( 'Hide Options Price', 'woocommerce-product-addon' ),
			'desc'  => __( 'Hides the option prices in Select, Radio, Checkbox, and Image inputs when displaying prices with labels.', 'woocommerce-product-addon' ),
			'label' => __( 'Yes', 'woocommerce-product-addon' ),
		),
		'ppom_taxable_option_price'          => array(
			'type'  => 'checkbox',
			'title' => __( 'Taxable Options Price', 'woocommerce-product-addon' ),
			'desc'  => __( 'Apply tax settings to option prices as configured in WooCommerce → Tax.', 'woocommerce-product-addon' ),
			'label' => __( 'Yes', 'woocommerce-product-addon' ),
		),
		'ppom_taxable_fixed_price'           => array(
			'type'  => 'checkbox',
			'title' => __( 'Apply Tax on Fixed/Cart Fee', 'woocommerce-product-addon' ),
			'desc'  => __( 'When this option is enabled, the fixed fee will be taxable.', 'woocommerce-product-addon' ),
			'label' => __( 'Yes', 'woocommerce-product-addon' ),
		),
		'ppom_price_table_location'          => array(
			'type'    => 'select',
			'title'   => __( 'Price Table Position', 'woocommerce-product-addon' ),
			'desc'    => __( 'Set the location where the price table will be rendered on the front end.', 'woocommerce-product-addon' ),
			'default' => 'after',
			'options' => array(
				'after'  => __( 'After PPOM Fields', 'woocommerce-product-addon' ),
				'before' => __( 'Before  PPOM Fields', 'woocommerce-product-addon' ),
			),
		),
		'ppom_pro_advanced'                  => array(
			'type'  => 'section',
			'title' => __( 'Advanced Settings', 'woocommerce-product-addon' ),
		),
		'ppom_remove_unused_images_schedule' => array(
			'type'    => 'select',
			'title'   => __( 'Delete Unused Images', 'woocommerce-product-addon' ),
			'desc'    => __( 'Set the duration for keeping uploaded images from abandoned carts. Reactivate the plugin after updating this option to apply changes.', 'woocommerce-product-addon' ),
			'default' => 'daily',
			'options' => array(
				'daily'   => __( 'Daily', 'woocommerce-product-addon' ),
				'weekly'  => __( 'Weekly', 'woocommerce-product-addon' ),
				'monthly' => __( 'Monthly', 'woocommerce-product-addon' ),
			),
		),
		'ppom_meta_overrides'                => array(
			'type'    => 'select',
			'title'   => __( 'Meta Group Overrides', 'woocommerce-product-addon' ),
			'desc'    => __( 'Leave this set to default if you\'re not sure.', 'woocommerce-product-addon' ),
			'default' => 'default',
			'options' => array(
				'default'             => __( 'Default', 'woocommerce-product-addon' ),
				'category_override'   => __( 'Category Overrides Individual Assignment', 'woocommerce-product-addon' ),
				'individual_override' => __( 'Individual Overrides Category Assignment', 'woocommerce-product-addon' ),
			),
		),
		'ppom_meta_priority'                 => array(
			'type'    => 'select',
			'title'   => __( 'Meta Group Priority', 'woocommerce-product-addon' ),
			'desc'    => __( 'Choose which meta group takes priority when applied: Category or Product. Leave this set to default if you\'re not sure.', 'woocommerce-product-addon' ),
			'default' => 'default',
			'options' => array(
				'category_first'   => __( 'Category First', 'woocommerce-product-addon' ),
				'individual_first' => __( 'Individual First', 'woocommerce-product-addon' ),
			),
		),
	);

	$style_settings = array(
		'ppom_styles_general_section'         => array(
			'type'  => 'section',
			'title' => __( 'General Settings', 'woocommerce-product-addon' ),
		),
		'ppom_input_label_style'              => array(
			'type'   => 'typography',
			'title'  => __( 'Field Label', 'woocommerce-product-addon' ),
			'output' => '.ppom-wrapper label.form-control-label',
		),
		'ppom_input_desc_style'               => array(
			'type'   => 'typography',
			'title'  => __( 'Field Description', 'woocommerce-product-addon' ),
			'output' => '.ppom-wrapper .ppom-input-desc',
		),
		'ppom_input_option_label_style'       => array(
			'type'   => 'typography',
			'title'  => __( 'Options Label', 'woocommerce-product-addon' ),
			'output' => '.ppom-wrapper .ppom-input-option-label',
		),
		'ppom_input_option_price_label_style' => array(
			'type'   => 'typography',
			'title'  => __( 'Options Price Label', 'woocommerce-product-addon' ),
			'output' => '.ppom-wrapper .ppom-option-label-price',
		),
		'ppom_styles_advance_section'         => array(
			'type'  => 'section',
			'title' => __( 'Input Box Settings', 'woocommerce-product-addon' ),
			'desc'  => __( 'PPOM frontend elements can be styled from these settings.', 'woocommerce-product-addon' ),
		),
		'ppom_input_box_bgclr'                => array(
			'type'   => 'color',
			'title'  => __( 'Background Color', 'woocommerce-product-addon' ),
			'output' => array( 'background-color' => '.ppom-wrapper input, .ppom-wrapper select, .ppom-wrapper textarea' ),
		),
		'ppom_input_box_txtclr'               => array(
			'type'   => 'color',
			'title'  => __( 'Text Color', 'woocommerce-product-addon' ),
			'output' => array( 'color' => '.ppom-wrapper input, .ppom-wrapper select, .ppom-wrapper textarea' ),
		),
		'ppom_input_box_border'               => array(
			'type'   => 'css_editor',
			'title'  => __( 'Border Style', 'woocommerce-product-addon' ),
			'mode'   => 'border',
			'output' => '.ppom-wrapper input, .ppom-wrapper select, .ppom-wrapper textarea',
		),
		'ppom_input_box_border_focus'         => array(
			'type'   => 'css_editor',
			'title'  => __( 'Border Focus Style', 'woocommerce-product-addon' ),
			'mode'   => 'border',
			'output' => '.ppom-wrapper input:focus, .ppom-wrapper select:focus, .ppom-wrapper textarea:focus',
		),
		'ppom_input_box_border_shadow_focus'  => array(
			'type'      => 'text',
			'title'     => __( 'Box Shodow Focus', 'woocommerce-product-addon' ),
			'desc'      => __( 'If you don\'t want to apply it, simply set the value to \'none.\'', 'woocommerce-product-addon' ),
			'hint'      => '0 0 0 0.2rem rgb(0 123 255 / 25%)',
			'reference' => array(
				'ref_title' => __( 'See reference', 'woocommerce-product-addon' ),
				'ref_link'  => 'https://www.w3schools.com/cssref/css3_pr_box-shadow.asp',
			),
		),
		'ppom_styles_price_table_section'     => array(
			'type'  => 'section',
			'title' => __( 'Price Table Settings', 'woocommerce-product-addon' ),
		),
		'ppom_price_table_txtclr'             => array(
			'type'   => 'color',
			'title'  => __( 'Text Color', 'woocommerce-product-addon' ),
			'output' => array( 'color' => '.ppom-wrapper #ppom-price-container table th, .ppom-wrapper #ppom-price-container table td' ),
		),
		'ppom_price_table_bgclr'              => array(
			'type'   => 'color',
			'title'  => __( 'Background Color', 'woocommerce-product-addon' ),
			'output' => array( 'background-color' => '.ppom-wrapper #ppom-price-container table' ),
		),
		'ppom_styles_tooltip_section'         => array(
			'type'  => 'section',
			'title' => __( 'Tooltip Settings', 'woocommerce-product-addon' ),
		),
		'ppom_input_tooltip_iconclr'          => array(
			'type'   => 'color',
			'title'  => __( 'Icon Color', 'woocommerce-product-addon' ),
			'output' => array( 'color' => '.ppom-wrapper .ppom-tooltip ' ),
		),
		'ppom_input_tooltip_maxwidth'         => array(
			'type'  => 'text',
			'title' => __( 'Max. Width', 'woocommerce-product-addon' ),
			'desc'  => __( 'Default: 500px', 'woocommerce-product-addon' ),
		),
		'ppom_input_tooltip_txtclr'           => array(
			'type'  => 'color',
			'title' => __( 'Text Color', 'woocommerce-product-addon' ),
		),
		'ppom_input_tooltip_bgclr'            => array(
			'type'  => 'color',
			'title' => __( 'Background Color', 'woocommerce-product-addon' ),
		),
		'ppom_input_tooltip_borderclr'        => array(
			'type'  => 'color',
			'title' => __( 'Border Color', 'woocommerce-product-addon' ),
		),
		'ppom_input_tooltip_position'         => array(
			'type'    => 'select',
			'title'   => __( 'Position', 'woocommerce-product-addon' ),
			'options' => array(
				'top'    => __( 'Top', 'woocommerce-product-addon' ),
				'bottom' => __( 'Bottom', 'woocommerce-product-addon' ),
				'left'   => __( 'Left', 'woocommerce-product-addon' ),
				'right'  => __( 'Right', 'woocommerce-product-addon' ),
			),
		),
		'ppom_input_tooltip_animation'        => array(
			'type'    => 'select',
			'title'   => __( 'Animation', 'woocommerce-product-addon' ),
			'options' => array(
				'fade'  => __( 'Fade', 'woocommerce-product-addon' ),
				'grow'  => __( 'Grow', 'woocommerce-product-addon' ),
				'swing' => __( 'Swing', 'woocommerce-product-addon' ),
				'slide' => __( 'Slide', 'woocommerce-product-addon' ),
				'fall'  => __( 'Fall', 'woocommerce-product-addon' ),
			),
		),
		'ppom_input_tooltip_interactive'      => array(
			'type'  => 'checkbox',
			'title' => __( 'Interactive', 'woocommerce-product-addon' ),
			'desc'  => __( 'Allow users to interact with the tooltip.', 'woocommerce-product-addon' ),
		),
		'ppom_input_tooltip_trigger'          => array(
			'type'  => 'checkbox',
			'title' => __( 'Open Tooltip on click', 'woocommerce-product-addon' ),
			'desc'  => __( 'Allow users to open the tooltip by clicking on it. Default behavior is hover.', 'woocommerce-product-addon' ),
		),
	);

	$panels = array(
		'ppom_admin_pro_settings'    => array(
			'id'           => 'ppom_admin_pro_settings',
			'title'        => __( 'Pro Settings', 'woocommerce-product-addon' ),
			'desc'         => '',
			'is_available' => false,
			'is_sabpanel'  => true,
		),
		'ppom_admin_fields_settings' => array(
			'id'           => 'ppom_admin_fields_settings',
			'title'        => __( 'Fields Settings', 'woocommerce-product-addon' ),
			'is_available' => false,
			'desc'         => '',
			'is_sabpanel'  => true,
		),
		'ppom_admin_style_settings'  => array(
			'id'           => 'ppom_admin_style_settings',
			'title'        => __( 'Style Settings', 'woocommerce-product-addon' ),
			'desc'         => '',
			'is_available' => false,
			'is_sabpanel'  => true,
		),
	);
	PPOMSETTINGS()->register_panel( 'ppom_general_tab', $panels );


	PPOMSETTINGS()->register_setting( 'ppom_admin_pro_settings', $pro_settings );
	PPOMSETTINGS()->register_setting( 'ppom_admin_style_settings', $style_settings );

	$settings = array(
		'ppom_cart_enabled'              => array(
			'type'  => 'checkbox',
			'title' => __( 'Enable PPOM Cart Edit', 'woocommerce-product-addon' ),
			'desc'  => __( 'Enable this option to allow editing of PPOM fields in the cart.', 'woocommerce-product-addon' ),
		),
		'ppom-cart-edit-popup'           => array(
			'type'  => 'checkbox',
			'title' => __( 'Popup Edit', 'woocommerce-product-addon' ),
			'desc'  => __( 'Enable this option to open field editing for PPOM options in a popup.', 'woocommerce-product-addon' ),
		),
		'ppom_editcart_editoptions_text' => array(
			'type'    => 'text',
			'title'   => __( 'Edit Options Text', 'woocommerce-product-addon' ),
			'default' => __( 'Edit Options', 'woocommerce-product-addon' ),
			'desc'    => __( 'Change the label for the edit options button.', 'woocommerce-product-addon' ),
		),
		'ppom_edit_link_class'           => array(
			'type'  => 'text',
			'title' => __( 'Edit Button Class', 'woocommerce-product-addon' ),
			'desc'  => __( 'Add custom CSS classes to the edit button, separated by spaces.', 'woocommerce-product-addon' ),
		),
		'ppom_editcart_popup'            => array(
			'type'  => 'checkbox',
			'title' => __( 'Fields Modal', 'woocommerce-product-addon' ),
			'desc'  => __( 'This will display PPOM fields inside a modal instead of on the cart line item, keeping your cart page simple and clean.', 'woocommerce-product-addon' ),
		),
		'ppom_editcart_popup_label'      => array(
			'type'  => 'text',
			'title' => __( 'Model Button Label', 'woocommerce-product-addon' ),
			'desc'  => __( 'Change the label for the modal button.', 'woocommerce-product-addon' ),
		),
	);

	$panels = array(
		'ppom_addon_cartedit' => array(
			'id'           => 'ppom_addon_cartedit',
			'title'        => __( 'Cart Edit', 'woocommerce-product-addon' ),
			'desc'         => '',
			'icon'         => '',
			'is_available' => false,
			'is_sabpanel'  => true,
			'active'       => 'yes',
		),
	);

	PPOMSETTINGS()->register_panel( 'ppom_general_tab', $panels )->register_setting( 'ppom_addon_cartedit', $settings );

	$settings = array(
		'ppom-enf-emails-list'    => array(
			'type'  => 'text',
			'title' => __( 'Emails Recepients', 'woocommerce-product-addon' ),
			'desc'  => __( 'Enter the email addresses of the enquiry form recipients, separated by commas.', 'woocommerce-product-addon' ),
		),
		'ppom-enf-note'           => array(
			'type'    => 'textarea',
			'title'   => __( 'Note', 'woocommerce-product-addon' ),
			'desc'    => __( 'Set the message to be displayed on the enquiry form.', 'woocommerce-product-addon' ),
			'default' => 'All selected data will be sent to vendor',
		),
		'ppom-enf-send-msg'       => array(
			'type'  => 'textarea',
			'title' => __( 'Send Message', 'woocommerce-product-addon' ),
			'desc'  => __( 'Set the message to be displayed after the enquiry form is submitted.', 'woocommerce-product-addon' ),
		),
		'ppom-enf-btn-title'      => array(
			'type'  => 'text',
			'title' => __( 'Button Title', 'woocommerce-product-addon' ),
			'desc'  => __( 'Change the title of the enquiry form button.', 'woocommerce-product-addon' ),
		),
		'ppom-enf-btn-class'      => array(
			'type'  => 'text',
			'title' => __( 'Button Class', 'woocommerce-product-addon' ),
			'desc'  => __( 'Add custom CSS classes to the enquiry form button, separated by commas.', 'woocommerce-product-addon' ),
		),
		'ppom-enf-btn-text-color' => array(
			'type'  => 'color',
			'title' => __( 'Button Text Color', 'woocommerce-product-addon' ),
		),
		'ppom-enf-btn-bg-color'   => array(
			'type'  => 'color',
			'title' => __( 'Button Background Color', 'woocommerce-product-addon' ),
		),
		'ppom-enf-user-send'      => array(
			'type'  => 'checkbox',
			'title' => __( 'Send Enquiry Form Data to User', 'woocommerce-product-addon' ),
			'desc'  => __( 'Enable this option to send a copy of the enquiry form data to the user.', 'woocommerce-product-addon' ),
		),
		'ppom-enf-hide-cart'      => array(
			'type'  => 'checkbox',
			'title' => __( 'Hide Add to Cart Button', 'woocommerce-product-addon' ),
			'desc'  => __( 'Enable this option to hide the \'Add to Cart\' button on the product page.', 'woocommerce-product-addon' ),
		),
	);

	$panels = array(
		'ppom_addon_enquiryform' => array(
			'id'           => 'ppom_addon_enquiryform',
			'title'        => __( 'Enquiry Form', 'woocommerce-product-addon' ),
			'desc'         => '',
			'icon'         => '',
			'is_available' => false,
			'active'       => 'yes',
			'is_sabpanel'  => true,
		),
	);


	PPOMSETTINGS()->register_panel( 'ppom_general_tab', $panels )->register_setting( 'ppom_addon_enquiryform', $settings );


	$settings = array(
		'ppom_fieldspopup_section'       => array(
			'type'         => 'section',
			'is_available' => false,
			'title'        => __( 'Fields Popup', 'woocommerce-product-addon' ),
		),
		'ppom-fieldspopup-enable'        => array(
			'type'         => 'checkbox',
			'is_available' => false,
			'title'        => __( 'Enable Fields Popup', 'woocommerce-product-addon' ),
			'desc'         => __( 'Enable the PPOM fields popup for all products.', 'woocommerce-product-addon' ),
		),
		'ppom-fieldspopup-btn-label'     => array(
			'type'         => 'text',
			'is_available' => false,
			'title'        => __( 'Button Label', 'woocommerce-product-addon' ),
			'desc'         => __( 'Set the label for the popup button.', 'woocommerce-product-addon' ),
		),
		'ppom-fieldspopup-btn-textcolor' => array(
			'type'         => 'color',
			'is_available' => false,
			'title'        => __( 'Button Text Color', 'woocommerce-product-addon' ),
		),
		'ppom-fieldspopup-btn-bgcolor'   => array(
			'type'         => 'color',
			'is_available' => false,
			'title'        => __( 'Button Background Color', 'woocommerce-product-addon' ),
		),
	);

	PPOMSETTINGS()->register_setting( 'ppom_admin_fields_settings', $settings );

	$settings = array(
		'ppom_collapse_section'        => array(
			'type'         => 'section',
			'is_available' => false,
			'title'        => __( 'Collapse Field', 'woocommerce-product-addon' ),
		),
		'ppom-colapse-multiple-open'   => array(
			'type'         => 'checkbox',
			'is_available' => false,
			'title'        => __( 'Multiple Collapse Open', 'woocommerce-product-addon' ),
			'desc'         => __( 'Allow multiple collapsible sections to be open at the same time.', 'woocommerce-product-addon' ),
		),
		'ppom-colapse-hide-arrow'      => array(
			'type'         => 'checkbox',
			'is_available' => false,
			'title'        => __( 'Hide Headline Arrow', 'woocommerce-product-addon' ),
			'desc'         => __( 'Hide the arrow displayed under the headline.', 'woocommerce-product-addon' ),
		),
		'ppom-colapse-icon-position'   => array(
			'type'         => 'select',
			'is_available' => false,
			'title'        => __( 'Collapse Icon Position', 'woocommerce-product-addon' ),
			'default'      => 'right',
			'options'      => array(
				'left'  => 'Left',
				'right' => 'Right',
			),
			'desc'         => __( 'Control the position of the collapse icon.', 'woocommerce-product-addon' ),
		),
		'ppom-colapse-text-color'      => array(
			'type'         => 'color',
			'is_available' => false,
			'title'        => __( 'Collapse Text Color', 'woocommerce-product-addon' ),
		),
		'ppom-colapse-bg-color'        => array(
			'type'         => 'color',
			'is_available' => false,
			'title'        => __( 'Collapse Background Color', 'woocommerce-product-addon' ),
		),
		'ppom-colapse-open-text-color' => array(
			'type'         => 'color',
			'is_available' => false,
			'title'        => __( 'Collapse Open Text Color', 'woocommerce-product-addon' ),
		),
		'ppom-colapse-open-bg-color'   => array(
			'type'         => 'color',
			'is_available' => false,
			'title'        => __( 'Collapse Open Background Color', 'woocommerce-product-addon' ),
		),
		'ppom-collapse-nextprev'       => array(
			'type'         => 'checkbox',
			'is_available' => false,
			'title'        => __( 'Allow Next/Prev', 'woocommerce-product-addon' ),
			'desc'         => __( 'Allow user to show/hide collapse using prev/next button.', 'woocommerce-product-addon' ),
		),
	);

	PPOMSETTINGS()->register_setting( 'ppom_admin_fields_settings', $settings );


	$settings = array(
		'ppom_repeater_section'        => array(
			'type'         => 'section',
			'is_available' => false,
			'title'        => __( 'Field Repeater', 'woocommerce-product-addon' ),
		),
		'ppom_repeater_clone_title'    => array(
			'type'         => 'text',
			'is_available' => false,
			'title'        => __( 'Clone Title', 'woocommerce-product-addon' ),
			'desc'         => __( 'Set the title attribute for the Clone icon.', 'woocommerce-product-addon' ),
		),
		'ppom_repeater_remove_title'   => array(
			'type'         => 'text',
			'is_available' => false,
			'title'        => __( 'Remove Title', 'woocommerce-product-addon' ),
			'desc'         => __( 'Set the title attribute for the Remove icon.', 'woocommerce-product-addon' ),
		),
		'ppom_repeater_icons_size'     => array(
			'type'         => 'text',
			'is_available' => false,
			'title'        => __( 'Icons Size', 'woocommerce-product-addon' ),
			'desc'         => __( 'Icons size e.g 25px. Defaults to 20px.', 'woocommerce-product-addon' ),
		),
		'ppom_repeater_clone_mode'     => array(
			'type'         => 'select',
			'is_available' => false,
			'options'      => [
				'first_box' => 'Clone from first box only',
				'each_box'  => 'Clone from each box',
			],
			'title'        => __( 'Clone Mode', 'woocommerce-product-addon' ),
			'desc'         => __( 'How to clone the fields', 'woocommerce-product-addon' ),
		),
		'ppom_repeater_clone_position' => array(
			'type'         => 'select',
			'is_available' => false,
			'options'      => [
				'top'    => 'Top',
				'bottom' => 'Bottom',
			],
			'title'        => __( 'Clone Icons Position', 'woocommerce-product-addon' ),
			'desc'         => __( 'Set the placement of the clone icons within the repeater fields.', 'woocommerce-product-addon' ),
		),
		'ppom_repeater_icon_lib'       => array(
			'type'         => 'select',
			'is_available' => false,
			'options'      => [
				'dashicons'   => 'Dashicons',
				'fontawesome' => 'FontAwesome',
			],
			'title'        => __( 'Icons Library', 'woocommerce-product-addon' ),
			'desc'         => __( 'Select the icon library to be used for displaying icons.', 'woocommerce-product-addon' ),
		),
	);

	PPOMSETTINGS()->register_setting( 'ppom_admin_fields_settings', $settings );


	$settings = array(
		'ppom_bq_section'      => array(
			'type'         => 'section',
			'is_available' => false,
			'title'        => __( 'Bulk Quantity', 'woocommerce-product-addon' ),
		),
		'ppom-bq-display-type' => array(
			'type'         => 'select',
			'is_available' => false,
			'title'        => __( 'Display Type', 'woocommerce-product-addon' ),
			'default'      => 'Bulk Quantity Standard',
			'options'      => array(
				'bq_standard' => __( 'Bulk Quantity Standard', 'woocommerce-product-addon' ),
				'bq_packaged' => __( 'Bulk Quantity Packaged', 'woocommerce-product-addon' )
			),
			'desc'         => __( 'Choose how bulk quantities are displayed. Select \'Bulk Quantity Standard\' for standard bulk orders or \'Bulk Quantity Packaged\' for pre-packaged bulk quantities.', 'woocommerce-product-addon' ),
		)
	);

	PPOMSETTINGS()->register_setting( 'ppom_admin_fields_settings', $settings );

	$integration_settings = array();

	$integration_settings['ppom_integrations_rest'] = array(
		'type'  => 'section',
		'title' => __( 'REST API', 'woocommerce-product-addon' ),
	);
	$integration_settings['ppom_api_enable']        = array(
		'type'  => 'checkbox',
		'title' => __( 'PPOM REST API', 'woocommerce-product-addon' ),
		'desc'  => __( 'Check this option to enable PPOM REST API.', 'woocommerce-product-addon' ),
	);
	$integration_settings['ppom_rest_secret_key']   = array(
		'type'  => 'text',
		'title' => __( 'Secret Key', 'woocommerce-product-addon' ),
		'desc'  => __( 'Enter any characters to create a secret key. This key must be provided when making API requests.', 'woocommerce-product-addon' ),
	);

	$integration_settings['ppom_integrations_wcfm']  = array(
		'type'  => 'section',
		'title' => __( 'WCFM Vendors', 'woocommerce-product-addon' ),
	);
	$integration_settings['ppom_wcfm_allow_vendors'] = array(
		'type'  => 'checkbox',
		'title' => __( 'Allow Vendors to Create/Edit PPOM Fields', 'woocommerce-product-addon' ),
		'desc'  => __( 'Enable this option to allow vendors to create or edit PPOM fields. These fields will be global.', 'woocommerce-product-addon' ),
		'label' => __( 'Yes', 'woocommerce-product-addon' ),
	);
	$integration_settings['ppom_label_wcfm']         = array(
		'type'    => 'text',
		'title'   => __( 'Store Title', 'woocommerce-product-addon' ),
		'desc'    => __( 'Set the label to display on the product edit page.', 'woocommerce-product-addon' ),
		'default' => __( 'Custom Fields', 'woocommerce-product-addon' ),
		// Uncomment this section if you want to add conditions
		// 'conditions' => array(
		// array('ppom_wcfm_allow_vendors' , '==', array('true')),
		// )
	);


	$panels = array(
		'ppom_integrations' => array(
			'id'           => 'ppom_integrations',
			'is_available' => false,
			'title'        => __( 'Integrations', 'woocommerce-product-addon' ),
			'is_sabpanel'  => true,
		)
	);
	PPOMSETTINGS()->register_panel( 'ppom_general_tab', $panels )->register_setting( 'ppom_integrations', $integration_settings );

}
add_action('init', 'ppom_load_pro_options', 99);