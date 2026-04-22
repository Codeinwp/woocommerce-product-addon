<?php
/**
 * Unit tests for selected PPOM\Support\Helpers methods.
 *
 * @package ppom-pro
 */

use PPOM\Support\Helpers;

/**
 * @covers \PPOM\Support\Helpers
 */
class Test_Helpers_Src extends PPOM_Test_Case {

	/**
	 * @var callable|null
	 */
	private $field_col_filter;

	/**
	 * @return void
	 */
	public function tearDown(): void {
		if ( null !== $this->field_col_filter ) {
			remove_filter( 'ppom_field_col', $this->field_col_filter );
			$this->field_col_filter = null;
		}
		parent::tearDown();
	}

	/**
	 * @return void
	 */
	public function test_get_field_colum_defaults_to_twelve() {
		$this->assertSame( 12, Helpers::get_field_colum( array() ) );
	}

	/**
	 * @return void
	 */
	public function test_get_field_colum_respects_numeric_width() {
		$this->assertSame( 6, Helpers::get_field_colum( array( 'width' => 6 ) ) );
	}

	/**
	 * @return void
	 */
	public function test_get_field_colum_percent_width_falls_back_to_twelve() {
		$this->assertSame( 12, Helpers::get_field_colum( array( 'width' => '50%' ) ) );
	}

	/**
	 * @return void
	 */
	public function test_get_field_colum_clamps_above_twelve() {
		$this->assertSame( 12, Helpers::get_field_colum( array( 'width' => 24 ) ) );
	}

	/**
	 * @return void
	 */
	public function test_get_field_colum_apply_filters_ppom_field_col() {
		$this->field_col_filter = static function ( $col ) {
			return 4;
		};
		add_filter( 'ppom_field_col', $this->field_col_filter, 10, 1 );

		$this->assertSame( 4, Helpers::get_field_colum( array( 'width' => 8 ) ) );
	}

	/**
	 * @return void
	 */
	public function test_get_product_id_simple_product() {
		$product = $this->create_simple_product();

		$this->assertSame( $product->get_id(), Helpers::get_product_id( $product ) );
	}

	/**
	 * @return void
	 */
	public function test_get_product_id_variation_returns_parent() {
		$pair = $this->create_variable_product_with_variation();

		$this->assertSame( $pair['product']->get_id(), Helpers::get_product_id( $pair['variation'] ) );
	}

	/**
	 * @return void
	 */
	public function test_wpml_translate_passes_through_arrays() {
		$arr = array( 'a' => 1 );
		$this->assertSame( $arr, Helpers::wpml_translate( $arr, 'PPOM' ) );
	}
}
