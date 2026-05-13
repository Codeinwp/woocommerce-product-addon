<?php
/**
 * Builds catalog and per-type schema for the React field modal (admin).
 *
 * @package PPOM
 * @subpackage Admin\FieldModal
 */

namespace PPOM\Admin\FieldModal;

use PPOM_Fields_Meta;

/**
 * Produces JSON-safe catalog and per-type setting schemas for the React UI.
 *
 * @phpstan-type CatalogItem array{slug: string, title: string, desc: string}
 *
 * @internal
 */
final class FieldModalSchemaBuilder {

	/**
	 * Grouped catalog for React picker (same source as legacy modal).
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function get_catalog_groups_for_rest() {
		if ( ! function_exists( 'ppom_get_admin_field_type_groups' ) || ! function_exists( 'ppom_get_admin_field_modal_license_context' ) ) {
			return array();
		}

		$ctx    = ppom_get_admin_field_modal_license_context();
		$groups = ppom_get_admin_field_type_groups();
		$out    = array();

		foreach ( $groups as $group_id => $group ) {
			$fields_out = array();
			foreach ( $group['fields'] as $field ) {
				$min_plan     = isset( $field['plan'] ) ? (int) $field['plan'] : null;
				$locked       = null !== $min_plan && $ctx['plan_category'] < $min_plan;
				$fields_out[] = array(
					'slug'        => isset( $field['slug'] ) ? (string) $field['slug'] : '',
					'title'       => isset( $field['title'] ) ? (string) $field['title'] : '',
					'description' => isset( $field['description'] ) ? (string) $field['description'] : '',
					'icon'        => isset( $field['icon'] ) ? (string) $field['icon'] : '',
					'locked'      => $locked,
					'min_plan'    => $min_plan,
				);
			}
			$out[] = array(
				'id'     => (string) $group_id,
				'label'  => (string) $group['label'],
				'fields' => $fields_out,
			);
		}

		// Filter: ppom_admin_field_modal_catalog_groups — ( grouped rows ); return list of groups.
		$filtered = apply_filters( 'ppom_admin_field_modal_catalog_groups', $out );

		return is_array( $filtered ) ? array_values( $filtered ) : array();
	}

	/**
	 * Flat catalog for the picker (legacy filter + backward compatibility).
	 *
	 * @return array<int, array<string, mixed>>
	 *
	 * @phpstan-return list<array<string, mixed>>
	 */
	public function get_catalog() {
		$catalog = array();
		foreach ( $this->get_catalog_groups_for_rest() as $group ) {
			if ( empty( $group['fields'] ) ) {
				continue;
			}
			foreach ( $group['fields'] as $field ) {
				$catalog[] = array(
					'slug'     => $field['slug'],
					'title'    => $field['title'],
					'desc'     => isset( $field['description'] ) ? $field['description'] : '',
					'icon'     => isset( $field['icon'] ) ? $field['icon'] : '',
					'locked'   => ! empty( $field['locked'] ),
					'min_plan' => isset( $field['min_plan'] ) ? $field['min_plan'] : null,
				);
			}
		}

		$filtered = apply_filters( 'ppom_admin_field_modal_catalog', $catalog );

		return is_array( $filtered ) ? array_values( $filtered ) : array();
	}

	/**
	 * Settings + tabs schema for one input type.
	 *
	 * @param string $type Field type slug.
	 * @return array<string, mixed> Shape includes type, settings, tabs; filter may alter keys.
	 *
	 * @phpstan-return array<string, mixed>
	 */
	public function get_schema_for_type( $type ) {
		$type = sanitize_key( $type );
		$base = array(
			'type'     => $type,
			'settings' => array(),
			'tabs'     => array(),
		);

		if ( ! function_exists( 'PPOM' ) || ! isset( PPOM()->inputs[ $type ] ) ) {
			return (array) apply_filters( 'ppom_admin_field_modal_schema', $base, $type );
		}

		$meta     = PPOM()->inputs[ $type ];
		$settings = isset( $meta->settings ) && is_array( $meta->settings ) ? $meta->settings : array();

		$fm   = PPOM_Fields_Meta::get_instance();
		$tabs = $fm->ppom_fields_tabs( $type );

		$base['settings'] = $this->json_safe_settings( $settings );
		$base['tabs']     = $this->json_safe_tabs( $tabs );

		return (array) apply_filters( 'ppom_admin_field_modal_schema', $base, $type );
	}

	/**
	 * All type schemas keyed by slug (for boot payload; can be large).
	 *
	 * @return array<string, array<string, mixed>>
	 *
	 * @phpstan-return array<string, array<string, mixed>>
	 */
	public function get_all_type_schemas() {
		$out = array();
		foreach ( $this->get_catalog() as $item ) {
			if ( empty( $item['slug'] ) ) {
				continue;
			}
			$slug         = (string) $item['slug'];
			$out[ $slug ] = $this->get_schema_for_type( $slug );
		}
		return $out;
	}

	/**
	 * Removes callables from input-class settings for JSON encoding.
	 *
	 * @param array<string, mixed> $settings Raw settings from input class.
	 * @return array<string, mixed>
	 */
	private function json_safe_settings( array $settings ) {
		$clean = array();
		foreach ( $settings as $key => $meta ) {
			if ( ! is_array( $meta ) ) {
				continue;
			}
			$clean[ (string) $key ] = $this->strip_callables( $meta );
		}
		return $clean;
	}

	/**
	 * Removes callables from tab definitions for JSON encoding.
	 *
	 * @param array<string, mixed> $tabs Tab definitions.
	 * @return array<string, mixed>
	 */
	private function json_safe_tabs( array $tabs ) {
		$out = array();
		foreach ( $tabs as $id => $tab ) {
			if ( ! is_array( $tab ) ) {
				continue;
			}
			$out[ (string) $id ] = $this->strip_callables( $tab );
		}
		return $out;
	}

	/**
	 * Recursively drops callable values from an array tree.
	 *
	 * Non-array leaves are returned unchanged (may be scalar or object).
	 *
	 * @param mixed $data Tree with possible callables.
	 * @return mixed
	 *
	 * @phpstan-param mixed $data
	 * @phpstan-return mixed
	 */
	private function strip_callables( $data ) {
		if ( is_array( $data ) ) {
			$r = array();
			foreach ( $data as $k => $v ) {
				if ( is_callable( $v ) ) {
					continue;
				}
				$r[ $k ] = $this->strip_callables( $v );
			}
			return $r;
		}
		return $data;
	}
}
