<?php
/**
 * @package PPOM
 */

namespace PPOM\FieldMarkup\Renderers;

use PPOM\FieldMarkup\AbstractInputRenderer;
use PPOM\FieldMarkup\FieldChrome;

final class AudioVideoRenderer extends AbstractInputRenderer {

	/**
	 * @param array<string, mixed> $args
	 * @param mixed                $default_value
	 * @return string
	 */
	public function render( $args, $default_value ) {
		$args = $this->coerceArgs( $args );

		$type  = $this->context->getAttributeValue( 'type', $args );
		$id    = $this->context->getAttributeValue( 'id', $args );
		$name  = $this->context->getAttributeValue( 'name', $args );
		$label = $this->context->getAttributeValue( 'label', $args );
		$title = $this->context->getAttributeValue( 'title', $args );

		$audios = isset( $args['audios'] ) ? $args['audios'] : array();
		if ( ! is_array( $audios ) || array() === $audios ) {
			return __( 'audios not selected', 'woocommerce-product-addon' );
		}

		$input_wrapper_class = FieldChrome::inputWrapperClass( $this->context, $id, $args );
		$html                = '<div class="' . $input_wrapper_class . '">';
		if ( $label ) {
			$html .= '<label class="' . $this->context->getDefaultSettingValue( 'global', 'label_class', $id ) . '" for="' . $id . '">';
			$html .= $label . '</label>';
		}

		$html .= '<div class="ppom_audio_box">';
		foreach ( $audios as $audio ) {
			if ( ! is_array( $audio ) ) {
				continue;
			}

			$audio_id    = isset( $audio['id'] ) ? $audio['id'] : 0;
			$audio_title = isset( $audio['title'] ) ? stripslashes( (string) $audio['title'] ) : '';
			$audio_price = isset( $audio['price'] ) ? $audio['price'] : 0;

			$audio_url         = wp_get_attachment_url( $audio_id );
			$price_html        = ( $audio_price > 0 ) ? wp_kses_post( ppom_price( $audio_price ) ) : '';
			$audio_title_price = trim( esc_html( $audio_title ) . ( '' !== $price_html ? ' ' . $price_html : '' ) );

			$audio_json = (string) json_encode( $audio );

			$checked_option = '';
			if ( ! empty( $default_value ) ) {
				$checked_option = checked( $default_value, $audio_json, false );
			}

			$html .= '<div class="ppom_audio">';

			if ( ! empty( $audio_url ) ) {
				$html .= apply_filters( 'the_content', $audio_url );
			}

			$html .= '<div class="input_image">';
			if ( isset( $args['multiple_allowed'] ) && 'on' === $args['multiple_allowed'] ) {
				$html .= '<input type="checkbox" ';
				$html .= 'data-price="' . esc_attr( (string) $audio_price ) . '" ';
				$html .= 'class="ppom-input" ';
				$html .= 'data-data_name="' . esc_attr( $id ) . '" ';
				$html .= 'data-label="' . esc_attr( $audio_title ) . '" ';
				$html .= 'data-title="' . esc_attr( (string) $title ) . '" ';
				$html .= 'name="' . esc_attr( $name ) . '[]" ';
				$html .= 'value="' . esc_attr( $audio_json ) . '" />';
			} else {
				$html .= '<input type="radio" ';
				$html .= 'data-price="' . esc_attr( (string) $audio_price ) . '" ';
				$html .= 'data-label="' . esc_attr( $audio_title ) . '" ';
				$html .= 'class="ppom-input" ';
				$html .= 'data-data_name="' . esc_attr( $id ) . '" ';
				$html .= 'data-title="' . esc_attr( (string) $title ) . '" ';
				$html .= 'data-type="' . esc_attr( $type ) . '" name="' . esc_attr( $name ) . '[]" ';
				$html .= 'value="' . esc_attr( $audio_json ) . '" ' . $checked_option . ' />';
			}

			$html .= '<div class="p_u_i_name">' . $audio_title_price . '</div>';
			$html .= '</div>';

			$html .= '</div>';
		}

		$html .= '</div>';
		$html .= '</div>';

		return $this->applyOutputFilter( $html, $args, $default_value );
	}
}
