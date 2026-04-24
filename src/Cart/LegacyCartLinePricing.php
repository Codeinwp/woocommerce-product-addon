<?php
/**
 * Legacy mode: cart line total from JSON `ppom_option_price` (session restore).
 *
 * @package PPOM
 */

namespace PPOM\Cart;

/**
 * @since 33.0.19
 */
final class LegacyCartLinePricing {

	/**
	 * Adjust cart line product price from legacy PPOM option price JSON.
	 *
	 * @param array<string, mixed> $cart_items Cart item row.
	 * @param array<string, mixed> $values     Session values including ppom payload.
	 * @return array<string, mixed>
	 */
	public static function adjust_line_from_session( $cart_items, $values ) {

		if ( empty( $cart_items ) ) {
			return $cart_items;
		}

		if ( ! isset( $values['ppom']['ppom_option_price'] ) ) {
			return $cart_items;
		}

		$wc_product = $cart_items['data'];
		$product_id = ppom_get_product_id( $wc_product );

		$ppom_meta_ids = '';
		// removing id field
		if ( ! empty( $values ['ppom'] ['fields']['id'] ) ) {
			$ppom_meta_ids = $values ['ppom'] ['fields']['id'];
			unset( $values ['ppom'] ['fields']['id'] );
		}

		// converting back to org price if Currency Switcher is used
		$ppom_item_org_price = ppom_hooks_convert_price_back( $wc_product->get_price() );
		// $ppom_item_org_price = $wc_product->get_price();

		$ppom_item_order_qty = floatval( $cart_items['quantity'] );

		// Getting option price
		$option_prices = json_decode( wp_unslash( $values['ppom']['ppom_option_price'] ), true );
		// ppom_pa($option_prices);
		$total_option_price = 0;
		$ppom_matrix_price  = 0;

		$ppom_quantities_price        = 0;
		$ppom_quantities_usebaseprice = false;
		$ppom_quantities_include_base = false;

		$ppom_total_quantities = 0;
		$ppom_total_discount   = 0;
		$ppon_onetime_cost     = 0;
		$ppomm_measures        = 1;    // meassure need to be multiple with each so it will be 1


		// If quantities field found then we need to get total quantity to get correct matrix price
		// if matrix is also used

		if ( $option_prices ) {
			foreach ( $option_prices as $option ) {
				if ( $option['apply'] == 'quantities' ) {
					$ppom_total_quantities += $option['quantity'];
					$ppom_item_order_qty    = $ppom_total_quantities;
				}
			}
		}


		// Check if price is set by matrix
		$matrix_found = ppom_get_price_matrix_chunk( $wc_product, $option_prices, $ppom_item_order_qty );
		// ppom_pa($matrix_found);

		// Calculating option prices
		if ( $option_prices ) {
			foreach ( $option_prices as $option ) {

				// Do not add if option is fixed/onetime
				// if( $option['apply'] != 'variable' ) continue;

				// ppom_get_field_option_price

				switch ( $option['apply'] ) {

					case 'variable':
						$option_price = $option['price'];
						// verify prices from server due to security
						if ( isset( $option['data_name'] ) && isset( $option['option_id'] ) ) {

							$option_price = ppom_get_field_option_price_by_id( $option, $wc_product, $ppom_meta_ids );
						}

						$total_option_price += wc_format_decimal( $option_price, wc_get_price_decimals() );
						break;

					case 'onetime':
						$option_price = $option['price'];
						// verify prices from server due to security
						if ( isset( $option['data_name'] ) && isset( $option['option_id'] ) ) {

							$option_price = ppom_get_field_option_price_by_id( $option, $wc_product, $ppom_meta_ids );
						}
						$ppon_onetime_cost += wc_format_decimal( $option_price, wc_get_price_decimals() );
						break;

					case 'quantities':
						$ppom_quantities_use_option_price = apply_filters( 'ppom_quantities_use_option_price', true, $option_prices );
						if ( $ppom_quantities_use_option_price ) {

							$quantity_price = $option['price'];

							// If matrix found now product org price will be set to matrix
							if ( ! empty( $matrix_found ) && ! isset( $matrix_found['discount'] ) ) {

								$quantity_price = $matrix_found['price'];

							}

							$ppom_quantities_price += wc_format_decimal( ( $quantity_price * $option['quantity'] ), wc_get_price_decimals() );
							// $ppom_total_quantities += $option['quantity'];
						}

						if ( ! empty( $option['include'] ) && $option['include'] == 'on' ) {
							$ppom_quantities_include_base = true;
						}
						break;

					case 'bulkquantity':
						// Note: May need to add matrix price like in quantites (above)

						$ppom_quantities_price += wc_format_decimal( ( $option['price'] * $option['quantity'] ), wc_get_price_decimals() );
						$ppom_quantities_price += isset( $option['base'] ) ? $option['base'] : 0;

						if ( isset( $option['usebase_price'] ) && $option['usebase_price'] == 'yes' ) {
							$ppom_quantities_usebaseprice = true;
						}
						break;

					// Fixed price addon
					case 'fixedprice':
						$ppom_item_org_price = $option['unitprice'];

						// Well, it should NOT be like this but have to do this. will see later.
						$ppom_item_order_qty = 1;
						break;

					case 'measure':
						$measer_qty       = isset( $option['qty'] ) ? intval( $option['qty'] ) : 0;
						$price_multiplier = isset( $option['price_multiplier'] ) ? floatval( $option['price_multiplier'] ) : 1;
						$option_price     = $option['price'];

						$ppomm_measures *= $measer_qty * $price_multiplier;


						break;

				}


				/**
				 * @since 15.4: Updating options weight
				 */
				if ( ppom_pro_is_installed() ) {
					$option_weight = ppom_get_field_option_weight_by_id( $option, $ppom_meta_ids );
					if ( $option_weight > 0 ) {
						$new_weight = $wc_product->get_weight() + $option_weight;
						$wc_product->set_weight( $new_weight );
					}
				}
			}
		}


		// ppom_pa($matrix_found);
		if ( ! empty( $matrix_found ) ) {

			// Check that it's not a discount matrix
			if ( ! isset( $matrix_found['discount'] ) ) {
				$ppom_item_org_price = $matrix_found['price'];
			} else {

				// Discount matrix found
				if ( ! empty( $matrix_found['percent'] ) ) {

					$total_with_options = $ppom_item_org_price + $total_option_price + $ppon_onetime_cost;

					$price_after_precent = 0.0;
					// Check wheather to apply on Both (Base+Options) or only Base
					if ( $matrix_found['discount'] == 'both' ) {

						// Also adding quantities price if used
						$total_price_to_be_discount = $total_with_options + $ppom_quantities_price;

						$price_after_precent = ppom_get_amount_after_percentage( $total_price_to_be_discount, $matrix_found['percent'] );
					} elseif ( $matrix_found['discount'] == 'base' ) {

						$total_price_to_be_discount = $ppom_item_org_price + $ppom_quantities_price;
						$price_after_precent        = ppom_get_amount_after_percentage( $total_price_to_be_discount, $matrix_found['percent'] );
					}

					$ppom_total_discount += $price_after_precent;
				} else {
					/**
					 * when discount is in PRICE not Percent then applied to whole price Base+Option)
					 * so need to get per unit discount
					 */

					/*
					** @since 16.8
					** When each variation has own quantity, then cart quantity is disabled only one price is set
					** not indivisual
					**/
					if ( ! $ppom_quantities_usebaseprice ) {

						$ppom_total_discount += $matrix_found['price'];
					} else {
						$discount_per_unit    = $matrix_found['price'] / $ppom_item_order_qty;
						$ppom_total_discount += $discount_per_unit;
					}
				}
			}
		}


		if ( $ppom_quantities_price > 0 ) {

			if ( ! $ppom_quantities_include_base ) {
				// $ppom_item_org_price = ($ppom_item_org_price * $ppom_total_quantities);
				$ppom_item_org_price = 0;

				// when base price is NOT included the quantity is updated so it must be multiplied by options
				$total_option_price = ( $total_option_price * $ppom_total_quantities );
			}
		}

		// If measures found, Multiply it with options
		if ( $ppomm_measures > 0 ) {
			// $total_option_price = $total_option_price * $ppomm_measures;
			$ppom_item_org_price = $ppom_item_org_price * $ppomm_measures;
		}


		// var_dump($ppom_total_discount);
		// var_dump($ppom_item_org_price);
		// var_dump($total_option_price);
		// var_dump($ppom_quantities_price);


		$cart_line_total = ( $ppom_item_org_price + $total_option_price + $ppom_quantities_price - $ppom_total_discount );

		$cart_line_total = apply_filters( 'ppom_cart_line_total', $cart_line_total, $cart_items, $values );

		$wc_product->set_price( $cart_line_total );

		return $cart_items;
	}
}
