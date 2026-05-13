<?php
/**
 * REST callbacks for PPOM product field-group routes.
 *
 * @package PPOM
 * @subpackage REST
 */

namespace PPOM\Rest;

use PPOM_Meta;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Handles `/ppom/v1` product meta read/write/delete endpoints.
 *
 * @internal
 */
final class ProductMetaController {

	/**
	 * Secret key validation.
	 *
	 * @var RestSecretValidator
	 */
	private $secrets;

	/**
	 * Field shape for read responses.
	 *
	 * @var RestFieldFormatter
	 */
	private $formatter;

	/**
	 * Persistence for field groups.
	 *
	 * @var ProductFieldGroupService
	 */
	private $field_groups;

	/**
	 * Creates a product meta REST controller with its dependencies.
	 *
	 * @param RestSecretValidator      $secrets      Secret key validation.
	 * @param RestFieldFormatter       $formatter    Field shape for reads.
	 * @param ProductFieldGroupService $field_groups Persistence layer.
	 */
	public function __construct(
		RestSecretValidator $secrets,
		RestFieldFormatter $formatter,
		ProductFieldGroupService $field_groups
	) {
		$this->secrets      = $secrets;
		$this->formatter    = $formatter;
		$this->field_groups = $field_groups;
	}

	/**
	 * Returns the PPOM field schema attached to a product.
	 *
	 * @param WP_REST_Request $request REST request containing `product_id`.
	 *
	 * @return WP_REST_Response
	 *
	 * @see PPOM_Meta::__construct()
	 */
	public function get_ppom_meta_info_product( WP_REST_Request $request ) {

		// Request parameters.
		$product_id = $request->get_param( 'product_id' );

		$response_info = array();
		if ( $product_id == '' ) {
			$response_info = array(
				'status'  => 'no_product',
				'message' => __( 'No Product Found', 'woocommerce-product-addon' ),
			);

			return new WP_REST_Response( $response_info );
		}

		$product_id = intval( $product_id );
		$ppom       = new PPOM_Meta( $product_id );
		if ( ! $ppom->is_exists ) {

			$response_info = array(
				'status'  => 'no_meta',
				'message' => __( 'No Meta Found', 'woocommerce-product-addon' ),
			);

			return new WP_REST_Response( $response_info );
		}

		$meta_id     = $ppom->single_meta_id;
		$ppom_fields = $ppom->fields;


		$ppom_fields = $this->formatter->filter_required_keys_only( $ppom_fields );

		$response_info = array(
			'status'      => 'success',
			/* translators: %s: Field Meta ID */
			'message'     => sprintf( __( 'Meta found %s', 'woocommerce-product-addon' ), $meta_id ),
			'meta_id'     => intval( $meta_id ),
			'product_id'  => $product_id,
			'ppom_fields' => $ppom_fields,
		);


		// Create the response object.
		$response = new WP_REST_Response( $response_info );

		return $response;
	}

	/**
	 * Returns a PPOM field schema by field-group ID.
	 *
	 * @param WP_REST_Request $request REST request containing `id`.
	 *
	 * @return WP_REST_Response
	 *
	 * @see PPOM_Meta::get_fields_by_id()
	 */
	public function get_ppom_meta_by_id( WP_REST_Request $request ) {

		// Request parameters.
		$ppom_id = $request->get_param( 'id' );
		$ppom_id = intval( $ppom_id );

		$ppom        = new PPOM_Meta();
		$ppom_fields = $ppom->get_fields_by_id( $ppom_id );
		if ( ! $ppom_fields ) {

			$response_info = array(
				'status'  => false,
				'message' => __( 'No Meta Found', 'woocommerce-product-addon' ),
			);

			return new WP_REST_Response( $response_info );
		}

		$ppom_fields = $this->formatter->filter_required_keys_only( $ppom_fields );

		$response_info = array(
			'status'      => true,
			/* translators: %s: Field Meta ID */
			'message'     => sprintf( __( 'Meta found %s', 'woocommerce-product-addon' ), $ppom_id ),
			'meta_id'     => intval( $ppom_id ),
			'ppom_fields' => $ppom_fields,
		);


		// Create the response object.
		$response = new WP_REST_Response( $response_info );

		return $response;
	}

	/**
	 * Creates or updates the PPOM field group attached to a product.
	 *
	 * The submitted `fields` payload is JSON-decoded, validated against the
	 * configured secret key, and then written into the PPOM field-group table.
	 *
	 * @param WP_REST_Request $request REST request containing `product_id`,
	 *                                 `secret_key`, and `fields`.
	 *
	 * @return WP_REST_Response
	 */
	public function ppom_save_meta_product( WP_REST_Request $request ) {

		// Request parameters.
		$product_id = $request->get_param( 'product_id' );
		$secretkey  = $request->get_param( 'secret_key' );

		$all_data = $request->get_params();

		if ( empty( $all_data['fields'] ) ) {
			$response_info = array(
				'status'  => 'no_fields',
				'message' => __( 'No fields to save', 'woocommerce-product-addon' ),
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

		$response_info = array();
		if ( $product_id == '' ) {
			$response_info = array(
				'status'  => 'no_product',
				'message' => __( 'No Product Found', 'woocommerce-product-addon' ),
			);

			return new WP_REST_Response( $response_info );
		}


		$product_id    = intval( $product_id );
		$ppom          = new PPOM_Meta( $product_id );
		$ppom_settings = $ppom->ppom_settings;

		$ppom_fields = json_decode( stripslashes( $all_data['fields'] ), true );
		if ( ! is_array( $ppom_fields ) ) {
			return new WP_REST_Response(
				array(
					'status'  => 'fields_not_valid',
					'message' => __( 'Submitted fields are in valid format.', 'woocommerce-product-addon' ),
				)
			);
		}

		$meta_response = array();
		if ( empty( $ppom_settings ) ) {
			$meta_response = $this->field_groups->save_new_meta_data( $product_id, $ppom_fields );
		} else {
			if ( ! is_object( $ppom_settings ) ) {
				return new WP_REST_Response(
					array(
						'status'  => 'no_meta',
						'message' => __( 'No Meta Found', 'woocommerce-product-addon' ),
					)
				);
			}
			$meta_response = $this->field_groups->update_meta_data( $ppom_settings, $ppom_fields, $product_id );
		}


		return new WP_REST_Response( $meta_response );
	}


	/**
	 * Deletes selected PPOM fields from the field group attached to a product.
	 *
	 * @param WP_REST_Request $request REST request containing `product_id`,
	 *                                 `secret_key`, and `fields`.
	 *
	 * @return WP_REST_Response
	 */
	public function delete_ppom_fields_product( WP_REST_Request $request ) {

		// Request parameters.
		$product_id = $request->get_param( 'product_id' );
		$secretkey  = $request->get_param( 'secret_key' );

		$all_data = $request->get_params();

		if ( empty( $all_data['fields'] ) ) {
			$response_info = array(
				'status'  => 'no_fields',
				'message' => __( 'No fields to save', 'woocommerce-product-addon' ),
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

		$response_info = array();
		if ( $product_id == '' ) {
			$response_info = array(
				'status'  => 'no_product',
				'message' => __( 'No Product Found', 'woocommerce-product-addon' ),
			);

			return new WP_REST_Response( $response_info );
		}

		$product_id    = intval( $product_id );
		$ppom          = new PPOM_Meta( $product_id );
		$ppom_settings = $ppom->ppom_settings;

		if ( ! is_object( $ppom_settings ) ) {
			return new WP_REST_Response(
				array(
					'status'  => 'no_meta',
					'message' => __( 'No Meta Found', 'woocommerce-product-addon' ),
				)
			);
		}

		$delete_fields = json_decode( stripslashes( $all_data['fields'] ), true );
		if ( ! is_array( $delete_fields ) ) {
			return new WP_REST_Response(
				array(
					'status'  => 'fields_not_valid',
					'message' => __( 'Submitted fields are in valid format.', 'woocommerce-product-addon' ),
				)
			);
		}

		$meta_response = $this->field_groups->delete_meta_data( $ppom_settings, $delete_fields, $product_id );

		return new WP_REST_Response( $meta_response );
	}
}
