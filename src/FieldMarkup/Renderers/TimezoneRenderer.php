<?php
/**
 * @package PPOM
 */

namespace PPOM\FieldMarkup\Renderers;

use PPOM\FieldMarkup\AbstractInputRenderer;
use PPOM\FieldMarkup\FieldChrome;

final class TimezoneRenderer extends AbstractInputRenderer {

	/**
	 * @param array<string, mixed> $args
	 * @param mixed                $selected_value
	 * @return string
	 */
	public function render( $args, $selected_value ) {
		$args = $this->coerceArgs( $args );

		$label      = $this->context->getAttributeValue( 'label', $args );
		$classes    = $this->context->getAttributeValue( 'classes', $args );
		$id         = $this->context->getAttributeValue( 'id', $args );
		$name       = $this->context->getAttributeValue( 'name', $args );
		$multiple   = $this->context->getAttributeValue( 'multiple', $args );
		$attributes = $this->context->getAttributeValue( 'attributes', $args );

		$title = $args['title'];

		$options = $this->context->getAttributeValue( 'options', $args );

		if ( ! $options ) {
			return '';
		}

		$input_wrapper_class = FieldChrome::inputWrapperClass( $this->context, $id, $args );
		$html                = '<div class="' . $input_wrapper_class . '">';

		if ( $label ) {
			$html .= '<label class="' . $this->context->getDefaultSettingValue( 'global', 'label_class', $id ) . '" for="' . $id . '">';
			$html .= $label . '</label>';
		}

		$html .= '<select ';
		$html .= 'id="' . esc_attr( $id ) . '" ';
		$html .= 'name="' . esc_attr( $name ) . '" ';
		$html .= 'class="' . esc_attr( $classes ) . '" ';
		$html .= 'data-data_name="' . esc_attr( $id ) . '" ';
		$html .= ( $multiple ) ? 'multiple' : '';

		foreach ( $attributes as $attr => $value ) {
			$html .= esc_attr( $attr ) . '="' . esc_attr( $value ) . '" ';
		}

		$html .= '>';

		foreach ( $options as $key => $option_label ) {
			if ( is_array( $selected_value ) ) {
				foreach ( $selected_value as $s ) {
					$html .= '<option ' . selected( $s, $key, false ) . ' value="' . esc_attr( $key ) . '" ';
					$html .= 'data-price="" ';
					$html .= 'data-label="' . esc_attr( $option_label ) . '" ';
					$html .= 'data-onetime="" ';
					$html .= '>' . $option_label . '</option>';
				}
			} else {
				$html .= '<option ' . selected( $selected_value, $key, false ) . ' ';
				$html .= 'value="' . esc_attr( $key ) . '" ';
				$html .= 'data-title="' . esc_attr( $title ) . '" ';
				$html .= '>' . $option_label . '</option>';
			}
		}

		$html .= '</select>';
		$html .= '</div>';

		return $this->applyOutputFilter( $html, $args, $selected_value );
	}
}
