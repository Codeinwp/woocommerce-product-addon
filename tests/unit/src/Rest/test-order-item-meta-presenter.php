<?php
/**
 * Unit tests for PPOM\Rest\OrderItemMetaPresenter.
 *
 * @package ppom-pro
 */

use PPOM\Rest\OrderItemMetaPresenter;

/**
 * @covers \PPOM\Rest\OrderItemMetaPresenter
 */
class Test_Order_Item_Meta_Presenter extends PPOM_Test_Case {

	/**
	 * @return void
	 */
	public function test_get_order_item_meta_empty_order_returns_empty_list() {
		$order      = wc_create_order();
		$order->save();
		$presenter = new OrderItemMetaPresenter();

		$this->assertSame( array(), $presenter->get_order_item_meta( $order->get_id() ) );
	}

	/**
	 * @return void
	 */
	public function test_get_order_item_meta_single_line_has_product_id_and_meta_array() {
		$product   = $this->create_simple_product();
		$order     = $this->create_order_with_product( $product, 1 );
		$presenter = new OrderItemMetaPresenter();
		$data      = $presenter->get_order_item_meta( $order->get_id() );

		$this->assertCount( 1, $data );
		$this->assertSame( $product->get_id(), $data[0]['product_id'] );
		$this->assertIsArray( $data[0]['product_meta_data'] );
	}
}
