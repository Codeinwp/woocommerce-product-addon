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
use PPOM\Files\Handler;
use PPOM\Hooks\Callbacks;
use PPOM\Pricing\Engine;
use PPOM\Pricing\PriceMode;
use PPOM\Support\Helpers;
use PPOM\WooCommerce\Product\ProductHandler;

/**
 * @internal
 */
final class CartHandler {

	/**
	 * Cart item key that keeps each add of a quantity-controlling product on its own line.
	 *
	 * WooCommerce merges identical adds by summing their quantities, while those
	 * fields fix the line quantity themselves.
	 */
	private const LINE_KEY = 'ppom_line_key';

	/**
	 * Legacy `ppom_option_price` applies whose amounts the server builds from saved field settings.
	 */
	private const LEGACY_PRICED_APPLIES = array( 'variable', 'onetime', 'quantities', 'bulkquantity', 'fixedprice', 'measure' );

	/**
	 * Option fields the legacy line never charges: their rows only size the order or pick a matrix.
	 */
	private const LEGACY_UNPRICED_FIELD_TYPES = array( 'eventcalendar', 'pricematrix' );

	/**
	 * Option fields whose submitted value carries the option id rather than its label.
	 */
	private const LEGACY_ID_VALUED_FIELD_TYPES = array( 'image', 'audio', 'cropper' );

	/**
	 * Option fields `Helpers::get_field_option_price_by_id()` cannot price, so their rows carry the price.
	 */
	private const LEGACY_ROW_PRICED_FIELD_TYPES = array( 'audio', 'cropper' );

	/**
	 * Legacy option-price rows: summed quantities when `include` is empty, or 1 when `include` is `on`.
	 *
	 * @param array $option_prices Legacy `ppom_option_price` rows.
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
	 * @param array $option_prices Legacy `ppom_option_price` rows (may be empty).
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
	 * @param int   $quantity     Requested quantity.
	 *
	 * @return array
	 *
	 * @throws \Exception When an add that skipped WooCommerce's validation fails PPOM's,
	 *                    or a cropped image exceeds its field's size limit.
	 *
	 * @see ppom_check_validation()
	 * @see ppom_price_controller()
	 * @see Helpers::make_meta_data()
	 */
	public static function add_cart_item_data( $cart, $product_id, $variation_id = 0, $quantity = 1 ) {

		$is_bundled = function_exists( 'wc_pb_is_bundled_cart_item' ) && wc_pb_is_bundled_cart_item( $cart );

		// Before the payload and edit-removal steps; WC_Cart::add_to_cart() fails the add on throw.
		if ( ! $is_bundled && ! ProductHandler::validate_unvalidated_add( (int) $product_id, (int) $quantity, (int) $variation_id ) ) {
			throw new \Exception();
		}

		// A crop the field cannot store fails the add before an edited cart item is replaced.
		$oversized_crop = $is_bundled ? null : Callbacks::oversized_crop_field( (int) $product_id, $_POST );
		if ( null !== $oversized_crop ) {
			throw new \Exception(
				sprintf(
					/* translators: %s: cropper field title */
					esc_html__( '%s: the cropped image is larger than the allowed file size.', 'woocommerce-product-addon' ),
					esc_html( (string) ( $oversized_crop['title'] ?? '' ) )
				)
			);
		}

		if ( ! isset( $_POST['ppom'] ) ) {
			return $cart;
		}

		$ppom = new PPOM_Meta( $product_id );
		if ( ! $ppom->ppom_settings ) {
			return $cart;
		}

		$verified = self::remove_replaced_cart_item();

		// ADDED WC BUNDLES COMPATIBILITY
		if ( $is_bundled ) {
			return $cart;
		}

		// Keep only this visitor's uploads before saving or storing the payload.
		$owned_payload = Handler::retain_owned_uploads( $_POST['ppom'], (int) $product_id, $verified );

		// PPOM also saving cropped images under this filter.
		$ppom_posted_fields = apply_filters( 'ppom_add_cart_item_data', $owned_payload, $_POST );
		$ppom_posted_fields = Helpers::filter_ppom_payload_by_active_variation( (array) $ppom_posted_fields, $product_id, $variation_id );
		if ( empty( $ppom_posted_fields['fields'] ) ) {
			return $cart;
		}

		// The filter above receives raw $_POST and may mutate the payload, so
		// re-scrub before it is stored: only this visitor's uploads persist.
		$cart['ppom'] = Handler::retain_owned_uploads( $ppom_posted_fields, (int) $product_id, $verified );

		$cart[ Handler::VERIFIED_FILES_KEY ] = Handler::payload_file_names( $cart['ppom'] );

		return self::separate_quantity_controlled_line( $cart, (int) $product_id );
	}

	/**
	 * Gives the cart item of a quantity-controlling product its own line.
	 *
	 * Sold-individually products keep the shared cart id, which is how
	 * WooCommerce rejects a second add.
	 *
	 * @param array<string, mixed> $cart_item_data Cart item data.
	 * @param int                  $product_id     Parent product ID.
	 *
	 * @return array<string, mixed>
	 */
	public static function separate_quantity_controlled_line( array $cart_item_data, int $product_id ): array {
		if ( Helpers::is_cart_quantity_updatable( $product_id ) ) {
			return $cart_item_data;
		}

		$product = wc_get_product( $product_id );
		if ( $product && $product->is_sold_individually() ) {
			return $cart_item_data;
		}

		$cart_item_data[ self::LINE_KEY ] = wp_generate_uuid4();

		return $cart_item_data;
	}

	/**
	 * Upload names verified for the cart item an edit replaces.
	 *
	 * The edited item resubmits the same uploads, which the current session may
	 * not own when the cart was restored on another device.
	 *
	 * @return list<string>
	 */
	public static function replaced_item_verified_files(): array {

		$replaced = self::replaced_cart_item();

		return null === $replaced ? array() : Handler::verified_file_names( $replaced['item'] );
	}

	/**
	 * Removes the cart item an edit replaces and returns its verified uploads.
	 *
	 * @return list<string>
	 */
	private static function remove_replaced_cart_item(): array {

		$replaced = self::replaced_cart_item();
		if ( null === $replaced ) {
			return array();
		}

		$replaced['cart']->remove_cart_item( $replaced['key'] );

		return Handler::verified_file_names( $replaced['item'] );
	}

	/**
	 * The cart item named by ppom_cart_key, when it is in this session's cart.
	 *
	 * @return array{cart: \WC_Cart, key: string, item: array<string, mixed>}|null
	 */
	private static function replaced_cart_item(): ?array {

		$wc_cart = function_exists( 'WC' ) ? WC()->cart : null;

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- WooCommerce add-to-cart sends no nonce; the key only matches this session's cart.
		$key = isset( $_POST['ppom_cart_key'] ) && is_string( $_POST['ppom_cart_key'] ) ? sanitize_text_field( wp_unslash( $_POST['ppom_cart_key'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		$item = '' !== $key && $wc_cart ? $wc_cart->get_cart_item( $key ) : array();
		if ( empty( $item ) ) {
			return null;
		}

		return array(
			'cart' => $wc_cart,
			'key'  => $key,
			'item' => $item,
		);
	}

	public static function update_cart_fees( $cart_items, $values ) {

		if ( empty( $cart_items ) ) {
			return $cart_items;
		}

		if ( empty( $values['ppom'] ) || ! is_array( $values['ppom'] ) ) {
			return $cart_items;
		}

		$wc_product = $cart_items['data'];
		$product_id = Helpers::get_product_id( $wc_product );

		$variation_id   = isset( $cart_items['variation_id'] ) ? absint( $cart_items['variation_id'] ) : 0;
		$values['ppom'] = Helpers::filter_ppom_payload_by_active_variation( $values['ppom'], $product_id, $variation_id );

		$option_prices = self::legacy_option_price_rows( $values['ppom'], (int) $product_id, $variation_id, $wc_product );

		if ( empty( $option_prices ) ) {
			return $cart_items;
		}

		$ppom_meta_ids = '';
		// removing id field
		if ( ! empty( $values ['ppom'] ['fields']['id'] ) ) {
			$ppom_meta_ids = implode( ',', self::attached_meta_ids( $product_id, $values ['ppom'] ['fields']['id'] ) );
			unset( $values ['ppom'] ['fields']['id'] );
		}

		// Stored price in store currency; switcher read filters apply to the line afterwards (#755).
		$ppom_item_org_price = $wc_product->get_price( 'edit' );
		// $ppom_item_org_price = $wc_product->get_price();

		$ppom_item_order_qty = floatval( $cart_items['quantity'] );

		$total_option_price = 0;

		$ppom_quantities_price        = 0;
		$ppom_quantities_usebaseprice = false;
		$ppom_quantities_include_base = false;

		$ppom_total_discount = 0;
		$ppon_onetime_cost   = 0;
		$ppomm_measures      = 1;    // meassure need to be multiple with each so it will be 1

		$matrix_ctx            = self::legacy_option_prices_matrix_context( $option_prices, $cart_items['quantity'] );
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
		foreach ( $option_prices as $option ) {
			$resolved_line_price = null;
			if ( isset( $option['apply'] ) && ( 'variable' === $option['apply'] || 'onetime' === $option['apply'] ) ) {
				$resolved_line_price = isset( $option['price'] ) ? $option['price'] : 0;
				if ( isset( $option['data_name'], $option['option_id'] ) && ! isset( $option['price'] ) ) {
					$resolved_line_price = Helpers::get_field_option_price_by_id( $option, $wc_product, $ppom_meta_ids, true );
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

			foreach ( self::legacy_onetime_fees( $item ) as $onetime ) {
				$fee = $onetime['row'];

				$label = $fee_no . '-' . $fee['product_title'] . ': ' . $fee['label'];
				$label = apply_filters( 'ppom_fixed_fee_label', $label, $fee, $item );

				$fee_price = apply_filters( 'ppom_cart_fixed_fee', Helpers::convert_fee_price( $onetime['amount'] ), $fee, $cart );

				if ( $fee_price != 0 ) {
					$cart->add_fee( esc_html( $label ), $fee_price, $onetime['taxable'] );
					++$fee_no;
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

		if ( empty( $cart_item['ppom'] ) || ! is_array( $cart_item['ppom'] ) || ! isset( $cart_item['data'] ) || ! $cart_item['data'] instanceof \WC_Product ) {
			return $quantity;
		}

		$option_prices = self::legacy_option_price_rows(
			$cart_item['ppom'],
			absint( $cart_item['product_id'] ),
			isset( $cart_item['variation_id'] ) ? absint( $cart_item['variation_id'] ) : 0,
			$cart_item['data']
		);

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
	 * Fixed fees of a legacy cart line, priced from the saved field settings.
	 *
	 * @param array<string, mixed> $item Cart item.
	 *
	 * @return list<array{row: array<string, mixed>, amount: float, taxable: bool}>
	 */
	private static function legacy_onetime_fees( array $item ): array {
		$pristine = self::line_product( $item );

		if ( null === $pristine || empty( $item['ppom'] ) || ! is_array( $item['ppom'] ) ) {
			return array();
		}

		$product_id      = absint( $item['product_id'] );
		$variation_id    = isset( $item['variation_id'] ) ? absint( $item['variation_id'] ) : 0;
		$selected_fields = self::posted_fields( $item['ppom'] );
		$attached_ids    = self::attached_field_group_ids( $product_id, $selected_fields );
		$counted_fields  = array();
		$fees            = array();

		foreach ( self::legacy_option_price_rows( $item['ppom'], $product_id, $variation_id, $pristine ) as $row ) {
			if ( 'onetime' !== $row['apply'] ) {
				continue;
			}

			$amount = isset( $row['onetime_amount'] ) ? (float) $row['onetime_amount'] : self::canonical_onetime_price( $row, $pristine, $attached_ids, $selected_fields, $counted_fields );

			if ( null !== $amount ) {
				$fees[] = array(
					'row'     => $row,
					'amount'  => $amount,
					'taxable' => true === self::resolved_onetime_taxable( $row, $attached_ids ),
				);
			}
		}

		return $fees;
	}

	/**
	 * Product a cart line is priced from: the selected variation, or the product itself.
	 *
	 * @param array<string, mixed> $item Cart item.
	 *
	 * @return \WC_Product|null
	 */
	private static function line_product( array $item ): ?\WC_Product {
		$product_id   = isset( $item['product_id'] ) ? absint( $item['product_id'] ) : 0;
		$variation_id = isset( $item['variation_id'] ) ? absint( $item['variation_id'] ) : 0;
		$product      = wc_get_product( $variation_id ? $variation_id : $product_id );

		return $product instanceof \WC_Product ? $product : null;
	}

	/**
	 * Submitted field values of a PPOM payload, keyed by data name, with the group ids under `id`.
	 *
	 * @param array<string, mixed> $ppom Cart item PPOM payload.
	 *
	 * @return array<string, mixed>
	 */
	private static function posted_fields( array $ppom ): array {
		return isset( $ppom['fields'] ) && is_array( $ppom['fields'] ) ? $ppom['fields'] : array();
	}

	/**
	 * Group ids the submitted fields name that are attached to the product.
	 *
	 * @param int                  $product_id Parent product ID.
	 * @param array<string, mixed> $fields     Submitted field values.
	 *
	 * @return int[]
	 */
	private static function attached_field_group_ids( int $product_id, array $fields ): array {
		return self::attached_meta_ids( $product_id, isset( $fields['id'] ) ? $fields['id'] : '' );
	}

	/**
	 * Legacy `ppom_option_price` rows of a cart line, priced from the saved field settings.
	 *
	 * The posted rows are untrusted, so only those the legacy pricing reads for
	 * something other than an amount (the price matrix selector) are kept.
	 *
	 * @param array<string, mixed> $ppom         Cart item PPOM payload.
	 * @param int                  $product_id   Parent product ID.
	 * @param int                  $variation_id Selected variation ID, or 0.
	 * @param \WC_Product          $line_product Product the line is priced from.
	 *
	 * @return list<array<string, mixed>>
	 */
	private static function legacy_option_price_rows( array $ppom, int $product_id, int $variation_id, \WC_Product $line_product ): array {
		$ppom         = Helpers::filter_ppom_payload_by_active_variation( $ppom, $product_id, $variation_id );
		$rows         = self::unpriced_payload_rows( $ppom );
		$fields       = self::posted_fields( $ppom );
		$attached_ids = self::attached_field_group_ids( $product_id, $fields );

		if ( empty( $attached_ids ) ) {
			return $rows;
		}

		$meta_ids      = implode( ',', $attached_ids );
		$product_title = get_post_field( 'post_title', $product_id );
		unset( $fields['id'] );

		foreach ( $fields as $data_name => $value ) {
			$field_meta = Helpers::get_field_meta_by_dataname( 0, sanitize_key( (string) $data_name ), $meta_ids );

			if ( empty( $field_meta ) || ! is_array( $field_meta ) || Helpers::is_field_hidden_in_payload( (string) $data_name, $ppom ) ) {
				continue;
			}

			foreach ( self::legacy_field_rows( $field_meta, $value, $line_product ) as $row ) {
				$row['product_title'] = $product_title;
				$rows[]               = $row;
			}
		}

		return $rows;
	}

	/**
	 * Posted rows whose apply the legacy pricing never takes an amount from.
	 *
	 * @param array<string, mixed> $ppom Cart item PPOM payload.
	 *
	 * @return list<array<string, mixed>>
	 */
	private static function unpriced_payload_rows( array $ppom ): array {
		$posted = isset( $ppom['ppom_option_price'] ) && is_string( $ppom['ppom_option_price'] ) ? json_decode( wp_unslash( $ppom['ppom_option_price'] ), true ) : array();
		$rows   = array();

		foreach ( is_array( $posted ) ? $posted : array() as $row ) {
			if ( is_array( $row ) && isset( $row['apply'] ) && ! in_array( $row['apply'], self::LEGACY_PRICED_APPLIES, true ) ) {
				$rows[] = $row;
			}
		}

		return $rows;
	}

	/**
	 * Rows the legacy product page builds for one submitted field.
	 *
	 * @param array<string, mixed> $field_meta Saved field definition.
	 * @param mixed                $value      Submitted value of the field.
	 * @param \WC_Product          $product    Product the line is priced from.
	 *
	 * @return list<array<string, mixed>>
	 */
	private static function legacy_field_rows( array $field_meta, $value, \WC_Product $product ): array {
		switch ( isset( $field_meta['type'] ) ? $field_meta['type'] : '' ) {
			case 'quantities':
				return self::legacy_quantities_rows( $field_meta, $value, $product );
			case 'selectqty':
				return self::legacy_selectqty_rows( $field_meta, $value, $product );
			case 'vqmatrix':
				return self::legacy_vqmatrix_rows( $field_meta, $value, $product );
			case 'qtypack':
				return self::legacy_qtypack_rows( $field_meta, $value, $product );
			case 'bulkquantity':
				return self::legacy_bulkquantity_rows( $field_meta, $value, $product );
			default:
				return self::legacy_single_field_rows( $field_meta, $value, $product );
		}
	}

	/**
	 * Rows of the field types that do not size the order.
	 *
	 * @param array<string, mixed> $field_meta Saved field definition.
	 * @param mixed                $value      Submitted value of the field.
	 * @param \WC_Product          $product    Product the line is priced from.
	 *
	 * @return list<array<string, mixed>>
	 */
	private static function legacy_single_field_rows( array $field_meta, $value, \WC_Product $product ): array {
		switch ( isset( $field_meta['type'] ) ? $field_meta['type'] : '' ) {
			case 'fixedprice':
				return self::legacy_fixedprice_rows( $field_meta, $value, $product );
			case 'measure':
				return self::legacy_measure_rows( $field_meta, $value );
			case 'text':
			case 'color':
			case 'date':
				return self::legacy_text_rows( $field_meta, $value, $product );
			case 'file':
				return self::legacy_upload_rows( $field_meta, $value, $product );
			case 'cropper':
				return array_merge( self::legacy_upload_rows( $field_meta, $value, $product ), self::legacy_option_rows( $field_meta, $value, $product ) );
			default:
				return self::legacy_option_rows( $field_meta, $value, $product );
		}
	}

	/**
	 * The row of an upload field: its saved file cost for each uploaded file.
	 *
	 * Fixed fees carry their store-currency amount, as the one-time fee resolver
	 * prices only option and text fields.
	 *
	 * @param array<string, mixed> $field_meta Saved field definition.
	 * @param mixed                $value      Submitted uploads keyed by file id.
	 * @param \WC_Product          $product    Product the line is priced from.
	 *
	 * @return list<array<string, mixed>>
	 */
	private static function legacy_upload_rows( array $field_meta, $value, \WC_Product $product ): array {
		$cost  = isset( $field_meta['file_cost'] ) && is_scalar( $field_meta['file_cost'] ) ? (string) $field_meta['file_cost'] : '';
		$files = 0;

		foreach ( is_array( $value ) ? $value : array() as $file ) {
			$files += is_array( $file ) && isset( $file['org'] ) ? 1 : 0;
		}

		if ( '' === $cost || 0 === $files ) {
			return array();
		}

		$row = array(
			'apply'     => self::legacy_field_apply( $field_meta ),
			'label'     => self::field_title( $field_meta ),
			'price'     => (float) Helpers::saved_option_amount( $cost, $product, true ) * $files,
			'data_name' => sanitize_key( (string) $field_meta['data_name'] ),
		);

		if ( 'onetime' === $row['apply'] ) {
			$row['onetime_amount'] = self::canonical_amount( $cost, $product ) * $files;
		}

		return array( $row );
	}

	/**
	 * One row per saved option the submitted value selects.
	 *
	 * @param array<string, mixed> $field_meta Saved field definition.
	 * @param mixed                $value      Submitted value of the field.
	 * @param \WC_Product          $product    Product the line is priced from.
	 *
	 * @return list<array<string, mixed>>
	 */
	private static function legacy_option_rows( array $field_meta, $value, \WC_Product $product ): array {
		$type = $field_meta['type'] ?? '';

		if ( in_array( $type, self::LEGACY_UNPRICED_FIELD_TYPES, true ) ) {
			return array();
		}

		$submitted = self::submitted_option_tokens( $value );
		$rows      = array();

		foreach ( self::saved_option_rows( $field_meta ) as $saved_option ) {
			if ( ! self::legacy_option_selected( $saved_option, $field_meta, $submitted ) ) {
				continue;
			}

			$row = array(
				'apply'     => self::legacy_field_apply( $field_meta ),
				'label'     => self::legacy_option_label( $saved_option, $field_meta ),
				'data_name' => sanitize_key( (string) $field_meta['data_name'] ),
				'option_id' => self::legacy_option_id( $saved_option, $field_meta ),
			);

			if ( in_array( $type, self::LEGACY_ROW_PRICED_FIELD_TYPES, true ) ) {
				$row['price'] = (float) Helpers::saved_option_amount( isset( $saved_option['price'] ) && is_scalar( $saved_option['price'] ) ? (string) $saved_option['price'] : '', $product, true );
			}

			$rows[] = $row;
		}

		return $rows;
	}

	/**
	 * Fee label of a saved option: the field title and the option name.
	 *
	 * @param array<string, mixed> $saved_option Saved option row.
	 * @param array<string, mixed> $field_meta   Saved field definition.
	 *
	 * @return string
	 */
	private static function legacy_option_label( array $saved_option, array $field_meta ): string {
		$option_label = isset( $saved_option['option'] ) ? $saved_option['option'] : ( $saved_option['title'] ?? '' );

		return trim( self::field_title( $field_meta ) . ' ' . ( is_scalar( $option_label ) ? (string) $option_label : '' ) );
	}

	/**
	 * Whether the submitted value selects a saved option.
	 *
	 * Labels are merchant-editable and may equal another option's id, so ids count
	 * only for fields that post the id itself.
	 *
	 * @param array<string, mixed> $saved_option Saved option row.
	 * @param array<string, mixed> $field_meta   Saved field definition.
	 * @param string[]             $submitted    Tokens submitted for the field.
	 *
	 * @return bool
	 */
	private static function legacy_option_selected( array $saved_option, array $field_meta, array $submitted ): bool {
		if ( in_array( $field_meta['type'] ?? '', self::LEGACY_ID_VALUED_FIELD_TYPES, true ) ) {
			return array() !== array_intersect( self::saved_option_ids( $saved_option, $field_meta ), $submitted );
		}

		return self::saved_option_matches_submission( $saved_option, $submitted );
	}

	/**
	 * Id the cart price lookup addresses a saved option by.
	 *
	 * @param array<string, mixed> $saved_option Saved option row.
	 * @param array<string, mixed> $field_meta   Saved field definition.
	 *
	 * @return string
	 */
	private static function legacy_option_id( array $saved_option, array $field_meta ): string {
		$id = isset( $saved_option['id'] ) && is_scalar( $saved_option['id'] ) ? (string) $saved_option['id'] : '';

		if ( 'image' === $field_meta['type'] ) {
			return $field_meta['data_name'] . '-' . $id;
		}

		return '' !== $id ? $id : (string) Helpers::get_option_id( $saved_option, $field_meta );
	}

	/**
	 * Row of a priced text-input field the shopper filled in.
	 *
	 * The page prices Text and Color inputs, and Date only with the jQuery datepicker,
	 * which renders it as a text input.
	 *
	 * @param array<string, mixed> $field_meta Saved field definition.
	 * @param mixed                $value      Submitted value of the field.
	 * @param \WC_Product          $product    Product the line is priced from.
	 *
	 * @return list<array<string, mixed>>
	 */
	private static function legacy_text_rows( array $field_meta, $value, \WC_Product $product ): array {
		$price         = isset( $field_meta['price'] ) && is_scalar( $field_meta['price'] ) ? (string) $field_meta['price'] : '';
		$native_picker = 'date' === $field_meta['type'] && 'on' !== ( $field_meta['jquery_dp'] ?? '' );

		if ( $native_picker || '' === $price || ! is_scalar( $value ) || '' === (string) $value ) {
			return array();
		}

		return array(
			array(
				'apply'     => self::legacy_field_apply( $field_meta ),
				'label'     => self::field_title( $field_meta ),
				'price'     => (float) Helpers::saved_option_amount( $price, $product, true ),
				'data_name' => sanitize_key( (string) $field_meta['data_name'] ),
			),
		);
	}

	/**
	 * One `quantities` row per option of a Quantities field.
	 *
	 * @param array<string, mixed> $field_meta Saved field definition.
	 * @param mixed                $value      Submitted quantities keyed by option label.
	 * @param \WC_Product          $product    Product the line is priced from.
	 *
	 * @return list<array<string, mixed>>
	 */
	private static function legacy_quantities_rows( array $field_meta, $value, \WC_Product $product ): array {
		$rows = array();

		foreach ( self::converted_options( $field_meta, $product ) as $option ) {
			$label  = (string) $option['raw'];
			$rows[] = array(
				'apply'         => 'quantities',
				'label'         => $label,
				'price'         => self::option_line_amount( $option, $product ),
				'quantity'      => self::submitted_quantity( $value, $label ),
				'include'       => self::legacy_includes_base( $field_meta ),
				'usebase_price' => 'no',
				'unlink_qty'    => isset( $field_meta['unlink_order_qty'] ) && is_scalar( $field_meta['unlink_order_qty'] ) ? (string) $field_meta['unlink_order_qty'] : '',
			);
		}

		return $rows;
	}

	/**
	 * The `quantities` row of a Select Quantity field.
	 *
	 * @param array<string, mixed> $field_meta Saved field definition.
	 * @param mixed                $value      Submitted `option` and `qty`.
	 * @param \WC_Product          $product    Product the line is priced from.
	 *
	 * @return list<array<string, mixed>>
	 */
	private static function legacy_selectqty_rows( array $field_meta, $value, \WC_Product $product ): array {
		$options = self::converted_options( $field_meta, $product );
		$key     = is_array( $value ) && isset( $value['option'] ) && is_scalar( $value['option'] ) ? (string) $value['option'] : '';

		if ( ! isset( $options[ $key ] ) ) {
			return array();
		}

		$include = self::legacy_includes_base( $field_meta );

		return array(
			array(
				'apply'         => 'quantities',
				'label'         => self::field_title( $field_meta ),
				'price'         => self::option_line_amount( $options[ $key ], $product ),
				'quantity'      => self::posted_quantity( $value['qty'] ?? 0 ),
				'include'       => $include,
				'usebase_price' => 'on' === $include ? 'no' : 'yes',
				'unlink_qty'    => $include,
			),
		);
	}

	/**
	 * One `quantities` row per cell of a Variation Quantity Matrix field.
	 *
	 * @param array<string, mixed> $field_meta Saved field definition.
	 * @param mixed                $value      Submitted quantities keyed by `<priced>-<simple>` option ids.
	 * @param \WC_Product          $product    Product the line is priced from.
	 *
	 * @return list<array<string, mixed>>
	 */
	private static function legacy_vqmatrix_rows( array $field_meta, $value, \WC_Product $product ): array {
		$priced_options = isset( $field_meta['options'] ) && is_array( $field_meta['options'] ) ? $field_meta['options'] : array();
		$simple_options = isset( $field_meta['row_options'] ) && is_array( $field_meta['row_options'] ) ? $field_meta['row_options'] : array();
		$rows           = array();

		foreach ( $priced_options as $priced ) {
			$price = self::option_or_default_price( $priced, $field_meta, $product );

			foreach ( $simple_options as $simple ) {
				$key    = ucfirst( self::trimmed_option_id( $priced ) ) . '-' . ucfirst( self::trimmed_option_id( $simple ) );
				$rows[] = array(
					'apply'         => 'quantities',
					'label'         => $key,
					'price'         => (float) Helpers::saved_option_amount( $price, $product, true ),
					'quantity'      => self::submitted_quantity( $value, $key ),
					'include'       => self::legacy_includes_base( $field_meta ),
					'usebase_price' => empty( $priced['price'] ) ? 'yes' : 'no',
				);
			}
		}

		return $rows;
	}

	/**
	 * The `variable` row of a Quantities Pack field: every option's price times its quantity.
	 *
	 * @param array<string, mixed> $field_meta Saved field definition.
	 * @param mixed                $value      Submitted quantities keyed by option label.
	 * @param \WC_Product          $product    Product the line is priced from.
	 *
	 * @return list<array<string, mixed>>
	 */
	private static function legacy_qtypack_rows( array $field_meta, $value, \WC_Product $product ): array {
		$options = isset( $field_meta['options'] ) && is_array( $field_meta['options'] ) ? $field_meta['options'] : array();
		$total   = 0.0;

		foreach ( $options as $option ) {
			if ( ! is_array( $option ) || ! isset( $option['option'] ) || ! is_scalar( $option['option'] ) ) {
				continue;
			}

			$price  = self::option_or_default_price( $option, $field_meta, $product );
			$total += self::submitted_quantity( $value, trim( (string) $option['option'] ) ) * (float) Helpers::saved_option_amount( $price, $product, true );
		}

		if ( 0.0 === $total ) {
			return array();
		}

		return array(
			array(
				'apply' => 'variable',
				'label' => self::field_title( $field_meta ),
				'price' => $total,
			),
		);
	}

	/**
	 * The `bulkquantity` row: the selected column and base price of the tier the quantity falls in.
	 *
	 * @param array<string, mixed> $field_meta Saved field definition.
	 * @param mixed                $value      Submitted `option` and `qty`.
	 * @param \WC_Product          $product    Product the line is priced from.
	 *
	 * @return list<array<string, mixed>>
	 */
	private static function legacy_bulkquantity_rows( array $field_meta, $value, \WC_Product $product ): array {
		if ( ! is_array( $value ) || ! isset( $value['option'], $value['qty'] ) || ! is_scalar( $value['option'] ) ) {
			return array();
		}

		$tiers    = isset( $field_meta['options'] ) && is_string( $field_meta['options'] ) ? json_decode( wp_unslash( $field_meta['options'] ), true ) : array();
		$quantity = self::posted_quantity( $value['qty'] );
		$tier     = Engine::price_bulkquantity_chunk( $product, $tiers, $quantity );
		$column   = (string) $value['option'];

		return array(
			array(
				'apply'    => 'bulkquantity',
				'label'    => $column,
				'price'    => in_array( $column, array( 'Quantity Range', 'Base Price' ), true ) ? 0.0 : self::tier_amount( $tier, $column ),
				'base'     => self::tier_amount( $tier, 'Base Price' ),
				'quantity' => $quantity,
				'include'  => apply_filters( 'ppom_bulkquantity_includeprice', '', $field_meta, $product ),
			),
		);
	}

	/**
	 * The `fixedprice` row: the per-unit price of the selected package.
	 *
	 * @param array<string, mixed> $field_meta Saved field definition.
	 * @param mixed                $value      Submitted package label.
	 * @param \WC_Product          $product    Product the line is priced from.
	 *
	 * @return list<array<string, mixed>>
	 */
	private static function legacy_fixedprice_rows( array $field_meta, $value, \WC_Product $product ): array {
		$options = self::converted_options( $field_meta, $product );
		$key     = is_scalar( $value ) ? (string) $value : '';

		if ( ! isset( $options[ $key ] ) || ! function_exists( 'PPOM_FPP' ) ) {
			return array();
		}

		$package    = $options[ $key ];
		$unit_price = empty( $package['price'] ) ? 0 : PPOM_FPP()->get_unit_price( $package, $field_meta );

		return array(
			array(
				'apply'     => 'fixedprice',
				'label'     => (string) $package['raw'],
				'price'     => $package['price'],
				'unitprice' => (float) Helpers::convert_to_wcml_line_currency( $unit_price ),
				'quantity'  => empty( $package['price'] ) ? 0 : $package['raw'],
			),
		);
	}

	/**
	 * The `measure` row: the entered measurement scales the product price.
	 *
	 * @param array<string, mixed> $field_meta Saved field definition.
	 * @param mixed                $value      Submitted measurement.
	 *
	 * @return list<array<string, mixed>>
	 */
	private static function legacy_measure_rows( array $field_meta, $value ): array {
		$multiplier = isset( $field_meta['price-multiplier'] ) && is_numeric( $field_meta['price-multiplier'] ) ? (float) $field_meta['price-multiplier'] : 1.0;

		return array(
			array(
				'apply'            => 'measure',
				'label'            => self::field_title( $field_meta ),
				'data_name'        => sanitize_key( (string) $field_meta['data_name'] ),
				'qty'              => is_scalar( $value ) ? (string) $value : '',
				'use_units'        => 'no',
				'price_multiplier' => $multiplier,
			),
		);
	}

	/**
	 * Saved options keyed by label, as the product page renders them.
	 *
	 * @param array<string, mixed> $field_meta Saved field definition.
	 * @param \WC_Product          $product    Product the line is priced from.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	private static function converted_options( array $field_meta, \WC_Product $product ): array {
		$options   = Helpers::convert_options_to_key_val( isset( $field_meta['options'] ) ? $field_meta['options'] : array(), $field_meta, $product );
		$converted = array();

		foreach ( is_array( $options ) ? $options : array() as $key => $option ) {
			if ( is_array( $option ) && isset( $option['raw'] ) ) {
				$converted[ (string) $key ] = $option;
			}
		}

		return $converted;
	}

	/**
	 * Line amount of a converted option, from its saved price.
	 *
	 * @param array<string, mixed> $option  Option as `converted_options()` returns it.
	 * @param \WC_Product          $product Product the line is priced from.
	 *
	 * @return float
	 */
	private static function option_line_amount( array $option, \WC_Product $product ): float {
		$raw_price = isset( $option['raw_price'] ) && is_scalar( $option['raw_price'] ) ? (string) $option['raw_price'] : '';

		return (float) Helpers::saved_option_amount( $raw_price, $product, true );
	}

	/**
	 * Saved option price, or the field's default price when the option has none.
	 *
	 * Price matrix products ignore the default price, as the product page does.
	 *
	 * @param mixed                $option     Saved option row.
	 * @param array<string, mixed> $field_meta Saved field definition.
	 * @param \WC_Product          $product    Product the line is priced from.
	 *
	 * @return string
	 */
	private static function option_or_default_price( $option, array $field_meta, \WC_Product $product ): string {
		if ( is_array( $option ) && isset( $option['price'] ) && is_scalar( $option['price'] ) && '' !== (string) $option['price'] ) {
			return (string) $option['price'];
		}

		if ( empty( $field_meta['default_price'] ) || ! is_scalar( $field_meta['default_price'] ) || Helpers::has_field_by_type( Helpers::get_product_id( $product ), 'pricematrix' ) ) {
			return '';
		}

		return (string) $field_meta['default_price'];
	}

	/**
	 * Saved option id, trimmed as the matrix template keys its inputs.
	 *
	 * @param mixed $option Saved option row.
	 *
	 * @return string
	 */
	private static function trimmed_option_id( $option ): string {
		return is_array( $option ) && ! empty( $option['option_id'] ) && is_scalar( $option['option_id'] ) ? stripslashes( trim( (string) $option['option_id'] ) ) : '';
	}

	/**
	 * Amount in one column of a bulk quantity tier.
	 *
	 * @param array<string, mixed> $tier   Tier row, empty when no tier matched.
	 * @param string               $column Column name.
	 *
	 * @return float
	 */
	private static function tier_amount( array $tier, string $column ): float {
		return isset( $tier[ $column ] ) && is_numeric( $tier[ $column ] ) ? (float) $tier[ $column ] : 0.0;
	}

	/**
	 * Quantity the shopper entered for one option.
	 *
	 * @param mixed  $value Submitted quantities keyed by option label, HTML-encoded as the page names them.
	 * @param string $label Option label.
	 *
	 * @return int
	 */
	private static function submitted_quantity( $value, string $label ): int {
		foreach ( is_array( $value ) ? $value : array() as $key => $quantity ) {
			if ( html_entity_decode( (string) $key ) === $label ) {
				return self::posted_quantity( $quantity );
			}
		}

		return 0;
	}

	/**
	 * A submitted quantity as a whole, non-negative number.
	 *
	 * @param mixed $quantity Submitted quantity.
	 *
	 * @return int
	 */
	private static function posted_quantity( $quantity ): int {
		return is_scalar( $quantity ) ? max( 0, (int) $quantity ) : 0;
	}

	/**
	 * The `include` flag the product page sets on a quantity field's rows: `on` when the field is priced.
	 *
	 * @param array<string, mixed> $field_meta Saved field definition.
	 *
	 * @return string
	 */
	private static function legacy_includes_base( array $field_meta ): string {
		return Engine::is_field_has_price( $field_meta ) ? 'on' : '';
	}

	/**
	 * Legacy apply of a field's option prices.
	 *
	 * @param array<string, mixed> $field_meta Saved field definition.
	 *
	 * @return string
	 */
	private static function legacy_field_apply( array $field_meta ): string {
		return isset( $field_meta['onetime'] ) && 'on' === $field_meta['onetime'] ? 'onetime' : 'variable';
	}

	/**
	 * Title of a saved field.
	 *
	 * @param array<string, mixed> $field_meta Saved field definition.
	 *
	 * @return string
	 */
	private static function field_title( array $field_meta ): string {
		return isset( $field_meta['title'] ) && is_scalar( $field_meta['title'] ) ? stripslashes( (string) $field_meta['title'] ) : '';
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
	 * Percentages resolve against the store-currency product base price, so callers
	 * convert flat and percentage amounts the same way. The amount stays net, because
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
			$base   = Helpers::wcml_store_currency_price( Callbacks::convert_price_back( Helpers::get_product_price( $product, null, 'cart' ) ) );
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

	// Legacy line subtotal: the product price plus every fixed fee on the line.
	public static function item_subtotal( $item_subtotal, $cart_item, $cart_item_key ) {

		if ( ! PriceMode::is_legacy() || ! is_array( $cart_item ) ) {
			return $item_subtotal;
		}

		$line_product = self::line_product( $cart_item );

		if ( null === $line_product ) {
			return $item_subtotal;
		}

		$base_product = isset( $cart_item['data'] ) && $cart_item['data'] instanceof \WC_Product ? $cart_item['data'] : $line_product;
		$price        = 0.0;

		foreach ( self::legacy_onetime_fees( $cart_item ) as $onetime ) {
			$option_price = apply_filters( 'ppom_option_price', $onetime['amount'] );
			$price       += floatval( wp_strip_all_tags( (string) $option_price ) );
		}

		if ( 0.0 === $price ) {
			return $item_subtotal;
		}

		// Saved amounts are net, so they take the store's tax display treatment.
		if ( $price > 0 ) {
			$price = (float) apply_filters( 'ppom_option_price_vat', $price, $line_product );
		}

		$quantity      = $cart_item['quantity'];
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
