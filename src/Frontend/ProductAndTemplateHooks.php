<?php
/**
 * Product page rendering and input template path hooks.
 *
 * @package PPOM
 */

namespace PPOM\Frontend;

/**
 * @since 33.0.19
 */
final class ProductAndTemplateHooks {

	/**
	 * @return void
	 */
	public static function register(): void {
		add_action( 'admin_bar_menu', 'ppom_admin_bar_menu', 1000 );

		if ( ppom_is_legacy_mode() ) {
			add_action( 'woocommerce_before_add_to_cart_button', 'ppom_woocommerce_show_fields', 15 );
		} else {
			add_action( 'woocommerce_before_add_to_cart_button', 'ppom_woocommerce_inputs_template_base', 15 );
		}

		add_filter( 'ppom_input_templates_path', 'ppom_hooks_check_theme_path', 10, 3 );
	}
}
