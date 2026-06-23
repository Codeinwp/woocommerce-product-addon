<?php
/**
 * Regression test for issue #638: ppom_option_price must not be
 * double-converted when PPOM Pro is also active and registers its own
 * ppom_hooks_convert_price callback on the same filter.
 *
 * Reported symptom: a 145 EUR option appeared as ~8,102 DKK instead of
 * ~1,084 DKK because the exchange rate (≈7.45) was applied twice — once
 * by PPOM Pro's hook and once by the core hook added in v34.x.
 *
 * @package PPOM
 */
class Test_Option_Price_Currency_Conversion extends WP_UnitTestCase {

	/** @var callable|null */
	private $woocs_filter;

	/** @var NM_PersonalizedProduct */
	private $ppom;

	public function setUp(): void {
		parent::setUp();
		$this->ppom = PPOM();
		// Ensure Core's hook is absent at test start — we control it per-test
		// via maybe_register_option_price_hook() so each test starts clean.
		remove_filter( 'ppom_option_price', array( $this->ppom, 'ppom_convert_price' ), 99 );
	}

	public function tearDown(): void {
		if ( null !== $this->woocs_filter ) {
			remove_filter( 'woocs_exchange_value', $this->woocs_filter );
			$this->woocs_filter = null;
		}
		remove_filter( 'ppom_option_price', 'ppom_hooks_convert_price', 99 );
		remove_filter( 'ppom_option_price', array( $this->ppom, 'ppom_convert_price' ), 99 );
		parent::tearDown();
	}

	private function add_woocs_filter( float $rate ): void {
		$this->woocs_filter = static function ( $price ) use ( $rate ) {
			return (float) $price * $rate;
		};
		add_filter( 'woocs_exchange_value', $this->woocs_filter, 10, 1 );
	}

	/**
	 * When Pro's ppom_hooks_convert_price is already on ppom_option_price,
	 * maybe_register_option_price_hook() must NOT add the core callback —
	 * preventing the exchange rate from being applied a second time.
	 */
	public function test_core_hook_not_registered_when_pro_hook_present() {
		add_filter( 'ppom_option_price', 'ppom_hooks_convert_price', 99, 1 );

		$this->ppom->maybe_register_option_price_hook();

		$this->assertFalse(
			has_filter( 'ppom_option_price', array( $this->ppom, 'ppom_convert_price' ) ),
			'Core ppom_convert_price must not be registered when Pro already has ppom_hooks_convert_price on the filter.'
		);
	}

	/**
	 * When Pro's hook is absent, maybe_register_option_price_hook() must
	 * add the core callback so standalone installs still convert prices.
	 */
	public function test_core_hook_registered_when_pro_hook_absent() {
		$this->ppom->maybe_register_option_price_hook();

		$this->assertNotFalse(
			has_filter( 'ppom_option_price', array( $this->ppom, 'ppom_convert_price' ) ),
			'Core ppom_convert_price must be registered when Pro hook is absent.'
		);
	}

	/**
	 * End-to-end: with Pro's hook present, applying ppom_option_price must
	 * convert via WOOCS exactly once (not twice as in the v34.x regression).
	 */
	public function test_option_price_converted_exactly_once_when_pro_hook_present() {
		$rate = 7.45;
		$this->add_woocs_filter( $rate );

		// Pro is active — its hook converts; Core's hook must stay off.
		add_filter( 'ppom_option_price', 'ppom_hooks_convert_price', 99, 1 );
		$this->ppom->maybe_register_option_price_hook();

		$result = apply_filters( 'ppom_option_price', 145.0 );

		$this->assertEqualsWithDelta(
			145.0 * $rate,
			(float) $result,
			0.01,
			sprintf( 'Expected %.2f (one conversion) but got %.2f.', 145.0 * $rate, (float) $result )
		);
	}

	/**
	 * End-to-end: without Pro's hook, Core alone converts via WOOCS once.
	 */
	public function test_option_price_converted_exactly_once_when_only_core_hook_present() {
		$rate = 7.45;
		$this->add_woocs_filter( $rate );

		$this->ppom->maybe_register_option_price_hook();

		$result = apply_filters( 'ppom_option_price', 145.0 );

		$this->assertEqualsWithDelta(
			145.0 * $rate,
			(float) $result,
			0.01,
			'Core alone should convert through WOOCS exactly once.'
		);
	}
}
