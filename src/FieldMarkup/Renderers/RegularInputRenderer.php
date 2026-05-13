<?php
/**
 * text, date, daterange, datetime-local, email, number, color
 *
 * @package PPOM
 */

namespace PPOM\FieldMarkup\Renderers;

use PPOM\FieldMarkup\AbstractInputRenderer;
use PPOM\FieldMarkup\FieldChrome;

final class RegularInputRenderer extends AbstractInputRenderer {

	/**
	 * @param array<string, mixed> $args
	 * @param mixed                $default_value
	 * @return string
	 */
	public function render( $args, $default_value ) {
		$args    = $this->coerceArgs( $args );
		$product = isset( $args['product_id'] ) ? wc_get_product( $args['product_id'] ) : null;

		$type = $this->context->getAttributeValue( 'type', $args );

		$label       = $this->context->getAttributeValue( 'label', $args );
		$classes     = $this->context->getAttributeValue( 'classes', $args );
		$id          = $this->context->getAttributeValue( 'id', $args );
		$name        = $this->context->getAttributeValue( 'name', $args );
		$placeholder = $this->context->getAttributeValue( 'placeholder', $args );
		$attributes  = $this->context->getAttributeValue( 'attributes', $args );

		$num_min = $this->context->getAttributeValue( 'min', $args );
		$num_max = $this->context->getAttributeValue( 'max', $args );
		$step    = $this->context->getAttributeValue( 'step', $args );

		$onetime           = $this->context->getAttributeValue( 'onetime', $args );
		$taxable           = $this->context->getAttributeValue( 'taxable', $args );
		$price             = $this->context->getAttributeValue( 'price', $args );
		$price_without_tax = '';

		$title = $args['title'];

		if ( 'on' === $onetime && 'on' === $taxable ) {
			$price_without_tax = $price;
			$price             = ppom_get_price_including_tax( $price, $product );
		}

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

		if ( '' !== $price ) {
			$classes .= ' ppom-priced';
		}

		$html .= '<input type="' . esc_attr( $type ) . '" ';
		$html .= 'id="' . esc_attr( $id ) . '" ';
		$html .= 'name="' . esc_attr( $name ) . '" ';
		$html .= 'class="' . esc_attr( $classes ) . '" ';
		$html .= 'placeholder="' . esc_attr( $placeholder ) . '" ';
		$html .= 'autocomplete="off" ';
		$html .= 'data-type="' . esc_attr( $type ) . '" ';
		$html .= 'data-data_name="' . esc_attr( $id ) . '" ';
		$html .= 'data-title="' . esc_attr( $title ) . '" ';
		$html .= 'data-price="' . esc_attr( $price ) . '" ';
		$html .= 'data-onetime="' . esc_attr( $onetime ) . '" ';
		$html .= 'data-taxable="' . esc_attr( $taxable ) . '" ';
		$html .= 'data-without_tax="' . esc_attr( $price_without_tax ) . '" ';

		if ( 'number' === $type ) {
			$html .= 'min="' . esc_attr( $num_min ) . '" ';
			$html .= 'max="' . esc_attr( $num_max ) . '" ';
			$html .= 'step="' . esc_attr( $step ) . '" ';
		}

		if ( isset( $args['use_regex'] ) && 'on' === $args['use_regex'] && isset( $args['input_mask'] ) && '' !== $args['input_mask'] ) {
			$html .= 'data-inputmask-regex="' . esc_attr( $args['input_mask'] ) . '" ';
		}

		if ( '' != $default_value ) {
			$html .= 'value="' . esc_attr( $default_value ) . '" ';
		}

		foreach ( $attributes as $attr => $value ) {
			$html .= esc_attr( $attr ) . '="' . esc_attr( $value ) . '" ';
		}

		$html .= '>';
		$html .= '</div>';

		return $this->applyOutputFilter( $html, $args, $default_value );
	}
}
