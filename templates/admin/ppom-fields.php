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
                                <a class="btn btn-sm btn-secondary ppom-products-modal"
                                   data-ppom_id="<?php echo esc_attr( $product_meta_id ); ?>"
                                   data-formmodal-id="ppom-product-modal"
								>
									<?php _e( 'Attach to Products', 'woocommerce-product-addon' ); ?>
								</a>
								<?php } ?>
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
						<?php if ( ppom_is_legacy_user() ) : ?>
							<div class="row">
								<div class="col-md-12 col-sm-12">
									<div class="notice notice-info">
										<p>
											<?php
											echo sprintf(
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
									<textarea id="ppom-css-editor" class="form-control" name="productmeta_style"><?php echo wp_unslash( $productmeta_style ); ?></textarea>
									<br>
									<p><?php esc_html_e( 'Use', 'woocommerce-product-addon' ); ?> <code>selector</code> <?php esc_html_e( 'to target block wrapper.', 'woocommerce-product-addon' ); ?></p>
									<p><?php esc_html_e( 'Example:', 'woocommerce-product-addon' ); ?></p>
									<pre className="ppom-css-editor-help"><?php echo esc_html( "selector {\n    background: #000;\n}\nselector img {\n    border-radius: 100%;\n}" ); ?></pre>
									<p><?php esc_html_e( 'You can also use other CSS syntax here, such as media queries.', 'woocommerce-product-addon' ); ?></p>
								</div>
							</div>
							<div class="col-md-6 col-sm-12">
								<div class="form-group">
									<label><?php _e( 'Custom Javascipt', 'woocommerce-product-addon' ); ?>
										<span class="ppom-helper-icon" data-ppom-tooltip="ppom_tooltip"
											  title="<?php _e( 'Add your own javascipt script.', 'woocommerce-product-addon' ); ?>"><i
													class="dashicons dashicons-editor-help"></i></span>
									</label>
									<textarea id="ppom-js-editor" class="form-control"
											  name="productmeta_js"><?php echo wp_unslash( $productmeta_js ); ?></textarea>
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
					}
					?>
					</tbody>
				</table>
			</div>
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
?>