<?php
/**
 * PPOM Template Library
 * Curated field group templates based on real customer usage data.
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not Allowed' );
}

class PPOM_Template_Library {

	private static $ins;

	public static function get_instance() {
		is_null( self::$ins ) && self::$ins = new self();
		return self::$ins;
	}

	function __construct() {
		add_action( 'wp_ajax_ppom_get_templates', array( $this, 'ajax_get_templates' ) );
		add_action( 'wp_ajax_ppom_import_template', array( $this, 'ajax_import_template' ) );
	}

	/**
	 * Get all available templates.
	 * Based on analysis of real PPOM customer usage patterns and support data.
	 */
	public static function get_templates() {
		$templates = array(

			// ──────────────────────────────────────────────
			// Template 1: Custom Print / Text on Product
			// ──────────────────────────────────────────────
			'custom-print-text' => array(
				'name'         => __( 'Custom Print / Text on Product', 'woocommerce-product-addon' ),
				'description'  => __( 'T-shirts, mugs, stickers, laser engraving — personalisation text, font, colour, placement, and artwork upload.', 'woocommerce-product-addon' ),
				'icon'         => 'dashicons-edit',
				'category'     => 'personalisation',
				'pro'          => true,
				'pro_features' => __( 'Uses File Upload for artwork', 'woocommerce-product-addon' ),
				'fields'      => array(
					array(
						'type'          => 'radio',
						'title'         => 'Personalisation Type',
						'data_name'     => 'personalisation_type',
						'description'   => 'Choose how you want to personalise this product',
						'required'      => 'on',
						'error_message' => 'Please select a personalisation type',
						'width'         => '12',
						'status'        => 'on',
						'options'       => array(
							array( 'option' => 'Custom Text', 'price' => '', 'id' => 'custom_text' ),
							array( 'option' => 'Upload My Own Design', 'price' => '', 'id' => 'upload_design' ),
						),
					),
					array(
						'type'          => 'text',
						'title'         => 'Personalisation Text',
						'data_name'     => 'personalisation_text',
						'description'   => 'Enter the text you want printed on the product',
						'placeholder'   => 'e.g., Happy Birthday Sarah!',
						'required'      => 'on',
						'error_message' => 'Please enter your personalisation text',
						'maxlength'     => '50',
						'price'         => '5',
						'onetime'       => 'on',
						'width'         => '12',
						'status'        => 'on',
						'logic'         => 'on',
						'conditions'    => array(
							'rules' => array(
								array( 'field_name' => 'personalisation_type', 'operator' => 'is', 'value' => 'Custom Text' ),
							),
							'visibility' => 'show',
							'relation'   => 'OR',
						),
					),
					array(
						'type'        => 'select',
						'title'       => 'Font Style',
						'data_name'   => 'font_style',
						'description' => 'Choose a font for your text',
						'required'    => '',
						'width'       => '6',
						'status'      => 'on',
						'options'     => array(
							array( 'option' => 'Classic Serif', 'price' => '', 'id' => 'serif' ),
							array( 'option' => 'Modern Sans', 'price' => '', 'id' => 'sans' ),
							array( 'option' => 'Handwritten Script', 'price' => '2', 'id' => 'script' ),
							array( 'option' => 'Bold Block', 'price' => '', 'id' => 'block' ),
						),
						'desc_tooltip' => 'on',
						'logic'        => 'on',
						'conditions'   => array(
							'rules' => array(
								array( 'field_name' => 'personalisation_type', 'operator' => 'is', 'value' => 'Custom Text' ),
							),
							'visibility' => 'show',
							'relation'   => 'OR',
						),
					),
					array(
						'type'        => 'select',
						'title'       => 'Placement',
						'data_name'   => 'placement',
						'description' => 'Where should we place the personalisation?',
						'required'    => 'on',
						'width'       => '6',
						'status'      => 'on',
						'options'     => array(
							array( 'option' => 'Front Centre', 'price' => '', 'id' => 'front_centre' ),
							array( 'option' => 'Back Centre', 'price' => '3', 'id' => 'back_centre' ),
							array( 'option' => 'Front + Back', 'price' => '7', 'id' => 'front_back' ),
							array( 'option' => 'Left Chest', 'price' => '', 'id' => 'left_chest' ),
						),
					),
					array(
						'type'          => 'file',
						'title'         => 'Upload Your Artwork',
						'data_name'     => 'artwork_upload',
						'description'   => 'Accepted formats: PNG, JPG, PDF, AI, EPS. Max 10MB.',
						'required'      => '',
						'price'         => '',
						'width'         => '12',
						'status'        => 'on',
						'logic'         => 'on',
						'conditions'    => array(
							'rules' => array(
								array( 'field_name' => 'personalisation_type', 'operator' => 'is', 'value' => 'Upload My Own Design' ),
							),
							'visibility' => 'show',
							'relation'   => 'OR',
						),
					),
					array(
						'type'        => 'textarea',
						'title'       => 'Design Notes',
						'data_name'   => 'design_notes',
						'placeholder' => 'Any special instructions for placement, sizing, or design preferences...',
						'required'    => '',
						'maxlength'   => '300',
						'width'       => '12',
						'status'      => 'on',
						'desc_tooltip' => 'on',
					),
				),
			),

			// ──────────────────────────────────────────────
			// Template 2: Made-to-Measure / Dimensions Pricing
			// ──────────────────────────────────────────────
			'made-to-measure' => array(
				'name'         => __( 'Made-to-Measure / Dimensions Pricing', 'woocommerce-product-addon' ),
				'description'  => __( 'Curtains, blinds, carpet, fabric — width × height pricing with material options and fitting services.', 'woocommerce-product-addon' ),
				'icon'         => 'dashicons-image-crop',
				'category'     => 'pricing',
				'pro'          => true,
				'pro_features' => __( 'Uses Measure Input with Price Formula', 'woocommerce-product-addon' ),
				'fields'      => array(
					array(
						'type'          => 'radio',
						'title'         => 'Measurement Unit',
						'data_name'     => 'measurement_unit',
						'description'   => 'Choose the unit for your measurements',
						'required'      => 'on',
						'width'         => '12',
						'status'        => 'on',
						'default_value' => 'cm',
						'options'       => array(
							array( 'option' => 'Centimetres (cm)', 'price' => '', 'id' => 'cm' ),
							array( 'option' => 'Inches', 'price' => '', 'id' => 'inches' ),
							array( 'option' => 'Millimetres (mm)', 'price' => '', 'id' => 'mm' ),
						),
					),
					array(
						'type'             => 'measure',
						'title'            => 'Width (cm)',
						'data_name'        => 'width_cm',
						'description'      => 'Enter the width in centimetres',
						'placeholder'      => 'e.g., 120',
						'required'         => 'on',
						'error_message'    => 'Please enter the width',
						'min'              => '30',
						'max'              => '500',
						'step'             => '1',
						'price'            => '2.50',
						'price-multiplier' => '0.01',
						'width'            => '6',
						'status'           => 'on',
					),
					array(
						'type'             => 'measure',
						'title'            => 'Height (cm)',
						'data_name'        => 'height_cm',
						'description'      => 'Enter the height/drop in centimetres',
						'placeholder'      => 'e.g., 180',
						'required'         => 'on',
						'error_message'    => 'Please enter the height',
						'min'              => '30',
						'max'              => '500',
						'step'             => '1',
						'price-multiplier' => '0.01',
						'width'            => '6',
						'status'           => 'on',
					),
					array(
						'type'          => 'select',
						'title'         => 'Material / Fabric',
						'data_name'     => 'material',
						'description'   => 'Different materials affect the final price',
						'required'      => 'on',
						'error_message' => 'Please select a material',
						'width'         => '6',
						'status'        => 'on',
						'options'       => array(
							array( 'option' => 'Cotton (Standard)', 'price' => '', 'id' => 'cotton' ),
							array( 'option' => 'Polyester Blend', 'price' => '5', 'id' => 'polyester' ),
							array( 'option' => 'Linen', 'price' => '12', 'id' => 'linen' ),
							array( 'option' => 'Velvet (Premium)', 'price' => '25', 'id' => 'velvet' ),
							array( 'option' => 'Silk (Luxury)', 'price' => '40', 'id' => 'silk' ),
						),
					),
					array(
						'type'        => 'checkbox',
						'title'       => 'Additional Options',
						'data_name'   => 'additional_options',
						'description' => 'Optional extras',
						'required'    => '',
						'width'       => '6',
						'status'      => 'on',
						'options'     => array(
							array( 'option' => 'Blackout Lining', 'price' => '15', 'id' => 'blackout' ),
							array( 'option' => 'Thermal Lining', 'price' => '12', 'id' => 'thermal' ),
							array( 'option' => 'Tiebacks Included', 'price' => '8', 'id' => 'tiebacks' ),
						),
					),
					array(
						'type'      => 'checkbox',
						'title'     => 'Fitting Service',
						'data_name' => 'fitting_service',
						'required'  => '',
						'width'     => '12',
						'status'    => 'on',
						'onetime'   => 'on',
						'options'   => array(
							array( 'option' => 'Professional fitting service', 'price' => '45', 'id' => 'fitting' ),
						),
					),
				),
			),

			// ──────────────────────────────────────────────
			// Template 3: Food Customiser — Build Your Own
			// ──────────────────────────────────────────────
			'food-customiser' => array(
				'name'        => __( 'Food Customiser — Build Your Own', 'woocommerce-product-addon' ),
				'description' => __( 'Pizza, burgers, cakes, chocolates — size, base, toppings with prices, delivery date, and gift message.', 'woocommerce-product-addon' ),
				'icon'        => 'dashicons-carrot',
				'category'    => 'food',
				'fields'      => array(
					array(
						'type'          => 'radio',
						'title'         => 'Size',
						'data_name'     => 'size',
						'description'   => 'Choose your size',
						'required'      => 'on',
						'error_message' => 'Please select a size',
						'width'         => '6',
						'status'        => 'on',
						'options'       => array(
							array( 'option' => 'Small', 'price' => '', 'id' => 'small' ),
							array( 'option' => 'Medium', 'price' => '3', 'id' => 'medium' ),
							array( 'option' => 'Large', 'price' => '6', 'id' => 'large' ),
							array( 'option' => 'Family', 'price' => '10', 'id' => 'family' ),
						),
					),
					array(
						'type'          => 'radio',
						'title'         => 'Base / Crust',
						'data_name'     => 'base_type',
						'description'   => 'Select your base',
						'required'      => 'on',
						'error_message' => 'Please select a base',
						'width'         => '6',
						'status'        => 'on',
						'options'       => array(
							array( 'option' => 'Classic', 'price' => '', 'id' => 'classic' ),
							array( 'option' => 'Thin & Crispy', 'price' => '', 'id' => 'thin' ),
							array( 'option' => 'Stuffed Crust', 'price' => '2.50', 'id' => 'stuffed' ),
							array( 'option' => 'Gluten-Free', 'price' => '3', 'id' => 'glutenfree' ),
						),
					),
					array(
						'type'        => 'checkbox',
						'title'       => 'Toppings / Extras',
						'data_name'   => 'toppings',
						'description' => 'Add as many as you like',
						'required'    => '',
						'width'       => '12',
						'status'      => 'on',
						'options'     => array(
							array( 'option' => 'Pepperoni', 'price' => '1.50', 'id' => 'pepperoni' ),
							array( 'option' => 'Mushrooms', 'price' => '1', 'id' => 'mushrooms' ),
							array( 'option' => 'Bell Peppers', 'price' => '1', 'id' => 'peppers' ),
							array( 'option' => 'Onions', 'price' => '0.75', 'id' => 'onions' ),
							array( 'option' => 'Olives', 'price' => '1', 'id' => 'olives' ),
							array( 'option' => 'Extra Cheese', 'price' => '1.50', 'id' => 'extra_cheese' ),
							array( 'option' => 'Jalapeños', 'price' => '0.75', 'id' => 'jalapenos' ),
							array( 'option' => 'Bacon', 'price' => '2', 'id' => 'bacon' ),
						),
					),
					array(
						'type'        => 'date',
						'title'       => 'Delivery / Pickup Date',
						'data_name'   => 'delivery_date',
						'description' => 'When do you need this?',
						'required'    => 'on',
						'width'       => '6',
						'status'      => 'on',
					),
					array(
						'type'        => 'checkbox',
						'title'       => 'Gift Option',
						'data_name'   => 'is_gift',
						'required'    => '',
						'width'       => '6',
						'status'      => 'on',
						'options'     => array(
							array( 'option' => 'This is a gift', 'price' => '', 'id' => 'is_gift' ),
						),
					),
					array(
						'type'        => 'textarea',
						'title'       => 'Gift Message',
						'data_name'   => 'gift_message',
						'placeholder' => 'Write a personal message for the recipient...',
						'required'    => '',
						'maxlength'   => '200',
						'width'       => '12',
						'status'      => 'on',
						'logic'       => 'on',
						'conditions'  => array(
							'rules' => array(
								array( 'field_name' => 'is_gift', 'operator' => 'is', 'value' => 'This is a gift' ),
							),
							'visibility' => 'show',
							'relation'   => 'OR',
						),
					),
					array(
						'type'        => 'textarea',
						'title'       => 'Special Instructions',
						'data_name'   => 'special_instructions',
						'placeholder' => 'Allergies, dietary requirements, or special requests...',
						'required'    => '',
						'maxlength'   => '300',
						'width'       => '12',
						'status'      => 'on',
					),
				),
			),

			// ──────────────────────────────────────────────
			// Template 4: Clothing with Name/Number
			// ──────────────────────────────────────────────
			'clothing-name-number' => array(
				'name'         => __( 'Clothing with Name & Number', 'woocommerce-product-addon' ),
				'description'  => __( 'Sports jerseys, personalised apparel — size, colour, player name, number, and optional logo upload.', 'woocommerce-product-addon' ),
				'icon'         => 'dashicons-tag',
				'category'     => 'apparel',
				'pro'          => true,
				'pro_features' => __( 'Uses conditional pricing formulas', 'woocommerce-product-addon' ),
				'fields'      => array(
					array(
						'type'          => 'select',
						'title'         => 'Size',
						'data_name'     => 'garment_size',
						'required'      => 'on',
						'error_message' => 'Please select a size',
						'width'         => '4',
						'status'        => 'on',
						'options'       => array(
							array( 'option' => 'XS', 'price' => '', 'id' => 'xs' ),
							array( 'option' => 'S', 'price' => '', 'id' => 's' ),
							array( 'option' => 'M', 'price' => '', 'id' => 'm' ),
							array( 'option' => 'L', 'price' => '', 'id' => 'l' ),
							array( 'option' => 'XL', 'price' => '2', 'id' => 'xl' ),
							array( 'option' => '2XL', 'price' => '4', 'id' => '2xl' ),
							array( 'option' => '3XL', 'price' => '6', 'id' => '3xl' ),
						),
					),
					array(
						'type'          => 'select',
						'title'         => 'Colour',
						'data_name'     => 'garment_colour',
						'required'      => 'on',
						'error_message' => 'Please select a colour',
						'width'         => '4',
						'status'        => 'on',
						'options'       => array(
							array( 'option' => 'White', 'price' => '', 'id' => 'white' ),
							array( 'option' => 'Black', 'price' => '', 'id' => 'black' ),
							array( 'option' => 'Navy', 'price' => '', 'id' => 'navy' ),
							array( 'option' => 'Red', 'price' => '', 'id' => 'red' ),
							array( 'option' => 'Grey', 'price' => '', 'id' => 'grey' ),
							array( 'option' => 'Royal Blue', 'price' => '', 'id' => 'royal_blue' ),
						),
					),
					array(
						'type'          => 'select',
						'title'         => 'Print Method',
						'data_name'     => 'print_method',
						'required'      => 'on',
						'width'         => '4',
						'status'        => 'on',
						'options'       => array(
							array( 'option' => 'Screen Print (Standard)', 'price' => '', 'id' => 'screen' ),
							array( 'option' => 'Embroidery', 'price' => '5', 'id' => 'embroidery' ),
							array( 'option' => 'Vinyl Transfer', 'price' => '3', 'id' => 'vinyl' ),
						),
					),
					array(
						'type'        => 'text',
						'title'       => 'Name on Back',
						'data_name'   => 'player_name',
						'description' => 'Leave blank if not required',
						'placeholder' => 'e.g., SMITH',
						'required'    => '',
						'maxlength'   => '20',
						'price'       => '5',
						'onetime'     => 'on',
						'width'       => '6',
						'status'      => 'on',
					),
					array(
						'type'          => 'number',
						'title'         => 'Number on Back',
						'data_name'     => 'player_number',
						'description'   => 'Leave blank if not required',
						'placeholder'   => 'e.g., 10',
						'required'      => '',
						'price'         => '5',
						'onetime'       => 'on',
						'width'         => '6',
						'status'        => 'on',
					),
					array(
						'type'        => 'textarea',
						'title'       => 'Additional Notes',
						'data_name'   => 'clothing_notes',
						'placeholder' => 'Any special requests or instructions...',
						'required'    => '',
						'maxlength'   => '200',
						'width'       => '12',
						'status'      => 'on',
					),
				),
			),

			// ──────────────────────────────────────────────
			// Template 5: Print Shop / Artwork Upload
			// ──────────────────────────────────────────────
			'print-shop-upload' => array(
				'name'         => __( 'Print Shop / Artwork Upload', 'woocommerce-product-addon' ),
				'description'  => __( 'Business cards, flyers, banners — product type, quantity pricing, finish, turnaround time, and artwork upload.', 'woocommerce-product-addon' ),
				'icon'         => 'dashicons-format-image',
				'category'     => 'print',
				'pro'          => true,
				'pro_features' => __( 'Uses File Upload and Price Matrix', 'woocommerce-product-addon' ),
				'fields'      => array(
					array(
						'type'          => 'select',
						'title'         => 'Paper / Finish',
						'data_name'     => 'paper_finish',
						'required'      => 'on',
						'error_message' => 'Please select a finish',
						'width'         => '6',
						'status'        => 'on',
						'options'       => array(
							array( 'option' => 'Matte (Standard)', 'price' => '', 'id' => 'matte' ),
							array( 'option' => 'Gloss', 'price' => '2', 'id' => 'gloss' ),
							array( 'option' => 'Silk', 'price' => '3', 'id' => 'silk' ),
							array( 'option' => 'Uncoated / Recycled', 'price' => '1', 'id' => 'uncoated' ),
						),
					),
					array(
						'type'          => 'radio',
						'title'         => 'Turnaround Time',
						'data_name'     => 'turnaround',
						'description'   => 'Faster turnaround incurs additional charges',
						'required'      => 'on',
						'error_message' => 'Please select a turnaround time',
						'width'         => '6',
						'status'        => 'on',
						'options'       => array(
							array( 'option' => 'Standard (5 working days)', 'price' => '', 'id' => 'standard' ),
							array( 'option' => 'Express (2 working days)', 'price' => '15', 'id' => 'express' ),
							array( 'option' => 'Same Day (order before 12pm)', 'price' => '35', 'id' => 'sameday' ),
						),
					),
					array(
						'type'        => 'checkbox',
						'title'       => 'Design Service',
						'data_name'   => 'design_service',
						'description' => 'Need help with your design?',
						'required'    => '',
						'width'       => '12',
						'status'      => 'on',
						'onetime'     => 'on',
						'options'     => array(
							array( 'option' => 'I need professional design help', 'price' => '25', 'id' => 'design_help' ),
						),
					),
					array(
						'type'        => 'file',
						'title'       => 'Upload Artwork File',
						'data_name'   => 'artwork_file',
						'description' => 'Upload print-ready artwork. Accepted: PDF, AI, EPS, PNG, JPG. Max 25MB.',
						'required'    => '',
						'width'       => '12',
						'status'      => 'on',
					),
					array(
						'type'        => 'textarea',
						'title'       => 'Artwork / Design Notes',
						'data_name'   => 'artwork_notes',
						'placeholder' => 'Describe your design, include colours, text, branding requirements...',
						'required'    => '',
						'maxlength'   => '500',
						'width'       => '12',
						'status'      => 'on',
					),
				),
			),

			// ──────────────────────────────────────────────
			// Template 6: Personalised Gift
			// ──────────────────────────────────────────────
			'personalised-gift' => array(
				'name'        => __( 'Personalised Gift', 'woocommerce-product-addon' ),
				'description' => __( 'Gift wrapping, message card, recipient name, delivery date — with conditional fields that build on each other.', 'woocommerce-product-addon' ),
				'icon'        => 'dashicons-heart',
				'category'    => 'gifts',
				'fields'      => array(
					array(
						'type'        => 'text',
						'title'       => 'Personalisation Text',
						'data_name'   => 'gift_personalisation',
						'description' => 'Text to be added to the product',
						'placeholder' => 'e.g., To Sarah, with love',
						'required'    => 'on',
						'maxlength'   => '40',
						'price'       => '3',
						'onetime'     => 'on',
						'width'       => '12',
						'status'      => 'on',
					),
					array(
						'type'        => 'select',
						'title'       => 'Font',
						'data_name'   => 'gift_font',
						'required'    => '',
						'width'       => '6',
						'status'      => 'on',
						'options'     => array(
							array( 'option' => 'Elegant Script', 'price' => '', 'id' => 'script' ),
							array( 'option' => 'Classic Serif', 'price' => '', 'id' => 'serif' ),
							array( 'option' => 'Modern Sans', 'price' => '', 'id' => 'sans' ),
							array( 'option' => 'Playful Handwritten', 'price' => '', 'id' => 'handwritten' ),
						),
					),
					array(
						'type'        => 'date',
						'title'       => 'Required By Date',
						'data_name'   => 'required_by_date',
						'description' => 'Allow at least 3 working days for personalisation',
						'required'    => 'on',
						'width'       => '6',
						'status'      => 'on',
					),
					array(
						'type'        => 'checkbox',
						'title'       => 'Gift Wrapping',
						'data_name'   => 'gift_wrapping_options',
						'description' => 'Make it extra special',
						'required'    => '',
						'width'       => '12',
						'status'      => 'on',
						'onetime'     => 'on',
						'options'     => array(
							array( 'option' => 'Gift Wrapping', 'price' => '3.50', 'id' => 'wrapping' ),
							array( 'option' => 'Premium Gift Box', 'price' => '8', 'id' => 'giftbox' ),
							array( 'option' => 'Add Ribbon & Bow', 'price' => '2', 'id' => 'ribbon' ),
						),
					),
					array(
						'type'        => 'textarea',
						'title'       => 'Gift Card Message',
						'data_name'   => 'gift_card_message',
						'placeholder' => 'Write a personal message to include with the gift...',
						'required'    => '',
						'maxlength'   => '200',
						'width'       => '12',
						'status'      => 'on',
						'logic'       => 'on',
						'conditions'  => array(
							'rules' => array(
								array( 'field_name' => 'gift_wrapping_options', 'operator' => 'any', 'value' => '' ),
							),
							'visibility' => 'show',
							'relation'   => 'OR',
						),
					),
					array(
						'type'        => 'text',
						'title'       => 'Recipient Name (for card)',
						'data_name'   => 'recipient_name',
						'placeholder' => 'Who is this gift for?',
						'required'    => '',
						'maxlength'   => '50',
						'width'       => '6',
						'status'      => 'on',
						'logic'       => 'on',
						'conditions'  => array(
							'rules' => array(
								array( 'field_name' => 'gift_wrapping_options', 'operator' => 'any', 'value' => '' ),
							),
							'visibility' => 'show',
							'relation'   => 'OR',
						),
					),
				),
			),

			// ──────────────────────────────────────────────
			// Template 7: Jewellery / Engraving
			// ──────────────────────────────────────────────
			'jewellery-engraving' => array(
				'name'         => __( 'Jewellery / Engraving', 'woocommerce-product-addon' ),
				'description'  => __( 'Rings, bracelets, watches — metal choice, ring size, engraving text and font, gift box option.', 'woocommerce-product-addon' ),
				'icon'         => 'dashicons-admin-customizer',
				'category'     => 'personalisation',
				'pro'          => true,
				'pro_features' => __( 'Uses Image Select and conditional pricing', 'woocommerce-product-addon' ),
				'fields'      => array(
					array(
						'type'          => 'select',
						'title'         => 'Metal / Material',
						'data_name'     => 'metal_type',
						'required'      => 'on',
						'error_message' => 'Please select a metal type',
						'width'         => '6',
						'status'        => 'on',
						'options'       => array(
							array( 'option' => 'Sterling Silver', 'price' => '', 'id' => 'silver' ),
							array( 'option' => 'Gold Plated', 'price' => '15', 'id' => 'gold_plated' ),
							array( 'option' => '9ct Gold', 'price' => '45', 'id' => '9ct_gold' ),
							array( 'option' => '18ct Gold', 'price' => '95', 'id' => '18ct_gold' ),
							array( 'option' => 'Rose Gold', 'price' => '25', 'id' => 'rose_gold' ),
						),
					),
					array(
						'type'          => 'select',
						'title'         => 'Ring Size',
						'data_name'     => 'ring_size',
						'description'   => 'Use our size guide if unsure',
						'required'      => 'on',
						'error_message' => 'Please select a ring size',
						'width'         => '6',
						'status'        => 'on',
						'desc_tooltip'  => 'on',
						'options'       => array(
							array( 'option' => 'F (Small)', 'price' => '', 'id' => 'f' ),
							array( 'option' => 'H', 'price' => '', 'id' => 'h' ),
							array( 'option' => 'J', 'price' => '', 'id' => 'j' ),
							array( 'option' => 'L (Medium)', 'price' => '', 'id' => 'l' ),
							array( 'option' => 'N', 'price' => '', 'id' => 'n' ),
							array( 'option' => 'P', 'price' => '', 'id' => 'p' ),
							array( 'option' => 'R (Large)', 'price' => '', 'id' => 'r' ),
							array( 'option' => 'T', 'price' => '', 'id' => 't' ),
							array( 'option' => 'V', 'price' => '', 'id' => 'v' ),
						),
					),
					array(
						'type'        => 'checkbox',
						'title'       => 'Add Engraving',
						'data_name'   => 'add_engraving',
						'required'    => '',
						'width'       => '12',
						'status'      => 'on',
						'onetime'     => 'on',
						'options'     => array(
							array( 'option' => 'Yes, add engraving', 'price' => '8', 'id' => 'yes_engrave' ),
						),
					),
					array(
						'type'          => 'text',
						'title'         => 'Engraving Text',
						'data_name'     => 'engraving_text',
						'placeholder'   => 'Max 25 characters',
						'required'      => 'on',
						'error_message' => 'Please enter your engraving text',
						'maxlength'     => '25',
						'width'         => '6',
						'status'        => 'on',
						'logic'         => 'on',
						'conditions'    => array(
							'rules' => array(
								array( 'field_name' => 'add_engraving', 'operator' => 'is', 'value' => 'Yes, add engraving' ),
							),
							'visibility' => 'show',
							'relation'   => 'OR',
						),
					),
					array(
						'type'      => 'select',
						'title'     => 'Engraving Font',
						'data_name' => 'engraving_font',
						'required'  => '',
						'width'     => '6',
						'status'    => 'on',
						'options'   => array(
							array( 'option' => 'Script', 'price' => '', 'id' => 'script' ),
							array( 'option' => 'Block Capitals', 'price' => '', 'id' => 'block' ),
							array( 'option' => 'Italic', 'price' => '', 'id' => 'italic' ),
						),
						'logic'      => 'on',
						'conditions' => array(
							'rules' => array(
								array( 'field_name' => 'add_engraving', 'operator' => 'is', 'value' => 'Yes, add engraving' ),
							),
							'visibility' => 'show',
							'relation'   => 'OR',
						),
					),
					array(
						'type'      => 'checkbox',
						'title'     => 'Presentation',
						'data_name' => 'presentation',
						'required'  => '',
						'width'     => '12',
						'status'    => 'on',
						'onetime'   => 'on',
						'options'   => array(
							array( 'option' => 'Luxury Gift Box', 'price' => '6', 'id' => 'luxury_box' ),
							array( 'option' => 'Polishing Cloth', 'price' => '3', 'id' => 'polish' ),
						),
					),
				),
			),

			// ──────────────────────────────────────────────
			// Template 8: Service Booking with Date
			// ──────────────────────────────────────────────
			'service-booking' => array(
				'name'         => __( 'Service Booking with Date', 'woocommerce-product-addon' ),
				'description'  => __( 'Appointments, services, deliveries — service type, date, time slot, location, duration, and contact details.', 'woocommerce-product-addon' ),
				'icon'         => 'dashicons-calendar-alt',
				'category'     => 'services',
				'pro'          => true,
				'pro_features' => __( 'Uses DateRange and Price Formula', 'woocommerce-product-addon' ),
				'fields'      => array(
					array(
						'type'          => 'select',
						'title'         => 'Service Type',
						'data_name'     => 'service_type',
						'description'   => 'Select the service you require',
						'required'      => 'on',
						'error_message' => 'Please select a service type',
						'width'         => '6',
						'status'        => 'on',
						'options'       => array(
							array( 'option' => 'Standard Service', 'price' => '', 'id' => 'standard' ),
							array( 'option' => 'Premium Service', 'price' => '25', 'id' => 'premium' ),
							array( 'option' => 'VIP / Priority', 'price' => '50', 'id' => 'vip' ),
						),
					),
					array(
						'type'          => 'date',
						'title'         => 'Preferred Date',
						'data_name'     => 'service_date',
						'description'   => 'Select your preferred appointment date',
						'required'      => 'on',
						'error_message' => 'Please select a date',
						'width'         => '6',
						'status'        => 'on',
					),
					array(
						'type'          => 'select',
						'title'         => 'Preferred Time Slot',
						'data_name'     => 'time_slot',
						'required'      => 'on',
						'error_message' => 'Please select a time slot',
						'width'         => '6',
						'status'        => 'on',
						'options'       => array(
							array( 'option' => 'Morning (9am–12pm)', 'price' => '', 'id' => 'morning' ),
							array( 'option' => 'Afternoon (12pm–5pm)', 'price' => '', 'id' => 'afternoon' ),
							array( 'option' => 'Evening (5pm–8pm)', 'price' => '10', 'id' => 'evening' ),
						),
					),
					array(
						'type'        => 'number',
						'title'       => 'Duration (hours)',
						'data_name'   => 'duration_hours',
						'description' => 'How many hours do you need?',
						'placeholder' => 'e.g., 2',
						'required'    => 'on',
						'width'       => '6',
						'status'      => 'on',
						'default_value' => '1',
					),
					array(
						'type'        => 'checkbox',
						'title'       => 'On-Site Service',
						'data_name'   => 'onsite_service',
						'required'    => '',
						'width'       => '12',
						'status'      => 'on',
						'options'     => array(
							array( 'option' => 'Yes, come to my location', 'price' => '20', 'id' => 'onsite_yes' ),
						),
					),
					array(
						'type'          => 'text',
						'title'         => 'Address / Location',
						'data_name'     => 'service_address',
						'placeholder'   => 'Enter the full address for on-site service',
						'required'      => 'on',
						'error_message' => 'Please enter the service location address',
						'width'         => '12',
						'status'        => 'on',
						'logic'         => 'on',
						'conditions'    => array(
							'rules' => array(
								array( 'field_name' => 'onsite_service', 'operator' => 'is', 'value' => 'Yes, come to my location' ),
							),
							'visibility' => 'show',
							'relation'   => 'OR',
						),
					),
					array(
						'type'          => 'text',
						'title'         => 'Contact Phone',
						'data_name'     => 'contact_phone',
						'description'   => 'In case we need to reach you about the appointment',
						'placeholder'   => 'Your phone number',
						'required'      => 'on',
						'error_message' => 'Please enter a contact phone number',
						'width'         => '6',
						'status'        => 'on',
						'desc_tooltip'  => 'on',
					),
					array(
						'type'        => 'textarea',
						'title'       => 'Special Requirements',
						'data_name'   => 'special_requirements',
						'placeholder' => 'Any specific needs, accessibility requirements, or other information...',
						'required'    => '',
						'maxlength'   => '300',
						'width'       => '12',
						'status'      => 'on',
					),
				),
			),

		);

		return apply_filters( 'ppom_template_library', $templates );
	}

	/**
	 * AJAX: Get templates list.
	 */
	public function ajax_get_templates() {
		check_ajax_referer( 'ppom_ai_nonce', 'nonce' );

		if ( ! ppom_security_role() ) {
			wp_send_json_error( __( 'Permission denied.', 'woocommerce-product-addon' ) );
		}

		wp_send_json_success( self::get_templates() );
	}

	/**
	 * AJAX: Import a template as a new field group.
	 */
	public function ajax_import_template() {
		check_ajax_referer( 'ppom_ai_nonce', 'nonce' );

		if ( ! ppom_security_role() ) {
			wp_send_json_error( __( 'Permission denied.', 'woocommerce-product-addon' ) );
		}

		$template_id = isset( $_POST['template_id'] ) ? sanitize_text_field( $_POST['template_id'] ) : '';
		$fields_json = isset( $_POST['fields'] ) ? wp_unslash( $_POST['fields'] ) : '';
		$group_name  = isset( $_POST['group_name'] ) ? sanitize_text_field( $_POST['group_name'] ) : '';
		$custom_css  = isset( $_POST['css'] ) ? wp_unslash( $_POST['css'] ) : '';

		// If a template_id is provided, use the built-in template
		if ( ! empty( $template_id ) ) {
			$templates = self::get_templates();
			if ( ! isset( $templates[ $template_id ] ) ) {
				wp_send_json_error( __( 'Template not found.', 'woocommerce-product-addon' ) );
			}
			$template   = $templates[ $template_id ];
			$group_name = empty( $group_name ) ? $template['name'] : $group_name;
			$fields     = $template['fields'];
		} elseif ( ! empty( $fields_json ) ) {
			// Custom fields (from AI wizard)
			$fields = json_decode( $fields_json, true );
			if ( ! is_array( $fields ) ) {
				wp_send_json_error( __( 'Invalid fields data.', 'woocommerce-product-addon' ) );
			}
		} else {
			wp_send_json_error( __( 'No template or fields provided.', 'woocommerce-product-addon' ) );
		}

		if ( empty( $group_name ) ) {
			$group_name = __( 'Imported Template', 'woocommerce-product-addon' );
		}

		// Insert into the PPOM table
		global $wpdb;
		$ppom_table = $wpdb->prefix . PPOM_TABLE_META;

		// First insert to get the ID
		$insert_data = array(
			'productmeta_name'       => $group_name,
			'productmeta_validation' => 'yes',
			'the_meta'               => '[]',
			'productmeta_created'    => current_time( 'mysql' ),
		);
		$insert_format = array( '%s', '%s', '%s', '%s' );

		if ( ! empty( $custom_css ) ) {
			$insert_data['productmeta_style'] = $custom_css;
			$insert_format[] = '%s';
		}

		$result = $wpdb->insert(
			$ppom_table,
			$insert_data,
			$insert_format
		);

		if ( false === $result ) {
			wp_send_json_error( __( 'Failed to save template. Database error.', 'woocommerce-product-addon' ) );
		}

		$new_id = $wpdb->insert_id;

		// Re-index fields starting from 1, normalize AI output
		$indexed_fields = array();
		$i = 1;
		foreach ( $fields as $field ) {
			$indexed_fields[ $i ] = PPOM_AI_Service::normalize_field( $field );
			$i++;
		}

		// Run through the same filter the normal admin save uses — this injects ppom_id into each field
		$indexed_fields = apply_filters( 'ppom_meta_data_saving', $indexed_fields, $new_id );
		$indexed_fields = ppom_sanitize_array_data( $indexed_fields );

		// Update with the complete fields data including ppom_id
		ppom_admin_update_ppom_meta_only( $new_id, $indexed_fields );

		wp_send_json_success( array(
			'message'  => sprintf( __( 'Template "%s" imported successfully!', 'woocommerce-product-addon' ), $group_name ),
			'group_id' => $new_id,
			'edit_url' => admin_url( 'admin.php?page=ppom&do_meta=edit&productmeta_id=' . $new_id ),
		) );
	}
}

PPOM_Template_Library::get_instance();
