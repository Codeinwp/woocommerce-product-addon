<?php
/**
 * Deprecated product-meta checks (delegates to PPOM_Meta).
 *
 * @package PPOM
 */

namespace PPOM\Deprecated;

use PPOM_Meta;

/**
 * @internal
 */
final class LegacyProductMeta {

	/**
	 * Check if product has PPOM meta (deprecated).
	 *
	 * @param int $product_id Product ID.
	 * @return bool
	 */
	public static function has_product_meta( $product_id ) {
		$ppom = new PPOM_Meta( $product_id );

		if ( ! $ppom->is_exists ) {
			return false;
		}

		return true;
	}
}
