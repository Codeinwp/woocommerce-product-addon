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
    <?php echo apply_filters('ppom_render_attach_popup' , ''); ?>
</div>
<?php
