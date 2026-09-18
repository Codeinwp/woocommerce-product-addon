<?php
/**
 * Registers PPOM abilities with the WordPress Abilities API (WP 6.9+).
 *
 * Every ability is a thin wrapper over existing PPOM services: the field-group
 * repository, the admin manager persistence helpers, the field modal schema
 * builder and the order REST presenter.
 *
 * @package PPOM
 * @subpackage Abilities
 */

namespace PPOM\Abilities;

use PPOM\Admin\FieldModal\FieldModalSchemaBuilder;
use PPOM\Admin\Manager;
use PPOM\Core\RegisterHooks;
use PPOM\Meta\MetaRepositoryAccessor;
use PPOM\Rest\OrderItemMetaPresenter;
use PPOM\Support\Helpers;
use WP_Error;

/**
 * PPOM abilities.
 *
 * @internal
 *
 * @phpstan-import-type PPOM_Meta_Group_Row from \PPOM_Meta_Repository
 */
final class Abilities implements RegisterHooks {

	const CATEGORY = 'ppom';

	const MAX_PER_PAGE = 100;

	const MAX_FIELDS = 200;

	const MAX_TITLE_LENGTH = 50;

	/**
	 * @return void
	 */
	public function register(): void {
		if ( ! function_exists( 'wp_register_ability' ) ) {
			return;
		}

		add_action( 'wp_abilities_api_categories_init', array( $this, 'register_category' ) );
		add_action( 'wp_abilities_api_init', array( $this, 'register_abilities' ) );
	}

	/**
	 * @return void
	 */
	public function register_category() {
		if ( ! function_exists( 'wp_register_ability_category' ) ) {
			return;
		}

		wp_register_ability_category(
			self::CATEGORY,
			array(
				'label'       => __( 'PPOM', 'woocommerce-product-addon' ),
				'description' => __( 'Product field groups, field types and customer selections managed by PPOM.', 'woocommerce-product-addon' ),
			)
		);
	}

	/**
	 * @return void
	 */
	public function register_abilities() {
		$group_summary_schema = array(
			'type'       => 'object',
			'properties' => array(
				'id'          => array( 'type' => 'integer' ),
				'title'       => array( 'type' => 'string' ),
				'disabled'    => array( 'type' => 'boolean' ),
				'field_count' => array( 'type' => 'integer' ),
				'created'     => array( 'type' => 'string' ),
			),
		);

		$field_schema = array(
			'type'                 => 'object',
			'description'          => __( 'A PPOM field definition. Requires "type" (see ppom/list-field-types), "data_name" (unique lowercase key) and "title". Other keys follow the settings schema of the field type, including "options" (with per-option "price"), "logic" and "conditions".', 'woocommerce-product-addon' ),
			'properties'           => array(
				'type'      => array( 'type' => 'string' ),
				'data_name' => array( 'type' => 'string' ),
				'title'     => array( 'type' => 'string' ),
			),
			'additionalProperties' => true,
		);

		$group_schema = array(
			'type'       => 'object',
			'properties' => array(
				'id'                    => array( 'type' => 'integer' ),
				'title'                 => array( 'type' => 'string' ),
				'disabled'              => array( 'type' => 'boolean' ),
				'dynamic_price_display' => array( 'type' => 'string' ),
				'created'               => array( 'type' => 'string' ),
				'fields'                => array(
					'type'  => 'array',
					'items' => $field_schema,
				),
			),
		);

		wp_register_ability(
			'ppom/assign-field-groups',
			array(
				'label'               => __( 'Assign PPOM field groups to a product', 'woocommerce-product-addon' ),
				'description'         => __( 'Reads the PPOM field groups attached directly to a product, or replaces them when group_ids is provided. Attaching more than one group requires PPOM Pro.', 'woocommerce-product-addon' ),
				'category'            => self::CATEGORY,
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'product_id' => array(
							'type'        => 'integer',
							'description' => __( 'WooCommerce product ID.', 'woocommerce-product-addon' ),
						),
						'group_ids'  => array(
							'type'        => 'array',
							'description' => __( 'Field group IDs to attach. Omit to read the current assignment; pass an empty list to detach all groups.', 'woocommerce-product-addon' ),
							'items'       => array( 'type' => 'integer' ),
						),
					),
					'required'   => array( 'product_id' ),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'product_id' => array( 'type' => 'integer' ),
						'group_ids'  => array(
							'type'  => 'array',
							'items' => array( 'type' => 'integer' ),
						),
						'groups'     => array(
							'type'  => 'array',
							'items' => array(
								'type'       => 'object',
								'properties' => array(
									'id'    => array( 'type' => 'integer' ),
									'title' => array( 'type' => 'string' ),
								),
							),
						),
					),
				),
				'execute_callback'    => array( $this, 'assign_field_groups' ),
				'permission_callback' => array( $this, 'can_edit_product' ),
				'meta'                => array(
					'annotations'  => array(
						'readonly'    => false,
						'destructive' => false,
						'idempotent'  => true,
					),
					'show_in_rest' => true,
				),
			)
		);

		wp_register_ability(
			'ppom/get-order-selections',
			array(
				'label'               => __( 'Get PPOM order selections', 'woocommerce-product-addon' ),
				'description'         => __( 'Returns the PPOM field values customers submitted for each line item of an order, including the names of uploaded files. Contains customer data.', 'woocommerce-product-addon' ),
				'category'            => self::CATEGORY,
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'order_id' => array(
							'type'        => 'integer',
							'description' => __( 'WooCommerce order ID.', 'woocommerce-product-addon' ),
						),
					),
					'required'   => array( 'order_id' ),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'order_id'   => array( 'type' => 'integer' ),
						'line_items' => array(
							'type'  => 'array',
							'items' => array(
								'type'       => 'object',
								'properties' => array(
									'item_id'    => array( 'type' => 'integer' ),
									'product_id' => array( 'type' => 'integer' ),
									'selections' => array(
										'type'  => 'array',
										'items' => array(
											'type'       => 'object',
											'properties' => array(
												'field'   => array( 'type' => 'string' ),
												'label'   => array( 'type' => 'string' ),
												'value'   => array( 'type' => 'string' ),
												'display' => array( 'type' => 'string' ),
											),
										),
									),
									'files'      => array(
										'type'  => 'array',
										'items' => array(
											'type'       => 'object',
											'properties' => array(
												'field' => array( 'type' => 'string' ),
												'name'  => array( 'type' => 'string' ),
											),
										),
									),
								),
							),
						),
					),
				),
				'execute_callback'    => array( $this, 'get_order_selections' ),
				'permission_callback' => array( $this, 'can_manage_woocommerce' ),
				'meta'                => array(
					'annotations'  => array(
						'readonly'    => true,
						'destructive' => false,
						'idempotent'  => true,
					),
					'show_in_rest' => true,
				),
			)
		);

		wp_register_ability(
			'ppom/list-field-types',
			array(
				'label'               => __( 'List PPOM field types', 'woocommerce-product-addon' ),
				'description'         => __( 'Lists the available PPOM field types and whether each one is locked behind a PPOM Pro plan. Pass "type" to also get the settings schema of one field type.', 'woocommerce-product-addon' ),
				'category'            => self::CATEGORY,
				'input_schema'        => array(
					'type'       => 'object',
					'default'    => array(),
					'properties' => array(
						'type' => array(
							'type'        => 'string',
							'description' => __( 'Optional field type slug to return the settings schema for.', 'woocommerce-product-addon' ),
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'types'  => array(
							'type'  => 'array',
							'items' => array(
								'type'       => 'object',
								'properties' => array(
									'slug'        => array( 'type' => 'string' ),
									'title'       => array( 'type' => 'string' ),
									'description' => array( 'type' => 'string' ),
									'group'       => array( 'type' => 'string' ),
									'locked'      => array( 'type' => 'boolean' ),
									'min_plan'    => array( 'type' => 'string' ),
								),
							),
						),
						'schema' => array(
							'type'                 => 'object',
							'additionalProperties' => true,
						),
					),
				),
				'execute_callback'    => array( $this, 'list_field_types' ),
				'permission_callback' => array( $this, 'can_manage_field_groups' ),
				'meta'                => array(
					'annotations'  => array(
						'readonly'    => true,
						'destructive' => false,
						'idempotent'  => true,
					),
					'show_in_rest' => true,
				),
			)
		);

		wp_register_ability(
			'ppom/list-field-groups',
			array(
				'label'               => __( 'List PPOM field groups', 'woocommerce-product-addon' ),
				'description'         => __( 'Lists reusable PPOM field groups with pagination and an optional title search.', 'woocommerce-product-addon' ),
				'category'            => self::CATEGORY,
				'input_schema'        => array(
					'type'       => 'object',
					'default'    => array(),
					'properties' => array(
						'search'   => array(
							'type'        => 'string',
							'description' => __( 'Optional search term matched against the group title.', 'woocommerce-product-addon' ),
						),
						'page'     => array(
							'type'    => 'integer',
							'minimum' => 1,
							'default' => 1,
						),
						'per_page' => array(
							'type'    => 'integer',
							'minimum' => 1,
							'maximum' => self::MAX_PER_PAGE,
							'default' => 50,
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'groups' => array(
							'type'  => 'array',
							'items' => $group_summary_schema,
						),
						'total'  => array( 'type' => 'integer' ),
						'page'   => array( 'type' => 'integer' ),
					),
				),
				'execute_callback'    => array( $this, 'list_field_groups' ),
				'permission_callback' => array( $this, 'can_manage_field_groups' ),
				'meta'                => array(
					'annotations'  => array(
						'readonly'    => true,
						'destructive' => false,
						'idempotent'  => true,
					),
					'show_in_rest' => true,
				),
			)
		);

		wp_register_ability(
			'ppom/get-field-group',
			array(
				'label'               => __( 'Get a PPOM field group', 'woocommerce-product-addon' ),
				'description'         => __( 'Returns one PPOM field group with its settings and full field definitions, including option prices and conditional logic.', 'woocommerce-product-addon' ),
				'category'            => self::CATEGORY,
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'group_id' => array(
							'type'        => 'integer',
							'description' => __( 'Field group ID.', 'woocommerce-product-addon' ),
						),
					),
					'required'   => array( 'group_id' ),
				),
				'output_schema'       => $group_schema,
				'execute_callback'    => array( $this, 'get_field_group' ),
				'permission_callback' => array( $this, 'can_manage_field_groups' ),
				'meta'                => array(
					'annotations'  => array(
						'readonly'    => true,
						'destructive' => false,
						'idempotent'  => true,
					),
					'show_in_rest' => true,
				),
			)
		);

		wp_register_ability(
			'ppom/upsert-field-group',
			array(
				'label'               => __( 'Create or update a PPOM field group', 'woocommerce-product-addon' ),
				'description'         => __( 'Creates a PPOM field group, or updates it when group_id is provided. On update the submitted fields replace the stored fields; omitted settings keep their stored value. Conditions and option prices are part of each field definition. Set dry_run to validate without saving.', 'woocommerce-product-addon' ),
				'category'            => self::CATEGORY,
				'input_schema'        => array(
					'type'       => 'object',
					'default'    => array(),
					'properties' => array(
						'group_id'              => array(
							'type'        => 'integer',
							'description' => __( 'Existing field group ID. Omit to create a new group.', 'woocommerce-product-addon' ),
						),
						'title'                 => array(
							'type'        => 'string',
							'description' => __( 'Group title, at most 50 characters. Required when creating.', 'woocommerce-product-addon' ),
							'maxLength'   => self::MAX_TITLE_LENGTH,
						),
						'fields'                => array(
							'type'        => 'array',
							'description' => __( 'Field definitions. Required when creating.', 'woocommerce-product-addon' ),
							'items'       => $field_schema,
							'maxItems'    => self::MAX_FIELDS,
						),
						'dynamic_price_display' => array(
							'type'        => 'string',
							'description' => __( 'Price table display: "no" (not set), "hide" (do not show the price table), "option_sum" (show only the options total) or "all_option" (show each option price).', 'woocommerce-product-addon' ),
							'enum'        => array( 'no', 'hide', 'option_sum', 'all_option' ),
						),
						'dry_run'               => array(
							'type'    => 'boolean',
							'default' => false,
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'id'      => array( 'type' => 'integer' ),
						'created' => array( 'type' => 'boolean' ),
						'dry_run' => array( 'type' => 'boolean' ),
						'valid'   => array( 'type' => 'boolean' ),
						'errors'  => array(
							'type'  => 'array',
							'items' => array(
								'type'       => 'object',
								'properties' => array(
									'code'    => array( 'type' => 'string' ),
									'message' => array( 'type' => 'string' ),
								),
							),
						),
						'group'   => $group_schema,
					),
				),
				'execute_callback'    => array( $this, 'upsert_field_group' ),
				'permission_callback' => array( $this, 'can_manage_field_groups' ),
				'meta'                => array(
					'annotations'  => array(
						'readonly'    => false,
						'destructive' => false,
						'idempotent'  => true,
					),
					'show_in_rest' => true,
				),
			)
		);

		wp_register_ability(
			'ppom/delete-field-group',
			array(
				'label'               => __( 'Delete a PPOM field group', 'woocommerce-product-addon' ),
				'description'         => __( 'Permanently deletes a PPOM field group and removes it from the products it was attached to.', 'woocommerce-product-addon' ),
				'category'            => self::CATEGORY,
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'group_id' => array(
							'type'        => 'integer',
							'description' => __( 'Field group ID.', 'woocommerce-product-addon' ),
						),
					),
					'required'   => array( 'group_id' ),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'id'      => array( 'type' => 'integer' ),
						'deleted' => array( 'type' => 'boolean' ),
					),
				),
				'execute_callback'    => array( $this, 'delete_field_group' ),
				'permission_callback' => array( $this, 'can_manage_field_groups' ),
				'meta'                => array(
					'annotations'  => array(
						'readonly'    => false,
						'destructive' => true,
						'idempotent'  => false,
					),
					'show_in_rest' => true,
				),
			)
		);
	}

	// Permissions.

	/**
	 * Mirrors the field-group admin screen, its AJAX handlers and the field modal REST routes.
	 *
	 * @return bool
	 *
	 * @see Helpers::security_role()
	 */
	public function can_manage_field_groups() {
		return (bool) Helpers::security_role();
	}

	/**
	 * Mirrors the `ppom/v1/get/order` REST route.
	 *
	 * @return bool
	 *
	 * @see \PPOM\Rest\Routes::check_write_permission()
	 */
	public function can_manage_woocommerce() {
		return current_user_can( 'manage_woocommerce' ); // phpcs:ignore WordPress.WP.Capabilities.Unknown
	}

	/**
	 * Mirrors the product edit screen and the attach link: the user must be able to edit the product.
	 *
	 * @param mixed $input Ability input.
	 * @return bool
	 */
	public function can_edit_product( $input = null ) {
		$product_id = is_array( $input ) && isset( $input['product_id'] ) ? absint( $input['product_id'] ) : 0;
		if ( $product_id <= 0 ) {
			return current_user_can( 'edit_products' ); // phpcs:ignore WordPress.WP.Capabilities.Unknown
		}

		return current_user_can( 'edit_post', $product_id );
	}

	// Callbacks.

	/**
	 * @param mixed $input Ability input.
	 * @return array<string, mixed>|WP_Error
	 */
	public function assign_field_groups( $input = null ) {
		$input      = is_array( $input ) ? $input : array();
		$product_id = isset( $input['product_id'] ) ? absint( $input['product_id'] ) : 0;

		if ( $product_id <= 0 || 'product' !== get_post_type( $product_id ) ) {
			return new WP_Error( 'ppom_product_not_found', __( 'Product not found.', 'woocommerce-product-addon' ), array( 'status' => 404 ) );
		}

		if ( ! current_user_can( 'edit_post', $product_id ) ) {
			return new WP_Error( 'ppom_forbidden', __( 'Sorry, you are not allowed to perform this action.', 'woocommerce-product-addon' ), array( 'status' => 403 ) );
		}

		if ( array_key_exists( 'group_ids', $input ) ) {
			if ( ! is_array( $input['group_ids'] ) ) {
				return new WP_Error( 'ppom_invalid_group_ids', __( 'group_ids must be a list of field group IDs.', 'woocommerce-product-addon' ), array( 'status' => 400 ) );
			}

			$group_ids = Helpers::normalize_ppom_meta_ids( $input['group_ids'] );

			if ( count( $group_ids ) > 1 && ! Helpers::pro_is_installed() ) {
				return new WP_Error( 'ppom_pro_required', __( 'Using multiple PPOM field groups on the same product is available in PRO.', 'woocommerce-product-addon' ), array( 'status' => 403 ) );
			}

			$found = array();
			foreach ( MetaRepositoryAccessor::instance()->get_rows_by_ids( $group_ids ) as $row ) {
				$found[] = (int) $row->productmeta_id;
			}
			$missing = array_values( array_diff( $group_ids, $found ) );
			if ( ! empty( $missing ) ) {
				return new WP_Error(
					'ppom_group_not_found',
					/* translators: %s: comma separated field group IDs */
					sprintf( __( 'Field group not found: %s', 'woocommerce-product-addon' ), implode( ', ', $missing ) ),
					array( 'status' => 404 )
				);
			}

			Manager::set_product_field_groups( $product_id, $group_ids );
		}

		$assigned = Helpers::normalize_ppom_meta_ids( get_post_meta( $product_id, PPOM_PRODUCT_META_KEY, true ) );
		$groups   = array();
		foreach ( MetaRepositoryAccessor::instance()->get_rows_by_ids( $assigned ) as $row ) {
			$groups[] = array(
				'id'    => (int) $row->productmeta_id,
				'title' => stripslashes( (string) $row->productmeta_name ),
			);
		}

		return array(
			'product_id' => $product_id,
			'group_ids'  => $assigned,
			'groups'     => $groups,
		);
	}

	/**
	 * @param mixed $input Ability input.
	 * @return array<string, mixed>|WP_Error
	 */
	public function get_order_selections( $input = null ) {
		$order_id = is_array( $input ) && isset( $input['order_id'] ) ? absint( $input['order_id'] ) : 0;

		if ( ! function_exists( 'wc_get_order' ) ) {
			return new WP_Error( 'ppom_woocommerce_missing', __( 'WooCommerce is not active.', 'woocommerce-product-addon' ), array( 'status' => 500 ) );
		}

		$order = $order_id > 0 ? wc_get_order( $order_id ) : false;
		if ( ! $order instanceof \WC_Order ) {
			return new WP_Error( 'ppom_order_not_found', __( 'No Order Found', 'woocommerce-product-addon' ), array( 'status' => 404 ) );
		}

		// The presenter emits one entry per order item, in order-item order.
		$presented  = ( new OrderItemMetaPresenter() )->get_order_item_meta( $order_id );
		$line_items = array();
		$index      = 0;

		foreach ( $order->get_items() as $item_id => $item ) {
			$entry = isset( $presented[ $index ] ) ? $presented[ $index ] : array();
			++$index;

			$product_id = isset( $entry['product_id'] ) ? (int) $entry['product_id'] : 0;
			$selections = array();

			if ( ! empty( $entry['product_meta_data'] ) && is_array( $entry['product_meta_data'] ) ) {
				foreach ( $entry['product_meta_data'] as $meta ) {
					$key        = isset( $meta['key'] ) ? (string) $meta['key'] : '';
					$field_meta = Helpers::get_field_meta_by_dataname( $product_id, $key );

					$selections[] = array(
						'field'   => $key,
						'label'   => is_array( $field_meta ) && isset( $field_meta['title'] ) ? (string) $field_meta['title'] : $key,
						'value'   => $this->to_text( isset( $meta['value'] ) ? $meta['value'] : '' ),
						'display' => $this->to_text( isset( $meta['display'] ) ? $meta['display'] : '' ),
					);
				}
			}

			$line_items[] = array(
				'item_id'    => (int) $item_id,
				'product_id' => $product_id,
				'selections' => $selections,
				'files'      => $this->extract_uploaded_files( $item->get_meta( '_ppom_fields' ) ),
			);
		}

		return array(
			'order_id'   => $order_id,
			'line_items' => $line_items,
		);
	}

	/**
	 * @param mixed $input Ability input.
	 * @return array<string, mixed>
	 */
	public function list_field_types( $input = null ) {
		$builder = new FieldModalSchemaBuilder();
		$types   = array();

		foreach ( $builder->get_catalog_groups_for_rest() as $group ) {
			if ( empty( $group['fields'] ) || ! is_array( $group['fields'] ) ) {
				continue;
			}
			foreach ( $group['fields'] as $field ) {
				$types[] = array(
					'slug'        => isset( $field['slug'] ) ? (string) $field['slug'] : '',
					'title'       => isset( $field['title'] ) ? (string) $field['title'] : '',
					'description' => isset( $field['description'] ) ? (string) $field['description'] : '',
					'group'       => isset( $group['label'] ) ? (string) $group['label'] : '',
					'locked'      => ! empty( $field['locked'] ),
					'min_plan'    => isset( $field['min_plan_label'] ) ? (string) $field['min_plan_label'] : '',
				);
			}
		}

		$result = array( 'types' => $types );

		$type = is_array( $input ) && isset( $input['type'] ) ? sanitize_key( $input['type'] ) : '';
		if ( '' !== $type ) {
			$result['schema'] = $builder->get_schema_for_type( $type );
		}

		return $result;
	}

	/**
	 * @param mixed $input Ability input.
	 * @return array<string, mixed>
	 */
	public function list_field_groups( $input = null ) {
		$input    = is_array( $input ) ? $input : array();
		$search   = isset( $input['search'] ) ? sanitize_text_field( (string) $input['search'] ) : '';
		$page     = isset( $input['page'] ) ? max( 1, absint( $input['page'] ) ) : 1;
		$per_page = isset( $input['per_page'] ) ? absint( $input['per_page'] ) : 50;
		$per_page = min( self::MAX_PER_PAGE, max( 1, $per_page ) );

		$repository = MetaRepositoryAccessor::instance();
		$rows       = $repository->get_paged_rows(
			array(
				'per_page' => $per_page,
				'paged'    => $page,
				'search'   => $search,
			)
		);

		$groups = array();
		foreach ( $rows as $row ) {
			$fields   = json_decode( isset( $row->the_meta ) ? (string) $row->the_meta : '[]', true );
			$groups[] = array(
				'id'          => (int) $row->productmeta_id,
				'title'       => stripslashes( (string) $row->productmeta_name ),
				'disabled'    => $this->is_row_disabled( $row ),
				'field_count' => is_array( $fields ) ? count( $fields ) : 0,
				'created'     => isset( $row->productmeta_created ) ? (string) $row->productmeta_created : '',
			);
		}

		return array(
			'groups' => $groups,
			'total'  => (int) $repository->count_filtered_rows( $search ),
			'page'   => $page,
		);
	}

	/**
	 * @param mixed $input Ability input.
	 * @return array<string, mixed>|WP_Error
	 */
	public function get_field_group( $input = null ) {
		$group_id = is_array( $input ) && isset( $input['group_id'] ) ? absint( $input['group_id'] ) : 0;
		$row      = MetaRepositoryAccessor::instance()->get_row_by_id( $group_id );

		if ( null === $row ) {
			return $this->group_not_found();
		}

		return $this->format_group( $row );
	}

	/**
	 * @param mixed $input Ability input.
	 * @return array<string, mixed>|WP_Error
	 */
	public function upsert_field_group( $input = null ) {
		$input    = is_array( $input ) ? $input : array();
		$group_id = isset( $input['group_id'] ) ? absint( $input['group_id'] ) : 0;
		$dry_run  = ! empty( $input['dry_run'] );
		$creating = $group_id <= 0;
		$row      = null;

		if ( floatval( get_option( 'personalizedproduct_db_version' ) ) < 22.1 ) {
			return new WP_Error( 'ppom_db_outdated', __( 'Since version 22.0, Database has some changes. Please Deactivate & then activate the PPOM plugin.', 'woocommerce-product-addon' ), array( 'status' => 500 ) );
		}

		if ( ! $creating ) {
			$row = MetaRepositoryAccessor::instance()->get_row_by_id( $group_id );
			if ( null === $row ) {
				return $this->group_not_found();
			}
		}

		$errors = array();

		// Title.
		if ( isset( $input['title'] ) ) {
			$title = sanitize_text_field( (string) $input['title'] );
		} else {
			$title = null !== $row ? (string) $row->productmeta_name : '';
		}
		if ( '' === $title ) {
			$errors[] = $this->error_item( 'ppom_missing_title', __( 'A title is required.', 'woocommerce-product-addon' ) );
		} elseif ( strlen( $title ) > self::MAX_TITLE_LENGTH ) {
			$errors[] = $this->error_item( 'ppom_title_too_long', __( 'PPOM title is too long to save, please make it less than 50 characters.', 'woocommerce-product-addon' ) );
		}

		// Fields.
		if ( isset( $input['fields'] ) && is_array( $input['fields'] ) ) {
			$fields = array_values( $input['fields'] );
		} elseif ( null !== $row ) {
			$decoded = json_decode( (string) $row->the_meta, true );
			$fields  = is_array( $decoded ) ? array_values( $decoded ) : array();
		} else {
			$fields = array();
		}

		if ( empty( $fields ) && ( $creating || isset( $input['fields'] ) ) ) {
			$errors[] = $this->error_item( 'ppom_no_fields', __( 'No fields found.', 'woocommerce-product-addon' ) );
		}
		if ( count( $fields ) > self::MAX_FIELDS ) {
			/* translators: %d: maximum number of fields */
			$errors[] = $this->error_item( 'ppom_too_many_fields', sprintf( __( 'A field group accepts at most %d fields per request.', 'woocommerce-product-addon' ), self::MAX_FIELDS ) );
		}

		if ( isset( $input['dynamic_price_display'] ) && ! in_array( $input['dynamic_price_display'], array( 'no', 'hide', 'option_sum', 'all_option' ), true ) ) {
			$errors[] = $this->error_item( 'ppom_invalid_price_display', __( 'dynamic_price_display must be one of: no, hide, option_sum, all_option.', 'woocommerce-product-addon' ) );
		}

		if ( isset( $input['fields'] ) ) {
			$errors = array_merge( $errors, $this->validate_fields( $fields ) );
		}

		if ( $dry_run || ! empty( $errors ) ) {
			if ( ! $dry_run ) {
				return new WP_Error(
					'ppom_invalid_field_group',
					$errors[0]['message'],
					array(
						'status' => 400,
						'errors' => $errors,
					)
				);
			}

			return array(
				'id'      => $group_id,
				'created' => false,
				'dry_run' => true,
				'valid'   => empty( $errors ),
				'errors'  => $errors,
			);
		}

		$settings = array(
			'productmeta_name'      => $title,
			'dynamic_price_display' => $this->setting_value( $input, 'dynamic_price_display', $row, $creating ? 'no' : '' ),
			// Not exposed through abilities: keep whatever is stored.
			'send_file_attachment'  => null !== $row && isset( $row->send_file_attachment ) ? (string) $row->send_file_attachment : ( $creating ? 'NA' : '' ),
			'show_cart_thumb'       => null !== $row && isset( $row->show_cart_thumb ) ? (string) $row->show_cart_thumb : ( $creating ? 'NA' : '' ),
			'aviary_api_key'        => null !== $row && isset( $row->aviary_api_key ) ? (string) $row->aviary_api_key : ( $creating ? 'NA' : '' ),
			'productmeta_style'     => null !== $row && isset( $row->productmeta_style ) ? (string) $row->productmeta_style : '',
			'productmeta_js'        => null !== $row && isset( $row->productmeta_js ) ? (string) $row->productmeta_js : '',
		);

		if ( $creating ) {
			$group_id = (int) Manager::create_group_from_fields( $fields, $settings );
			if ( $group_id <= 0 ) {
				return new WP_Error( 'ppom_save_failed', __( 'The field group could not be saved.', 'woocommerce-product-addon' ), array( 'status' => 500 ) );
			}
		} else {
			$result = Manager::update_group_from_fields( $group_id, $fields, $settings );
			if ( false === $result ) {
				return new WP_Error( 'ppom_save_failed', __( 'The field group could not be saved.', 'woocommerce-product-addon' ), array( 'status' => 500 ) );
			}
		}

		$saved = MetaRepositoryAccessor::instance()->get_row_by_id( $group_id );

		$response = array(
			'id'      => $group_id,
			'created' => $creating,
			'dry_run' => false,
			'valid'   => true,
			'errors'  => array(),
		);
		if ( null !== $saved ) {
			$response['group'] = $this->format_group( $saved );
		}

		return $response;
	}

	/**
	 * @param mixed $input Ability input.
	 * @return array<string, mixed>|WP_Error
	 */
	public function delete_field_group( $input = null ) {
		$group_id   = is_array( $input ) && isset( $input['group_id'] ) ? absint( $input['group_id'] ) : 0;
		$repository = MetaRepositoryAccessor::instance();

		if ( null === $repository->get_row_by_id( $group_id ) ) {
			return $this->group_not_found();
		}

		if ( ! $repository->delete_by_id( $group_id ) ) {
			return new WP_Error( 'ppom_delete_failed', __( 'Error while deleting the PPOM, try again.', 'woocommerce-product-addon' ), array( 'status' => 500 ) );
		}

		return array(
			'id'      => $group_id,
			'deleted' => true,
		);
	}

	// Helpers.

	/**
	 * Validates submitted field definitions against the registered field types.
	 *
	 * @param array<int, mixed> $fields Submitted fields.
	 * @return array<int, array{code: string, message: string}>
	 */
	private function validate_fields( array $fields ) {
		$errors  = array();
		$catalog = array();
		foreach ( ( new FieldModalSchemaBuilder() )->get_catalog() as $item ) {
			if ( ! empty( $item['slug'] ) ) {
				$catalog[ (string) $item['slug'] ] = $item;
			}
		}
		$inputs = function_exists( 'PPOM' ) && is_array( PPOM()->inputs ) ? PPOM()->inputs : array();
		$seen   = array();

		foreach ( $fields as $index => $field ) {
			$position = (int) $index + 1;

			if ( ! is_array( $field ) ) {
				/* translators: %d: field position */
				$errors[] = $this->error_item( 'ppom_invalid_field', sprintf( __( 'Field %d must be an object.', 'woocommerce-product-addon' ), $position ) );
				continue;
			}

			$type      = isset( $field['type'] ) && is_scalar( $field['type'] ) ? sanitize_key( (string) $field['type'] ) : '';
			$data_name = isset( $field['data_name'] ) && is_scalar( $field['data_name'] ) ? (string) $field['data_name'] : '';

			if ( '' === $type ) {
				/* translators: %d: field position */
				$errors[] = $this->error_item( 'ppom_missing_field_type', sprintf( __( 'Field %d is missing "type".', 'woocommerce-product-addon' ), $position ) );
			} elseif ( isset( $catalog[ $type ] ) && ! empty( $catalog[ $type ]['locked'] ) ) {
				$errors[] = $this->error_item(
					'ppom_pro_field_type',
					/* translators: 1: field position, 2: field type, 3: plan name */
					sprintf( __( 'Field %1$d uses the "%2$s" field type, which requires PPOM Pro (%3$s plan or higher).', 'woocommerce-product-addon' ), $position, $type, (string) $catalog[ $type ]['min_plan_label'] )
				);
			} elseif ( ! isset( $inputs[ $type ] ) && isset( $catalog[ $type ] ) ) {
				/* translators: 1: field position, 2: field type */
				$errors[] = $this->error_item( 'ppom_field_type_unavailable', sprintf( __( 'Field %1$d uses the "%2$s" field type, which is not available on this site.', 'woocommerce-product-addon' ), $position, $type ) );
			} elseif ( ! isset( $inputs[ $type ] ) ) {
				/* translators: 1: field position, 2: field type */
				$errors[] = $this->error_item( 'ppom_unknown_field_type', sprintf( __( 'Field %1$d uses the unknown field type "%2$s".', 'woocommerce-product-addon' ), $position, $type ) );
			}

			if ( '' === $data_name ) {
				/* translators: %d: field position */
				$errors[] = $this->error_item( 'ppom_missing_data_name', sprintf( __( 'Field %d is missing "data_name".', 'woocommerce-product-addon' ), $position ) );
			} elseif ( sanitize_key( $data_name ) !== $data_name ) {
				/* translators: %d: field position */
				$errors[] = $this->error_item( 'ppom_invalid_data_name', sprintf( __( 'Field %d has an invalid "data_name": use lowercase letters, numbers, dashes and underscores only.', 'woocommerce-product-addon' ), $position ) );
			} elseif ( isset( $seen[ $data_name ] ) ) {
				/* translators: 1: field position, 2: data name */
				$errors[] = $this->error_item( 'ppom_duplicate_data_name', sprintf( __( 'Field %1$d reuses the "data_name" "%2$s".', 'woocommerce-product-addon' ), $position, $data_name ) );
			}
			$seen[ $data_name ] = true;
		}

		return $errors;
	}

	/**
	 * @param array<string, mixed> $input    Ability input.
	 * @param string               $key      Setting / column key.
	 * @param object|null          $row      Stored row on update.
	 * @param string               $fallback Default when neither input nor row has a value.
	 * @return string
	 */
	private function setting_value( array $input, $key, $row, $fallback ) {
		if ( isset( $input[ $key ] ) && is_scalar( $input[ $key ] ) ) {
			return sanitize_text_field( (string) $input[ $key ] );
		}
		if ( null !== $row && isset( $row->$key ) ) {
			return (string) $row->$key;
		}

		return $fallback;
	}

	/**
	 * @param object $row Field-group row.
	 * @return array<string, mixed>
	 *
	 * @phpstan-param PPOM_Meta_Group_Row $row
	 */
	private function format_group( $row ) {
		$fields = json_decode( isset( $row->the_meta ) ? (string) $row->the_meta : '[]', true );

		// aviary_api_key, productmeta_js and productmeta_style are intentionally not returned.
		return array(
			'id'                    => (int) $row->productmeta_id,
			'title'                 => stripslashes( (string) $row->productmeta_name ),
			'disabled'              => $this->is_row_disabled( $row ),
			'dynamic_price_display' => isset( $row->dynamic_price_display ) ? (string) $row->dynamic_price_display : '',
			'created'               => isset( $row->productmeta_created ) ? (string) $row->productmeta_created : '',
			'fields'                => is_array( $fields ) ? array_values( $fields ) : array(),
		);
	}

	/**
	 * @param object $row Field-group row.
	 * @return bool
	 *
	 * @phpstan-param PPOM_Meta_Group_Row $row
	 */
	private function is_row_disabled( $row ) {
		return isset( $row->productmeta_disabled ) && 'on' === (string) $row->productmeta_disabled;
	}

	/**
	 * Uploaded file names stored on an order item, by field key. Files are referenced, never embedded.
	 *
	 * @param mixed $ppom_fields `_ppom_fields` order item meta.
	 * @return array<int, array{field: string, name: string}>
	 */
	private function extract_uploaded_files( $ppom_fields ) {
		$files = array();
		if ( ! is_array( $ppom_fields ) || empty( $ppom_fields['fields'] ) || ! is_array( $ppom_fields['fields'] ) ) {
			return $files;
		}

		foreach ( $ppom_fields['fields'] as $key => $value ) {
			if ( ! is_array( $value ) ) {
				continue;
			}
			foreach ( $value as $file ) {
				if ( is_array( $file ) && ! empty( $file['org'] ) && is_string( $file['org'] ) ) {
					$files[] = array(
						'field' => (string) $key,
						'name'  => sanitize_file_name( $file['org'] ),
					);
				}
			}
		}

		return $files;
	}

	/**
	 * @param mixed $value Stored or formatted value.
	 * @return string
	 */
	private function to_text( $value ) {
		if ( is_scalar( $value ) ) {
			return trim( wp_strip_all_tags( (string) $value ) );
		}

		$json = wp_json_encode( $value );

		return false === $json ? '' : $json;
	}

	/**
	 * @param string $code    Stable error code.
	 * @param string $message Human readable message.
	 * @return array{code: string, message: string}
	 */
	private function error_item( $code, $message ) {
		return array(
			'code'    => $code,
			'message' => $message,
		);
	}

	/**
	 * @return WP_Error
	 */
	private function group_not_found() {
		return new WP_Error( 'ppom_group_not_found', __( 'Field group not found.', 'woocommerce-product-addon' ), array( 'status' => 404 ) );
	}
}
