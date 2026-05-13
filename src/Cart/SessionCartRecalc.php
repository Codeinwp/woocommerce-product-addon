<?php
/**
 * Triggers cart total recalculation after session restore hooks.
 *
 * @package PPOM
 */

namespace PPOM\Cart;

/**
 * @since 33.0.19
 */
final class SessionCartRecalc {

	/**
	 * @param \WC_Cart $cart WooCommerce cart.
	 * @return void
	 */
	public static function recalculate( $cart ): void {
		$cart->calculate_totals();
	}
}
