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
}
