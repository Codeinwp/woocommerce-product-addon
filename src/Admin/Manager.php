<?php
/**
 * Registers PPOM admin product and field-group management callbacks.
 *
 * @package PPOM
 * @subpackage Admin
 */

namespace PPOM\Admin;

use PPOM_Meta;
use PPOM\Meta\MetaRepositoryAccessor;
use PPOM\Support\Helpers;
use PPOM\Validation\Validator;

/**
 * @internal
 */
final class Manager {

	// Product edit integration.

	/**
	 * Adding column in product list.
	 *
	 * @param array $columns Product list table columns.
	 *
	 * @return array
	 */
	public static function show_product_meta( $columns ) {
		$last_column_key             = array_key_last( $columns );
		$last_column                 = array_pop( $columns );
		$columns['ppom_meta']        = __( 'PPOM', 'woocommerce-product-addon' );
		$columns[ $last_column_key ] = $last_column;
		return $columns;
	}

	public static function product_meta_column( $column, $post_id ) {

		switch ( $column ) {

			case 'ppom_meta':
				$product_meta = '';
				$ppom         = new PPOM_Meta( $post_id );

				$ppom_settings_url = admin_url( 'admin.php?page=ppom' );

				if ( $ppom->has_multiple_meta() ) {
					$total_items  = count( $ppom->meta_id ); // Get the total number of items.
					$current_item = 0; // Counter to track the current iteration.
					$has_fields   = false;
					$settings_map = $ppom->get_settings_by_ids( (array) $ppom->meta_id );
					foreach ( $ppom->meta_id as $meta_id ) {
						++$current_item; // Increment the counter.

						$mid          = absint( $meta_id );
						$ppom_setting = ( $mid > 0 && isset( $settings_map[ $mid ] ) ) ? $settings_map[ $mid ] : null;
						if ( $ppom_setting ) {
							$meta_title = stripslashes( $ppom_setting->productmeta_name );
							$url_edit   = add_query_arg(
								array(
									'productmeta_id' => $ppom_setting->productmeta_id,
									'do_meta'        => 'edit',
								),
								$ppom_settings_url
							);
							printf( '<a href="%1$s">%2$s</a>', esc_url( $url_edit ), $meta_title );
							// Add a comma only if it's not the last item
							if ( $current_item < $total_items ) {
								echo ', ';
							}
							$has_fields = true;
						}               
					}
					if ( ! $has_fields ) {
						printf( '<a class="btn button" href="%1$s">%2$s</a>', esc_url( $ppom_settings_url ), __( 'Add Fields', 'woocommerce-product-addon' ) );
					}
				} elseif ( $ppom->ppom_settings ) {
					$url_edit = add_query_arg(
						array(
							'productmeta_id' => $ppom->meta_id,
							'do_meta'        => 'edit',
						),
						$ppom_settings_url
					);
					printf( '<a href="%1$s">%2$s</a>', esc_url( $url_edit ), $ppom->meta_title );
				} else {
					printf( '<a class="btn button" href="%1$s">%2$s</a>', esc_url( $ppom_settings_url ), __( 'Add Fields', 'woocommerce-product-addon' ) );
				}

				break;

		}
	}

	public static function product_meta_metabox() {

		add_meta_box( 'ppom-select-meta', __( 'Select PPOM Meta', 'woocommerce-product-addon' ), 'ppom_meta_list', 'product', 'side', 'default' );
	}

	/**
	 * Renders the product edit metabox for selecting a PPOM field group.
	 *
	 * Reads the current product assignment from {@see PPOM_Meta} and lists every
	 * available group returned by {@see PPOM()} for attachment.
	 *
	 * @param WP_Post $post Product post being edited.
	 *
	 * @return void
	 *
	 * @see PPOM_Meta::__construct()
	 */
	public static function meta_list( $post ) {

		$ppom         = new PPOM_Meta( $post->ID );
		$all_meta     = PPOM()->get_product_meta_list_for_ui();
		$ppom_setting = admin_url( 'admin.php?page=ppom' );
	
		$html = '<div class="options_group ppom-settings-container" style="max-height:375px; overflow:auto;">';

		if ( count( $all_meta ) > 1 ) {
			// UP-SELL
			$html .= '<a class="ppom-upsell-link" target="_blank" href="' . tsdk_utmify( tsdk_translate_link( PPOM_UPGRADE_URL ), 'product-edit', 'metabox' ) . '">';
			$html .= '<span class="dashicons dashicons-external"></span> ';
			$html .= __( 'Using multiple PPOM field groups on the same product is available in PRO.', 'woocommerce-product-addon' );
			$html .= '</a>';
		}
		// PPOM Fields select table.
		$html .= '<table id="ppom_meta_sortable" class="wp-list-table widefat fixed striped">';
		// Hide search if we don't have many metas

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

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Filtered metabox markup is expected here.
		echo apply_filters( 'ppom_select_meta_in_product', $html, $ppom, $all_meta );

		echo '<div class="ppom_extra_options_panel">';
		do_action( 'ppom_meta_box_after_list', $post );
		echo '</div>';
	}

	// Product-field assignment persistence.

	/**
	 * Persists the selected PPOM field group IDs on a WooCommerce product.
	 *
	 * Normalizes the submitted `ppom_product_meta` payload to an integer array and
	 * stores it under {@see PPOM_PRODUCT_META_KEY}.
	 *
	 * @param int $post_id Product ID receiving the PPOM assignment.
	 *
	 * @return void
	 */
	public static function process_product_meta( $post_id ) {


		$ppom_meta_selected = isset( $_POST ['ppom_product_meta'] ) ? $_POST ['ppom_product_meta'] : array();
	
		if ( is_numeric( $ppom_meta_selected ) ) {
			$ppom_meta_selected = array( $ppom_meta_selected );
		} elseif ( ! is_array( $ppom_meta_selected ) ) {
			$ppom_meta_selected = array();
		}
	
		$ppom_meta_selected = array_map( 'intval', $ppom_meta_selected );
	
		// ppom_pa($ppom_meta_selected); exit;
		update_post_meta( $post_id, PPOM_PRODUCT_META_KEY, $ppom_meta_selected );

		do_action( 'ppom_proccess_meta', $post_id );
	}

	// Show notices
	public static function show_notices() {

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

	// Field group create and update.

	/**
	 * Creates a PPOM field group from the admin builder request.
	 *
	 * Verifies the admin nonce and capability, sanitizes the submitted field
	 * schema, inserts the field-group row into the PPOM custom table, then updates
	 * each field entry with the generated PPOM ID. When a product ID is submitted,
	 * the new field group is attached to that product.
	 *
	 * @return void
	 *
	 * @see Validator::sanitize_array_data()
	 * @see Helpers::attach_fields_to_product()
	 */
	public static function save_form_meta() {

		$db_version      = floatval( get_option( 'personalizedproduct_db_version' ) );
		$ppom_form_nonce = isset( $_POST['ppom_form_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['ppom_form_nonce'] ) ) : '';

		if ( $db_version < 22.1 ) {
			$resp = array(
				'message' => __( 'Since version 22.0, Database has some changes. Please Deactivate & then activate the PPOM plugin.', 'woocommerce-product-addon' ),
				'status'  => 'error',
			);

			wp_send_json( $resp );
		}

		// print_r($_REQUEST); exit;

		if ( empty( $ppom_form_nonce )
		|| ! wp_verify_nonce( $ppom_form_nonce, 'ppom_form_nonce_action' )
		|| ! Helpers::security_role()
		) {
			$resp = array(
				'message' => __( 'Sorry, you are not allowed to perform this action.', 'woocommerce-product-addon' ),
				'status'  => 'error',
			);

			wp_send_json( $resp );
		}

		$ppom = array();

		if ( is_string( $_REQUEST['ppom'] ) ) {
			$ppom_encoded = $_REQUEST['ppom'];
			parse_str( $ppom_encoded, $ppom_decoded );
			$ppom = $ppom_decoded['ppom'];
		}

		$ppom_meta = isset( $_REQUEST['ppom_meta'] ) ? $_REQUEST['ppom_meta'] : $ppom;

		if ( empty( $ppom_meta ) ) {
			$resp = array(
				'message' => __( 'No fields found.', 'woocommerce-product-addon' ),
				'status'  => 'error',
			);
			wp_send_json( $resp );
		}

		// sanitize
		$productmeta_name     = isset( $_REQUEST['productmeta_name'] ) ? sanitize_text_field( $_REQUEST['productmeta_name'] ) : '';
		$dynamic_price_hide   = isset( $_REQUEST['dynamic_price_hide'] ) ? sanitize_text_field( $_REQUEST['dynamic_price_hide'] ) : '';
		$send_file_attachment = isset( $_REQUEST['send_file_attachment'] ) ? sanitize_text_field( $_REQUEST['send_file_attachment'] ) : 'NA';
		$show_cart_thumb      = isset( $_REQUEST['show_cart_thumb'] ) ? sanitize_text_field( $_REQUEST['show_cart_thumb'] ) : 'NA';
		$aviary_api_key       = isset( $_REQUEST['aviary_api_key'] ) ? sanitize_text_field( $_REQUEST['aviary_api_key'] ) : 'NA';
		$productmeta_style    = isset( $_REQUEST['productmeta_style'] ) ? sanitize_text_field( $_REQUEST['productmeta_style'] ) : '';
		$productmeta_js       = isset( $_REQUEST['productmeta_js'] ) ? sanitize_text_field( $_REQUEST['productmeta_js'] ) : '';
		$product_id           = isset( $_REQUEST['product_id'] ) ? intval( $_REQUEST['product_id'] ) : 0;

		if ( strlen( $productmeta_name ) > 50 ) {
			$resp = array(
				'message' => __( 'PPOM title is too long to save, please make it less than 50 characters.', 'woocommerce-product-addon' ),
				'status'  => 'error',
			);

			wp_send_json( $resp );
		}

		$settings = array(
			'productmeta_name'      => $productmeta_name,
			'dynamic_price_display' => $dynamic_price_hide,
			'send_file_attachment'  => $send_file_attachment,
			'show_cart_thumb'       => $show_cart_thumb,
			'aviary_api_key'        => $aviary_api_key,
			'productmeta_style'     => $productmeta_style,
			'productmeta_js'        => $productmeta_js,
		);

		$ppom_id = self::create_group_from_fields( (array) $ppom_meta, $settings, $product_id );

		$redirect_to = '';

		if ( $ppom_id ) {
			$ppom_args   = array(
				'page'           => 'ppom',
				'productmeta_id' => $ppom_id,
				'do_meta'        => 'edit',
			);
			$redirect_to = (string) add_query_arg( $ppom_args, admin_url( 'admin.php' ) );
		}

		if ( $ppom_id && self::is_attachable_product( $product_id ) ) {
			$product_link = (string) get_permalink( $product_id );
			if ( '' !== $product_link ) {
				$redirect_to = $product_link;
			}
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

	/**
	 * Persists a new PPOM field group from a normalized payload.
	 *
	 * Runs the `ppom_meta_data_saving` filter twice (once before insert with an
	 * empty id, once after with the resolved id) to match WPML/registration
	 * expectations, sanitizes via {@see Validator::sanitize_array_data()},
	 * inserts via {@see MetaRepositoryAccessor::insert_group()}, then rewrites
	 * `the_meta` so each field row carries the new ppom_id. When a product ID
	 * is supplied, the new group is attached to that product.
	 *
	 * Shared by {@see self::save_form_meta()} and {@see self::import_template()}.
	 *
	 * @param array<int, array<string, mixed>> $fields     Untouched field schema (filter input, the_meta shape).
	 * @param array<string, string>            $settings   Group settings: productmeta_name, dynamic_price_display, send_file_attachment, show_cart_thumb, aviary_api_key, productmeta_style, productmeta_js.
	 * @param int                              $product_id Optional WooCommerce product ID to attach the new group to. 0 to skip.
	 * @return int New productmeta_id, or 0 on insert failure.
	 */
	private static function create_group_from_fields( array $fields, array $settings, $product_id = 0 ) {

		$product_meta      = apply_filters( 'ppom_meta_data_saving', $fields, '' );
		$product_meta      = Validator::sanitize_array_data( $product_meta );
		$product_meta      = array_filter(
			$product_meta,
			function ( $pm ) {
				return ! empty( $pm['type'] ) || ! empty( $pm['data_name'] );
			}
		);
		$product_meta_json = wp_json_encode( $product_meta );

		$row = array(
			'productmeta_name'      => isset( $settings['productmeta_name'] ) ? (string) $settings['productmeta_name'] : '',
			'dynamic_price_display' => isset( $settings['dynamic_price_display'] ) ? (string) $settings['dynamic_price_display'] : '',
			'send_file_attachment'  => isset( $settings['send_file_attachment'] ) ? (string) $settings['send_file_attachment'] : 'NA',
			'show_cart_thumb'       => isset( $settings['show_cart_thumb'] ) ? (string) $settings['show_cart_thumb'] : 'NA',
			'aviary_api_key'        => trim( isset( $settings['aviary_api_key'] ) ? (string) $settings['aviary_api_key'] : 'NA' ),
			'the_meta'              => $product_meta_json,
			'productmeta_created'   => current_time( 'mysql' ),
		);

		if ( ! Helpers::is_legacy_user() ) {
			$row['productmeta_style'] = isset( $settings['productmeta_style'] ) ? (string) $settings['productmeta_style'] : '';
			$row['productmeta_js']    = isset( $settings['productmeta_js'] ) ? (string) $settings['productmeta_js'] : '';
		}

		$row = apply_filters( 'ppom_settings_meta_data_new', $row );

		$format  = array_fill( 0, count( $row ), '%s' );
		$ppom_id = MetaRepositoryAccessor::instance()->insert_group( $row, $format );

		if ( ! $ppom_id ) {
			return 0;
		}

		// Re-run the filter with the resolved id so listeners (e.g. WPML)
		// register against the final productmeta_id.
		$product_meta = apply_filters( 'ppom_meta_data_saving', $fields, $ppom_id );
		$product_meta = Validator::sanitize_array_data( $product_meta );
		$product_meta = array_filter(
			$product_meta,
			function ( $pm ) {
				return ! empty( $pm['type'] ) && ! empty( $pm['data_name'] );
			}
		);

		self::update_ppom_meta_only( $ppom_id, $product_meta );

		if ( self::is_attachable_product( $product_id ) ) {
			Helpers::attach_fields_to_product( $ppom_id, $product_id );
		}

		return (int) $ppom_id;
	}

	/**
	 * Returns true when the supplied ID resolves to an existing WooCommerce
	 * product the current user is allowed to edit.
	 *
	 * Guards the product-attach + product-permalink redirect paths against
	 * arbitrary or non-existent post IDs, which would otherwise create orphan
	 * postmeta rows and produce empty redirect URLs from `get_permalink()`.
	 *
	 * @param mixed $product_id Candidate product ID from the request.
	 * @return bool
	 */
	private static function is_attachable_product( $product_id ) {

		$product_id = (int) $product_id;
		if ( $product_id <= 0 ) {
			return false;
		}

		$post = get_post( $product_id );
		if ( ! $post || 'product' !== $post->post_type ) {
			return false;
		}

		return current_user_can( 'edit_post', $product_id );
	}

	/**
	 * Imports a curated template into a new PPOM field group.
	 *
	 * Verifies the import nonce + capability, looks up the requested template
	 * by slug from {@see PPOM_Template_Library}, enforces the server-side Pro
	 * gate, then delegates persistence to
	 * {@see self::create_group_from_fields()} so the same filter chain and
	 * sanitization that `save_form_meta` uses applies.
	 *
	 * Responds with `{ status, message, productmeta_id, redirect_to }` JSON.
	 *
	 * @return void
	 */
	public static function import_template() {

		$db_version = floatval( get_option( 'personalizedproduct_db_version' ) );
		$nonce      = isset( $_POST['ppom_import_template_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['ppom_import_template_nonce'] ) ) : '';

		if ( $db_version < 22.1 ) {
			wp_send_json(
				array(
					'message' => __( 'Since version 22.0, Database has some changes. Please Deactivate & then activate the PPOM plugin.', 'woocommerce-product-addon' ),
					'status'  => 'error',
				)
			);
		}

		if ( empty( $nonce )
			|| ! wp_verify_nonce( $nonce, 'ppom_import_template_action' )
			|| ! Helpers::security_role()
		) {
			wp_send_json(
				array(
					'message' => __( 'Sorry, you are not allowed to perform this action.', 'woocommerce-product-addon' ),
					'status'  => 'error',
				)
			);
		}

		$slug     = isset( $_POST['template'] ) ? sanitize_key( wp_unslash( $_POST['template'] ) ) : '';
		$template = $slug ? \PPOM_Template_Library::get_template( $slug ) : null;

		if ( null === $template ) {
			wp_send_json(
				array(
					'message' => __( 'Unknown template.', 'woocommerce-product-addon' ),
					'status'  => 'error',
				)
			);
		}

		if ( ! \PPOM_Template_Library::user_can_use( $template ) ) {
			wp_send_json(
				array(
					'message' => __( 'This template requires PPOM Pro with an active license.', 'woocommerce-product-addon' ),
					'status'  => 'error',
				)
			);
		}

		$product_id = isset( $_POST['product_id'] ) ? intval( $_POST['product_id'] ) : 0;

		$group_name = isset( $template['group_name'] ) ? (string) $template['group_name'] : '';
		if ( strlen( $group_name ) > 50 ) {
			$group_name = substr( $group_name, 0, 50 );
		}

		$settings = array(
			'productmeta_name'      => $group_name,
			'dynamic_price_display' => '',
			'send_file_attachment'  => 'NA',
			'show_cart_thumb'       => 'NA',
			'aviary_api_key'        => 'NA',
			'productmeta_style'     => '',
			'productmeta_js'        => '',
		);

		$fields  = isset( $template['fields'] ) && is_array( $template['fields'] ) ? $template['fields'] : array();
		$ppom_id = self::create_group_from_fields( $fields, $settings, $product_id );

		if ( ! $ppom_id ) {
			wp_send_json(
				array(
					'message' => __( 'Could not import the template. Please try again.', 'woocommerce-product-addon' ),
					'status'  => 'error',
				)
			);
		}

		$redirect_to = (string) add_query_arg(
			array(
				'page'           => 'ppom',
				'productmeta_id' => $ppom_id,
				'do_meta'        => 'edit',
			),
			admin_url( 'admin.php' )
		);

		if ( self::is_attachable_product( $product_id ) ) {
			$product_link = (string) get_permalink( $product_id );
			if ( '' !== $product_link ) {
				$redirect_to = $product_link;
			}
		}

		wp_send_json(
			array(
				'message'        => __( 'Template imported successfully.', 'woocommerce-product-addon' ),
				'status'         => 'success',
				'productmeta_id' => $ppom_id,
				'redirect_to'    => esc_url_raw( $redirect_to ),
			)
		);
	}

	/**
	 * Updates an existing PPOM field group from the admin builder request.
	 *
	 * Rebuilds the stored `the_meta` JSON payload from the submitted field schema
	 * and updates the field-group settings row in the PPOM custom table.
	 *
	 * @return void
	 *
	 * @see Validator::sanitize_array_data()
	 */
	public static function update_form_meta() {


		$return_page     = isset( $_REQUEST['ppom_meta'] ) ? 'ppom-energy' : 'ppom';
		$productmeta_id  = isset( $_REQUEST['productmeta_id'] ) ? sanitize_text_field( $_REQUEST['productmeta_id'] ) : '';
		$ppom_form_nonce = isset( $_POST['ppom_form_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['ppom_form_nonce'] ) ) : '';

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


		if ( empty( $ppom_form_nonce )
		|| ! wp_verify_nonce( $ppom_form_nonce, 'ppom_form_nonce_action' )
		|| ! Helpers::security_role()
		) {
			$resp = array(
				'message' => __( 'Sorry, you are not allowed to perform this action.', 'woocommerce-product-addon' ),
				'status'  => 'error',
			);

			wp_send_json( $resp );
		}

		if ( is_string( $_REQUEST['ppom'] ) ) {
			$ppom_encoded = $_REQUEST['ppom'];
			parse_str( $ppom_encoded, $ppom_decoded );
			$_REQUEST['ppom'] = $ppom_decoded['ppom'];
		}

		$ppom_meta    = isset( $_REQUEST['ppom_meta'] ) ? $_REQUEST['ppom_meta'] : $_REQUEST['ppom'];
		$product_meta = apply_filters( 'ppom_meta_data_saving', (array) $ppom_meta, $productmeta_id );
		$product_meta = Validator::sanitize_array_data( $product_meta );
		// Remove the meta row if the type or data_name is empty.
		$product_meta = array_filter(
			$product_meta,
			function ( $pm ) {
				return ! empty( $pm['type'] ) || ! empty( $pm['data_name'] );
			}
		);
		$product_meta = json_encode( $product_meta );
	
		$productmeta_name     = isset( $_REQUEST['productmeta_name'] ) ? sanitize_text_field( $_REQUEST['productmeta_name'] ) : '';
		$dynamic_price_hide   = isset( $_REQUEST['dynamic_price_hide'] ) ? sanitize_text_field( $_REQUEST['dynamic_price_hide'] ) : '';
		$send_file_attachment = isset( $_REQUEST['send_file_attachment'] ) ? sanitize_text_field( $_REQUEST['send_file_attachment'] ) : '';
		$show_cart_thumb      = isset( $_REQUEST['show_cart_thumb'] ) ? sanitize_text_field( $_REQUEST['show_cart_thumb'] ) : '';
		$aviary_api_key       = isset( $_REQUEST['aviary_api_key'] ) ? sanitize_text_field( $_REQUEST['aviary_api_key'] ) : '';
		$productmeta_style    = isset( $_REQUEST['productmeta_style'] ) ? sanitize_text_field( $_REQUEST['productmeta_style'] ) : '';
		$productmeta_js       = isset( $_REQUEST['productmeta_js'] ) ? sanitize_text_field( $_REQUEST['productmeta_js'] ) : '';

		if ( strlen( $productmeta_name ) > 50 ) {
			$resp = array(
				'message' => __( 'PPOM title is too long to save, please make it less than 50 characters.', 'woocommerce-product-addon' ),
				'status'  => 'error',
			);

			wp_send_json( $resp );
		}

		$ppom_settings_meta_data = array(
			'productmeta_name'      => $productmeta_name,
			'dynamic_price_display' => $dynamic_price_hide,
			'send_file_attachment'  => $send_file_attachment,
			'show_cart_thumb'       => $show_cart_thumb,
			'aviary_api_key'        => trim( $aviary_api_key ),
			'the_meta'              => $product_meta,
		);
		if ( ! Helpers::is_legacy_user() ) {
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

		$rows_effected = MetaRepositoryAccessor::instance()->update_group( (int) $productmeta_id, $dt, $format, $where, $where_format );

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

	/**
	 * Rewrites only the stored PPOM field schema for a field group.
	 *
	 * @param int   $ppom_id   PPOM field-group ID.
	 * @param array $ppom_meta Normalized field definitions for `the_meta`.
	 *
	 * @return bool
	 */
	public static function update_ppom_meta_only( $ppom_id, $ppom_meta ) {

		$json = wp_json_encode( Validator::sanitize_array_data( $ppom_meta ) );
		if ( false === $json ) {
			return false;
		}
		$rows_effected = MetaRepositoryAccessor::instance()->update_the_meta_only( (int) $ppom_id, $json );

		return (bool) $rows_effected;
	}

	// Field group deletion.

	/**
	 * Deletes a single PPOM field group from the admin UI.
	 *
	 * Verifies the admin nonce and capability before removing the row from the
	 * PPOM custom table.
	 *
	 * @return void
	 */
	public static function delete_meta() {

		$ppom_meta_nonce = isset( $_POST['ppom_meta_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['ppom_meta_nonce'] ) ) : '';

		if ( empty( $ppom_meta_nonce )
		|| ! wp_verify_nonce( $ppom_meta_nonce, 'ppom_meta_nonce_action' )
		|| ! Helpers::security_role()
		) {
			$response = array(
				'status'  => 'error',
				'message' => __( 'Sorry, you are not allowed to perform this action please try again', 'woocommerce-product-addon' ),
			);

			wp_send_json( $response );
		}

		$productmeta_id = isset( $_REQUEST['productmeta_id'] ) ? sanitize_text_field( $_REQUEST['productmeta_id'] ) : '';

		$ppom_id = intval( $productmeta_id );
		$res     = MetaRepositoryAccessor::instance()->delete_by_id( $ppom_id );


		$response = array();
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

	/**
	 * Deletes multiple PPOM field groups from the admin list table.
	 *
	 * Sanitizes the submitted field-group IDs and removes the matching rows from
	 * the PPOM custom table in a single prepared query.
	 *
	 * @return void
	 */
	public static function delete_selected_meta() {

		$ppom_meta_nonce = isset( $_POST['ppom_meta_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['ppom_meta_nonce'] ) ) : '';

		if ( empty( $ppom_meta_nonce )
		|| ! wp_verify_nonce( $ppom_meta_nonce, 'ppom_meta_nonce_action' )
		|| ! Helpers::security_role()
		|| ! array_key_exists( 'productmeta_ids', $_POST )
		|| ! is_array( $_POST['productmeta_ids'] )
		) {
			_e( 'Sorry, you are not allowed to perform this action', 'woocommerce-product-addon' );
			die( 0 );
		}

		$del_ids = array();

		// for the performance wise, prefer to use foreach instead of array_map-array_filter-array_fill stack.
		foreach ( $_POST['productmeta_ids'] as $id ) {
			$id = absint( $id );

			if ( 0 === $id ) {
				continue;
			}

			$del_ids[] = $id;
		}

		global $wpdb;

		$res = MetaRepositoryAccessor::instance()->delete_by_ids( $del_ids );

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
	public static function simplify_meta( $meta ) {
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

	// Admin product-page shortcuts.

	/**
	 * Adds PPOM edit and attach shortcuts to the product admin bar.
	 *
	 * Builds links from the current product's resolved PPOM assignment so store
	 * managers can jump straight to the field-group editor or attach another group.
	 *
	 * @return void
	 *
	 * @see PPOM_Meta::__construct()
	 * @see Helpers::attach_fields_to_product()
	 */
	public static function bar_menu() {

		if ( ! is_product() ) {
			return;
		}

		global $wp_admin_bar, $product;

		$product_id = Helpers::get_product_id( $product );
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

		$all_meta = PPOM()->get_product_meta_list_for_ui();
		foreach ( $all_meta as $meta ) {

			$apply_link = admin_url( 'admin-post.php' );
			$apply_arg  = array(
				'productid' => $product_id,
				'metaid'    => $meta->productmeta_id,
				'metatitle' => $meta->productmeta_name,
				'action'    => 'ppom_attach',
				'nonce'     => wp_create_nonce( 'ppom_attach' ),
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

	/**
	 * Set Black Friday data.
	 *
	 * @param array $configs The configuration array for the loaded products.
	 *
	 * @return array
	 */
	public static function add_black_friday_data( $configs ) {
		$config = $configs['default'];

		$message   = __( 'Conditional fields, file uploads, pricing formulas. Let customers configure products the way they need. Exclusively for existing PPOM users.', 'woocommerce-product-addon' );
		$cta_label = __( 'Get PPOM Pro', 'woocommerce-product-addon' );

		$plan             = apply_filters( 'product_ppom_license_plan', 0 );
		$license          = apply_filters( 'product_ppom_license_key', false );
		$status           = apply_filters( 'product_ppom_license_status', false );
		$pro_product_slug = defined( 'PPOM_PRO_BASENAME' ) ? PPOM_PRO_BASENAME : '';

		$is_pro     = 'valid' === $status;
		$is_expired = 'expired' === $status || 'active-expired' === $status;

		if ( $is_pro ) {
			// translators: %s is the discount percentage.
			$config['plugin_meta_message'] = sprintf( __( 'Black Friday Sale - up to %s off', 'woocommerce-product-addon' ), '30%' );
			// translators: %1$s - discount, %2$s - discount.
			$message   = sprintf( __( 'Upgrade your PPOM Pro plan: %1$s off this week. Already on the plan you need? Renew early and save up to %2$s.', 'woocommerce-product-addon' ), '30%', '20%' );
			$cta_label = __( 'See your options', 'woocommerce-product-addon' );
		} elseif ( $is_expired ) {
			// translators: %s is the discount percentage.
			$config['plugin_meta_message'] = sprintf( __( 'Black Friday Sale - %s off', 'woocommerce-product-addon' ), '50%' );
			$message                       = __( 'Your PPOM Pro features are still here, just locked. Renew at a reduced rate this week.', 'woocommerce-product-addon' );
			$cta_label                     = __( 'Reactivate now', 'woocommerce-product-addon' );
		} else {
			// translators: %s is the discount percentage.
			$config['plugin_meta_message'] = sprintf( __( 'Black Friday Sale - %s off', 'woocommerce-product-addon' ), '60%' );
			// translators: %s - discount.
			$config['title'] = sprintf( __( 'PPOM Pro: %s off this week', 'woocommerce-product-addon' ), '60%' );
		}

		$url_params = array(
			'utm_term' => $is_pro ? 'plan-' . $plan : 'free',
			'lkey'     => ! empty( $license ) ? $license : false,
			'expired'  => $is_expired ? '1' : false,
		);

		if ( ( $is_pro || $is_expired ) && ! empty( $pro_product_slug ) ) {
			$config['plugin_meta_targets'] = array( $pro_product_slug );
		}

		$config['message']   = $message;
		$config['cta_label'] = $cta_label;

		$config['sale_url'] = add_query_arg(
			$url_params,
			tsdk_translate_link( tsdk_utmify( 'https://themeisle.link/ppom-bf', 'bfcm', 'ppom' ) )
		);

		$configs[ PPOM_PRODUCT_SLUG ] = $config;

		return $configs;
	}
}
