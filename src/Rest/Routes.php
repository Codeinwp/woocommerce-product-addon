<?php
/**
 * Registers PPOM REST API routes for field groups and order metadata.
 *
 * @package PPOM
 * @subpackage REST
 *
 * @see woocommerce-product-addon.php Bootstraps via `new \PPOM\Rest\Routes()`.
 */

namespace PPOM\Rest;

/**
 * Facade: wires `rest_api_init` routes to {@see ProductMetaController} and
 * {@see OrderMetaController}. Exposes legacy instance methods for backward
 * compatibility with the former `PPOM_Rest` class.
 *
 * @internal
 */
final class Routes {

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
	 * Order line-item meta formatting.
	 *
	 * @var OrderItemMetaPresenter
	 */
	private $order_presenter;

	/**
	 * Product field-group persistence.
	 *
	 * @var ProductFieldGroupService
	 */
	private $field_groups;

	/**
	 * Product REST controller.
	 *
	 * @var ProductMetaController
	 */
	private $product_controller;

	/**
	 * Order REST controller.
	 *
	 * @var OrderMetaController
	 */
	private $order_controller;

	/**
	 * Hooks PPOM REST route registration when the API setting is enabled.
	 *
	 * @return void
	 */
	public function __construct() {

		$this->secrets         = new RestSecretValidator();
		$this->formatter       = new RestFieldFormatter();
		$this->order_presenter = new OrderItemMetaPresenter();
		$this->field_groups    = new ProductFieldGroupService();

		$this->product_controller = new ProductMetaController(
			$this->secrets,
			$this->formatter,
			$this->field_groups
		);

		$this->order_controller = new OrderMetaController(
			$this->secrets,
			$this->order_presenter
		);

		if ( ppom_is_api_enable() ) {
			add_action( 'rest_api_init', array( $this, 'init_api' ) );
		}
	}


	/**
	 * Registers the public `ppom/v1` routes for products and orders.
	 *
	 * Write routes still validate the submitted `secret_key` inside their
	 * callbacks before mutating PPOM field groups or order item metadata.
	 *
	 * @return void
	 */
	public function init_api() {

		$p = $this->product_controller;
		$o = $this->order_controller;

		register_rest_route(
			'ppom/v1',
			'/get/product/',
			array(
				'methods'             => 'GET',
				'callback'            => array( $p, 'get_ppom_meta_info_product' ),
				'permission_callback' => array( $this, 'check_read_permission' ),
			)
		);

		register_rest_route(
			'ppom/v1',
			'/get/id/(?P<id>\d+)',
			array(
				'methods'             => 'GET',
				'callback'            => array( $p, 'get_ppom_meta_by_id' ),
				'permission_callback' => array( $this, 'check_read_permission' ),
			)
		);

		register_rest_route(
			'ppom/v1',
			'/set/product/',
			array(
				'methods'             => 'POST',
				'callback'            => array( $p, 'ppom_save_meta_product' ),
				'permission_callback' => array( $this, 'check_write_permission' ),
			)
		);

		register_rest_route(
			'ppom/v1',
			'/delete/product/',
			array(
				'methods'             => 'POST',
				'callback'            => array( $p, 'delete_ppom_fields_product' ),
				'permission_callback' => array( $this, 'check_write_permission' ),
			)
		);

		register_rest_route(
			'ppom/v1',
			'/get/order/',
			array(
				'methods'             => 'GET',
				'callback'            => array( $o, 'get_ppom_meta_info_order' ),
				'permission_callback' => array( $this, 'check_write_permission' ),
			)
		);

		register_rest_route(
			'ppom/v1',
			'/set/order/',
			array(
				'methods'             => 'POST',
				'callback'            => array( $o, 'ppom_update_meta_order' ),
				'permission_callback' => array( $this, 'check_write_permission' ),
			)
		);

		register_rest_route(
			'ppom/v1',
			'/delete/order/',
			array(
				'methods'             => 'POST',
				'callback'            => array( $o, 'delete_ppom_fields_order' ),
				'permission_callback' => array( $this, 'check_write_permission' ),
			)
		);
	}

	// -------------------------------------------------------------------------
	// Backward compatibility: former public API of PPOM_Rest.
	// -------------------------------------------------------------------------

	/**
	 * Validates the shared PPOM REST secret key.
	 *
	 * @param string $secretkey Secret key submitted by the API client.
	 *
	 * @return bool
	 */
	public function is_secret_key_valid( $secretkey ) {

		return $this->secrets->is_secret_key_valid( $secretkey );
	}

	/**
	 * Forwards to {@see ProductFieldGroupService::save_new_meta_data()}.
	 *
	 * @phpstan-param array<int|string, array<string, mixed>> $ppom_fields
	 * @phpstan-return array<string, mixed>
	 *
	 * @param int   $product_id  Product ID receiving the new field group.
	 * @param array $ppom_fields Field definitions decoded from the request.
	 *
	 * @return array
	 */
	public function save_new_meta_data( $product_id, $ppom_fields ) {

		return $this->field_groups->save_new_meta_data( $product_id, $ppom_fields );
	}

	/**
	 * Forwards to {@see ProductFieldGroupService::update_meta_data()}.
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

		return $this->field_groups->update_meta_data( $ppom_meta, $ppom_fields, $product_id );
	}

	/**
	 * Forwards to {@see ProductFieldGroupService::delete_meta_data()}.
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

		return $this->field_groups->delete_meta_data( $ppom_meta, $delete_fields, $product_id );
	}

	/**
	 * Forwards to {@see OrderItemMetaPresenter::get_order_item_meta()}.
	 *
	 * @phpstan-return list<array<string, mixed>>
	 *
	 * @param int $order_id WooCommerce order ID.
	 *
	 * @return array
	 */
	public function get_order_item_meta( $order_id ) {

		return $this->order_presenter->get_order_item_meta( $order_id );
	}

	/**
	 * Forwards to {@see RestFieldFormatter::filter_required_keys_only()}.
	 *
	 * @phpstan-param array<int|string, array<string, mixed>> $ppom_fields
	 * @phpstan-return list<array<string, mixed>>
	 *
	 * @param array $ppom_fields Full PPOM field definitions.
	 *
	 * @return array
	 */
	public function filter_required_keys_only( $ppom_fields ) {

		return $this->formatter->filter_required_keys_only( $ppom_fields );
	}

	/**
	 * Legacy no-op: WordPress core sends REST CORS via `rest_send_cors_headers`.
	 *
	 * @return void
	 */
	public function set_headers() {
	}

	/**
	 * Checks if the current user can read PPOM REST data.
	 *
	 * @return bool
	 */
	public function check_read_permission() {
		return current_user_can( 'edit_products' ); // phpcs:ignore WordPress.WP.Capabilities.Unknown
	}

	/**
	 * Checks if the current user can write PPOM REST data.
	 *
	 * @return bool
	 */
	public function check_write_permission() {
		return current_user_can( 'manage_woocommerce' ); // phpcs:ignore WordPress.WP.Capabilities.Unknown
	}
}

if ( ! class_exists( 'PPOM_Rest', false ) ) {
	class_alias( Routes::class, 'PPOM_Rest' );
}
