<?php
/**
 * @package PPOM
 */

namespace PPOM\FieldMarkup\Renderers;

use PPOM\FieldMarkup\AbstractInputRenderer;
use PPOM\FieldMarkup\FieldChrome;

final class CropperRenderer extends AbstractInputRenderer {

	/**
	 * @param array<string, mixed> $args
	 * @param mixed                $selected_value
	 * @return string
	 */
	public function render( $args, $selected_value ) {
		$args = $this->coerceArgs( $args );

		$id      = $this->context->getAttributeValue( 'id', $args );
		$name    = $this->context->getAttributeValue( 'name', $args );
		$label   = $this->context->getAttributeValue( 'label', $args );
		$title   = $this->context->getAttributeValue( 'title', $args );
		$classes = $this->context->getAttributeValue( 'classes', $args );

		$input_wrapper_class = FieldChrome::inputWrapperClass( $this->context, $id, $args );

		$html = '<div id="ppom-file-container-' . esc_attr( $args['id'] ) . '" class="' . $input_wrapper_class . '">';
		if ( $label ) {
			$html .= '<label class="' . $this->context->getDefaultSettingValue( 'global', 'label_class', $id ) . '" for="' . $id . '">';
			$html .= $label . '</label>';
		}

		$container_height = isset( $args['dragdrop'] ) ? 'auto' : '30px';
		$html            .= '<div class="ppom-file-container text-center" ';
		$html            .= 'style="height: ' . esc_attr( $container_height ) . ' ;">';
		$html            .= '<a id="selectfiles-' . esc_attr( $args['id'] ) . '" ';
		$html            .= 'href="javascript:;" ';
		$html            .= 'class="btn btn-primary ' . esc_attr( $args['button_class'] ) . '">';
		$html            .= $args['button_label'] . '</a>';
		$html            .= '<span class="ppom-dragdrop-text">' . __( 'Drag file/directory here', 'woocommerce-product-addon' ) . '</span>';
		$html            .= '</div>';

		if ( ! empty( $args['dragdrop'] ) ) {
			$html .= '<div class="ppom-droptext">';
			$html .= __( 'Drag file/directory here', 'woocommerce-product-addon' );
			$html .= '</div>';
		}

		$html .= '<div id="filelist-' . esc_attr( $args['id'] ) . '" class="filelist"></div>';

		$html .= '<div class="ppom-croppie-wrapper-' . esc_attr( $args['id'] ) . ' text-center">';
		$html .= '<div class="ppom-croppie-preview">';

		if ( isset( $args['options'] ) && count( $args['options'] ) > 0 ) {
			$cropping_sizes = $args['options'];

			$select_css  = 'width:' . $args['croppie_options']['boundary']['width'] . 'px;';
			$select_css .= 'margin:5px auto;display:none;';

			$html .= '<select style="' . esc_attr( $select_css ) . '" ';
			$html .= 'class="' . esc_attr( $classes ) . '" ';
			$html .= 'name="' . esc_attr( $name ) . '[ratio]" ';
			$html .= 'data-field_name="' . esc_attr( $args['id'] ) . '" ';
			$html .= 'data-data_name="' . esc_attr( $args['id'] ) . '" ';
			$html .= 'id="crop-size-' . esc_attr( $args['id'] ) . '">';

			if ( ! empty( $args['first_option'] ) ) {
				$html .= sprintf( '<option value="">%s</option>', $args['first_option'] );
			}

			foreach ( $cropping_sizes as $key => $size ) {
				$option_label = $size['label'];
				$option_price = $size['price'];
				$raw_label    = $size['raw'];
				$without_tax  = $size['without_tax'];
				$option_id    = $size['option_id'];

				if ( '__first_option__' === $option_id ) {
					continue;
				}

				$html .= '<option ' . selected( $selected_value, $key, false ) . ' ';
				$html .= 'value="' . esc_attr( $option_id ) . '" ';
				$html .= 'data-price="' . esc_attr( $option_price ) . '" ';
				$html .= 'data-label="' . esc_attr( $raw_label ) . '" ';
				$html .= 'data-title="' . esc_attr( $title ) . '" ';
				$html .= 'data-without_tax="' . esc_attr( $without_tax ) . '" ';
				$html .= 'data-width="' . esc_attr( $size['width'] ) . '" data-height="' . esc_attr( $size['height'] ) . '" ';
				$html .= '>' . $option_label . '</option>';
			}

			$html .= '</select>';
		}

		$html .= '</div>';
		$html .= '</div>';

		$html .= '</div>';

		return $this->applyOutputFilter( $html, $args, $selected_value );
	}
}
