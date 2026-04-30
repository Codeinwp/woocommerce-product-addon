<?php
/**
 * Modern mode: recalculates the cart line price from PPOM field prices (session restore path).
 *
 * @package PPOM
 */

namespace PPOM\Pricing;

/**
 * @since 33.0.19
 */
final class ModernLineItemPricing {

	/**
	 * Apply PPOM pricing to a cart line restored from session.
	 *
	 * @param array<string, mixed> $cart_item Cart item.
	 * @param array<string, mixed> $values    Raw cart values (includes ppom payload).
	 * @return array<string, mixed>
	 */
	public static function apply_to_cart_item( $cart_item, $values ) {
		if ( empty( $cart_item ) ) {
			return $cart_item;
		}

		if ( ! isset( $values['ppom']['fields'] ) ) {
			return $cart_item;
		}

		$wc_product        = $cart_item['data'];
		$product_id        = ppom_get_product_id( $wc_product );
		$variation_id      = isset( $cart_item['variation_id'] ) ? $cart_item['variation_id'] : '';
		$ppom_fields_post  = $values['ppom']['fields'];
		$product_quantity  = floatval( $cart_item['quantity'] );
		$ppom_field_prices = ppom_get_field_prices( $ppom_fields_post, $product_id, $product_quantity, $variation_id, $cart_item );
		$ppom_discount     = 0;
		$ppom_pricematrix  = isset( $cart_item['ppom']['price_matrix_found'] ) ? $cart_item['ppom']['price_matrix_found'] : null;

		$total_addon_price    = ppom_price_get_addon_total( $ppom_field_prices );
		$total_cart_fee_price = ppom_price_get_cart_fee_total( $ppom_field_prices );

		$product_price = apply_filters( 'ppom_product_price_on_cart', $wc_product->get_price(), $cart_item );

		$price_info         = ppom_price_get_product_base(
			$product_price,
			$wc_product,
			$ppom_fields_post,
			$product_quantity,
			$ppom_field_prices,
			$ppom_discount,
			$ppom_pricematrix
		);
		$product_base_price = $price_info['price'];

		$ppom_total_price = floatval( $total_addon_price ) + floatval( $product_base_price ) - $ppom_discount;

		do_action( 'ppom_before_calculate_cart_total', $ppom_field_prices, $ppom_fields_post, $cart_item );

		$ppom_total_price = apply_filters( 'ppom_cart_line_total', $ppom_total_price, $cart_item, $values );
		$wc_product->set_price( $ppom_total_price );

		return $cart_item;
	}
}
