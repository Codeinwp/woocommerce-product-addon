<?php
/**
 * Password Input Template.
 *
 * @package WooCommerce Product Addon
 **/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** @var PPOM_SettingsFramework $class_ins */

$input_id = isset( $input_meta['input_id'] ) ? $input_meta['input_id'] : '';
$default  = isset( $input_meta['default'] ) ? $input_meta['default'] : '';
$saved    = esc_attr( $class_ins::get_saved_settings( $input_id, $default ) );
?>

<div class="ppom-password-field-wrap">
	<input
		type="password"
		name="<?php echo esc_attr( $class_ins::get_form_name( $input_id ) ); ?>"
		data-rule-id="<?php echo esc_attr( $input_id ); ?>"
		id="<?php echo esc_attr( $input_id ); ?>"
		value="<?php echo esc_attr( $saved ); ?>"
		autocomplete="new-password"
	>
	<button type="button"
		class="ppom-eye-toggle button"
		data-target="<?php echo esc_attr( $input_id ); ?>"
		aria-label="<?php esc_attr_e( 'Toggle visibility', 'woocommerce-product-addon' ); ?>"
		title="<?php esc_attr_e( 'Show / Hide', 'woocommerce-product-addon' ); ?>">
		<span class="dashicons dashicons-visibility"></span>
	</button>
	<button type="button"
		class="ppom-copy-btn button"
		data-target="<?php echo esc_attr( $input_id ); ?>"
		aria-label="<?php esc_attr_e( 'Copy to clipboard', 'woocommerce-product-addon' ); ?>"
		title="<?php esc_attr_e( 'Copy', 'woocommerce-product-addon' ); ?>">
		<span class="dashicons dashicons-clipboard"></span>
	</button>
</div>
