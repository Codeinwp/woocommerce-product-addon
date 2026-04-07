<?php
/**
 * Orders: line item meta, display, email, upload finalization, order again.
 *
 * @package PPOM
 * @subpackage WooCommerce
 *
 * @see woocommerce.php Parent loader under inc/.
 */

namespace PPOM\WooCommerce\Order;

use PPOM\Files\Handler;
use PPOM\Support\Helpers;

/**
 * @internal
 */
final class OrderHandler {

	// Order persistence.

	/**
	 * Stores PPOM metadata on an order line item.
	 *
	 * Saves formatted display values as line-item meta and preserves the raw PPOM
	 * payload in `_ppom_fields` for later formatting and replay.
	 *
	 * @param WC_Order_Item_Product $item          Order line item.
	 * @param string                $cart_item_key Cart item key.
	 * @param array                 $values        Cart item values.
	 * @param WC_Order              $order         Order being created.
	 *
	 * @return void
	 *
	 * @see Helpers::make_meta_data()
	 * @see Helpers::get_field_meta_by_dataname()
	 * @see self::order_value()
	 */
	public static function order_item_meta( $item, $cart_item_key, $values, $order ) {

		if ( ! isset( $values ['ppom']['fields'] ) ) {
			return;
		}
		// ADDED WC BUNDLES COMPATIBILITY
		if ( function_exists( 'wc_pb_is_bundled_cart_item' ) && wc_pb_is_bundled_cart_item( $values ) ) {
			return;
		}

		$ppom_meta = Helpers::make_meta_data( $values, 'order' );
		// ppom_pa($item->get_product_id()); exit;

		$cropper_fields = array();
		foreach ( $ppom_meta as $key => $meta ) {

			if ( ! isset( $meta['value'] ) ) {
				continue;
			}

			// WPML
			$meta_key = Helpers::wpml_translate( $key, 'PPOM' );

			$meta_value = isset( $meta['display'] ) ? $meta['display'] : $meta['value'];
			$item->update_meta_data( $key, $meta_value );

			// Since 24.5: Removing the image cropper base64 data
			// Reason: https://clients.najeebmedia.com/forums/topic/order-search-in-woocommerce-not-working/
			$meta = Helpers::get_field_meta_by_dataname( $item->get_product_id(), $key );
			if ( isset( $meta['type'] ) && $meta['type'] == 'cropper' ) {
				$cropper_fields[] = $key;
			}
		}

		$no_base64                = array_diff_key( $values ['ppom']['fields'], array_flip( $cropper_fields ) );
		$values['ppom']['fields'] = $no_base64;

		// Since 15.2, saving all fields as another meta
		$item->update_meta_data( '_ppom_fields', $values ['ppom'] );
	}

	// Changing order item meta key to label
	public static function order_key( $display_key, $meta, $item ) {

		if ( $item->get_type() != 'line_item' ) {
			return $display_key;
		}

		$field_meta = Helpers::get_field_meta_by_dataname( $item->get_product_id(), $display_key );
		if ( isset( $field_meta['title'] ) && $field_meta['title'] != '' ) {
			$display_key = stripslashes( $field_meta['title'] );
		}

		return $display_key;
	}

	/**
	 * Formats PPOM order item meta for display.
	 *
	 * @param mixed              $display_value Formatted value from WooCommerce.
	 * @param object|null        $meta          Order item meta object.
	 * @param WC_Order_Item|null $item   Order item.
	 *
	 * @return mixed
	 *
	 * @see Helpers::generate_html_for_files()
	 * @see Helpers::get_field_meta_by_dataname()
	 * @see self::order_item_meta()
	 */
	public static function order_value( $display_value, $meta = null, $item = null ) {

		if ( is_null( $item ) ) {
			return $display_value;
		}

		if ( $item->get_type() != 'line_item' ) {
			return $display_value;
		}

		$field_meta = Helpers::get_field_meta_by_dataname( $item->get_product_id(), $meta->key );

		// if( ! isset($field_meta['type']) ) return $display_value;

		$input_type = isset( $field_meta['type'] ) ? $field_meta['type'] : '';

		switch ( $input_type ) {

			case 'file':
			case 'cropper':
				/**
				 * File upload and croppers now save only filename in meta
				 * seperated by commas, now here we will build it's html to show thumbs in item orde
				 *
				 * @since: 10.10
				 */
				$display_value = Helpers::generate_html_for_files( $meta->value, $input_type, $item );
				break;

			case 'image':
				$display_value = $meta->value;
				break;

			default:
				// Important hook: changing order value format using local hooks
				// Also being used for export order lite
				$display_value = apply_filters( 'ppom_order_display_value', $display_value, $meta, $item );
				break;

		}

		return $display_value;
	}


	// Hiding some ppom meta like ppom_has_quantities
	public static function hide_order_meta( $formatted_meta, $order_item ) {

		if ( empty( $formatted_meta ) ) {
			return $formatted_meta;
		}

		$ppom_meta_searching = $formatted_meta;
		// ppom_has_quantities
		foreach ( $ppom_meta_searching as $meta_id => $meta_data ) {

			if ( $meta_data->key == 'ppom_has_quantities' ) {
				unset( $formatted_meta[ $meta_id ] );
			}
		}

		return $formatted_meta;
	}

	// Upload finalization.

	/**
	 * Moves uploaded PPOM files into the confirmed order directory.
	 *
	 * File names and destination paths are resolved on the server from the cart
	 * payload and the saved field schema.
	 *
	 * @param int      $order_id    Order ID.
	 * @param mixed    $posted_data Posted checkout data.
	 * @param WC_Order $order       Processed order.
	 *
	 * @return void
	 *
	 * @see Handler::get_dir_path()
	 * @see Helpers::get_field_meta_by_dataname()
	 * @see Handler::get_file_download_url()
	 */
	public static function rename_files( $order_id, $posted_data, $order ) {

		global $woocommerce;

		// getting product id in cart
		$cart = WC()->cart->get_cart();

		// ppom_pa($cart); exit;


		// since 8.1, files will be send to email as attachment

		// ppom_pa($cart); exit;
		foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {

			// ppom_pa($cart_item); exit;
			if ( ! isset( $cart_item['ppom']['fields'] ) ) {
				continue;
			}

			$product_id      = $cart_item['product_id'];
			$all_moved_files = array();

			foreach ( $cart_item['ppom']['fields'] as $key => $values ) {

				if ( $key == 'id' ) {
					continue;
				}

				$field_meta = Helpers::get_field_meta_by_dataname( $product_id, $key );
				if ( ! is_array( $field_meta ) ) {
					continue;
				}

				$field_type  = $field_meta['type'];
				$field_label = isset( $field_meta['title'] ) ? $field_meta['title'] : $field_meta['data_name'];
				$moved_files = array();

				if ( $field_type == 'file' || $field_type == 'cropper' ) {

					$base_dir_path      = Handler::get_dir_path();
					$confirm_dir        = 'confirmed/' . $order_id;
					$confirmed_dir_path = Handler::get_dir_path( $confirm_dir );
					$edits_dir_path     = Handler::get_dir_path( 'edits' );

					foreach ( $values as $file_id => $file_data ) {
						if ( ! isset( $file_data['org'] ) ) {
							continue;
						}
						$file_name    = $file_data['org'];
						$file_cropped = isset( $file_data['cropped'] ) ? true : false;

						$new_filename     = Handler::file_get_name( $file_name, $product_id, $cart_item );
						$source_file      = $base_dir_path . $file_name;
						$destination_path = $confirmed_dir_path . $new_filename;


						if ( file_exists( $destination_path ) ) {
							break;
						}

						/*
						$moved_files[] = array('path' => $destination_path,
											'file_name' => $file_name,
											'product_id' => $product_id);*/

						if ( file_exists( $source_file ) ) {

							if ( ! rename( $source_file, $destination_path ) ) {
								die( 'Error while re-naming order image ' . $source_file );
							}
						}

						// renaming edited files
						$source_file_edit      = $edits_dir_path . $file_name;
						$destination_path_edit = '';

						$file_edited = false;
						if ( file_exists( $source_file_edit ) ) {

							$destination_path_edit = $edits_dir_path . $new_filename;
							if ( ! rename( $source_file_edit, $destination_path_edit ) ) {
								die( 'Error while re-naming order image ' . $source_file_edit );
							} else {
								$file_edited = true;
							}
						}

						$moved_files[] = array(
							'path'           => $destination_path,
							'file_name'      => $file_name,
							'file_label'     => $field_label,
							'file_cropped'   => $file_cropped,
							'file_edited'    => $file_edited,
							'file_edit_path' => $destination_path_edit,
							'product_id'     => $product_id,
							'field_name'     => $key,
						);

						// $moved_files['file_edited'] = $file_edited;
					}

					$all_moved_files[ $key ] = $moved_files;
				}
			}

			do_action( 'ppom_after_files_moved', $all_moved_files, $order_id, $order );
		}
	}

	/**
	 * Responsible from the adding a support for Order Again functionality in the WooCommerce My Account -> Order View page.
	 * The method adds PPOM Fields to the given order item from the provided order. (Clones the PPOM data of data order item to the new cart)
	 *
	 * @param  array                  $cart_item_data Current custom item data.
	 * @param  \WC_Order_Item_Product $item Order Item Product
	 * @param  \WC_Order              $order
	 * @return void
	 */
	public static function wc_order_again_compatibility( $cart_item_data, $item, $order ) {
		$ppom_data = $item->get_meta( '_ppom_fields' );

		if ( is_array( $ppom_data ) && array_key_exists( 'fields', $ppom_data ) ) {
			$cart_item_data['ppom'] = $ppom_data;
		}

		return $cart_item_data;
	}

	/**
	 * Outputs the formatted meta data for WooCommerce order items.
	 *
	 * @param int                    $item_id The item ID.
	 * @param \WC_Order_Item_Product $item The order item object.
	 */
	public static function order_item_meta_html( $item_id, $item ) {
		$formatted_meta = $item->get_formatted_meta_data();

		$strings        = array();
		$meta_item_html = '';
		$output_args    = apply_filters(
			'ppom_woocommerce_item_meta_args',
			array(
				'before'       => '<ul class="wc-item-meta"><li>',
				'after'        => '</li></ul>',
				'separator'    => '</li><li>',
				'label_before' => '<strong class="wc-item-meta-label">',
				'label_after'  => ':</strong> ',
			)
		);
		foreach ( $formatted_meta as $meta ) {
			$strings[] = $output_args['label_before'] . wp_kses_post( $meta->display_key ) . $output_args['label_after'] . self::order_value( $meta->display_value, $meta, $item );
		}

		if ( $strings ) {
			$meta_item_html = $output_args['before'] . implode( $output_args['separator'], $strings ) . $output_args['after'];
		}
		echo wp_kses_post( $meta_item_html );
	}

	/**
	 * Check if the email improvements feature is enabled.
	 *
	 * @return bool
	 */
	public static function wc_email_improvements_enabled() {
		return 'yes' === get_option( 'woocommerce_feature_email_improvements_enabled', 'no' );
	}

	/**
	 * Outputs the formatted meta data for invoice or packing slips.
	 *
	 * @param string                 $html HTML of the item meta data 
	 * @param \WC_Order_Item_Product $item The order item object.
	 * @param array                  $args arguments for display the html.
	 */
	public static function invoice_packing_slips_html( $html, $item, $args = array() ) {
		$strings = array();
		$args    = wp_parse_args(
			$args,
			array(
				'before'       => '<ul class="wc-item-meta"><li>',
				'after'        => '</li></ul>',
				'separator'    => '</li><li>',
				'echo'         => true,
				'autop'        => false,
				'label_before' => '<strong class="wc-item-meta-label">',
				'label_after'  => ':</strong> ',
			)
		);

		foreach ( $item->get_all_formatted_meta_data() as $meta_id => $meta ) {
			$meta_value = self::order_value( $meta->display_value, $meta, $item );
			$value      = $args['autop'] ? wp_kses_post( $meta_value ) : wp_kses_post( make_clickable( trim( $meta_value ) ) );
			$strings[]  = $args['label_before'] . wp_kses_post( $meta->display_key ) . $args['label_after'] . $value;
		}

		if ( $strings ) {
			$html = $args['before'] . implode( $args['separator'], $strings ) . $args['after'];
		}

		return $html;
	}
}
