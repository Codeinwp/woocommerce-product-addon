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

<?php
$ppom_upsell_modals = array(
	array(
		'id'    => 'ppom-import-upsell',
		'title' => __( 'Importing fields is a PRO feature', 'woocommerce-product-addon' ),
		'body'  => __( 'We\'re sorry, importing fields is not available on your plan. Please upgrade to the Pro plan to unlock all these features and enhance your product fields management capabilities.', 'woocommerce-product-addon' ),
		'utm'   => 'lockedimport',
	),
	array(
		'id'    => 'ppom-export-upsell',
		'title' => __( 'Exporting fields is a PRO feature', 'woocommerce-product-addon' ),
		'body'  => __( 'We\'re sorry, exporting fields is not available on your plan. Please upgrade to the Pro plan to unlock all these features and enhance your product fields management capabilities.', 'woocommerce-product-addon' ),
		'utm'   => 'lockedexport',
	),
);

if ( ! ppom_pro_is_installed() ) :
	foreach ( $ppom_upsell_modals as $ppom_upsell_modal ) :
		?>
<div id="<?php echo esc_attr( $ppom_upsell_modal['id'] ); ?>" class="ppom-modal-box ppom-upsell-modal" style="display: none;">
	<div class="ppom-modal-body">
		<button type="button" aria-label="close" class="close-model ppom-js-modal-close"><span class="dashicons dashicons-no-alt"></span></button>
		<div class="ppom-lock-icon">
			<span class="dashicons dashicons-lock"></span>
		</div>
		<h3><?php echo esc_html( $ppom_upsell_modal['title'] ); ?></h3>
		<p><?php echo esc_html( $ppom_upsell_modal['body'] ); ?></p>
		<a class="btn btn-success" href="<?php echo esc_url( tsdk_utmify( tsdk_translate_link( PPOM_UPGRADE_URL ), $ppom_upsell_modal['utm'] ) ); ?>" target="_blank">
			<?php esc_html_e( 'Upgrade to PRO', 'woocommerce-product-addon' ); ?>
		</a>
	</div>
</div>
		<?php
	endforeach;
endif;
?>
