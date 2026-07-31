<?php
/**
 * Regression coverage for the `[ppom]` shortcode renderer.
 *
 * Guards against issue #677: the shortcode callback lives in namespace
 * `PPOM\Hooks`, so an unqualified reference to the legacy global
 * `PPOM_FRONTEND_SCRIPTS` class resolves to `PPOM\Hooks\PPOM_FRONTEND_SCRIPTS`
 * and fatals with "Class ... not found" when a `product_id` is supplied.
 *
 * @package ppom-pro
 */

class Test_Shortcode_Render_Regression extends PPOM_Test_Case {

	/**
	 * @return array<string, mixed>
	 */
	private function text_field() {
		return array(
			'type'       => 'text',
			'title'      => 'Custom text',
			'data_name'  => 'custom_text',
			'visibility' => 'everyone',
			'status'     => 'on',
		);
	}

	/**
	 * Rendering the shortcode with a valid product_id must not fatal and must
	 * return the add-to-cart form markup.
	 *
	 * @return void
	 */
	public function test_render_shortcode_with_product_id_does_not_fatal() {
		$product = $this->create_simple_product();
		$this->insert_ppom_meta( array( $this->text_field() ), $product->get_id() );

		$output = \PPOM\Hooks\Callbacks::render_shortcode( array( 'product_id' => (string) $product->get_id() ) );

		$this->assertIsString( $output );
		$this->assertStringContainsString( '<form class="cart"', $output );
		$this->assertStringContainsString( 'name="add-to-cart"', $output );
	}

	/**
	 * An invalid product_id should render the validation notice rather than fatal.
	 *
	 * @return void
	 */
	public function test_render_shortcode_with_invalid_product_id_returns_notice() {
		$output = \PPOM\Hooks\Callbacks::render_shortcode( array( 'product_id' => '999999' ) );

		$this->assertIsString( $output );
		$this->assertStringContainsString( 'Product ID is not valid', $output );
	}
}
