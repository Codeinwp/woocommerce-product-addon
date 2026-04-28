<?php
/**
 * Curated PPOM field-group template library.
 *
 * Provides a registry of pre-built field-group templates for the Quick Setup
 * wizard. Each template is a complete field-schema array that can be imported
 * into a new field group through {@see \PPOM\Admin\Manager::import_template()}.
 *
 * Strings in template definitions are translator-facing.
 *
 * @package PPOM
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registry of curated PPOM field-group templates.
 */
class PPOM_Template_Library {

	/**
	 * Field types available in the free build (no `plan` key in the field
	 * builder's group definition at templates/admin/ppom-fields.php).
	 *
	 * @var array<int, string>
	 */
	const FREE_TYPES = array(
		'text',
		'textarea',
		'email',
		'number',
		'hidden',
		'checkbox',
		'radio',
		'select',
		'date',
	);

	/**
	 * Returns whether a field type requires a Pro license.
	 *
	 * @param string $type Field type slug as stored in the_meta.
	 * @return bool
	 */
	public static function is_pro_type( $type ) {
		return ! in_array( $type, self::FREE_TYPES, true );
	}

	/**
	 * Returns the lowest license category required to use every field in the
	 * given template definition.
	 *
	 * @param array<string, mixed> $template Template definition.
	 * @return int LICENSE_PLAN_FREE or LICENSE_PLAN_1.
	 */
	public static function derive_template_plan( array $template ) {
		$fields = isset( $template['fields'] ) && is_array( $template['fields'] ) ? $template['fields'] : array();
		foreach ( $fields as $field ) {
			$type = isset( $field['type'] ) ? (string) $field['type'] : '';
			if ( self::is_pro_type( $type ) ) {
				return NM_PersonalizedProduct::LICENSE_PLAN_1;
			}
		}
		return NM_PersonalizedProduct::LICENSE_PLAN_FREE;
	}

	/**
	 * Returns whether the current user can import the given template based on
	 * its derived plan and the active license status.
	 *
	 * Free templates are unrestricted. Pro templates require a `valid` license
	 * status and a plan tier at least as high as the template's derived plan.
	 *
	 * @param array<string, mixed> $template Template definition.
	 * @return bool
	 */
	public static function user_can_use( array $template ) {
		$required = self::derive_template_plan( $template );
		if ( NM_PersonalizedProduct::LICENSE_PLAN_FREE === $required ) {
			return true;
		}
		$status = apply_filters( 'product_ppom_license_status', false );
		if ( 'valid' !== $status ) {
			return false;
		}
		$plan = NM_PersonalizedProduct::get_license_category(
			intval( apply_filters( 'product_ppom_license_plan', 0 ) )
		);
		return $plan >= $required;
	}

	/**
	 * Looks up a single template definition by slug.
	 *
	 * @param string $slug Template slug.
	 * @return array<string, mixed>|null
	 */
	public static function get_template( $slug ) {
		$slug      = (string) $slug;
		$templates = self::get_templates();
		return isset( $templates[ $slug ] ) ? $templates[ $slug ] : null;
	}

	/**
	 * Returns the number of fields in a template definition.
	 *
	 * @param array<string, mixed> $template Template definition.
	 * @return int
	 */
	public static function get_field_count( array $template ) {
		$fields = isset( $template['fields'] ) && is_array( $template['fields'] ) ? $template['fields'] : array();
		return count( $fields );
	}

	/**
	 * Returns the full template registry, keyed by slug.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function get_templates() {
		static $cache = null;
		if ( null === $cache ) {
			$cache = array(
				'food-customiser'      => self::tpl_food_customiser(),
				'personalised-gift'    => self::tpl_personalised_gift(),
				'custom-print'         => self::tpl_custom_print(),
				'made-to-measure'      => self::tpl_made_to_measure(),
				'clothing-name-number' => self::tpl_clothing_name_number(),
				'print-shop'           => self::tpl_print_shop(),
				'jewellery-engraving'  => self::tpl_jewellery_engraving(),
				'service-booking-date' => self::tpl_service_booking_date(),
			);
		}
		return $cache;
	}

	/**
	 * Food Customiser — Build Your Own (Free).
	 *
	 * @return array<string, mixed>
	 */
	private static function tpl_food_customiser() {
		return array(
			'slug'        => 'food-customiser',
			'title'       => __( 'Food Customiser — Build Your Own', 'woocommerce-product-addon' ),
			'description' => __( 'Pizzas, burgers, salads. Size, base, toppings, pickup date and an optional gift message.', 'woocommerce-product-addon' ),
			'icon'        => 'fa-cutlery',
			'group_name'  => __( 'Food Customiser', 'woocommerce-product-addon' ),
			'fields'      => array(
				array(
					'type'        => 'radio',
					'title'       => __( 'Size', 'woocommerce-product-addon' ),
					'data_name'   => 'size',
					'description' => __( 'Choose a size.', 'woocommerce-product-addon' ),
					'required'    => 'on',
					'width'       => 12,
					'options'     => array(
						array(
							'option' => __( 'Small', 'woocommerce-product-addon' ),
							'price'  => '0',
						),
						array(
							'option' => __( 'Medium', 'woocommerce-product-addon' ),
							'price'  => '3',
						),
						array(
							'option' => __( 'Large', 'woocommerce-product-addon' ),
							'price'  => '6',
						),
					),
				),
				array(
					'type'        => 'radio',
					'title'       => __( 'Base', 'woocommerce-product-addon' ),
					'data_name'   => 'base',
					'description' => __( 'Pick your base.', 'woocommerce-product-addon' ),
					'required'    => 'on',
					'width'       => 12,
					'options'     => array(
						array(
							'option' => __( 'Classic', 'woocommerce-product-addon' ),
							'price'  => '0',
						),
						array(
							'option' => __( 'Wholewheat', 'woocommerce-product-addon' ),
							'price'  => '0',
						),
						array(
							'option' => __( 'Gluten-free', 'woocommerce-product-addon' ),
							'price'  => '2',
						),
					),
				),
				array(
					'type'        => 'checkbox',
					'title'       => __( 'Toppings', 'woocommerce-product-addon' ),
					'data_name'   => 'toppings',
					'description' => __( 'Add as many as you like.', 'woocommerce-product-addon' ),
					'width'       => 12,
					'options'     => array(
						array(
							'option' => __( 'Mushroom', 'woocommerce-product-addon' ),
							'price'  => '1',
						),
						array(
							'option' => __( 'Olives', 'woocommerce-product-addon' ),
							'price'  => '1',
						),
						array(
							'option' => __( 'Pepperoni', 'woocommerce-product-addon' ),
							'price'  => '1.5',
						),
						array(
							'option' => __( 'Extra cheese', 'woocommerce-product-addon' ),
							'price'  => '1.5',
						),
						array(
							'option' => __( 'Bell pepper', 'woocommerce-product-addon' ),
							'price'  => '1',
						),
					),
				),
				array(
					'type'        => 'date',
					'title'       => __( 'Pickup date', 'woocommerce-product-addon' ),
					'data_name'   => 'pickup_date',
					'description' => __( 'When should we have it ready?', 'woocommerce-product-addon' ),
					'required'    => 'on',
					'width'       => 6,
				),
				array(
					'type'      => 'checkbox',
					'title'     => __( 'Add a gift message', 'woocommerce-product-addon' ),
					'data_name' => 'add_gift_message',
					'width'     => 6,
					'options'   => array(
						array(
							'option' => __( 'Yes, include a gift message', 'woocommerce-product-addon' ),
							'price'  => '0',
						),
					),
				),
				array(
					'type'        => 'textarea',
					'title'       => __( 'Gift message', 'woocommerce-product-addon' ),
					'data_name'   => 'gift_message',
					'description' => __( 'Up to 200 characters.', 'woocommerce-product-addon' ),
					'maxlength'   => '200',
					'width'       => 12,
					'logic'       => 'on',
					'conditions'  => array(
						array(
							'field'    => 'add_gift_message',
							'operator' => 'is',
							'value'    => __( 'Yes, include a gift message', 'woocommerce-product-addon' ),
							'action'   => 'show',
						),
					),
				),
			),
		);
	}

	/**
	 * Personalised Gift (Free).
	 *
	 * @return array<string, mixed>
	 */
	private static function tpl_personalised_gift() {
		return array(
			'slug'        => 'personalised-gift',
			'title'       => __( 'Personalised Gift', 'woocommerce-product-addon' ),
			'description' => __( 'Mugs, coasters, keychains. Custom text, font, delivery date, gift wrapping and an optional card message.', 'woocommerce-product-addon' ),
			'icon'        => 'fa-gift',
			'group_name'  => __( 'Personalised Gift', 'woocommerce-product-addon' ),
			'fields'      => array(
				array(
					'type'          => 'text',
					'title'         => __( 'Personalisation text', 'woocommerce-product-addon' ),
					'data_name'     => 'personalisation_text',
					'description'   => __( 'The text to print on the gift.', 'woocommerce-product-addon' ),
					'placeholder'   => __( 'e.g. Happy Birthday Sam', 'woocommerce-product-addon' ),
					'maxlength'     => '40',
					'required'      => 'on',
					'error_message' => __( 'Please enter the personalisation text.', 'woocommerce-product-addon' ),
					'width'         => 12,
				),
				array(
					'type'      => 'select',
					'title'     => __( 'Font', 'woocommerce-product-addon' ),
					'data_name' => 'font',
					'required'  => 'on',
					'width'     => 6,
					'options'   => array(
						array(
							'option' => __( 'Serif', 'woocommerce-product-addon' ),
							'price'  => '0',
						),
						array(
							'option' => __( 'Sans-serif', 'woocommerce-product-addon' ),
							'price'  => '0',
						),
						array(
							'option' => __( 'Script', 'woocommerce-product-addon' ),
							'price'  => '0',
						),
						array(
							'option' => __( 'Handwritten', 'woocommerce-product-addon' ),
							'price'  => '0',
						),
					),
				),
				array(
					'type'        => 'date',
					'title'       => __( 'Delivery date', 'woocommerce-product-addon' ),
					'data_name'   => 'delivery_date',
					'description' => __( 'When should it arrive?', 'woocommerce-product-addon' ),
					'required'    => 'on',
					'width'       => 6,
				),
				array(
					'type'      => 'checkbox',
					'title'     => __( 'Gift wrapping', 'woocommerce-product-addon' ),
					'data_name' => 'gift_wrapping',
					'width'     => 12,
					'options'   => array(
						array(
							'option' => __( 'Add gift wrapping', 'woocommerce-product-addon' ),
							'price'  => '4',
						),
					),
				),
				array(
					'type'        => 'textarea',
					'title'       => __( 'Card message', 'woocommerce-product-addon' ),
					'data_name'   => 'card_message',
					'description' => __( 'Optional message printed on the card.', 'woocommerce-product-addon' ),
					'maxlength'   => '200',
					'width'       => 12,
					'logic'       => 'on',
					'conditions'  => array(
						array(
							'field'    => 'gift_wrapping',
							'operator' => 'is',
							'value'    => __( 'Add gift wrapping', 'woocommerce-product-addon' ),
							'action'   => 'show',
						),
					),
				),
			),
		);
	}

	/**
	 * Custom Print / Text on Product (Pro).
	 *
	 * @return array<string, mixed>
	 */
	private static function tpl_custom_print() {
		return array(
			'slug'         => 'custom-print',
			'title'        => __( 'Custom Print / Text on Product', 'woocommerce-product-addon' ),
			'description'  => __( 'Print custom text on a product. Choose font, placement and optionally upload artwork.', 'woocommerce-product-addon' ),
			'icon'         => 'fa-print',
			'group_name'   => __( 'Custom Print', 'woocommerce-product-addon' ),
			'uses_feature' => __( 'Uses File Upload for artwork', 'woocommerce-product-addon' ),
			'fields'       => array(
				array(
					'type'        => 'text',
					'title'       => __( 'Print text', 'woocommerce-product-addon' ),
					'data_name'   => 'print_text',
					'placeholder' => __( 'Up to 40 characters', 'woocommerce-product-addon' ),
					'maxlength'   => '40',
					'required'    => 'on',
					'width'       => 12,
				),
				array(
					'type'      => 'select',
					'title'     => __( 'Font', 'woocommerce-product-addon' ),
					'data_name' => 'print_font',
					'required'  => 'on',
					'width'     => 6,
					'options'   => array(
						array(
							'option' => __( 'Arial', 'woocommerce-product-addon' ),
							'price'  => '0',
						),
						array(
							'option' => __( 'Helvetica', 'woocommerce-product-addon' ),
							'price'  => '0',
						),
						array(
							'option' => __( 'Times New Roman', 'woocommerce-product-addon' ),
							'price'  => '0',
						),
						array(
							'option' => __( 'Courier', 'woocommerce-product-addon' ),
							'price'  => '0',
						),
					),
				),
				array(
					'type'      => 'radio',
					'title'     => __( 'Placement', 'woocommerce-product-addon' ),
					'data_name' => 'placement',
					'required'  => 'on',
					'width'     => 6,
					'options'   => array(
						array(
							'option' => __( 'Front', 'woocommerce-product-addon' ),
							'price'  => '0',
						),
						array(
							'option' => __( 'Back', 'woocommerce-product-addon' ),
							'price'  => '0',
						),
						array(
							'option' => __( 'Both sides', 'woocommerce-product-addon' ),
							'price'  => '5',
						),
					),
				),
				array(
					'type'      => 'checkbox',
					'title'     => __( 'Upload artwork', 'woocommerce-product-addon' ),
					'data_name' => 'upload_artwork_toggle',
					'width'     => 12,
					'options'   => array(
						array(
							'option' => __( 'I want to upload my own artwork', 'woocommerce-product-addon' ),
							'price'  => '0',
						),
					),
				),
				array(
					'type'        => 'file',
					'title'       => __( 'Artwork file', 'woocommerce-product-addon' ),
					'data_name'   => 'artwork_file',
					'description' => __( 'PNG, JPG or PDF. Up to 10MB.', 'woocommerce-product-addon' ),
					'width'       => 12,
					'logic'       => 'on',
					'conditions'  => array(
						array(
							'field'    => 'upload_artwork_toggle',
							'operator' => 'is',
							'value'    => __( 'I want to upload my own artwork', 'woocommerce-product-addon' ),
							'action'   => 'show',
						),
					),
				),
			),
		);
	}

	/**
	 * Made-to-Measure / Dimensions Pricing (Pro).
	 *
	 * @return array<string, mixed>
	 */
	private static function tpl_made_to_measure() {
		return array(
			'slug'         => 'made-to-measure',
			'title'        => __( 'Made-to-Measure / Dimensions Pricing', 'woocommerce-product-addon' ),
			'description'  => __( 'Curtains, blinds, panels. Width × height in selected units, material and fitting options.', 'woocommerce-product-addon' ),
			'icon'         => 'fa-arrows-alt',
			'group_name'   => __( 'Made-to-Measure', 'woocommerce-product-addon' ),
			'uses_feature' => __( 'Uses Measure Input with price-per-unit pricing', 'woocommerce-product-addon' ),
			'fields'       => array(
				array(
					'type'      => 'radio',
					'title'     => __( 'Unit', 'woocommerce-product-addon' ),
					'data_name' => 'measure_unit',
					'required'  => 'on',
					'width'     => 12,
					'options'   => array(
						array(
							'option' => 'cm',
							'price'  => '0',
						),
						array(
							'option' => 'inches',
							'price'  => '0',
						),
						array(
							'option' => 'mm',
							'price'  => '0',
						),
					),
				),
				array(
					'type'      => 'measure',
					'title'     => __( 'Width', 'woocommerce-product-addon' ),
					'data_name' => 'width',
					'required'  => 'on',
					'width'     => 6,
					'options'   => array(
						array(
							'option' => __( 'Width', 'woocommerce-product-addon' ),
							'price'  => '0.50',
						),
					),
				),
				array(
					'type'      => 'measure',
					'title'     => __( 'Height', 'woocommerce-product-addon' ),
					'data_name' => 'height',
					'required'  => 'on',
					'width'     => 6,
					'options'   => array(
						array(
							'option' => __( 'Height', 'woocommerce-product-addon' ),
							'price'  => '0.50',
						),
					),
				),
				array(
					'type'      => 'select',
					'title'     => __( 'Material', 'woocommerce-product-addon' ),
					'data_name' => 'material',
					'required'  => 'on',
					'width'     => 6,
					'options'   => array(
						array(
							'option' => __( 'Cotton', 'woocommerce-product-addon' ),
							'price'  => '0',
						),
						array(
							'option' => __( 'Linen', 'woocommerce-product-addon' ),
							'price'  => '15',
						),
						array(
							'option' => __( 'Velvet', 'woocommerce-product-addon' ),
							'price'  => '30',
						),
						array(
							'option' => __( 'Blackout', 'woocommerce-product-addon' ),
							'price'  => '20',
						),
					),
				),
				array(
					'type'      => 'radio',
					'title'     => __( 'Fitting', 'woocommerce-product-addon' ),
					'data_name' => 'fitting',
					'required'  => 'on',
					'width'     => 6,
					'options'   => array(
						array(
							'option' => __( 'Self-fit', 'woocommerce-product-addon' ),
							'price'  => '0',
						),
						array(
							'option' => __( 'Professional fitting', 'woocommerce-product-addon' ),
							'price'  => '50',
						),
					),
				),
			),
		);
	}

	/**
	 * Clothing with Name & Number (Pro).
	 *
	 * @return array<string, mixed>
	 */
	private static function tpl_clothing_name_number() {
		return array(
			'slug'         => 'clothing-name-number',
			'title'        => __( 'Clothing with Name & Number', 'woocommerce-product-addon' ),
			'description'  => __( 'Sports jerseys and team kits. Size, colour, print method, custom name and number.', 'woocommerce-product-addon' ),
			'icon'         => 'fa-trophy',
			'group_name'   => __( 'Clothing — Name & Number', 'woocommerce-product-addon' ),
			'uses_feature' => __( 'Uses one-time fees for personalisation', 'woocommerce-product-addon' ),
			'fields'       => array(
				array(
					'type'      => 'radio',
					'title'     => __( 'Size', 'woocommerce-product-addon' ),
					'data_name' => 'size',
					'required'  => 'on',
					'width'     => 4,
					'options'   => array(
						array(
							'option' => 'XS',
							'price'  => '0',
						),
						array(
							'option' => 'S',
							'price'  => '0',
						),
						array(
							'option' => 'M',
							'price'  => '0',
						),
						array(
							'option' => 'L',
							'price'  => '0',
						),
						array(
							'option' => 'XL',
							'price'  => '2',
						),
						array(
							'option' => 'XXL',
							'price'  => '4',
						),
					),
				),
				array(
					'type'      => 'select',
					'title'     => __( 'Colour', 'woocommerce-product-addon' ),
					'data_name' => 'colour',
					'required'  => 'on',
					'width'     => 4,
					'options'   => array(
						array(
							'option' => __( 'Red', 'woocommerce-product-addon' ),
							'price'  => '0',
						),
						array(
							'option' => __( 'Blue', 'woocommerce-product-addon' ),
							'price'  => '0',
						),
						array(
							'option' => __( 'Black', 'woocommerce-product-addon' ),
							'price'  => '0',
						),
						array(
							'option' => __( 'White', 'woocommerce-product-addon' ),
							'price'  => '0',
						),
					),
				),
				array(
					'type'      => 'radio',
					'title'     => __( 'Print method', 'woocommerce-product-addon' ),
					'data_name' => 'print_method',
					'required'  => 'on',
					'width'     => 4,
					'options'   => array(
						array(
							'option' => __( 'Heat transfer', 'woocommerce-product-addon' ),
							'price'  => '0',
						),
						array(
							'option' => __( 'Embroidery', 'woocommerce-product-addon' ),
							'price'  => '8',
						),
					),
				),
				array(
					'type'        => 'text',
					'title'       => __( 'Name on shirt', 'woocommerce-product-addon' ),
					'data_name'   => 'name_on_shirt',
					'placeholder' => __( 'Up to 12 characters', 'woocommerce-product-addon' ),
					'maxlength'   => '12',
					'price'       => '5',
					'onetime'     => 'on',
					'width'       => 6,
				),
				array(
					'type'        => 'number',
					'title'       => __( 'Number on shirt', 'woocommerce-product-addon' ),
					'data_name'   => 'number_on_shirt',
					'placeholder' => '0-99',
					'minlength'   => '0',
					'maxlength'   => '99',
					'price'       => '5',
					'onetime'     => 'on',
					'width'       => 6,
				),
			),
		);
	}

	/**
	 * Print Shop / Artwork Upload (Pro).
	 *
	 * @return array<string, mixed>
	 */
	private static function tpl_print_shop() {
		return array(
			'slug'         => 'print-shop',
			'title'        => __( 'Print Shop / Artwork Upload', 'woocommerce-product-addon' ),
			'description'  => __( 'Print products. Finish, turnaround pricing, optional design service and an artwork upload.', 'woocommerce-product-addon' ),
			'icon'         => 'fa-file-image-o',
			'group_name'   => __( 'Print Shop', 'woocommerce-product-addon' ),
			'uses_feature' => __( 'Uses File Upload and turnaround pricing', 'woocommerce-product-addon' ),
			'fields'       => array(
				array(
					'type'      => 'select',
					'title'     => __( 'Finish', 'woocommerce-product-addon' ),
					'data_name' => 'finish',
					'required'  => 'on',
					'width'     => 6,
					'options'   => array(
						array(
							'option' => __( 'Matte', 'woocommerce-product-addon' ),
							'price'  => '0',
						),
						array(
							'option' => __( 'Gloss', 'woocommerce-product-addon' ),
							'price'  => '0',
						),
						array(
							'option' => __( 'Soft-touch', 'woocommerce-product-addon' ),
							'price'  => '5',
						),
					),
				),
				array(
					'type'      => 'select',
					'title'     => __( 'Turnaround', 'woocommerce-product-addon' ),
					'data_name' => 'turnaround',
					'required'  => 'on',
					'width'     => 6,
					'options'   => array(
						array(
							'option' => __( 'Standard (5 days)', 'woocommerce-product-addon' ),
							'price'  => '0',
						),
						array(
							'option' => __( 'Express (2 days)', 'woocommerce-product-addon' ),
							'price'  => '15',
						),
						array(
							'option' => __( 'Same-day', 'woocommerce-product-addon' ),
							'price'  => '40',
						),
					),
				),
				array(
					'type'      => 'radio',
					'title'     => __( 'Design service', 'woocommerce-product-addon' ),
					'data_name' => 'design_service',
					'required'  => 'on',
					'width'     => 12,
					'options'   => array(
						array(
							'option' => __( 'I have my own artwork', 'woocommerce-product-addon' ),
							'price'  => '0',
						),
						array(
							'option' => __( 'Need design help', 'woocommerce-product-addon' ),
							'price'  => '50',
						),
					),
				),
				array(
					'type'        => 'file',
					'title'       => __( 'Upload artwork', 'woocommerce-product-addon' ),
					'data_name'   => 'artwork',
					'description' => __( 'PDF, AI or PNG. Up to 25MB.', 'woocommerce-product-addon' ),
					'width'       => 12,
				),
			),
		);
	}

	/**
	 * Jewellery / Engraving (Pro).
	 *
	 * @return array<string, mixed>
	 */
	private static function tpl_jewellery_engraving() {
		return array(
			'slug'         => 'jewellery-engraving',
			'title'        => __( 'Jewellery / Engraving', 'woocommerce-product-addon' ),
			'description'  => __( 'Rings and pendants. Metal with price tiers, ring size and optional engraving text and font.', 'woocommerce-product-addon' ),
			'icon'         => 'fa-diamond',
			'group_name'   => __( 'Jewellery / Engraving', 'woocommerce-product-addon' ),
			'uses_feature' => __( 'Uses metal price tiers and conditional engraving fields', 'woocommerce-product-addon' ),
			'fields'       => array(
				array(
					'type'      => 'radio',
					'title'     => __( 'Metal', 'woocommerce-product-addon' ),
					'data_name' => 'metal',
					'required'  => 'on',
					'width'     => 12,
					'options'   => array(
						array(
							'option' => __( 'Sterling silver', 'woocommerce-product-addon' ),
							'price'  => '0',
						),
						array(
							'option' => __( '9ct gold', 'woocommerce-product-addon' ),
							'price'  => '120',
						),
						array(
							'option' => __( '18ct gold', 'woocommerce-product-addon' ),
							'price'  => '320',
						),
						array(
							'option' => __( 'Platinum', 'woocommerce-product-addon' ),
							'price'  => '480',
						),
					),
				),
				array(
					'type'      => 'select',
					'title'     => __( 'Ring size', 'woocommerce-product-addon' ),
					'data_name' => 'ring_size',
					'required'  => 'on',
					'width'     => 6,
					'options'   => array(
						array(
							'option' => 'H',
							'price'  => '0',
						),
						array(
							'option' => 'I',
							'price'  => '0',
						),
						array(
							'option' => 'J',
							'price'  => '0',
						),
						array(
							'option' => 'K',
							'price'  => '0',
						),
						array(
							'option' => 'L',
							'price'  => '0',
						),
						array(
							'option' => 'M',
							'price'  => '0',
						),
						array(
							'option' => 'N',
							'price'  => '0',
						),
						array(
							'option' => 'O',
							'price'  => '0',
						),
						array(
							'option' => 'P',
							'price'  => '0',
						),
					),
				),
				array(
					'type'      => 'checkbox',
					'title'     => __( 'Add engraving', 'woocommerce-product-addon' ),
					'data_name' => 'engrave_toggle',
					'width'     => 6,
					'options'   => array(
						array(
							'option' => __( 'Engrave this piece', 'woocommerce-product-addon' ),
							'price'  => '20',
						),
					),
				),
				array(
					'type'        => 'text',
					'title'       => __( 'Engraving text', 'woocommerce-product-addon' ),
					'data_name'   => 'engraving_text',
					'placeholder' => __( 'Up to 20 characters', 'woocommerce-product-addon' ),
					'maxlength'   => '20',
					'width'       => 6,
					'logic'       => 'on',
					'conditions'  => array(
						array(
							'field'    => 'engrave_toggle',
							'operator' => 'is',
							'value'    => __( 'Engrave this piece', 'woocommerce-product-addon' ),
							'action'   => 'show',
						),
					),
				),
				array(
					'type'       => 'select',
					'title'      => __( 'Engraving font', 'woocommerce-product-addon' ),
					'data_name'  => 'engraving_font',
					'width'      => 6,
					'options'    => array(
						array(
							'option' => __( 'Block', 'woocommerce-product-addon' ),
							'price'  => '0',
						),
						array(
							'option' => __( 'Script', 'woocommerce-product-addon' ),
							'price'  => '0',
						),
						array(
							'option' => __( 'Roman', 'woocommerce-product-addon' ),
							'price'  => '0',
						),
					),
					'logic'      => 'on',
					'conditions' => array(
						array(
							'field'    => 'engrave_toggle',
							'operator' => 'is',
							'value'    => __( 'Engrave this piece', 'woocommerce-product-addon' ),
							'action'   => 'show',
						),
					),
				),
			),
		);
	}

	/**
	 * Service Booking with Date (Pro).
	 *
	 * @return array<string, mixed>
	 */
	private static function tpl_service_booking_date() {
		return array(
			'slug'         => 'service-booking-date',
			'title'        => __( 'Service Booking with Date', 'woocommerce-product-addon' ),
			'description'  => __( 'On-site services. Service type, booking date, time slot and optional address and phone.', 'woocommerce-product-addon' ),
			'icon'         => 'fa-calendar-check-o',
			'group_name'   => __( 'Service Booking', 'woocommerce-product-addon' ),
			'uses_feature' => __( 'Uses conditional fields tied to service type', 'woocommerce-product-addon' ),
			'fields'       => array(
				array(
					'type'      => 'radio',
					'title'     => __( 'Service type', 'woocommerce-product-addon' ),
					'data_name' => 'service_type',
					'required'  => 'on',
					'width'     => 12,
					'options'   => array(
						array(
							'option' => __( 'Standard', 'woocommerce-product-addon' ),
							'price'  => '0',
						),
						array(
							'option' => __( 'Premium', 'woocommerce-product-addon' ),
							'price'  => '40',
						),
						array(
							'option' => __( 'On-site visit', 'woocommerce-product-addon' ),
							'price'  => '80',
						),
					),
				),
				array(
					'type'      => 'date',
					'title'     => __( 'Booking date', 'woocommerce-product-addon' ),
					'data_name' => 'booking_date',
					'required'  => 'on',
					'width'     => 6,
				),
				array(
					'type'      => 'select',
					'title'     => __( 'Time slot', 'woocommerce-product-addon' ),
					'data_name' => 'time_slot',
					'required'  => 'on',
					'width'     => 6,
					'options'   => array(
						array(
							'option' => __( 'Morning (9:00 — 12:00)', 'woocommerce-product-addon' ),
							'price'  => '0',
						),
						array(
							'option' => __( 'Afternoon (12:00 — 17:00)', 'woocommerce-product-addon' ),
							'price'  => '0',
						),
						array(
							'option' => __( 'Evening (17:00 — 20:00)', 'woocommerce-product-addon' ),
							'price'  => '15',
						),
					),
				),
				array(
					'type'        => 'text',
					'title'       => __( 'Address', 'woocommerce-product-addon' ),
					'data_name'   => 'address',
					'description' => __( 'Required for on-site visits.', 'woocommerce-product-addon' ),
					'width'       => 6,
					'logic'       => 'on',
					'conditions'  => array(
						array(
							'field'    => 'service_type',
							'operator' => 'is',
							'value'    => __( 'On-site visit', 'woocommerce-product-addon' ),
							'action'   => 'show',
						),
					),
				),
				array(
					'type'        => 'text',
					'title'       => __( 'Phone', 'woocommerce-product-addon' ),
					'data_name'   => 'phone',
					'description' => __( 'So we can confirm the visit.', 'woocommerce-product-addon' ),
					'width'       => 6,
					'logic'       => 'on',
					'conditions'  => array(
						array(
							'field'    => 'service_type',
							'operator' => 'is',
							'value'    => __( 'On-site visit', 'woocommerce-product-addon' ),
							'action'   => 'show',
						),
					),
				),
			),
		);
	}
}
