<?php
/**
 * Pricing mode helpers.
 *
 * Thin facade over legacy `ppom_*` helpers for modern vs legacy cart pricing.
 *
 * @package PPOM
 */

namespace PPOM\Pricing;

/**
 * Detects whether PPOM is using legacy fee-based or modern line-item pricing.
 *
 * @since 33.0.19
 */
final class PriceMode {

	/**
	 * Whether the store uses legacy fee-based PPOM pricing.
	 *
	 * @return bool True when legacy fee-based pricing is active.
	 */
	public static function is_legacy(): bool {
		// phpcs:ignore Universal.Operators.StrictComparisons.LooseEqual -- Match ppom_get_price_mode() return type from legacy helpers.
		return ppom_get_price_mode() == 'legacy';
	}
}
