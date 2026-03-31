<?php
/**
 * Seeds a PPOM field group containing pro-gated quantity and price-matrix fields.
 *
 * This runs through WP-CLI inside wp-env so the storefront E2E can cover the
 * runtime behavior even when the free admin builder hides these field types.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

global $args, $wpdb;

$suffix = isset( $args[0] ) ? sanitize_key( $args[0] ) : (string) time();
$product_name = 'PPOM Quantity Matrix Product';

$product_id = (int) $wpdb->get_var(
	$wpdb->prepare(
		"SELECT ID FROM {$wpdb->posts} WHERE post_type = %s AND post_status = %s AND post_title = %s ORDER BY ID DESC LIMIT 1",
		'product',
		'publish',
		$product_name
	)
);

if ( ! $product_id ) {
	$product = new WC_Product_Simple();
	$product->set_name( $product_name );
	$product->set_status( 'publish' );
	$product->set_catalog_visibility( 'visible' );
	$product->set_regular_price( '9.99' );
	$product_id = $product->save();
}

if ( ! $product_id ) {
	echo wp_json_encode(
		array(
			'status'  => 'error',
			'message' => 'Quantity matrix product could not be created',
		)
	);
	exit( 1 );
}

$fields = array(
	array(
		'type'     => 'pricematrix',
		'title'    => 'Matrix Pricing',
		'data_name'=> 'price_matrix_' . $suffix,
		'qty_step' => '2',
		'options'  => array(
			array(
				'option' => '2-2',
				'price'  => '8',
				'label'  => 'Qty 2',
				'id'     => 'qty_2',
			),
			array(
				'option' => '4-4',
				'price'  => '7',
				'label'  => 'Qty 4',
				'id'     => 'qty_4',
			),
		),
	),
	array(
		'type'       => 'quantities',
		'title'      => 'Seats',
		'data_name'  => 'seat_quantity_' . $suffix,
		'view_control' => 'simple_view',
		'min_qty'    => '2',
		'max_qty'    => '4',
		'options'    => array(
			array(
				'option'  => 'seat',
				'price'   => '',
				'weight'  => '',
				'default' => '0',
				'min'     => '0',
				'max'     => '4',
				'stock'   => '',
			),
		),
	),
);

$meta_name = 'Quantity Matrix ' . $suffix;
$ppom_table = $wpdb->prefix . PPOM_TABLE_META;

$wpdb->insert(
	$ppom_table,
	array(
		'productmeta_name'       => $meta_name,
		'productmeta_validation' => 'no',
		'dynamic_price_display'  => 'no',
		'send_file_attachment'   => '',
		'show_cart_thumb'        => 'no',
		'aviary_api_key'         => '',
		'productmeta_style'      => '',
		'productmeta_categories' => '',
		'the_meta'               => wp_json_encode( $fields ),
		'productmeta_created'    => current_time( 'mysql' ),
	)
);

$meta_id = (int) $wpdb->insert_id;

if ( ! $meta_id ) {
	echo wp_json_encode(
		array(
			'status'  => 'error',
			'message' => 'Unable to create PPOM meta row',
		)
	);
	exit( 1 );
}

$fields = apply_filters( 'ppom_meta_data_saving', $fields, $meta_id );
ppom_admin_update_ppom_meta_only( $meta_id, $fields );
update_post_meta( $product_id, PPOM_PRODUCT_META_KEY, $meta_id );

echo wp_json_encode(
	array(
		'status'            => 'success',
		'meta_id'           => $meta_id,
		'product_id'        => $product_id,
		'product_name'      => $product_name,
		'product_permalink' => get_permalink( $product_id ),
		'quantity_field_id' => 'seat_quantity_' . $suffix,
	)
);
