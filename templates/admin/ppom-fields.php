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

$ppom                   = '';
$productmeta_name       = '';
$dynamic_price_hide     = '';
$send_file_attachment   = '';
$show_cart_thumb        = '';
$aviary_api_key         = '';
$productmeta_style      = '';
$productmeta_js         = '';
$productmeta_categories = '';
$product_meta_id        = 0;
$product_meta           = array();
$ppom_field_index       = 1;

if ( ( isset( $_REQUEST['_wpnonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ), 'ppom_meta_edit' ) ) && ( isset( $_REQUEST ['do_meta'] ) && 'edit' === $_REQUEST ['do_meta'] ) ) {
	$product_meta_id = isset( $_REQUEST ['productmeta_id'] ) ? intval( $_REQUEST ['productmeta_id'] ) : 0;
	$ppom            = new PPOM_Meta();
	$ppom_settings   = $ppom->get_settings_by_id( $product_meta_id );

	$productmeta_name       = ( isset( $ppom_settings->productmeta_name ) ? stripslashes( $ppom_settings->productmeta_name ) : '' );
	$dynamic_price_hide     = ( isset( $ppom_settings->dynamic_price_display ) ? $ppom_settings->dynamic_price_display : '' );
	$send_file_attachment   = ( isset( $ppom_settings->send_file_attachment ) ? $ppom_settings->send_file_attachment : '' );
	$show_cart_thumb        = ( isset( $ppom_settings->show_cart_thumb ) ? $ppom_settings->show_cart_thumb : '' );
	$aviary_api_key         = ( isset( $ppom_settings->aviary_api_key ) ? $ppom_settings->aviary_api_key : '' );
	$productmeta_style      = ( isset( $ppom_settings->productmeta_style ) ? $ppom_settings->productmeta_style : '' );
	$productmeta_js         = ( isset( $ppom_settings->productmeta_js ) ? $ppom_settings->productmeta_js : '' );
	$productmeta_categories = ( isset( $ppom_settings->productmeta_categories ) ? $ppom_settings->productmeta_categories : '' );
	$product_meta           = json_decode( $ppom_settings->the_meta, true );
}

$url_cancel = add_query_arg(
	array(
		'action'         => false,
		'productmeta_id' => false,
		'do_meta'        => false,
	)
);

echo '<p><a class="btn btn-primary" href="' . esc_url( $url_cancel ) . '">' . esc_html__( '&laquo; Existing Product Meta', 'woocommerce-product-addon' ) . '</a></p>';

$product_id = isset( $_GET['product_id'] ) ? intval( $_GET['product_id'] ) : 0;

?>

<div class="ppom-admin-fields-wrapper">

	<!-- All fields inputs name show -->
	<div id="ppom_fields_model_id" class="ppom-modal-box ppom-fields-name-model">
		<header>
			<h3><?php esc_html_e( 'Select Field', 'woocommerce-product-addon' ); ?></h3>
		</header>
		<div class="ppom-modal-body">
			<ul class="list-group list-inline">
				<?php
				foreach ( PPOM()->inputs as $field_type => $meta ) {

					if ( $meta != null ) {
						$fields_title = isset( $meta->title ) ? $meta->title : null;
						$fields_icon  = isset( $meta->icon ) ? $meta->icon : null;
						?>
						<li class="ppom_select_field list-group-item"
							data-field-type="<?php echo esc_attr( $field_type ); ?>">
							<span class="ppom-fields-icon">
								<?php echo wp_kses_post( $fields_icon ); ?>
							</span>
							<span>
								<?php echo esc_html( $fields_title ); ?>
							</span>
						</li>
						<?php
					}
				}

				// show only if pro is not activated.
				if( ! ppom_pro_is_installed() ) {
					foreach( PPOM_Freemium::get_instance()->get_pro_fields() as $field ) {
						?>
							<li onclick="return;" class="ppom_select_field list-group-item locked">
								<span class="ppom-fields-icon">
									<?php echo wp_kses_post( $field['icon'] ); ?>
								</span>
								<span>
									<?php echo esc_html( $field['title'] ); ?>
								</span>
								<span>
									<i class="fa fa-lock" aria-hidden="true"></i>
								</span>
								<span class="upsell-btn-wrapper">
									<a target="_blank" href="<?php echo esc_url( tsdk_utmify('https://themeisle.com/plugins/ppom-pro/upgrade/','lockedfields') ); ?>">Get Pro</a>
								</span>
							</li>
						<?php
					}
				}
				?>
			</ul>
		</div>
		<footer>
			<button type="button"
					class="btn btn-default close-model ppom-js-modal-close"><?php esc_html_e( 'Close', 'woocommerce-product-addon' ); ?></button>
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

			<input type="hidden" name="productmeta_id" value="<?php echo esc_attr( $product_meta_id ); ?>">
			<input type="hidden" name="product_id" value="<?php echo esc_attr( $product_id ); ?>">


			<div class="ppom-basic-setting-section">
				<h2 class="ppom-heading-style"><?php esc_html_e( 'Product Meta Basic Settings', 'woocommerce-product-addon' ); ?><span></span></h2>
				<div class="ppom-tabs-init ppom-admin-tabs-css">
					<!--General Tab-->
					<input type="radio" name="css-tabs" id="ppom-general-tab" checked>
					<label for="ppom-general-tab" class="ppom-tab-label">General</label>
					<div class="ppom-admin-tab-content">
						<div class="row">
							<div class="col-md-6 col-sm-12">
								<div class="form-group">
									<label><?php esc_html_e( 'Meta group name', 'woocommerce-product-addon' ); ?>
										<span class="ppom-helper-icon" data-ppom-tooltip="ppom_tooltip"
											  title="<?php esc_html_e( 'For your reference.', 'woocommerce-product-addon' ); ?>"><i
													class="dashicons dashicons-editor-help"></i></span>
									</label>
									<input type="text" class="form-control" maxlength="50" name="productmeta_name"
										   value="<?php echo esc_attr( $productmeta_name ); ?>">
								</div>
							</div>
							<div class="col-md-6 col-sm-12">
								<div class="form-group">
									<label><?php esc_html_e( 'Control price display on product page', 'woocommerce-product-addon' ); ?>
										<span class="ppom-helper-icon" data-ppom-tooltip="ppom_tooltip"
											  title="<?php esc_html_e( 'Control how price table will be shown for options or disable.', 'woocommerce-product-addon' ); ?>"><i
													class="dashicons dashicons-editor-help"></i></span>
									</label>
									<select name="dynamic_price_hide" class="form-control">
										<option value="no"><?php esc_html_e( 'Select Option', 'woocommerce-product-addon' ); ?></option>
										<option value="hide" <?php selected( $dynamic_price_hide, 'hide' ); ?>><?php esc_html_e( 'Do Not Show Price Table', 'woocommerce-product-addon' ); ?></option>
										<option value="option_sum" <?php selected( $dynamic_price_hide, 'option_sum' ); ?>><?php esc_html_e( "Show Only Option's Total", 'woocommerce-product-addon' ); ?></option>
										<option value="all_option" <?php selected( $dynamic_price_hide, 'all_option' ); ?>><?php esc_html_e( "Show Each Option's Price", 'woocommerce-product-addon' ); ?></option>
									</select>
								</div>
							</div>
							<div class="col-md-6 col-sm-12">
								<div class="form-group">
									<label><?php esc_html_e( 'Apply for Categories', 'woocommerce-product-addon' ); ?>
										<span class="ppom-helper-icon" data-ppom-tooltip="ppom_tooltip"
											  title="<?php esc_html_e( 'If you want to apply this meta against categories, type here each category SLUG per line. For All type: All. Leave blank for default.', 'woocommerce-product-addon' ); ?>"><i
													class="dashicons dashicons-editor-help"></i></span>
									</label>
									<textarea class="form-control" name="productmeta_categories"><?php echo esc_html( stripslashes( $productmeta_categories ) ); ?></textarea>
								</div>
							</div>
						</div>
						<?php
						do_action( 'ppom_field_meta_general_tab', $ppom );
						?>
					</div>

					<!--Style Tab-->
					<input type="radio" name="css-tabs" id="ppom-style-tab">
					<label for="ppom-style-tab" class="ppom-tab-label">Style</label>
					<div class="ppom-admin-tab-content">
						<div class="row">
							<div class="col-md-6 col-sm-12">
								<div class="form-group">
									<label><?php esc_html_e( 'Custom CSS', 'woocommerce-product-addon' ); ?>
										<span class="ppom-helper-icon" data-ppom-tooltip="ppom_tooltip"
											  title="<?php esc_html_e( 'Add your own CSS.', 'woocommerce-product-addon' ); ?>"><i
													class="dashicons dashicons-editor-help"></i></span>
									</label>
									<textarea id="ppom-css-editor" class="form-control" name="productmeta_style"><?php echo esc_html( wp_unslash( $productmeta_style ) ); ?></textarea>
								</div>
							</div>
							<div class="col-md-6 col-sm-12">
								<div class="form-group">
									<label><?php esc_html_e( 'Custom Javascipt', 'woocommerce-product-addon' ); ?>
										<span class="ppom-helper-icon" data-ppom-tooltip="ppom_tooltip"
											  title="<?php esc_html_e( 'Add your own javascipt script.', 'woocommerce-product-addon' ); ?>"><i
													class="dashicons dashicons-editor-help"></i></span>
									</label>
									<textarea id="ppom-js-editor" class="form-control" name="productmeta_js"><?php echo esc_html( wp_unslash( $productmeta_js ) ); ?></textarea>
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
						<div data-saved_dataname="<?php echo esc_attr($the_field_id); ?>" id="ppom_field_model_<?php echo esc_attr( $f_index ); ?>"
							 class="ppom-modal-box ppom-slider ppom_sort_id_<?php echo esc_attr( $f_index ); ?>">
							<div class="ppom-model-content">

								<header>
									<h3>
										<?php echo esc_html( $field_type ); ?>
										<span class="ppom-dataname-reader">(<?php echo esc_html( $the_field_id ); ?>)</span>
									</h3>
								</header>
								<div class="ppom-modal-body">
									<?php
										$form_meta->render_field_meta( $defualt_fields, $field_type, $f_index, $field_meta );
									?>
								</div>
								<footer>
									<span class="ppom-req-field-id"></span>
									<button type="button"
											class="btn btn-default close-model ppom-js-modal-close"><?php esc_html_e( 'Close', 'woocommerce-product-addon' ); ?></button>
									<button class="btn btn-primary ppom-update-field ppom-add-fields-js-action"
											data-field-index='<?php echo esc_attr( $f_index ); ?>'
											data-field-type='<?php echo esc_attr( $field_type ); ?>'><?php esc_html_e( 'Update Field', 'woocommerce-product-addon' ); ?></button>
								</footer>
								<?php
								$ppom_field_index = $f_index;
								$ppom_field_index ++;
								$f_index ++;
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
			<div class="table-responsive">
				<h2 class="ppom-heading-style"><?php esc_html_e( 'Add PPOM Fields', 'woocommerce-product-addon' ); ?></h2>
				<table class="table ppom_field_table  table-striped">
					<thead>
					<tr>
						<th colspan="6">
							<button type="button" class="btn btn-primary"
									data-modal-id="ppom_fields_model_id"><?php esc_html_e( 'Add field', 'woocommerce-product-addon' ); ?></button>
							<button type="button"
									class="btn btn-danger ppom_remove_field"><?php esc_html_e( 'Remove', 'woocommerce-product-addon' ); ?></button>
						</th>
					</tr>
					<tr class="ppom-thead-bg">
						<th></th>
						<th class="ppom-check-all-field ppom-checkboxe-style">
							<label>
								<input type="checkbox">
								<span></span>
							</label>
						</th>
						<th><?php esc_html_e( 'Status', 'woocommerce-product-addon' ); ?></th>
						<th><?php esc_html_e( 'Data Name', 'woocommerce-product-addon' ); ?></th>
						<th><?php esc_html_e( 'Type', 'woocommerce-product-addon' ); ?></th>
						<th><?php esc_html_e( 'Title', 'woocommerce-product-addon' ); ?></th>
						<th><?php esc_html_e( 'Placeholder', 'woocommerce-product-addon' ); ?></th>
						<th><?php esc_html_e( 'Required', 'woocommerce-product-addon' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'woocommerce-product-addon' ); ?></th>
					</tr>
					</thead>
					<tfoot>
					<tr class="ppom-thead-bg">
						<th></th>
						<th class="ppom-check-all-field ppom-checkboxe-style">
							<label>
								<input type="checkbox">
								<span></span>
							</label>
						</th>
						<th><?php esc_html_e( 'Status', 'woocommerce-product-addon' ); ?></th>
						<th><?php esc_html_e( 'Data Name', 'woocommerce-product-addon' ); ?></th>
						<th><?php esc_html_e( 'Type', 'woocommerce-product-addon' ); ?></th>
						<th><?php esc_html_e( 'Title', 'woocommerce-product-addon' ); ?></th>
						<th><?php esc_html_e( 'Placeholder', 'woocommerce-product-addon' ); ?></th>
						<th><?php esc_html_e( 'Required', 'woocommerce-product-addon' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'woocommerce-product-addon' ); ?></th>
					</tr>
					<tr>
						<th colspan="12">
							<div class="ppom-submit-btn text-right">
								<span class="ppom-meta-save-notice"></span>
								<input type="submit" class="btn btn-primary"
									   value="<?php esc_html_e( 'Save Fields', 'woocommerce-product-addon' ); ?>">
							</div>
						</th>
					</tr>
					</tfoot>
					<tbody>
					<?php
					if ( $product_meta ) {

						$f_index = 1;
						foreach ( $product_meta as $field_index => $field_meta ) {

							$field_type      = isset( $field_meta['type'] ) ? $field_meta['type'] : '';
							$the_title       = isset( $field_meta['title'] ) ? $field_meta['title'] : '';
							$the_field_id    = isset( $field_meta['data_name'] ) ? $field_meta['data_name'] : '';
							$the_placeholder = isset( $field_meta['placeholder'] ) ? $field_meta['placeholder'] : '';
							$the_required    = isset( $field_meta['required'] ) ? $field_meta['required'] : '';
							$field_status    = isset( $field_meta['status'] ) ? $field_meta['status'] : 'on';
							// ppom_pa($field_status);
							if ( $the_required == 'on' ) {
								$_ok = 'Yes';
							} else {
								$_ok = 'No';
							}
							?>

							<tr class="row_no_<?php echo esc_attr( $f_index ); ?>"
								id="ppom_sort_id_<?php echo esc_attr( $f_index ); ?>">
								<td class="ppom-sortable-handle">
									<i class="fa fa-arrows" aria-hidden="true"></i>
								</td>
								<td class="ppom-check-one-field ppom-checkboxe-style">
									<label>
										<input type="checkbox" value="<?php echo esc_attr( $f_index ); ?>">
										<span></span>
									</label>
								</td>
								<td>
									<div class="onoffswitch">
										<input <?php echo checked( $field_status, 'on' ); ?> type="checkbox"
																							 class="onoffswitch-checkbox"
																							 id="ppom-onoffswitch-<?php echo esc_attr( $f_index ); ?>"
																							 tabindex="0">
										<label class="onoffswitch-label"
											   for="ppom-onoffswitch-<?php echo esc_attr( $f_index ); ?>">
											<span class="onoffswitch-inner"></span>
											<span class="onoffswitch-switch"></span>
										</label>
										<input type="hidden" value="<?php echo esc_attr( $field_status ); ?>"
											   name="ppom[<?php echo esc_attr( $f_index ); ?>][status]">
									</div>
								</td>
								<td class="ppom_meta_field_id"><?php echo esc_html( $the_field_id ); ?></td>
								<td class="ppom_meta_field_type"><?php echo esc_html( $field_type ); ?></td>
								<td class="ppom_meta_field_title"><?php echo esc_html( $the_title ); ?></td>
								<td class="ppom_meta_field_plchlder"><?php echo esc_html( $the_placeholder ); ?></td>
								<td class="ppom_meta_field_req"><?php echo esc_html( $_ok ); ?></td>
								<td>
									<button class="btn  ppom_copy_field"
											data-field-type="<?php echo esc_attr( $field_type ); ?>"
											title="<?php esc_html_e( 'Copy Field', 'woocommerce-product-addon' ); ?>"
											id="<?php echo esc_attr( $f_index ); ?>"><span
												class="dashicons dashicons-admin-page"></span></span></i></button>
									<button class="btn ppom-edit-field"
											data-modal-id="ppom_field_model_<?php echo esc_attr( $f_index ); ?>"
											id="<?php echo esc_attr( $f_index ); ?>"
											title="<?php esc_html_e( 'Edit Field', 'woocommerce-product-addon' ); ?>"><span
												class="dashicons dashicons-edit"></span></button>
								</td>
							</tr>
							<?php
							$ppom_field_index = $f_index;
							$ppom_field_index ++;
							$f_index ++;
						}
					}
					?>
					</tbody>
				</table>
			</div>
		</form>
	</div>
</div>

<br><p><a class="btn btn-primary" href="<?php echo esc_url( $url_cancel ); ?>"><?php echo esc_html_e( '&laquo; Existing Product Meta', 'woocommerce-product-addon' ); ?></a>
</p>

<div class="checker">
	<?php $form_meta->render_field_settings(); ?>
</div>
