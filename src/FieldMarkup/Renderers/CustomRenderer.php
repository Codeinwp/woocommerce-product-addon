<?php
/**
 * @package PPOM
 */

namespace PPOM\FieldMarkup\Renderers;

use PPOM\FieldMarkup\AbstractInputRenderer;
use PPOM\FieldMarkup\FieldChrome;

final class CustomRenderer extends AbstractInputRenderer {

	/**
	 * @param array<string, mixed> $args
	 * @param mixed                $default_value
	 * @return string
	 */
	public function render( $args, $default_value ) {
		$args = $this->coerceArgs( $args );

		$id    = $this->context->getAttributeValue( 'id', $args );
		$label = $this->context->getAttributeValue( 'label', $args );

		$input_wrapper_class = FieldChrome::inputWrapperClass( $this->context, $id, $args );
		$html                = '<div class="' . $input_wrapper_class . '">';
		if ( $label ) {
			$html .= '<label class="' . $this->context->getDefaultSettingValue( 'global', 'label_class', $id ) . '" for="' . $id . '">';
			$html .= wp_kses(
				$label,
				array(
					'span' => array(
						'class'  => true,
						'data-*' => true,
						'title'  => true,
					),
				)
			) . '</label>';
		}

		$html .= apply_filters( 'nmform_custom_input', $html, $args, $default_value );

		$html .= '</div>';

		return $this->applyOutputFilter( $html, $args, $default_value );
	}
}
