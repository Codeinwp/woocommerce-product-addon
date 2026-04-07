<?php
/**
 * Catalog and loops: price matrix HTML and legacy quantity input arguments.
 *
 * @package PPOM
 * @subpackage WooCommerce
 *
 * @see woocommerce.php Parent loader under inc/.
 */

namespace PPOM\WooCommerce\Catalog;

/**
 * @internal
 */
final class CatalogHandler {

	// alter price on shop page if price matrix found
	public static function alter_price( $price, $product ) {

		$product_id = ppom_get_product_id( $product );

		if ( class_exists( 'sitepress' ) ) {
			$default_lang = apply_filters( 'wpml_default_language', null );
			$product      = wc_get_product( apply_filters( 'wpml_object_id', $product->get_id(), 'product', true, $default_lang ) );
		}

		$price_matrix_found = ppom_has_field_by_type( $product_id, 'pricematrix' );
		if ( empty( $price_matrix_found ) && apply_filters( 'ppom_hide_product_price_if_zero', true, $product ) ) {
			if ( $product->get_price() <= 0 ) {
				return '';
			}
		}

		if ( empty( $price_matrix_found ) ) {
			return $price;
		}

		$from_pice = '';
		$to_price  = '';

		if ( ! in_array( $product->get_type(), array( 'variable', 'grouped', 'external' ) ) ) {

			$price_range = array();

			foreach ( $price_matrix_found as $meta ) {

				// ppom_pa($meta);

				if ( ! ppom_is_field_visible( $meta ) ) {
					continue;
				}

				if ( $meta['type'] == 'pricematrix' ) {

					$options = $meta['options'];
					$ranges  = ppom_convert_options_to_key_val( $options, $meta, $product );
					// ppom_pa($ranges);

					if ( isset( $meta['discount'] ) && $meta['discount'] == 'on' ) {

						$last_discount = end( $ranges );
						$least_price   = $last_discount['price'];

						if ( ! empty( $last_discount['percent'] ) ) {
							$max_discount = $last_discount['percent'];
							$least_price  = ppom_get_amount_after_percentage( $product->get_price(), $max_discount );
						}

						$least_price = floatval( $product->get_price() ) - $least_price;
						$least_price = wc_format_decimal( $least_price, wc_get_price_decimals() );
						// var_dump($least_price);
						$price = wc_price( $least_price ) . '-' . $price;
					} else {

						foreach ( $ranges as $range ) {
							$price_range[] = $range['price'];
						}

						if ( ! empty( $price_range ) ) {

							$from_pice = min( $price_range );
							$to_price  = max( $price_range );
							$price     = wc_format_price_range( $from_pice, $to_price );
						}
					}
				}
			}
		}

		return apply_filters( 'ppom_loop_matrix_price', $price, $from_pice, $to_price );
	}

	/*
	public static function hide_variation_price_html($show, $parent, $variation) {

	$product_id = $parent->get_id();
	$ppom       = new PPOM_Meta( $product_id );

	if( $ppom->is_exists && $ppom->price_display != 'hide' ) {
		$show = false;
	}

	return $show;

	}*/

	// Set default quantity for price matrix
	public static function product_default_quantity( $args, $product ) {

		if ( ! is_product() ) {
			return $args;
		}

		$product_id = ppom_get_product_id( $product );
		$ppom       = new PPOM_Meta( $product_id );
		if ( ! $ppom->is_exists ) {
			return $args;
		}

		$ppom_matrix_found = ppom_has_field_by_type( $product_id, 'pricematrix' );

		if ( $ppom_matrix_found ) {

			$price_matrix = reset( $ppom_matrix_found );
			// If it is Discount Matrix, do not set min quantity
			// if( isset($meta['discount']) && $meta['discount'] == 'on' ) continue;
			$options = $price_matrix['options'];
			$ranges  = ppom_convert_options_to_key_val( $options, $price_matrix, $product );
			if ( ! empty( $ranges ) ) {
				$first_range         = reset( $ranges );
				$qty_ranges          = explode( '-', $first_range['raw'] );
				$args['input_value'] = $qty_ranges[0];
			}
		}

		return $args;
	}

	// Set min quantity for price matrix
	public static function set_min_quantity( $min_quantity, $product ) {

		$product_id = ppom_get_product_id( $product );
		$ppom       = new PPOM_Meta( $product_id );
		if ( ! $ppom->is_exists ) {
			return $min_quantity;
		}

		$ppom_matrix_found = ppom_has_field_by_type( $product_id, 'pricematrix' );
		if ( $ppom_matrix_found ) {
			foreach ( $ppom_matrix_found as $meta ) {

				// If it is Discount Matrix, do not set min quantity
				// if( isset($meta['discount']) && $meta['discount'] == 'on' ) continue;
				$options = $meta['options'];
				$ranges  = ppom_convert_options_to_key_val( $options, $meta, $product );

				if ( empty( $ranges ) ) {
					continue;
				}

				$first_range  = reset( $ranges );
				$qty_ranges   = explode( '-', $first_range['raw'] );
				$min_quantity = $qty_ranges[0];
			}
		}

		// Check min quantity for variations
		$ppom_quantities_found = ppom_has_field_by_type( $product_id, 'quantities' );
		if ( $ppom_quantities_found ) {
			foreach ( $ppom_quantities_found as $qty ) {
				if ( ! $qty['min_qty'] ) {
					continue;
				}

				if ( $min_quantity < floatval( $qty['min_qty'] ) ) {
					$min_quantity = $qty['min_qty'];
				}
			}
		}

		return $min_quantity;
	}

	// Set max quantity for price matrix
	public static function set_max_quantity( $max_quantity, $product ) {

		$product_id = ppom_get_product_id( $product );
		$ppom       = new PPOM_Meta( $product_id );
		if ( ! $ppom->is_exists ) {
			return $max_quantity;
		}

		$last_range = array();

		$ppom_matrix_found = ppom_has_field_by_type( $product_id, 'pricematrix' );

		if ( $ppom_matrix_found ) {
			foreach ( $ppom_matrix_found as $meta ) {

				// If it is Discount Matrix, do not set max quantity
				if ( isset( $meta['discount'] ) && $meta['discount'] == 'on' ) {
					continue;
				}

				$options = $meta['options'];
				// ppom_pa($options);
				$ranges = ppom_convert_options_to_key_val( $options, $meta, $product );

				if ( empty( $ranges ) ) {
					continue;
				}

				$last_range   = end( $ranges );
				$qty_ranges   = explode( '-', $last_range['raw'] );
				$max_quantity = $qty_ranges[1];
			}
		}

		// Check min quantity for variations
		$ppom_quantities_found = ppom_has_field_by_type( $product_id, 'quantities' );
		if ( $ppom_quantities_found ) {
			foreach ( $ppom_quantities_found as $qty ) {
				if ( ! $qty['max_qty'] ) {
					continue;
				}

				if ( $max_quantity < floatval( $qty['max_qty'] ) ) {
					$max_quantity = $qty['max_qty'];
				}
			}
		}

		return $max_quantity;
	}

	// Set quantity step for price matrix
	public static function set_quantity_step( $quantity_step, $product ) {

		$product_id = ppom_get_product_id( $product );
		$ppom       = new PPOM_Meta( $product_id );
		if ( ! $ppom->is_exists ) {
			return $quantity_step;
		}

		$last_range = array();

		$ppom_matrix_found = ppom_has_field_by_type( $product_id, 'pricematrix' );
		if ( $ppom_matrix_found ) {
			foreach ( $ppom_matrix_found as $meta ) {

				$quantity_step = empty( $meta['qty_step'] ) ? 1 : $meta['qty_step'];
			}
		}

		return $quantity_step;
	}
}
