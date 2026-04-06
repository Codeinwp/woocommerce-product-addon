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
$product_meta_id        = 0;
$product_meta           = array();
$ppom_field_index       = 1;
$is_edit_screen         = false;
$is_new_group           = false;

if ( isset( $_REQUEST['action'] ) && $_REQUEST['action'] == 'new' ) {
	$is_edit_screen = true;
	$is_new_group   = true;
}
if ( isset( $_REQUEST['productmeta_id'] ) && $_REQUEST ['do_meta'] == 'edit' ) {

	$product_meta_id = intval( $_REQUEST['productmeta_id'] );
	$ppom            = new PPOM_Meta();
	$ppom_settings   = $ppom->get_settings_by_id( $product_meta_id );
	$is_edit_screen  = true;

	$productmeta_name       = ( isset( $ppom_settings->productmeta_name ) ? stripslashes( $ppom_settings->productmeta_name ) : '' );
	$dynamic_price_hide     = ( isset( $ppom_settings->dynamic_price_display ) ? $ppom_settings->dynamic_price_display : '' );
	$send_file_attachment   = ( isset( $ppom_settings->send_file_attachment ) ? $ppom_settings->send_file_attachment : '' );
	$show_cart_thumb        = ( isset( $ppom_settings->show_cart_thumb ) ? $ppom_settings->show_cart_thumb : '' );
	$aviary_api_key         = ( isset( $ppom_settings->aviary_api_key ) ? $ppom_settings->aviary_api_key : '' );
	$productmeta_style      = ! empty( $ppom_settings->productmeta_style ) ? $ppom_settings->productmeta_style : "selector {\n}\n";
	$productmeta_js         = ( isset( $ppom_settings->productmeta_js ) ? $ppom_settings->productmeta_js : '' );
	$product_meta           = json_decode( $ppom_settings->the_meta, true );
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

$license_data      = get_option( 'ppom_pro_license_data', array() );
$plan_category     = NM_PersonalizedProduct::LICENSE_PLAN_FREE;
$is_pro_installed  = ppom_pro_is_installed();
$license_status    = $is_pro_installed && ! empty( $license_data->license ) ? $license_data->license : 'invalid';

if ( $is_pro_installed && isset( $license_data->plan ) && is_numeric( $license_data->plan ) ) {
	$plan_category = NM_PersonalizedProduct::get_license_category( intval( $license_data->plan ) );
}

$fields_groups = [
	'text-group' => [
		'label' => __( 'Text', 'woocommerce-product-addon' ),
		'fields' => [
			[
				'slug'        => 'text',
				'title'       => __( 'Text Input', 'woocommerce-product-addon' ),
				'description' => __( 'Simple text field', 'woocommerce-product-addon' ),
				'icon'        => 'fa-pencil-square-o'
			],
			[
				'slug'        => 'textarea',
				'title'       => __( 'Textarea Input', 'woocommerce-product-addon' ),
				'description' => __( 'Simple area field', 'woocommerce-product-addon' ),
				'icon'        => 'fa-file-text-o'
			],
			[
				'slug'        => 'email',
				'title'       => __( 'Email Input', 'woocommerce-product-addon' ),
				'description' => __( 'Simple email field', 'woocommerce-product-addon' ),
				'icon'        => 'fa-user-plus'
			],
			[
				'slug'        => 'number',
				'title'       => __( 'Number', 'woocommerce-product-addon' ),
				'description' => __( 'Number Input', 'woocommerce-product-addon' ),
				'icon'        => 'fa-hashtag',
			],
			[
				'slug'        => 'hidden',
				'title'       => __( 'Hidden Input', 'woocommerce-product-addon' ),
				'description' => __( 'Simple hidden field', 'woocommerce-product-addon' ),
				'icon'        => 'fa-hashtag'
			],
			[
				'slug'        => 'phone',
				'title'       => __( 'Phone Input', 'woocommerce-product-addon' ),
				'description' => __( 'Simple Phone field', 'woocommerce-product-addon' ),
				'plan'        => NM_PersonalizedProduct::LICENSE_PLAN_1,
				'icon'        => 'fa-check',
			]
		]
	],
	'choices' => [
		'label' => __( 'Choices', 'woocommerce-product-addon' ),
		'fields' => [
			[
				'slug'        => 'checkbox',
				'title'       => __( 'Checkbox', 'woocommerce-product-addon' ),
				'description' => __( 'Checkbox Input', 'woocommerce-product-addon' ),
				'icon'        => 'fa-check-square-o',
			],
			[
				'slug'        => 'radio',
				'title'       => __( 'Radio', 'woocommerce-product-addon' ),
				'description' => __( 'Radio Input', 'woocommerce-product-addon' ),
				'icon'        => 'fa-dot-circle-o',
			],
			[
				'slug'        => 'select',
				'title'       => __( 'Select', 'woocommerce-product-addon' ),
				'description' => __( 'Select Input', 'woocommerce-product-addon' ),
				'icon'        => 'fa-check',
			],
			[
				'slug'        => 'switcher',
				'title'       => __( 'Radio Switcher', 'woocommerce-product-addon' ),
				'description' => __( 'Radio button switcher', 'woocommerce-product-addon' ),
				'plan'        => NM_PersonalizedProduct::LICENSE_PLAN_1,
				'icon'        => 'fa-dot-circle-o',
			],
			[
				'slug'        => 'superlist',
				'title'       => __( 'Super List', 'woocommerce-product-addon' ),
				'description' => __( 'Advanced list input', 'woocommerce-product-addon' ),
				'plan'        => NM_PersonalizedProduct::LICENSE_PLAN_1,
				'icon'        => 'fa-check',
			],
			[
				'slug'        => 'chained',
				'title'       => __( 'Chained Input', 'woocommerce-product-addon' ),
				'description' => __( 'Linked dropdown selections where choices in one field depend on previous selections.', 'woocommerce-product-addon' ),
				'plan'        => NM_PersonalizedProduct::LICENSE_PLAN_1,
				'icon'        => 'fa-check',
			],
		]
	],
	'visual-group' => [
		'label' => __( 'Media', 'woocommerce-product-addon' ),
		'fields' => [

			[
				'slug'        => 'file',
				'title'       => __( 'File Input', 'woocommerce-product-addon' ),
				'description' => __( 'File upload field', 'woocommerce-product-addon' ),
				'plan'        => NM_PersonalizedProduct::LICENSE_PLAN_1,
				'icon'        => 'fa-file',
			],
			[
				'slug'        => 'emojis',
				'title'       => __( 'Emojis', 'woocommerce-product-addon' ),
				'description' => __( 'Emoji picker for input fields', 'woocommerce-product-addon' ),
				'plan'        => NM_PersonalizedProduct::LICENSE_PLAN_1,
				'icon'        => 'fa-user-plus',
			],
			[
				'slug'        => 'conditional_meta',
				'title'       => __( 'Conditional Images', 'woocommerce-product-addon' ),
				'description' => __( 'Images that change based on input conditions', 'woocommerce-product-addon' ),
				'plan'        => NM_PersonalizedProduct::LICENSE_PLAN_1,
				'icon'        => 'fa-picture-o',
			],
			[
				'slug'        => 'image',
				'title'       => __( 'Images', 'woocommerce-product-addon' ),
				'description' => __( 'Select images.', 'woocommerce-product-addon' ),
				'plan'        => NM_PersonalizedProduct::LICENSE_PLAN_1,
				'icon'        => 'fa-picture-o',
			],
			[
				'slug'        => 'imageselect',
				'title'       => __( 'Image DropDown', 'woocommerce-product-addon' ),
				'description' => __( 'Dropdown with image selections', 'woocommerce-product-addon' ),
				'plan'        => NM_PersonalizedProduct::LICENSE_PLAN_1,
				'icon'        => 'fa-file-image-o',
			],
			[
				'slug'        => 'audio',
				'title'       => __( 'Audio / Video', 'woocommerce-product-addon' ),
				'description' => __( 'Audio and video input field', 'woocommerce-product-addon' ),
				'plan'        => NM_PersonalizedProduct::LICENSE_PLAN_1,
				'icon'        => 'fa-file-video-o',
			],
			[
				'slug'        => 'cropper',
				'title'       => __( 'Image Cropper', 'woocommerce-product-addon' ),
				'description' => __( 'Image cropping tool', 'woocommerce-product-addon' ),
				'plan'        => NM_PersonalizedProduct::LICENSE_PLAN_1,
				'icon'        => 'fa-crop',
			],
			[
				'slug'        => 'texter',
				'title'       => __( 'Personalization Preview', 'woocommerce-product-addon' ),
				'description' => __( 'Preview for personalized products', 'woocommerce-product-addon' ),
				'plan'        => NM_PersonalizedProduct::LICENSE_PLAN_1,
				'icon'        => 'fa-keyboard-o',
			]
		]
	],
	'advanced-group' => [
		'label' => __( 'Special Format', 'woocommerce-product-addon' ),
		'fields' => [
			[
				'slug'        => 'date',
				'title'       => __( 'Date', 'woocommerce-product-addon' ),
				'description' => __( 'Date input field', 'woocommerce-product-addon' ),
				'icon'        => 'fa-calendar',
			],
			[
				'slug'        => 'timezone',
				'title'       => __( 'Timezone Input', 'woocommerce-product-addon' ),
				'description' => __( 'Timezone selector for input fields', 'woocommerce-product-addon' ),
				'plan'        => NM_PersonalizedProduct::LICENSE_PLAN_1,
				'icon'        => 'fa-clock-o',
			],

			[
				'slug'        => 'daterange',
				'title'       => __( 'DateRange Input', 'woocommerce-product-addon' ),
				'description' => __( 'Date range input field', 'woocommerce-product-addon' ),
				'plan'        => NM_PersonalizedProduct::LICENSE_PLAN_1,
				'icon'        => 'fa-table',
			],
			[
				'slug'        => 'section',
				'title'       => __( 'HTML', 'woocommerce-product-addon' ),
				'description' => __( 'Custom HTML input field', 'woocommerce-product-addon' ),
				'plan'        => NM_PersonalizedProduct::LICENSE_PLAN_1,
				'icon'        => 'fa-code',
			],

			[
				'slug'        => 'color',
				'title'       => __( 'Color Picker', 'woocommerce-product-addon' ),
				'description' => __( 'Color picker input field', 'woocommerce-product-addon' ),
				'plan'        => NM_PersonalizedProduct::LICENSE_PLAN_1,
				'icon'        => 'fa-modx',
			],
			[
				'slug'        => 'palettes',
				'title'       => __( 'Color Palettes', 'woocommerce-product-addon' ),
				'description' => __( 'Color palette selection', 'woocommerce-product-addon' ),
				'plan'        => NM_PersonalizedProduct::LICENSE_PLAN_1,
				'icon'        => 'fa-user-plus',
			],
			[
				'slug'        => 'fonts',
				'title'       => __( 'Fonts Picker', 'woocommerce-product-addon' ),
				'description' => __( 'Font selection tool', 'woocommerce-product-addon' ),
				'plan'        => NM_PersonalizedProduct::LICENSE_PLAN_1,
				'icon'        => 'fa-font',
			],

			[
				'slug'        => 'domain',
				'title'       => __( 'Domain', 'woocommerce-product-addon' ),
				'description' => __( 'Domain input field', 'woocommerce-product-addon' ),
				'plan'        => NM_PersonalizedProduct::LICENSE_PLAN_3,
				'icon'        => 'fa-server',
			],
			[
				'slug'        => 'textcounter',
				'title'       => __( 'Text Counter', 'woocommerce-product-addon' ),
				'description' => __( 'Character count for input fields', 'woocommerce-product-addon' ),
				'plan'        => NM_PersonalizedProduct::LICENSE_PLAN_1,
				'icon'        => 'fa-comments-o',
			],
			[
				'slug'        => 'collapse',
				'title'       => __( 'Collapse', 'woocommerce-product-addon' ),
				'description' => __( 'Group and toggle other input fields within a collapsible section, helping to organize long forms more efficiently.', 'woocommerce-product-addon' ),
				'plan'        => NM_PersonalizedProduct::LICENSE_PLAN_1,
				'icon'        => 'fa-money',
			],
			[
				'slug'        => 'divider',
				'title'       => __( 'Divider', 'woocommerce-product-addon' ),
				'description' => __( 'Add a visual separator.', 'woocommerce-product-addon' ),
				'plan'        => NM_PersonalizedProduct::LICENSE_PLAN_1,
				'icon'        => 'fa-pencil-square-o',
			]
		]
	],
	'pricing-group' => [
		'label' => __( 'Pricing & Quantity', 'woocommerce-product-addon' ),
		'fields' => [
			[
				'slug'        => 'fixedprice',
				'title'       => __( 'Fixed Price', 'woocommerce-product-addon' ),
				'description' => __( 'Fixed pricing options', 'woocommerce-product-addon' ),
				'plan'        => NM_PersonalizedProduct::LICENSE_PLAN_1,
				'icon'        => 'fa-money',
			],
			[
				'slug'        => 'quantities',
				'title'       => __( 'Variation Quantity ', 'woocommerce-product-addon' ),
				'description' => __( 'Quantity selection with options', 'woocommerce-product-addon' ),
				'plan'        => NM_PersonalizedProduct::LICENSE_PLAN_2,
				'icon'        => 'fa-list-ol',
			],
			[
				'slug'        => 'pricematrix',
				'title'       => __( 'Price Matrix', 'woocommerce-product-addon' ),
				'description' => __( 'Matrix-based pricing', 'woocommerce-product-addon' ),
				'plan'        => NM_PersonalizedProduct::LICENSE_PLAN_1,
				'icon'        => 'fa-usd',
			],
			[
				'slug'        => 'qtypack',
				'title'       => __( 'Quantities Pack', 'woocommerce-product-addon' ),
				'description' => __( 'Pack-based quantity options', 'woocommerce-product-addon' ),
				'plan'        => NM_PersonalizedProduct::LICENSE_PLAN_1,
				'icon'        => 'fa-list-alt',
			],
			[
				'slug'        => 'vqmatrix',
				'title'       => __( 'Variation Matrix', 'woocommerce-product-addon' ),
				'description' => __( 'Quantity selector for variations', 'woocommerce-product-addon' ),
				'plan'        => NM_PersonalizedProduct::LICENSE_PLAN_3,
				'icon'        => 'fa-list-ol',
			],
			[
				'slug'        => 'measure',
				'title'       => __( 'Measure Input', 'woocommerce-product-addon' ),
				'description' => __( 'Measurement input field', 'woocommerce-product-addon' ),
				'plan'        => NM_PersonalizedProduct::LICENSE_PLAN_1,
				'icon'        => 'fa-building-o',
			],
			[
				'slug'        => 'quantityoption',
				'title'       => __( 'Quantity Option', 'woocommerce-product-addon' ),
				'description' => __( 'Quantity selection options', 'woocommerce-product-addon' ),
				'plan'        => NM_PersonalizedProduct::LICENSE_PLAN_1,
				'icon'        => 'fa-money',
			],
			[
				'slug'        => 'bulkquantity',
				'title'       => __( 'Bulk Quantity', 'woocommerce-product-addon' ),
				'description' => __( 'Bulk quantity selection for products', 'woocommerce-product-addon' ),
				'plan'        => NM_PersonalizedProduct::LICENSE_PLAN_1,
				'icon'        => 'fa-columns',
			],
			[
				'slug'        => 'selectqty',
				'title'       => __( 'Select Option Quantity', 'woocommerce-product-addon' ),
				'description' => __( 'Select option\'s quantity', 'woocommerce-product-addon' ),
				'plan'        => NM_PersonalizedProduct::LICENSE_PLAN_1,
				'icon'        => 'fa-list-ol',
			],
		]
	]
];

?>

<div class="ppom-admin-fields-wrapper">

	<!-- All fields inputs name show -->
	<div id="ppom_fields_model_id" class="ppom-modal-box ppom-fields-name-model">
		<header class="ppom-modal-header">
			<h3><?php _e( 'Select Field Type', 'woocommerce-product-addon' ); ?></h3>
			<div class="ppom-search-container">
				<input type="text" name="ppom-search-field" placeholder="<?php _e( 'Search Fields', 'woocommerce-product-addon' )?>" />
				<span class="ppom-search-icon">
					<i class="fa fa-search" aria-hidden="true"></i>
				</span>
			</div>
		</header>
		<div class="ppom-modal-body ppom-modal-add-field">
			<div class="ppom-fields">
				<div class="ppom-modal-shortcuts">
                    <a  href="#all">
						<?php echo __( 'All', 'woocommerce-product-addon' ) ?>
                    </a>
					<?php

					foreach( $fields_groups as $group_id => $group ) {
					?>
						<a
							href="#<?php echo esc_attr( $group_id ) ?>"
						>
						<?php echo esc_html( $group['label'] ) ?>
						</a>
					<?php
					}
					?>
				</div>
				<div class="ppom-fields-sections">
					<?php
					foreach( $fields_groups as $group_id => $group ) {
						?>
						<div class="ppom-fields-section" id="<?php echo esc_attr( $group_id ) ?>-ppom-fields">
							<div class="ppom-fields-section-title">
								<h5
									id="<?php echo esc_attr( $group_id ) ?>"
								>
									<?php echo esc_html( $group['label'] ) ?>
								</h5>
							</div>
							<div
								class="ppom-fields-grid"
							>
								<?php
								foreach( $group['fields'] as $field ) {
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
												<?php _e( 'PRO', 'woocommerce-product-addon' ) ?>
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
					<h2><?php _e( 'Unlock all Features!', 'woocommerce-product-addon' ) ?></h2>
				</div>
				<div class="ppom-sidebar-upsell-content">
					<p><?php _e( 'Upgrade to the Pro plan to unlock all features and enhance your product fields management capabilities:', 'woocommerce-product-addon' ) ?></p>
					<div class="ppom-sidebar-upsell-features-grid">
						<div><?php _e( 'Unlock 30+ input fields', 'woocommerce-product-addon' ) ?></div>
						<div><?php _e( 'Meta Fields Repeater', 'woocommerce-product-addon' ) ?></div>
						<div><?php _e( 'Cart Edit', 'woocommerce-product-addon' ) ?></div>
						<div><?php _e( 'Quantities Pack', 'woocommerce-product-addon' ) ?></div>
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

			<input type="hidden" name="productmeta_id" value="<?php echo esc_attr( $product_meta_id ); ?>">
			<input type="hidden" name="product_id" value="<?php echo esc_attr( $product_id ); ?>">


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
									<input type="text" class="form-control" maxlength="50" name="productmeta_name"
										   value="<?php echo $productmeta_name; ?>">
								</div>
							</div>
							<div class="col-md-6 col-sm-12">
								<div class="form-group">
									<label><?php _e( 'Control price display on product page', 'woocommerce-product-addon' ); ?>
										<span class="ppom-helper-icon" data-ppom-tooltip="ppom_tooltip"
											  title="<?php _e( 'Control how price table will be shown for options or disable.', 'woocommerce-product-addon' ); ?>"><i
													class="dashicons dashicons-editor-help"></i></span>
									</label>
									<select name="dynamic_price_hide" class="form-control">
										<option value="no"><?php _e( 'Select Option', 'woocommerce-product-addon' ); ?></option>
										<option value="hide" <?php selected( $dynamic_price_hide, 'hide' ); ?>><?php _e( 'Do Not Show Price Table', 'woocommerce-product-addon' ); ?></option>
										<option value="option_sum" <?php selected( $dynamic_price_hide, 'option_sum' ); ?>><?php _e( "Show Only Option's Total", 'woocommerce-product-addon' ); ?></option>
										<option value="all_option" <?php selected( $dynamic_price_hide, 'all_option' ); ?>><?php _e( "Show Each Option's Price", 'woocommerce-product-addon' ); ?></option>
									</select>
								</div>

								<?php if ( $is_edit_screen && ! $is_new_group ) { ?>
								<!-- Legacy modal button kept hidden for existing-meta table compatibility -->
								<a class="btn btn-sm btn-secondary ppom-products-modal" style="display:none;"
								   data-ppom_id="<?php echo esc_attr( $product_meta_id ); ?>"
								   data-formmodal-id="ppom-product-modal"
								>
									<?php _e( 'Attach to Products', 'woocommerce-product-addon' ); ?>
								</a>
								<?php } ?>
							</div>
						</div>
						<?php if ( $is_edit_screen && ! $is_new_group ) { ?>
						<div class="row ppom-inline-attach-section">
							<div class="col-md-12">
								<h4 class="ppom-inline-attach-heading"><?php _e( 'Assign to Products & Categories', 'woocommerce-product-addon' ); ?></h4>
							</div>
							<div class="col-md-6 col-sm-12" id="ppom-inline-products-wrap">
								<div class="form-group">
									<label><?php _e( 'Products', 'woocommerce-product-addon' ); ?>
										<span class="ppom-helper-icon" data-ppom-tooltip="ppom_tooltip"
											  title="<?php _e( 'Select the product(s) where you want these fields to appear.', 'woocommerce-product-addon' ); ?>"><i
													class="dashicons dashicons-editor-help"></i></span>
									</label>
									<select id="ppom-inline-products" name="ppom-inline-products[]" class="form-control" multiple="multiple" style="width:100%">
									</select>
									<span class="ppom-inline-attach-status" id="ppom-products-status"></span>
								</div>
							</div>
							<div class="col-md-6 col-sm-12" id="ppom-inline-categories-wrap">
								<div class="form-group">
									<label><?php _e( 'Categories', 'woocommerce-product-addon' ); ?>
										<span class="ppom-helper-icon" data-ppom-tooltip="ppom_tooltip"
											  title="<?php _e( 'Select the product categories where you want these fields to appear.', 'woocommerce-product-addon' ); ?>"><i
													class="dashicons dashicons-editor-help"></i></span>
									</label>
									<select id="ppom-inline-categories" name="ppom-inline-categories[]" class="form-control" multiple="multiple" style="width:100%">
									</select>
									<span class="ppom-inline-attach-status" id="ppom-categories-status"></span>
								</div>
							</div>
							<input type="hidden" id="ppom-inline-ppom-id" value="<?php echo esc_attr( $product_meta_id ); ?>">
							<input type="hidden" id="ppom-inline-products-initial" value="">
							<input type="hidden" id="ppom-inline-categories-initial" value="">
						</div>
						<?php } ?>
						<?php
						do_action( 'ppom_field_meta_general_tab', $ppom );
						?>
					</div>

					<!--Style Tab-->
					<input type="radio" name="css-tabs" id="ppom-style-tab">
					<label for="ppom-style-tab" class="ppom-tab-label">Style</label>
					<div class="ppom-admin-tab-content">
						<?php
					$is_style_locked = ppom_is_legacy_user();
					$upgrade_style_url = tsdk_utmify( tsdk_translate_link( PPOM_UPGRADE_URL ), 'customstyle', 'ppompage' );
					?>
						<?php if ( $is_style_locked ) : ?>
						<div class="ppom-style-locked-wrapper">
							<div class="ppom-style-locked-overlay">
								<div class="ppom-style-locked-cta">
									<span class="dashicons dashicons-lock"></span>
									<strong><?php _e( 'Pro Feature', 'woocommerce-product-addon' ); ?></strong>
									<p><?php _e( 'Custom CSS and JS let you fully control how your product fields look and behave.', 'woocommerce-product-addon' ); ?></p>
									<a href="<?php echo esc_url( $upgrade_style_url ); ?>" target="_blank" class="btn btn-primary btn-sm"><?php _e( 'Upgrade to Pro', 'woocommerce-product-addon' ); ?></a>
								</div>
							</div>
						<?php endif; ?>
						<div class="row">
							<div class="col-md-6 col-sm-12">
								<div class="form-group">
									<label><?php _e( 'Custom CSS', 'woocommerce-product-addon' ); ?>
										<span class="ppom-helper-icon" data-ppom-tooltip="ppom_tooltip"
											  title="<?php _e( 'Style your product fields. Use "selector" to target the fields wrapper element.', 'woocommerce-product-addon' ); ?>"><i
													class="dashicons dashicons-editor-help"></i></span>
									</label>
									<textarea id="ppom-css-editor" class="form-control" name="productmeta_style"><?php echo wp_unslash( $productmeta_style ); ?></textarea>
									<br>
									<p><?php esc_html_e( 'Use', 'woocommerce-product-addon' ); ?> <code>selector</code> <?php esc_html_e( 'to target the fields wrapper. You can also use media queries.', 'woocommerce-product-addon' ); ?></p>

									<?php if ( ! $is_style_locked ) : ?>
									<?php $has_ai_key = PPOM_AI_Service::is_available(); ?>
									<div class="ppom-style-ai-row" style="margin-top:10px;">
										<label for="ppom-style-ai-prompt" style="font-weight:500; font-size:12px;"><?php _e( 'AI Helper', 'woocommerce-product-addon' ); ?></label>
										<?php if ( $has_ai_key ) : ?>
										<div style="display:flex; gap:6px; margin-top:4px;">
											<input type="text" id="ppom-style-ai-prompt" class="form-control" style="flex:1;" placeholder="<?php echo esc_attr__( 'e.g., Make fields look modern with rounded corners and soft shadows', 'woocommerce-product-addon' ); ?>">
											<button type="button" class="btn btn-sm btn-secondary" id="ppom-style-ai-btn"><?php _e( 'Generate', 'woocommerce-product-addon' ); ?></button>
										</div>
										<span id="ppom-style-ai-status" style="font-size:11px; color:#646970;"></span>
										<?php else : ?>
										<p style="font-size:12px; color:#646970; margin:4px 0 0;"><?php _e( 'Requires API key.', 'woocommerce-product-addon' ); ?> <a href="<?php echo esc_url( admin_url( 'admin.php?page=ppom&view=settings' ) ); ?>"><?php _e( 'Configure', 'woocommerce-product-addon' ); ?></a></p>
										<?php endif; ?>
									</div>
									<?php endif; ?>

									<div class="ppom-style-presets <?php echo $is_style_locked ? 'ppom-presets-locked' : ''; ?>" style="margin-top:12px;">
										<label style="font-weight:500; font-size:12px; margin-bottom:6px; display:block;">
											<?php _e( 'Quick Presets', 'woocommerce-product-addon' ); ?>
											<?php if ( $is_style_locked ) : ?>
												<span style="font-size:10px; background:#2271b1; color:#fff; padding:1px 6px; border-radius:3px; font-weight:600; margin-left:4px;">PRO</span>
											<?php endif; ?>
										</label>
										<div class="ppom-style-preset-buttons" style="display:flex; flex-wrap:wrap; gap:4px;">
											<button type="button" class="btn btn-sm btn-secondary ppom-css-preset" <?php echo $is_style_locked ? 'disabled' : ''; ?> data-css="selector {
    padding: 20px;
    background: #f9f9f9;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
}
selector label {
    font-weight: 600;
    color: #333;
}
selector input,
selector select,
selector textarea {
    border-radius: 6px;
    border: 1px solid #ccc;
    padding: 8px 12px;
}"><?php _e( 'Clean & Modern', 'woocommerce-product-addon' ); ?></button>
											<button type="button" class="btn btn-sm btn-secondary ppom-css-preset" <?php echo $is_style_locked ? 'disabled' : ''; ?> data-css="selector {
    padding: 24px;
    background: #ffffff;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
}
selector label {
    font-size: 14px;
    font-weight: 500;
    margin-bottom: 6px;
}
selector input,
selector select,
selector textarea {
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    padding: 10px 14px;
    transition: border-color 0.2s;
}
selector input:focus,
selector select:focus,
selector textarea:focus {
    border-color: #3b82f6;
    outline: none;
    box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
}"><?php _e( 'Soft Shadow Card', 'woocommerce-product-addon' ); ?></button>
											<button type="button" class="btn btn-sm btn-secondary ppom-css-preset" <?php echo $is_style_locked ? 'disabled' : ''; ?> data-css="selector {
    padding: 20px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 12px;
    color: #fff;
}
selector label {
    color: #fff;
    font-weight: 600;
}
selector input,
selector select,
selector textarea {
    background: rgba(255,255,255,0.15);
    border: 1px solid rgba(255,255,255,0.3);
    border-radius: 6px;
    color: #fff;
    padding: 8px 12px;
}
selector input::placeholder,
selector textarea::placeholder {
    color: rgba(255,255,255,0.6);
}"><?php _e( 'Gradient Bold', 'woocommerce-product-addon' ); ?></button>
											<button type="button" class="btn btn-sm btn-secondary ppom-css-preset" <?php echo $is_style_locked ? 'disabled' : ''; ?> data-css="selector {
    padding: 16px;
    border: 2px solid #1a1a1a;
    background: #fff;
}
selector label {
    font-family: inherit;
    font-weight: 700;
    text-transform: uppercase;
    font-size: 11px;
    letter-spacing: 1px;
    color: #1a1a1a;
}
selector input,
selector select,
selector textarea {
    border: 2px solid #1a1a1a;
    border-radius: 0;
    padding: 8px 12px;
}
selector input:focus,
selector select:focus {
    border-color: #1a1a1a;
    box-shadow: 3px 3px 0 #1a1a1a;
}"><?php _e( 'Brutalist', 'woocommerce-product-addon' ); ?></button>
											<button type="button" class="btn btn-sm btn-secondary ppom-css-preset" <?php echo $is_style_locked ? 'disabled' : ''; ?> data-css="selector {
    padding: 0;
    border: none;
}
selector .form-group,
selector .ppom-input-wrapper {
    margin-bottom: 0;
    padding: 14px 0;
    border-bottom: 1px solid #eee;
}
selector label {
    font-size: 13px;
    color: #555;
}
selector input,
selector select,
selector textarea {
    border: none;
    border-bottom: 1px solid #ccc;
    border-radius: 0;
    padding: 6px 0;
    background: transparent;
}
selector input:focus,
selector select:focus {
    border-bottom-color: #000;
    outline: none;
    box-shadow: none;
}"><?php _e( 'Minimal Underline', 'woocommerce-product-addon' ); ?></button>
										</div>
									</div>
								</div>
							</div>
							<div class="col-md-6 col-sm-12">
								<div class="form-group">
									<label><?php _e( 'Custom Javascript', 'woocommerce-product-addon' ); ?>
										<span class="ppom-helper-icon" data-ppom-tooltip="ppom_tooltip"
											  title="<?php _e( 'Add custom Javascript that runs on the product page. Use jQuery to interact with PPOM fields.', 'woocommerce-product-addon' ); ?>"><i
													class="dashicons dashicons-editor-help"></i></span>
									</label>
									<textarea id="ppom-js-editor" class="form-control"
											  name="productmeta_js"><?php echo wp_unslash( $productmeta_js ); ?></textarea>
									<br>
									<p><?php esc_html_e( 'Runs on the product page when fields are loaded. Access fields via jQuery.', 'woocommerce-product-addon' ); ?></p>

									<?php if ( ! $is_style_locked ) : ?>
									<?php $has_ai_key_js = PPOM_AI_Service::is_available(); ?>
									<div class="ppom-style-ai-row" style="margin-top:10px;">
										<label for="ppom-js-ai-prompt" style="font-weight:500; font-size:12px;"><?php _e( 'AI Helper', 'woocommerce-product-addon' ); ?></label>
										<?php if ( $has_ai_key_js ) : ?>
										<div style="display:flex; gap:6px; margin-top:4px;">
											<input type="text" id="ppom-js-ai-prompt" class="form-control" style="flex:1;" placeholder="<?php echo esc_attr__( 'e.g., Add tooltips when hovering over option prices', 'woocommerce-product-addon' ); ?>">
											<button type="button" class="btn btn-sm btn-secondary" id="ppom-js-ai-btn"><?php _e( 'Generate', 'woocommerce-product-addon' ); ?></button>
										</div>
										<span id="ppom-js-ai-status" style="font-size:11px; color:#646970;"></span>
										<?php else : ?>
										<p style="font-size:12px; color:#646970; margin:4px 0 0;"><?php _e( 'Requires API key.', 'woocommerce-product-addon' ); ?> <a href="<?php echo esc_url( admin_url( 'admin.php?page=ppom&view=settings' ) ); ?>"><?php _e( 'Configure', 'woocommerce-product-addon' ); ?></a></p>
										<?php endif; ?>
									</div>
									<?php endif; ?>

									<div class="ppom-style-presets <?php echo $is_style_locked ? 'ppom-presets-locked' : ''; ?>" style="margin-top:12px;">
										<label style="font-weight:500; font-size:12px; margin-bottom:6px; display:block;">
											<?php _e( 'JS Snippets', 'woocommerce-product-addon' ); ?>
											<?php if ( $is_style_locked ) : ?>
												<span style="font-size:10px; background:#2271b1; color:#fff; padding:1px 6px; border-radius:3px; font-weight:600; margin-left:4px;">PRO</span>
											<?php endif; ?>
										</label>
										<div class="ppom-style-preset-buttons" style="display:flex; flex-wrap:wrap; gap:4px;">
											<button type="button" class="btn btn-sm btn-secondary ppom-js-preset" <?php echo $is_style_locked ? 'disabled' : ''; ?> data-snippet="charCounter"><?php _e( 'Character Counter', 'woocommerce-product-addon' ); ?></button>
											<button type="button" class="btn btn-sm btn-secondary ppom-js-preset" <?php echo $is_style_locked ? 'disabled' : ''; ?> data-snippet="scrollToErrors"><?php _e( 'Focus to Errors', 'woocommerce-product-addon' ); ?></button>
											<button type="button" class="btn btn-sm btn-secondary ppom-js-preset" <?php echo $is_style_locked ? 'disabled' : ''; ?> data-snippet="priceHighlight"><?php _e( 'Price Highlight', 'woocommerce-product-addon' ); ?></button>
											<button type="button" class="btn btn-sm btn-secondary ppom-js-preset" <?php echo $is_style_locked ? 'disabled' : ''; ?> data-snippet="collapsible"><?php _e( 'Collapsible Sections', 'woocommerce-product-addon' ); ?></button>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>

				</div>
				<?php if ( $is_style_locked ) : ?>
			</div><!-- /.ppom-style-locked-wrapper -->
			<?php endif; ?>
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
				<h2 class="ppom-heading-style"><?php _e( 'Add PPOM Fields', 'woocommerce-product-addon' ); ?></h2>
				<table class="table ppom_field_table  table-striped">
					<thead>
					<tr>
						<th colspan="6">
							<button type="button" class="btn btn-primary"
									data-modal-id="ppom_fields_model_id"><?php _e( 'Add field', 'woocommerce-product-addon' ); ?></button>
							<button type="button"
									class="btn btn-danger ppom_remove_field"><?php _e( 'Remove', 'woocommerce-product-addon' ); ?></button>
							<?php
						$ai_btn_no_pro = ! ppom_pro_is_valid_license();
						$ai_btn_no_key = ppom_pro_is_valid_license() && ! PPOM_AI_Service::is_available();
						?>
							<button type="button" class="btn btn-secondary ppom-formula-builder-trigger" style="margin-left:4px;">
								<?php _e( 'AI Helper', 'woocommerce-product-addon' ); ?>
								<?php if ( $ai_btn_no_pro ) : ?>
									<span style="font-size:10px; background:#2271b1; color:#fff; padding:1px 6px; border-radius:3px; font-weight:600; margin-left:2px;">PRO</span>
								<?php endif; ?>
							</button>
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
						<th><?php _e( 'Status', 'woocommerce-product-addon' ); ?></th>
						<th><?php _e( 'Data Name', 'woocommerce-product-addon' ); ?></th>
						<th><?php _e( 'Type', 'woocommerce-product-addon' ); ?></th>
						<th><?php _e( 'Title', 'woocommerce-product-addon' ); ?></th>
						<th><?php _e( 'Placeholder', 'woocommerce-product-addon' ); ?></th>
						<th><?php _e( 'Required', 'woocommerce-product-addon' ); ?></th>
						<th><?php _e( 'Actions', 'woocommerce-product-addon' ); ?></th>
					</tr>
					</thead>

					<tfoot>
					<?php if ( $product_meta && is_array( $product_meta) && 8 < count( $product_meta ) ) { ?>
					<tr class="ppom-thead-bg">
						<th></th>
						<th class="ppom-check-all-field ppom-checkboxe-style">
							<label>
								<input type="checkbox">
								<span></span>
							</label>
						</th>
						<th><?php _e( 'Status', 'woocommerce-product-addon' ); ?></th>
						<th><?php _e( 'Data Name', 'woocommerce-product-addon' ); ?></th>
						<th><?php _e( 'Type', 'woocommerce-product-addon' ); ?></th>
						<th><?php _e( 'Title', 'woocommerce-product-addon' ); ?></th>
						<th><?php _e( 'Placeholder', 'woocommerce-product-addon' ); ?></th>
						<th><?php _e( 'Required', 'woocommerce-product-addon' ); ?></th>
						<th><?php _e( 'Actions', 'woocommerce-product-addon' ); ?></th>
					</tr>
					<?php } ?>
					<tr>
						<th colspan="12">
							<div class="ppom-submit-btn text-right">
								<span class="ppom-meta-save-notice"></span>
								<input type="submit" class="btn btn-primary"
									   value="<?php _e( 'Save Fields', 'woocommerce-product-addon' ); ?>">
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
								<td class="ppom_meta_field_id"><?php echo $the_field_id; ?></td>
								<td class="ppom_meta_field_type"><?php echo $field_type; ?></td>
								<td class="ppom_meta_field_title"><?php echo $the_title; ?></td>
								<td class="ppom_meta_field_plchlder"><?php echo $the_placeholder; ?></td>
								<td class="ppom_meta_field_req"><?php echo $_ok; ?></td>
								<td>
									<button class="btn  ppom_copy_field <?php echo ! ppom_pro_is_valid_license() && ! isset( PPOM()->inputs[ $field_type ] ) ? 'ppom-is-pro-field' : ''; ?>"
											data-field-type="<?php echo esc_attr( $field_type ); ?>"
											title="<?php _e( 'Copy Field', 'woocommerce-product-addon' ); ?>"
											id="<?php echo esc_attr( $f_index ); ?>"><span
												class="dashicons dashicons-admin-page"></span></span></i></button>
									<button class="btn ppom-edit-field <?php echo ! ppom_pro_is_valid_license() && ! isset( PPOM()->inputs[ $field_type ] ) ? 'ppom-is-pro-field' : ''; ?>"
											data-modal-id="ppom_field_model_<?php echo esc_attr( $f_index ); ?>"
											id="<?php echo esc_attr( $f_index ); ?>"
											title="<?php _e( 'Edit Field', 'woocommerce-product-addon' ); ?>"><span
												class="dashicons dashicons-edit"></span></button>
								</td>
							</tr>
							<?php
							$ppom_field_index = $f_index;
							$ppom_field_index ++;
							$f_index ++;
						}
					} else {
					?>
						<tr class="ppom-empty-state-row">
							<td colspan="9">
								<div class="ppom-empty-state">
									<div class="ppom-empty-state-icon">
										<span class="dashicons dashicons-forms"></span>
									</div>
									<h3><?php _e( 'No fields added yet', 'woocommerce-product-addon' ); ?></h3>
									<p><?php _e( 'Start building your product form by adding your first field. Choose from text inputs, dropdowns, checkboxes, and more.', 'woocommerce-product-addon' ); ?></p>
									<button type="button" class="btn btn-primary ppom-empty-state-btn"
											data-modal-id="ppom_fields_model_id">
										<span class="dashicons dashicons-plus-alt2"></span>
										<?php _e( 'Add Your First Field', 'woocommerce-product-addon' ); ?>
									</button>
								</div>
							</td>
						</tr>
					<?php
					}
					?>
					</tbody>
				</table>
			</div>

			<?php if ( $is_edit_screen && ! $is_new_group ) : ?>
			<!-- Live Preview Section -->
			<div class="ppom-live-preview-section">
				<div class="ppom-live-preview-header">
					<h2 class="ppom-heading-style">
						<?php _e( 'Live Preview', 'woocommerce-product-addon' ); ?>
						<span class="ppom-preview-badge"><?php _e( 'Beta', 'woocommerce-product-addon' ); ?></span>
					</h2>
					<div class="ppom-preview-controls">
						<label for="ppom-preview-product"><?php _e( 'Preview on product:', 'woocommerce-product-addon' ); ?></label>
						<select id="ppom-preview-product" style="min-width:200px;"></select>
						<button type="button" class="btn btn-sm btn-secondary" id="ppom-preview-refresh" disabled>
							<?php _e( 'Refresh', 'woocommerce-product-addon' ); ?>
						</button>
					</div>
				</div>
				<div id="ppom-preview-no-products" class="ppom-preview-notice" style="display:none;">
					<span class="dashicons dashicons-info"></span>
					<?php _e( 'No products assigned yet. Assign this field group to at least one product using the "Products" dropdown above, save your fields, then refresh this page to preview.', 'woocommerce-product-addon' ); ?>
					<button type="button" class="btn btn-sm btn-secondary ppom-preview-scroll-to-assign"><?php _e( 'Go to assignment', 'woocommerce-product-addon' ); ?></button>
				</div>
				<div class="ppom-live-preview-container" id="ppom-live-preview-container" style="display:none;">
					<div class="ppom-preview-loading" style="display:none;">
						<span class="spinner is-active" style="float:none;"></span>
						<?php _e( 'Loading preview...', 'woocommerce-product-addon' ); ?>
					</div>
					<iframe id="ppom-preview-iframe" style="width:100%; border:1px solid #c3c4c7; border-radius:4px; min-height:500px;" src="about:blank"></iframe>
				</div>
				<p class="ppom-preview-hint" id="ppom-preview-hint">
					<span class="dashicons dashicons-info"></span>
					<?php _e( 'Select a product to preview. The preview shows the actual product page. Save your fields first for changes to appear.', 'woocommerce-product-addon' ); ?>
				</p>
			</div>
			<?php endif; ?>

		</form>
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

<?php

ppom_load_template( 'admin/product-modal.php' );

// Inline product/category assignment JS
if ( $is_edit_screen && ! $is_new_group && $product_meta_id ) {
?>
<script type="text/javascript">
jQuery(function($) {
	var ppomId = <?php echo intval( $product_meta_id ); ?>;
	var $products = $('#ppom-inline-products');
	var $categories = $('#ppom-inline-categories');
	var $productsInitial = $('#ppom-inline-products-initial');
	var $categoriesInitial = $('#ppom-inline-categories-initial');

	var attachedNonce = '';

	// Load currently assigned data via existing endpoint (only for initial values + categories)
	$.get(ajaxurl + '?action=ppom_get_products&ppom_id=' + ppomId, function(html) {
		var $parsed = $('<div>').html(html);
		attachedNonce = $parsed.find('#ppom_attached_nonce').val();

		// Pre-populate ONLY currently assigned products (not all products)
		var initialProductIds = [];
		$parsed.find('#attach-to-products select option:selected').each(function() {
			var val = $(this).val();
			var label = $(this).text();
			if (val && val !== '-1') {
				$products.append(new Option(label, val, true, true));
				initialProductIds.push(val);
			}
		});
		$productsInitial.val(initialProductIds.join(','));

		// Categories (usually small number, load all is fine)
		$parsed.find('#attach-to-categories select option').each(function() {
			var val = $(this).val();
			var label = $(this).text();
			var selected = $(this).is(':selected');
			if (val) {
				$categories.append(new Option(label, val, selected, selected));
			}
		});
		$categoriesInitial.val(
			$parsed.find('#attach-to-categories input[type="hidden"]').val() || ''
		);

		// Initialize Products Select2 with AJAX search
		$products.select2({
			placeholder: '<?php echo esc_js( __( 'Search and select products...', 'woocommerce-product-addon' ) ); ?>',
			allowClear: true,
			width: '100%',
			ajax: {
				url: ajaxurl,
				dataType: 'json',
				delay: 300,
				data: function(params) {
					return { action: 'ppom_search_products', q: params.term || '', page: params.page || 1 };
				},
				processResults: function(data, params) {
					params.page = params.page || 1;
					return { results: data.results, pagination: data.pagination };
				},
				cache: true
			},
			minimumInputLength: 0
		});

		// Categories — standard Select2 (few items)
		$categories.select2({ placeholder: '<?php echo esc_js( __( 'Search and select categories...', 'woocommerce-product-addon' ) ); ?>', allowClear: true, width: '100%' });

		// Auto-save on change
		var saveTimeout;
		$products.add($categories).on('change', function() {
			clearTimeout(saveTimeout);
			$('#ppom-products-status, #ppom-categories-status').text('');
			saveTimeout = setTimeout(function() {
				var formData = new FormData();
				formData.append('action', 'ppom_attach_ppoms');
				formData.append('ppom_id', ppomId);
				formData.append('ppom_attached_nonce', attachedNonce);

				// Products
				var selectedProducts = $products.val() || [];
				selectedProducts.forEach(function(v) {
					formData.append('ppom-attach-to-products[]', v);
				});
				formData.append('ppom-attach-to-products-initial', $productsInitial.val());

				// Categories
				var selectedCategories = $categories.val() || [];
				selectedCategories.forEach(function(v) {
					formData.append('ppom-attach-to-categories[]', v);
				});

				$('#ppom-products-status').text('<?php echo esc_js( __( 'Saving...', 'woocommerce-product-addon' ) ); ?>');

				fetch(ajaxurl, { method: 'POST', body: formData })
					.then(function(r) { return r.json(); })
					.then(function(resp) {
						$('#ppom-products-status').text(resp.status === 'success' ? '<?php echo esc_js( __( 'Saved', 'woocommerce-product-addon' ) ); ?>' : '<?php echo esc_js( __( 'Error saving', 'woocommerce-product-addon' ) ); ?>');
						// Update initial values so subsequent saves compute correct diff
						$productsInitial.val((selectedProducts || []).join(','));
						setTimeout(function() { $('#ppom-products-status').fadeOut(300, function(){ $(this).text('').show(); }); }, 2000);
					})
					.catch(function() {
						$('#ppom-products-status').text('<?php echo esc_js( __( 'Error saving', 'woocommerce-product-addon' ) ); ?>');
					});
			}, 600);
		});
	});
});
</script>
<?php
}

// Style Tab — Presets and AI Assistant JS
if ( $is_edit_screen ) {
	$style_nonce = wp_create_nonce( 'ppom_ai_nonce' );
?>
<script>
jQuery(function($) {
	// Get CodeMirror instance for CSS editor
	function getCSSEditor() {
		// wp.codeEditor.initialize creates a .CodeMirror div right after the textarea
		var cmElements = document.querySelectorAll('.CodeMirror');
		for (var i = 0; i < cmElements.length; i++) {
			var cm = cmElements[i].CodeMirror;
			if (cm) {
				// Check if this CodeMirror is for the CSS editor by looking at the textarea it wraps
				var textarea = cm.getTextArea();
				if (textarea && textarea.id === 'ppom-css-editor') {
					return cm;
				}
			}
		}
		return null;
	}

	function setCSS(css) {
		var cm = getCSSEditor();
		if (cm) {
			cm.setValue(css);
			cm.save(); // sync back to textarea immediately
		} else {
			$('#ppom-css-editor').val(css);
		}
	}

	function appendCSS(css) {
		var cm = getCSSEditor();
		var current = cm ? cm.getValue().trim() : $('#ppom-css-editor').val().trim();
		setCSS(current ? current + '\n\n' + css : css);
	}

	// CSS Preset buttons — replace the CSS entirely
	$(document).on('click', '.ppom-css-preset:not([disabled])', function(e) {
		e.preventDefault();
		$('.ppom-css-preset').removeClass('active');
		$(this).addClass('active');
		var css = $(this).data('css');
		if (css) {
			setCSS(css);
		}
	});

	// JS Preset snippets — avoid HTML tags, use document.createElement instead
	var jsSnippets = {};
	jsSnippets.charCounter = [
		"/* Live character counter for text fields */",
		"jQuery('.ppom-field-wrapper input[maxlength]').each(function() {",
		"    var input = this;",
		"    var $input = jQuery(input);",
		"    var max = $input.attr('maxlength');",
		"    if (max) {",
		"        var counter = document.createElement('span');",
		"        counter.style.cssText = 'font-size:11px;color:#888;display:block;margin-top:2px';",
		"        input.parentNode.insertBefore(counter, input.nextSibling);",
		"        function updateCount() {",
		"            counter.textContent = input.value.length + ' / ' + max;",
		"        }",
		"        $input.on('input', updateCount);",
		"        updateCount();",
		"    }",
		"});"
	].join("\n");
	jsSnippets.scrollToErrors = [
		"/* Focus first empty required field on validation error */",
		"jQuery(function() {",
		"    if (!document.querySelector('.wc-block-components-notice-banner.is-error, .woocommerce-error')) return;",
		"    setTimeout(function() {",
		"        jQuery('.ppom-required').each(function() {",
		"            if (!this.value) {",
		"                this.focus();",
		"                return false;",
		"            }",
		"        });",
		"    }, 1000);",
		"});"
	].join("\n");
	jsSnippets.priceHighlight = [
		"/* Animated price update highlight */",
		"var ppomPriceEl = document.querySelector('#ppom-price-container, [class*=ppom-price-container]');",
		"if (ppomPriceEl) {",
		"    var ppomObserver = new MutationObserver(function() {",
		"        ppomPriceEl.style.transition = 'background 0.3s';",
		"        ppomPriceEl.style.background = '#fff3cd';",
		"        setTimeout(function() { ppomPriceEl.style.background = ''; }, 600);",
		"    });",
		"    ppomObserver.observe(ppomPriceEl, {childList: true, subtree: true, characterData: true});",
		"}"
	].join("\n");
	jsSnippets.collapsible = [
		"/* Collapsible full-width field sections */",
		"jQuery('.ppom-field-wrapper.col-md-12').each(function() {",
		"    var $wrapper = jQuery(this);",
		"    var $label = $wrapper.find('label.form-control-label').first();",
		"    if (!$label.length) return;",
		"    var arrow = document.createElement('span');",
		"    arrow.className = 'dashicons dashicons-arrow-down-alt2';",
		"    arrow.style.cssText = 'font-size:14px;vertical-align:middle;margin-left:4px;cursor:pointer';",
		"    $label[0].appendChild(arrow);",
		"    $label.css({cursor:'pointer', userSelect:'none'});",
		"    /* Find all content inside .form-group except the label itself */",
		"    var $formGroup = $wrapper.find('.form-group');",
		"    var $content = $formGroup.children().not($label);",
		"    $label.on('click', function(e) {",
		"        if (jQuery(e.target).is('input,select,textarea')) return;",
		"        $content.slideToggle(200);",
		"        arrow.classList.toggle('dashicons-arrow-down-alt2');",
		"        arrow.classList.toggle('dashicons-arrow-up-alt2');",
		"    });",
		"});"
	].join("\n");

	// JS Editor helper
	function getJSEditor() {
		var cmElements = document.querySelectorAll('.CodeMirror');
		for (var i = 0; i < cmElements.length; i++) {
			var cm = cmElements[i].CodeMirror;
			if (cm) {
				var textarea = cm.getTextArea();
				if (textarea && textarea.id === 'ppom-js-editor') return cm;
			}
		}
		return null;
	}

	function appendJS(js) {
		var cm = getJSEditor();
		if (cm) {
			var current = cm.getValue().trim();
			cm.setValue(current ? current + '\n\n' + js : js);
			cm.save();
		} else {
			var $ta = $('#ppom-js-editor');
			var current = $ta.val().trim();
			$ta.val(current ? current + '\n\n' + js : js);
		}
	}

	// JS Preset buttons — append to JS editor
	$(document).on('click', '.ppom-js-preset:not([disabled])', function(e) {
		e.preventDefault();
		var key = $(this).data('snippet');
		if (key && jsSnippets[key]) {
			appendJS(jsSnippets[key]);
		}
	});

	<?php if ( ! $is_style_locked && PPOM_AI_Service::is_available() ) : ?>
	// AI CSS Assistant
	function aiGenerate(action, promptSelector, statusSelector, callback) {
		var prompt = $(promptSelector).val().trim();
		if (!prompt) return;
		var $btn = $(statusSelector).prev().find('button').last();
		$btn.prop('disabled', true);
		$(statusSelector).text('<?php echo esc_js( __( 'Generating...', 'woocommerce-product-addon' ) ); ?>');

		$.post(ajaxurl, {
			action: action,
			nonce: '<?php echo esc_js( $style_nonce ); ?>',
			prompt: prompt
		}, function(response) {
			$btn.prop('disabled', false);
			if (response.success) {
				callback(response.data);
				$(statusSelector).text('<?php echo esc_js( __( 'Added! Review and save.', 'woocommerce-product-addon' ) ); ?>');
				setTimeout(function() { $(statusSelector).text(''); }, 3000);
			} else {
				$(statusSelector).text(response.data || '<?php echo esc_js( __( 'Failed to generate.', 'woocommerce-product-addon' ) ); ?>');
			}
		}).fail(function() {
			$btn.prop('disabled', false);
			$(statusSelector).text('<?php echo esc_js( __( 'Request failed.', 'woocommerce-product-addon' ) ); ?>');
		});
	}

	$(document).on('click', '#ppom-style-ai-btn', function(e) {
		e.preventDefault();
		aiGenerate('ppom_ai_generate_css', '#ppom-style-ai-prompt', '#ppom-style-ai-status', function(data) {
			if (data.css) appendCSS(data.css);
		});
	});

	$(document).on('click', '#ppom-js-ai-btn', function(e) {
		e.preventDefault();
		aiGenerate('ppom_ai_generate_js', '#ppom-js-ai-prompt', '#ppom-js-ai-status', function(data) {
			if (data.js) appendJS(data.js);
		});
	});

	// Allow Enter in AI prompt inputs
	$('#ppom-style-ai-prompt, #ppom-js-ai-prompt').on('keypress', function(e) {
		if (e.which === 13) {
			e.preventDefault();
			$(this).closest('.ppom-style-ai-row').find('button').click();
		}
	});
	<?php endif; ?>
});
</script>
<?php
}

// Live Preview JS
if ( $is_edit_screen && ! $is_new_group && $product_meta_id ) {
?>
<script>
jQuery(function($) {
	var $previewSelect = $('#ppom-preview-product');
	var $iframe = $('#ppom-preview-iframe');
	var $container = $('#ppom-live-preview-container');
	var $hint = $('#ppom-preview-hint');
	var $noProducts = $('#ppom-preview-no-products');
	var $loading = $('.ppom-preview-loading');
	var $refreshBtn = $('#ppom-preview-refresh');
	var hasAssignedProducts = false;

	var ppomId = <?php echo intval( $product_meta_id ); ?>;

	// Load only assigned products initially, then use AJAX search for the rest
	$.get(ajaxurl + '?action=ppom_get_products&ppom_id=' + ppomId, function(html) {
		var $parsed = $('<div>').html(html);
		var assignedFirst = null;
		$parsed.find('#attach-to-products select option:selected').each(function() {
			var val = $(this).val();
			var label = $(this).text();
			if (val && val !== '-1') {
				$previewSelect.append(new Option(label, val, false, false));
				if (!assignedFirst) {
					assignedFirst = val;
					hasAssignedProducts = true;
				}
			}
		});
		$previewSelect.select2({
			placeholder: '<?php echo esc_js( __( 'Select a product...', 'woocommerce-product-addon' ) ); ?>',
			allowClear: true,
			width: '250px',
			ajax: {
				url: ajaxurl,
				dataType: 'json',
				delay: 300,
				data: function(params) {
					return { action: 'ppom_search_products', q: params.term || '', page: params.page || 1 };
				},
				processResults: function(data, params) {
					params.page = params.page || 1;
					return { results: data.results, pagination: data.pagination };
				},
				cache: true
			},
			minimumInputLength: 0
		});

		if (!hasAssignedProducts) {
			$hint.hide();
			$noProducts.show();
		} else {
			// Auto-select the first assigned product
			$previewSelect.val(assignedFirst).trigger('change');
		}
	});

	// Scroll to assignment section
	$(document).on('click', '.ppom-preview-scroll-to-assign', function() {
		var $section = $('.ppom-inline-attach-section');
		if ($section.length) {
			$('html, body').animate({ scrollTop: $section.offset().top - 50 }, 400);
			$section.css('background', '#fff8e5');
			setTimeout(function() { $section.css('background', ''); }, 2000);
		}
	});

	function loadPreview(productId) {
		if (!productId) {
			$container.hide();
			$hint.show();
			$noProducts.hide();
			$refreshBtn.prop('disabled', true);
			return;
		}

		$container.show();
		$hint.hide();
		$noProducts.hide();
		$loading.show();
		$refreshBtn.prop('disabled', false);

		$.get(ajaxurl, { action: 'ppom_get_preview_url', product_id: productId }, function(response) {
			var url = response.data || '<?php echo esc_js( home_url( '/?p=' ) ); ?>' + productId;
			$iframe.attr('src', url);
		}).fail(function() {
			$iframe.attr('src', '<?php echo esc_js( home_url( '/?p=' ) ); ?>' + productId);
		});

		$iframe.off('load').on('load', function() {
			$loading.hide();
			try {
				var iframeDoc = this.contentDocument || this.contentWindow.document;
				var ppomWrap = iframeDoc.querySelector('.ppom-wrapper, .ppom-field-wrapper, form.cart');
				if (ppomWrap) {
					ppomWrap.scrollIntoView({ behavior: 'smooth', block: 'center' });
				}
			} catch(e) { /* cross-origin, ignore */ }
		});
	}

	$previewSelect.on('change', function() { loadPreview($(this).val()); });
	$refreshBtn.on('click', function() { loadPreview($previewSelect.val()); });
});
</script>
<?php
}

// AI Helper Modal — always render, show upgrade/configure notice inside if needed
{
	$formula_nonce = wp_create_nonce( 'ppom_ai_nonce' );
?>
<!-- AI Helper Modal -->
<div id="ppom-formula-builder-modal" class="ppom-modal-box ppom-wizard-modal" style="display:none; max-width:700px;">
	<header>
		<h3><?php _e( 'AI Helper', 'woocommerce-product-addon' ); ?></h3>
	</header>
	<div class="ppom-modal-body" style="padding:20px;">
		<?php if ( ! ppom_pro_is_valid_license() ) : ?>
		<div style="background:#f0f6fc; border-left:4px solid #2271b1; padding:16px; margin-bottom:14px; border-radius:0 4px 4px 0; text-align:center;">
			<span class="dashicons dashicons-lock" style="font-size:24px; width:24px; height:24px; color:#2271b1; margin-bottom:6px;"></span><br>
			<strong style="font-size:14px;"><?php _e( 'Pro Feature', 'woocommerce-product-addon' ); ?></strong><br>
			<span style="font-size:13px; color:#646970;"><?php _e( 'Describe what you need and AI will generate the fields for you.', 'woocommerce-product-addon' ); ?></span><br><br>
			<a href="<?php echo esc_url( tsdk_utmify( tsdk_translate_link( PPOM_UPGRADE_URL ), 'ai-helper', 'ppompage' ) ); ?>" target="_blank" class="btn btn-primary btn-sm"><?php _e( 'Upgrade to Pro', 'woocommerce-product-addon' ); ?></a>
		</div>
		<?php elseif ( ! PPOM_AI_Service::is_available() ) : ?>
		<div style="background:#fff8e5; border-left:4px solid #dba617; padding:12px 16px; margin-bottom:14px; border-radius:0 4px 4px 0;">
			<strong><?php _e( 'API key required', 'woocommerce-product-addon' ); ?></strong><br>
			<span style="font-size:13px;"><?php _e( 'To use AI features, add your OpenAI or Anthropic API key in', 'woocommerce-product-addon' ); ?> <a href="<?php echo esc_url( admin_url( 'admin.php?page=ppom&view=settings' ) ); ?>"><?php _e( 'PPOM Settings', 'woocommerce-product-addon' ); ?></a>.</span>
		</div>
		<?php endif; ?>
		<?php if ( $product_meta && is_array( $product_meta ) && count( $product_meta ) > 0 ) : ?>
		<div style="background:#f6f7f7; padding:10px 14px; border-radius:4px; margin-bottom:14px; font-size:13px;">
			<strong><?php _e( 'Existing fields:', 'woocommerce-product-addon' ); ?></strong>
			<?php
			$existing_labels = array();
			foreach ( $product_meta as $fm ) {
				$existing_labels[] = esc_html( ( $fm['title'] ?? '' ) . ' (' . ( $fm['type'] ?? '' ) . ')' );
			}
			echo implode( ', ', $existing_labels );
			?>
			<div style="color:#646970; margin-top:4px;"><?php _e( 'New fields will be added alongside these. Describe what you want to add or change.', 'woocommerce-product-addon' ); ?></div>
		</div>
		<?php endif; ?>

		<!-- Input step -->
		<div id="ppom-formula-input">
			<label for="ppom-formula-description" style="font-weight:500; display:block; margin-bottom:6px;">
				<?php _e( 'What fields do you want to add or what pricing logic do you need?', 'woocommerce-product-addon' ); ?>
			</label>
			<textarea id="ppom-formula-description" rows="4" style="width:100%; padding:10px; border:1px solid #8c8f94; border-radius:4px; font-size:13px;<?php echo ! ppom_pro_is_valid_license() ? ' opacity:0.5;' : ''; ?>" placeholder="<?php echo esc_attr__( "Example: Add a gift wrapping option for 4.99, and a custom message text field with max 100 characters.", 'woocommerce-product-addon' ); ?>" <?php echo ! ppom_pro_is_valid_license() ? 'disabled' : ''; ?>></textarea>
			<div style="margin-top:10px; display:flex; gap:8px; align-items:center;">
				<button type="button" class="btn btn-primary" id="ppom-formula-generate-btn" <?php echo ( ! ppom_pro_is_valid_license() || ! PPOM_AI_Service::is_available() ) ? 'disabled' : ''; ?>>
					<?php _e( 'Generate Fields', 'woocommerce-product-addon' ); ?>
				</button>
				<span class="spinner" id="ppom-formula-spinner" style="float:none;"></span>
			</div>
			<div id="ppom-formula-error" style="display:none; margin-top:10px; color:#d63638; padding:10px; background:#fcf0f1; border-radius:4px;"></div>
		</div>

		<!-- Result step -->
		<div id="ppom-formula-result" style="display:none;">
			<div id="ppom-formula-explanation" style="background:#f0f6fc; border-left:4px solid #2271b1; padding:12px 16px; margin-bottom:16px; border-radius:0 4px 4px 0; font-size:13px;"></div>

			<div id="ppom-formula-example" style="background:#f6f7f7; padding:12px 16px; margin-bottom:16px; border-radius:4px; display:none;">
				<strong><?php _e( 'Example Calculation:', 'woocommerce-product-addon' ); ?></strong>
				<div id="ppom-formula-example-content" style="margin-top:8px; font-size:13px;"></div>
			</div>

			<div id="ppom-formula-removals" style="display:none; background:#fcf0f1; border-left:4px solid #d63638; padding:10px 14px; margin-bottom:12px; border-radius:0 4px 4px 0; font-size:13px;">
				<strong><?php _e( 'Fields to remove:', 'woocommerce-product-addon' ); ?></strong> <span id="ppom-formula-removals-list"></span>
			</div>
			<h5 id="ppom-formula-additions-heading" style="display:none;"><?php _e( 'Fields to add:', 'woocommerce-product-addon' ); ?></h5>
			<table class="table table-striped" style="font-size:13px;">
				<thead>
					<tr><th><?php _e( 'Type', 'woocommerce-product-addon' ); ?></th><th><?php _e( 'Title', 'woocommerce-product-addon' ); ?></th><th><?php _e( 'Data Name', 'woocommerce-product-addon' ); ?></th><th><?php _e( 'Price', 'woocommerce-product-addon' ); ?></th></tr>
				</thead>
				<tbody id="ppom-formula-fields-tbody"></tbody>
			</table>
		</div>
	</div>
	<footer>
		<button type="button" class="btn btn-default ppom-js-modal-close"><?php _e( 'Cancel', 'woocommerce-product-addon' ); ?></button>
		<button type="button" class="btn btn-default" id="ppom-formula-back-btn" style="display:none;"><?php _e( 'Back', 'woocommerce-product-addon' ); ?></button>
		<button type="button" class="btn btn-primary" id="ppom-formula-add-btn" style="display:none;"><?php _e( 'Add These Fields', 'woocommerce-product-addon' ); ?></button>
	</footer>
</div>

<script>
jQuery(function($) {
	var formulaNonce = '<?php echo esc_js( $formula_nonce ); ?>';
	var formulaFields = null;
	var formulaRemoveFields = null;
	var currentGroupId = <?php echo intval( $product_meta_id ); ?>;

	// Collect existing fields context for the AI
	var existingFieldsContext = <?php
		$context = array();
		if ( $product_meta && is_array( $product_meta ) ) {
			foreach ( $product_meta as $fm ) {
				$context[] = array(
					'type'      => $fm['type'] ?? '',
					'title'     => $fm['title'] ?? '',
					'data_name' => $fm['data_name'] ?? '',
				);
			}
		}
		echo wp_json_encode( $context );
	?>;

	// Open formula builder
	$(document).on('click', '.ppom-formula-builder-trigger', function(e) {
		e.preventDefault();
		formulaFields = null;
		$('#ppom-formula-description').val('');
		$('#ppom-formula-input').show();
		$('#ppom-formula-result').hide();
		$('#ppom-formula-add-btn, #ppom-formula-back-btn').hide();
		$('#ppom-formula-error').hide();
		$('body').append('<div class="ppom-modal-overlay ppom-js-modal-close"></div>');
		$('#ppom-formula-builder-modal').fadeIn();
	});

	// Generate
	$(document).on('click', '#ppom-formula-generate-btn', function() {
		var desc = $('#ppom-formula-description').val().trim();
		if (!desc) {
			$('#ppom-formula-error').text('<?php echo esc_js( __( 'Please describe what you want to add.', 'woocommerce-product-addon' ) ); ?>').show();
			return;
		}

		// Prepend existing fields context so AI knows what's already there
		var fullDesc = desc;
		if (existingFieldsContext.length) {
			var existingList = existingFieldsContext.map(function(f) {
				return f.title + ' (' + f.type + ', data_name: ' + f.data_name + ')';
			}).join('; ');
			fullDesc = 'EXISTING FIELDS in this group (do NOT recreate these, only add NEW fields): ' + existingList + '\n\nUser request: ' + desc;
		}

		$('#ppom-formula-error').hide();
		$('#ppom-formula-spinner').addClass('is-active');
		$(this).prop('disabled', true);

		$.post(ajaxurl, {
			action: 'ppom_ai_formula',
			nonce: formulaNonce,
			description: fullDesc
		}, function(response) {
			$('#ppom-formula-spinner').removeClass('is-active');
			$('#ppom-formula-generate-btn').prop('disabled', false);

			if (response.success) {
				formulaFields = response.data.fields || [];
				formulaRemoveFields = response.data.remove_fields || [];

				$('#ppom-formula-explanation').html(
					'<strong><?php echo esc_js( __( 'Plan:', 'woocommerce-product-addon' ) ); ?></strong><br>' +
					(response.data.explanation || '').replace(/\n/g, '<br>')
				);

				if (response.data.example) {
					var exHtml = '';
					if (response.data.example.inputs) {
						var parts = [];
						$.each(response.data.example.inputs, function(k, v) { parts.push(k + ' = ' + v); });
						exHtml += '<strong><?php echo esc_js( __( 'Inputs:', 'woocommerce-product-addon' ) ); ?></strong> ' + parts.join(', ') + '<br>';
					}
					exHtml += '<strong><?php echo esc_js( __( 'Calculation:', 'woocommerce-product-addon' ) ); ?></strong> ' + (response.data.example.calculation || '') + '<br>';
					exHtml += '<strong><?php echo esc_js( __( 'Add-on Total:', 'woocommerce-product-addon' ) ); ?></strong> ' + (response.data.example.total_addon || '');
					$('#ppom-formula-example-content').html(exHtml);
					$('#ppom-formula-example').show();
				} else {
					$('#ppom-formula-example').hide();
				}

				var tbody = '';
				$.each(formulaFields, function(i, f) {
					var price = f.price || f['price-multiplier'] || '';
					if (f.options && f.options.length) {
						var pp = [];
						$.each(f.options, function(j, o) { if(o.price) pp.push(o.option + ': ' + o.price); });
						if (pp.length) price = pp.join(', ');
					}
					tbody += '<tr><td><code>' + (f.type||'') + '</code></td><td>' + (f.title||'') + '</td><td><code>' + (f.data_name||'') + '</code></td><td>' + (price||'—') + '</td></tr>';
				});
				$('#ppom-formula-fields-tbody').html(tbody);

				// Show removals if any
				if (formulaRemoveFields && formulaRemoveFields.length) {
					$('#ppom-formula-removals-list').text(formulaRemoveFields.join(', '));
					$('#ppom-formula-removals').show();
				} else {
					$('#ppom-formula-removals').hide();
				}

				// Show additions heading if there are fields to add
				if (formulaFields && formulaFields.length) {
					$('#ppom-formula-additions-heading').show();
				} else {
					$('#ppom-formula-additions-heading').hide();
				}

				// Update button label
				var btnLabel = '';
				if (formulaFields.length && formulaRemoveFields.length) {
					btnLabel = '<?php echo esc_js( __( 'Apply Changes', 'woocommerce-product-addon' ) ); ?>';
				} else if (formulaRemoveFields.length) {
					btnLabel = '<?php echo esc_js( __( 'Remove Fields', 'woocommerce-product-addon' ) ); ?>';
				} else {
					btnLabel = '<?php echo esc_js( __( 'Add These Fields', 'woocommerce-product-addon' ) ); ?>';
				}
				$('#ppom-formula-add-btn').text(btnLabel);

				$('#ppom-formula-input').hide();
				$('#ppom-formula-result').show();
				$('#ppom-formula-add-btn, #ppom-formula-back-btn').show();
			} else {
				$('#ppom-formula-error').text(response.data || '<?php echo esc_js( __( 'Generation failed. Please try again.', 'woocommerce-product-addon' ) ); ?>').show();
			}
		}).fail(function() {
			$('#ppom-formula-spinner').removeClass('is-active');
			$('#ppom-formula-generate-btn').prop('disabled', false);
			$('#ppom-formula-error').text('<?php echo esc_js( __( 'Request failed.', 'woocommerce-product-addon' ) ); ?>').show();
		});
	});

	// Back
	$(document).on('click', '#ppom-formula-back-btn', function() {
		$('#ppom-formula-input').show();
		$('#ppom-formula-result').hide();
		$('#ppom-formula-add-btn, #ppom-formula-back-btn').hide();
	});

	// Add/remove fields in the CURRENT group
	$(document).on('click', '#ppom-formula-add-btn', function() {
		if ((!formulaFields || !formulaFields.length) && (!formulaRemoveFields || !formulaRemoveFields.length)) return;

		var $btn = $(this);
		$btn.prop('disabled', true).text('<?php echo esc_js( __( 'Applying...', 'woocommerce-product-addon' ) ); ?>');

		var postData = {
			action: 'ppom_add_fields_to_group',
			nonce: formulaNonce,
			group_id: currentGroupId
		};
		if (formulaFields && formulaFields.length) {
			postData.fields = JSON.stringify(formulaFields);
		}
		if (formulaRemoveFields && formulaRemoveFields.length) {
			postData.remove_fields = JSON.stringify(formulaRemoveFields);
		}

		$.post(ajaxurl, postData, function(response) {
			$btn.prop('disabled', false).text('<?php echo esc_js( __( 'Add These Fields', 'woocommerce-product-addon' ) ); ?>');
			if (response.success) {
				// Reload the page to show new fields
				window.location.reload();
			} else {
				alert(response.data || '<?php echo esc_js( __( 'Failed to add fields.', 'woocommerce-product-addon' ) ); ?>');
			}
		});
	});
});
</script>
<?php
}
?>