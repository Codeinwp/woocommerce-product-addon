<?php
/**
 * Picker catalog slugs must match the field-modal fixture used by TS definitions.
 *
 * @package woocommerce-product-addon
 */

require_once __DIR__ . '/class-ppom-test-case.php';

/**
 * @coversNothing
 */
class Test_Field_Modal_Picker_Catalog_Fixture extends PPOM_Test_Case {

	/**
	 * @return void
	 */
	public function test_php_catalog_slugs_match_json_fixture() {
		$fixture_path = PPOM_PATH . '/packages/admin/field-modal/src/definitions/picker-catalog.fixture.json';
		$this->assertFileExists( $fixture_path, 'Picker catalog fixture must exist for TS + PHPUnit parity.' );

		$decoded = json_decode( (string) file_get_contents( $fixture_path ), true );
		$this->assertIsArray( $decoded );
		$this->assertArrayHasKey( 'slugs', $decoded );
		$this->assertIsArray( $decoded['slugs'] );

		$groups = ppom_get_admin_field_type_groups();
		$this->assertNotEmpty( $groups );

		$php_slugs = array();
		foreach ( $groups as $group ) {
			$this->assertArrayHasKey( 'fields', $group );
			foreach ( $group['fields'] as $field ) {
				$this->assertArrayHasKey( 'slug', $field );
				$php_slugs[] = (string) $field['slug'];
			}
		}

		$php_sorted = $php_slugs;
		sort( $php_sorted );

		$json_sorted = $decoded['slugs'];
		$this->assertIsArray( $json_sorted );
		sort( $json_sorted );

		$this->assertSame(
			$php_sorted,
			$json_sorted,
			'Update packages/admin/field-modal/src/definitions/picker-catalog.fixture.json when the admin picker catalog changes.'
		);
	}
}
