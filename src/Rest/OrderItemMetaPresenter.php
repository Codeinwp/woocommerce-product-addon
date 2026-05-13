<?php
/**
 * Builds per-line-item PPOM meta payloads for order REST responses.
 *
 * @package PPOM
 * @subpackage REST
 */

namespace PPOM\Rest;

use PPOM\Support\Helpers;

/**
 * Maps WooCommerce order items to REST-friendly PPOM meta arrays.
 *
 * @internal
 */
final class OrderItemMetaPresenter {

	/**
	 * Returns formatted PPOM metadata for every order item in an order.
	 *
	 * @phpstan-return list<array<string, mixed>>
	 *
	 * @param int $order_id WooCommerce order ID.
	 *
	 * @return array
	 *
	 * @see Helpers::generate_cart_meta()
	 * @see Helpers::get_field_meta_by_dataname()
	 */
	public function get_order_item_meta( $order_id ) {

		$order = wc_get_order( $order_id );

		$order_item_meta_data = array();

		foreach ( $order->get_items() as $item_id => $item_product ) {

			// Get the special meta data in an array.
			$product_id     = $item_product->get_product_id();
			$ppom_meta_data = $item_product->get_meta( '_ppom_fields' );
			$context        = 'api';
			$ppom_meta_ids  = null;
			$ppom_meta      = Helpers::generate_cart_meta( $ppom_meta_data, $product_id, $ppom_meta_ids, $context );

			// Checkbox/radio/select price detail.
			$meta_info = array();
			foreach ( $item_product->get_meta_data() as $meta_data ) {

				$formatted_data = array();
				$fields_info    = Helpers::get_field_meta_by_dataname( $product_id, $meta_data->key );
				if ( ! $fields_info ) {
					continue;
				}

				$formatted_data['id']    = $meta_data->id;
				$formatted_data['key']   = $meta_data->key;
				$formatted_data['value'] = $meta_data->value;

				if ( isset( $ppom_meta[ $meta_data->key ] ) ) {
					$formatted_value           = isset( $ppom_meta[ $meta_data->key ]['value'] ) ? $ppom_meta[ $meta_data->key ]['value'] : $meta_data->value;
					$formatted_data['display'] = isset( $ppom_meta[ $meta_data->key ]['display'] ) ? $ppom_meta[ $meta_data->key ]['display'] : $formatted_value;
					$formatted_data['value']   = $formatted_value;
				}

				$meta_info[] = $formatted_data;
			}


			$order_item_meta_data[] = array(
				'product_id'        => $product_id,
				'product_meta_data' => $meta_info,
			);
		}

		return $order_item_meta_data;
	}
}
