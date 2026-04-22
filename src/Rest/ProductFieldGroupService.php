<?php
/**
 * Inserts, merges, and deletes PPOM field-group rows for product-scoped REST writes.
 *
 * @package PPOM
 * @subpackage REST
 */

namespace PPOM\Rest;

use WC_Product;

/**
 * Writes PPOM field-group rows and product attachment meta.
 *
 * @internal
 */
final class ProductFieldGroupService {

	/**
	 * Creates a new PPOM field group for a product through the REST API.
	 *
	 * Inserts the initial field-group row, writes the normalized field schema,
	 * and attaches the new PPOM ID to the product post meta.
	 *
	 * @phpstan-param array<int|string, array<string, mixed>> $ppom_fields
	 * @phpstan-return array<string, mixed>
	 *
	 * @param int   $product_id  Product ID receiving the new field group.
	 * @param array $ppom_fields Field definitions decoded from the request.
	 *
	 * @return array
	 *
	 * @see ppom_admin_update_ppom_meta_only()
	 */
	public function save_new_meta_data( $product_id, $ppom_fields ) {

		$product = new WC_Product( $product_id );

		$productmeta_name       = $product->get_title();
		$productmeta_validation = 'no';
		$dynamic_price_hide     = 'no';
		$send_file_attachment   = '';
		$show_cart_thumb        = 'no';
		$aviary_api_key         = '';
		$productmeta_style      = '';
		$productmeta_categories = '';

		$dt = array(
			'productmeta_name'       => $productmeta_name,
			'productmeta_validation' => $productmeta_validation,
			'dynamic_price_display'  => $dynamic_price_hide,
			'send_file_attachment'   => $send_file_attachment,
			'show_cart_thumb'        => $show_cart_thumb,
			'aviary_api_key'         => trim( $aviary_api_key ),
			'productmeta_style'      => $productmeta_style,
			'productmeta_categories' => $productmeta_categories,
			'the_meta'               => wp_json_encode( $ppom_fields ),
			'productmeta_created'    => current_time( 'mysql' ),
		);


		$format = array(
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
		);

		$res_id = ppom_meta_repository()->insert_group( $dt, $format );

		$ppom_fields = apply_filters( 'ppom_meta_data_saving', $ppom_fields, $res_id );
		// Updating PPOM Meta with ppom_id in each meta array.
		ppom_admin_update_ppom_meta_only( $res_id, $ppom_fields );

		$resp = array();
		if ( $res_id ) {

			$resp = array(
				'status'     => 'success',
				'meta_id'    => $res_id,
				'product_id' => $product_id,
				'fields'     => $ppom_fields,
			);

			// Also setting ppom meta to product.
			update_post_meta( $product_id, PPOM_PRODUCT_META_KEY, $res_id );
		} else {

			$resp = array(
				'message'    => __( 'No changes found.', 'woocommerce-product-addon' ),
				'status'     => 'error',
				'meta_id'    => '',
				'product_id' => $product_id,
			);
		}

		return $resp;
	}

	/**
	 * Merges submitted REST fields into an existing PPOM field group.
	 *
	 * @phpstan-param array<int|string, array<string, mixed>> $ppom_fields
	 * @phpstan-return array<string, mixed>
	 *
	 * @param object $ppom_meta   Existing PPOM settings row.
	 * @param array  $ppom_fields Field definitions decoded from the request.
	 * @param int    $product_id  Product ID attached to the field group.
	 *
	 * @return array
	 */
	public function update_meta_data( $ppom_meta, $ppom_fields, $product_id ) {

		$existing_fields = json_decode( $ppom_meta->the_meta, true );

		$saved_fields = array();
		$merger_array = array();

		// First saving new fields.
		foreach ( $ppom_fields as $new_field ) {

			$merger_array[] = $new_field;
			$saved_fields[] = $new_field['data_name'];
		}

		// Now checking old fields.
		foreach ( $existing_fields as $old_field ) {

			if ( ! in_array( $old_field['data_name'], $saved_fields ) ) {

				$merger_array[] = $old_field;
			}
		}

		$merger_array = apply_filters( 'ppom_meta_data_saving', $merger_array, $ppom_meta->productmeta_id );

		$data  = array( 'the_meta' => wp_json_encode( $merger_array ) );
		$where = array(
			'productmeta_id' => $ppom_meta->productmeta_id,
		);

		$format       = array( '%s' );
		$where_format = array( '%d' );

		ppom_meta_repository()->update_group(
			(int) $ppom_meta->productmeta_id,
			$data,
			$format,
			$where,
			$where_format
		);

		$resp = array(
			'status'     => 'success',
			'meta_id'    => $ppom_meta->productmeta_id,
			'product_id' => $product_id,
			'fields'     => $merger_array,
		);

		return $resp;
	}

	/**
	 * Removes selected fields, or an entire field group, from a product.
	 *
	 * @phpstan-param array<int|string, string> $delete_fields
	 * @phpstan-return array<string, mixed>
	 *
	 * @param object $ppom_meta     Existing PPOM settings row.
	 * @param array  $delete_fields Field keys requested for deletion.
	 * @param int    $product_id    Product ID attached to the field group.
	 *
	 * @return array
	 */
	public function delete_meta_data( $ppom_meta, $delete_fields, $product_id ) {

		$existing_fields = json_decode( $ppom_meta->the_meta );

		$merger_array = array();

		// Check if all fields request exist.
		if ( in_array( '__all_keys', $delete_fields ) ) {

			// Unset product meta key.
			delete_post_meta( $product_id, PPOM_PRODUCT_META_KEY );

			// Deleting all fields.
			$res                = ppom_meta_repository()->delete_by_id( (int) $ppom_meta->productmeta_id );
			$delete_fields_resp = array( 'ppom_id' => $ppom_meta->productmeta_id );

			$resp = array(
				'status'     => 'success',
				'meta_id'    => $ppom_meta->productmeta_id,
				'product_id' => $product_id,
				'fields'     => '',
			);

			return $resp;
		}


		// Only adding those fields which are not deleted.
		foreach ( $existing_fields as $field ) {

			if ( ! isset( $field->data_name ) ) {
				continue;
			}

			if ( ! in_array( $field->data_name, $delete_fields ) ) {

				$merger_array[] = $field;
			}
		}


		$data  = array( 'the_meta' => wp_json_encode( $merger_array ) );
		$where = array(
			'productmeta_id' => $ppom_meta->productmeta_id,
		);

		$format       = array( '%s' );
		$where_format = array( '%d' );

		ppom_meta_repository()->update_group(
			(int) $ppom_meta->productmeta_id,
			$data,
			$format,
			$where,
			$where_format
		);

		$resp = array(
			'status'     => 'success',
			'meta_id'    => $ppom_meta->productmeta_id,
			'product_id' => $product_id,
			'fields'     => $merger_array,
		);

		return $resp;
	}
}
