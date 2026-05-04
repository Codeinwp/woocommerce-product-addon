<?php
/**
 * Settings Panel Text Input Template
 **/

/* 
**========== Block direct access =========== 
*/
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$input_id    = isset( $input_meta['input_id'] ) ? $input_meta['input_id'] : '';
$default     = isset( $input_meta['default'] ) ? $input_meta['default'] : '';
$is_readonly = isset( $input_meta['readonly'] ) ? $input_meta['readonly'] : false;
$class       = isset( $input_meta['class'] ) ? $input_meta['class'] : '';

?>

<input
		type="text" <?php echo $is_readonly ? 'readonly' : ''; ?>
		class="<?php echo esc_attr( $class ); ?>"
		name="<?php echo esc_attr( $class_ins::get_form_name( $input_id ) ); ?>"
		data-rule-id="<?php echo esc_attr( $input_id ); ?>"
		id="<?php echo esc_attr( $input_id ); ?>"
		value="<?php echo esc_attr( $class_ins::get_saved_settings( $input_id, $default ) ); ?>"
>
