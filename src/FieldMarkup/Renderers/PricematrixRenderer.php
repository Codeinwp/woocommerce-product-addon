<?php
/**
 * @package PPOM
 */

namespace PPOM\FieldMarkup\Renderers;

use PPOM\FieldMarkup\AbstractInputRenderer;
use PPOM\FieldMarkup\FieldChrome;

final class PricematrixRenderer extends AbstractInputRenderer {

	/**
	 * @param array<string, mixed> $args
	 * @param mixed                $default_value
	 * @return string
	 */
	public function render( $args, $default_value ) {
		$args = $this->coerceArgs( $args );

		$id        = $this->context->getAttributeValue( 'id', $args );
		$label     = $this->context->getAttributeValue( 'label', $args );
		$ranges    = $args['ranges'];
		$discount  = $args['discount'];
		$is_hidden = ( isset( $args['hide_matrix'] ) && 'on' === $args['hide_matrix'] );

		$input_wrapper_class = FieldChrome::inputWrapperClass( $this->context, $id, $args );
		$html                = '<div class="' . $input_wrapper_class . '">';
		if ( $label ) {
			$html .= '<label class="' . $this->context->getDefaultSettingValue( 'global', 'label_class', $id ) . '" for="' . $id . '">';
			$html .= $label . '</label>';
		}

		if ( ! $is_hidden ) {
			foreach ( $ranges as $opt ) {
				$price = isset( $opt['raw_price'] ) ? trim( $opt['raw_price'] ) : 0;
				$label = isset( $opt['label'] ) ? $opt['label'] : $opt['raw'];

				if ( ! empty( $opt['percent'] ) ) {
					$percent = $opt['percent'];
					if ( 'on' === $discount ) {
						$price = "-{$percent}";
					} else {
						$price = "{$percent} (" . wc_price( (float) $price ) . ')';
					}
				} else {
					$price = wc_price( (float) $price );
				}

				$range_id = isset( $opt['option_id'] ) ? $opt['option_id'] : '';

				$html .= '<div class="ppom-pricematrix-range ppom-range-' . esc_attr( (string) $range_id ) . '">';
				$html .= '<span class="pm-range">' . apply_filters( 'ppom_matrix_item_label', stripslashes( trim( $label ) ), $opt ) . '</span>';
				$html .= '<span class="pm-price" style="float:right">' . apply_filters( 'ppom_matrix_item_price', $price, $opt ) . '</span>';
				$html .= '</div>';
			}
		}

		if ( isset( $args['show_slider'] ) && 'on' === $args['show_slider'] ) {
			$first_range  = reset( $ranges );
			$qty_ranges   = explode( '-', $first_range['raw'] );
			$min_quantity = (int) $qty_ranges[0] - 1;

			$last_range   = end( $ranges );
			$qty_ranges   = explode( '-', $last_range['raw'] );
			$max_quantity = isset( $qty_ranges[1] ) ? $qty_ranges[1] : $qty_ranges[0];
			$qty_step     = ! empty( $args['qty_step'] ) ? $args['qty_step'] : 1;

			$html .= '<div class="ppom-slider-container">';

			if ( apply_filters( 'ppom_range_slider_legacy', false, $args ) ) {
				$html .= '<input class="ppom-range-slide" data-slider-id="ppomSlider" ';
				$html .= 'type="text" data-slider-min="' . esc_attr( (string) $min_quantity ) . '"
    		                            data-slider-max="' . esc_attr( (string) $max_quantity ) . '" 
    		                            data-slider-step="' . esc_attr( (string) $qty_step ) . '" 
    		                            data-slider-value="0"/>';
			} else {
				$html .= '<input type="range" class="form-control-range ppom-range-bs-slider" 
		                                id="' . esc_attr( $id ) . '"
		                                min="' . esc_attr( (string) $min_quantity ) . '"
    		                            max="' . esc_attr( (string) $max_quantity ) . '" 
    		                            step="' . esc_attr( (string) $qty_step ) . '" >';
			}
			$html .= '</div>';
		}

		$html .= '</div>';

		$ranges_json = wp_json_encode( $ranges );
		$ranges_json = false === $ranges_json ? '' : $ranges_json;
		$html       .= '<input name="ppom[ppom_pricematrix]" data-dataname="' . esc_attr( $id ) . '" data-discount="' . esc_attr( $discount ) . '" class="active ppom_pricematrix ppom-input" type="hidden" value="' . esc_attr( $ranges_json ) . '" />';

		return $this->applyOutputFilter( $html, $args, $default_value );
	}
}
