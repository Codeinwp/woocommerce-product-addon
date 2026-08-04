<?php
/**
 * Quantities Input Template
 *
 * This template can be overridden by copying it to yourtheme/ppom/frontend/inputs/quantities.php
 *
 * @version 1.0
 **/

/* 
**========== Block direct access =========== 
*/
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$fm = new PPOM_InputManager( $field_meta, 'quantities' );

// If options empty
if ( isset( $fm->options()[0]['option'] ) && $fm->options()[0]['option'] == '' ) {

	echo '<div class="ppom-option-notice">';
	echo '<p>' . esc_html( __( 'Please add some options to display variations.', 'woocommerce-product-addon' ) ) . '</p>';
	echo '</div>';

	return '';
}

$view_control = $fm->get_meta_value( 'view_control' );

/*
 * When a price matrix is attached, quantities render but their own option
 * prices are ignored (blanked in ppom_convert_options_to_key_val) — the
 * matrix drives pricing based on the total quantity.
 */

?>

<div class="ppom-input-quantities table-responsive <?php echo esc_attr( $fm->field_inner_wrapper_classes() ); ?>">

	<!-- if title of field exist -->
	<?php if ( $fm->field_label() ) : ?>
		<label
			class="<?php echo esc_attr( $fm->label_classes() ); ?>"
			for="<?php echo esc_attr( $fm->data_name() ); ?>"
		>
		<?php echo ppom_esc_html( $fm->field_label() ); ?>
		</label>
	<?php endif ?>

	<input type="hidden" name="ppom_quantities_option_price" id="ppom_quantities_option_price">

	<!-- Load differents Layout -->
	<?php
	$template_vars = array(
		'field_meta'    => $field_meta,
		'default_value' => $default_value,
		'product'       => $product,
	);

	if ( $view_control == 'horizontal' ) {
		ppom_load_input_templates( 'frontend/component/quantities/horizontal-layout.php', $template_vars );
	} elseif ( $view_control == 'simple_view' ) {
		ppom_load_input_templates( 'frontend/component/quantities/vertical-layout.php', $template_vars );
	} else {
		ppom_load_input_templates( 'frontend/component/quantities/grid-layout.php', $template_vars );
	}
	?>
</div>
