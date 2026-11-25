<?php
/**
 * Tests for the ppom/get-order-selections ability output.
 *
 * @package ppom-pro
 */

require_once dirname( __DIR__, 2 ) . '/class-ppom-test-case.php';

use PPOM\Abilities\Abilities;

/**
 * @covers \PPOM\Abilities\Abilities::get_order_selections
 */
class Test_Abilities_Order_Selections extends PPOM_Test_Case {

	/**
	 * @return void
	 */
	public function test_order_selections_return_plain_text_without_html_entities(): void {
		$product = $this->create_simple_product();
		$meta_id = $this->insert_ppom_meta(
			array(
				array(
					'type'      => 'quantities',
					'title'     => 'Sizes',
					'data_name' => 'sizes',
				),
			),
			$product->get_id()
		);

		$order = $this->create_order_with_product( $product );
		$items = $order->get_items();
		$item  = reset( $items );
		$item->update_meta_data( 'sizes', 'stored' );
		$item->update_meta_data(
			'_ppom_fields',
			array(
				'fields' => array(
					'id'    => (string) $meta_id,
					'sizes' => array( "Men's S & M" => '1' ),
				),
			)
		);
		$item->save();

		$result = ( new Abilities() )->get_order_selections( array( 'order_id' => $order->get_id() ) );

		$this->assertIsArray( $result );
		$encoded = (string) wp_json_encode( $result );
		$this->assertStringContainsString( "Men's S & M = 1", $encoded );
		$this->assertStringNotContainsString( '&amp;', $encoded );
		$this->assertStringNotContainsString( '&#039;', $encoded );
	}
}
