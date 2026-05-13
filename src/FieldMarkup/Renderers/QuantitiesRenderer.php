<?php
/**
 * @package PPOM
 */

namespace PPOM\FieldMarkup\Renderers;

use PPOM\FieldMarkup\AbstractInputRenderer;
use PPOM\FieldMarkup\FieldChrome;

final class QuantitiesRenderer extends AbstractInputRenderer {

	/**
	 * @param array<string, mixed> $args
	 * @param mixed                $default_value
	 * @return string
	 */
	public function render( $args, $default_value ) {
		$args    = $this->coerceArgs( $args );
		$product = isset( $args['product_id'] ) ? wc_get_product( $args['product_id'] ) : null;

		$product_id   = ppom_get_product_id( $product );
		$matrix_found = ppom_has_field_by_type( $product_id, 'pricematrix' );
		if ( ! empty( $matrix_found ) && ppom_is_field_has_price( $args ) ) {
			$error_msg = __( 'Quantities cannot be used with Price Matrix, Remove prices from quantities input.', 'woocommerce-product-addon' );

			return sprintf( '<div class="woocommerce-error">%s</div>', $error_msg );
		}

		$id    = $this->context->getAttributeValue( 'id', $args );
		$label = $this->context->getAttributeValue( 'label', $args );

		$input_wrapper_class = FieldChrome::inputWrapperClass( $this->context, $id, $args );
		$html                = '<div class="ppom-input-quantities ' . $input_wrapper_class . ' table-responsive">';
		if ( $label ) {
			$html .= '<label class="' . $this->context->getDefaultSettingValue( 'global', 'label_class', $id ) . '" for="' . $id . '">';
			$html .= $label . '</label>';
		}

		$template_vars = array(
			'args'          => $args,
			'default_value' => $default_value,
		);
		ob_start();
		ppom_load_template( 'input/quantities.php', $template_vars );
		$html .= ob_get_clean();

		$html .= '</div>';

		return $this->applyOutputFilter( $html, $args, $default_value );
	}
}
