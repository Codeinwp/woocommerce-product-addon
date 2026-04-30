<?php
/**
 * Cart, checkout, pricing session, and order line-item hooks for PPOM.
 *
 * @package PPOM
 */

namespace PPOM\Cart;

use PPOM\Pricing\PriceMode;

/**
 * @since 33.0.19
 */
final class WooCommerceCartLifecycleHooks {

	/**
	 * @return void
	 */
	public static function register(): void {
		if ( ! ppom_is_client_validation_enabled() ) {
			add_filter( 'woocommerce_add_to_cart_validation', 'ppom_woocommerce_validate_product', 10, 3 );
		}

		add_filter( 'woocommerce_add_cart_item_data', 'ppom_woocommerce_add_cart_item_data', 10, 2 );

		if ( PriceMode::is_legacy() ) {

			add_filter( 'woocommerce_get_cart_item_from_session', 'ppom_woocommerce_update_cart_fees', 10, 2 );
			add_action( 'woocommerce_cart_calculate_fees', 'ppom_woocommerce_add_fixed_fee' );
		} else {

			add_filter( 'woocommerce_get_cart_item_from_session', 'ppom_price_check_price_matrix', 8, 2 );

			add_filter( 'woocommerce_get_cart_item_from_session', 'ppom_price_controller', 10, 2 );

			add_action( 'woocommerce_cart_calculate_fees', 'ppom_price_cart_fee' );
			add_action( 'ppom_before_calculate_cart_total', 'ppom_hooks_update_cart_weight', 10, 3 );
		}

		add_action( 'woocommerce_cart_loaded_from_session', 'ppom_calculate_totals_from_session' );

		add_action( 'woocommerce_widget_shopping_cart_before_buttons', 'ppom_woocommerce_mini_cart_fixed_fee' );

		add_filter( 'woocommerce_get_price_html', 'ppom_woocommerce_alter_price', 10, 2 );

		add_filter( 'woocommerce_quantity_input_args', 'ppom_validation_product_limits', 10, 2 );
		add_filter( 'woocommerce_available_variation', 'ppom_validation_variation_limits', 10, 3 );

		add_filter( 'woocommerce_get_item_data', 'ppom_woocommerce_add_item_meta', 10, 2 );

		add_filter( 'woocommerce_add_to_cart_quantity', 'ppom_woocommerce_add_to_cart_quantity', 10, 2 );

		add_filter( 'woocommerce_add_to_cart_redirect', 'ppom_hooks_redirect_to_cart_if_shortcode', 10, 1 );

		if ( PriceMode::is_legacy() ) {
			add_filter( 'woocommerce_cart_item_quantity', 'ppom_woocommerce_control_cart_quantity_legacy', 10, 2 );
		} else {
			add_filter( 'woocommerce_cart_item_quantity', 'ppom_woocommerce_control_cart_quantity', 99, 2 );
		}

		add_filter( 'woocommerce_cart_item_subtotal', 'ppom_woocommerce_item_subtotal', 10, 3 );
		add_filter( 'woocommerce_checkout_cart_item_quantity', 'ppom_woocommerce_control_checkout_quantity', 10, 3 );
		add_filter( 'woocommerce_order_item_quantity_html', 'ppom_woocommerce_control_oder_item_quantity', 10, 2 );
		add_filter( 'woocommerce_email_order_item_quantity', 'ppom_woocommerce_control_email_item_quantity', 10, 2 );

		add_filter( 'woocommerce_update_cart_validation', 'ppom_woocommerce_cart_update_validate', 10, 4 );

		add_action( 'woocommerce_checkout_create_order_line_item', 'ppom_woocommerce_order_item_meta', 99, 4 );

		add_filter( 'woocommerce_order_item_display_meta_key', 'ppom_woocommerce_order_key', 10, 3 );

		add_filter( 'woocommerce_order_item_display_meta_value', 'ppom_woocommerce_order_value', 10, 3 );
		if ( ppom_wc_email_improvements_enabled() ) {
			add_action( 'woocommerce_order_item_meta_end', 'ppom_woocommerce_order_item_meta_html', 10, 2 );
			add_filter( 'woocommerce_display_item_meta', '__return_empty_string' );
			add_filter( 'wpo_ips_display_item_meta_html', 'ppom_invoice_packing_slips_html', 10, 3 );
		}
		add_filter( 'woocommerce_order_item_get_formatted_meta_data', 'ppom_woocommerce_hide_order_meta', 10, 2 );
		add_filter( 'woocommerce_is_attribute_in_product_name', '__return_false' );

		add_action( 'woocommerce_checkout_order_processed', 'ppom_woocommerce_rename_files', 10, 3 );
	}
}
