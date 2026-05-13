<?php
/**
 * Modern mode: registers WooCommerce fees for matrix discounts and PPOM cart_fee options.
 *
 * @package PPOM
 */

namespace PPOM\Pricing;

/**
 * @since 33.0.19
 */
final class ModernCartFeeApplicator {

	/**
	 * Add PPOM-derived fees to the cart (matrix discount + cart_fee priced fields).
	 *
	 * @param \WC_Cart $cart WooCommerce cart.
	 * @return void
	 */
	public static function apply_to_cart( $cart ): void {
		$fee_no       = 1;
		$cart_counter = 1;
		foreach ( $cart->get_cart() as $item ) {

			if ( ! isset( $item['ppom']['fields'] ) ) {
				continue;
			}

			$product           = $item['data'];
			$ppom_fields_post  = $item['ppom']['fields'];
			$product_id        = $item['product_id'];
			$variation_id      = isset( $item['variation_id'] ) ? $item['variation_id'] : '';
			$quantity          = $item['quantity'];
			$ppom_field_prices = ppom_get_field_prices( $ppom_fields_post, $product_id, $quantity, $variation_id, $item );

			$cart_item_price = floatval( $product->get_price() );

			if ( $matrix_found = ppom_price_has_discount_matrix( $product, $quantity ) ) {

				$native_product      = wc_get_product( $product_id );
				$price_tobe_discount = ppom_get_product_price( $native_product ) * $quantity;
				if ( $matrix_found['discount'] == 'both' ) {
					$total_addon_price    = ppom_price_get_addon_total( $ppom_field_prices );
					$total_cart_fee_price = ppom_price_get_cart_fee_total( $ppom_field_prices );
					$price_tobe_discount  = ( $cart_item_price * $quantity ) + $total_cart_fee_price;
				}

				if ( ! empty( $matrix_found['percent'] ) ) {
					$matrix_discount = ppom_get_amount_after_percentage( $price_tobe_discount, $matrix_found['percent'] );
				} else {
					$matrix_discount = $matrix_found['raw_price'];
				}
				$discount_label   = $cart_counter . '-' . $matrix_found['label'];
				$matrix_discount  = floatval( $matrix_discount );
				$discount_taxable = apply_filters( 'ppom_matrix_discount_taxable', false, $item, $cart );
				$cart->add_fee( esc_html( $discount_label ), - $matrix_discount, $discount_taxable );
			}

			foreach ( $ppom_field_prices as $fee ) {

				if ( $fee['apply'] != 'cart_fee' ) {
					continue;
				}

				$label        = $fee['label'];
				$option_label = isset( $fee['option_label'] ) ? $fee['option_label'] : '';
				$fee_price    = apply_filters( 'ppom_option_price', $fee['price'] );
				$taxable      = $fee['taxable'];

				$label = "{$fee_no}-{$label} ({$option_label})";
				$label = apply_filters( 'ppom_fixed_fee_label', $label, $fee, $item );

				if ( ! empty( $fee['without_tax'] ) ) {
					$fee_price = $fee['without_tax'];
				}

				$taxable = ppom_get_option( 'ppom_taxable_fixed_price' );
				$taxable = $taxable == 'yes' ? true : false;

				$fee_price = apply_filters( 'ppom_cart_fixed_fee', $fee_price, $fee, $cart );
				$taxable   = apply_filters( 'ppom_cart_fixed_fee_taxable', $taxable, $fee, $cart );

				if ( $fee_price != 0 ) {
					$tax_class = $product->get_tax_class( 'unfiltered' );

					if ( wc_prices_include_tax() ) {
						$tax = \WC_Tax::calc_tax( $fee_price, \WC_Tax::get_rates( $tax_class ), true );

						$total_tax = array_sum( $tax );
						$fee_price = $fee_price - $total_tax;
					}

					$cart->add_fee( esc_html( $label ), $fee_price, $taxable, $tax_class );
					++$fee_no;
				}
			}

			++$cart_counter;
		}
	}
}
