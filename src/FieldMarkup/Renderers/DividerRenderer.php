<?php
/**
 * @package PPOM
 */

namespace PPOM\FieldMarkup\Renderers;

use PPOM\FieldMarkup\AbstractInputRenderer;
use PPOM\FieldMarkup\FieldChrome;

/**
 * Renders divider / section separator markup (aligned with the frontend divider template).
 */
final class DividerRenderer extends AbstractInputRenderer {

	/**
	 * @param array<string, mixed> $args            Field meta-style arguments (divider_styles, colors, etc.).
	 * @param mixed                $default_value Unused; kept for renderer interface parity.
	 * @return string
	 */
	public function render( $args, $default_value ) {
		$args = $this->coerceArgs( $args );

		$id    = $this->context->getAttributeValue( 'id', $args );
		$label = $this->context->getAttributeValue( 'label', $args );

		$divider_styles = isset( $args['divider_styles'] ) ? (string) $args['divider_styles'] : '';
		$style1_border  = isset( $args['style1_border'] ) ? (string) $args['style1_border'] : '';
		$divider_height = isset( $args['divider_height'] ) ? (string) $args['divider_height'] : '';
		$divider_color  = isset( $args['divider_color'] ) ? (string) $args['divider_color'] : '#808080';
		$txtsize        = isset( $args['divider_txtsize'] ) ? (string) $args['divider_txtsize'] : '14px';
		$txtclr         = isset( $args['divider_txtclr'] ) ? (string) $args['divider_txtclr'] : '#2f2121';

		$custom_css  = '';
		$custom_css .= '.' . $id . ' .ppom-divider-txt {
                    font-size: ' . $txtsize . ' !important;
                    color: ' . $txtclr . ' !important;
                }';

		if ( 'style1' === $divider_styles ) {
			$custom_css .= '.' . $id . ' .ppom-divider-' . $style1_border . ' {
                        border-top: ' . $divider_height . ' ' . $style1_border . ' ' . $divider_color . ' !important;
                        background-color: transparent !important;
                    }';

			$custom_css .= '.' . $id . ' .ppom-divider-line-clr:before,' .
					'.' . $id . ' .ppom-divider-line-clr:after {
                        border-top: ' . $divider_height . ' ' . $style1_border . ' ' . $divider_color . ' !important;
                    }';
		}

		if ( 'style2' === $divider_styles ) {
			$custom_css .= '.' . $id . ' .ppom-divider-gradient {
                        background: ' . $divider_color . ';
                        height: ' . $divider_height . ';
                        line-height: ' . $divider_height . ' !important;
                    }';

			$custom_css .= '.' . $id . ' .ppom-divider-gradient:before {
                        background: linear-gradient(to right, white, ' . $divider_color . ');
                    }';
			$custom_css .= '.' . $id . ' .ppom-divider-gradient:after {
                        background: linear-gradient(to left, white, ' . $divider_color . ');
                    }';
		}

		if ( 'style3' === $divider_styles ) {
			$custom_css .= '.' . $id . ' .ppom-divider-donotcross {
                        background: ' . $divider_color . ';
                        height: ' . $divider_height . ';
                        line-height: ' . $divider_height . ' !important;
                    }';
		}

		if ( 'style4' === $divider_styles ) {
			$custom_css .= '.' . $id . ' .ppom-divider-easy-shadow span:first-child {
                        background-image: -webkit-gradient(linear, 0 0, 0 100%, from(transparent), to(' . $divider_color . '));
                    	background-image: -webkit-linear-gradient(180deg, transparent, ' . $divider_color . ');
                    	background-image: -moz-linear-gradient(180deg, transparent, ' . $divider_color . ');
                    	background-image: -o-linear-gradient(180deg, transparent, ' . $divider_color . ');
                    	background-image: linear-gradient(90deg, transparent, ' . $divider_color . ');
                    }';
			$custom_css .= '.' . $id . ' .ppom-divider-easy-shadow span:last-child {
                        background-image: -webkit-gradient(linear, 0 0, 0 100%, from(' . $divider_color . '), to(transparent));
                        background-image: -webkit-linear-gradient(180deg, ' . $divider_color . ', transparent);
                    	background-image: -moz-linear-gradient(180deg, ' . $divider_color . ', transparent);
                    	background-image: -o-linear-gradient(180deg, ' . $divider_color . ', transparent);
                    	background-image: linear-gradient(90deg, ' . $divider_color . ', transparent);
                    }';
		}

		if ( 'style5' === $divider_styles ) {
			$custom_css .= '.' . $id . ' .ppom-divider-fancy-line span {
                        background-color: ' . $divider_color . ';
                        height: ' . $divider_height . ';
                        line-height: ' . $divider_height . ' !important;
                    }';
		}

		$inner_classes = apply_filters( 'ppom_input_wrapper_class', 'form-group', $args );

		$input_wrapper_class = FieldChrome::inputWrapperClass( $this->context, $id, $args );
		$html                = '<div class="' . $input_wrapper_class . '">';

		$html .= '<style>';
		$html .= esc_attr( $custom_css );
		$html .= '</style>';

		$html .= '<div class="' . esc_attr( $inner_classes ) . '">';

		if ( 'style1' === $divider_styles ) {
			if ( $label ) {
				$html .= '<h2 class="ppom-divider-with-txt ppom-divider-line ppom-divider-line-clr ppom-divider-txt">' . ppom_esc_html( $label ) . '</h2>';
			} else {
				$html .= '<hr class="ppom-divider-' . esc_attr( $style1_border ) . '">';
			}
		}

		if ( 'style2' === $divider_styles ) {
			$html .= '<h2 class="ppom-divider-with-txt ppom-divider-gradient ppom-divider-txt">' . ppom_esc_html( $label ) . '</h2>';
		}

		if ( 'style3' === $divider_styles ) {
			$html .= '<h2 class="ppom-divider-with-txt ppom-divider-donotcross ppom-divider-txt">' . ppom_esc_html( $label ) . '</h2>';
		}

		if ( 'style4' === $divider_styles ) {
			$html .= '<div class="ppom-divider-easy-shadow">';
			$html .= '<span></span>';
			$html .= '<span class="ppom-divider-txt">' . ppom_esc_html( $label ) . '</span>';
			$html .= '<span></span>';
			$html .= '</div>';
		}

		if ( 'style5' === $divider_styles ) {
			$html .= '<h1 class="ppom-divider-fancy-heading ppom-divider-txt">' . ppom_esc_html( $label ) . '</h1>';
			$html .= '<div class="ppom-divider-fancy-line">';
			$html .= '<span></span>';
			$html .= '</div>';
		}

		$html .= '</div>';
		$html .= '</div>';

		return $this->applyOutputFilter( $html, $args, $default_value );
	}
}
