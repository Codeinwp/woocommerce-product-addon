<?php
/**
 * @package PPOM
 */

namespace PPOM\FieldMarkup\Renderers;

use PPOM\FieldMarkup\AbstractInputRenderer;
use PPOM\FieldMarkup\FieldChrome;

final class RadioRenderer extends AbstractInputRenderer {

	/**
	 * @param array<string, mixed> $args
	 * @param mixed                $checked_value
	 * @return string
	 */
	public function render( $args, $checked_value ) {
		$args = $this->coerceArgs( $args );

		$type = $this->context->getAttributeValue( 'type', $args );

		$label   = $this->context->getAttributeValue( 'label', $args );
		$classes = $this->context->getAttributeValue( 'classes', $args );
		$id      = $this->context->getAttributeValue( 'id', $args );
		$name    = $this->context->getAttributeValue( 'name', $args );

		$title   = $args['title'];
		$onetime = $args['onetime'];
		$taxable = $args['taxable'];

		$options = $this->context->getAttributeValue( 'options', $args );
		if ( ! $options ) {
			return '';
		}

		$radio_wrapper_class = apply_filters( 'ppom_radio_wrapper_class', 'form-check' );
		$radio_label_class   = $this->context->getAttributeValue( 'radio_label_class', $args );

		$input_wrapper_class = FieldChrome::inputWrapperClass( $this->context, $id, $args );
		$html                = '<div class="' . $input_wrapper_class . '">';

		if ( $label ) {
			$html .= '<label class="' . $this->context->getDefaultSettingValue( 'global', 'label_class', $id ) . '" for="' . $id . '">';
			$html .= $label . '</label>';
		}

		foreach ( $options as $key => $value ) {
			$option_label = $value['label'];
			$option_price = $value['price'];
			$raw_label    = $value['raw'];
			$without_tax  = $value['without_tax'];
			$option_id    = $value['option_id'];
			$dom_id       = apply_filters( 'ppom_dom_option_id', $option_id, $args );

			$checked_option = '';
			if ( '' != $checked_value ) {
				$checked_value  = stripcslashes( $checked_value );
				$checked_option = checked( $checked_value, $key, false );
			}

			$html .= '<div class="' . esc_attr( $radio_wrapper_class ) . '">';
			$html .= '<label class="' . esc_attr( $radio_label_class ) . '" for="' . esc_attr( $dom_id ) . '">';
			$html .= '<input type="' . esc_attr( $type ) . '" ';
			$html .= 'id="' . esc_attr( $dom_id ) . '" ';
			$html .= 'name="' . esc_attr( $name ) . '" ';
			$html .= 'class="' . esc_attr( $classes ) . '" ';
			$html .= 'value="' . esc_attr( $key ) . '" ';
			$html .= 'data-price="' . esc_attr( $option_price ) . '" ';
			$html .= 'data-optionid="' . esc_attr( $option_id ) . '" ';
			$html .= 'data-label="' . esc_attr( $raw_label ) . '" ';
			$html .= 'data-title="' . esc_attr( $title ) . '" ';
			$html .= 'data-onetime="' . esc_attr( $onetime ) . '" ';
			$html .= 'data-taxable="' . esc_attr( $taxable ) . '" ';
			$html .= 'data-without_tax="' . esc_attr( $without_tax ) . '" ';
			$html .= 'data-data_name="' . esc_attr( $id ) . '" ';
			$html .= $checked_option;
			$html .= '> ';
			$html .= '<span class="ppom-label-radio">' . $option_label . '</span>';
			$html .= '</label>';
			$html .= '</div>';
		}

		$html .= '</div>';

		return $this->applyOutputFilter( $html, $args, $checked_value );
	}
}
