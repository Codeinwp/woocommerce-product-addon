<?php
/**
 * Filters PPOM field arrays down to keys exposed by public REST read routes.
 *
 * @package PPOM
 * @subpackage REST
 */

namespace PPOM\Rest;

/**
 * Normalizes PPOM field rows for JSON read responses.
 *
 * @internal
 */
final class RestFieldFormatter {

	/**
	 * Reduces field definitions to the keys exposed by the REST read routes.
	 *
	 * @phpstan-param array<int|string, array<string, mixed>> $ppom_fields
	 * @phpstan-return list<array<string, mixed>>
	 *
	 * @param array $ppom_fields Full PPOM field definitions.
	 *
	 * @return array
	 */
	public function filter_required_keys_only( $ppom_fields ) {

		// @phpstan-var list<array<string, mixed>> $new_ppom_fields
		$new_ppom_fields = array();
		if ( $ppom_fields ) {

			foreach ( $ppom_fields as $field ) {

				$title       = isset( $field['title'] ) ? $field['title'] : '';
				$type        = isset( $field['type'] ) ? $field['type'] : '';
				$data_name   = isset( $field['data_name'] ) ? $field['data_name'] : '';
				$description = isset( $field['description'] ) ? $field['description'] : '';
				$required    = isset( $field['required'] ) ? $field['required'] : '';
				$placeholder = isset( $field['placeholder'] ) ? $field['placeholder'] : '';

				if ( $type == 'imageselect' || $type == 'image' ) {
					$options = isset( $field['images'] ) ? $field['images'] : '';
				} else {
					$options = isset( $field['options'] ) ? $field['options'] : '';
				}

				$new_ppom_fields[] = apply_filters(
					"ppom_rest_field_$type",
					array(
						'title'       => $title,
						'type'        => $type,
						'data_name'   => $data_name,
						'description' => $description,
						'required'    => $required,
						'placeholder' => $placeholder,
						'options'     => $options,
					),
					$field,
					$ppom_fields
				);
			}
		}

		return apply_filters( 'ppom_rest_fields', $new_ppom_fields, $ppom_fields );
	}
}
