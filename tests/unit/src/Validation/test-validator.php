<?php
/**
 * Unit tests for PPOM\Validation\Validator sanitization helpers.
 *
 * @package ppom-pro
 */

use PPOM\Validation\Validator;

/**
 * @covers \PPOM\Validation\Validator
 */
class Test_Validator_Src extends WP_UnitTestCase {

	/**
	 * @var callable|null
	 */
	private $fields_with_html_filter;

	/**
	 * @return void
	 */
	public function tearDown(): void {
		if ( null !== $this->fields_with_html_filter ) {
			remove_filter( 'ppom_fields_with_html', $this->fields_with_html_filter );
			$this->fields_with_html_filter = null;
		}
		parent::tearDown();
	}

	/**
	 * @return void
	 */
	public function test_esc_html_allows_basic_markup_strips_unknown_tags() {
		$html      = '<p>Hello</p><ppom-malicious-custom-tag>bad</ppom-malicious-custom-tag>';
		$sanitized = Validator::esc_html( $html );

		$this->assertStringContainsString( 'Hello', $sanitized );
		$this->assertStringNotContainsString( 'ppom-malicious-custom-tag', $sanitized );
	}

	/**
	 * @return void
	 */
	public function test_sanitize_array_data_uses_esc_html_for_html_fields() {
		$data = Validator::sanitize_array_data(
			array(
				'description' => '<strong>x</strong><ppom-unknown>strip</ppom-unknown>',
				'title'       => 'Plain <b>title</b>',
			)
		);

		$this->assertStringContainsString( '<strong>', $data['description'] );
		$this->assertStringNotContainsString( 'ppom-unknown', $data['description'] );
		$this->assertStringNotContainsString( '<b>', $data['title'] );
	}

	/**
	 * @return void
	 */
	public function test_sanitize_array_data_recurses_nested_arrays() {
		$data = Validator::sanitize_array_data(
			array(
				'nested' => array(
					'tooltip' => '<em>tip</em>',
				),
			)
		);

		$this->assertStringContainsString( '<em>', $data['nested']['tooltip'] );
	}

	/**
	 * @return void
	 */
	public function test_fields_with_html_includes_expected_keys() {
		$keys = Validator::fields_with_html();

		$this->assertContains( 'description', $keys );
		$this->assertContains( 'tooltip', $keys );
		$this->assertContains( 'html', $keys );
	}

	/**
	 * @return void
	 */
	public function test_fields_with_html_respects_filter() {
		$this->fields_with_html_filter = static function ( $have_html ) {
			$have_html[] = 'custom_html_key';
			return $have_html;
		};
		add_filter( 'ppom_fields_with_html', $this->fields_with_html_filter );

		$this->assertContains( 'custom_html_key', Validator::fields_with_html() );
	}

	/**
	 * @return void
	 */
	public function test_safecss_filter_attr_allows_rgb() {
		$this->assertTrue( Validator::safecss_filter_attr( false, 'color: rgb(255, 0, 0);' ) );
		$this->assertTrue( Validator::safecss_filter_attr( false, 'background: rgba(0,0,0,0.5);' ) );
	}

	/**
	 * @return void
	 */
	public function test_safecss_filter_attr_respects_original_allow_when_no_rgb() {
		$this->assertTrue( Validator::safecss_filter_attr( true, 'margin: 0;' ) );
		$this->assertFalse( Validator::safecss_filter_attr( false, 'margin: 0;' ) );
	}
}
