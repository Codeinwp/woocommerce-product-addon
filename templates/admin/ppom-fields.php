<?php
/*
** PPOM New Form Meta
*/

/*
**========== Direct access not allowed ===========
*/
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// get class instance
$form_meta = PPOM_FIELDS_META();

$ppom                 = '';
$productmeta_name     = '';
$dynamic_price_hide   = '';
$send_file_attachment = '';
$show_cart_thumb      = '';
$aviary_api_key       = '';
$productmeta_style    = '';
$productmeta_js       = '';
$product_meta_id      = 0;
$product_meta         = array();
$ppom_field_index     = 1;
$is_edit_screen       = false;
$is_new_group         = false;
$is_legacy_user       = ppom_is_legacy_user();

if ( isset( $_REQUEST['action'] ) && $_REQUEST['action'] == 'new' ) {
	$is_edit_screen = true;
	$is_new_group   = true;
}
if ( isset( $_REQUEST['productmeta_id'] ) && $_REQUEST ['do_meta'] == 'edit' ) {

	$product_meta_id = intval( $_REQUEST['productmeta_id'] );
	$ppom            = new PPOM_Meta();
	$ppom_settings   = $ppom->get_settings_by_id( $product_meta_id );
	$is_edit_screen  = true;

	$productmeta_name     = ( isset( $ppom_settings->productmeta_name ) ? stripslashes( $ppom_settings->productmeta_name ) : '' );
	$dynamic_price_hide   = ( isset( $ppom_settings->dynamic_price_display ) ? $ppom_settings->dynamic_price_display : '' );
	$send_file_attachment = ( isset( $ppom_settings->send_file_attachment ) ? $ppom_settings->send_file_attachment : '' );
	$show_cart_thumb      = ( isset( $ppom_settings->show_cart_thumb ) ? $ppom_settings->show_cart_thumb : '' );
	$aviary_api_key       = ( isset( $ppom_settings->aviary_api_key ) ? $ppom_settings->aviary_api_key : '' );
	$productmeta_style    = ! empty( $ppom_settings->productmeta_style ) ? $ppom_settings->productmeta_style : "selector {\n}\n";
	$productmeta_js       = ( isset( $ppom_settings->productmeta_js ) ? $ppom_settings->productmeta_js : '' );
	$product_meta         = json_decode( $ppom_settings->the_meta, true );
}

$url_cancel = add_query_arg(
	array(
		'action'         => false,
		'productmeta_id' => false,
		'do_meta'        => false,
	)
);

echo '<p><a href="' . esc_url( $url_cancel ) . '">&laquo; ' . __( 'Existing Product Meta', 'woocommerce-product-addon' ) . '</a></p>';

$product_id = isset( $_GET['product_id'] ) ? intval( $_GET['product_id'] ) : '';

$license_data     = get_option( 'ppom_pro_license_data', array() );
$plan_category    = NM_PersonalizedProduct::LICENSE_PLAN_FREE;
$is_pro_installed = ppom_pro_is_installed();
$license_status   = $is_pro_installed && ! empty( $license_data->license ) ? $license_data->license : 'invalid';

if ( $is_pro_installed && isset( $license_data->plan ) && is_numeric( $license_data->plan ) ) {
	$plan_category = NM_PersonalizedProduct::get_license_category( intval( $license_data->plan ) );
}

$fields_groups = function_exists( 'ppom_get_admin_field_type_groups' ) ? ppom_get_admin_field_type_groups() : array();

?>

<div class="ppom-admin-fields-wrapper">

	<!-- All fields inputs name show -->
	<div id="ppom_fields_model_id" class="ppom-modal-box ppom-fields-name-model">
		<header class="ppom-modal-header">
			<h3><?php _e( 'Select Field Type', 'woocommerce-product-addon' ); ?></h3>
			<div class="ppom-search-container">
				<input type="text" name="ppom-search-field" placeholder="<?php _e( 'Search Fields', 'woocommerce-product-addon' ); ?>" />
				<span class="ppom-search-icon">
					<i class="fa fa-search" aria-hidden="true"></i>
				</span>
			</div>
		</header>
		<div class="ppom-modal-body ppom-modal-add-field">
			<div class="ppom-fields">
				<div class="ppom-modal-shortcuts">
					<a  href="#all">
						<?php echo __( 'All', 'woocommerce-product-addon' ); ?>
					</a>
					<?php

					foreach ( $fields_groups as $group_id => $group ) {
						?>
						<a
							href="#<?php echo esc_attr( $group_id ); ?>"
						>
						<?php echo esc_html( $group['label'] ); ?>
						</a>
						<?php
					}
					?>
				</div>
				<div class="ppom-fields-sections">
					<?php
					foreach ( $fields_groups as $group_id => $group ) {
						?>
						<div class="ppom-fields-section" id="<?php echo esc_attr( $group_id ); ?>-ppom-fields">
							<div class="ppom-fields-section-title">
								<h5
									id="<?php echo esc_attr( $group_id ); ?>"
								>
									<?php echo esc_html( $group['label'] ); ?>
								</h5>
							</div>
							<div
								class="ppom-fields-grid"
							>
								<?php
								foreach ( $group['fields'] as $field ) {
									$is_locked = isset( $field['plan'] ) && $plan_category < $field['plan'];
									?>
									<button
										class="ppom_select_field ppom-field-item <?php echo ( $is_locked ) ? 'ppom-locked-field' : ''; ?>"
										data-field-type="<?php echo esc_attr( $field['slug'] ); ?>"
									>
										<span class="ppom-fields-icon">
											<i class="fa <?php echo $field['icon']; ?>" aria-hidden="true"></i>
										</span>
										<span>
											<?php echo esc_html( $field['title'] ); ?>
										</span>
										<?php
										if ( $is_locked ) {
											?>
										<span class="upsell-btn-wrapper">
											<a target="_blank" href="#">
												<i class="fa fa-lock" aria-hidden="true"></i>
												<?php _e( 'PRO', 'woocommerce-product-addon' ); ?>
											</a>
										</span>
											<?php
										}

										?>

										<p class="upsell-tooltip">
											<?php echo esc_html( $field['description'] ); ?>
										</p>
									</button>
									<?php
								}
								?>
							</div>
						</div>
						<?php
					}
					?>
				</div>
			</div>
			<?php
			if ( 'valid' !== $license_status ) {
				?>
			<div class="ppom-sidebar-upsell">
				<div class="ppom-sidebar-upsell-header">
					<i class="dashicons dashicons-lock"></i>
					<h2><?php _e( 'Unlock all Features!', 'woocommerce-product-addon' ); ?></h2>
				</div>
				<div class="ppom-sidebar-upsell-content">
					<p><?php _e( 'Upgrade to the Pro plan to unlock all features and enhance your product fields management capabilities:', 'woocommerce-product-addon' ); ?></p>
					<div class="ppom-sidebar-upsell-features-grid">
						<div><?php _e( 'Unlock 30+ input fields', 'woocommerce-product-addon' ); ?></div>
						<div><?php _e( 'Meta Fields Repeater', 'woocommerce-product-addon' ); ?></div>
						<div><?php _e( 'Cart Edit', 'woocommerce-product-addon' ); ?></div>
						<div><?php _e( 'Quantities Pack', 'woocommerce-product-addon' ); ?></div>
					</div>
				</div>
				<a target="_blank" href="<?php echo tsdk_utmify( tsdk_translate_link( PPOM_UPGRADE_URL ), 'add-field-modal', 'ppompage' ); ?>" class="cta-button">Get started!</a>
			</div>
				<?php
			}
			?>
		</div>
		<footer>
			<button type="button"
					class="btn btn-default close-model ppom-js-modal-close"><?php _e( 'Close', 'woocommerce-product-addon' ); ?></button>
		</footer>
	</div>

	<div class="ppom-main-field-wrapper">
		<form class="ppom-save-fields-meta">

			<?php if ( $product_meta_id != 0 ) { ?>
				<input type="hidden" name="action" value="ppom_update_form_meta">
			<?php } else { ?>
				<input type="hidden" name="action" value="ppom_save_form_meta">
			<?php } ?>

			<?php
			// nonce field
			wp_nonce_field( 'ppom_form_nonce_action', 'ppom_form_nonce' );
			?>

			<input type="hidden" name="productmeta_id" value="<?php echo esc_attr( (string) $product_meta_id ); ?>">
			<input type="hidden" name="product_id" value="<?php echo esc_attr( (string) $product_id ); ?>">


			<div class="ppom-basic-setting-section">
				<h2 class="ppom-heading-style"><?php _e( 'Product Meta Basic Settings', 'woocommerce-product-addon' ); ?><span></span></h2>
				<div class="ppom-tabs-init ppom-admin-tabs-css">
					<!--General Tab-->
					<input type="radio" name="css-tabs" id="ppom-general-tab" checked>
					<label for="ppom-general-tab" class="ppom-tab-label">General</label>
					<div class="ppom-admin-tab-content">
						<div class="row">
							<div class="col-md-6 col-sm-12">
								<div class="form-group">
									<label><?php _e( 'Meta group name', 'woocommerce-product-addon' ); ?>
										<span class="ppom-helper-icon" data-ppom-tooltip="ppom_tooltip"
												title="<?php _e( 'For your reference.', 'woocommerce-product-addon' ); ?>"><i
													class="dashicons dashicons-editor-help"></i></span>
									</label>
									<input type="text" class="form-control ppom-form-control" maxlength="50" name="productmeta_name"
											value="<?php echo esc_attr( $productmeta_name ); ?>" placeholder="<?php esc_attr_e( 'Enter meta group name', 'woocommerce-product-addon' ); ?>">
								</div>
							</div>
							<div class="col-md-6 col-sm-12">
								<div class="form-group">
									<label><?php _e( 'Control price display on product page', 'woocommerce-product-addon' ); ?>
										<span class="ppom-helper-icon" data-ppom-tooltip="ppom_tooltip"
												title="<?php _e( 'Control how price table will be shown for options or disable.', 'woocommerce-product-addon' ); ?>"><i
													class="dashicons dashicons-editor-help"></i></span>
									</label>
									<select name="dynamic_price_hide" class="form-control ppom-form-control">
										<option value="no"><?php _e( 'Select Option', 'woocommerce-product-addon' ); ?></option>
										<option value="hide" <?php selected( $dynamic_price_hide, 'hide' ); ?>><?php _e( 'Do Not Show Price Table', 'woocommerce-product-addon' ); ?></option>
										<option value="option_sum" <?php selected( $dynamic_price_hide, 'option_sum' ); ?>><?php _e( "Show Only Option's Total", 'woocommerce-product-addon' ); ?></option>
										<option value="all_option" <?php selected( $dynamic_price_hide, 'all_option' ); ?>><?php _e( "Show Each Option's Price", 'woocommerce-product-addon' ); ?></option>
									</select>
								</div>

							</div>
						</div>
						<?php if ( $is_edit_screen && ! $is_new_group ) { ?>
						<div class="row">
							<div class="col-md-12 col-sm-12">
								<?php
								// Output is composed of pre-escaped HTML built by SelectComponent::render().
								echo NM_PersonalizedProduct_Admin::render_inline_attach_selects( $product_meta_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								?>
							</div>
						</div>
						<?php } ?>
							<?php
							do_action( 'ppom_field_meta_general_tab', $ppom );
							?>
					</div>

					<!--Style Tab-->
					<input type="radio" name="css-tabs" id="ppom-style-tab">
					<label for="ppom-style-tab" class="ppom-tab-label">
						<?php esc_html_e( 'Style', 'woocommerce-product-addon' ); ?>
						<span class="ppom-badge"><?php esc_html_e( 'css/js', 'woocommerce-product-addon' ); ?></span>
					</label>
					<div class="ppom-admin-tab-content">
						<?php if ( $is_legacy_user ) : ?>
							<div class="row">
								<div class="col-md-12 col-sm-12">
									<div class="notice notice-info">
										<p>
											<?php
											printf(
												// translators: %1$s the upgrade link with label: 'Upgrade To Pro'.
												__( 'Custom CSS and JS customization is not available on your current plan. %1$s plan to unlock the ability to fully customize your fields\' appearance and functionality.', 'woocommerce-product-addon' ),
												sprintf(
													'<a href="%s" target="_blank">%s</a>',
													esc_url( tsdk_translate_link( tsdk_utmify( PPOM_UPGRADE_URL, 'customstyle' ) ) ),
													__( 'Upgrade to the Pro', 'woocommerce-product-addon' )
												)
											);
											?>
										</p>
									</div>
								</div>
							</div>
						<?php endif; ?>
						<div class="row">
							<div class="col-md-6 col-sm-12">
								<div class="form-group">
									<label><?php _e( 'Custom CSS', 'woocommerce-product-addon' ); ?>
										<span class="ppom-helper-icon" data-ppom-tooltip="ppom_tooltip"
												title="<?php _e( 'Add your own CSS.', 'woocommerce-product-addon' ); ?>"><i
													class="dashicons dashicons-editor-help"></i></span>
									</label>
									<div class="ppom-editor-wrapper<?php echo $is_legacy_user ? ' ppom-locked' : ''; ?>">
										<?php if ( $is_legacy_user ) : ?>
										<div class="ppom-locked-overlay">
											<div class="ppom-lock-icon">
												<svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" stroke="#ff0000">
													<path d="M19 11H5C3.89543 11 3 11.8954 3 13V20C3 21.1046 3.89543 22 5 22H19C20.1046 22 21 21.1046 21 20V13C21 11.8954 20.1046 11 19 11Z" stroke="#ff0000" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
													<path d="M7 11V7C7 5.67392 7.52678 4.40215 8.46447 3.46447C9.40215 2.52678 10.6739 2 12 2C13.3261 2 14.5979 2.52678 15.5355 3.46447C16.4732 4.40215 17 5.67392 17 7V11" stroke="#ff0000" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
												</svg>
											</div>
											<div class="ppom-lock-text"><?php esc_html_e( 'Locked on free plan', 'woocommerce-product-addon' ); ?></div>
											<div class="ppom-lock-subtext"><?php esc_html_e( 'Upgrade to add CSS', 'woocommerce-product-addon' ); ?></div>
										</div>
										<?php endif; ?>
										<textarea id="ppom-css-editor" class="form-control"
												name="productmeta_style"><?php echo esc_textarea( wp_unslash( $productmeta_style ) ); ?></textarea>
									</div>
									<br>
									<div class="ppom-style-usage-example css-example<?php echo $is_legacy_user ? ' ppom-locked-example' : ''; ?>">
										<p>
											<span class="ppom-info-icon dashicons dashicons-info-outline"></span>
											<strong><?php esc_html_e( 'How to use', 'woocommerce-product-addon' ); ?></strong></p>
										<p><?php esc_html_e( 'Use', 'woocommerce-product-addon' ); ?> <code>selector</code> <?php esc_html_e( 'to target block wrapper.', 'woocommerce-product-addon' ); ?></p>
										<p><?php esc_html_e( 'Example:', 'woocommerce-product-addon' ); ?></p>
										<textarea id="ppom-css-example-editor" class="ppom-css-example-code" readonly><?php echo esc_textarea( "selector {\n    background: #000;\n}\nselector img {\n    border-radius: 100%;\n}" ); ?></textarea>
										<p><?php esc_html_e( 'You can also use other CSS syntax here, such as media queries.', 'woocommerce-product-addon' ); ?></p>
									</div>
								</div>
							</div>
							<div class="col-md-6 col-sm-12">
								<div class="form-group">
									<label><?php _e( 'Custom JavaScript', 'woocommerce-product-addon' ); ?>
										<span class="ppom-helper-icon" data-ppom-tooltip="ppom_tooltip"
												title="<?php _e( 'Add your own JavaScript code.', 'woocommerce-product-addon' ); ?>"><i
													class="dashicons dashicons-editor-help"></i></span>
									</label>
									<div class="ppom-editor-wrapper<?php echo $is_legacy_user ? ' ppom-locked' : ''; ?>">
										<?php if ( $is_legacy_user ) : ?>
										<div class="ppom-locked-overlay">
											<div class="ppom-lock-icon">
												<svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" stroke="#ff0000">
													<path d="M19 11H5C3.89543 11 3 11.8954 3 13V20C3 21.1046 3.89543 22 5 22H19C20.1046 22 21 21.1046 21 20V13C21 11.8954 20.1046 11 19 11Z" stroke="#ff0000" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
													<path d="M7 11V7C7 5.67392 7.52678 4.40215 8.46447 3.46447C9.40215 2.52678 10.6739 2 12 2C13.3261 2 14.5979 2.52678 15.5355 3.46447C16.4732 4.40215 17 5.67392 17 7V11" stroke="#ff0000" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
												</svg>
											</div>
											<div class="ppom-lock-text"><?php esc_html_e( 'Locked on free plan', 'woocommerce-product-addon' ); ?></div>
											<div class="ppom-lock-subtext"><?php esc_html_e( 'Upgrade to add JavaScript', 'woocommerce-product-addon' ); ?></div>
										</div>
										<?php endif; ?>
										<textarea id="ppom-js-editor" class="form-control"
												name="productmeta_js"><?php echo esc_textarea( wp_unslash( $productmeta_js ) ); ?></textarea>
									</div>
									<br>
									<div class="ppom-style-usage-example<?php echo $is_legacy_user ? ' ppom-locked-example' : ''; ?>">
										<p>
											<span class="ppom-info-icon dashicons dashicons-info-outline"></span>
											<strong><?php esc_html_e( 'How to use', 'woocommerce-product-addon' ); ?></strong></p>
										<p><?php esc_html_e( 'Write plain JavaScript to control field behaviour. The code runs after the field is rendered on the page.', 'woocommerce-product-addon' ); ?></p>
										<p><?php esc_html_e( 'Example:', 'woocommerce-product-addon' ); ?></p>
										<textarea id="ppom-js-example-editor" class="ppom-js-example-code" readonly><?php echo esc_textarea( "document.querySelector('.ppom-wrapper')\n  .addEventListener('change', function(e) {\n    console.log(e.target.value);\n});" ); ?></textarea>
									</div>
								</div>
							</div>
						</div>
					</div>

				</div>
				<div class="clearboth"></div>
			</div>


			<!-- saving all fields via model -->
			<div class="ppom_save_fields_model">
				<?php
				if ( $product_meta ) {

					$f_index = 1;
					foreach ( $product_meta as $field_index => $field_meta ) {

						$field_type      = isset( $field_meta['type'] ) ? $field_meta['type'] : '';
						$the_title       = isset( $field_meta['title'] ) ? $field_meta['title'] : '';
						$the_field_id    = isset( $field_meta['data_name'] ) ? $field_meta['data_name'] : '';
						$the_placeholder = isset( $field_meta['placeholder'] ) ? $field_meta['placeholder'] : '';
						$defualt_fields  = isset( PPOM()->inputs[ $field_type ]->settings ) ? PPOM()->inputs[ $field_type ]->settings : array();
						$defualt_fields  = apply_filters( "ppom_settings_{$field_type}", $defualt_fields, $field_type );
						$defualt_fields  = $form_meta->update_html_classes( $defualt_fields );
						?>

						<!-- New PPOM Model  -->
						<div data-saved_dataname="<?php echo esc_attr( $the_field_id ); ?>" id="ppom_field_model_<?php echo esc_attr( $f_index ); ?>"
							class="ppom-modal-box ppom-slider ppom_sort_id_<?php echo esc_attr( $f_index ); ?>">
							<div class="ppom-model-content">

								<header>
									<h3>
										<?php echo $field_type; ?>
										<span class="ppom-dataname-reader">(<?php echo $the_field_id; ?>)</span>
									</h3>
								</header>
								<div class="ppom-modal-body">
									<?php
									echo $form_meta->render_field_meta( $defualt_fields, $field_type, $f_index, $field_meta );
									?>
								</div>
								<footer>
									<span class="ppom-req-field-id"></span>
									<button type="button"
											class="btn btn-default close-model ppom-js-modal-close"><?php _e( 'Close', 'woocommerce-product-addon' ); ?></button>
									<button class="btn btn-primary ppom-update-field ppom-add-fields-js-action"
											data-field-index='<?php echo esc_attr( $f_index ); ?>'
											data-field-type='<?php echo esc_attr( $field_type ); ?>'><?php _e( 'Update Field', 'woocommerce-product-addon' ); ?></button>
								</footer>
								<?php
								$ppom_field_index = $f_index;
								++$ppom_field_index;
								++$f_index;
								?>
							</div>
						</div>
						<?php
					}
				}

				echo '<input type="hidden" id="field_index" value="' . esc_attr( $ppom_field_index ) . '">';
				?>
			</div>

			<!-- all fields append on table -->
			<div class="ppom-fields-list-table-wrapper">
				<h2 class="ppom-heading-style"><?php _e( 'Add PPOM Fields', 'woocommerce-product-addon' ); ?></h2>
				<?php
				$ppom_fields_list_table = new \PPOM\Admin\FieldGroupFieldsListTable(
					$product_meta,
					$is_edit_screen && ! $is_new_group
				);
				$ppom_fields_list_table->prepare_items();
				$ppom_fields_list_table->display();
				?>
				<?php if ( function_exists( 'ppom_use_react_field_modal' ) && ppom_use_react_field_modal() ) : ?>
					<div id="ppom-field-modal-root"></div>
				<?php endif; ?>
			</div>
		</form>

		<?php if ( $is_edit_screen && ! $is_new_group ) { ?>
			<div id="ppom-live-preview-modal" class="ppom-modal-box ppom-live-preview-modal">
				<header class="ppom-live-preview-header">
					<div class="ppom-live-preview-title-wrap">
						<h3><?php esc_html_e( 'Live Product Preview', 'woocommerce-product-addon' ); ?></h3>
					</div>
				</header>
				<div class="ppom-modal-body">
					<div class="ppom-preview-controls">
						<div class="ppom-preview-product-control">
							<label for="ppom-preview-product-select"><?php esc_html_e( 'Preview product', 'woocommerce-product-addon' ); ?></label>
							<div class="ppom-preview-select-row">
								<div class="ppom-preview-select-cell">
									<select id="ppom-preview-product-select" class="form-control"></select>
								</div>
								<button type="button" class="btn btn-primary ppom-preview-refresh">
									<?php esc_html_e( 'Refresh', 'woocommerce-product-addon' ); ?>
								</button>
							</div>
						</div>
					</div>
					<div class="ppom-preview-notice ppom-preview-notice-error ppom-hide-element"></div>
					<div class="ppom-preview-notice ppom-preview-notice-warning ppom-hide-element">
						<p class="ppom-preview-empty-message"></p>
						<button type="button" class="btn btn-secondary ppom-preview-go-to-assignment"></button>
					</div>
					<div class="ppom-preview-iframe-wrap ppom-hide-element">
						<iframe
							id="ppom-live-preview-iframe"
							title="<?php esc_attr_e( 'Product page preview', 'woocommerce-product-addon' ); ?>"
							loading="lazy"
						></iframe>
					</div>
				</div>
				<footer>
					<button type="button" class="btn btn-default ppom-js-modal-close"><?php esc_html_e( 'Close', 'woocommerce-product-addon' ); ?></button>
				</footer>
			</div>
		<?php } ?>
	</div>
</div>

<!-- Upgrade to pro modal -->
<div id="ppom-lock-fields-upsell" class="ppom-modal-box ppom-upsell-modal" style="display: none;">
	<?php
	$license     = get_option( 'ppom_pro_license_data', 'free' );
	$license_key = '';
	$download_id = '';
	if ( ! empty( $license ) && ( is_object( $license ) && isset( $license->download_id ) ) ) {
		$license_key = $license->key;
		$download_id = $license->download_id;
	}
	$admin_license_url = admin_url( 'options-general.php#ppom_pro_license' );
	$renew_license_url = tsdk_translate_link( tsdk_utmify( PPOM_STORE_URL . '?edd_license_key=' . $license_key . '&download_id=' . $download_id, 'ppom_license_block' ) );
	?>
	<div class="ppom-modal-body">
		<button type="button" aria-label="close" class="close-model ppom-js-modal-close"><span class="dashicons dashicons-no-alt"></span></button>
		<div class="ppom-lock-icon">
			<span class="dashicons dashicons-lock"></span>		
		</div>
		<h3><?php esc_html_e( 'Alert!', 'woocommerce-product-addon' ); ?></h3>
		<p>
			<?php esc_html_e( 'In order to edit premium fields, benefit from updates and support for PPOM Premium plugin, please renew your license code or activate it.', 'woocommerce-product-addon' ); ?>
		</p>
		<div class="ppom-upsell-button">
			<a class="btn btn-info mr-3" href="<?php echo esc_url( $renew_license_url ); ?>" target="_blank"><span class="dashicons dashicons-cart"></span> <?php esc_html_e( 'Renew License', 'woocommerce-product-addon' ); ?></a>
			<a class="btn btn-success" href="<?php echo esc_url( $admin_license_url ); ?>" target="_blank"><span class="dashicons dashicons-unlock"></span> <?php esc_html_e( 'Activate License', 'woocommerce-product-addon' ); ?></a>
		</div>
	</div>
</div>

<div class="checker">
	<?php $form_meta->render_field_settings(); ?>
</div>
