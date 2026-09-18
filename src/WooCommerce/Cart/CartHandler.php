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

use PPOM_Meta;
use PPOM\Hooks\Callbacks;
use PPOM\Pricing\Engine;
use PPOM\Support\Helpers;

/**
 * @internal
 */
final class CartHandler {

	/**
	 * Legacy option-price JSON: summed quantities when `include` is empty, or 1 when `include` is `on`.
	 *
	 * @param array $option_prices Decoded `ppom_option_price` payload.
	 *
	 * @return int
	 */
	public static function legacy_option_prices_aggregate_display_quantity( array $option_prices ) {
		$ppom_has_quantities = 0;

		foreach ( $option_prices as $option ) {
			if ( isset( $option['include'] ) && $option['include'] == '' ) {
				if ( isset( $option['quantity'] ) ) {
					$ppom_has_quantities += intval( $option['quantity'] );
				}
			} elseif ( isset( $option['include'] ) && $option['include'] == 'on' ) {
				$ppom_has_quantities = 1;
			}
		}

		return $ppom_has_quantities;
	}

	/**
	 * Whether a cart line meta row should be omitted because the display value is considered empty.
	 *
	 * Text-like types keep `0` / `'0'` visible; other types use `empty()` semantics.
	 *
	 * @param string $type    Field input type.
	 * @param mixed  $display Display string (or value coerced for empty check).
	 *
	 * @return bool
	 */
	public static function should_skip_cart_meta_for_empty_display( $type, $display ) {
		$allowed_input_fields = array( 'text', 'textarea', 'number' );
		$is_allowed_field     = in_array( $type, $allowed_input_fields, true );

		if ( $is_allowed_field && '' === $display ) {
			return true;
		}

		if ( ! $is_allowed_field && empty( $display ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Quantities-type option rows: summed quantities and the order quantity used for matrix lookup.
	 *
	 * @param array $option_prices Decoded `ppom_option_price` list (may be empty).
	 * @param mixed $cart_quantity WooCommerce line quantity.
	 *
	 * @return array{total_quantities: float|int, matrix_order_qty: float}
	 */
	public static function legacy_option_prices_matrix_context( array $option_prices, $cart_quantity ) {
		$ppom_total_quantities = 0;
		$ppom_item_order_qty   = floatval( $cart_quantity );

		foreach ( $option_prices as $option ) {
			if ( isset( $option['apply'] ) && $option['apply'] == 'quantities' ) {
				$qty = isset( $option['quantity'] ) ? $option['quantity'] : 0;
				$ppom_total_quantities += intval( $qty );
				$ppom_item_order_qty    = $ppom_total_quantities;
			}
		}

		return array(
			'total_quantities' => $ppom_total_quantities,
			'matrix_order_qty' => $ppom_item_order_qty,
		);
	}

	/**
	 * Applies a resolved price-matrix chunk to base price and/or discount accumulator (legacy cart line).
	 *
	 * @param array     $matrix_found                 Chunk from `Helpers::get_price_matrix_chunk()`.
	 * @param float|int $ppom_item_org_price          Product base subtotal before matrix override.
	 * @param float|int $total_option_price           Summed variable option prices.
	 * @param float|int $ppon_onetime_cost            Summed onetime option prices.
	 * @param float|int $ppom_quantities_price        Quantities / bulk line component.
	 * @param bool      $ppom_quantities_usebaseprice Bulk quantity “use base” flag.
	 * @param float|int $ppom_item_order_qty          Quantity used when splitting fixed matrix discounts.
	 *
	 * @return array{replace_org_price: bool, org_price: float|int|string, discount_delta: float|int|string}
	 */
	public static function apply_price_matrix_chunk_to_line_components(
		$matrix_found,
		$ppom_item_org_price,
		$total_option_price,
		$ppon_onetime_cost,
		$ppom_quantities_price,
		$ppom_quantities_usebaseprice,
		$ppom_item_order_qty
	) {
		if ( empty( $matrix_found ) ) {
			return array(
				'replace_org_price' => false,
				'org_price'         => $ppom_item_org_price,
				'discount_delta'    => 0,
			);
		}

		if ( ! isset( $matrix_found['discount'] ) ) {
			return array(
				'replace_org_price' => true,
				'org_price'         => $matrix_found['price'],
				'discount_delta'    => 0,
			);
		}

		if ( ! empty( $matrix_found['percent'] ) ) {
			$total_with_options = floatval( $ppom_item_org_price ) + floatval( $total_option_price ) + floatval( $ppon_onetime_cost );
			$discount_delta     = 0;

			if ( $matrix_found['discount'] == 'both' ) {
				$total_price_to_be_discount = $total_with_options + floatval( $ppom_quantities_price );
				$discount_delta             = Engine::get_amount_after_percentage( $total_price_to_be_discount, $matrix_found['percent'] );
			} elseif ( $matrix_found['discount'] == 'base' ) {
				$total_price_to_be_discount = floatval( $ppom_item_org_price ) + floatval( $ppom_quantities_price );
				$discount_delta             = Engine::get_amount_after_percentage( $total_price_to_be_discount, $matrix_found['percent'] );
			}

			return array(
				'replace_org_price' => false,
				'org_price'         => $ppom_item_org_price,
				'discount_delta'    => $discount_delta,
			);
		}

		if ( ! $ppom_quantities_usebaseprice ) {
			return array(
				'replace_org_price' => false,
				'org_price'         => $ppom_item_org_price,
				'discount_delta'    => $matrix_found['price'],
			);
		}

		return array(
			'replace_org_price' => false,
			'org_price'         => $ppom_item_org_price,
			'discount_delta'    => $matrix_found['price'] / $ppom_item_order_qty,
		);
	}

	/**
	 * When quantities options exclude base price, zero base and scale variable options by summed quantities.
	 *
	 * @param float|int $ppom_quantities_price       Quantities component total.
	 * @param bool      $ppom_quantities_include_base Whether base is included in quantities pricing.
	 * @param float|int $ppom_item_org_price         Current base component.
	 * @param float|int $total_option_price          Current variable option total.
	 * @param float|int $ppom_total_quantities       Sum from quantities-type options.
	 *
	 * @return array{ppom_item_org_price: float|int, total_option_price: float|int}
	 */
	public static function apply_legacy_quantities_exclude_base_adjustment(
		$ppom_quantities_price,
		$ppom_quantities_include_base,
		$ppom_item_org_price,
		$total_option_price,
		$ppom_total_quantities
	) {
		if ( $ppom_quantities_price > 0 && ! $ppom_quantities_include_base ) {
			return array(
				'ppom_item_org_price' => 0,
				'total_option_price'  => $total_option_price,
			);
		}

		return array(
			'ppom_item_org_price' => $ppom_item_org_price,
			'total_option_price'  => $total_option_price,
		);
	}

	/**
	 * Applies accumulated measure multiplier to the base component (legacy line pricing).
	 *
	 * @param float|int $ppom_item_org_price Base subtotal.
	 * @param float|int $ppomm_measures        Product of measure qty × multiplier across options.
	 *
	 * @return float|int
	 */
	public static function apply_legacy_measure_multiplier_to_org_price( $ppom_item_org_price, $ppomm_measures ) {
		if ( $ppomm_measures > 0 ) {
			return $ppom_item_org_price * $ppomm_measures;
		}

		return $ppom_item_org_price;
	}

	/**
	 * Final legacy cart line total before `ppom_cart_line_total` filter.
	 *
	 * @param float|int|string $ppom_item_org_price   Base component.
	 * @param float|int|string $total_option_price    Variable options component.
	 * @param float|int|string $ppom_quantities_price Quantities / bulk component.
	 * @param float|int|string $ppom_total_discount   Matrix discount component.
	 *
	 * @return float
	 */
	public static function legacy_cart_line_total_from_components(
		$ppom_item_org_price,
		$total_option_price,
		$ppom_quantities_price,
		$ppom_total_discount
	) {
		return floatval( $ppom_item_org_price )
			+ floatval( $total_option_price )
			+ floatval( $ppom_quantities_price )
			- floatval( $ppom_total_discount );
	}

	/**
	 * Pure reducer for one legacy `ppom_option_price` row (after variable/onetime prices are server-resolved).
	 *
	 * Callers must pass `resolved_line_price` for `variable` and `onetime` applies (from
	 * `Helpers::get_field_option_price_by_id()` when ids are present). Other applies ignore it.
	 *
	 * @param array $option  Single decoded option row.
	 * @param array $state   State with keys: ppom_item_org_price, ppom_item_order_qty, total_option_price,
	 *                       ppon_onetime_cost, ppom_quantities_price, ppom_quantities_usebaseprice,
	 *                       ppom_quantities_include_base, ppomm_measures.
	 * @param array $context `matrix_found` (chunk array), `option_prices` (full list for filters),
	 *                       `resolved_line_price` (scalar|null).
	 *
	 * @return array<string, mixed> Updated state (same keys).
	 */
	public static function accumulate_legacy_option_price_row( array $option, array $state, array $context ) {
		$matrix_found  = isset( $context['matrix_found'] ) ? $context['matrix_found'] : array();
		$option_prices = isset( $context['option_prices'] ) ? $context['option_prices'] : array();
		$resolved      = array_key_exists( 'resolved_line_price', $context ) ? $context['resolved_line_price'] : null;
		$decimals      = wc_get_price_decimals();

		if ( ! isset( $option['apply'] ) ) {
			return $state;
		}

		switch ( $option['apply'] ) {

			case 'variable':
				$option_price                 = null !== $resolved ? $resolved : $option['price'];
				$state['total_option_price'] += wc_format_decimal( $option_price, $decimals );
				break;

			case 'onetime':
				$option_price                = null !== $resolved ? $resolved : $option['price'];
				$state['ppon_onetime_cost'] += wc_format_decimal( $option_price, $decimals );
				break;

			case 'quantities':
				$ppom_quantities_use_option_price = apply_filters( 'ppom_quantities_use_option_price', true, $option_prices );
				if ( $ppom_quantities_use_option_price ) {

					$quantity_price = $option['price'];

					if ( ! empty( $matrix_found ) && ! isset( $matrix_found['discount'] ) ) {
						$quantity_price = $matrix_found['price'];
					}

					$state['ppom_quantities_price'] += wc_format_decimal( ( $quantity_price * $option['quantity'] ), $decimals );
				}

				if ( ! empty( $option['include'] ) && $option['include'] == 'on' ) {
					$state['ppom_quantities_include_base'] = true;
				}
				break;

			case 'bulkquantity':
				$state['ppom_quantities_price'] += wc_format_decimal( ( $option['price'] * $option['quantity'] ), $decimals );
				$base_addon = 0.0;
				if ( isset( $option['base'] ) && is_scalar( $option['base'] ) ) {
					$base_addon = floatval( $option['base'] );
				}
				$state['ppom_quantities_price'] += $base_addon;

				if ( isset( $option['usebase_price'] ) && $option['usebase_price'] == 'yes' ) {
					$state['ppom_quantities_usebaseprice'] = true;
				}
				break;

			case 'fixedprice':
				$state['ppom_item_org_price'] = $option['unitprice'];
				$state['ppom_item_order_qty'] = 1;
				break;

			case 'measure':
				$measer_qty               = isset( $option['qty'] ) ? intval( $option['qty'] ) : 0;
				$price_multiplier         = isset( $option['price_multiplier'] ) ? floatval( $option['price_multiplier'] ) : 1;
				$state['ppomm_measures'] *= $measer_qty * $price_multiplier;
				break;

		}

		return $state;
	}

	// Cart data and session pricing.

	/**
	 * Stores posted PPOM data on the WooCommerce cart item.
	 *
	 * The posted payload remains untrusted and is revalidated and repriced later in
	 * the cart and checkout lifecycle.
	 *
	 * @param array $cart         Cart item data.
	 * @param int   $product_id   Product ID.
	 * @param int   $variation_id Selected variation ID, or 0 for simple products.
	 * @param int   $quantity     Requested quantity (unused; kept for filter compatibility).
	 *
	 * @return array
	 *
	 * @see ppom_check_validation()
	 * @see ppom_price_controller()
	 * @see Helpers::make_meta_data()
	 */
	public static function add_cart_item_data( $cart, $product_id, $variation_id = 0, $quantity = 1 ) {

		if ( ! isset( $_POST['ppom'] ) ) {
			return $cart;
		}

		$ppom = new PPOM_Meta( $product_id );
		if ( ! $ppom->ppom_settings ) {
			return $cart;
		}

		$wc_cart = function_exists( 'WC' ) ? WC()->cart : null;

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Verified by WooCommerce add-to-cart nonce.
		if ( isset( $_POST['ppom_cart_key'] ) && is_string( $_POST['ppom_cart_key'] ) && $wc_cart ) {
			$remove_key = sanitize_text_field( wp_unslash( $_POST['ppom_cart_key'] ) );
			if ( '' !== $remove_key && $wc_cart->get_cart_item( $remove_key ) ) {
				$wc_cart->remove_cart_item( $remove_key );
			}
		}
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		// ADDED WC BUNDLES COMPATIBILITY
		if ( function_exists( 'wc_pb_is_bundled_cart_item' ) && wc_pb_is_bundled_cart_item( $cart ) ) {
			return $cart;
		}

		// PPOM also saving cropped images under this filter.
		$ppom_posted_fields = apply_filters( 'ppom_add_cart_item_data', $_POST['ppom'], $_POST );
		$ppom_posted_fields = Helpers::filter_ppom_payload_by_active_variation( (array) $ppom_posted_fields, $product_id, $variation_id );
		if ( empty( $ppom_posted_fields['fields'] ) ) {
			return $cart;
		}

		$cart['ppom'] = $ppom_posted_fields;

		return $cart;
	}

	public static function update_cart_fees( $cart_items, $values ) {

		if ( empty( $cart_items ) ) {
			return $cart_items;
		}

		$wc_product = $cart_items['data'];
		$product_id = Helpers::get_product_id( $wc_product );

		if ( empty( $values['ppom']['ppom_option_price'] ) && ! empty( $values['ppom']['fields'] ) ) {
			$computed = Helpers::compute_option_price_from_fields( $values['ppom']['fields'], $product_id );
			if ( ! empty( $computed ) ) {
				$values['ppom']['ppom_option_price'] = wp_json_encode( $computed );
			}
		}

		if ( ! isset( $values['ppom']['ppom_option_price'] ) ) {
			return $cart_items;
		}

		$variation_id   = isset( $cart_items['variation_id'] ) ? absint( $cart_items['variation_id'] ) : 0;
		$values['ppom'] = Helpers::filter_ppom_payload_by_active_variation( (array) $values['ppom'], $product_id, $variation_id );

		$ppom_meta_ids = '';
		// removing id field
		if ( ! empty( $values ['ppom'] ['fields']['id'] ) ) {
			$ppom_meta_ids = $values ['ppom'] ['fields']['id'];
			unset( $values ['ppom'] ['fields']['id'] );
		}

		// converting back to org price if Currency Switcher is used
		$ppom_item_org_price = Callbacks::convert_price_back( $wc_product->get_price() );
		// $ppom_item_org_price = $wc_product->get_price();

		$ppom_item_order_qty = floatval( $cart_items['quantity'] );

		// Getting option price
		$option_prices = json_decode( wp_unslash( $values['ppom']['ppom_option_price'] ), true );
		// ppom_pa($option_prices);
		$total_option_price = 0;

		$ppom_quantities_price        = 0;
		$ppom_quantities_usebaseprice = false;
		$ppom_quantities_include_base = false;

		$ppom_total_discount = 0;
		$ppon_onetime_cost   = 0;
		$ppomm_measures      = 1;    // meassure need to be multiple with each so it will be 1

		$matrix_ctx            = self::legacy_option_prices_matrix_context( $option_prices ? $option_prices : array(), $cart_items['quantity'] );
		$ppom_total_quantities = $matrix_ctx['total_quantities'];
		$ppom_item_order_qty   = $matrix_ctx['matrix_order_qty'];

		// Check if price is set by matrix
		$matrix_found = Helpers::get_price_matrix_chunk( $wc_product, $option_prices, $ppom_item_order_qty );
		// ppom_pa($matrix_found);

		$pricing_state = array(
			'ppom_item_org_price'          => $ppom_item_org_price,
			'ppom_item_order_qty'          => $ppom_item_order_qty,
			'total_option_price'           => $total_option_price,
			'ppon_onetime_cost'            => $ppon_onetime_cost,
			'ppom_quantities_price'        => $ppom_quantities_price,
			'ppom_quantities_usebaseprice' => $ppom_quantities_usebaseprice,
			'ppom_quantities_include_base' => $ppom_quantities_include_base,
			'ppomm_measures'               => $ppomm_measures,
		);

		// Calculating option prices
		if ( $option_prices ) {
			foreach ( $option_prices as $option ) {
				if ( ! is_array( $option ) ) {
					continue;
				}

				$resolved_line_price = null;
				if ( isset( $option['apply'] ) && ( 'variable' === $option['apply'] || 'onetime' === $option['apply'] ) ) {
					$resolved_line_price = isset( $option['price'] ) ? $option['price'] : 0;
					if ( isset( $option['data_name'] ) && isset( $option['option_id'] ) ) {
						$resolved_line_price = Helpers::get_field_option_price_by_id( $option, $wc_product, $ppom_meta_ids );
					}
				}

				$pricing_state = self::accumulate_legacy_option_price_row(
					$option,
					$pricing_state,
					array(
						'matrix_found'        => $matrix_found,
						'option_prices'       => $option_prices,
						'resolved_line_price' => $resolved_line_price,
					)
				);

				/**
				 * @since 15.4: Updating options weight
				 */
				if ( Helpers::pro_is_installed() ) {
					$option_weight = Helpers::get_field_option_weight_by_id( $option, $ppom_meta_ids );
					if ( $option_weight > 0 ) {
						$new_weight = $wc_product->get_weight() + $option_weight;
						$wc_product->set_weight( $new_weight );
					}
				}
			}
		}

		$ppom_item_org_price          = $pricing_state['ppom_item_org_price'];
		$ppom_item_order_qty          = $pricing_state['ppom_item_order_qty'];
		$total_option_price           = $pricing_state['total_option_price'];
		$ppon_onetime_cost            = $pricing_state['ppon_onetime_cost'];
		$ppom_quantities_price        = $pricing_state['ppom_quantities_price'];
		$ppom_quantities_usebaseprice = $pricing_state['ppom_quantities_usebaseprice'];
		$ppom_quantities_include_base = $pricing_state['ppom_quantities_include_base'];
		$ppomm_measures               = $pricing_state['ppomm_measures'];


		// ppom_pa($matrix_found);
		$matrix_adjustment = self::apply_price_matrix_chunk_to_line_components(
			$matrix_found,
			$ppom_item_org_price,
			$total_option_price,
			$ppon_onetime_cost,
			$ppom_quantities_price,
			$ppom_quantities_usebaseprice,
			$ppom_item_order_qty
		);
		if ( $matrix_adjustment['replace_org_price'] ) {
			$ppom_item_org_price = $matrix_adjustment['org_price'];
		}
		$ppom_total_discount += $matrix_adjustment['discount_delta'];

		$qty_base_adjustment = self::apply_legacy_quantities_exclude_base_adjustment(
			$ppom_quantities_price,
			$ppom_quantities_include_base,
			$ppom_item_org_price,
			$total_option_price,
			$ppom_total_quantities
		);
		$ppom_item_org_price = $qty_base_adjustment['ppom_item_org_price'];
		$total_option_price  = $qty_base_adjustment['total_option_price'];

		$ppom_item_org_price = self::apply_legacy_measure_multiplier_to_org_price( $ppom_item_org_price, $ppomm_measures );

		$cart_line_total = self::legacy_cart_line_total_from_components(
			$ppom_item_org_price,
			$total_option_price,
			$ppom_quantities_price,
			$ppom_total_discount
		);

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
				$product_id      = isset( $item['product_id'] ) ? absint( $item['product_id'] ) : 0;
				$variation_id    = isset( $item['variation_id'] ) ? absint( $item['variation_id'] ) : 0;
				$pristine        = wc_get_product( $variation_id ? $variation_id : $product_id );
				$selected_fields = isset( $item['ppom']['fields'] ) && is_array( $item['ppom']['fields'] ) ? $item['ppom']['fields'] : array();
				$ppom_meta_ids   = isset( $selected_fields['id'] ) ? $selected_fields['id'] : '';
				$attached_ids    = empty( $ppom_meta_ids ) ? array() : self::attached_meta_ids( $product_id, $ppom_meta_ids );
				$counted_fields  = array();

				foreach ( $option_prices as $fee ) {

					if ( ! is_array( $fee ) || ! isset( $fee['apply'] ) || $fee['apply'] != 'onetime' ) {
						continue;
					}


					$label = $fee_no . '-' . $fee['product_title'] . ': ' . $fee['label'];
					$label = apply_filters( 'ppom_fixed_fee_label', $label, $fee, $item );

					$taxable   = ( isset( $fee['taxable'] ) && $fee['taxable'] == 'on' ) ? true : false;
					$fee_price = $fee['price'];

					if ( ! empty( $fee['without_tax'] ) ) {
						$fee_price = $fee['without_tax'];
					}

					// Charge the saved amount, never the payload's, so the line subtotal agrees.
					$resolved = null;

					if ( $pristine instanceof \WC_Product && ! empty( $ppom_meta_ids ) ) {
						$resolved = self::canonical_onetime_price( $fee, $pristine, $attached_ids, $selected_fields, $counted_fields );
					}

					if ( null !== $resolved ) {
						$fee_price        = apply_filters( 'ppom_option_price', $resolved );
						$resolved_taxable = self::resolved_onetime_taxable( $fee, $attached_ids );

						if ( null !== $resolved_taxable ) {
							$taxable = $resolved_taxable;
						}
					} else {
						$fee_price = self::unresolved_payload_amount( $fee_price );
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
			$fixed_fee_html .= '<td class="subtotal-text">' . esc_html( $fee->name ) . '</td>';
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

		$ppom_meta = Helpers::make_meta_data( $cart_item );
		// ppom_pa($ppom_meta);

		foreach ( $ppom_meta as $key => $meta ) {
			$row = self::convert_ppom_meta_entry_to_item_meta_row( $key, $meta );
			if ( null === $row ) {
				continue;
			}
			$item_meta[] = $row;
		}

		return $item_meta;
	}

	/**
	 * Builds a single WooCommerce cart-item meta row from one `make_meta_data()` entry,
	 * or `null` when the entry should be skipped.
	 *
	 * Some entries (e.g. `ppom_has_quantities`) are stored as a scalar tracker rather than
	 * a meta array; those become a hidden row keyed by `$key`. Array entries respect
	 * `should_skip_cart_meta_for_empty_display()` and the legacy name/value/hidden shape.
	 *
	 * @param string $key  Cart meta key from `make_meta_data()`.
	 * @param mixed  $meta Cart meta entry (array shape or scalar sentinel).
	 *
	 * @return array{name: string, value: mixed, hidden: bool, display: mixed}|null
	 */
	public static function convert_ppom_meta_entry_to_item_meta_row( $key, $meta ) {
		if ( ! is_array( $meta ) ) {
			return array(
				'name'    => $key,
				'value'   => $meta,
				'hidden'  => true,
				'display' => $meta,
			);
		}

		$hidden     = isset( $meta['hidden'] ) ? $meta['hidden'] : false;
		$meta_name  = isset( $meta['name'] ) ? $meta['name'] : '';
		$meta_value = isset( $meta['value'] ) ? $meta['value'] : '';
		$display    = isset( $meta['display'] ) ? $meta['display'] : $meta_value;
		$meta_type  = isset( $meta['type'] ) ? $meta['type'] : '';

		// Empty / unnamed entries (e.g. cropper rows with no cart value) must fall
		// through to the bottom legacy branch; only skip when the entry actually
		// names a field whose display value is empty.
		if ( ! empty( $meta_name ) && self::should_skip_cart_meta_for_empty_display( $meta_type, $display ) ) {
			return null;
		}

		if ( ! empty( $meta_name ) ) {
			if ( ! is_scalar( $meta_value ) ) {
				$meta_value = wp_json_encode( $meta_value );
			}

			if ( ! is_scalar( $display ) ) {
				$display = wp_json_encode( $display );
			}

			if ( apply_filters( 'ppom_show_option_price_cart', false ) && isset( $meta['price'] ) ) {
				$meta_value .= ' (' . wc_price( $meta['price'] ) . ')';
			}

			$meta_key = stripslashes( $meta_name );
			$meta_key = Helpers::wpml_translate( $meta_key, 'PPOM' );

			return array(
				'name'    => wp_strip_all_tags( $meta_key ),
				'value'   => $meta_value,
				'hidden'  => $hidden,
				'display' => $display,
			);
		}

		return array(
			'name'    => $key,
			'value'   => wp_json_encode( $meta ),
			'hidden'  => $hidden,
			'display' => is_scalar( $display ) ? $display : wp_json_encode( $display ),
		);
	}

	// When quantities is used then reset quantity to 1
	public static function add_to_cart_quantity( $quantity, $product_id ) {

		if ( Helpers::reset_cart_quantity_to_one( $product_id ) ) {
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
		$option_prices = json_decode( wp_unslash( $cart_item['ppom']['ppom_option_price'] ), true );
		// ppom_pa($option_prices);

		if ( empty( $option_prices ) ) {
			return $quantity;
		}

		$ppom_has_quantities = self::legacy_option_prices_aggregate_display_quantity( $option_prices );

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

		if ( Helpers::is_cart_quantity_updatable( $product_id ) ) {
			return $quantity;
		}

		$ppom_has_quantities = Engine::price_get_total_quantities( $ppom_fields_post, $product_id );

		// var_dump(!$ppom_quantitiles_allow_update_cart);
		// If no quantity updated then return default
		$ppom_quantitiles_allow_update_cart = apply_filters( 'ppom_quantities_allow_cart_update', false, $ppom_fields_post );
		if ( $ppom_has_quantities != 0 && ! $ppom_quantitiles_allow_update_cart ) {
			$quantity = '<span class="ppom-cart-quantity">' . $ppom_has_quantities . '</span>';
		}

		return $quantity;
	}

	/**
	 * Amount to use for a row that cannot be resolved from saved meta.
	 *
	 * @param mixed $amount Amount carried by the payload row.
	 *
	 * @return float
	 */
	private static function unresolved_payload_amount( $amount ) {
		$amount = is_scalar( $amount ) ? (float) $amount : 0.0;

		return $amount < 0 ? 0.0 : $amount;
	}

	/**
	 * Whether a resolved fixed fee is taxable according to its saved field.
	 *
	 * @param array<string, mixed> $option       Single decoded option row.
	 * @param int[]                $attached_ids Group ids confirmed attached to the product.
	 *
	 * @return bool|null Null when the field cannot be resolved.
	 */
	private static function resolved_onetime_taxable( array $option, array $attached_ids ) {
		if ( empty( $attached_ids ) || ! isset( $option['data_name'] ) ) {
			return null;
		}

		$field_meta = Helpers::get_field_meta_by_dataname( 0, sanitize_key( (string) $option['data_name'] ), implode( ',', $attached_ids ) );

		if ( empty( $field_meta ) || ! is_array( $field_meta ) ) {
			return null;
		}

		return isset( $field_meta['onetime_taxable'] ) && 'on' === $field_meta['onetime_taxable'];
	}

	/**
	 * Canonical fixed-fee amount for one `ppom_option_price` row, or null when the row
	 * cannot be resolved from saved meta at all.
	 *
	 * @param array<string, mixed> $option          Single decoded option row.
	 * @param \WC_Product          $product         Pristine product of the cart line.
	 * @param int[]                $attached_ids    Group ids confirmed attached to the product.
	 * @param array<string, mixed> $selected_fields Field values submitted for the line.
	 * @param array<string, bool>  $counted_fields  Fields already totalled for this line.
	 *
	 * @return float|null
	 */
	public static function canonical_onetime_price( array $option, $product, array $attached_ids, array $selected_fields, array &$counted_fields ) {
		if ( ! isset( $option['data_name'] ) ) {
			return null;
		}

		if ( isset( $option['option_id'] ) ) {
			$key = sanitize_key( (string) $option['data_name'] ) . '|' . (string) $option['option_id'];

			if ( isset( $counted_fields[ $key ] ) ) {
				return 0.0;
			}

			$counted_fields[ $key ] = true;

			return self::resolved_onetime_option_price( $option, $product, $attached_ids, $selected_fields );
		}

		return self::resolved_onetime_field_total( $option['data_name'], $product, $attached_ids, $selected_fields, $counted_fields );
	}

	/**
	 * Narrows payload-supplied PPOM group ids to those actually attached to the product.
	 *
	 * @param int                     $product_id    Product the cart line refers to.
	 * @param string|array<int, mixed> $ppom_meta_ids Group ids carried by the cart payload.
	 *
	 * @return int[] Attached group ids; empty when none of the requested ids belong to the product.
	 */
	private static function attached_meta_ids( $product_id, $ppom_meta_ids ) {
		$requested = is_array( $ppom_meta_ids ) ? $ppom_meta_ids : explode( ',', (string) $ppom_meta_ids );
		$requested = array_filter( array_map( 'absint', $requested ) );

		if ( empty( $requested ) ) {
			return array();
		}

		$ppom    = new \PPOM_Meta( $product_id );
		$allowed = $ppom->get_meta_id( $product_id );
		$allowed = array_filter( array_map( 'absint', is_array( $allowed ) ? $allowed : array( $allowed ) ) );

		return array_values( array_intersect( $requested, $allowed ) );
	}

	/**
	 * Server-resolved fixed-fee amount for one `ppom_option_price` row.
	 *
	 * @param array<string, mixed> $option          Single decoded option row.
	 * @param \WC_Product          $product         Product of the cart line; percentage prices need it.
	 * @param int[]                $attached_ids    Group ids confirmed attached to the product.
	 * @param array<string, mixed> $selected_fields Field values submitted for the line.
	 *
	 * @return float
	 */
	private static function resolved_onetime_option_price( array $option, $product, array $attached_ids, array $selected_fields ) {
		if ( empty( $attached_ids ) ) {
			return 0.0;
		}

		$meta_ids   = implode( ',', $attached_ids );
		$field_meta = Helpers::get_field_meta_by_dataname( 0, sanitize_key( (string) $option['data_name'] ), $meta_ids );

		if ( empty( $field_meta ) || ! is_array( $field_meta ) ) {
			return 0.0;
		}

		if ( ! isset( $field_meta['onetime'] ) || 'on' !== $field_meta['onetime'] ) {
			return 0.0;
		}

		if ( ! self::option_is_selected( $field_meta, $option['option_id'], $selected_fields ) ) {
			return 0.0;
		}

		$option_id = (string) $option['option_id'];

		foreach ( self::saved_option_rows( $field_meta ) as $saved_option ) {
			if ( in_array( $option_id, self::saved_option_ids( $saved_option, $field_meta ), true ) ) {
				return self::saved_option_amount( $field_meta, $saved_option, $product );
			}
		}

		return self::canonical_amount( Helpers::get_field_option_price_by_id( $option, $product, $meta_ids ), $product );
	}

	/**
	 * Server-resolved fixed-fee total for a row that carries no option id.
	 *
	 * @param string               $data_name       Field data name carried by the row.
	 * @param \WC_Product          $product         Pristine product of the cart line.
	 * @param int[]                $attached_ids    Group ids confirmed attached to the product.
	 * @param array<string, mixed> $selected_fields Field values submitted for the line.
	 * @param array<string, bool>  $counted_fields  Fields already totalled for this line.
	 *
	 * @return float|null Canonical total, or null when the field prices outside its option list.
	 */
	private static function resolved_onetime_field_total( $data_name, $product, array $attached_ids, array $selected_fields, array &$counted_fields ) {
		if ( empty( $attached_ids ) ) {
			return 0.0;
		}

		$field_meta = Helpers::get_field_meta_by_dataname( 0, sanitize_key( (string) $data_name ), implode( ',', $attached_ids ) );

		if ( empty( $field_meta ) || ! is_array( $field_meta ) ) {
			return 0.0;
		}

		if ( ! isset( $field_meta['onetime'] ) || 'on' !== $field_meta['onetime'] ) {
			return 0.0;
		}

		$saved_rows = self::saved_option_rows( $field_meta );

		if ( empty( $saved_rows ) ) {
			// Text-like fields price the field itself; anything else prices outside saved meta.
			if ( ! isset( $field_meta['price'] ) || ! is_scalar( $field_meta['price'] ) || '' === (string) $field_meta['price'] ) {
				return null;
			}

			$key = isset( $field_meta['data_name'] ) ? (string) $field_meta['data_name'] : (string) $data_name;

			if ( isset( $counted_fields[ $key ] ) ) {
				return 0.0;
			}

			$counted_fields[ $key ] = true;

			return empty( self::submitted_option_tokens( isset( $selected_fields[ $key ] ) ? $selected_fields[ $key ] : '' ) )
				? 0.0
				: self::canonical_amount( $field_meta['price'], $product );
		}

		$key = isset( $field_meta['data_name'] ) ? (string) $field_meta['data_name'] : (string) $data_name;

		if ( isset( $counted_fields[ $key ] ) ) {
			return 0.0;
		}

		$counted_fields[ $key ] = true;

		if ( ! isset( $selected_fields[ $key ] ) ) {
			return 0.0;
		}

		$submitted = self::submitted_option_tokens( $selected_fields[ $key ] );

		if ( empty( $submitted ) ) {
			return 0.0;
		}

		$total = 0.0;

		foreach ( $saved_rows as $saved_option ) {
			if ( self::saved_option_matches_submission( $saved_option, $submitted ) ) {
				$total += self::saved_option_amount( $field_meta, $saved_option, $product );
			}
		}

		return $total;
	}

	/**
	 * Canonical amount of one saved option, discount first as the pricing engine does.
	 *
	 * @param array<string, mixed> $field_meta   Saved field definition.
	 * @param array<string, mixed> $saved_option Saved option row.
	 * @param \WC_Product          $product      Pristine product of the cart line.
	 *
	 * @return float
	 */
	private static function saved_option_amount( array $field_meta, array $saved_option, $product ) {
		$amount = '';

		if ( isset( $saved_option['discount'] ) && is_scalar( $saved_option['discount'] ) && (float) $saved_option['discount'] > 0 ) {
			$amount = (string) $saved_option['discount'];
		} elseif ( isset( $saved_option['price'] ) && is_scalar( $saved_option['price'] ) ) {
			$amount = (string) $saved_option['price'];
		}

		return self::canonical_amount( $amount, $product );
	}

	/**
	 * Turns a saved amount into the figure the cart shows.
	 *
	 * Percentages resolve against the product base price. The amount stays net, because
	 * the tax display transformation belongs to the display path alone.
	 *
	 * @param mixed       $amount  Saved amount, possibly a percentage.
	 * @param \WC_Product $product Pristine product of the cart line.
	 *
	 * @return float
	 */
	private static function canonical_amount( $amount, $product ) {
		if ( ! is_scalar( $amount ) || '' === (string) $amount ) {
			return 0.0;
		}

		$amount = (string) $amount;

		if ( false !== strpos( $amount, '%' ) ) {
			$base   = Callbacks::convert_price_back( Helpers::get_product_price( $product, null, 'cart' ) );
			$amount = Engine::get_amount_after_percentage( $base, $amount );
		}

		return (float) $amount;
	}

	/**
	 * Every id shape a saved option row can be referenced by.
	 *
	 * Image rows are addressed as `<data_name>-<id>`, while other types use the id
	 * `Helpers::get_option_id()` generates.
	 *
	 * @param array<string, mixed> $saved_option Saved option row.
	 * @param array<string, mixed> $field_meta   Saved field definition.
	 *
	 * @return string[]
	 */
	private static function saved_option_ids( array $saved_option, array $field_meta ) {
		$data_name = isset( $field_meta['data_name'] ) ? (string) $field_meta['data_name'] : '';
		$ids       = array( (string) Helpers::get_option_id( $saved_option, $field_meta ) );

		if ( isset( $saved_option['id'] ) && is_scalar( $saved_option['id'] ) ) {
			$ids[] = (string) $saved_option['id'];
			$ids[] = $data_name . '-' . $saved_option['id'];
		}

		return array_values( array_unique( $ids ) );
	}

	/**
	 * Whether an option id belongs to the values submitted for its field.
	 *
	 * Cart payloads are untrusted, so rows are only honoured when the submitted values still
	 * evidence the option selection.
	 *
	 * @param array<string, mixed> $field_meta      Saved field definition.
	 * @param string               $option_id       Option id carried by the cart row.
	 * @param array<string, mixed> $selected_fields Field values submitted for the line.
	 *
	 * @return bool
	 */
	private static function option_is_selected( array $field_meta, $option_id, array $selected_fields ) {
		$data_name = isset( $field_meta['data_name'] ) ? (string) $field_meta['data_name'] : '';

		if ( '' === $data_name || ! isset( $selected_fields[ $data_name ] ) ) {
			return false;
		}

		$submitted = self::submitted_option_tokens( $selected_fields[ $data_name ] );

		if ( empty( $submitted ) ) {
			return false;
		}

		$option_id  = (string) $option_id;
		$candidates = array();

		foreach ( $submitted as $token ) {
			$candidates[] = $token;
			$candidates[] = sanitize_key( $data_name . '_' . $token );
			$candidates[] = $data_name . '-' . $token;
		}

		foreach ( self::saved_option_rows( $field_meta ) as $saved_option ) {
			if ( ! self::saved_option_matches_submission( $saved_option, $submitted ) ) {
				continue;
			}

			$candidates = array_merge( $candidates, self::saved_option_ids( $saved_option, $field_meta ) );
		}

		return in_array( $option_id, $candidates, true );
	}

	/**
	 * Flattens a submitted field value into comparable option tokens.
	 *
	 * @param mixed $value Submitted value for one field.
	 *
	 * @return string[]
	 */
	private static function submitted_option_tokens( $value ) {
		$tokens = array();

		foreach ( (array) $value as $entry ) {
			if ( is_array( $entry ) ) {
				$tokens = array_merge( $tokens, self::submitted_option_tokens( $entry ) );
				continue;
			}

			if ( ! is_scalar( $entry ) ) {
				continue;
			}

			$entry    = (string) $entry;
			$tokens[] = $entry;

			$decoded = json_decode( stripslashes( $entry ), true );
			if ( ! is_array( $decoded ) ) {
				continue;
			}

			foreach ( array( 'option_id', 'image_id', 'id' ) as $key ) {
				if ( isset( $decoded[ $key ] ) && is_scalar( $decoded[ $key ] ) ) {
					$tokens[] = (string) $decoded[ $key ];
				}
			}
		}

		return array_values( array_unique( $tokens ) );
	}

	/**
	 * Saved option rows of a field, whichever key its type keeps them under.
	 *
	 * @param array<string, mixed> $field_meta Saved field definition.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private static function saved_option_rows( array $field_meta ) {
		$rows = array();

		foreach ( array( 'options', 'images', 'audio' ) as $key ) {
			if ( empty( $field_meta[ $key ] ) || ! is_array( $field_meta[ $key ] ) ) {
				continue;
			}

			foreach ( $field_meta[ $key ] as $row ) {
				if ( is_array( $row ) ) {
					$rows[] = $row;
				}
			}
		}

		return $rows;
	}

	/**
	 * Whether a saved option row is named by the submitted tokens.
	 *
	 * @param array<string, mixed> $saved_option Saved option row.
	 * @param string[]             $submitted    Tokens submitted for the field.
	 *
	 * @return bool
	 */
	private static function saved_option_matches_submission( array $saved_option, array $submitted ) {
		foreach ( array( 'option', 'title' ) as $key ) {
			if ( ! isset( $saved_option[ $key ] ) || ! is_scalar( $saved_option[ $key ] ) ) {
				continue;
			}

			$label = (string) $saved_option[ $key ];

			if ( in_array( $label, $submitted, true ) ) {
				return true;
			}

			$translated = Helpers::wpml_translate( $label, 'PPOM' );
			if ( is_scalar( $translated ) && in_array( (string) $translated, $submitted, true ) ) {
				return true;
			}
		}

		return false;
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

		$product_id   = $cart_item['product_id'];
		$quantity     = $cart_item['quantity'];
		$variation_id = isset( $cart_item['variation_id'] ) ? absint( $cart_item['variation_id'] ) : 0;

		// The selected variation prices the line, and its pristine copy resolves addons.
		$pristine        = wc_get_product( $variation_id ? $variation_id : $product_id );
		$line_product    = $pristine instanceof \WC_Product ? $pristine : new \WC_Product( $product_id );
		$base_product    = isset( $cart_item['data'] ) && $cart_item['data'] instanceof \WC_Product ? $cart_item['data'] : $line_product;
		$ppom_meta_ids   = isset( $cart_item['ppom']['fields']['id'] ) ? $cart_item['ppom']['fields']['id'] : '';
		$selected_fields = isset( $cart_item['ppom']['fields'] ) && is_array( $cart_item['ppom']['fields'] ) ? $cart_item['ppom']['fields'] : array();
		$attached_ids    = null;
		$counted_fields  = array();

		$price          = 0.0;
		$resolved_price = 0.0;

		foreach ( $option_prices as $option ) {
			if ( ! is_array( $option ) ) {
				continue;
			}

			$option = Helpers::translation_options( $option );
			if ( ! isset( $option['apply'] ) || 'onetime' !== $option['apply'] ) {
				continue;
			}

			$option_price = isset( $option['price'] ) ? (float) $option['price'] : 0.0;

			$resolved = null;

			if ( ! empty( $ppom_meta_ids ) ) {
				if ( null === $attached_ids ) {
					$attached_ids = self::attached_meta_ids( $product_id, $ppom_meta_ids );
				}

				$resolved = self::canonical_onetime_price( $option, $line_product, $attached_ids, $selected_fields, $counted_fields );
			}

			if ( null !== $resolved ) {
				$option_price = $resolved;
			} else {
				if ( isset( $option['discount'] ) && $option['discount'] > 0 ) {
					$option_price = (float) $option['discount'];
				}

				$option_price = self::unresolved_payload_amount( $option_price );
			}

			$option_price = apply_filters( 'ppom_option_price', $option_price );
			$option_price = floatval( wp_strip_all_tags( (string) $option_price ) );

			// Accumulate every fixed-fee option on the line, not just the last one.
			if ( null !== $resolved ) {
				$resolved_price += $option_price;
			} else {
				$price += $option_price;
			}
		}

		// Saved amounts are net, so only they take the store's tax display treatment.
		if ( $resolved_price > 0 ) {
			$resolved_price = (float) apply_filters( 'ppom_option_price_vat', $resolved_price, $line_product );
		}

		$price += $resolved_price;

		if ( 0.0 === $price ) {
			return $item_subtotal;
		}
		$product_price = floatval( $base_product->get_price() ) * $quantity;
		$item_subtotal = $product_price + $price;
		return Helpers::price( $item_subtotal );
	}

	public static function control_checkout_quantity( $quantity, $cart_item, $cart_item_key ) {

		// ppom_pa($cart_item);
		if ( ! isset( $cart_item['ppom']['fields'] ) ) {
			return $quantity;
		}

		$ppom_fields_post = $cart_item['ppom']['fields'];
		$product_id       = $cart_item['product_id'];

		if ( Helpers::is_cart_quantity_updatable( $product_id ) ) {
			return $quantity;
		}

		$ppom_has_quantities = Engine::price_get_total_quantities( $ppom_fields_post, $product_id );

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

		if ( Helpers::is_cart_quantity_updatable( $product_id ) ) {
			return $quantity;
		}

		$ppom_has_quantities = Engine::price_get_total_quantities( $ppom_fields_post, $product_id );

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

		if ( Helpers::is_cart_quantity_updatable( $product_id ) ) {
			return $quantity;
		}

		$ppom_has_quantities = Engine::price_get_total_quantities( $ppom_fields_post, $product_id );

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

		if ( Helpers::is_cart_quantity_updatable( $product_id ) ) {
			return $quantity;
		}

		$ppom_has_quantities = Engine::price_get_total_quantities( $ppom_fields_post, $product_id );

		if ( $ppom_has_quantities > 0 ) {
			$quantity = $ppom_has_quantities;
		}

		return $quantity;
	}

	public static function cart_update_validate( $cart_validated, $cart_item_key, $values, $quantity ) {

		$max_quantity = Helpers::get_cart_item_max_quantity( $values );

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
