<?php
/**
 * Unit tests for PPOM\Pricing\ModernCartFeeApplicator::apply_to_cart.
 *
 * The applicator pushes PPOM-derived discounts (price matrix) and cart_fee
 * priced options into WooCommerce's fee bus. We exercise it against a real
 * WC_Cart instance so the fee assertions cover the end-to-end shape.
 *
 * @package ppom-pro
 */

require_once dirname( __DIR__, 2 ) . '/class-ppom-test-case.php';

use PPOM\Pricing\ModernCartFeeApplicator;
use PPOM\Pricing\Engine;

/**
 * @covers \PPOM\Pricing\ModernCartFeeApplicator
 */
class Test_Pricing_ModernCartFeeApplicator extends PPOM_Test_Case {

	/**
	 * Items without ppom fields are skipped — no fees added.
	 *
	 * @return void
	 */
	public function test_items_without_ppom_fields_are_ignored() {
		$product = $this->create_simple_product( array( 'regular_price' => '10', 'virtual' => true ) );

		$this->initialize_woocommerce_checkout_context();
		WC()->cart->add_to_cart( $product->get_id(), 1 );

		ModernCartFeeApplicator::apply_to_cart( WC()->cart );

		$this->assertSame( array(), WC()->cart->get_fees() );
	}

	/**
	 * A "onetime" PPOM select field routes its priced option through cart_fee,
	 * which the applicator must push onto the cart as a fee.
	 *
	 * @return void
	 */
	public function test_cart_fee_priced_field_adds_woocommerce_fee() {
		$product = $this->create_simple_product( array( 'regular_price' => '10', 'virtual' => true ) );

		$this->insert_ppom_meta(
			array(
				$this->build_select_field(
					'gift_wrap',
					'Gift Wrap',
					array( array( 'option' => 'Premium box', 'price' => '7' ) ),
					array( 'onetime' => 'on' )
				),
			),
			$product->get_id()
		);

		$this->initialize_woocommerce_checkout_context();

		$cart_key = $this->add_product_to_real_cart(
			$product->get_id(),
			array( 'fields' => array( 'gift_wrap' => 'Premium box' ) )
		);
		$this->assertNotFalse( $cart_key );

		$this->reload_real_cart_from_session();
		// Engine::price_cart_fee is hooked on woocommerce_cart_calculate_fees in modern
		// mode, so add_to_cart above already enqueued one fee. Clear before asserting
		// what ModernCartFeeApplicator alone produces.
		WC()->cart->fees_api()->remove_all_fees();
		ModernCartFeeApplicator::apply_to_cart( WC()->cart );

		$fees = WC()->cart->get_fees();
		$this->assertCount( 1, $fees );

		$fee = reset( $fees );
		$this->assertStringContainsString( 'Gift Wrap', $fee->name );
		$this->assertStringContainsString( 'Premium box', $fee->name );
		$this->assertEqualsWithDelta( 7.0, (float) $fee->amount, 0.0001 );
	}

	/**
	 * Fee priced at 0 is dropped (the applicator skips `fee_price == 0`).
	 *
	 * @return void
	 */
	public function test_zero_price_fee_is_not_added_to_cart() {
		$product = $this->create_simple_product( array( 'regular_price' => '10', 'virtual' => true ) );

		$this->insert_ppom_meta(
			array(
				$this->build_select_field(
					'addon',
					'Addon',
					array( array( 'option' => 'Free', 'price' => '0' ) ),
					array( 'onetime' => 'on' )
				),
			),
			$product->get_id()
		);

		$this->initialize_woocommerce_checkout_context();
		$cart_key = $this->add_product_to_real_cart(
			$product->get_id(),
			array( 'fields' => array( 'addon' => 'Free' ) )
		);
		$this->assertNotFalse( $cart_key );
		$this->reload_real_cart_from_session();
		WC()->cart->fees_api()->remove_all_fees();

		ModernCartFeeApplicator::apply_to_cart( WC()->cart );

		$this->assertSame( array(), WC()->cart->get_fees() );
	}

	/**
	 * The ppom_fixed_fee_label filter can rewrite the cart fee label.
	 *
	 * @return void
	 */
	public function test_fixed_fee_label_filter_can_override_label() {
		$product = $this->create_simple_product( array( 'regular_price' => '10', 'virtual' => true ) );

		$this->insert_ppom_meta(
			array(
				$this->build_select_field(
					'gift_wrap',
					'Gift Wrap',
					array( array( 'option' => 'Premium', 'price' => '5' ) ),
					array( 'onetime' => 'on' )
				),
			),
			$product->get_id()
		);

		$this->initialize_woocommerce_checkout_context();
		$cart_key = $this->add_product_to_real_cart(
			$product->get_id(),
			array( 'fields' => array( 'gift_wrap' => 'Premium' ) )
		);
		$this->assertNotFalse( $cart_key );
		$this->reload_real_cart_from_session();
		WC()->cart->fees_api()->remove_all_fees();

		$filter = static function () {
			return 'Custom Label';
		};
		add_filter( 'ppom_fixed_fee_label', $filter );

		try {
			ModernCartFeeApplicator::apply_to_cart( WC()->cart );
		} finally {
			remove_filter( 'ppom_fixed_fee_label', $filter );
		}

		$fees = WC()->cart->get_fees();
		$this->assertCount( 1, $fees );
		$this->assertSame( 'Custom Label', reset( $fees )->name );
	}

	/**
	 * The ppom_cart_fixed_fee filter can override the fee amount before WC sees it.
	 *
	 * @return void
	 */
	public function test_cart_fixed_fee_filter_can_override_amount() {
		$product = $this->create_simple_product( array( 'regular_price' => '10', 'virtual' => true ) );

		$this->insert_ppom_meta(
			array(
				$this->build_select_field(
					'gift_wrap',
					'Gift Wrap',
					array( array( 'option' => 'Premium', 'price' => '5' ) ),
					array( 'onetime' => 'on' )
				),
			),
			$product->get_id()
		);

		$this->initialize_woocommerce_checkout_context();
		$cart_key = $this->add_product_to_real_cart(
			$product->get_id(),
			array( 'fields' => array( 'gift_wrap' => 'Premium' ) )
		);
		$this->assertNotFalse( $cart_key );
		$this->reload_real_cart_from_session();
		WC()->cart->fees_api()->remove_all_fees();

		$filter = static function () {
			return 99.0;
		};
		add_filter( 'ppom_cart_fixed_fee', $filter );

		try {
			ModernCartFeeApplicator::apply_to_cart( WC()->cart );
		} finally {
			remove_filter( 'ppom_cart_fixed_fee', $filter );
		}

		$fees = WC()->cart->get_fees();
		$this->assertCount( 1, $fees );
		$this->assertEqualsWithDelta( 99.0, (float) reset( $fees )->amount, 0.0001 );
	}

	/**
	 * Regression for #630: Engine::price_cart_fee must not fatally resolve
	 * WooCommerce tax classes inside PPOM\Pricing namespace.
	 *
	 * @return void
	 */
	public function test_engine_cart_fee_with_tax_inclusive_prices_does_not_fatal() {
		$product = $this->create_simple_product( array( 'regular_price' => '10', 'virtual' => true ) );

		$this->insert_ppom_meta(
			array(
				$this->build_select_field(
					'gift_wrap',
					'Gift Wrap',
					array( array( 'option' => 'Premium box', 'price' => '7' ) ),
					array( 'onetime' => 'on' )
				),
			),
			$product->get_id()
		);

		$this->initialize_woocommerce_checkout_context();
		$cart_key = $this->add_product_to_real_cart(
			$product->get_id(),
			array( 'fields' => array( 'gift_wrap' => 'Premium box' ) )
		);
		$this->assertNotFalse( $cart_key );

		$original_prices_include_tax = get_option( 'woocommerce_prices_include_tax' );
		$original_calc_taxes         = get_option( 'woocommerce_calc_taxes' );
		update_option( 'woocommerce_calc_taxes', 'yes' );
		update_option( 'woocommerce_prices_include_tax', 'yes' );

		try {
			$this->reload_real_cart_from_session();
			WC()->cart->fees_api()->remove_all_fees();

			Engine::price_cart_fee( WC()->cart );
		} finally {
			update_option( 'woocommerce_prices_include_tax', $original_prices_include_tax );
			update_option( 'woocommerce_calc_taxes', $original_calc_taxes );
		}

		$fees = WC()->cart->get_fees();
		$this->assertCount( 1, $fees );
		$this->assertEqualsWithDelta( 7.0, (float) reset( $fees )->amount, 0.0001 );
	}
}
