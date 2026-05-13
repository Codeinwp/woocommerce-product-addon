<?php
/**
 * @package PPOM
 */

namespace PPOM\FieldMarkup\Renderers;

use PPOM\FieldMarkup\AbstractInputRenderer;

/**
 * Renders a hidden PPOM field for the FieldMarkup registry path.
 */
final class HiddenInputRenderer extends AbstractInputRenderer {

	/**
	 * @param array<string, mixed> $args            Field arguments (id, name, field_value).
	 * @param mixed                $default_value Used when field_value is empty.
	 * @return string
	 */
	public function render( $args, $default_value ) {
		$args = $this->coerceArgs( $args );

		$id   = $this->context->getAttributeValue( 'id', $args );
		$name = $this->context->getAttributeValue( 'name', $args );

		$field_value = isset( $args['field_value'] ) ? $args['field_value'] : '';
		if ( '' === $field_value && '' !== $default_value && ! is_array( $default_value ) ) {
			$field_value = $default_value;
		}

		$html  = '<input type="hidden"';
		$html .= ' id="' . esc_attr( $id ) . '"';
		$html .= ' name="' . esc_attr( $name ) . '"';
		$html .= ' value="' . esc_attr( (string) $field_value ) . '" />';

		return $this->applyOutputFilter( $html, $args, $default_value );
	}
}
