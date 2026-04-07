<?php
/**
 * @package PPOM
 */

namespace PPOM\FieldMarkup\Renderers;

use PPOM\FieldMarkup\AbstractInputRenderer;
use PPOM\FieldMarkup\FieldChrome;

final class FileRenderer extends AbstractInputRenderer {

	/**
	 * @param array<string, mixed> $args
	 * @param mixed                $default_files
	 * @return string
	 */
	public function render( $args, $default_files ) {
		$args = $this->coerceArgs( $args );

		$id    = $this->context->getAttributeValue( 'id', $args );
		$label = $this->context->getAttributeValue( 'label', $args );

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
		$html            .= '<span class="ppom-dragdrop-text">';
		$html            .= __( 'Drag File Here', 'woocommerce-product-addon' );
		$html            .= '</span>';
		$html            .= '</div>';

		if ( ! empty( $args['dragdrop'] ) ) {
			$html .= '<div class="ppom-droptext">';
			$html .= __( 'Drag file/directory here', 'woocommerce-product-addon' );
			$html .= '</div>';
		}

		$html .= '<div id="filelist-' . esc_attr( $args['id'] ) . '" class="filelist">';

		if ( ! empty( $default_files ) ) {
			foreach ( $default_files as $key => $file ) {
				$file_preview = ppom_uploaded_file_preview( $file['org'], $args );
				if ( ! isset( $file['org'] ) || '' === $file_preview ) {
					continue;
				}

				$html .= '<div class="u_i_c_box" id="u_i_c_' . esc_attr( $key ) . '" data-fileid="' . esc_attr( $key ) . '">';

				$html .= $file_preview;

				$file_name  = $file['org'];
				$data_name  = 'ppom[fields][' . $args['id'] . '][' . $key . '][org]';
				$file_class = 'ppom-file-cb ppom-file-cb-' . $args['id'];

				$html .= '<input checked="checked" name="' . esc_attr( $data_name ) . '" ';
				$html .= 'data-price="' . esc_attr( $args['file_cost'] ) . '" ';
				$html .= 'data-label="' . esc_attr( $file_name ) . '" ';
				$html .= 'data-title="' . esc_attr( $label ) . '" ';
				$html .= 'value="' . esc_attr( $file_name ) . '" ';
				$html .= 'class="' . esc_attr( $file_class ) . '" ';
				$html .= 'type="checkbox"/>';

				$html .= '</div>';
			}
		}

		$html .= '</div>';

		$html .= '</div>';

		return $this->applyOutputFilter( $html, $args, $default_files );
	}
}
