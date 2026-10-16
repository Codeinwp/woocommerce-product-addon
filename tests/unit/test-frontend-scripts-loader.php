<?php
/**
 * Tests for PPOM_FRONTEND_SCRIPTS::load_scripts_by_product_id().
 *
 * @package PPOM
 */

require_once __DIR__ . '/class-ppom-test-case.php';

class Test_Frontend_Scripts_Loader extends PPOM_Test_Case {

	/**
	 * A non-product ID that has PPOM meta (a stale product_id meta on a page)
	 * loads nothing instead of building a payload for a missing product.
	 */
	public function test_loader_skips_id_that_is_not_a_product(): void {
		$post_id = self::factory()->post->create();
		$this->insert_ppom_meta( array( $this->build_text_field( 'note' ) ), $post_id );

		$ran    = false;
		$filter = static function ( $vars ) use ( &$ran ) {
			$ran = true;
			return $vars;
		};
		add_filter( 'ppom_input_vars', $filter );

		try {
			$result = PPOM_FRONTEND_SCRIPTS::load_scripts_by_product_id( $post_id );
		} finally {
			remove_filter( 'ppom_input_vars', $filter );
		}

		$this->assertSame( '', $result );
		$this->assertFalse( $ran );
	}
}
