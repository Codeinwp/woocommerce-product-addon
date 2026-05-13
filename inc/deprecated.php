<?php
/**
 * PPOM Deprecated functions — compatibility wrappers.
 *
 * @package PPOM
 */

// check if product has meta Returns Meta ID if true otherwise null
// Deprecated, using PPOM_Meta::is_exist function
/**
 * @param int $product_id Product ID.
 * @return bool
 */
function ppom_has_product_meta( $product_id ) {
	return \PPOM\Deprecated\LegacyProductMeta::has_product_meta( $product_id );
}
