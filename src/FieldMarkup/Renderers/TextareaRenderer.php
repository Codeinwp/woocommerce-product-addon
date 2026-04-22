<?php
/**
 * @package PPOM
 */

namespace PPOM\FieldMarkup\Renderers;

use PPOM\FieldMarkup\AbstractInputRenderer;
use PPOM\FieldMarkup\FieldChrome;

final class TextareaRenderer extends AbstractInputRenderer {

	/**
	 * @param array<string, mixed> $args
	 * @param mixed                $default_value
	 * @return string
	 */
	public function render( $args, $default_value ) {
		$args = $this->coerceArgs( $args );

		$label       = $this->context->getAttributeValue( 'label', $args );
		$classes     = $this->context->getAttributeValue( 'classes', $args );
		$id          = $this->context->getAttributeValue( 'id', $args );
		$name        = $this->context->getAttributeValue( 'name', $args );
		$placeholder = $this->context->getAttributeValue( 'placeholder', $args );
		$attributes  = $this->context->getAttributeValue( 'attributes', $args );
		$rich_editor = $this->context->getAttributeValue( 'rich_editor', $args );

		$rows = $this->context->getAttributeValue( 'rows', $args );

		$input_wrapper_class = FieldChrome::inputWrapperClass( $this->context, $id, $args );
		$html                = '<div class="' . $input_wrapper_class . '">';

		if ( $label ) {
			$html .= '<label class="' . $this->context->getDefaultSettingValue( 'global', 'label_class', $id ) . '" for="' . $id . '">';
			$html .= $label . '</label>';
		}

		if ( 'on' === $rich_editor ) {
			$wp_editor_setting = array(
				'media_buttons' => false,
				'textarea_rows' => $rows,
				'editor_class'  => $classes,
				'teeny'         => true,
				'textarea_name' => $name,
			);

			ob_start();
			wp_editor( $default_value, $id, $wp_editor_setting );
			$html .= ob_get_clean();
		} else {
			$html .= '<textarea ';
			$html .= 'id="' . esc_attr( $id ) . '" ';
			$html .= 'name="' . esc_attr( $name ) . '" ';
			$html .= 'data-data_name="' . esc_attr( $id ) . '" ';
			$html .= 'class="' . esc_attr( $classes ) . '" ';
			$html .= 'placeholder="' . esc_attr( $placeholder ) . '" ';
			$html .= 'rows="' . esc_attr( $rows ) . '" ';

			foreach ( $attributes as $attr => $value ) {
				$html .= esc_attr( $attr ) . '="' . esc_attr( $value ) . '" ';
			}

			$html .= '>';

			if ( '' != $default_value ) {
				$default_value = str_replace( '<br />', "\n", $default_value );
				$html         .= esc_html( $default_value );
			}

			$html .= '</textarea>';
		}

		$html .= '</div>';

		return $this->applyOutputFilter( $html, $args, $default_value );
	}
}
