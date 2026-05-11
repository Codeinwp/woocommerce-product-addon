<?php
/**
 * Unit tests for PPOM\Admin\FieldModal\FieldModalSchemaBuilder.
 *
 * Covers the JSON-safe catalog and per-type schema producer for the React modal.
 *
 * @package ppom-pro
 */

require_once dirname( __DIR__, 3 ) . '/class-ppom-test-case.php';

use PPOM\Admin\FieldModal\FieldModalSchemaBuilder;

/**
 * @covers \PPOM\Admin\FieldModal\FieldModalSchemaBuilder
 */
class Test_Admin_FieldModal_FieldModalSchemaBuilder extends PPOM_Test_Case {

	private function builder(): FieldModalSchemaBuilder {
		return new FieldModalSchemaBuilder();
	}

	/**
	 * get_catalog_groups_for_rest returns the field-type picker groups with the
	 * required keys per group and per field, with each `locked` flag set to a bool.
	 *
	 * @return void
	 */
	public function test_get_catalog_groups_for_rest_returns_normalized_group_shape() {
		$groups = $this->builder()->get_catalog_groups_for_rest();

		$this->assertIsArray( $groups );
		$this->assertNotEmpty( $groups, 'PPOM ships built-in field-type groups.' );

		foreach ( $groups as $group ) {
			$this->assertArrayHasKey( 'id', $group );
			$this->assertArrayHasKey( 'label', $group );
			$this->assertArrayHasKey( 'fields', $group );
			$this->assertIsString( $group['id'] );
			$this->assertIsString( $group['label'] );
			$this->assertIsArray( $group['fields'] );

			foreach ( $group['fields'] as $field ) {
				$this->assertArrayHasKey( 'slug', $field );
				$this->assertArrayHasKey( 'title', $field );
				$this->assertArrayHasKey( 'description', $field );
				$this->assertArrayHasKey( 'icon', $field );
				$this->assertArrayHasKey( 'locked', $field );
				$this->assertArrayHasKey( 'min_plan', $field );
				$this->assertTrue( null === $field['locked'] || is_bool( $field['locked'] ) );
			}
		}
	}

	/**
	 * The ppom_admin_field_modal_catalog_groups filter can drop or reorder groups.
	 *
	 * @return void
	 */
	public function test_get_catalog_groups_for_rest_applies_catalog_filter() {
		$filter = static function () {
			return array(
				array(
					'id'     => 'only-one',
					'label'  => 'Only one',
					'fields' => array(),
				),
			);
		};
		add_filter( 'ppom_admin_field_modal_catalog_groups', $filter );

		try {
			$groups = $this->builder()->get_catalog_groups_for_rest();
		} finally {
			remove_filter( 'ppom_admin_field_modal_catalog_groups', $filter );
		}

		$this->assertCount( 1, $groups );
		$this->assertSame( 'only-one', $groups[0]['id'] );
	}

	/**
	 * get_catalog is the flat version of the grouped catalog — one entry per field type.
	 *
	 * @return void
	 */
	public function test_get_catalog_flattens_groups_into_field_list() {
		$grouped = $this->builder()->get_catalog_groups_for_rest();
		$flat    = $this->builder()->get_catalog();

		$expected_count = array_sum(
			array_map(
				static function ( $group ) {
					return is_array( $group['fields'] ?? null ) ? count( $group['fields'] ) : 0;
				},
				$grouped
			)
		);

		$this->assertCount( $expected_count, $flat );

		foreach ( $flat as $field ) {
			$this->assertArrayHasKey( 'slug', $field );
			$this->assertArrayHasKey( 'title', $field );
			$this->assertArrayHasKey( 'desc', $field );
			$this->assertArrayHasKey( 'icon', $field );
			$this->assertArrayHasKey( 'locked', $field );
			$this->assertIsBool( $field['locked'] );
			$this->assertArrayHasKey( 'min_plan', $field );
		}
	}

	/**
	 * get_schema_for_type returns the base scaffolding even when the input type
	 * isn't registered (modal must still render an empty form).
	 *
	 * @return void
	 */
	public function test_get_schema_for_type_returns_base_shape_for_unknown_type() {
		$schema = $this->builder()->get_schema_for_type( 'not-a-real-type-xyz' );

		$this->assertSame( 'not-a-real-type-xyz', $schema['type'] );
		$this->assertSame( array(), $schema['settings'] );
		$this->assertSame( array(), $schema['tabs'] );
	}

	/**
	 * For a known core type (text) the schema must include the type slug and
	 * a settings array — exact keys are owned by PPOM_Fields_Meta.
	 *
	 * @return void
	 */
	public function test_get_schema_for_type_returns_text_settings() {
		$schema = $this->builder()->get_schema_for_type( 'text' );

		$this->assertSame( 'text', $schema['type'] );
		$this->assertIsArray( $schema['settings'] );
		$this->assertIsArray( $schema['tabs'] );
	}

	/**
	 * sanitize_key is applied to the input — type=" text" or "TEXT!" still maps to "text".
	 *
	 * @return void
	 */
	public function test_get_schema_for_type_normalizes_type_via_sanitize_key() {
		$schema = $this->builder()->get_schema_for_type( '  TEXT!  ' );

		$this->assertSame( 'text', $schema['type'] );
	}

	/**
	 * get_all_type_schemas keys results by slug, with each entry being a valid schema array.
	 *
	 * @return void
	 */
	public function test_get_all_type_schemas_returns_map_keyed_by_slug() {
		$all = $this->builder()->get_all_type_schemas();

		$this->assertIsArray( $all );
		$this->assertNotEmpty( $all );

		foreach ( $all as $slug => $schema ) {
			$this->assertIsString( $slug );
			$this->assertNotSame( '', $slug );
			$this->assertSame( $slug, $schema['type'] );
			$this->assertArrayHasKey( 'settings', $schema );
			$this->assertArrayHasKey( 'tabs', $schema );
		}
	}

	/**
	 * The full schema map must be JSON-encodable — strip_callables/json_safe_*
	 * are the whole point of this builder.
	 *
	 * @return void
	 */
	public function test_get_all_type_schemas_is_json_encodable() {
		$all     = $this->builder()->get_all_type_schemas();
		$encoded = wp_json_encode( $all );

		$this->assertNotFalse( $encoded, 'Builder output must be JSON-safe.' );
		$this->assertJson( $encoded );
	}

	/**
	 * Filter ppom_admin_field_modal_schema can swap a type's schema entirely.
	 *
	 * @return void
	 */
	public function test_get_schema_for_type_applies_schema_filter() {
		$filter = static function ( $base, $type ) {
			$base['custom']      = true;
			$base['custom_type'] = $type;
			return $base;
		};
		add_filter( 'ppom_admin_field_modal_schema', $filter, 10, 2 );

		try {
			$schema = $this->builder()->get_schema_for_type( 'text' );
		} finally {
			remove_filter( 'ppom_admin_field_modal_schema', $filter, 10 );
		}

		$this->assertTrue( $schema['custom'] );
		$this->assertSame( 'text', $schema['custom_type'] );
	}

	/**
	 * Callable values inside settings must not survive into the final schema.
	 *
	 * Mock a fake input with a closure inside its `settings` and assert it's stripped.
	 *
	 * @return void
	 */
	public function test_settings_with_callable_values_are_stripped() {
		if ( ! function_exists( 'PPOM' ) ) {
			$this->markTestSkipped( 'PPOM() helper not loaded.' );
		}

		// sanitize_key() lowercases — keep the slug all-lowercase so isset() still hits our mock.
		$slug = 'ppom-test-input-' . strtolower( wp_generate_password( 4, false ) );
		$mock = new \stdClass();
		$mock->settings = array(
			'a' => array(
				'label'          => 'A',
				'inline_handler' => static function () {
					return 'should be stripped';
				},
				'nested'         => array(
					'cb'   => 'strtolower',
					'safe' => 'keep me',
				),
			),
		);

		$original_inputs           = PPOM()->inputs;
		PPOM()->inputs[ $slug ]    = $mock;

		try {
			$schema = $this->builder()->get_schema_for_type( $slug );
		} finally {
			PPOM()->inputs = $original_inputs;
		}

		$this->assertArrayHasKey( 'a', $schema['settings'] );
		$this->assertArrayNotHasKey( 'inline_handler', $schema['settings']['a'] );
		$this->assertSame( 'A', $schema['settings']['a']['label'] );
		$this->assertSame( 'keep me', $schema['settings']['a']['nested']['safe'] );
		$this->assertArrayNotHasKey( 'cb', $schema['settings']['a']['nested'] );
	}
}
