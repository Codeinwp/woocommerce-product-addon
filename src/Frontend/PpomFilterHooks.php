<?php
/**
 * PPOM-specific filters (field rendering, cart item shaping, shortcode).
 *
 * @package PPOM
 */

namespace PPOM\Frontend;

/**
 * @since 33.0.19
 */
final class PpomFilterHooks {

	/**
	 * @return void
	 */
	public static function register(): void {
		add_filter( 'ppom_field_attributes', 'ppom_hooks_set_attributes', 10, 2 );
		add_filter( 'ppom_field_setting', 'ppom_hooks_input_args', 10, 3 );
		add_filter( 'ppom_has_posted_field_value', 'ppom_hooks_checkbox_valided', 10, 3 );
		add_filter( 'nmform_attribute_value', 'ppom_hooks_color_to_text_type', 10, 3 );
		add_filter( 'ppom_show_option_price', 'ppom_hooks_show_option_price_pricematrix', 10, 2 );
		add_filter( 'ppom_meta_data_saving', 'ppom_hooks_register_wpml', 10, 2 );

		if ( ppom_get_conditions_mode() === 'new' ) {
			add_filter( 'ppom_input_wrapper_class', 'ppom_hooks_input_wrapper_class_new', 10, 2 );
			add_filter( 'ppom_field_main_wrapper_class', 'ppom_hooks_input_main_wrapper_class', 10, 3 );
		} else {
			add_filter( 'ppom_input_wrapper_class', 'ppom_hooks_input_wrapper_class', 10, 2 );
		}

		add_filter( 'ppom_add_cart_item_data', 'ppom_hooks_save_cropped_image', 10, 2 );
		add_filter( 'ppom_order_display_value', 'ppom_hooks_format_order_value', 999, 3 );
		add_filter( 'ppom_option_price_operator', 'ppom_hooks_set_option_operator', 99, 3 );

		add_filter( 'ppom_option_meta', 'update_converted_option_keys', 9, 5 );

		add_shortcode( 'ppom', 'ppom_hooks_render_shortcode' );
	}
}
