<?php
/**
 * Persists field-group data from the React admin modal via the same pipeline as legacy AJAX.
 *
 * @package PPOM
 * @subpackage Admin\FieldModal
 */

namespace PPOM\Admin\FieldModal;

use PPOM\Admin\Manager;
use PPOM\Meta\MetaRepositoryAccessor;
use PPOM\Support\Helpers;
use PPOM\Validation\Validator;

/**
 * Saves field groups from the React modal using legacy sanitization filters.
 *
 * @phpstan-type FieldRow array<string, mixed>
 * @phpstan-type SaveResult array{productmeta_id: int, redirect_to: string, message: string, fields: array<int, FieldRow>}
 *
 * @internal
 */
final class FieldModalPersistence {

	/**
	 * Validates one field row against classic modal requirements.
	 *
	 * @param array<string, mixed> $row Field row payload.
	 * @return array<int, string>
	 */
	private function validate_field_row( array $row ) {
		$errors = array();

		if ( array_key_exists( 'data_name', $row ) && '' === trim( (string) $row['data_name'] ) ) {
			$errors[] = __( 'Data Name must be required', 'woocommerce-product-addon' );
		}

		return $errors;
	}

	/**
	 * Normalizes, validates, and filters fields from REST JSON.
	 *
	 * @param mixed      $fields         Ordered field rows (array of field rows).
	 * @param int|string $productmeta_id Context id (0 for new).
	 * @return array<int, array<string, mixed>>|\WP_Error
	 *
	 * @phpstan-param mixed $fields
	 * @phpstan-return array<int, FieldRow>|\WP_Error
	 */
	public function normalize_fields( $fields, $productmeta_id ) {
		if ( ! is_array( $fields ) ) {
			return new \WP_Error( 'ppom_invalid_fields', __( 'Invalid fields payload.', 'woocommerce-product-addon' ), array( 'status' => 400 ) );
		}

		$out = array();
		foreach ( array_values( $fields ) as $index => $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			// Preserve keys the React modal does not render (e.g. Pro CFR / cond_field_repeater*).
			// Client sends full field rows; unknown keys must survive until Pro filters and sanitize run.
			// Filter: ppom_admin_field_modal_normalize_field — ( field row, index, productmeta_id ).
			$row = apply_filters( 'ppom_admin_field_modal_normalize_field', $row, (int) $index, $productmeta_id );
			if ( ! is_array( $row ) ) {
				continue;
			}
			// Filter: ppom_admin_field_modal_validate_field — ( errors, field row, index, productmeta_id ); return string[].
			$errors = apply_filters( 'ppom_admin_field_modal_validate_field', $this->validate_field_row( $row ), $row, (int) $index, $productmeta_id );
			if ( ! is_array( $errors ) ) {
				$errors = array();
			}
			if ( ! empty( $errors ) ) {
				return new \WP_Error(
					'ppom_field_validation',
					__( 'Field validation failed.', 'woocommerce-product-addon' ),
					array(
						'status' => 400,
						'errors' => $errors,
					)
				);
			}
			$out[] = $row;
		}

		$productmeta_id = is_numeric( $productmeta_id ) ? (string) $productmeta_id : '';
		$out            = apply_filters( 'ppom_meta_data_saving', $out, $productmeta_id );
		$out            = Validator::sanitize_array_data( $out );
		$out            = array_filter(
			$out,
			static function ( $pm ) {
				return ! empty( $pm['type'] ) || ! empty( $pm['data_name'] );
			}
		);

		return array_values( $out );
	}

	/**
	 * Creates a new group (same shape as legacy save_form_meta after insert).
	 *
	 * @param array<string, mixed>             $group  Group settings (name, price display, attachments, etc.).
	 * @param array<int, array<string, mixed>> $fields Field rows.
	 * @return array<string, mixed>|\WP_Error
	 *
	 * @phpstan-param array<string, mixed> $group
	 * @phpstan-param array<int, FieldRow> $fields
	 * @phpstan-return SaveResult|\WP_Error
	 */
	public function create_group( array $group, array $fields ) {
		$db_version = floatval( get_option( 'personalizedproduct_db_version' ) );
		if ( $db_version < 22.1 ) {
			return new \WP_Error(
				'ppom_db_version',
				__( 'Since version 22.0, Database has some changes. Please Deactivate & then activate the PPOM plugin.', 'woocommerce-product-addon' ),
				array( 'status' => 400 )
			);
		}

		$normalized = $this->normalize_fields( $fields, 0 );
		if ( is_wp_error( $normalized ) ) {
			return $normalized;
		}

		$productmeta_name     = isset( $group['productmeta_name'] ) ? sanitize_text_field( (string) $group['productmeta_name'] ) : '';
		$dynamic_price_hide   = isset( $group['dynamic_price_display'] ) ? sanitize_text_field( (string) $group['dynamic_price_display'] ) : '';
		$send_file_attachment = isset( $group['send_file_attachment'] ) ? sanitize_text_field( (string) $group['send_file_attachment'] ) : '';
		$show_cart_thumb      = isset( $group['show_cart_thumb'] ) ? sanitize_text_field( (string) $group['show_cart_thumb'] ) : '';
		$aviary_api_key       = isset( $group['aviary_api_key'] ) ? sanitize_text_field( (string) $group['aviary_api_key'] ) : '';
		$productmeta_style    = isset( $group['productmeta_style'] ) ? sanitize_textarea_field( (string) $group['productmeta_style'] ) : '';
		$productmeta_js       = isset( $group['productmeta_js'] ) ? sanitize_textarea_field( (string) $group['productmeta_js'] ) : '';

		if ( strlen( $productmeta_name ) > 50 ) {
			return new \WP_Error(
				'ppom_title_length',
				__( 'PPOM title is too long to save, please make it less than 50 characters.', 'woocommerce-product-addon' ),
				array( 'status' => 400 )
			);
		}

		$product_meta_json = wp_json_encode( $normalized );
		if ( false === $product_meta_json ) {
			return new \WP_Error( 'ppom_encode', __( 'Could not encode field data.', 'woocommerce-product-addon' ), array( 'status' => 500 ) );
		}

		$ppom_settings_meta_data = array(
			'productmeta_name'      => $productmeta_name,
			'dynamic_price_display' => $dynamic_price_hide,
			'send_file_attachment'  => $send_file_attachment,
			'show_cart_thumb'       => $show_cart_thumb,
			'aviary_api_key'        => trim( $aviary_api_key ),
			'the_meta'              => $product_meta_json,
			'productmeta_created'   => current_time( 'mysql' ),
		);

		if ( ! Helpers::is_legacy_user() ) {
			$ppom_settings_meta_data['productmeta_style'] = $productmeta_style;
			$ppom_settings_meta_data['productmeta_js']    = $productmeta_js;
		}

		$dt     = apply_filters( 'ppom_settings_meta_data_new', $ppom_settings_meta_data );
		$format = array_fill( 0, count( $dt ), '%s' );

		$ppom_id = MetaRepositoryAccessor::instance()->insert_group( $dt, $format );
		if ( $ppom_id <= 0 ) {
			return new \WP_Error( 'ppom_insert_failed', __( 'Could not create field group.', 'woocommerce-product-addon' ), array( 'status' => 500 ) );
		}

		// Second pass: same as legacy — filter with real id and require type + data_name.
		$refined = apply_filters( 'ppom_meta_data_saving', $normalized, (string) $ppom_id );
		$refined = Validator::sanitize_array_data( $refined );
		$refined = array_filter(
			$refined,
			static function ( $pm ) {
				return ! empty( $pm['type'] ) && ! empty( $pm['data_name'] );
			}
		);
		Manager::update_ppom_meta_only( (int) $ppom_id, $refined );

		$redirect_to = add_query_arg(
			array(
				'page'           => 'ppom',
				'productmeta_id' => $ppom_id,
				'do_meta'        => 'edit',
			),
			admin_url( 'admin.php' )
		);

		return array(
			'productmeta_id' => (int) $ppom_id,
			'redirect_to'    => esc_url_raw( $redirect_to ),
			'message'        => __( 'Form added successfully', 'woocommerce-product-addon' ),
			'fields'         => array_values( $refined ),
		);
	}

	/**
	 * Updates an existing group.
	 *
	 * @param int                              $id     Group id.
	 * @param array<string, mixed>             $group  Group settings.
	 * @param array<int, array<string, mixed>> $fields Field rows.
	 * @return array<string, mixed>|\WP_Error
	 *
	 * @phpstan-param array<string, mixed> $group
	 * @phpstan-param array<int, FieldRow> $fields
	 * @phpstan-return SaveResult|\WP_Error
	 */
	public function update_group( int $id, array $group, array $fields ) {
		if ( $id <= 0 ) {
			return new \WP_Error( 'ppom_invalid_id', __( 'Invalid field group id.', 'woocommerce-product-addon' ), array( 'status' => 400 ) );
		}

		$db_version = floatval( get_option( 'personalizedproduct_db_version' ) );
		if ( $db_version < 22.1 ) {
			return new \WP_Error(
				'ppom_db_version',
				__( 'Since version 22.0, Database has some changes. Please Deactivate & then activate the PPOM plugin.', 'woocommerce-product-addon' ),
				array( 'status' => 400 )
			);
		}

		$row = MetaRepositoryAccessor::instance()->get_row_by_id( $id );
		if ( null === $row ) {
			return new \WP_Error( 'ppom_not_found', __( 'Field group not found.', 'woocommerce-product-addon' ), array( 'status' => 404 ) );
		}

		$normalized = $this->normalize_fields( $fields, (string) $id );
		if ( is_wp_error( $normalized ) ) {
			return $normalized;
		}

		$productmeta_name     = isset( $group['productmeta_name'] ) ? sanitize_text_field( (string) $group['productmeta_name'] ) : '';
		$dynamic_price_hide   = isset( $group['dynamic_price_display'] ) ? sanitize_text_field( (string) $group['dynamic_price_display'] ) : '';
		$send_file_attachment = isset( $group['send_file_attachment'] ) ? sanitize_text_field( (string) $group['send_file_attachment'] ) : '';
		$show_cart_thumb      = isset( $group['show_cart_thumb'] ) ? sanitize_text_field( (string) $group['show_cart_thumb'] ) : '';
		$aviary_api_key       = isset( $group['aviary_api_key'] ) ? sanitize_text_field( (string) $group['aviary_api_key'] ) : '';
		$productmeta_style    = isset( $group['productmeta_style'] ) ? sanitize_textarea_field( (string) $group['productmeta_style'] ) : '';
		$productmeta_js       = isset( $group['productmeta_js'] ) ? sanitize_textarea_field( (string) $group['productmeta_js'] ) : '';

		if ( strlen( $productmeta_name ) > 50 ) {
			return new \WP_Error(
				'ppom_title_length',
				__( 'PPOM title is too long to save, please make it less than 50 characters.', 'woocommerce-product-addon' ),
				array( 'status' => 400 )
			);
		}

		$product_meta_json = wp_json_encode( $normalized );
		if ( false === $product_meta_json ) {
			return new \WP_Error( 'ppom_encode', __( 'Could not encode field data.', 'woocommerce-product-addon' ), array( 'status' => 500 ) );
		}

		$ppom_settings_meta_data = array(
			'productmeta_name'      => $productmeta_name,
			'dynamic_price_display' => $dynamic_price_hide,
			'send_file_attachment'  => $send_file_attachment,
			'show_cart_thumb'       => $show_cart_thumb,
			'aviary_api_key'        => trim( $aviary_api_key ),
			'the_meta'              => $product_meta_json,
		);
		if ( ! Helpers::is_legacy_user() ) {
			$ppom_settings_meta_data['productmeta_style'] = $productmeta_style;
			$ppom_settings_meta_data['productmeta_js']    = $productmeta_js;
		}

		$dt = apply_filters( 'ppom_settings_meta_data_update', $ppom_settings_meta_data, (string) $id );

		$where = array( 'productmeta_id' => $id );

		$format       = array_fill( 0, count( $dt ), '%s' );
		$where_format = array( '%d' );

		MetaRepositoryAccessor::instance()->update_group( $id, $dt, $format, $where, $where_format );

		$redirect_to = add_query_arg(
			array(
				'page'           => 'ppom',
				'productmeta_id' => $id,
				'do_meta'        => 'edit',
			),
			admin_url( 'admin.php' )
		);

		return array(
			'productmeta_id' => $id,
			'redirect_to'    => esc_url_raw( $redirect_to ),
			'message'        => __( 'Form updated successfully', 'woocommerce-product-addon' ),
			'fields'         => $normalized,
		);
	}
}
