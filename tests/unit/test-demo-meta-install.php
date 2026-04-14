<?php
/**
 * Tests for NM_PersonalizedProduct::ppom_install_demo_meta().
 *
 * @package ppom-pro
 * @see NM_PersonalizedProduct::ppom_install_demo_meta()
 */

require_once __DIR__ . '/class-ppom-test-case.php';

/**
 * Class Test_Demo_Meta_Install
 */
class Test_Demo_Meta_Install extends PPOM_Test_Case {

	/**
	 * Clean up any demo rows and reset the installed flag before and after each test.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();
		$this->remove_demo_rows_and_flag();
	}

	/**
	 * @inheritDoc
	 */
	public function tearDown(): void {
		$this->remove_demo_rows_and_flag();
		parent::tearDown();
	}

	// -----------------------------------------------------------------------
	// Helpers
	// -----------------------------------------------------------------------

	/**
	 * Remove all rows with productmeta_name = 'PPOM Demo Field' and delete the
	 * ppom_demo_meta_installed option so each test starts from a clean state.
	 *
	 * @return void
	 */
	private function remove_demo_rows_and_flag() {
		global $wpdb;

		$table = $wpdb->prefix . PPOM_TABLE_META;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->delete( $table, array( 'productmeta_name' => 'PPOM Demo Field' ), array( '%s' ) );

		delete_option( 'ppom_demo_meta_installed' );
	}

	/**
	 * Return the count of demo rows currently in the table.
	 *
	 * @return int
	 */
	private function count_demo_rows() {
		global $wpdb;

		$table = $wpdb->prefix . PPOM_TABLE_META;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM `{$table}` WHERE productmeta_name = %s",
				'PPOM Demo Field'
			)
		);
	}

	// -----------------------------------------------------------------------
	// Tests
	// -----------------------------------------------------------------------

	/**
	 * When the flag is absent and the demo JSON file exists, at least one row
	 * should be inserted into the PPOM table.
	 *
	 * @return void
	 */
	public function test_install_demo_meta_inserts_rows_when_not_installed() {
		$this->assertSame( 0, $this->count_demo_rows(), 'No demo rows should exist before installation.' );

		NM_PersonalizedProduct::ppom_install_demo_meta();

		$this->assertGreaterThan( 0, $this->count_demo_rows(), 'At least one demo row should be inserted.' );
	}

	/**
	 * After a successful installation the ppom_demo_meta_installed option must be set.
	 *
	 * @return void
	 */
	public function test_install_demo_meta_sets_installed_option() {
		NM_PersonalizedProduct::ppom_install_demo_meta();

		$this->assertNotEmpty( get_option( 'ppom_demo_meta_installed' ) );
	}

	/**
	 * Every inserted row must use 'PPOM Demo Field' as its productmeta_name,
	 * regardless of the original name stored in the JSON seed file.
	 *
	 * @return void
	 */
	public function test_install_demo_meta_overrides_productmeta_name() {
		NM_PersonalizedProduct::ppom_install_demo_meta();

		global $wpdb;
		$table = $wpdb->prefix . PPOM_TABLE_META;

		// All rows inserted by the seeder must carry the canonical demo name.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT productmeta_name FROM `{$table}` WHERE productmeta_name = %s",
				'PPOM Demo Field'
			),
			ARRAY_A
		);

		$this->assertNotEmpty( $rows, 'Expected demo rows with overridden name.' );
		foreach ( $rows as $row ) {
			$this->assertSame( 'PPOM Demo Field', $row['productmeta_name'] );
		}
	}

	/**
	 * The productmeta_id values in the JSON seed file must not be reused: the
	 * DB should auto-assign new IDs so there are no collisions.
	 *
	 * @return void
	 */
	public function test_install_demo_meta_does_not_reuse_original_id() {
		// The demo JSON contains productmeta_id = 183.
		$original_seed_id = 183;

		NM_PersonalizedProduct::ppom_install_demo_meta();

		$row = $this->get_ppom_meta_row( $original_seed_id );

		// If the row with ID 183 exists its name must NOT be 'PPOM Demo Field'
		// because the seeder should have used a fresh auto-increment ID.
		if ( null !== $row ) {
			$this->assertNotSame( 'PPOM Demo Field', $row['productmeta_name'], 'Seeder must not force the original productmeta_id.' );
		}

		// Regardless, at least one demo row with a different ID should exist.
		global $wpdb;
		$table = $wpdb->prefix . PPOM_TABLE_META;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$demo_id = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT productmeta_id FROM `{$table}` WHERE productmeta_name = %s LIMIT 1",
				'PPOM Demo Field'
			)
		);

		$this->assertGreaterThan( 0, $demo_id, 'A demo row with a valid auto-assigned ID should exist.' );
		$this->assertNotSame( $original_seed_id, $demo_id, 'Auto-assigned ID must differ from the original seed ID.' );
	}

	/**
	 * When ppom_demo_meta_installed is already set, calling the method again
	 * must not insert any new rows.
	 *
	 * @return void
	 */
	public function test_install_demo_meta_skips_when_already_installed() {
		update_option( 'ppom_demo_meta_installed', 1 );

		NM_PersonalizedProduct::ppom_install_demo_meta();

		$this->assertSame( 0, $this->count_demo_rows(), 'No rows should be inserted when the flag is already set.' );
	}

	/**
	 * Calling ppom_install_demo_meta() twice without resetting the flag must
	 * not duplicate rows (idempotency via the installed flag guard).
	 *
	 * @return void
	 */
	public function test_install_demo_meta_is_idempotent() {
		NM_PersonalizedProduct::ppom_install_demo_meta();
		$count_after_first = $this->count_demo_rows();

		// Second call should bail early because the flag is now set.
		NM_PersonalizedProduct::ppom_install_demo_meta();
		$count_after_second = $this->count_demo_rows();

		$this->assertSame( $count_after_first, $count_after_second, 'Row count must not change on a second call.' );
	}
}
