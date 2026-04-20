<?php
/**
 * Modern mode: attaches price matrix field metadata to the cart item before line pricing runs.
 *
 * @package PPOM
 */

namespace PPOM\Pricing;

/**
 * @since 33.0.19
 */
final class PriceMatrixCartItemEnricher {

	/**
	 * Populate `ppom.price_matrix_found` on the cart item when a matrix applies.
	 *
	 * @param array<string, mixed> $cart_items Cart item row.
	 * @param array<string, mixed> $values     Session values (unused; kept for hook signature parity).
	 * @return array<string, mixed>
	 */
	public static function enrich_from_session( $cart_items, $values ) {
		if ( ! isset( $cart_items['ppom'] ) ) {
			return $cart_items;
		}

		$wc_product = $cart_items['data'];
		$product_id = ppom_get_product_id( $wc_product );

		$pricematrix_field = ppom_has_field_by_type( $product_id, 'pricematrix' );
		if ( ! $pricematrix_field ) {
			return $cart_items;
		}

		$matrix_found = array();
		foreach ( $pricematrix_field as $pm ) {

			$pm_dataname = isset( $pm['data_name'] ) ? $pm['data_name'] : '';
			// var_dump($pm_dataname, ppom_is_field_hidden_by_condition( $pm_dataname ));
			$conditionally_hidden = $cart_items['ppom']['conditionally_hidden'];
			if ( ppom_is_field_hidden_by_condition( $pm_dataname, $conditionally_hidden ) ) {
				continue;
			}

			$matrix_found = $pm;
			break;
		}

		$cart_items['ppom']['price_matrix_found'] = apply_filters( 'ppom_price_marix_found', $matrix_found, $cart_items );

		return $cart_items;
	}
}
