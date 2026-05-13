<?php
/**
 * Unit tests for PPOM\Admin\Manager.
 *
 * @package ppom-pro
 */

use PPOM\Admin\Manager;

/**
 * @covers \PPOM\Admin\Manager
 */
class Test_Admin_Manager extends WP_UnitTestCase {

	/**
	 * @return void
	 */
	public function test_show_product_meta_inserts_ppom_before_last_column() {
		$columns = array(
			'thumb' => '<span>Thumb</span>',
			'name'  => 'Name',
			'sku'   => 'SKU',
		);

		$updated = Manager::show_product_meta( $columns );

		$this->assertArrayHasKey( 'ppom_meta', $updated );
		$this->assertSame( 'SKU', $updated['sku'] );
		$this->assertArrayHasKey( 'name', $updated );
		$keys = array_keys( $updated );
		$this->assertSame( 'sku', end( $keys ) );
	}
}
