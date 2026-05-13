<?php
/**
 * @package PPOM
 */

namespace PPOM\FieldMarkup\Renderers;

use PPOM\FieldMarkup\AbstractInputRenderer;
use PPOM\FieldMarkup\FieldChrome;

final class SelectRenderer extends AbstractInputRenderer {

	/**
	 * @param array<string, mixed> $args
	 * @param mixed                $selected_value
	 * @return string
	 */
	public function render( $args, $selected_value ) {
		$args    = $this->coerceArgs( $args );
		$product = isset( $args['product_id'] ) ? wc_get_product( $args['product_id'] ) : null;

		$type       = $this->context->getAttributeValue( 'type', $args );
		$label      = $this->context->getAttributeValue( 'label', $args );
		$classes    = $this->context->getAttributeValue( 'classes', $args );
		$id         = $this->context->getAttributeValue( 'id', $args );
		$name       = $this->context->getAttributeValue( 'name', $args );
		$multiple   = $this->context->getAttributeValue( 'multiple', $args );
		$attributes = $this->context->getAttributeValue( 'attributes', $args );

		$title   = $args['title'];
		$onetime = $args['onetime'];
		$taxable = $args['taxable'];

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

		$product_type = $product->get_type();

		foreach ( $options as $key => $value ) {
			$option_label = $value['label'];
			$option_price = $value['price'];
			$option_id    = isset( $value['id'] ) ? $value['id'] : '';
			$raw_label    = $value['raw'];
			$without_tax  = $value['without_tax'];
			$opt_percent  = isset( $value['percent'] ) ? $value['percent'] : '';

			$ppom_has_percent = '' !== $opt_percent ? 'ppom-option-has-percent' : '';
			$option_class     = array(
				"ppom-option-{$option_id}",
				"ppom-{$product_type}-option",
				$ppom_has_percent,
			);
			$option_class     = apply_filters( 'ppom_option_classes', implode( ' ', $option_class ), $args );

			if ( empty( $option_price ) && ! empty( $value['option_weight'] ) ) {
				$option_price = 0;
			}

			if ( is_array( $selected_value ) ) {
				foreach ( $selected_value as $s ) {
					$html .= '<option ' . selected( $s, $key, false ) . ' value="' . esc_attr( $key ) . '" ';
					$html .= 'class="' . esc_attr( $option_class ) . '" ';
					$html .= 'data-price="' . esc_attr( $option_price ) . '" ';
					$html .= 'data-label="' . esc_attr( $option_label ) . '" ';
					$html .= 'data-onetime="' . esc_attr( $onetime ) . '" ';
					$html .= '>' . $option_label . '</option>';
				}
			} else {
				$html .= '<option ' . selected( $selected_value, $key, false ) . ' ';
				$html .= 'value="' . esc_attr( $key ) . '" ';
				$html .= 'class="' . esc_attr( $option_class ) . '" ';
				$html .= 'data-price="' . esc_attr( $option_price ) . '" ';
				$html .= 'data-optionid="' . esc_attr( $option_id ) . '" ';
				$html .= 'data-percent="' . esc_attr( $opt_percent ) . '" ';
				$html .= 'data-label="' . esc_attr( $raw_label ) . '" ';
				$html .= 'data-title="' . esc_attr( $title ) . '" ';
				$html .= 'data-onetime="' . esc_attr( $onetime ) . '" ';
				$html .= 'data-taxable="' . esc_attr( $taxable ) . '" ';
				$html .= 'data-without_tax="' . esc_attr( $without_tax ) . '" ';
				$html .= 'data-data_name="' . esc_attr( $id ) . '" ';
				$html .= '>' . $option_label . '</option>';
			}
		}

		$html .= '</select>';
		$html .= '</div>';

		return $this->applyOutputFilter( $html, $args, $selected_value );
	}
}
