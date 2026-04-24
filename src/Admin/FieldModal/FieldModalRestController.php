<?php
/**
 * Admin-only REST API for the React field modal.
 *
 * @package PPOM
 * @subpackage Admin\FieldModal
 */

namespace PPOM\Admin\FieldModal;

use PPOM\Meta\MetaRepositoryAccessor;
use PPOM\Support\Helpers;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * REST endpoints for loading and saving field groups from the React modal.
 *
 * @phpstan-type BootPayload array<string, mixed>
 * @phpstan-type SaveSuccess array{success: bool, message: string, productmeta_id: int, redirect_to: string, fields: array<int, array<string, mixed>>}
 *
 * @internal
 */
final class FieldModalRestController {

	/**
	 * REST route namespace (same base as public PPOM API).
	 *
	 * Public class constant for PHP 7.0+ compatibility (no visibility modifier required).
	 */
	const REST_NAMESPACE = 'ppom/v1';

	/**
	 * Schema builder instance.
	 *
	 * @var FieldModalSchemaBuilder
	 */
	private $schema_builder;

	/**
	 * Persistence handler instance.
	 *
	 * @var FieldModalPersistence
	 */
	private $persistence;

	/**
	 * Creates the controller with optional collaborators (for tests).
	 *
	 * Parameters are untyped so this stays valid on PHP 7.0 (no nullable object type hints).
	 *
	 * @param FieldModalSchemaBuilder|null $schema_builder Optional schema builder.
	 * @param FieldModalPersistence|null   $persistence    Optional persistence handler.
	 *
	 * @phpstan-param FieldModalSchemaBuilder|null $schema_builder
	 * @phpstan-param FieldModalPersistence|null   $persistence
	 */
	public function __construct( $schema_builder = null, $persistence = null ) {
		if ( $schema_builder instanceof FieldModalSchemaBuilder ) {
			$this->schema_builder = $schema_builder;
		} else {
			$this->schema_builder = new FieldModalSchemaBuilder();
		}
		if ( $persistence instanceof FieldModalPersistence ) {
			$this->persistence = $persistence;
		} else {
			$this->persistence = new FieldModalPersistence();
		}
	}

	/**
	 * Registers `ppom/v1/admin/field-groups` routes.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			self::REST_NAMESPACE,
			'/admin/field-groups/context',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_context' ),
				'permission_callback' => array( $this, 'permission_check' ),
				'args'                => array(
					'productmeta_id' => array(
						'type'              => 'integer',
						'default'           => 0,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/admin/field-groups/schema/(?P<type>[a-z0-9_-]+)',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_type_schema' ),
				'permission_callback' => array( $this, 'permission_check' ),
				'args'                => array(
					'type' => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_key',
					),
				),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/admin/field-groups',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'create_group' ),
				'permission_callback' => array( $this, 'permission_check' ),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/admin/field-groups/(?P<id>\d+)',
			array(
				'methods'             => array( 'PUT', 'PATCH' ),
				'callback'            => array( $this, 'update_group' ),
				'permission_callback' => array( $this, 'permission_check' ),
				'args'                => array(
					'id' => array(
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
				),
			)
		);
	}

	/**
	 * Ensures the current user matches PPOM admin permission roles.
	 *
	 * @return bool|WP_Error
	 */
	public function permission_check() {
		if ( ! Helpers::security_role() ) {
			return new WP_Error(
				'ppom_forbidden',
				__( 'Sorry, you are not allowed to perform this action.', 'woocommerce-product-addon' ),
				array( 'status' => 403 )
			);
		}
		return true;
	}

	/**
	 * GET JSON schema for one field type (lazy load for React modal).
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function get_type_schema( WP_REST_Request $request ) {
		$type   = (string) $request->get_param( 'type' );
		$schema = $this->schema_builder->get_schema_for_type( $type );

		return rest_ensure_response(
			array(
				'type'   => $type,
				'schema' => $schema,
			)
		);
	}

	/**
	 * GET context for bootstrapping the React modal.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error Response body includes catalog, fields, group, type_schemas, links, and runtime flags.
	 *
	 * @phpstan-return WP_REST_Response|WP_Error
	 */
	public function get_context( WP_REST_Request $request ) {
		$productmeta_id = (int) $request->get_param( 'productmeta_id' );
		$cfr_upgrade_url = PPOM_UPGRADE_URL;
		if ( function_exists( 'tsdk_translate_link' ) ) {
			$cfr_upgrade_url = tsdk_translate_link( $cfr_upgrade_url );
		}
		if ( function_exists( 'tsdk_utmify' ) ) {
			$cfr_upgrade_url = tsdk_utmify( $cfr_upgrade_url, 'react-field-modal', 'cfr' );
		}
		$condition_upgrade_url = PPOM_UPGRADE_URL;
		if ( function_exists( 'tsdk_translate_link' ) ) {
			$condition_upgrade_url = tsdk_translate_link( $condition_upgrade_url );
		}
		if ( function_exists( 'tsdk_utmify' ) ) {
			$condition_upgrade_url = tsdk_utmify( $condition_upgrade_url, 'react-field-modal', 'condition' );
		}

		$catalog        = $this->schema_builder->get_catalog();
		$catalog_groups = $this->schema_builder->get_catalog_groups_for_rest();
		$license_ctx    = function_exists( 'ppom_get_admin_field_modal_license_context' )
			? ppom_get_admin_field_modal_license_context()
			: array(
				'plan_category'  => -1,
				'license_status' => 'invalid',
				'show_upsell'    => true,
			);
		$upsell         = function_exists( 'ppom_get_admin_field_modal_upsell_for_rest' )
			? ppom_get_admin_field_modal_upsell_for_rest()
			: null;

		$group = array(
			'productmeta_name'      => '',
			'dynamic_price_display' => 'hide',
			'send_file_attachment'  => 'YES',
			'show_cart_thumb'       => 'YES',
			'aviary_api_key'        => '',
			'productmeta_style'     => '',
			'productmeta_js'        => '',
		);

		$fields = array();

		$conditions_pro_enabled = function_exists( 'ppom_pro_is_installed' )
			&& ppom_pro_is_installed()
			&& 'valid' === apply_filters( 'product_ppom_license_status', '' );

		$conditional_repeater_unlocked = false;
		if ( function_exists( 'ppom_pro_is_installed' ) && ppom_pro_is_installed() && function_exists( 'PPOM' ) ) {
			$ppom = PPOM();
			if ( is_object( $ppom ) && method_exists( $ppom, 'is_license_of_type' ) ) {
				$conditional_repeater_unlocked = $ppom->is_license_of_type( 'plus' );
			}
		}
		/**
		 * Whether Conditional Field Repeater (Plus tier) is available in the admin UI.
		 * Tests may override without loading PPOM Pro.
		 *
		 * @param bool $unlocked       Default from license check.
		 * @param int  $productmeta_id Field group id or 0.
		 */
		$conditional_repeater_unlocked    = (bool) apply_filters(
			'ppom_field_modal_conditional_repeater_unlocked',
			$conditional_repeater_unlocked,
			$productmeta_id
		);
		$conditional_repeater_show_upsell = ! $conditional_repeater_unlocked;

		if ( $productmeta_id > 0 ) {
			$row = MetaRepositoryAccessor::instance()->get_row_by_id( $productmeta_id );
			if ( null === $row ) {
				return new WP_Error(
					'ppom_not_found',
					__( 'Field group not found.', 'woocommerce-product-addon' ),
					array( 'status' => 404 )
				);
			}
			$group['productmeta_name']      = isset( $row->productmeta_name ) ? (string) $row->productmeta_name : '';
			$group['dynamic_price_display'] = isset( $row->dynamic_price_display ) ? (string) $row->dynamic_price_display : 'hide';
			$group['send_file_attachment']  = isset( $row->send_file_attachment ) ? (string) $row->send_file_attachment : 'YES';
			$group['show_cart_thumb']       = isset( $row->show_cart_thumb ) ? (string) $row->show_cart_thumb : 'YES';
			$group['aviary_api_key']        = isset( $row->aviary_api_key ) ? (string) $row->aviary_api_key : '';
			$group['productmeta_style']     = isset( $row->productmeta_style ) ? (string) $row->productmeta_style : '';
			$group['productmeta_js']        = isset( $row->productmeta_js ) ? (string) $row->productmeta_js : '';

			$decoded = json_decode( isset( $row->the_meta ) ? (string) $row->the_meta : '[]', true );
			$fields  = is_array( $decoded ) ? array_values( $decoded ) : array();
		}

		$payload = array(
			'productmeta_id'                   => $productmeta_id,
			'conditions_pro_enabled'           => $conditions_pro_enabled,
			'conditional_repeater_unlocked'    => $conditional_repeater_unlocked,
			'conditional_repeater_show_upsell' => $conditional_repeater_show_upsell,
			'group'                            => $group,
			'fields'                           => $fields,
			'catalog'                          => $catalog,
			'catalog_groups'                   => $catalog_groups,
			'type_schemas'                     => array(),
			'license'                          => array(
				'plan_category'  => (int) $license_ctx['plan_category'],
				'license_status' => (string) $license_ctx['license_status'],
				'show_upsell'    => (bool) $license_ctx['show_upsell'],
			),
			'upsell'                           => $upsell,
			'links'                            => array(
				'cfrDocsUrl'         => 'https://docs.themeisle.com/article/1700-personalized-product-meta-manager#conditional-repeater',
				'cfrUpgradeUrl'      => esc_url( $cfr_upgrade_url ),
				'cfrViewDemoUrl'     => 'https://demo-ppom-lite.vertisite.cloud/product/personalized-caps-using-conditional-repeater/',
				'conditionUpgradeUrl' => esc_url( $condition_upgrade_url ),
			),
			'rest_url'                         => rest_url( self::REST_NAMESPACE . '/' ),
			'nonce'                            => wp_create_nonce( 'wp_rest' ),
		);

		/**
		 * Augment or replace boot payload for the React field modal.
		 *
		 * @param array<string, mixed> $payload        Boot payload.
		 * @param int                  $productmeta_id Group id or 0 for new.
		 *
		 * @phpstan-param BootPayload $payload
		 */
		$payload = apply_filters( 'ppom_admin_field_modal_boot_payload', $payload, $productmeta_id );

		return rest_ensure_response( $payload );
	}

	/**
	 * POST create field group.
	 *
	 * @param WP_REST_Request $request Request with JSON body: group, fields.
	 * @return WP_REST_Response|WP_Error
	 *
	 * @phpstan-return WP_REST_Response|WP_Error
	 */
	public function create_group( WP_REST_Request $request ) {
		$params = json_decode( $request->get_body(), true );
		if ( ! is_array( $params ) ) {
			return new WP_Error( 'ppom_invalid_json', __( 'Invalid JSON body.', 'woocommerce-product-addon' ), array( 'status' => 400 ) );
		}

		$group  = isset( $params['group'] ) && is_array( $params['group'] ) ? $params['group'] : array();
		$fields = isset( $params['fields'] ) && is_array( $params['fields'] ) ? array_values( $params['fields'] ) : array();

		$result = $this->persistence->create_group( $group, $fields );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return rest_ensure_response(
			array(
				'success'        => true,
				'message'        => $result['message'],
				'productmeta_id' => $result['productmeta_id'],
				'redirect_to'    => $result['redirect_to'],
				'fields'         => $result['fields'],
			)
		);
	}

	/**
	 * PUT update field group.
	 *
	 * @param WP_REST_Request $request Request with JSON body: group, fields; URL id param.
	 * @return WP_REST_Response|WP_Error
	 *
	 * @phpstan-return WP_REST_Response|WP_Error
	 */
	public function update_group( WP_REST_Request $request ) {
		$id     = (int) $request->get_param( 'id' );
		$params = json_decode( $request->get_body(), true );
		if ( ! is_array( $params ) ) {
			return new WP_Error( 'ppom_invalid_json', __( 'Invalid JSON body.', 'woocommerce-product-addon' ), array( 'status' => 400 ) );
		}

		$group  = isset( $params['group'] ) && is_array( $params['group'] ) ? $params['group'] : array();
		$fields = isset( $params['fields'] ) && is_array( $params['fields'] ) ? array_values( $params['fields'] ) : array();

		$result = $this->persistence->update_group( $id, $group, $fields );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return rest_ensure_response(
			array(
				'success'        => true,
				'message'        => $result['message'],
				'productmeta_id' => $result['productmeta_id'],
				'redirect_to'    => $result['redirect_to'],
				'fields'         => $result['fields'],
			)
		);
	}
}
