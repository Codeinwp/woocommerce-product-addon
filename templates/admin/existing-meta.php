<?php
/*
** PPOM Existing Meta Template
**
** Renders the PPOM Field Groups list using \PPOM\Admin\MetaGroupsListTable
** (a `WP_List_Table` subclass). The table instance is prepared in
** `NM_PersonalizedProduct_Admin::load_admin_menu()` so bulk-action redirects
** can fire before any output, and is passed in via `ppom_load_template()`'s
** `$variables` array (extracted as `$list_table`).
*/

/*
**========== Direct access not allowed ===========
*/
if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not Allowed' );
}

/**
 * Field-groups list table prepared in `NM_PersonalizedProduct_Admin::load_admin_menu()`.
 *
 * @var \PPOM\Admin\MetaGroupsListTable|null $list_table
 */
if ( ! isset( $list_table ) ) {
	return;
}

// Single-row AJAX delete (`a.ppom-delete-single-product`) reads this nonce
// from `#ppom_meta_nonce`. Keep it inside the rendered output.
wp_nonce_field( 'ppom_meta_nonce_action', 'ppom_meta_nonce' );
?>

<div class="ppom-existing-meta-wrapper">
	<form method="get">
		<input type="hidden" name="page" value="ppom" />
		<?php $list_table->search_box( __( 'Search', 'woocommerce-product-addon' ), 'ppom-meta-search' ); ?>
	</form>

	<form method="post">
		<?php $list_table->display(); ?>
	</form>
</div>

<?php
ppom_load_template( 'admin/product-modal.php' );
?>

<!-- Upgrade to pro modal -->
<div id="ppom-import-upsell" class="ppom-modal-box ppom-upsell-modal" style="display: none;">
	<div class="ppom-modal-body">
		<button type="button" aria-label="close" class="close-model ppom-js-modal-close"><span class="dashicons dashicons-no-alt"></span></button>
		<div class="ppom-lock-icon">
			<span class="dashicons dashicons-lock"></span>
		</div>
		<h3><?php esc_html_e( 'Importing fields is a PRO feature', 'woocommerce-product-addon' ); ?></h3>
		<p>
			<?php esc_html_e( 'We\'re sorry, importing fields is not available on your plan. Please upgrade to the Pro plan to unlock all these features and enhance your product fields management capabilities.', 'woocommerce-product-addon' ); ?>
		</p>
		<a class="btn btn-success" href="<?php echo esc_url( tsdk_utmify( tsdk_translate_link( PPOM_UPGRADE_URL ), 'lockedimport' ) ); ?>" target="_blank">
			<?php esc_html_e( 'Upgrade to PRO', 'woocommerce-product-addon' ); ?>
		</a>
	</div>
</div>
