<?php
/**
 * PPOM Main HTML Template
 *
 * Rendering all fields on product page
 *
 * @version 1.0
 **/

/*
**========== Block direct access ===========
*/
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


// check if duplicate ppom fields render
if ( ! $form_obj::$ppom->has_unique_datanames() ) {
	$duplicate_found = apply_filters( 'ppom_duplicate_datanames_text', __( 'Some of your fields has duplicated datanames, please fix it', 'woocommerce-product-addon' ) );

	echo '<div class="error">' . esc_html( $duplicate_found ) . '</div>';

	return '';
}

// ppom meta ids
$ppom_wrapper_id = is_array( $form_obj::$ppom->meta_id ) ? implode( '-', $form_obj::$ppom->meta_id ) : $form_obj::$ppom->meta_id;

if ( isset( $form_obj::$ppom->meta_id ) ) {
	$ppom_groups = is_array( $form_obj::$ppom->meta_id ) ? $form_obj::$ppom->meta_id : array( $form_obj::$ppom->meta_id );
} else {
	$ppom_groups = array();
}

?>

<div id="ppom-box-<?php echo esc_attr( $ppom_wrapper_id ); ?>" class="ppom-wrapper">


	<!-- Display price table before fields -->
	<?php
	if ( ppom_get_price_table_location() === 'before' ) {
		echo $form_obj->render_price_table_html();
	}
	?>

	<!-- Render hidden inputs -->
	<?php $form_obj->form_contents(); ?>
	<?php
	foreach ( $ppom_groups as $meta_id ) :
		$ppom                           = new PPOM_Meta();
		$ppom_settings                  = $ppom->get_settings_by_id( $meta_id );
		$form_obj::$ppom->ppom_settings = $ppom_settings;
		$allowed_variations             = ppom_get_variation_ids_for_group( $form_obj->product_id, $meta_id );
		$wrapper_classes                = $form_obj->wrapper_inner_classes();
		if ( ! empty( $allowed_variations ) ) {
			$wrapper_classes .= ' ppom-variation-rule-group';
		}
		?>
		<div
			class="<?php echo esc_attr( $wrapper_classes ); ?>"
			data-ppom-group-id="<?php echo esc_attr( (string) $meta_id ); ?>"
			<?php if ( ! empty( $allowed_variations ) ) : ?>
				data-ppom-allowed-variations="<?php echo esc_attr( implode( ',', array_map( 'absint', $allowed_variations ) ) ); ?>"
				style="display:none"
				aria-hidden="true"
			<?php endif; ?>
		>

			<?php
			/**
			 * Hook before ppom fields.
			 */
			do_action( 'ppom_before_ppom_fields', $form_obj );
			?>

			<?php $form_obj->ppom_fields_render( $meta_id ); ?>

			<?php
			/**
			 * Hook after ppom fields.
			 */
			do_action( 'ppom_after_ppom_fields', $form_obj );
			?>

		</div>
		<?php endforeach; ?> <!-- end form-row -->

	<!-- Display price table after fields -->
	<?php
	if ( ppom_get_price_table_location() === 'after' ) {
		echo $form_obj->render_price_table_html();
	}
	?>


	<div id="ppom-error-container" class="woocommerce-notices-wrapper"></div>

	<div style="clear:both"></div>

</div>  <!-- end ppom-wrapper -->
