<?php
/**
 * Unit tests for PPOM\WooCommerce\Order\OrderHandler (pure / low-coupling behavior).
 *
 * @package ppom-pro
 */

use PPOM\WooCommerce\Order\OrderHandler;

/**
 * @covers \PPOM\WooCommerce\Order\OrderHandler
 */
class Test_Order_Handler extends PPOM_Test_Case {

	/**
	 * @return void
	 */
	public function test_posted_fields_without_cropper_payloads_removes_listed_keys() {
		$posted = array(
			'text_field'   => 'hello',
			'crop_a'       => array( 'raw' => 'base64...' ),
			'crop_b'       => array( 'raw' => 'more' ),
			'another_text' => 'x',
		);

		$stripped = OrderHandler::posted_fields_without_cropper_payloads(
			$posted,
			array( 'crop_a', 'crop_b' )
		);

		$this->assertSame(
			array(
				'text_field'   => 'hello',
				'another_text' => 'x',
			),
			$stripped
		);
	}

	/**
	 * @return void
	 */
	public function test_posted_fields_without_cropper_payloads_noop_when_no_cropper_keys() {
		$posted = array( 'a' => 1, 'b' => 2 );

		$this->assertSame( $posted, OrderHandler::posted_fields_without_cropper_payloads( $posted, array() ) );
	}

	/**
	 * @return void
	 */
	public function test_order_meta_display_key_from_field_meta_uses_title_when_present() {
		$this->assertSame(
			'Size',
			OrderHandler::order_meta_display_key_from_field_meta(
				'size_key',
				array( 'title' => 'Size', 'data_name' => 'size_key' )
			)
		);
	}

	/**
	 * @return void
	 */
	public function test_order_meta_display_key_from_field_meta_strips_slashes_from_title() {
		$this->assertSame(
			'Line "quoted"',
			OrderHandler::order_meta_display_key_from_field_meta(
				'k',
				array( 'title' => 'Line \"quoted\"' )
			)
		);
	}

	/**
	 * @return void
	 */
	public function test_order_meta_display_key_from_field_meta_falls_back_when_no_title() {
		$this->assertSame( 'raw_key', OrderHandler::order_meta_display_key_from_field_meta( 'raw_key', array( 'type' => 'text' ) ) );
		$this->assertSame( 'raw_key', OrderHandler::order_meta_display_key_from_field_meta( 'raw_key', false ) );
	}

	/**
	 * @return void
	 */
	public function test_hide_order_meta_removes_ppom_has_quantities() {
		$m1       = new stdClass();
		$m1->key  = 'ppom_has_quantities';
		$m1->id   = 1;
		$m2       = new stdClass();
		$m2->key  = 'customer_note_field';
		$m2->id   = 2;
		$meta     = array( 10 => $m1, 20 => $m2 );

		$result = OrderHandler::hide_order_meta( $meta, null );

		$this->assertArrayHasKey( 20, $result );
		$this->assertArrayNotHasKey( 10, $result );
		$this->assertSame( 'customer_note_field', $result[20]->key );
	}

	/**
	 * @return void
	 */
	public function test_hide_order_meta_returns_empty_unchanged() {
		$this->assertSame( array(), OrderHandler::hide_order_meta( array(), null ) );
	}

	/**
	 * @return void
	 */
	public function test_wc_order_again_compatibility_copies_ppom_block_from_item_meta() {
		$cart = array( 'existing' => true );
		$item = new class() {
			/**
			 * @param string $key Meta key.
			 *
			 * @return array<string, mixed>|string
			 */
			public function get_meta( $key = '' ) {
				if ( '_ppom_fields' !== $key ) {
					return '';
				}

				return array(
					'fields' => array( 'size' => 'L' ),
				);
			}
		};

		$out = OrderHandler::wc_order_again_compatibility( $cart, $item, null );

		$this->assertSame( true, $out['existing'] );
		$this->assertSame( array( 'fields' => array( 'size' => 'L' ) ), $out['ppom'] );
	}

	/**
	 * @return void
	 */
	public function test_wc_order_again_compatibility_ignores_non_array_meta() {
		$cart = array();
		$item = new class() {
			/**
			 * @param string $key Meta key.
			 *
			 * @return string
			 */
			public function get_meta( $key = '' ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
				return '';
			}
		};

		$this->assertSame( array(), OrderHandler::wc_order_again_compatibility( $cart, $item, null ) );
	}

	/**
	 * @return void
	 */
	public function test_wc_email_improvements_enabled_reads_option() {
		update_option( 'woocommerce_feature_email_improvements_enabled', 'yes' );
		$this->assertTrue( OrderHandler::wc_email_improvements_enabled() );

		update_option( 'woocommerce_feature_email_improvements_enabled', 'no' );
		$this->assertFalse( OrderHandler::wc_email_improvements_enabled() );
	}
}
