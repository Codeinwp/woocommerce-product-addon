<?php
/**
 * admin related functions/hooks
 *
 * @since 10.0
 **/

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not Allowed.' );
}

/**
 * Adding column in product list.
 *
 * @param array $columns Product list table columns.
 *
 * @return array
 */
function ppom_admin_show_product_meta( $columns ) {
	$last_column_key             = array_key_last( $columns );
	$last_column                 = array_pop( $columns );
	$columns['ppom_meta']        = __( 'PPOM', 'woocommerce-product-addon' );
	$columns[ $last_column_key ] = $last_column;
	return $columns;
}

function ppom_admin_product_meta_column( $column, $post_id ) {

	switch ( $column ) {

		case 'ppom_meta':
			$product_meta = '';
			$ppom         = new PPOM_Meta( $post_id );

			$ppom_settings_url = admin_url( 'admin.php?page=ppom' );

			if ( $ppom->has_multiple_meta() ) {
				$total_items = count( $ppom->meta_id ); // Get the total number of items.
				$current_item = 0; // Counter to track the current iteration.
                $has_fields = false;
				foreach ( $ppom->meta_id as $meta_id ) {
					$current_item++; // Increment the counter.

					$ppom_setting = $ppom->get_settings_by_id( $meta_id );
					if ( $ppom_setting ) {
						$meta_title = stripslashes( $ppom_setting->productmeta_name );
						$url_edit   = add_query_arg(
							array(
								'productmeta_id' => $ppom_setting->productmeta_id,
								'do_meta'        => 'edit',
							),
							$ppom_settings_url
						);
						echo sprintf( '<a href="%1$s">%2$s</a>', esc_url( $url_edit ), $meta_title );
						// Add a comma only if it's not the last item
						if ( $current_item < $total_items ) {
							echo ', ';
						}
                        $has_fields = true;
					}

				}
                if( ! $has_fields  ) {
                    echo sprintf( '<a class="btn button" href="%1$s">%2$s</a>', esc_url( $ppom_settings_url ), __( 'Add Fields', 'woocommerce-product-addon' ) );
                }
			} elseif ( $ppom->ppom_settings ) {
				$url_edit = add_query_arg(
					array(
						'productmeta_id' => $ppom->meta_id,
						'do_meta'        => 'edit',
					),
					$ppom_settings_url
				);
				echo sprintf( '<a href="%1$s">%2$s</a>', esc_url( $url_edit ), $ppom->meta_title );
			} else {
				echo sprintf( '<a class="btn button" href="%1$s">%2$s</a>', esc_url( $ppom_settings_url ), __( 'Add Fields', 'woocommerce-product-addon' ) );
			}

			break;

	}
}

function ppom_admin_product_meta_metabox() {

	add_meta_box( 'ppom-select-meta', __( 'Select PPOM Meta', 'woocommerce-product-addon' ), 'ppom_meta_list', 'product', 'side', 'default' );
}

function ppom_meta_list( $post ) {

	$ppom         = new PPOM_Meta( $post->ID );
	$all_meta     = PPOM()->get_product_meta_all();
	$ppom_setting = admin_url( 'admin.php?page=ppom' );
	
	$html  = '<div class="options_group ppom-settings-container" style="max-height:375px; overflow:auto;">';

	if ( count( $all_meta ) > 1 ) {
		// UP-SELL
		$html .= '<a class="ppom-upsell-link" target="_blank" href="' . tsdk_utmify( tsdk_translate_link( PPOM_UPGRADE_URL ), 'product-edit', 'metabox' ) . '">';
		$html .= '<span class="dashicons dashicons-external"></span> ';
		$html .= __( 'Using multiple PPOM field groups on the same product is available in PRO.', 'woocommerce-product-addon' );
		$html .= '</a>';
	}
	// PPOM Fields select table.
	$html .= '<table id="ppom_meta_sortable" class="wp-list-table widefat fixed striped">';
	//Hide search if we don't have many metas

	$html .= '<div class="ppom-search-meta" style="text-align: right;">';
	if ( count( $all_meta ) > 3 ) {
		$html .= '<input type="text" class="ppom-search-meta-js" placeholder="' . __( 'Search Group', 'woocommerce-product-addon' ) . '">';
	}
	$html .= '<a target="_blank" class="button button-primary" href="' . esc_url( $ppom_setting ) . '">' . __( 'Create New Field Group', 'woocommerce-product-addon' ) . '</a>';
	$html .= '</div>';

	$html .= '<thead><tr>';
	$html .= '<th>' . __( 'Select a Field Group', 'woocommerce-product-addon' ) . '</th>';
	$html .= '<th>' . __( 'Group ID', 'woocommerce-product-addon' ) . '</th>';
	$html .= '<th>' . __( 'Group Name', 'woocommerce-product-addon' ) . '</th>';
	$html .= '<th>' . __( 'Edit', 'woocommerce-product-addon' ) . '</th>';
	$html .= '</tr></thead>';
	
	foreach ( $all_meta as $meta ) {
		$html .= '<tr data-ppom-search="' . esc_attr( sanitize_key( $meta->productmeta_name ) ) . '" style="cursor: move;">';

		// Select/Checkbox
		$html .= '<td width="5%">';
		$html .= '<input name="ppom_product_meta" type="radio" style="cursor:auto;" value="' . esc_attr( $meta->productmeta_id ) . '" ';
		if (
			isset( $ppom->meta_id ) &&
			(
				(
					is_array( $ppom->meta_id ) &&
					in_array( $meta->productmeta_id, $ppom->meta_id )
				) ||
				(
					is_numeric( $ppom->meta_id ) &&
					$ppom->meta_id === $meta->productmeta_id
				)
			)
		) {
			$html .= ' checked ';
		}
		$html .= 'id="ppom-' . esc_attr( $meta->productmeta_id ) . '">';
		$html .= '</td>';

		// ID Column
		$html .= '<td width="5%">' . $meta->productmeta_id . '</td>';

		// Meta Name Column
		$html .= '<td width="50%">' . stripslashes( $meta->productmeta_name ) . '</td>';

		// Edit Meta Shortcut Column
		$url_edit = add_query_arg(
			array(
				'productmeta_id' => $meta->productmeta_id,
				'do_meta'        => 'edit',
			),
			$ppom_setting
		);

		$html .= '<td width="5%">';
		$html .= '<a target="_blank" style="font-weight:600; color:#0073aa" href="' . esc_url( $url_edit ) . '"><span class="dashicons dashicons-edit"></span></a>';
		$html .= '</td>';

		$html .= '</tr>';
	}

	$html .= '</table>';
	$html .= '</div>';

	$html .= '<hr>';
	$html .= '<div class="ppom-settings-container">';
	$html .= '<label class="ppom-settings-container-item ppom-disabled-text"><input type="checkbox" disabled>' . __( 'Enable Pop-up.', 'woocommerce-product-addon' ) . '<span class="woocommerce-help-tip" data-tip="' . __( 'Enable this option to display product fields in a popup instead of directly on the product page.', 'woocommerce-product-addon' ) . '"></span><i>' . sprintf(
		// translators: %1$s the opening link HTML tag, %2$s the close link HTML tag.
		__( 'Available in the %1$sPremium%2$s version.', 'woocommerce-product-addon' ),
		'<a target="_blank" href="' . tsdk_utmify( tsdk_translate_link( PPOM_UPGRADE_URL ), 'enable-popup', 'metabox' ) . '">',
		'</a>'
	) . '</i></label>';
	$html .= '</div>';

	$html .= '<hr>';
	$html .= '<div class="ppom-settings-container">';
	$html .= '<label class="ppom-settings-container-item ppom-disabled-text"><input disabled type="checkbox">' . __( 'Enable Enquiry Form', 'woocommerce-product-addon' ) . '<span class="woocommerce-help-tip" data-tip="' . __( 'Enhances your product pages by adding a customizable enquiry button. It allows customers to send inquiries directly to the admin about products with PPOM Fields via email.', 'woocommerce-product-addon' ) . '"></span><i>' . sprintf(
		// translators: %1$s the link to Store with label: 'Premium'.
		__( 'Available in the %1$s version.', 'woocommerce-product-addon' ),
		sprintf(
			'<a href="%s" target="_blank">%s</a>',
			esc_url( tsdk_utmify( tsdk_translate_link( PPOM_UPGRADE_URL ), 'enable-enquiry', 'metabox' ) ),
			__( 'Premium', 'woocommerce-product-addon' )
		)
		
	) . '</i></label>';
	$html .= '</div>';

	?>
	<script type="text/javascript">
		jQuery(function ($) {
			jQuery(document).on('keyup', '.ppom-search-meta-js', function (e) {
				e.preventDefault();

				var div = $(this).parent().parent();
				var search_val = $(this).val().toLowerCase();
				if (search_val != '') {
					div.find('#ppom_meta_sortable tbody tr').hide();
					div.find('#ppom_meta_sortable tbody tr[data-ppom-search*="' + search_val + '"]').show();
				} else {
					div.find('#ppom_meta_sortable tbody tr:hidden').show();
				}
			});

			$("#ppom_meta_sortable tbody").sortable();
		});
	</script>
	<?php

	echo apply_filters( 'ppom_select_meta_in_product', $html, $ppom, $all_meta );

	echo '<div class="ppom_extra_options_panel">';
	do_action( 'ppom_meta_box_after_list', $post );
	echo '</div>';
}

/*
 * saving meta data against product
 */
function ppom_admin_process_product_meta( $post_id ) {


	$ppom_meta_selected = isset( $_POST ['ppom_product_meta'] ) ? $_POST ['ppom_product_meta'] : array();
	
	if ( is_numeric( $ppom_meta_selected ) ) {
		$ppom_meta_selected = array( $ppom_meta_selected );
	} else if ( ! is_array( $ppom_meta_selected ) ) {
		$ppom_meta_selected = array();
	}
	
	$ppom_meta_selected = array_map( 'intval', $ppom_meta_selected );
	
	// ppom_pa($ppom_meta_selected); exit;
	update_post_meta( $post_id, PPOM_PRODUCT_META_KEY, $ppom_meta_selected );

	do_action( 'ppom_proccess_meta', $post_id );
}

// Show notices
function ppom_admin_show_notices() {

	if ( $resp_notices = get_transient( 'ppom_meta_imported' ) && function_exists( 'wc_print_notices' ) ) {
		?>
		<div id="message" class="<?php echo esc_attr( $resp_notices['class'] ); ?> updated notice is-dismissible">
			<p><?php wc_print_notices( $resp_notices['message'] ); ?></p>
			<button type="button" class="notice-dismiss">
				<span class="screen-reader-text"><?php _e( 'Dismiss this notice', 'woocommerce-product-addon' ); ?></span>
			</button>
		</div>
		<?php

		delete_transient( 'ppom_meta_imported' );
	}
}

/*
 * saving form meta in admin call
 */
function ppom_admin_save_form_meta() {

	$db_version = floatval( get_option( 'personalizedproduct_db_version' ) );

	if ( $db_version < 22.1 ) {
		$resp = array(
			'message' => __( 'Since version 22.0, Database has some changes. Please Deactivate & then activate the PPOM plugin.', 'woocommerce-product-addon' ),
			'status'  => 'error',
		);

		wp_send_json( $resp );
	}

	// print_r($_REQUEST); exit;

	if ( ! isset( $_POST['ppom_form_nonce'] )
		 || ! wp_verify_nonce( $_POST['ppom_form_nonce'], 'ppom_form_nonce_action' )
		 || ! ppom_security_role()
	) {
		$resp = array(
			'message' => __( 'Sorry, you are not allowed to perform this action.', 'woocommerce-product-addon' ),
			'status'  => 'error',
		);

		wp_send_json( $resp );
	}

	global $wpdb;

	extract( $_REQUEST );

	$send_file_attachment = 'NA';
	$aviary_api_key       = 'NA';
	$show_cart_thumb      = 'NA';

	if ( is_string( $_REQUEST['ppom'] ) ) {
		$ppom_encoded = $_REQUEST['ppom'];
		parse_str( $ppom_encoded, $ppom_decoded);
		$_REQUEST['ppom'] = $ppom_decoded['ppom'];
	}

	$ppom_meta    = ( isset($_REQUEST['ppom_meta']) ? $_REQUEST['ppom_meta'] : isset($_REQUEST['ppom']) ) ? $_REQUEST['ppom'] : '';

	if ( empty( $ppom_meta ) ) {
		$resp = array(
			'message'        => __( 'No fields found.', 'woocommerce-product-addon' ),
			'status'         => 'error',
		);
		wp_send_json( $resp );
	}

	$product_meta = apply_filters( 'ppom_meta_data_saving', (array) $ppom_meta, $productmeta_id );
	$product_meta = ppom_sanitize_array_data( $product_meta );
	$product_meta = array_filter(
		$product_meta,
		function( $pm ) {
			return ! empty( $pm['type'] ) || ! empty( $pm['data_name'] );
		}
	);
	$product_meta = json_encode( $product_meta );

	// sanitize
	$productmeta_name       = isset( $_REQUEST['productmeta_name'] ) ? sanitize_text_field( $_REQUEST['productmeta_name'] ) : '';
	$dynamic_price_hide     = isset( $_REQUEST['dynamic_price_hide'] ) ? sanitize_text_field( $_REQUEST['dynamic_price_hide'] ) : '';
	$send_file_attachment   = isset( $_REQUEST['send_file_attachment'] ) ? sanitize_text_field( $_REQUEST['send_file_attachment'] ) : '';
	$show_cart_thumb        = isset( $_REQUEST['show_cart_thumb'] ) ? sanitize_text_field( $_REQUEST['show_cart_thumb'] ) : '';
	$aviary_api_key         = isset( $_REQUEST['aviary_api_key'] ) ? sanitize_text_field( $_REQUEST['aviary_api_key'] ) : '';
	$productmeta_style      = isset( $_REQUEST['productmeta_style'] ) ? sanitize_text_field( $_REQUEST['productmeta_style'] ) : '';
	$productmeta_js         = isset( $_REQUEST['productmeta_js'] ) ? sanitize_text_field( $_REQUEST['productmeta_js'] ) : '';

	if ( strlen( $productmeta_name ) > 50 ) {
		$resp = array(
			'message' => __( 'PPOM title is too long to save, please make it less than 50 characters.', 'woocommerce-product-addon' ),
			'status'  => 'error',
		);

		wp_send_json( $resp );
	}

	$ppom_settings_meta_data = array(
		'productmeta_name'       => $productmeta_name,
		'dynamic_price_display'  => $dynamic_price_hide,
		'send_file_attachment'   => $send_file_attachment,
		'show_cart_thumb'        => $show_cart_thumb,
		'aviary_api_key'         => trim( $aviary_api_key ),
		'the_meta'               => $product_meta,
		'productmeta_created'    => current_time( 'mysql' ),
	);

	if ( ! ppom_is_legacy_user() ) {
		$ppom_settings_meta_data['productmeta_style'] = $productmeta_style;
		$ppom_settings_meta_data['productmeta_js']    = $productmeta_js;
	}

	$dt = apply_filters( 'ppom_settings_meta_data_new', $ppom_settings_meta_data );

	// wp_send_json($dt);

	$format = array(
		'%s',
		'%s',
		'%s',
		'%s',
		'%s',
		'%s',
		'%s',
	);

	global $wpdb;
	$ppom_table = $wpdb->prefix . PPOM_TABLE_META;
	$wpdb->insert( $ppom_table, $dt, $format );


	$ppom_id = $wpdb->insert_id;
	if ( is_string( $ppom ) ) {
		$ppom_encoded = $ppom;
		parse_str( $ppom_encoded, $ppom_decoded);
		$ppom = $ppom_decoded['ppom'];
	}

	$product_meta = apply_filters( 'ppom_meta_data_saving', (array) $ppom, $ppom_id );
	$product_meta = ppom_sanitize_array_data( $product_meta );
	$product_meta = array_filter(
		$product_meta,
		function( $pm ) {
			return ! empty( $pm['type'] ) && ! empty( $pm['data_name'] );
		}
	);
	
	// Updating PPOM Meta with ppom_id in each meta array
	ppom_admin_update_ppom_meta_only( $ppom_id, $product_meta );

	$redirect_to = '';

	if ( $ppom_id ) {
		$ppom_args   = array(
			'page'           => 'ppom',
			'productmeta_id' => $ppom_id,
			'do_meta'        => 'edit',
		);
		$redirect_to = add_query_arg( $ppom_args, admin_url( 'admin.php' ) );
	}


	if ( isset( $_REQUEST['product_id'] ) && $_REQUEST['product_id'] != '' ) {
		ppom_attach_fields_to_product( $ppom_id, intval( $_REQUEST['product_id'] ) );
		$redirect_to = get_permalink( $product_id );
	}

	$resp = array();
	if ( $ppom_id ) {

		$resp = array(
			'message'        => __( 'Form added successfully', 'woocommerce-product-addon' ),
			'status'         => 'success',
			'productmeta_id' => $ppom_id,
			'redirect_to'    => esc_url_raw( $redirect_to ),
		);
	} else {

		$resp = array(
			'message'        => __( 'No changes found.', 'woocommerce-product-addon' ),
			'status'         => 'success',
			'productmeta_id' => '',
		);
	}

	wp_send_json( $resp );
}

/*
 * updating form meta in admin call
 */
function ppom_admin_update_form_meta() {


	$return_page = isset( $_REQUEST['ppom_meta'] ) ? 'ppom-energy' : 'ppom';
	extract( $_REQUEST );

	$ppom_args   = array(
		'page'           => $return_page,
		'productmeta_id' => $productmeta_id,
		'do_meta'        => 'edit',
	);
	$redirect_to = add_query_arg( $ppom_args, admin_url( 'admin.php' ) );

	$db_version = floatval( get_option( 'personalizedproduct_db_version' ) );

	if ( $db_version < 22.1 ) {
		$resp = array(
			'message'        => __( 'Since version 22.0, Database has some changes. Please Deactivate & then activate the PPOM plugin.', 'woocommerce-product-addon' ),
			'status'         => 'error',
			'productmeta_id' => $productmeta_id,
			'redirect_to'    => esc_url_raw( $redirect_to ),
		);

		wp_send_json( $resp );
	}


	if ( ! isset( $_POST['ppom_form_nonce'] )
		 || ! wp_verify_nonce( $_POST['ppom_form_nonce'], 'ppom_form_nonce_action' )
		 || ! ppom_security_role()
	) {
		$resp = array(
			'message' => __( 'Sorry, you are not allowed to perform this action.', 'woocommerce-product-addon' ),
			'status'  => 'error',
		);

		wp_send_json( $resp );
	}
	global $wpdb;

	if ( is_string( $_REQUEST['ppom'] ) ) {
		$ppom_encoded = $_REQUEST['ppom'];
		parse_str( $ppom_encoded, $ppom_decoded);
		$_REQUEST['ppom'] = $ppom_decoded['ppom'];
	}

	$ppom_meta    = isset( $_REQUEST['ppom_meta'] ) ? $_REQUEST['ppom_meta'] : $_REQUEST['ppom'];
	$product_meta = apply_filters( 'ppom_meta_data_saving', (array) $ppom_meta, $productmeta_id );
	$product_meta = ppom_sanitize_array_data( $product_meta );
	// Remove the meta row if the type or data_name is empty.
	$product_meta = array_filter(
		$product_meta,
		function( $pm ) {
			return ! empty( $pm['type'] ) || ! empty( $pm['data_name'] );
		}
	);
	$product_meta = json_encode( $product_meta );
	
	$productmeta_name       = isset( $_REQUEST['productmeta_name'] ) ? sanitize_text_field( $_REQUEST['productmeta_name'] ) : '';
	$dynamic_price_hide     = isset( $_REQUEST['dynamic_price_hide'] ) ? sanitize_text_field( $_REQUEST['dynamic_price_hide'] ) : '';
	$send_file_attachment   = isset( $_REQUEST['send_file_attachment'] ) ? sanitize_text_field( $_REQUEST['send_file_attachment'] ) : '';
	$show_cart_thumb        = isset( $_REQUEST['show_cart_thumb'] ) ? sanitize_text_field( $_REQUEST['show_cart_thumb'] ) : '';
	$aviary_api_key         = isset( $_REQUEST['aviary_api_key'] ) ? sanitize_text_field( $_REQUEST['aviary_api_key'] ) : '';
	$productmeta_style      = isset( $_REQUEST['productmeta_style'] ) ? sanitize_text_field( $_REQUEST['productmeta_style'] ) : '';
	$productmeta_js         = isset( $_REQUEST['productmeta_js'] ) ? sanitize_text_field( $_REQUEST['productmeta_js'] ) : '';

	if ( strlen( $productmeta_name ) > 50 ) {
		$resp = array(
			'message' => __( 'PPOM title is too long to save, please make it less than 50 characters.', 'woocommerce-product-addon' ),
			'status'  => 'error',
		);

		wp_send_json( $resp );
	}

	$ppom_settings_meta_data = array(
		'productmeta_name'       => $productmeta_name,
		'dynamic_price_display'  => $dynamic_price_hide,
		'send_file_attachment'   => $send_file_attachment,
		'show_cart_thumb'        => $show_cart_thumb,
		'aviary_api_key'         => trim( $aviary_api_key ),
		'the_meta'               => $product_meta,
	);
	if ( ! ppom_is_legacy_user() ) {
		$ppom_settings_meta_data['productmeta_style'] = $productmeta_style;
		$ppom_settings_meta_data['productmeta_js']    = $productmeta_js;
	}

	$dt = apply_filters( 'ppom_settings_meta_data_update', $ppom_settings_meta_data, $productmeta_id );

	// wp_send_json($dt);

	$where = array(
		'productmeta_id' => $productmeta_id,
	);

	$format       = array(
		'%s',
		'%s',
		'%s',
		'%s',
		'%s',
		'%s',
		'%s',
		'%s',
		'%s',
	);
	$where_format = array(
		'%d',
	);

	global $wpdb;
	$ppom_table    = $wpdb->prefix . PPOM_TABLE_META;
	$rows_effected = $wpdb->update( $ppom_table, $dt, $where, $format, $where_format );

	// $wpdb->show_errors(); $wpdb->print_error();

	$return_page = isset( $_REQUEST['ppom_meta'] ) ? 'ppom-energy' : 'ppom';

	$ppom_args   = array(
		'page'           => $return_page,
		'productmeta_id' => $productmeta_id,
		'do_meta'        => 'edit',
	);
	$redirect_to = add_query_arg( $ppom_args, admin_url( 'admin.php' ) );

	$resp = array();
	if ( $rows_effected ) {

		$resp = array(
			'message'        => __( 'Form updated successfully', 'woocommerce-product-addon' ),
			'status'         => 'success',
			'productmeta_id' => $productmeta_id,
			'redirect_to'    => esc_url_raw( $redirect_to ),
		);
	} else {

		$resp = array(
			'message'        => __( 'Form updated successfully.', 'woocommerce-product-addon' ),
			'status'         => 'success',
			'productmeta_id' => $productmeta_id,
			'redirect_to'    => esc_url_raw( $redirect_to ),
		);
	}

	wp_send_json( $resp );
}

// Update PPOM Fields Only
function ppom_admin_update_ppom_meta_only( $ppom_id, $ppom_meta ) {

	// print_r($_REQUEST); exit;
	global $wpdb;

	$dt = array(
		'the_meta' => wp_json_encode( ppom_sanitize_array_data( $ppom_meta ) ),
	);

	// ppom_pa($dt); exit;

	$where = array(
		'productmeta_id' => $ppom_id,
	);

	$format       = array(
		'%s',
	);
	$where_format = array(
		'%d',
	);

	global $wpdb;
	$ppom_table    = $wpdb->prefix . PPOM_TABLE_META;
	$rows_effected = $wpdb->update( $ppom_table, $dt, $where, $format, $where_format );

	// $wpdb->show_errors(); $wpdb->print_error();

	if ( $rows_effected ) {

		return true;
	} else {
		return false;
	}

}

/*
 * delete meta
 */
function ppom_admin_delete_meta() {

	if ( ! isset( $_POST['ppom_meta_nonce'] )
		 || ! wp_verify_nonce( $_POST['ppom_meta_nonce'], 'ppom_meta_nonce_action' )
		 || ! ppom_security_role()
	) {
		$response = array(
			'status'  => 'error',
			'message' => __( 'Sorry, you are not allowed to perform this action please try again', 'woocommerce-product-addon' ),
		);

		wp_send_json( $response );
	}

	global $wpdb;

	extract( $_REQUEST );

	$tbl_name = $wpdb->prefix . PPOM_TABLE_META;
	$ppom_id  = intval( $productmeta_id );
	$res      = $wpdb->query( $wpdb->prepare( "DELETE FROM {$tbl_name} WHERE productmeta_id = %d", $productmeta_id ) );


	$response = [];
	if ( $res ) {
		$response = array(
			'status'  => 'success',
			'message' => __( 'Meta deleted successfully', 'woocommerce-product-addon' ),
		);
	} else {
		$response = array(
			'status'  => 'error',
			'message' => __( 'Error while deleting the PPOM, try again.', 'woocommerce-product-addon' ),
		);
	}

	wp_send_json( $response );
}

/*
 * delete meta
 */
function ppom_admin_delete_selected_meta() {

	if ( ! isset( $_POST['ppom_meta_nonce'] )
		 || ! wp_verify_nonce( $_POST['ppom_meta_nonce'], 'ppom_meta_nonce_action' )
		 || ! ppom_security_role()
		 || ! array_key_exists( 'productmeta_ids', $_POST )
		 || ! is_array( $_POST['productmeta_ids'] )
	) {
		_e( 'Sorry, you are not allowed to perform this action', 'woocommerce-product-addon' );
		die( 0 );
	}

	global $wpdb;

	$del_ids = [];
	$del_ids_ph = [];

	// for the performance wise, prefer to use foreach instead of array_map-array_filter-array_fill stack.
	foreach( $_POST['productmeta_ids'] as $id ) {
		$id = absint( $id );

		if( 0 === $id ) {
			continue;
		}

		$del_ids[] = $id;
		$del_ids_ph[] = '%d';
	}

	$del_ids_ph = implode( ', ', $del_ids_ph );

	$tbl_name        = $wpdb->prefix . PPOM_TABLE_META;

	$res = $wpdb->query( $wpdb->prepare( "DELETE FROM {$tbl_name} WHERE productmeta_id IN ({$del_ids_ph})", $del_ids ) );

	if ( $res ) {
		_e( 'Meta deleted successfully', 'woocommerce-product-addon' );
	} else {
		$wpdb->show_errors();
		$wpdb->print_error();
	}

	die( 0 );
}


/*
 * simplifying meta for admin view in existing-meta.php
 */
function ppom_admin_simplify_meta( $meta ) {
	// echo $meta;
	$metas = json_decode( $meta );

	$html = '';
	if ( $metas ) {
		$html .= '<ul>';
		foreach ( $metas as $meta => $data ) {

			// ppom_pa($data);
			$req     = ( isset( $data->required ) && $data->required == 'on' ) ? 'yes' : 'no';
			$title   = ( isset( $data->title ) ? $data->title : '' );
			$type    = ( isset( $data->type ) ? $data->type : '' );
			$options = ( isset( $data->options ) ? $data->options : '' );

			$html .= '<li>';
			$html .= '<strong>label:</strong> ' . esc_html( $title );
			$html .= ' | <strong>type:</strong> ' . esc_html( $type );

			if ( ! is_object( $options ) && is_array( $options ) ) {
				$html .= ' | <strong>options:</strong> ';
				foreach ( $options as $option ) {

					$display_info = '';
					if ( isset( $option->option ) ) {
						$display_info = $option->option;
					} elseif ( isset( $option->width ) ) {
						$display_info = $option->width . 'x' . $option->height;
					}

					if ( empty( $option->price ) ) {
						$html .= esc_html( $display_info ) . ', ';
					} else {
						$html .= esc_html( $display_info ) . ' (' . $option->price . '), ';
					}
				}
			}


			$html .= ' | <strong>required:</strong> ' . esc_html( $req );
			$html .= '</li>';
		}

		$html .= '</ul>';
	}

	return $html;
}

// Showing PPOM Edit on Product Page
function ppom_admin_bar_menu() {

	if ( ! is_product() ) {
		return;
	}

	global $wp_admin_bar, $product;

	$product_id = ppom_get_product_id( $product );
	$ppom       = new PPOM_Meta( $product_id );

	if ( ! $ppom->is_exists ) {
		return;
	}

	$ppom_setting_url = admin_url( 'admin.php' );
	$ppom_setting_url = add_query_arg(
		array(
			'page'           => 'ppom',
			'productmeta_id' => $ppom->single_meta_id,
			'do_meta'        => 'edit',
		),
		$ppom_setting_url
	);

	$bar_title = "Edit PPOM ({$ppom->meta_title})";
	$wp_admin_bar->add_node(
		array(
			'id'    => 'ppom-setting-bar',
			'title' => $bar_title,
			'href'  => esc_url( $ppom_setting_url ),
		)
	);

	$all_meta = PPOM()->get_product_meta_all();
	foreach ( $all_meta as $meta ) {

		$apply_link = admin_url( 'admin-post.php' );
		$apply_arg  = array(
			'productid' => $product_id,
			'metaid'    => $meta->productmeta_id,
			'metatitle' => $meta->productmeta_name,
			'action'    => 'ppom_attach',
		);
		$apply_link = add_query_arg( $apply_arg, $apply_link );
		$bar_title  = "Apply {$meta->productmeta_name}";
		$wp_admin_bar->add_node(
			array(
				'id'     => "ppom-setting-bar-{$meta->productmeta_id}",
				'title'  => $bar_title,
				'href'   => esc_url( $apply_link ),
				'parent' => 'ppom-setting-bar',
			)
		);
	}
}
