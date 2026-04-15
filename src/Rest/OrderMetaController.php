<?php
/**
 * REST callbacks for PPOM order line-item metadata routes.
 *
 * @package PPOM
 * @subpackage REST
 */

namespace PPOM\Rest;

use WP_REST_Request;
use WP_REST_Response;

/**
 * Handles `/ppom/v1` order meta read/update/delete endpoints.
 *
 * @internal
 */
final class OrderMetaController {

	/**
	 * Secret key validation.
	 *
	 * @var RestSecretValidator
	 */
	private $secrets;

	/**
	 * Order line-item meta formatting.
	 *
	 * @var OrderItemMetaPresenter
	 */
	private $order_items;

	/**
	 * Creates an order meta REST controller with its dependencies.
	 *
	 * @param RestSecretValidator    $secrets     Secret key validation.
	 * @param OrderItemMetaPresenter $order_items Line-item presenter.
	 */
	public function __construct(
		RestSecretValidator $secrets,
		OrderItemMetaPresenter $order_items
	) {
		$this->secrets     = $secrets;
		$this->order_items = $order_items;
	}

	/**
	 * Returns formatted PPOM order item metadata for an order.
	 *
	 * @param WP_REST_Request $request REST request containing `order_id`.
	 *
	 * @return WP_REST_Response
	 */
	public function get_ppom_meta_info_order( WP_REST_Request $request ) {

		// Request parameters.
		$order_id = $request->get_param( 'order_id' );

		$order = wc_get_order( $order_id );

		$response_info = array();
		if ( ! $order ) {
			$response_info = array(
				'status'  => 'no_order',
				'message' => __( 'No Order Found', 'woocommerce-product-addon' ),
			);

			return new WP_REST_Response( $response_info );
		}


		$item_product_meta = $this->order_items->get_order_item_meta( $order_id );

		$response_info = array(
			'status'           => 'success',
			/* translators: %s: WooCommerce Order ID */
			'message'          => sprintf( __( 'Order found %s', 'woocommerce-product-addon' ), $order_id ),
			'order_items_meta' => $item_product_meta,
		);


		// Create the response object.
		$response = new WP_REST_Response( $response_info );

		return $response;
	}

	/**
	 * Updates PPOM order item metadata for matching order products.
	 *
	 * @param WP_REST_Request $request REST request containing `order_id`,
	 *                                 `secret_key`, and `fields`.
	 *
	 * @return WP_REST_Response
	 */
	public function ppom_update_meta_order( WP_REST_Request $request ) {

		// Request parameters.
		$order_id  = $request->get_param( 'order_id' );
		$secretkey = $request->get_param( 'secret_key' );

		$all_data = $request->get_params();


		$order = wc_get_order( $order_id );

		$response_info = array();
		if ( ! $order ) {
			$response_info = array(
				'status'  => 'no_order',
				'message' => __( 'No Order Found', 'woocommerce-product-addon' ),
			);

			return new WP_REST_Response( $response_info );
		}

		if ( empty( $all_data['fields'] ) ) {
			$response_info = array(
				'status'  => 'no_fields',
				'message' => __( 'No meta found to save', 'woocommerce-product-addon' ),
			);

			return new WP_REST_Response( $response_info );
		}

		if ( empty( $secretkey ) || ! $this->secrets->is_secret_key_valid( $secretkey ) ) {
			$response_info = array(
				'status'  => 'key_not_valid',
				'message' => __( 'Secret key is not valid', 'woocommerce-product-addon' ),
			);

			return new WP_REST_Response( $response_info );
		}


		$item_product_meta = array();
		$order_item_meta   = json_decode( stripslashes( $all_data['fields'] ) );

		if ( empty( $order_item_meta ) ) {
			$response_info = array(
				'status'  => 'fields_not_valid',
				'message' => __( 'Submitted fields are in valid format.', 'woocommerce-product-addon' ),
			);

			return new WP_REST_Response( $response_info );
		}


		foreach ( $order->get_items() as $item_id => $item_product ) {

			// Line item product id.
			$product_id = $item_product->get_product_id();

			foreach ( $item_product->get_meta_data() as $item_meta_data ) {

				foreach ( $order_item_meta as $order_product_id => $item_meta ) {

					// Check if product id exists in requested fields.
					$order_product_id = intval( $order_product_id );

					if ( $order_product_id == $product_id ) {

						foreach ( $item_meta as $meta_key => $meta_val ) {


							$scalar_value = $meta_val;

							if ( is_array( $meta_val ) ) {
								$scalar_value = wp_json_encode( $meta_val );
							}

							$meta_update_res = wc_update_order_item_meta( $item_id, $meta_key, $scalar_value );
						}
					}
				}
			}
		}


		$item_product_meta = $this->order_items->get_order_item_meta( $order_id );

		$response_info = array(
			'status'           => 'success',
			/* translators: %s: WooCommerce Order ID */
			'message'          => sprintf( __( 'Order updated %s', 'woocommerce-product-addon' ), $order_id ),
			'order_items_meta' => $item_product_meta,
		);

		return new WP_REST_Response( $response_info );
	}

	/**
	 * Deletes selected PPOM order item metadata keys for an order.
	 *
	 * @param WP_REST_Request $request REST request containing `order_id`,
	 *                                 `secret_key`, and `fields`.
	 *
	 * @return WP_REST_Response
	 */
	public function delete_ppom_fields_order( WP_REST_Request $request ) {

		// Request parameters.
		$order_id  = $request->get_param( 'order_id' );
		$secretkey = $request->get_param( 'secret_key' );

		$all_data = $request->get_params();

		$order = wc_get_order( $order_id );

		$response_info = array();
		if ( ! $order ) {
			$response_info = array(
				'status'  => 'no_order',
				'message' => __( 'No Order Found', 'woocommerce-product-addon' ),
			);

			return new WP_REST_Response( $response_info );
		}

		if ( empty( $all_data['fields'] ) ) {
			$response_info = array(
				'status'  => 'no_fields',
				'message' => __( 'No fields to delete', 'woocommerce-product-addon' ),
			);

			return new WP_REST_Response( $response_info );
		}

		if ( empty( $secretkey ) || ! $this->secrets->is_secret_key_valid( $secretkey ) ) {
			$response_info = array(
				'status'  => 'key_not_valid',
				'message' => __( 'Secret key is not valid', 'woocommerce-product-addon' ),
			);

			return new WP_REST_Response( $response_info );
		}

		$item_product_meta = array();
		$order_item_meta   = json_decode( stripslashes( $all_data['fields'] ) );

		foreach ( $order->get_items() as $item_id => $item_product ) {

			// Line item product id.
			$product_id = $item_product->get_product_id();

			foreach ( $item_product->get_meta_data() as $item_meta_data ) {


				foreach ( $order_item_meta as $order_product_id => $delete_meta ) {

					// Check if product id exists in requested fields.
					if ( $order_product_id == $product_id ) {

						foreach ( $delete_meta as $meta_key ) {

							wc_delete_order_item_meta( $item_id, $meta_key );
						}
					}
				}
			}
		}


		$item_product_meta = $this->order_items->get_order_item_meta( $order_id );

		$response_info = array(
			'status'           => 'success',
			/* translators: %s: WooCommerce Order ID */
			'message'          => sprintf( __( 'Order updated %s', 'woocommerce-product-addon' ), $order_id ),
			'order_items_meta' => $item_product_meta,
		);

		return new WP_REST_Response( $response_info );
	}
}
