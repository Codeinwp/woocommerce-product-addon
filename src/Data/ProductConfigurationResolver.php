<?php
/**
 * Resolves which PPOM field-group IDs apply to a product (direct meta, categories, filters).
 *
 * @package PPOM
 */

namespace PPOM\Data;

/**
 * Computes merged PPOM field-group IDs from product meta and categories.
 *
 * @since 33.0.19
 */
final class ProductConfigurationResolver {

	/**
	 * Match product categories to PPOM rows that declare categories/tags.
	 *
	 * @param int|null          $product_id        Product ID.
	 * @param array<int, mixed> $category_tag_rows Rows from FieldGroupRepository::find_rows_with_categories_or_tags().
	 * @return array<int, int>
	 */
	public function match_categories_for_product( $product_id, array $category_tag_rows ): array {
		$product_categories = $product_id ? get_the_terms( $product_id, 'product_cat' ) : false;
		if ( ! is_array( $product_categories ) ) {
			$product_categories = array();
		}

		$meta_found = array();
		if ( $product_categories && $category_tag_rows ) {
			foreach ( $category_tag_rows as $row ) {
				if ( ! is_object( $row ) ) {
					continue;
				}

				$row_vars = get_object_vars( $row );
				$pm_cats  = isset( $row_vars['productmeta_categories'] ) ? (string) $row_vars['productmeta_categories'] : '';
				$pm_id    = isset( $row_vars['productmeta_id'] ) ? $row_vars['productmeta_id'] : 0;

				if ( 'All' === $pm_cats ) {
					$meta_found[] = $pm_id;
				} else {
					$meta_cat_array = preg_split( '/\r\n|\n/', $pm_cats );
					if ( ! is_array( $meta_cat_array ) ) {
						$meta_cat_array = array();
					}
					foreach ( $product_categories as $cat ) {
						// phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict -- Match legacy PPOM_Meta::ppom_has_category_meta() slug checks.
						if ( in_array( $cat->slug, $meta_cat_array ) ) {
							$meta_found[] = $pm_id;
						}
					}
				}
			}
		}

		$meta_found = apply_filters( 'ppom_pro_fields_to_display_on_product', $meta_found, $product_id, $category_tag_rows );

		return $meta_found;
	}

	/**
	 * Resolve merged meta IDs from product meta and category rules (same semantics as PPOM_Meta::get_meta_id).
	 *
	 * @param int|null        $product_id        Product ID.
	 * @param array<int, int> $ppom_in_category Category-linked IDs from match_categories_for_product().
	 * @return array<int, int>|int|null
	 */
	public function merge_meta_ids_from_product_and_categories( $product_id, array $ppom_in_category ) {
		$ppom_product_id = get_post_meta( (int) $product_id, PPOM_PRODUCT_META_KEY, true );

		if ( 0 === $ppom_product_id || 'None' === $ppom_product_id ) {
			$ppom_product_id = null;
		}

		if ( $ppom_in_category ) {

			$ppom_overrides = apply_filters( 'ppom_meta_overrides', 'default' );

			switch ( $ppom_overrides ) {

				case 'category_override':
					$ppom_product_id = $ppom_in_category;
					break;
				case 'individual_override':
					if ( null === $ppom_product_id ) {
						$ppom_product_id = $ppom_in_category;
					}
					break;
				default:
					// phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict -- Preserve legacy loose in_array() semantics from PPOM_Meta::get_meta_id().
					if ( is_array( $ppom_product_id ) && ! in_array( $ppom_in_category, $ppom_product_id ) ) {

						$ppom_priority = apply_filters( 'ppom_meta_priority', 'category_first' );

						if ( 'category_first' === $ppom_priority ) {

							$ppom_product_id = array_merge( $ppom_in_category, $ppom_product_id );
						} else {
							$ppom_product_id = array_merge( $ppom_product_id, $ppom_in_category );
						}
					} elseif ( ! $ppom_product_id ) {

						$ppom_product_id = $ppom_in_category;
					}
					break;

			}
		}

		return apply_filters( 'ppom_product_meta_id', is_array( $ppom_product_id ) ? array_unique( $ppom_product_id ) : $ppom_product_id, $product_id );
	}

	/**
	 * Returns category matches and merged meta IDs together.
	 *
	 * @param int|null          $product_id        Product ID.
	 * @param array<int, mixed> $category_tag_rows Repository rows.
	 * @return array{meta_id: array<int, int>|int|null, category_meta: array<int, int>}
	 */
	public function resolve_meta_id_bundle( $product_id, array $category_tag_rows ): array {
		$category_meta = $this->match_categories_for_product( $product_id, $category_tag_rows );
		$meta_id       = $this->merge_meta_ids_from_product_and_categories( $product_id, $category_meta );

		return array(
			'meta_id'       => $meta_id,
			'category_meta' => $category_meta,
		);
	}
}
