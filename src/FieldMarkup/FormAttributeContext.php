<?php
/**
 * Resolves field attributes and default settings (nmform_attribute_value, default_setting_value).
 *
 * @package PPOM
 */

namespace PPOM\FieldMarkup;

final class FormAttributeContext {

	/**
	 * @param string $attr
	 * @param mixed  $args
	 * @return mixed
	 */
	public function getAttributeValue( $attr, $args ) {
		if ( ! is_array( $args ) ) {
			$args = array();
		}
		$attr_value = '';
		$type       = isset( $args['type'] ) ? $args['type'] : $this->getDefaultSettingValue( 'global', 'type' );

		if ( isset( $args[ $attr ] ) ) {
			$attr_value = $args[ $attr ];
		} else {
			$attr_value = $this->getDefaultSettingValue( $type, $attr );
		}

		return apply_filters( 'nmform_attribute_value', $attr_value, $attr, $args );
	}

	/**
	 * @param string $setting_type
	 * @param string $key
	 * @param string $field_id
	 * @return mixed
	 */
	public function getDefaultSettingValue( $setting_type, $key, $field_id = '' ) {
		$defaults      = $this->getPropertyDefaults();
		$default_value = '';
		if ( isset( $defaults[ $setting_type ][ $key ] ) ) {
			$default_value = $defaults[ $setting_type ][ $key ];
		}

		return apply_filters( 'default_setting_value', $default_value, $setting_type, $key, $field_id );
	}

	/**
	 * @return array<string, array<string, mixed>>
	 */
	private function getPropertyDefaults() {
		$value = array(
			'global'   => array(
				'type'                => 'text',
				'input_wrapper_class' => 'form-group',
				'label_class'         => 'form-control-label',
			),
			'text'     => array(
				'placeholder' => '',
				'attributes'  => array(),
			),
			'date'     => array(),
			'email'    => array(),
			'number'   => array(),
			'cropper'  => array( 'classes' => array( 'ppom-cropping-size', 'form-control' ) ),
			'textarea' => array(
				'cols'        => 6,
				'rows'        => 3,
				'placeholder' => '',
			),
			'select'   => array( 'multiple' => false ),
			'checkbox' => array(
				'label_class'         => 'form-control-label',
				'check_wrapper_class' => 'form-check',
				'check_label_class'   => 'form-check-label',
				'classes'             => array( 'ppom-check-input' ),
			),
			'radio'    => array(
				'label_class'         => 'form-control-label',
				'radio_wrapper_class' => 'form-check',
				'radio_label_class'   => 'form-check-label',
				'classes'             => array( 'ppom-check-input' ),
			),
		);

		return apply_filters( 'nmform_property-defaults', $value );
	}
}
