<?php
/**
 * Plugin Name: PPOM E2E Bootstrap Helpers
 * Description: Adds wp-env-only AJAX helpers for authenticated E2E fixture setup and cleanup.
 *
 * @package woocommerce-product-addon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'PPOM_E2E_BOOTSTRAP_NONCE_ACTION' ) ) {
	define( 'PPOM_E2E_BOOTSTRAP_NONCE_ACTION', 'ppom_e2e_bootstrap' );
}

if ( ! defined( 'PPOM_E2E_FIXTURE_MARKER_META_KEY' ) ) {
	define( 'PPOM_E2E_FIXTURE_MARKER_META_KEY', '_ppom_e2e_fixture' );
}

if ( ! defined( 'PPOM_E2E_META_IDS_OPTION' ) ) {
	define( 'PPOM_E2E_META_IDS_OPTION', 'ppom_e2e_fixture_meta_ids' );
}

if ( ! defined( 'PPOM_E2E_LICENSE_FIXTURE_OPTION' ) ) {
	define( 'PPOM_E2E_LICENSE_FIXTURE_OPTION', 'ppom_e2e_license_fixture' );
}

if ( ! defined( 'PPOM_E2E_LICENSE_FILTER_PRIORITY' ) ) {
	// Late priority so E2E overrides win over in-plugin license hooks.
	define( 'PPOM_E2E_LICENSE_FILTER_PRIORITY', PHP_INT_MAX - 10 );
}

/**
 * Default license fixture: valid Essential plan (wp-env has no store key).
 *
 * @return array{status:string,plan:int}
 */
function ppom_e2e_default_license_fixture() {
	return array(
		'status' => 'valid',
		'plan'   => 1,
	);
}

/**
 * Resolved license fixture for filters and AJAX responses.
 *
 * @return array{status:string,plan:int}
 */
function ppom_e2e_get_license_fixture() {
	$defaults = ppom_e2e_default_license_fixture();
	$stored   = get_option( PPOM_E2E_LICENSE_FIXTURE_OPTION, null );

	if ( ! is_array( $stored ) ) {
		return $defaults;
	}

	$status = isset( $stored['status'] ) && 'invalid' === $stored['status'] ? 'invalid' : 'valid';
	$plan   = isset( $stored['plan'] ) ? max( 1, min( 3, absint( $stored['plan'] ) ) ) : $defaults['plan'];

	return array(
		'status' => $status,
		'plan'   => $plan,
	);
}

/**
 * The attach modal only enables tag selection when the license filter returns valid.
 * wp-env runs the free build without a store key; unlock valid for automated admin UI tests.
 * Use ppom_e2e_set_license_fixture to simulate inactive licenses in E2E.
 */
add_filter(
	'product_ppom_license_status',
	static function ( $value ) {
		$config = ppom_e2e_get_license_fixture();

		if ( 'valid' === $config['status'] ) {
			return 'valid';
		}

		return '';
	},
	PPOM_E2E_LICENSE_FILTER_PRIORITY,
	1
);

add_filter(
	'product_ppom_license_plan',
	static function ( $value ) {
		$config = ppom_e2e_get_license_fixture();

		if ( 'valid' !== $config['status'] ) {
			return 0;
		}

		return (int) $config['plan'];
	},
	PPOM_E2E_LICENSE_FILTER_PRIORITY,
	1
);

/**
 * Ensure the current request is authorized to manage E2E bootstrap data.
 *
 * @return void
 */
function ppom_e2e_require_capability() {
	if ( current_user_can( 'manage_woocommerce' ) || current_user_can( 'manage_options' ) ) {
		return;
	}

	wp_send_json_error(
		array(
			'message' => 'You are not allowed to manage PPOM E2E fixtures.',
		),
		403
	);
}

/**
 * Ensure a valid bootstrap nonce is present on the current AJAX request.
 *
 * @return void
 */
function ppom_e2e_require_nonce() {
	$nonce = isset( $_REQUEST['_ajax_nonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['_ajax_nonce'] ) ) : '';

	if ( ! $nonce || ! wp_verify_nonce( $nonce, PPOM_E2E_BOOTSTRAP_NONCE_ACTION ) ) {
		wp_send_json_error(
			array(
				'message' => 'Invalid or missing PPOM E2E bootstrap nonce.',
			),
			403
		);
	}
}

/**
 * Return a JSON-decoded request value.
 *
 * @param string $key     Request key.
 * @param mixed  $default Default value when the key is missing.
 *
 * @return mixed|WP_Error
 */
function ppom_e2e_decode_json_request( $key, $default = array() ) {
	if ( ! isset( $_POST[ $key ] ) ) {
		return $default;
	}

	$raw_value = wp_unslash( $_POST[ $key ] );

	if ( ! is_string( $raw_value ) ) {
		return new WP_Error(
			'invalid_json',
			sprintf(
				/* translators: %s: request parameter name. */
				__( 'The "%s" payload is not valid JSON.', 'woocommerce-product-addon' ),
				$key
			)
		);
	}

	if ( '' === $raw_value ) {
		return $default;
	}

	$decoded = json_decode( $raw_value, true );

	if ( JSON_ERROR_NONE !== json_last_error() ) {
		return new WP_Error(
			'invalid_json',
			sprintf(
				/* translators: %s: request parameter name. */
				__( 'The "%s" payload is not valid JSON.', 'woocommerce-product-addon' ),
				$key
			)
		);
	}

	return $decoded;
}

/**
 * Send a JSON error response from a WP_Error instance.
 *
 * @param WP_Error $error       Error instance.
 * @param int      $status_code HTTP status code.
 *
 * @return void
 */
function ppom_e2e_send_wp_error( $error, $status_code = 400 ) {
	wp_send_json_error(
		array(
			'code'    => $error->get_error_code(),
			'message' => $error->get_error_message(),
		),
		$status_code
	);
}

/**
 * Track a PPOM group ID created by the E2E bootstrap layer.
 *
 * @param int $meta_id PPOM group ID.
 *
 * @return void
 */
function ppom_e2e_track_meta_id( $meta_id ) {
	$meta_id = absint( $meta_id );

	if ( $meta_id <= 0 ) {
		return;
	}

	$tracked_ids   = get_option( PPOM_E2E_META_IDS_OPTION, array() );
	$tracked_ids   = is_array( $tracked_ids ) ? array_map( 'absint', $tracked_ids ) : array();
	$tracked_ids[] = $meta_id;

	update_option( PPOM_E2E_META_IDS_OPTION, array_values( array_unique( array_filter( $tracked_ids ) ) ), false );
}

/**
 * Return the tracked PPOM group IDs.
 *
 * @return int[]
 */
function ppom_e2e_get_tracked_meta_ids() {
	$tracked_ids = get_option( PPOM_E2E_META_IDS_OPTION, array() );

	return is_array( $tracked_ids )
		? array_values( array_unique( array_filter( array_map( 'absint', $tracked_ids ) ) ) )
		: array();
}

/**
 * Mark a post as fixture-owned data.
 *
 * @param int $post_id Fixture post ID.
 *
 * @return void
 */
function ppom_e2e_mark_fixture_post( $post_id ) {
	$post_id = absint( $post_id );

	if ( $post_id > 0 ) {
		update_post_meta( $post_id, PPOM_E2E_FIXTURE_MARKER_META_KEY, '1' );
	}
}

/**
 * Mark a term as fixture-owned data.
 *
 * @param int $term_id Fixture term ID.
 *
 * @return void
 */
function ppom_e2e_mark_fixture_term( $term_id ) {
	$term_id = absint( $term_id );

	if ( $term_id > 0 ) {
		update_term_meta( $term_id, PPOM_E2E_FIXTURE_MARKER_META_KEY, '1' );
	}
}

/**
 * Recursively delete directory contents while preserving the root directory.
 *
 * @param string $directory Absolute directory path.
 *
 * @return int Number of removed filesystem entries.
 */
function ppom_e2e_cleanup_directory_contents( $directory ) {
	if ( ! $directory || ! is_dir( $directory ) ) {
		return 0;
	}

	$removed_entries = 0;
	$items           = scandir( $directory );

	if ( ! is_array( $items ) ) {
		return 0;
	}

	foreach ( $items as $item ) {
		if ( '.' === $item || '..' === $item ) {
			continue;
		}

		$path = trailingslashit( $directory ) . $item;

		if ( is_dir( $path ) ) {
			$removed_entries += ppom_e2e_cleanup_directory_contents( $path );

			if ( is_dir( $path ) && rmdir( $path ) ) {
				++$removed_entries;
			}

			continue;
		}

		if ( file_exists( $path ) && unlink( $path ) ) {
			++$removed_entries;
		}
	}

	return $removed_entries;
}

/**
 * Normalize a list of category IDs from request data.
 *
 * @param mixed $category_ids Request payload.
 *
 * @return int[]
 */
function ppom_e2e_normalize_category_ids( $category_ids ) {
	return is_array( $category_ids )
		? array_values( array_unique( array_filter( array_map( 'absint', $category_ids ) ) ) )
		: array();
}

/**
 * Convert fixture attribute definitions into WooCommerce product attributes.
 *
 * @param array $attribute_definitions Attribute payload.
 *
 * @return array
 */
function ppom_e2e_build_product_attributes( $attribute_definitions ) {
	if ( ! is_array( $attribute_definitions ) ) {
		return array();
	}

	$product_attributes = array();

	foreach ( $attribute_definitions as $index => $attribute_definition ) {
		if ( ! is_array( $attribute_definition ) || empty( $attribute_definition['name'] ) ) {
			continue;
		}

		$options = isset( $attribute_definition['options'] ) && is_array( $attribute_definition['options'] )
			? array_values(
				array_filter(
					array_map( 'sanitize_text_field', $attribute_definition['options'] ),
					static function ( $option ) {
						return '' !== $option;
					}
				)
			)
			: array();

		if ( empty( $options ) ) {
			continue;
		}

		$product_attribute = new WC_Product_Attribute();
		$product_attribute->set_id( 0 );
		$product_attribute->set_name( sanitize_text_field( $attribute_definition['name'] ) );
		$product_attribute->set_options( $options );
		$product_attribute->set_position( (int) $index );
		$product_attribute->set_visible( ! isset( $attribute_definition['visible'] ) || (bool) $attribute_definition['visible'] );
		$product_attribute->set_variation( ! isset( $attribute_definition['variation'] ) || (bool) $attribute_definition['variation'] );

		$product_attributes[] = $product_attribute;
	}

	return $product_attributes;
}

/**
 * Normalize variation attribute input into WooCommerce format.
 *
 * @param mixed $attributes Variation attribute payload.
 *
 * @return array
 */
function ppom_e2e_normalize_variation_attributes( $attributes ) {
	if ( ! is_array( $attributes ) ) {
		return array();
	}

	$normalized_attributes = array();

	foreach ( $attributes as $attribute_name => $attribute_value ) {
		if ( ! is_scalar( $attribute_name ) || ! is_scalar( $attribute_value ) ) {
			continue;
		}

		$normalized_name  = sanitize_title( (string) $attribute_name );
		$normalized_value = sanitize_text_field( (string) $attribute_value );

		if ( '' === $normalized_name || '' === $normalized_value ) {
			continue;
		}

		$normalized_attributes[ $normalized_name ] = $normalized_value;
	}

	return $normalized_attributes;
}

/**
 * Prepare a PPOM field payload for persistence.
 *
 * @param mixed      $ppom_fields     Submitted PPOM fields.
 * @param int|string $productmeta_id  Existing PPOM group ID for filters.
 *
 * @return array|WP_Error
 */
function ppom_e2e_prepare_form_meta_fields( $ppom_fields, $productmeta_id = '' ) {
	if ( ! is_array( $ppom_fields ) || empty( $ppom_fields ) ) {
		return new WP_Error(
			'no_fields',
			__( 'No fields found.', 'woocommerce-product-addon' )
		);
	}

	$product_meta = apply_filters( 'ppom_meta_data_saving', $ppom_fields, $productmeta_id );
	$product_meta = ppom_sanitize_array_data( $product_meta );

	$serialized_meta = array_values(
		array_filter(
			$product_meta,
			static function ( $field ) {
				return ! empty( $field['type'] ) || ! empty( $field['data_name'] );
			}
		)
	);

	if ( empty( $serialized_meta ) ) {
		return new WP_Error(
			'no_fields',
			__( 'No fields found.', 'woocommerce-product-addon' )
		);
	}

	$final_meta = array_values(
		array_filter(
			$product_meta,
			static function ( $field ) {
				return ! empty( $field['type'] ) && ! empty( $field['data_name'] );
			}
		)
	);

	return array(
		'serialized' => wp_json_encode( $serialized_meta ),
		'final'      => $final_meta,
	);
}

/**
 * Insert a PPOM field group via the E2E bootstrap layer.
 *
 * @param array $args Group settings and field payload.
 *
 * @return array|WP_Error
 */
function ppom_e2e_insert_ppom_group( $args ) {
	$db_version = (float) get_option( 'personalizedproduct_db_version' );

	if ( $db_version < 22.1 ) {
		return new WP_Error(
			'db_version_outdated',
			__( 'Since version 22.0, Database has some changes. Please Deactivate & then activate the PPOM plugin.', 'woocommerce-product-addon' )
		);
	}

	$ppom_fields = isset( $args['fields'] ) && is_array( $args['fields'] )
		? $args['fields']
		: array();

	$prepared_fields = ppom_e2e_prepare_form_meta_fields( $ppom_fields );

	if ( is_wp_error( $prepared_fields ) ) {
		return $prepared_fields;
	}

	$productmeta_name = isset( $args['group_name'] )
		? sanitize_text_field( wp_unslash( $args['group_name'] ) )
		: '';
	$product_id       = isset( $args['product_id'] ) ? absint( $args['product_id'] ) : 0;
	$settings         = isset( $args['settings'] ) && is_array( $args['settings'] )
		? $args['settings']
		: array();

	if ( strlen( $productmeta_name ) > 50 ) {
		return new WP_Error(
			'group_name_too_long',
			__( 'PPOM title is too long to save, please make it less than 50 characters.', 'woocommerce-product-addon' )
		);
	}

	$ppom_settings_meta_data = array(
		'productmeta_name'      => $productmeta_name,
		'dynamic_price_display' => isset( $settings['dynamic_price_hide'] ) ? sanitize_text_field( wp_unslash( $settings['dynamic_price_hide'] ) ) : 'no',
		'send_file_attachment'  => isset( $settings['send_file_attachment'] ) ? sanitize_text_field( wp_unslash( $settings['send_file_attachment'] ) ) : '',
		'show_cart_thumb'       => isset( $settings['show_cart_thumb'] ) ? sanitize_text_field( wp_unslash( $settings['show_cart_thumb'] ) ) : 'no',
		'aviary_api_key'        => isset( $settings['aviary_api_key'] ) ? sanitize_text_field( wp_unslash( $settings['aviary_api_key'] ) ) : '',
		'the_meta'              => $prepared_fields['serialized'],
		'productmeta_created'   => current_time( 'mysql' ),
	);

	if ( ! ppom_is_legacy_user() ) {
		$ppom_settings_meta_data['productmeta_style'] = isset( $settings['productmeta_style'] ) ? sanitize_text_field( wp_unslash( $settings['productmeta_style'] ) ) : '';
		$ppom_settings_meta_data['productmeta_js']    = isset( $settings['productmeta_js'] ) ? sanitize_text_field( wp_unslash( $settings['productmeta_js'] ) ) : '';
	}

	$dt = apply_filters( 'ppom_settings_meta_data_new', $ppom_settings_meta_data );

	global $wpdb;
	$ppom_table = $wpdb->prefix . PPOM_TABLE_META;
	$inserted   = $wpdb->insert(
		$ppom_table,
		$dt,
		array_fill( 0, count( $dt ), '%s' )
	);

	if ( false === $inserted ) {
		return new WP_Error(
			'meta_insert_failed',
			__( 'PPOM group could not be saved.', 'woocommerce-product-addon' )
		);
	}

	$ppom_id      = (int) $wpdb->insert_id;
	$final_fields = ppom_e2e_prepare_form_meta_fields( $ppom_fields, $ppom_id );

	if ( is_wp_error( $final_fields ) ) {
		return $final_fields;
	}

	ppom_admin_update_ppom_meta_only( $ppom_id, $final_fields['final'] );

	if ( $product_id > 0 ) {
		ppom_attach_fields_to_product( $ppom_id, $product_id );
	}

	return array(
		'productmeta_id' => $ppom_id,
	);
}

/**
 * Attach a PPOM group to products and taxonomy targets via the bootstrap layer.
 *
 * @param array $args Attachment payload.
 *
 * @return array|WP_Error
 */
function ppom_e2e_attach_group( $args ) {
	$ppom_id = isset( $args['ppom_id'] ) ? absint( $args['ppom_id'] ) : 0;

	if ( $ppom_id <= 0 ) {
		return new WP_Error(
			'invalid_ppom_id',
			__( 'A valid PPOM group is required.', 'woocommerce-product-addon' )
		);
	}

	$is_pro_user               = 'valid' === apply_filters( 'product_ppom_license_status', '' );
	$products_to_attach        = isset( $args['product_ids'] ) && is_array( $args['product_ids'] ) ? array_values( array_unique( array_filter( array_map( 'absint', $args['product_ids'] ) ) ) ) : array();
	$products_to_attach_initial = isset( $args['product_ids_initial'] ) && is_array( $args['product_ids_initial'] ) ? array_values( array_unique( array_filter( array_map( 'absint', $args['product_ids_initial'] ) ) ) ) : array();

	$products_to_add = array_diff( $products_to_attach, $products_to_attach_initial );
	foreach ( $products_to_add as $product_to_add ) {
		if ( $is_pro_user ) {
			$current_attached_fields = get_post_meta( $product_to_add, PPOM_PRODUCT_META_KEY, true );

			if ( is_array( $current_attached_fields ) ) {
				$current_attached_fields[] = $ppom_id;
				$current_attached_fields   = array_unique( $current_attached_fields );
			} elseif ( is_numeric( $current_attached_fields ) ) {
				$current_attached_fields = array( $current_attached_fields, $ppom_id );
			} else {
				$current_attached_fields = array( $ppom_id );
			}

			$current_attached_fields = array_filter( $current_attached_fields, 'is_numeric' );
			update_post_meta( $product_to_add, PPOM_PRODUCT_META_KEY, $current_attached_fields );
		} else {
			update_post_meta( $product_to_add, PPOM_PRODUCT_META_KEY, array( $ppom_id ) );
		}
	}

	$products_to_remove = array_diff( $products_to_attach_initial, $products_to_attach );
	foreach ( $products_to_remove as $product_to_remove ) {
		$should_delete           = true;
		$current_attached_fields = get_post_meta( $product_to_remove, PPOM_PRODUCT_META_KEY, true );

		if ( is_array( $current_attached_fields ) ) {
			$key = array_search( $ppom_id, $current_attached_fields );

			if ( false !== $key ) {
				unset( $current_attached_fields[ $key ] );

				if ( ! empty( $current_attached_fields ) ) {
					$should_delete = false;
					update_post_meta( $product_to_remove, PPOM_PRODUCT_META_KEY, $current_attached_fields );
				}
			}
		}

		if ( $should_delete ) {
			delete_post_meta( $product_to_remove, PPOM_PRODUCT_META_KEY );
		}
	}

	$category_slugs = isset( $args['category_slugs'] ) && is_array( $args['category_slugs'] )
		? array_values( array_unique( array_filter( array_map( 'sanitize_title', $args['category_slugs'] ) ) ) )
		: array();
	$tag_slugs      = isset( $args['tag_slugs'] ) && is_array( $args['tag_slugs'] )
		? array_values( array_unique( array_filter( array_map( 'sanitize_title', $args['tag_slugs'] ) ) ) )
		: false;

	NM_PersonalizedProduct_Admin::save_categories_and_tags( $ppom_id, $category_slugs, $tag_slugs );

	return array(
		'updated_products'   => count( $products_to_add ) + count( $products_to_remove ),
		'updated_categories' => count( $category_slugs ),
		'updated_tags'       => is_array( $tag_slugs ) ? count( $tag_slugs ) : 0,
	);
}

/**
 * Return a nonce for authenticated E2E bootstrap requests.
 *
 * @return void
 */
function ppom_e2e_get_nonce() {
	ppom_e2e_require_capability();

	wp_send_json_success(
		array(
			'nonce' => wp_create_nonce( PPOM_E2E_BOOTSTRAP_NONCE_ACTION ),
		)
	);
}
add_action( 'wp_ajax_ppom_e2e_get_nonce', 'ppom_e2e_get_nonce' );
add_action( 'wp_ajax_nopriv_ppom_e2e_get_nonce', 'ppom_e2e_get_nonce' );

/**
 * Create a WooCommerce product category for fixtures.
 *
 * @return void
 */
function ppom_e2e_create_product_category() {
	ppom_e2e_require_capability();
	ppom_e2e_require_nonce();

	$name = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
	$slug = isset( $_POST['slug'] ) ? sanitize_title( wp_unslash( $_POST['slug'] ) ) : '';

	if ( '' === $name ) {
		wp_send_json_error(
			array(
				'message' => 'Category name is required.',
			),
			400
		);
	}

	$term = wp_insert_term(
		$name,
		'product_cat',
		array(
			'slug' => $slug,
		)
	);

	if ( is_wp_error( $term ) ) {
		ppom_e2e_send_wp_error( $term );
	}

	ppom_e2e_mark_fixture_term( $term['term_id'] );

	$created_term = get_term( $term['term_id'], 'product_cat' );

	wp_send_json_success(
		array(
			'id'   => (int) $created_term->term_id,
			'name' => $created_term->name,
			'slug' => $created_term->slug,
		)
	);
}
add_action( 'wp_ajax_ppom_e2e_create_product_category', 'ppom_e2e_create_product_category' );
add_action( 'wp_ajax_nopriv_ppom_e2e_create_product_category', 'ppom_e2e_create_product_category' );

/**
 * Create a WooCommerce product tag for fixtures.
 *
 * @return void
 */
function ppom_e2e_create_product_tag() {
	ppom_e2e_require_capability();
	ppom_e2e_require_nonce();

	$name = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
	$slug = isset( $_POST['slug'] ) ? sanitize_title( wp_unslash( $_POST['slug'] ) ) : '';

	if ( '' === $name ) {
		wp_send_json_error(
			array(
				'message' => 'Tag name is required.',
			),
			400
		);
	}

	$term = wp_insert_term(
		$name,
		'product_tag',
		array(
			'slug' => $slug,
		)
	);

	if ( is_wp_error( $term ) ) {
		ppom_e2e_send_wp_error( $term );
	}

	ppom_e2e_mark_fixture_term( $term['term_id'] );

	$created_term = get_term( $term['term_id'], 'product_tag' );

	wp_send_json_success(
		array(
			'id'   => (int) $created_term->term_id,
			'name' => $created_term->name,
			'slug' => $created_term->slug,
		)
	);
}
add_action( 'wp_ajax_ppom_e2e_create_product_tag', 'ppom_e2e_create_product_tag' );
add_action( 'wp_ajax_nopriv_ppom_e2e_create_product_tag', 'ppom_e2e_create_product_tag' );

/**
 * Read category/tag attachment columns for a PPOM group (E2E assertions).
 *
 * @return void
 */
function ppom_e2e_get_ppom_attach_row() {
	ppom_e2e_require_capability();
	ppom_e2e_require_nonce();

	$ppom_id = isset( $_POST['ppom_id'] ) ? absint( wp_unslash( $_POST['ppom_id'] ) ) : 0;

	if ( $ppom_id <= 0 ) {
		wp_send_json_error(
			array(
				'message' => 'A valid ppom_id is required.',
			),
			400
		);
	}

	if ( ! defined( 'PPOM_TABLE_META' ) ) {
		wp_send_json_error(
			array(
				'message' => 'PPOM meta table constant is unavailable.',
			),
			500
		);
	}

	global $wpdb;

	$table = $wpdb->prefix . PPOM_TABLE_META;
	$row   = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT productmeta_categories, productmeta_tags FROM {$table} WHERE productmeta_id = %d",
			$ppom_id
		),
		ARRAY_A
	);

	if ( empty( $row ) || ! is_array( $row ) ) {
		wp_send_json_error(
			array(
				'message' => 'PPOM row not found.',
			),
			404
		);
	}

	wp_send_json_success( $row );
}
add_action( 'wp_ajax_ppom_e2e_get_ppom_attach_row', 'ppom_e2e_get_ppom_attach_row' );
add_action( 'wp_ajax_nopriv_ppom_e2e_get_ppom_attach_row', 'ppom_e2e_get_ppom_attach_row' );

/**
 * Create a WooCommerce simple product for fixtures.
 *
 * @return void
 */
function ppom_e2e_create_simple_product() {
	ppom_e2e_require_capability();
	ppom_e2e_require_nonce();

	if ( ! class_exists( 'WC_Product_Simple' ) ) {
		wp_send_json_error(
			array(
				'message' => 'WooCommerce simple product support is unavailable.',
			),
			500
		);
	}

	$category_ids = ppom_e2e_decode_json_request( 'category_ids', array() );

	if ( is_wp_error( $category_ids ) ) {
		ppom_e2e_send_wp_error( $category_ids );
	}

	$name          = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
	$status        = isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : 'publish';
	$regular_price = isset( $_POST['regular_price'] ) ? wc_format_decimal( wp_unslash( $_POST['regular_price'] ) ) : '9.99';

	if ( '' === $name ) {
		wp_send_json_error(
			array(
				'message' => 'Product name is required.',
			),
			400
		);
	}

	if ( ! in_array( $status, array( 'draft', 'pending', 'private', 'publish' ), true ) ) {
		$status = 'publish';
	}

	$product = new WC_Product_Simple();
	$product->set_name( $name );
	$product->set_status( $status );
	$product->set_regular_price( $regular_price );
	$product->set_price( $regular_price );
	$product->set_category_ids( ppom_e2e_normalize_category_ids( $category_ids ) );

	$product_id = $product->save();

	if ( ! $product_id ) {
		wp_send_json_error(
			array(
				'message' => 'WooCommerce product could not be saved.',
			),
			500
		);
	}

	ppom_e2e_mark_fixture_post( $product_id );

	wp_send_json_success(
		array(
			'id'            => (int) $product_id,
			'name'          => $product->get_name(),
			'status'        => $product->get_status(),
			'type'          => $product->get_type(),
			'regular_price' => $product->get_regular_price(),
		)
	);
}
add_action( 'wp_ajax_ppom_e2e_create_simple_product', 'ppom_e2e_create_simple_product' );
add_action( 'wp_ajax_nopriv_ppom_e2e_create_simple_product', 'ppom_e2e_create_simple_product' );

/**
 * Create a WooCommerce variable product for fixtures.
 *
 * @return void
 */
function ppom_e2e_create_variable_product() {
	ppom_e2e_require_capability();
	ppom_e2e_require_nonce();

	if ( ! class_exists( 'WC_Product_Variable' ) ) {
		wp_send_json_error(
			array(
				'message' => 'WooCommerce variable product support is unavailable.',
			),
			500
		);
	}

	$category_ids       = ppom_e2e_decode_json_request( 'category_ids', array() );
	$attributes         = ppom_e2e_decode_json_request( 'attributes', array() );
	$default_attributes = ppom_e2e_decode_json_request( 'default_attributes', array() );

	if ( is_wp_error( $category_ids ) ) {
		ppom_e2e_send_wp_error( $category_ids );
	}

	if ( is_wp_error( $attributes ) ) {
		ppom_e2e_send_wp_error( $attributes );
	}

	if ( is_wp_error( $default_attributes ) ) {
		ppom_e2e_send_wp_error( $default_attributes );
	}

	$name   = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
	$status = isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : 'publish';

	if ( '' === $name ) {
		wp_send_json_error(
			array(
				'message' => 'Variable product name is required.',
			),
			400
		);
	}

	if ( ! in_array( $status, array( 'draft', 'pending', 'private', 'publish' ), true ) ) {
		$status = 'publish';
	}

	$product = new WC_Product_Variable();
	$product->set_name( $name );
	$product->set_status( $status );
	$product->set_category_ids( ppom_e2e_normalize_category_ids( $category_ids ) );
	$product->set_attributes( ppom_e2e_build_product_attributes( $attributes ) );
	$product->set_default_attributes( ppom_e2e_normalize_variation_attributes( $default_attributes ) );

	$product_id = $product->save();

	if ( ! $product_id ) {
		wp_send_json_error(
			array(
				'message' => 'WooCommerce variable product could not be saved.',
			),
			500
		);
	}

	ppom_e2e_mark_fixture_post( $product_id );

	wp_send_json_success(
		array(
			'id'     => (int) $product_id,
			'name'   => $product->get_name(),
			'status' => $product->get_status(),
			'type'   => $product->get_type(),
		)
	);
}
add_action( 'wp_ajax_ppom_e2e_create_variable_product', 'ppom_e2e_create_variable_product' );
add_action( 'wp_ajax_nopriv_ppom_e2e_create_variable_product', 'ppom_e2e_create_variable_product' );

/**
 * Create a WooCommerce variation for a variable product fixture.
 *
 * @return void
 */
function ppom_e2e_create_product_variation() {
	ppom_e2e_require_capability();
	ppom_e2e_require_nonce();

	if ( ! class_exists( 'WC_Product_Variation' ) ) {
		wp_send_json_error(
			array(
				'message' => 'WooCommerce product variation support is unavailable.',
			),
			500
		);
	}

	$attributes = ppom_e2e_decode_json_request( 'attributes', array() );

	if ( is_wp_error( $attributes ) ) {
		ppom_e2e_send_wp_error( $attributes );
	}

	$product_id     = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
	$parent_product = $product_id ? wc_get_product( $product_id ) : false;

	if ( ! $parent_product || ! $parent_product->is_type( 'variable' ) ) {
		wp_send_json_error(
			array(
				'message' => 'A valid variable parent product is required.',
			),
			400
		);
	}

	$status        = isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : 'publish';
	$regular_price = isset( $_POST['regular_price'] ) ? wc_format_decimal( wp_unslash( $_POST['regular_price'] ) ) : '12.99';

	if ( ! in_array( $status, array( 'draft', 'pending', 'private', 'publish' ), true ) ) {
		$status = 'publish';
	}

	$variation = new WC_Product_Variation();
	$variation->set_parent_id( $product_id );
	$variation->set_status( $status );
	$variation->set_regular_price( $regular_price );
	$variation->set_price( $regular_price );
	$variation->set_attributes( ppom_e2e_normalize_variation_attributes( $attributes ) );

	$variation_id = $variation->save();

	if ( ! $variation_id ) {
		wp_send_json_error(
			array(
				'message' => 'WooCommerce variation could not be saved.',
			),
			500
		);
	}

	ppom_e2e_mark_fixture_post( $variation_id );

	wp_send_json_success(
		array(
			'id'            => (int) $variation_id,
			'parent_id'     => $product_id,
			'status'        => $variation->get_status(),
			'regular_price' => $variation->get_regular_price(),
			'attributes'    => $variation->get_attributes(),
		)
	);
}
add_action( 'wp_ajax_ppom_e2e_create_product_variation', 'ppom_e2e_create_product_variation' );
add_action( 'wp_ajax_nopriv_ppom_e2e_create_product_variation', 'ppom_e2e_create_product_variation' );

/**
 * Create a PPOM field group for fixtures.
 *
 * @return void
 */
function ppom_e2e_create_ppom_group() {
	ppom_e2e_require_capability();
	ppom_e2e_require_nonce();

	$fields   = ppom_e2e_decode_json_request( 'fields', array() );
	$settings = ppom_e2e_decode_json_request( 'settings', array() );

	if ( is_wp_error( $fields ) ) {
		ppom_e2e_send_wp_error( $fields );
	}

	if ( is_wp_error( $settings ) ) {
		ppom_e2e_send_wp_error( $settings );
	}

	$group_name = isset( $_POST['group_name'] ) ? sanitize_text_field( wp_unslash( $_POST['group_name'] ) ) : '';
	$product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;

	$group_result = ppom_e2e_insert_ppom_group(
		array(
			'group_name'          => $group_name,
			'product_id'          => $product_id,
			'fields'              => is_array( $fields ) ? $fields : array(),
			'settings'            => is_array( $settings ) ? $settings : array(),
		)
	);

	if ( is_wp_error( $group_result ) ) {
		ppom_e2e_send_wp_error( $group_result );
	}

	ppom_e2e_track_meta_id( $group_result['productmeta_id'] );

	wp_send_json_success(
		array(
			'ppom_id'        => (int) $group_result['productmeta_id'],
			'productmeta_id' => (int) $group_result['productmeta_id'],
		)
	);
}
add_action( 'wp_ajax_ppom_e2e_create_ppom_group', 'ppom_e2e_create_ppom_group' );
add_action( 'wp_ajax_nopriv_ppom_e2e_create_ppom_group', 'ppom_e2e_create_ppom_group' );

/**
 * Attach a PPOM field group to products or categories.
 *
 * @return void
 */
function ppom_e2e_attach_ppom_group() {
	ppom_e2e_require_capability();
	ppom_e2e_require_nonce();

	$product_ids         = ppom_e2e_decode_json_request( 'product_ids', array() );
	$product_ids_initial = ppom_e2e_decode_json_request( 'product_ids_initial', array() );
	$category_slugs      = ppom_e2e_decode_json_request( 'category_slugs', array() );
	$tag_slugs           = ppom_e2e_decode_json_request( 'tag_slugs', array() );

	foreach ( array( $product_ids, $product_ids_initial, $category_slugs, $tag_slugs ) as $decoded_payload ) {
		if ( is_wp_error( $decoded_payload ) ) {
			ppom_e2e_send_wp_error( $decoded_payload );
		}
	}

	$attachment_result = ppom_e2e_attach_group(
		array(
			'ppom_id'             => isset( $_POST['ppom_id'] ) ? $_POST['ppom_id'] : 0,
			'product_ids'         => is_array( $product_ids ) ? $product_ids : array(),
			'product_ids_initial' => is_array( $product_ids_initial ) ? $product_ids_initial : array(),
			'category_slugs'      => is_array( $category_slugs ) ? $category_slugs : array(),
			'tag_slugs'           => is_array( $tag_slugs ) ? $tag_slugs : array(),
		)
	);

	if ( is_wp_error( $attachment_result ) ) {
		ppom_e2e_send_wp_error( $attachment_result );
	}

	wp_send_json_success( $attachment_result );
}
add_action( 'wp_ajax_ppom_e2e_attach_ppom_group', 'ppom_e2e_attach_ppom_group' );
add_action( 'wp_ajax_nopriv_ppom_e2e_attach_ppom_group', 'ppom_e2e_attach_ppom_group' );

/**
 * Set PPOM license fixture for E2E (drives product_ppom_license_* filters).
 *
 * @return void
 */
function ppom_e2e_set_license_fixture() {
	ppom_e2e_require_capability();
	ppom_e2e_require_nonce();

	$status_raw = isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : '';
	$plan_raw   = isset( $_POST['plan'] ) ? absint( wp_unslash( $_POST['plan'] ) ) : 0;

	$status = ( 'invalid' === $status_raw ) ? 'invalid' : 'valid';
	$plan   = max( 1, min( 3, $plan_raw > 0 ? $plan_raw : 1 ) );

	$stored = array(
		'status' => $status,
		'plan'   => $plan,
	);

	update_option( PPOM_E2E_LICENSE_FIXTURE_OPTION, $stored, false );

	wp_send_json_success( ppom_e2e_get_license_fixture() );
}
add_action( 'wp_ajax_ppom_e2e_set_license_fixture', 'ppom_e2e_set_license_fixture' );
add_action( 'wp_ajax_nopriv_ppom_e2e_set_license_fixture', 'ppom_e2e_set_license_fixture' );

/**
 * Read the current PPOM license fixture (for E2E assertions).
 *
 * @return void
 */
function ppom_e2e_read_license_fixture() {
	ppom_e2e_require_capability();
	ppom_e2e_require_nonce();

	wp_send_json_success( ppom_e2e_get_license_fixture() );
}
add_action( 'wp_ajax_ppom_e2e_read_license_fixture', 'ppom_e2e_read_license_fixture' );
add_action( 'wp_ajax_nopriv_ppom_e2e_read_license_fixture', 'ppom_e2e_read_license_fixture' );

/**
 * Reset PPOM E2E fixture state.
 *
 * @return void
 */
function ppom_e2e_reset_state() {
	ppom_e2e_require_capability();
	ppom_e2e_require_nonce();

	$deleted_meta_rows = 0;
	$tracked_meta_ids  = ppom_e2e_get_tracked_meta_ids();

	if ( ! empty( $tracked_meta_ids ) && defined( 'PPOM_TABLE_META' ) ) {
		global $wpdb;

		$ppom_table     = $wpdb->prefix . PPOM_TABLE_META;
		$placeholders   = implode( ',', array_fill( 0, count( $tracked_meta_ids ), '%d' ) );
		$deleted_result = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$ppom_table} WHERE productmeta_id IN ({$placeholders})",
				$tracked_meta_ids
			)
		);

		if ( false !== $deleted_result ) {
			$deleted_meta_rows = (int) $deleted_result;
		}
	}

	delete_option( PPOM_E2E_META_IDS_OPTION );
	delete_option( PPOM_E2E_LICENSE_FIXTURE_OPTION );

	if ( defined( 'PPOM_PRODUCT_META_KEY' ) ) {
		delete_post_meta_by_key( PPOM_PRODUCT_META_KEY );
	}

	$fixture_post_ids = get_posts(
		array(
			'post_type'      => array( 'product', 'product_variation' ),
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_key'       => PPOM_E2E_FIXTURE_MARKER_META_KEY,
			'meta_value'     => '1',
		)
	);

	$deleted_posts = 0;
	foreach ( $fixture_post_ids as $fixture_post_id ) {
		if ( wp_delete_post( $fixture_post_id, true ) ) {
			++$deleted_posts;
		}
	}

	$deleted_terms = 0;

	foreach ( array( 'product_cat', 'product_tag' ) as $fixture_taxonomy ) {
		$fixture_term_ids = get_terms(
			array(
				'taxonomy'   => $fixture_taxonomy,
				'hide_empty' => false,
				'fields'     => 'ids',
				'meta_query' => array(
					array(
						'key'   => PPOM_E2E_FIXTURE_MARKER_META_KEY,
						'value' => '1',
					),
				),
			)
		);

		if ( is_wp_error( $fixture_term_ids ) ) {
			continue;
		}

		foreach ( $fixture_term_ids as $fixture_term_id ) {
			$deleted_term = wp_delete_term( $fixture_term_id, $fixture_taxonomy );

			if ( ! is_wp_error( $deleted_term ) && $deleted_term ) {
				++$deleted_terms;
			}
		}
	}

	$deleted_upload_entries = 0;
	if ( function_exists( 'ppom_get_dir_path' ) ) {
		$deleted_upload_entries = ppom_e2e_cleanup_directory_contents( ppom_get_dir_path() );
	}

	wp_send_json_success(
		array(
			'deleted_meta_rows'      => $deleted_meta_rows,
			'deleted_posts'          => $deleted_posts,
			'deleted_terms'          => $deleted_terms,
			'deleted_upload_entries' => $deleted_upload_entries,
		)
	);
}
add_action( 'wp_ajax_ppom_e2e_reset_state', 'ppom_e2e_reset_state' );
add_action( 'wp_ajax_nopriv_ppom_e2e_reset_state', 'ppom_e2e_reset_state' );
