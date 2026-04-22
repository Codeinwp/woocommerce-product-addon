<?php
/**
 * @package PPOM
 */

namespace PPOM\FieldMarkup\Renderers;

use PPOM\FieldMarkup\AbstractInputRenderer;
use PPOM\FieldMarkup\FieldChrome;

final class SectionRenderer extends AbstractInputRenderer {

	/**
	 * @param array<string, mixed> $args
	 * @param mixed                $default_value
	 * @return string
	 */
	public function render( $args, $default_value ) {
		$args = $this->coerceArgs( $args );

		$name       = $this->context->getAttributeValue( 'name', $args );
		$id         = $this->context->getAttributeValue( 'id', $args );
		$label      = $this->context->getAttributeValue( 'label', $args );
		$field_html = $this->context->getAttributeValue( 'html', $args );

		$input_wrapper_class = FieldChrome::inputWrapperClass( $this->context, $id, $args );
		$html                = '<div class="' . $input_wrapper_class . '">';

		if ( $label ) {
			$field_html = $field_html . $label;
		}

		$html_content = apply_filters( 'ppom_section_content', $field_html );

		$html .= stripslashes( $html_content );

		$html .= '<div style="clear: both"></div>';

		$html .= '<input type="hidden" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $field_html ) . '">';

		$html .= '</div>';

		return $this->applyOutputFilter( $html, $args, $default_value );
	}
}
