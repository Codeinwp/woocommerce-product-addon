<?php
/**
 * @package PPOM
 */

namespace PPOM\FieldMarkup\Renderers;

use PPOM\FieldMarkup\AbstractInputRenderer;
use PPOM\FieldMarkup\FieldChrome;

final class ImageRenderer extends AbstractInputRenderer {

	/**
	 * @param array<string, mixed> $args
	 * @param mixed                $default_value
	 * @return string
	 */
	public function render( $args, $default_value ) {
		$args    = $this->coerceArgs( $args );
		$product = isset( $args['product_id'] ) ? wc_get_product( $args['product_id'] ) : null;

		$type    = $this->context->getAttributeValue( 'type', $args );
		$id      = $this->context->getAttributeValue( 'id', $args );
		$name    = $this->context->getAttributeValue( 'name', $args );
		$label   = $this->context->getAttributeValue( 'label', $args );
		$classes = $this->context->getAttributeValue( 'classes', $args );

		$title = $args['title'];

		$images = isset( $args['images'] ) ? $args['images'] : '';
		if ( ! $images ) {
			return __( 'Images not selected', 'woocommerce-product-addon' );
		}

		$input_wrapper_class = FieldChrome::inputWrapperClass( $this->context, $id, $args );
		$html                = '<div class="' . $input_wrapper_class . '">';
		if ( $label ) {
			$html .= '<label class="' . $this->context->getDefaultSettingValue( 'global', 'label_class', $id ) . '" for="' . $id . '">';
			$html .= $label . '</label>';
		}

		$selected_img_bordercolor = isset( $args['selected_img_bordercolor'] ) ? $args['selected_img_bordercolor'] : '';
		$html                    .= '<style>';
		$html                    .= '.' . $id . ' .nm-boxes-outer input:checked + img {
                       border: 2px solid ' . esc_attr( $selected_img_bordercolor ) . ' !important;
                   }
                    .' . $id . ' .pre_upload_image img{
                       height: ' . esc_attr( $args['image_height'] ) . ' !important;
                       width : ' . esc_attr( $args['image_width'] ) . ' !important;
                   }';
		$html                    .= '</style>';

		if ( isset( $args['legacy_view'] ) && 'on' === $args['legacy_view'] ) {
			$html .= '<div class="ppom_upload_image_box">';
			foreach ( $images as $image ) {
				$image_full  = isset( $image['link'] ) ? $image['link'] : 0;
				$image_id    = isset( $image['image_id'] ) ? $image['image_id'] : 0;
				$image_title = isset( $image['raw'] ) ? stripslashes( $image['raw'] ) : '';
				$image_label = isset( $image['label'] ) ? stripslashes( $image['label'] ) : '';
				$image_price = isset( $image['price'] ) ? $image['price'] : 0;
				$option_id   = $id . '-' . $image_id;

				if ( isset( $image['price'] ) && strpos( $image['price'], '%' ) !== false ) {
					$image_price = ppom_get_amount_after_percentage( $product->get_price(), $image['price'] );
				}

				$image_link = isset( $image['url'] ) ? $image['url'] : '';
				$image_url  = apply_filters( 'ppom_image_input_url', wp_get_attachment_thumb_url( $image_id ), $image, $args );

				$checked_option = '';
				if ( ! empty( $default_value ) ) {
					if ( ! is_array( $default_value ) ) {
						$checked_option = checked( $default_value, $image['raw'], false );
					}
				}

				$html .= '<div class="pre_upload_image ' . esc_attr( $classes ) . '">';

				if ( ! empty( $image_link ) ) {
					$html .= '<a href="' . esc_url( $image_link ) . '"><img class="img-thumbnail" src="' . esc_url( $image_url ) . '" /></a>';
				} else {
					$html .= '<img class="img-thumbnail"  data-model-id="modalImage' . esc_attr( $image_id ) . '" src="' . esc_url( $image_url ) . '" />';
				}

				$modal_vars = array(
					'image_id'    => $image_id,
					'image_full'  => $image_full,
					'image_title' => $image_label,
				);
				ob_start();
				ppom_load_template( 'v10/image-modals.php', $modal_vars );
				$html .= ob_get_clean();

				$html .= '<div class="input_image">';
				if ( isset( $args['multiple_allowed'] ) && 'on' === $args['multiple_allowed'] ) {
					$html .= '<input type="checkbox" ';
					$html .= 'id="' . esc_attr( $option_id ) . '" ';
					$html .= 'data-price="' . esc_attr( $image_price ) . '" ';
					$html .= 'class="ppom-input" ';
					$html .= 'data-label="' . esc_attr( $image_title ) . '" ';
					$html .= 'data-title="' . esc_attr( $title ) . '" ';
					$html .= 'data-optionid="' . esc_attr( $option_id ) . '" ';
					$html .= 'data-data_name="' . esc_attr( $id ) . '" ';
					$html .= 'name="' . $args['name'] . '[]" ';
					$html .= 'value="' . esc_attr( (string) json_encode( $image ) ) . '" />';
				} else {
					$html .= '<input type="radio" ';
					$html .= 'id="' . esc_attr( $option_id ) . '" ';
					$html .= 'data-price="' . esc_attr( $image_price ) . '" ';
					$html .= 'class="ppom-input" ';
					$html .= 'data-label="' . esc_attr( $image_title ) . '" ';
					$html .= 'data-title="' . esc_attr( $title ) . '" ';
					$html .= 'data-type="' . esc_attr( $type ) . '" name="' . $args['name'] . '[]" ';
					$html .= 'data-optionid="' . esc_attr( $option_id ) . '" ';
					$html .= 'data-data_name="' . esc_attr( $id ) . '" ';
					$html .= 'value="' . esc_attr( (string) json_encode( $image ) ) . '" ' . $checked_option . ' />';
				}

				$html .= '<div class="p_u_i_name">' . $image_label . '</div>';
				$html .= '</div>';

				$html .= '</div>';
			}

			$html .= '</div>';
		} else {
			$html .= '<div class="nm-boxes-outer">';

			foreach ( $images as $image ) {
				$image_id    = isset( $image['image_id'] ) ? $image['image_id'] : 0;
				$image_title = isset( $image['raw'] ) ? stripslashes( $image['raw'] ) : '';
				$image_label = isset( $image['label'] ) ? stripslashes( $image['label'] ) : '';
				$image_price = isset( $image['price'] ) ? $image['price'] : 0;
				$option_id   = $id . '-' . $image_id;

				if ( isset( $image['price'] ) && strpos( $image['price'], '%' ) !== false ) {
					$image_price = ppom_get_amount_after_percentage( $product->get_price(), $image['price'] );
				}

				$image_link = isset( $image['url'] ) ? $image['url'] : '';
				$image_url  = apply_filters( 'ppom_image_input_url', wp_get_attachment_thumb_url( $image_id ), $image, $args );

				$checked_option = '';

				if ( ! empty( $default_value ) ) {
					if ( is_array( $default_value ) ) {
						foreach ( $default_value as $img_data ) {
							if ( isset( $image['image_id'], $img_data['image_id'] ) && $image['image_id'] == $img_data['image_id'] ) {
								$checked_option = 'checked="checked"';
							}
						}
					} else {
						$checked_option = ( $image['raw'] == $default_value ? 'checked=checked' : '' );
					}
				}

				$html .= '<label>';
				$html .= '<div class="pre_upload_image ' . esc_attr( $classes ) . '" ';
				$html .= 'title="' . esc_attr( $image_label ) . '" data-ppom-tooltip="ppom_tooltip">';
				if ( isset( $args['multiple_allowed'] ) && 'on' === $args['multiple_allowed'] ) {
					$html .= '<input type="checkbox" ';
					$html .= 'id="' . esc_attr( $option_id ) . '" ';
					$html .= 'data-price="' . esc_attr( $image_price ) . '" ';
					$html .= 'data-label="' . esc_attr( $image_title ) . '" ';
					$html .= 'class="ppom-input" ';
					$html .= 'data-title="' . esc_attr( $title ) . '" ';
					$html .= 'data-optionid="' . esc_attr( $option_id ) . '" ';
					$html .= 'data-data_name="' . esc_attr( $id ) . '" ';
					$html .= 'name="' . $args['name'] . '[]" ';
					$html .= 'value="' . esc_attr( (string) json_encode( $image ) ) . '" ' . esc_attr( $checked_option ) . ' />';
				} else {
					$html .= '<input type="radio" ';
					$html .= 'id="' . esc_attr( $option_id ) . '" ';
					$html .= 'class="ppom-input" ';
					$html .= 'data-price="' . esc_attr( $image_price ) . '" ';
					$html .= 'data-label="' . esc_attr( $image_title ) . '" ';
					$html .= 'data-title="' . esc_attr( $title ) . '" ';
					$html .= 'data-optionid="' . esc_attr( $option_id ) . '" ';
					$html .= 'data-data_name="' . esc_attr( $id ) . '" ';
					$html .= 'data-type="' . esc_attr( $type ) . '" name="' . $args['name'] . '[]" ';
					$html .= 'value="' . esc_attr( (string) json_encode( $image ) ) . '" ' . esc_attr( $checked_option ) . ' />';
				}

				if ( $image['image_id'] != '' ) {
					if ( isset( $image['url'] ) && $image['url'] != '' ) {
						$html .= '<a href="' . $image['url'] . '"><img src="' . $image_url . '" /></a>';
					} else {
						$image_url = wp_get_attachment_thumb_url( $image['image_id'] );
						$html     .= '<img data-image-tooltip="' . wp_get_attachment_url( $image['image_id'] ) . '" class="img-thumbnail ppom-zoom-' . esc_attr( $id ) . '" src="' . esc_url( $image_url ) . '" />';
					}
				} elseif ( isset( $image['url'] ) && $image['url'] != '' ) {
					$html .= '<a href="' . $image['url'] . '"><img width="150" height="150" src="' . esc_url( $image['link'] ) . '" /></a>';
				} else {
					$html .= '<img class="img-thumbnail ppom-zoom-' . esc_attr( $id ) . '" data-image-tooltip="' . esc_url( $image['link'] ) . '" src="' . esc_url( $image['link'] ) . '" />';
				}

				$html .= '</div></label>';
			}

			$html .= '<div style="clear:both"></div>';

			$html .= '</div>';
		}
		$html .= '</div>';

		return $this->applyOutputFilter( $html, $args, $default_value );
	}
}
