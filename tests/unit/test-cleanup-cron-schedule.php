<?php
/**
 * Scheduling of the abandoned-upload cleanup sweep.
 *
 * @package ppom-pro
 */

require_once __DIR__ . '/class-ppom-test-case.php';

/**
 * @covers NM_PersonalizedProduct::schedule_unused_images_cleanup
 */
class Test_Cleanup_Cron_Schedule extends PPOM_Test_Case {

	const HOOK = 'do_action_remove_images';

	public function setUp(): void {
		parent::setUp();

		wp_clear_scheduled_hook( self::HOOK );
		$this->unset_ppom_option( 'ppom_remove_unused_images_schedule' );
	}

	public function tearDown(): void {
		wp_clear_scheduled_hook( self::HOOK );
		$this->unset_ppom_option( 'ppom_remove_unused_images_schedule' );

		parent::tearDown();
	}

	/**
	 * On a site where nobody has saved the settings page the frequency option is
	 * unset, and passing that straight to wp_schedule_event() left the sweep
	 * unscheduled forever — abandoned uploads were never removed.
	 *
	 * @return void
	 */
	public function test_sweep_is_scheduled_when_no_frequency_has_been_saved() {
		NM_PersonalizedProduct::schedule_unused_images_cleanup();

		$this->assertNotFalse(
			wp_next_scheduled( self::HOOK ),
			'The cleanup sweep must be scheduled even with no saved frequency.'
		);
		$this->assertSame( 'daily', wp_get_schedule( self::HOOK ) );
	}

	/**
	 * A frequency the merchant actually chose must be respected rather than
	 * overridden by the default. `monthly` is registered by WooCommerce, which
	 * PPOM requires, so every option the settings dropdown offers resolves.
	 *
	 * @return void
	 */
	public function test_sweep_honours_a_saved_frequency() {
		$this->set_ppom_option( 'ppom_remove_unused_images_schedule', 'monthly' );

		NM_PersonalizedProduct::schedule_unused_images_cleanup();

		$this->assertSame( 'monthly', wp_get_schedule( self::HOOK ) );
	}

	/**
	 * The option predates having a default and can still be stored empty. An
	 * unusable recurrence makes wp_schedule_event() fail without warning, which
	 * is exactly how the sweep came to never run.
	 *
	 * @return void
	 */
	public function test_sweep_is_still_scheduled_when_saved_frequency_is_empty() {
		$this->set_ppom_option( 'ppom_remove_unused_images_schedule', '' );

		NM_PersonalizedProduct::schedule_unused_images_cleanup();

		$this->assertNotFalse(
			wp_next_scheduled( self::HOOK ),
			'An empty saved frequency must not leave the sweep unscheduled.'
		);
	}

	/**
	 * The scheduler only helps if it actually runs. Scheduling used to happen in
	 * the activation hook alone, which is why installs that activated with an
	 * unusable recurrence never recovered; hooking it to `init` is what lets them
	 * pick the event up. Without this test the hook could be dropped and every
	 * other test here would still pass.
	 *
	 * @return void
	 */
	public function test_scheduler_runs_on_init() {
		$this->assertNotFalse(
			has_action( 'init', array( 'NM_PersonalizedProduct', 'schedule_unused_images_cleanup' ) ),
			'The cleanup scheduler must be hooked to init, not only to activation.'
		);
	}

	/**
	 * The sweep runs on a schedule, so a second pass must not stack a duplicate
	 * event on every request now that scheduling happens on `init`.
	 *
	 * @return void
	 */
	public function test_scheduling_twice_does_not_duplicate_the_event() {
		NM_PersonalizedProduct::schedule_unused_images_cleanup();
		$first = wp_next_scheduled( self::HOOK );

		NM_PersonalizedProduct::schedule_unused_images_cleanup();

		$this->assertSame( $first, wp_next_scheduled( self::HOOK ) );
		$this->assertCount( 1, _get_cron_array()[ $first ][ self::HOOK ] );
	}
}
