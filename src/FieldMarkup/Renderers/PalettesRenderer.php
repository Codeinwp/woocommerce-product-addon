<?php
/**
 * @package PPOM
 */

namespace PPOM\FieldMarkup\Renderers;

use PPOM\FieldMarkup\AbstractInputRenderer;
use PPOM\FieldMarkup\FieldChrome;

final class PalettesRenderer extends AbstractInputRenderer {

	/**
	 * @param array<string, mixed> $args
	 * @param mixed                $default_value
	 * @return string
	 */
	public function render( $args, $default_value ) {
		$args = $this->coerceArgs( $args );

		$type    = $this->context->getAttributeValue( 'type', $args );
		$id      = $this->context->getAttributeValue( 'id', $args );
		$name    = $this->context->getAttributeValue( 'name', $args );
		$label   = $this->context->getAttributeValue( 'label', $args );
		$classes = isset( $args['classes'] ) ? $args['classes'] : '';

		$title   = $args['title'];
		$onetime = $args['onetime'];
		$taxable = $args['taxable'];

		$options = isset( $args['options'] ) ? $args['options'] : '';
		if ( ! $options ) {
			return '';
		}

		$html = '';

		if ( ! is_array( $default_value ) ) {
			$default_value = explode( ',', $default_value );
		}

		$checked_value = array_map( 'trim', $default_value );

		$selected_palette_bcolor = isset( $args['selected_palette_bcolor'] ) ? $args['selected_palette_bcolor'] : '';
		$html                   .= '<style>';
		$html                   .= '.ppom-palettes label > input:checked + .ppom-single-palette {
                        border: 2px solid ' . esc_attr( $selected_palette_bcolor ) . ' !important;
                    }';
		$html                   .= '</style>';

		$input_wrapper_class = FieldChrome::inputWrapperClass( $this->context, $id, $args );
		$html               .= '<div class="' . $input_wrapper_class . '">';
		if ( $label ) {
			$html .= '<label class="' . $this->context->getDefaultSettingValue( 'global', 'label_class', $id ) . '" for="' . $id . '">';
			$html .= $label . '</label>';
		}
		$html .= '<div class="ppom-palettes ppom-palettes-' . esc_attr( $id ) . '">';
		foreach ( $options as $key => $value ) {
			$color_label_arr = explode( '-', $key );
			$color_code      = trim( $color_label_arr[0] );
			$color_label     = '';
			if ( isset( $color_label_arr[1] ) ) {
				$color_label = trim( $color_label_arr[1] );
			}

			$color_label  = $value['label'];
			$option_label = $value['label'];
			$option_price = $value['price'];
			$raw_label    = $value['raw'];
			$without_tax  = $value['without_tax'];

			$option_id = $value['option_id'];
			$dom_id    = apply_filters( 'ppom_dom_option_id', $option_id, $args );

			$checked_option = '';
			if ( count( $checked_value ) > 0 && in_array( $key, $checked_value, false ) && ! empty( $key ) ) {
				$checked_option = checked( $key, $key, false );
			}

			$html .= '<label for="' . esc_attr( $dom_id ) . '"> ';

			if ( isset( $args['multiple_allowed'] ) && 'on' === $args['multiple_allowed'] ) {
				$html .= '<input id="' . esc_attr( $dom_id ) . '" ';
				$html .= 'class="ppom-input" ';
				$html .= 'data-price="' . esc_attr( $option_price ) . '" ';
				$html .= 'data-label="' . esc_attr( $color_label ) . '" ';
				$html .= 'data-title="' . esc_attr( $title ) . '" ';
				$html .= 'type="checkbox" ';
				$html .= 'name="' . esc_attr( $name ) . '[]" ';
				$html .= 'value="' . esc_attr( $raw_label ) . '" ';
				$html .= 'data-onetime="' . esc_attr( $onetime ) . '" ';
				$html .= 'data-taxable="' . esc_attr( $taxable ) . '" ';
				$html .= 'data-without_tax="' . esc_attr( $without_tax ) . '" ';
				$html .= 'data-optionid="' . esc_attr( $option_id ) . '" ';
				$html .= 'data-data_name="' . esc_attr( $id ) . '" ';
				$html .= $checked_option;
				$html .= '>';
			} else {
				$html .= '<input id="' . esc_attr( $dom_id ) . '" ';
				$html .= 'class="ppom-input" ';
				$html .= 'data-price="' . esc_attr( $option_price ) . '" ';
				$html .= 'data-label="' . esc_attr( $color_label ) . '" ';
				$html .= 'data-title="' . esc_attr( $title ) . '" ';
				$html .= 'type="radio" ';
				$html .= 'name="' . esc_attr( $name ) . '[]" ';
				$html .= 'value="' . esc_attr( $raw_label ) . '" ';
				$html .= 'data-onetime="' . esc_attr( $onetime ) . '" ';
				$html .= 'data-taxable="' . esc_attr( $taxable ) . '" ';
				$html .= 'data-without_tax="' . esc_attr( $without_tax ) . '" ';
				$html .= 'data-optionid="' . esc_attr( $option_id ) . '" ';
				$html .= 'data-data_name="' . esc_attr( $id ) . '" ';
				$html .= $checked_option;
				$html .= '>';
			}

			$html .= '<span class="ppom-single-palette" ';
			$html .= 'title="' . esc_attr( $option_label ) . '" data-ppom-tooltip="ppom_tooltip" ';
			$html .= 'style="background-color:' . esc_attr( $color_code ) . '; ';
			$html .= 'width:' . esc_attr( $args['color_width'] ) . 'px; ';
			$html .= 'height:' . esc_attr( $args['color_height'] ) . 'px; ';
			if ( ! empty( $args['display_circle'] ) ) {
				$html .= 'border-radius: 50%; ';
			}
			$html .= '">';
			$html .= '</span>';

			$html .= '</label>';
		}
		$html .= '</div>';

		$html .= '</div>';

		return $this->applyOutputFilter( $html, $args, $default_value );
	}
}
