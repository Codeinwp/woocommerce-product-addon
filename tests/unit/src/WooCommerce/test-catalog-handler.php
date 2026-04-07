<?php
/**
 * Unit tests for PPOM\WooCommerce\Catalog\CatalogHandler helpers.
 *
 * @package ppom-pro
 */

use PPOM\WooCommerce\Catalog\CatalogHandler;

/**
 * @covers \PPOM\WooCommerce\Catalog\CatalogHandler
 */
class Test_Catalog_Handler extends PPOM_Test_Case {

	/**
	 * @return void
	 */
	public function test_price_matrix_range_parts_splits_on_first_dash() {
		$this->assertSame(
			array( 'min' => '1', 'max' => '10' ),
			CatalogHandler::price_matrix_range_parts( '1-10' )
		);
	}

	/**
	 * @return void
	 */
	public function test_price_matrix_range_parts_trims_segments() {
		$this->assertSame(
			array( 'min' => '2', 'max' => '20' ),
			CatalogHandler::price_matrix_range_parts( ' 2 - 20 ' )
		);
	}

	/**
	 * @return void
	 */
	public function test_price_matrix_range_parts_single_segment_has_null_max() {
		$this->assertSame(
			array( 'min' => '5', 'max' => null ),
			CatalogHandler::price_matrix_range_parts( '5' )
		);
	}

	/**
	 * @return void
	 */
	public function test_price_matrix_range_parts_uses_second_segment_only_when_multiple_dashes() {
		$this->assertSame(
			array( 'min' => '1', 'max' => '2' ),
			CatalogHandler::price_matrix_range_parts( '1-2-3' )
		);
	}

	/**
	 * @return void
	 */
	public function test_apply_quantities_min_floor_picks_highest_required_min() {
		$out = CatalogHandler::apply_quantities_min_floor(
			2,
			array(
				array( 'min_qty' => '' ),
				array( 'min_qty' => '5' ),
				array( 'min_qty' => '3' ),
			)
		);

		$this->assertSame( '5', $out );
	}

	/**
	 * @return void
	 */
	public function test_apply_quantities_max_ceiling_picks_highest_allowed_max() {
		$out = CatalogHandler::apply_quantities_max_ceiling(
			10,
			array(
				array( 'max_qty' => '50' ),
				array( 'max_qty' => '30' ),
			)
		);

		$this->assertSame( '50', $out );
	}

	/**
	 * @return void
	 */
	public function test_normalized_price_matrix_qty_step_defaults_to_one() {
		$this->assertSame( 1, CatalogHandler::normalized_price_matrix_qty_step( null ) );
		$this->assertSame( 1, CatalogHandler::normalized_price_matrix_qty_step( '' ) );
		$this->assertSame( 1, CatalogHandler::normalized_price_matrix_qty_step( 0 ) );
	}

	/**
	 * @return void
	 */
	public function test_normalized_price_matrix_qty_step_preserves_non_empty() {
		$this->assertSame( 3, CatalogHandler::normalized_price_matrix_qty_step( 3 ) );
		$this->assertSame( '2', CatalogHandler::normalized_price_matrix_qty_step( '2' ) );
	}
}
