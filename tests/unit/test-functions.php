<?php
/**
 * Class Test_Functions
 *
 * @package ppom-pro
 */

class Test_Functions extends WP_UnitTestCase {

	/**
	 * Test recursive sanitization.
	 * @return void
	 */
	public function testRecursiveSanitization() {
		$input = [
			'textarea' => '<h1>This is a large header</h1>
<img src="https://via.placeholder.com/300" alt="Placeholder Image" width="300" height="200">',
			'select_area' => 'Ares',
			'html_field' => '<span class="show_description ppom-input-desc">ares</span>',
			'radio' => 'Aces',
			'file_field' => [
				'o_1i0quc48f186hch410vbase1pn0g' => [
					'org' => 'o_1i0quc48f186hch410vbase1pn0g.png'
				]
			],
			'fixed_price' => 1000,
			'cropper' => [
				'o_1i0qucdipef614jnju2ca5q37m' => [
					'org' => 'o_1i0qucdipef614jnju2ca5q37m.png',
					'cropped' => 'data:image/png;base64,iVBORw0KGgoAAAAN'
				]
			]
		];

		$expected = [
			'textarea' => 'This is a large header',
			'select_area' => 'Ares',
			'html_field' => 'ares',
			'radio' => 'Aces',
			'file_field' => [
				'o_1i0quc48f186hch410vbase1pn0g' => [
					'org' => 'o_1i0quc48f186hch410vbase1pn0g.png'
				]
			],
			'fixed_price' => 1000,
			'cropper' => [
				'o_1i0qucdipef614jnju2ca5q37m' => [
					'org' => 'o_1i0qucdipef614jnju2ca5q37m.png',
					'cropped' => 'data:image/png;base64,iVBORw0KGgoAAAAN'
				]
			]
			];

		$this->assertEquals( $expected, ppom_recursive_sanitization( $input ) );
	}

	/**
	 * Cart fee price should handle currency symbols gracefully.
	 *
	 * @return void
	 */
	public function testCartFeePriceParsesCurrencySymbol() {
		$price_array = [
			[
				'apply'    => 'cart_fee',
				'price'    => '$10',
				'quantity' => 1,
			],
		];

		$this->assertEquals( 10.0, ppom_price_get_cart_fee_total( $price_array ) );
	}

	/**
	 * Addon price should normalize currency symbols and respect quantity.
	 *
	 * @return void
	 */
	public function testAddonPriceParsesCurrencySymbolWithQuantity() {
		$price_array = [
			[
				'apply'    => 'addon',
				'price'    => 'USD 15',
				'quantity' => '2',
			],
		];

		$this->assertEquals( 30.0, ppom_price_get_addon_total( $price_array ) );
	}
}
