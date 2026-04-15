<?php
/**
 * Validation and quantity limits.
 *
 * @package PPOM
 */

namespace PPOM\Validation;

use PPOM\Support\Helpers;

use PPOM_Meta;
use WC_Product;
use WC_Product_Variable;

/**
 * @internal
 */
final class Validator {

	/**
	 * Sanitizes field-builder content while preserving the HTML keys PPOM stores.
	 *
	 * @param string $content Raw field-builder content.
	 * @return string
	 */
	public static function esc_html( $content ) {

		global $allowedposttags;
		$allowed_atts                = array(
			'align'      => array(),
			'class'      => array(),
			'type'       => array(),
			'id'         => array(),
			'dir'        => array(),
			'lang'       => array(),
			'style'      => array(),
			'xml:lang'   => array(),
			'src'        => array(),
			'alt'        => array(),
			'href'       => array(),
			'rel'        => array(),
			'rev'        => array(),
			'target'     => array(),
			'novalidate' => array(),
			'type'       => array(),
			'value'      => array(),
			'name'       => array(),
			'tabindex'   => array(),
			'action'     => array(),
			'method'     => array(),
			'for'        => array(),
			'width'      => array(),
			'height'     => array(),
			'data'       => array(),
			'title'      => array(),
			'onclick'    => array(),
			'onchange'   => array(),
			'onkeyup'    => array(),
			'data-*'     => true, // allows all data-* attributes
			'style'      => array(),
		);
		$allowedposttags['form']     = $allowed_atts;
		$allowedposttags['label']    = $allowed_atts;
		$allowedposttags['input']    = $allowed_atts;
		$allowedposttags['textarea'] = $allowed_atts;
		$allowedposttags['iframe']   = $allowed_atts;
		$allowedposttags['script']   = $allowed_atts;
		$allowedposttags['style']    = $allowed_atts;
		$allowedposttags['strong']   = $allowed_atts;
		$allowedposttags['small']    = $allowed_atts;
		$allowedposttags['table']    = $allowed_atts;
		$allowedposttags['span']     = $allowed_atts;
		$allowedposttags['abbr']     = $allowed_atts;
		$allowedposttags['code']     = $allowed_atts;
		$allowedposttags['pre']      = $allowed_atts;
		$allowedposttags['div']      = $allowed_atts;
		$allowedposttags['img']      = $allowed_atts;
		$allowedposttags['h1']       = $allowed_atts;
		$allowedposttags['h2']       = $allowed_atts;
		$allowedposttags['h3']       = $allowed_atts;
		$allowedposttags['h4']       = $allowed_atts;
		$allowedposttags['h5']       = $allowed_atts;
		$allowedposttags['h6']       = $allowed_atts;
		$allowedposttags['ol']       = $allowed_atts;
		$allowedposttags['ul']       = $allowed_atts;
		$allowedposttags['li']       = $allowed_atts;
		$allowedposttags['em']       = $allowed_atts;
		$allowedposttags['hr']       = $allowed_atts;
		$allowedposttags['br']       = $allowed_atts;
		$allowedposttags['tr']       = $allowed_atts;
		$allowedposttags['td']       = $allowed_atts;
		$allowedposttags['p']        = $allowed_atts;
		$allowedposttags['a']        = $allowed_atts;
		$allowedposttags['b']        = $allowed_atts;
		$allowedposttags['i']        = $allowed_atts;
		$allowedposttags['br']       = $allowed_atts;
		$allowed_tags                = wp_kses_allowed_html( 'post' );

		return wp_kses( stripslashes_deep( $content ), $allowed_tags );
	}

	/**
	 * Recursively sanitizes PPOM field definitions before they are stored.
	 *
	 * @param array $data Untrusted field-definition array.
	 * @return array
	 */
	public static function sanitize_array_data( $data ) {
		foreach ( $data as $key => &$value ) {
			if ( is_array( $value ) ) {
				$value = self::sanitize_array_data( $value );
			} elseif ( in_array( $key, self::fields_with_html(), true ) ) {
				$value = self::esc_html( $value );
			} else {
				$value = sanitize_text_field( $value );
			}
		}
		unset( $value );

		return $data;
	}

	/**
	 * Returns field-definition keys that store sanitized HTML instead of plain text.
	 *
	 * @return array<int, string>
	 */
	public static function fields_with_html() {

		$have_html = array( 'description', 'tooltip', 'heading', 'html', 'error_message', 'checked', 'disable_custom_dates' );

		return apply_filters( 'ppom_fields_with_html', $have_html );
	}

	/**
	 * Applies PPOM quantity limits to WooCommerce quantity input arguments.
	 *
	 * @param array       $data List of data to update.
	 * @param \WC_Product $product Product object.
	 * @return array<string, mixed>
	 */
	public static function validation_product_limits( $data, $product ) {

		if ( Helpers::is_client_validation_enabled() ) {
			return $data;
		}

		$product_id   = $product->get_id();
		$variation_id = 0;

		if ( $product->is_type( 'variation' ) ) {
			$product_id   = $product->get_parent_id();
			$variation_id = $product->get_id();
		}

		$limits = self::get_product_limits( $product_id, $variation_id );

		// Min qty
		if ( $limits['min_qty'] > 0 ) {

			if ( $product->managing_stock() && ! $product->backorders_allowed() && absint( $limits['min_qty'] ) > $product->get_stock_quantity() ) {
				$data['min_value'] = $product->get_stock_quantity();

			} else {
				$data['min_value'] = $limits['min_qty'];
			}
		}

		// Max qty
		if ( $limits['max_qty'] > 0 ) {

			if ( $product->managing_stock() && $product->backorders_allowed() ) {
				$data['max_value'] = $limits['max_qty'];

			} elseif ( $product->managing_stock() && absint( $limits['max_qty'] ) > $product->get_stock_quantity() ) {
				$data['max_value'] = $product->get_stock_quantity();

			} else {
				$data['max_value'] = $limits['max_qty'];
			}
		}

		// Step
		if ( $limits['step'] > 0 ) {
			$data['step'] = $limits['step'];
			// If both minimum and maximum quantity are set, make sure both are equally divisible by group of quantity.
			if ( ( empty( $limits['max_qty'] ) || absint( $limits['max_qty'] ) % absint( $limits['step'] ) === 0 ) && ( empty( $limits['min_qty'] ) || absint( $limits['min_qty'] ) % absint( $limits['step'] ) === 0 ) ) {
				$data['step'] = $limits['step'];
			}
		}

		if ( empty( $limits['min_qty'] ) && ! $product->is_type( 'group' ) && $limits['step'] > 0 && $data['min_value'] <= 1 ) {
			$data['min_value'] = $limits['step'];
		}

		$data['input_value'] = $data['min_value'];

		return $data;
	}

	/**
	 * Adds PPOM quantity limits to the variation data passed to the frontend.
	 *
	 * @param array                $data Available variation data.
	 * @param \WC_Product          $product Product object.
	 * @param \WC_Product_Variable $variation Variation object.
	 * @return array<string, mixed>
	 */
	public static function validation_variation_limits( $data, $product, $variation ) {

		if ( Helpers::is_client_validation_enabled() ) {
			return $data;
		}

		$product_id   = $product->get_id();
		$variation_id = 0;

		if ( $product->is_type( 'variation' ) ) {
			$product_id   = $product->get_parent_id();
			$variation_id = $product->get_id();
		}

		$limits = self::get_product_limits( $product_id, $variation_id );
		if ( $limits['min_qty'] > 0 ) {

			if ( $product->managing_stock() && ! $product->backorders_allowed() && absint( $limits['min_qty'] ) > $product->get_stock_quantity() ) {
				$data['min_qty'] = $product->get_stock_quantity();

			} else {
				$data['min_qty'] = $limits['min_qty'];
			}
		}

		if ( $limits['max_qty'] > 0 ) {

			if ( $product->managing_stock() && $product->backorders_allowed() ) {
				$data['max_qty'] = $limits['max_qty'];

			} elseif ( $product->managing_stock() && absint( $limits['max_qty'] ) > $product->get_stock_quantity() ) {
				$data['max_qty'] = $product->get_stock_quantity();

			} else {
				$data['max_qty'] = $limits['max_qty'];
			}
		}

		if ( $limits['step'] > 0 ) {
			$data['step'] = 1;
			// If both minimum and maximum quantity are set, make sure both are equally divisible by group of quantity.
			if ( ( empty( $limits['max_qty'] ) || absint( $limits['max_qty'] ) % absint( $limits['step'] ) === 0 ) && ( empty( $limits['min_qty'] ) || absint( $limits['min_qty'] ) % absint( $limits['step'] ) === 0 ) ) {
				$data['step'] = $limits['step'];
			}
		}

		if ( empty( $limits['min_qty'] ) && ! $product->is_type( 'group' ) && $limits['step'] > 0 && $data['min_qty'] <= 1 ) {
			$data['min_qty'] = $limits['step'];
		}

		if ( $limits['input_value'] > 0 ) {
			$data['input_value'] = $limits['input_value'];
		}


		return $data;
	}

	/**
	 * Derives quantity limits from PPOM quantity and price-matrix fields.
	 *
	 * @param int $product_id   Parent product ID.
	 * @param int $variation_id Variation ID when resolving variation-specific data.
	 * @return array<string, int|float>
	 */
	public static function get_product_limits( $product_id, $variation_id ) {

		$product = wc_get_product( $product_id );
		$ppom    = new PPOM_Meta( $product_id );


		$min_quantity = 0;
		$max_quantity = 0;
		$qty_step     = 1;
		$input_val    = - 1;

		$limits['min_qty']     = intval( $min_quantity );
		$limits['max_qty']     = intval( $max_quantity );
		$limits['step']        = intval( $qty_step );
		$limits['input_value'] = $input_val;

		if ( ! $ppom->is_exists ) {
			return $limits;
		}

		$ppom_matrix_found = Helpers::has_field_by_type( $product_id, 'pricematrix' );
		if ( $ppom_matrix_found ) {
			foreach ( $ppom_matrix_found as $meta ) {

				// If it is Discount Matrix, do not set min quantity
				// if( isset($meta['discount']) && $meta['discount'] == 'on' ) continue;
				$options = $meta['options'];
				$ranges  = Helpers::convert_options_to_key_val( $options, $meta, $product );

				if ( empty( $ranges ) ) {
					continue;
				}

				$first_range  = reset( $ranges );
				$qty_ranges   = explode( '-', $first_range['raw'] );
				$min_quantity = $qty_ranges[0];
			}
		}

		if ( $ppom_matrix_found ) {
			foreach ( $ppom_matrix_found as $meta ) {

				// If it is Discount Matrix, do not set max quantity
				if ( isset( $meta['discount'] ) && $meta['discount'] == 'on' ) {
					continue;
				}

				$options = $meta['options'];
				// ppom_pa($options);
				$ranges = Helpers::convert_options_to_key_val( $options, $meta, $product );

				if ( empty( $ranges ) ) {
					continue;
				}

				$last_range   = end( $ranges );
				$qty_ranges   = explode( '-', $last_range['raw'] );
				$max_quantity = $qty_ranges[1];
			}
		}

		// Check min quantity for variations
		$ppom_quantities_found = Helpers::has_field_by_type( $product_id, 'quantities' );
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

		// Step
		$last_range = array();
		if ( $ppom_matrix_found ) {
			foreach ( $ppom_matrix_found as $meta ) {

				$qty_step = empty( $meta['qty_step'] ) ? 1 : $meta['qty_step'];
			}
		}

		// Input value
		if ( $ppom_matrix_found ) {

			$price_matrix = reset( $ppom_matrix_found );
			// If it is Discount Matrix, do not set min quantity
			// if( isset($meta['discount']) && $meta['discount'] == 'on' ) continue;
			$options = $price_matrix['options'];
			$ranges  = Helpers::convert_options_to_key_val( $options, $price_matrix, $product );
			if ( ! empty( $ranges ) ) {
				$first_range = reset( $ranges );
				$qty_ranges  = explode( '-', $first_range['raw'] );
				$input_val   = $qty_ranges[0];
			}
		}

		$limits['min_qty']     = intval( $min_quantity );
		$limits['max_qty']     = intval( $max_quantity );
		$limits['step']        = intval( $qty_step );
		$limits['input_value'] = $input_val;

		// ppom_pa($limits);
		return $limits;
	}

	/**
	 * Allows CSS declarations containing `rgb()` and `rgba()` values.
	 *
	 * @param bool   $allow_css  Whether the CSS in the string is considered safe.
	 * @param string $css_string The full CSS declaration.
	 * @return bool
	 */
	public static function safecss_filter_attr( $allow_css, $css_string ) {

		// If the CSS string contains rgb() or rgba(), mark it as safe.
		if ( stripos( $css_string, 'rgb(' ) !== false || stripos( $css_string, 'rgba(' ) !== false ) {
			return true;
		}

		return $allow_css;
	}
}
