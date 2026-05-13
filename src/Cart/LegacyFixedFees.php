<?php
/**
 * Legacy mode: WooCommerce cart fees for onetime PPOM options.
 *
 * @package PPOM
 */

namespace PPOM\Cart;

/**
 * @since 33.0.19
 */
final class LegacyFixedFees {

	/**
	 * Register legacy onetime fees on the cart.
	 *
	 * @param \WC_Cart $cart WooCommerce cart.
	 * @return void
	 */
	public static function apply_legacy_onetime_fees( $cart ): void {

		$fee_no = 1;
		foreach ( $cart->get_cart() as $item ) {

			if ( empty( $item['ppom']['ppom_option_price'] ) ) {
				continue;
			}

			// Getting option price
			$option_prices = json_decode( wp_unslash( $item['ppom']['ppom_option_price'] ), true );

			if ( $option_prices ) {
				foreach ( $option_prices as $fee ) {

					if ( $fee['apply'] != 'onetime' ) {
						continue;
					}


					$label = $fee_no . '-' . $fee['product_title'] . ': ' . $fee['label'];
					$label = apply_filters( 'ppom_fixed_fee_label', $label, $fee, $item );

					$taxable   = ( isset( $fee['taxable'] ) && $fee['taxable'] == 'on' ) ? true : false;
					$fee_price = $fee['price'];

					if ( ! empty( $fee['without_tax'] ) ) {
						$fee_price = $fee['without_tax'];
					}

					// if(  'incl' === get_option( 'woocommerce_tax_display_shop' ) ) {
					// $taxable = false;
					// }

					$fee_price = apply_filters( 'ppom_cart_fixed_fee', $fee_price, $fee, $cart );

					if ( $fee_price != 0 ) {
						$cart->add_fee( esc_html( $label ), $fee_price, $taxable );
						++$fee_no;
					}
				}
			}
		}
	}
}
