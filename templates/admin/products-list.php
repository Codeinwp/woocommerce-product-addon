<?php
/**
 * Product with PPOM IDs
 **/

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not Allowed' );
}

// nonce field
wp_nonce_field( 'ppom_attached_nonce_action', 'ppom_attached_nonce' );

?>
<div class="ppom-attach-container">
	<?php if ( ! ppom_pro_is_installed() || 'valid' !== apply_filters( 'product_ppom_license_status', ''  ) ) : ?>
		<div class="options_group ppom-settings-container">
			<a class="ppom-upsell-link" target="_blank" href="<?php echo esc_url( tsdk_utmify( tsdk_translate_link( PPOM_UPGRADE_URL ), 'product-edit', 'metabox' ) ); ?>">
				<span class="dashicons dashicons-external"></span> 
				<?php esc_html_e( 'Using multiple PPOM field groups on the same product is available in PRO.', 'woocommerce-product-addon' ); ?>
			</a>
		</div>
	<?php endif; ?>
    <?php echo apply_filters('ppom_render_attach_popup' , ''); ?>
</div>
<?php
