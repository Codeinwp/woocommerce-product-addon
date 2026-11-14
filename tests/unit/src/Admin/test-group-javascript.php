<?php
/**
 * Tests for who may save field-group JavaScript.
 *
 * @package PPOM
 */

use PPOM\Admin\Manager;

/**
 * @covers \PPOM\Admin\Manager::create_group_from_fields
 * @covers \PPOM\Admin\Manager::update_group_from_fields
 */
class Test_Group_Javascript extends WP_UnitTestCase {

	/**
	 * Log in as a user with or without unfiltered HTML.
	 *
	 * @param bool $unfiltered Whether the user may post unfiltered HTML.
	 */
	private function log_in( bool $unfiltered ): void {
		$user = self::factory()->user->create_and_get( array( 'role' => 'shop_manager' ) );
		$user->add_cap( 'unfiltered_html', $unfiltered );
		wp_set_current_user( $user->ID );
	}

	/**
	 * Settings for a group with JavaScript.
	 *
	 * @param string $js Group JavaScript.
	 * @return array<string, string>
	 */
	private function settings( string $js ): array {
		return array(
			'productmeta_name' => 'Group',
			'productmeta_js'   => $js,
		);
	}

	/**
	 * Stored JavaScript of a group.
	 *
	 * @param int $group_id Group ID.
	 */
	private function stored_js( int $group_id ): string {
		wp_cache_flush();

		return (string) ppom_meta_repository()->get_row_by_id( $group_id )->productmeta_js;
	}

	public function test_new_group_javascript_needs_unfiltered_html(): void {
		$this->log_in( false );

		$group_id = Manager::create_group_from_fields( array(), $this->settings( 'console.log(1)' ) );

		$this->assertSame( '', $this->stored_js( $group_id ) );
	}

	public function test_group_javascript_update_without_unfiltered_html_keeps_the_stored_script(): void {
		$this->log_in( true );
		$group_id = Manager::create_group_from_fields( array(), $this->settings( 'console.log(1)' ) );
		$this->assertSame( 'console.log(1)', $this->stored_js( $group_id ) );

		$this->log_in( false );
		Manager::update_group_from_fields( $group_id, array(), $this->settings( 'console.log(2)' ) );

		$this->assertSame( 'console.log(1)', $this->stored_js( $group_id ) );
	}

	public function test_group_javascript_update_with_unfiltered_html_is_saved(): void {
		$this->log_in( true );
		$group_id = Manager::create_group_from_fields( array(), $this->settings( 'console.log(1)' ) );

		Manager::update_group_from_fields( $group_id, array(), $this->settings( 'console.log(2)' ) );

		$this->assertSame( 'console.log(2)', $this->stored_js( $group_id ) );
	}
}
