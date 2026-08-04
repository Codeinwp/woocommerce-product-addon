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

		// Long field-group lists overflow the viewport in the toolbar submenu (#523).
		add_action(
			'wp_enqueue_scripts',
			static function () {
				if ( ! is_admin_bar_showing() || ! is_product() ) {
					return;
				}
				wp_add_inline_style(
					'admin-bar',
					'#wp-admin-bar-ppom-setting-bar .ab-sub-wrapper{max-height:50vh;overflow-y:auto}'
				);
			}
		);

		if ( ppom_is_legacy_mode() ) {
			add_action( 'woocommerce_before_add_to_cart_button', 'ppom_woocommerce_show_fields', 15 );
		} else {
			add_action( 'woocommerce_before_add_to_cart_button', 'ppom_woocommerce_inputs_template_base', 15 );
		}

		add_filter( 'ppom_input_templates_path', 'ppom_hooks_check_theme_path', 10, 3 );
	}
}
