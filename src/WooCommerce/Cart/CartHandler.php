<?php
/**
 * Cart and checkout UI: cart payload, session pricing (legacy), fees, quantities.
 *
 * @package PPOM
 * @subpackage WooCommerce
 *
 * @see woocommerce.php Parent loader under inc/.
 */

namespace PPOM\WooCommerce\Cart;

/**
 * @internal
 */
final class CartHandler {

	// Cart data and session pricing.

	/**
	 * Stores posted PPOM data on the WooCommerce cart item.
	 *
	 * The posted payload remains untrusted and is revalidated and repriced later in
	 * the cart and checkout lifecycle.
	 *
	 * @param array $cart       Cart item data.
	 * @param int   $product_id Product ID.
	 *
	 * @return array
	 *
	 * @see ppom_check_validation()
	 * @see ppom_price_controller()
	 * @see ppom_make_meta_data()
	 */
	public static function add_cart_item_data( $cart, $product_id ) {

		if ( ! isset( $_POST['ppom'] ) ) {
			return $cart;
		}

		$ppom = new PPOM_Meta( $product_id );
		if ( ! $ppom->ppom_settings ) {
			return $cart;
		}

		WC()->cart->remove_cart_item( $_POST['ppom_cart_key'] );

		// ADDED WC BUNDLES COMPATIBILITY
		if ( function_exists( 'wc_pb_is_bundled_cart_item' ) && wc_pb_is_bundled_cart_item( $cart ) ) {
			return $cart;
		}

		// PPOM also saving cropped images under this filter.
		$ppom_posted_fields = apply_filters( 'ppom_add_cart_item_data', $_POST['ppom'], $_POST );
		$cart['ppom']       = $ppom_posted_fields;

		return $cart;
	}

	public static function update_cart_fees( $cart_items, $values ) {

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

	public static function calculate_totals_from_session( $cart ) {
		$cart->calculate_totals();
	}


	public static function add_fixed_fee( $cart ) {

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

	// Show fixed fee in mini cart
	public static function mini_cart_fixed_fee() {

		if ( ! WC()->cart->get_fees() ) {
			return '';
		}

		$fixed_fee_html = '<table>';
		foreach ( WC()->cart->get_fees() as $fee ) {

			$item_fee = $fee->amount;
			if ( WC()->cart->display_prices_including_tax() && $fee->taxable ) {

				$item_fee = $fee->total + $fee->tax;
			}
			// var_dump($fee);
			$fixed_fee_html .= '<tr>';
			$fixed_fee_html .= '<td class="subtotal-text">' . esc_html( $fee->name );
			'</td>';
			$fixed_fee_html .= '<td class="subtotal-price">' . wc_price( $item_fee ) . '</td>';
			$fixed_fee_html .= '</tr>';
		}

		$fixed_fee_html .= '<tr><td colspan="2">' . __( 'Total will be calculated in the cart', 'woocommerce-product-addon' ) . '</td></tr>';
		$fixed_fee_html .= '</table>';

		echo apply_filters( 'ppom_mini_cart_fixed_fee', $fixed_fee_html );
	}

	public static function add_item_meta( $item_meta, $cart_item ) {

		if ( ! isset( $cart_item['ppom']['fields'] ) ) {
			return $item_meta;
		}


		// ADDED WC BUNDLES COMPATIBILITY
		if ( function_exists( 'wc_pb_is_bundled_cart_item' ) && wc_pb_is_bundled_cart_item( $cart_item ) ) {
			return $item_meta;
		}

		$ppom_meta = ppom_make_meta_data( $cart_item );
		// ppom_pa($ppom_meta);

		foreach ( $ppom_meta as $key => $meta ) {

			$hidden     = isset( $meta['hidden'] ) ? $meta['hidden'] : false;
			$meta_name  = isset( $meta['name'] ) ? $meta['name'] : '';
			$meta_value = isset( $meta['value'] ) ? $meta['value'] : '';
			$display    = isset( $meta['display'] ) ? $meta['display'] : $meta_value;
			if ( $key == 'ppom_has_quantities' ) {
				$hidden = true;
			}

			$allowed_input_fields = array( 'text', 'textarea', 'number' );
			$is_allowed_field     = in_array( $meta['type'], $allowed_input_fields, true );

			if ( $is_allowed_field && '' === $display ) {
				// Allow 0 and '0', skip only truly empty values
				continue;
			} elseif ( ! $is_allowed_field && empty( $display ) ) {
				// For other fields, treat 0 as empty as well
				continue;
			}

			if ( ! empty( $meta_name ) ) {

				if ( ! is_scalar( $meta_value ) ) {
					$meta_value = wp_json_encode( $meta_value );
				}

				if ( apply_filters( 'ppom_show_option_price_cart', false ) && isset( $meta['price'] ) ) {
					$meta_value .= ' (' . wc_price( $meta['price'] ) . ')';
				}

				$meta_key = stripslashes( $meta_name );

				// WPML
				$meta_key = ppom_wpml_translate( $meta_key, 'PPOM' );

				$item_meta[] = array(
					'name'    => wp_strip_all_tags( $meta_key ),
					'value'   => $meta_value,
					'hidden'  => $hidden,
					'display' => $display,
				);
			} else {
				$item_meta[] = array(
					'name'    => ( $key ),
					'value'   => $meta,
					'hidden'  => $hidden,
					'display' => $display,
				);
			}
		}

		return $item_meta;
	}

	// When quantities is used then reset quantity to 1
	public static function add_to_cart_quantity( $quantity, $product_id ) {

		if ( ppom_reset_cart_quantity_to_one( $product_id ) ) {
			$quantity = 1;
		}

		return $quantity;
	}

	// It is change cart quantity label
	public static function control_cart_quantity_legacy( $quantity, $cart_item_key ) {

		$cart_item = WC()->cart->get_cart_item( $cart_item_key );

		// ppom_pa($cart_item)
		if ( ! isset( $cart_item['ppom']['ppom_option_price'] ) &&
		! isset( $cart_item['ppom']['ppom_pricematrix'] ) ) {
			return $quantity;
		}

		// Getting option price
		$option_prices       = json_decode( wp_unslash( $cart_item['ppom']['ppom_option_price'] ), true );
		$ppom_has_quantities = 0;
		// ppom_pa($option_prices);

		if ( empty( $option_prices ) ) {
			return $quantity;
		}

		foreach ( $option_prices as $option ) {

			if ( isset( $option['include'] ) && $option['include'] == '' ) {
				if ( isset( $option['quantity'] ) ) {
					$ppom_has_quantities += intval( $option['quantity'] );
				}
			} elseif ( isset( $option['include'] ) && $option['include'] == 'on' ) {
				$ppom_has_quantities = 1;
			}
		}

		// var_dump($ppom_has_quantities);
		// If no quantity updated then return default
		$ppom_quantitiles_allow_update_cart = apply_filters( 'ppom_quantities_allow_cart_update', false, $option_prices );
		if ( $ppom_has_quantities != 0 && ! $ppom_quantitiles_allow_update_cart ) {
			$quantity = '<span class="ppom-cart-quantity">' . $ppom_has_quantities . '</span>';
		}

		return $quantity;
	}

	public static function control_cart_quantity( $quantity, $cart_item_key ) {

		$cart_item = WC()->cart->get_cart_item( $cart_item_key );

		if ( ! isset( $cart_item['ppom']['fields'] ) ) {
			return $quantity;
		}

		$ppom_fields_post = $cart_item['ppom']['fields'];
		$product_id       = $cart_item['product_id'];

		if ( ppom_is_cart_quantity_updatable( $product_id ) ) {
			return $quantity;
		}

		$ppom_has_quantities = ppom_price_get_total_quantities( $ppom_fields_post, $product_id );

		// var_dump(!$ppom_quantitiles_allow_update_cart);
		// If no quantity updated then return default
		$ppom_quantitiles_allow_update_cart = apply_filters( 'ppom_quantities_allow_cart_update', false, $ppom_fields_post );
		if ( $ppom_has_quantities != 0 && ! $ppom_quantitiles_allow_update_cart ) {
			$quantity = '<span class="ppom-cart-quantity">' . $ppom_has_quantities . '</span>';
		}

		return $quantity;
	}

	// Control subtotal when quantities input used.
	public static function item_subtotal( $item_subtotal, $cart_item, $cart_item_key ) {

		if ( ! isset( $cart_item['ppom']['ppom_option_price'] ) ) {
			return $item_subtotal;
		}

		// Getting option price.
		$option_prices = json_decode( wp_unslash( $cart_item['ppom']['ppom_option_price'] ), true );

		if ( empty( $option_prices ) ) {
			return $item_subtotal;
		}

		$price = 0;
		foreach ( $option_prices as $option ) {
			$option       = ppom_translation_options( $option );
			$option_price = isset( $option['price'] ) ? $option['price'] : 0;
			if ( 0 === $option_price || ( ! isset( $option['apply'] ) || 'onetime' !== $option['apply'] ) ) {
				continue;
			}
			$price = isset( $option['discount'] ) && $option['discount'] > 0 ? $option['discount'] : $option_price;
			if ( ! empty( $price ) ) {
				$price = apply_filters( 'ppom_option_price', $price );
				$price = floatval( wp_strip_all_tags( $price ) );
			}
		}

		if ( 0 === $price ) {
			return $item_subtotal;
		}
		$product_id    = $cart_item['product_id'];
		$quantity      = $cart_item['quantity'];
		$product_data  = new \WC_Product( $product_id );
		$product_price = floatval( $product_data->get_price() ) * $quantity;
		$item_subtotal = $product_price + $price;
		return ppom_price( $item_subtotal );
	}

	public static function control_checkout_quantity( $quantity, $cart_item, $cart_item_key ) {

		// ppom_pa($cart_item);
		if ( ! isset( $cart_item['ppom']['fields'] ) ) {
			return $quantity;
		}

		$ppom_fields_post = $cart_item['ppom']['fields'];
		$product_id       = $cart_item['product_id'];

		if ( ppom_is_cart_quantity_updatable( $product_id ) ) {
			return $quantity;
		}

		$ppom_has_quantities = ppom_price_get_total_quantities( $ppom_fields_post, $product_id );

		// If no quantity updated then return default
		if ( $ppom_has_quantities > 0 ) {
			$quantity = '<strong class="product-quantity">' . sprintf( '&times; %s', $ppom_has_quantities ) . '</strong>';
		}

		return $quantity;
	}

	public static function control_oder_item_quantity( $quantity, $item ) {

		$ppom_has_quantities = 0;

		$product_id = $item->get_product_id();

		$ppom_fields_post = wc_get_order_item_meta( $item->get_id(), '_ppom_fields' );
		if ( ! isset( $ppom_fields_post['fields'] ) ) {
			return $quantity;
		}

		$ppom_fields_post = $ppom_fields_post['fields'];

		if ( ppom_is_cart_quantity_updatable( $product_id ) ) {
			return $quantity;
		}

		$ppom_has_quantities = ppom_price_get_total_quantities( $ppom_fields_post, $product_id );

		if ( $ppom_has_quantities > 0 ) {
			$quantity = '<strong class="product-quantity">' . sprintf( '&times; %s', $ppom_has_quantities ) . '</strong>';
		}

		return $quantity;
	}

	public static function control_email_item_quantity( $quantity, $item ) {

		$ppom_has_quantities = 0;

		$product_id = $item->get_product_id();

		$ppom_fields_post = wc_get_order_item_meta( $item->get_id(), '_ppom_fields' );
		if ( ! isset( $ppom_fields_post['fields'] ) ) {
			return $quantity;
		}

		$ppom_fields_post = $ppom_fields_post['fields'];

		if ( ppom_is_cart_quantity_updatable( $product_id ) ) {
			return $quantity;
		}

		$ppom_has_quantities = ppom_price_get_total_quantities( $ppom_fields_post, $product_id );

		if ( $ppom_has_quantities > 0 ) {
			$quantity = '<strong class="product-quantity">' . esc_html( $ppom_has_quantities ) . '</strong>';
		}

		return $quantity;
	}

	public static function control_order_item_quantity( $quantity, $item ) {

		$ppom_has_quantities = 0;

		$product_id = $item->get_product_id();

		$ppom_fields_post = wc_get_order_item_meta( $item->get_id(), '_ppom_fields' );
		if ( ! isset( $ppom_fields_post['fields'] ) ) {
			return $quantity;
		}

		$ppom_fields_post = $ppom_fields_post['fields'];

		if ( ppom_is_cart_quantity_updatable( $product_id ) ) {
			return $quantity;
		}

		$ppom_has_quantities = ppom_price_get_total_quantities( $ppom_fields_post, $product_id );

		if ( $ppom_has_quantities > 0 ) {
			$quantity = $ppom_has_quantities;
		}

		return $quantity;
	}

	public static function cart_update_validate( $cart_validated, $cart_item_key, $values, $quantity ) {

		$max_quantity = ppom_get_cart_item_max_quantity( $values );

		if ( ! is_null( $max_quantity ) && $quantity > intval( $max_quantity ) ) {

			$cart_validated = false;
			wc_add_notice(
				sprintf(
				// translators: %d: the number of maximum quantity.
					__( 'Sorry, maximum quantity is %d.', 'woocommerce-product-addon' ),
					$max_quantity
				),
				'error'
			);
		}

		return $cart_validated;
	}
}
