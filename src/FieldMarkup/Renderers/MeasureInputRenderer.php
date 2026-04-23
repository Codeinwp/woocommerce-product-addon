<?php
/**
 * @package PPOM
 */

namespace PPOM\FieldMarkup\Renderers;

use PPOM\FieldMarkup\AbstractInputRenderer;
use PPOM\FieldMarkup\FieldChrome;

final class MeasureInputRenderer extends AbstractInputRenderer {

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

		$num_min = '' == $num_min ? 0 : $num_min;

		$use_units = isset( $args['use_units'] ) && 'on' === $args['use_units'];

		$input_wrapper_class = FieldChrome::inputWrapperClass( $this->context, $id, $args );
		$html                = '<div class="' . esc_attr( $input_wrapper_class ) . '">';
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

		$classes .= ' ppom-measure-input';

		$options = $this->context->getAttributeValue( 'options', $args );

		$dom_type = 'number';

		$html .= '<div class="ppom-measure">';

		if ( $use_units ) {
			$html .= '<div class="form-check form-check-inline">';

			foreach ( $options as $option ) {
				$data_label = $label;
				$option_id  = $option['option_id'];
				$unit       = $option['raw'];
				$html      .= '<input checked name="ppom[unit][' . $id . ']" value="' . esc_attr( $unit ) . '" class="form-check-input ppom-measure-unit" type="radio" id="' . esc_attr( $option_id ) . '" data-apply="measure" ';
				$html      .= sprintf( 'data-use_units="' . esc_attr( $use_units ? '1' : '0' ) . '" data-price="%s" data-label="%s" data-data_name="%s" data-unit="%s" data-optionid="%s">', $option['price'], esc_attr( $data_label ), $id, $unit, $option_id );
				$html      .= '<label class="form-check-label" id="' . esc_attr( $option_id ) . '">';
				$html      .= $option['label'];
				$html      .= '</label>';
			}

			$html .= '</div>';
		} else {
			$option_id = "{$id}_unit";
			$html     .= '<input style="display:none"  value="" checked name="ppom[unit][' . $id . ']" class="form-check-input ppom-input ppom-measure-unit" type="radio" id="' . esc_attr( $option_id ) . '" data-apply="measure" ';
			$html     .= sprintf(
				'data-use_units="no" data-price="%1$s" data-label="%2$s" data-data_name="%3$s" data-optionid="%4$s" data-qty="%5$s">',
				ppom_get_product_price( $product ),
				esc_attr( $label ),
				$id,
				$option_id,
				esc_attr( $default_value )
			);
		}

		$html .= '<input type="' . esc_attr( $dom_type ) . '" ';
		$html .= 'id="' . esc_attr( $id ) . '" ';
		$html .= 'data-data_name="' . esc_attr( $id ) . '" ';
		$html .= 'name="' . esc_attr( $name ) . '" ';
		$html .= 'class="' . esc_attr( $classes ) . '" ';
		$html .= 'placeholder="' . esc_attr( $placeholder ) . '" ';
		$html .= 'autocomplete="false" ';
		$html .= 'data-type="' . esc_attr( $type ) . '" ';

		$html .= 'min="' . esc_attr( $num_min ) . '" ';
		$html .= 'max="' . esc_attr( $num_max ) . '" ';
		$html .= 'step="' . esc_attr( $step ) . '" ';
		$html .= 'data-price="' . esc_attr( ppom_get_product_price( $product ) ) . '" ';
		$html .= 'data-title="' . esc_attr( $label ) . '" ';
		$html .= 'data-use_units="' . esc_attr( $use_units ? '1' : '0' ) . '" ';

		if ( '' != $default_value ) {
			$html .= 'value="' . esc_attr( $default_value ) . '" ';
		}

		foreach ( $attributes as $attr => $value ) {
			$html .= esc_attr( $attr ) . '="' . esc_attr( $value ) . '" ';
		}

		$html .= '>';
		$html .= '</div>';
		$html .= '</div>';

		return $this->applyOutputFilter( $html, $args, $default_value );
	}
}
