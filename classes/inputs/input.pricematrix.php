<?php
/**
 * Price matrix field type for PPOM product options.
 *
 * @package PPOM
 * @subpackage Inputs
 */

/**
 * Quantity-tiered pricing matrix with optional slider, discounts, and per-unit display options.
 */
class NM_PriceMatrix_wooproduct extends PPOM_Inputs {

	/*
	 * input control settings
	 */
	var $title, $desc, $settings;

	/*
	 * this var is pouplated with current plugin meta
	*/
	var $plugin_meta;

	/**
	 * Registers metadata and loads the field settings schema.
	 *
	 * @return void
	 */
	function __construct() {

		$this->plugin_meta = ppom_get_plugin_meta();

		$this->title    = __( 'Price Matrix', 'woocommerce-product-addon' );
		$this->desc     = __( 'Price/Quantity', 'woocommerce-product-addon' );
		$this->icon     = '<i class="fa fa-usd" aria-hidden="true"></i>';
		$this->settings = self::get_settings();
	}

	/**
	 * Builder setting definitions keyed by field option name (type, title, description, and UI hints).
	 *
	 * @return array<string, mixed>
	 */
	private function get_settings() {

		$input_meta = array(
			'title'               => array(
				'type'  => 'text',
				'title' => __( 'Title', 'woocommerce-product-addon' ),
				'desc'  => __( 'It will as section heading wrapped in h2', 'woocommerce-product-addon' ),
			),
			'data_name'           => array(
				'type'  => 'text',
				'title' => __( 'Data name', 'woocommerce-product-addon' ),
				'desc'  => __( 'REQUIRED: The identification name of this field, that you can insert into body email configuration. Note:Use only lowercase characters and underscores.', 'woocommerce-product-addon' ),
			),
			'description'         => array(
				'type'  => 'textarea',
				'title' => __( 'Description', 'woocommerce-product-addon' ),
				'desc'  => __( 'Type description, it will be display under section heading.', 'woocommerce-product-addon' ),
			),
			'discount_type'       => array(
				'type'        => 'select',
				'title'       => __( 'Discount On?', 'woocommerce-product-addon' ),
				'desc'        => __( 'Select discount option.', 'woocommerce-product-addon' ),
				'options'     => array(
					'both' => 'Base & Option',
					'base' => 'Only Base',
				),
				'col_classes' => array( 'col-md-3', 'col-sm-12' ),
			),
			'options'             => array(
				'type'  => 'paired-pricematrix',
				'title' => __( 'Price matrix', 'woocommerce-product-addon' ),
				'desc'  => __( 'Type quantity range with price.', 'woocommerce-product-addon' ),
			),
			'qty_step'            => array(
				'type'        => 'text',
				'title'       => __( 'Quantity Step', 'woocommerce-product-addon' ),
				'desc'        => __( 'Quantity step e.g: 3', 'woocommerce-product-addon' ),
				'col_classes' => array( 'col-md-3', 'col-sm-12' ),
			),
			'visibility'          => array(
				'type'    => 'select',
				'title'   => __( 'Visibility', 'woocommerce-product-addon' ),
				'desc'    => __( 'Set field visibility based on user.', 'woocommerce-product-addon' ),
				'options' => ppom_field_visibility_options(),
				'default' => 'everyone',
			),
			'visibility_role'     => array(
				'type'   => 'text',
				'title'  => __( 'User Roles', 'woocommerce-product-addon' ),
				'desc'   => __( 'Role separated by comma.', 'woocommerce-product-addon' ),
				'hidden' => true,
			),
			'discount'            => array(
				'type'        => 'checkbox',
				'title'       => __( 'Apply as discount', 'woocommerce-product-addon' ),
				'desc'        => __( 'Check for Apply as discount', 'woocommerce-product-addon' ),
				'col_classes' => array( 'col-md-3', 'col-sm-12' ),
			),
			'show_slider'         => array(
				'type'        => 'checkbox',
				'title'       => __( 'Enable Quantity Slider', 'woocommerce-product-addon' ),
				'desc'        => __( 'It will display Range slider for quantity under matrix', 'woocommerce-product-addon' ),
				'col_classes' => array( 'col-md-3', 'col-sm-12' ),
			),
			'show_price_per_unit' => array(
				'type'        => 'checkbox',
				'title'       => __( 'Show price per unit?', 'woocommerce-product-addon' ),
				'desc'        => __( 'It will calculate price against per unit and show along total', 'woocommerce-product-addon' ),
				'col_classes' => array( 'col-md-3', 'col-sm-12' ),
			),
			'hide_matrix_table'   => array(
				'type'        => 'checkbox',
				'title'       => __( 'Hide Price Matrix?', 'woocommerce-product-addon' ),
				'desc'        => __( 'Price Matrix table will be hidden', 'woocommerce-product-addon' ),
				'col_classes' => array( 'col-md-3', 'col-sm-12' ),
			),
			'desc_tooltip'        => array(
				'type'        => 'checkbox',
				'title'       => __( 'Show tooltip', 'woocommerce-product-addon' ),
				'desc'        => __( 'Show Description in Tooltip with Help Icon', 'woocommerce-product-addon' ),
				'col_classes' => array( 'col-md-3', 'col-sm-12' ),
			),
			'logic'               => array(
				'type'  => 'checkbox',
				'title' => __( 'Enable Conditions', 'woocommerce-product-addon' ),
				'desc'  => __( 'Tick it to turn conditional logic to work below', 'woocommerce-product-addon' ),
			),
			'conditions'          => array(
				'type'  => 'html-conditions',
				'title' => __( 'Conditions', 'woocommerce-product-addon' ),
				'desc'  => __( 'Set rules to show or hide the field based on specific conditions', 'woocommerce-product-addon' ),
			),
		);

		$type = 'pricematrix';

		return apply_filters( "poom_{$type}_input_setting", $input_meta, $this );
	}
}
