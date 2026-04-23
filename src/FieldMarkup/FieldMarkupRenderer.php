<?php
/**
 * Field markup rendering (canonical implementation).
 *
 * The legacy `NM_Form` class name and global `NMForm()` function are deprecated aliases to
 * this class; they are defined in {@see nmInput.class.php} for backward compatibility.
 * Prefer {@see self::get_instance()} in new code.
 *
 * @package PPOM
 */

namespace PPOM\FieldMarkup;

final class FieldMarkupRenderer {

	/**
	 * @var self|null
	 */
	private static $instance = null;

	/**
	 * @var FormAttributeContext
	 */
	private $context;

	/**
	 * @var AttributeAdjustmentFilter
	 */
	private $attributeAdjustmentFilter;

	/**
	 * @var InputRendererRegistry
	 */
	private $registry;

	private function __construct() {
		$this->context                   = new FormAttributeContext();
		$this->attributeAdjustmentFilter = new AttributeAdjustmentFilter( $this->context );
		add_filter( 'nmform_attribute_value', array( $this->attributeAdjustmentFilter, 'adjust' ), 10, 3 );
		$this->registry = new InputRendererRegistry( $this->context );
	}

	/**
	 * @return self
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Preferred entry point: dispatches to the correct renderer from `type` in `$args`.
	 *
	 * @param mixed $args            Field settings; must include `type` (and the keys each renderer expects).
	 * @param mixed $default_value   Default / posted / selected value (semantics match the field type).
	 * @return string
	 */
	public function Input( $args, $default_value = '' ) {
		if ( ! is_array( $args ) ) {
			$args = array();
		}
		$type = $this->context->getAttributeValue( 'type', $args );

		return $this->registry->render( $type, $args, $default_value );
	}

	/**
	 * @deprecated Use {@see self::Input()} with the same arguments (`type` on `$args` must be a regular input: text, date, daterange, datetime-local, email, number, color).
	 *
	 * @param mixed $args
	 * @param mixed $default_value
	 * @return string
	 */
	public function Regular( $args, $default_value = '' ) {
		return $this->registry->getRegularRenderer()->render( $args, $default_value );
	}

	/**
	 * @deprecated Use {@see self::Input()} with the same arguments (`type` must be `measure` on `$args`).
	 *
	 * @param array<string, mixed> $args
	 * @param mixed                $default_value
	 * @return string
	 */
	public function Measure( $args, $default_value = '' ) {
		return $this->registry->getMeasureRenderer()->render( $args, $default_value );
	}

	/**
	 * @deprecated Use {@see self::Input()} with the same arguments (`type` must be `textarea` on `$args`).
	 *
	 * @param array<string, mixed> $args
	 * @param mixed                $default_value
	 * @return string
	 */
	public function Textarea( $args, $default_value = '' ) {
		return $this->registry->getTextareaRenderer()->render( $args, $default_value );
	}

	/**
	 * @deprecated Use {@see self::Input()} with the same arguments (`type` must be `select` on `$args`).
	 *
	 * @param array<string, mixed> $args
	 * @param mixed                $selected_value
	 * @return string
	 */
	public function Select( $args, $selected_value = '' ) {
		return $this->registry->getSelectRenderer()->render( $args, $selected_value );
	}

	/**
	 * @deprecated Use {@see self::Input()} with the same arguments (`type` must be `timezone` on `$args`).
	 *
	 * @param array<string, mixed> $args
	 * @param mixed                $selected_value
	 * @return string
	 */
	public function Timezone( $args, $selected_value = '' ) {
		return $this->registry->getTimezoneRenderer()->render( $args, $selected_value );
	}

	/**
	 * @deprecated Use {@see self::Input()} with the same arguments (`type` must be `checkbox` on `$args`).
	 *
	 * @param array<string, mixed> $args
	 * @param mixed                $checked_value
	 * @return string
	 */
	public function Checkbox( $args, $checked_value = array() ) {
		return $this->registry->getCheckboxRenderer()->render( $args, $checked_value );
	}

	/**
	 * @deprecated Use {@see self::Input()} with the same arguments (`type` must be `radio` on `$args`).
	 *
	 * @param array<string, mixed> $args
	 * @param mixed                $checked_value
	 * @return string
	 */
	public function Radio( $args, $checked_value = '' ) {
		return $this->registry->getRadioRenderer()->render( $args, $checked_value );
	}

	/**
	 * @deprecated Use {@see self::Input()} with the same arguments (`type` must be `palettes` on `$args`).
	 *
	 * @param array<string, mixed> $args
	 * @param mixed                $default_value
	 * @return string
	 */
	public function Palettes( $args, $default_value = '' ) {
		return $this->registry->getPalettesRenderer()->render( $args, $default_value );
	}

	/**
	 * @deprecated Use {@see self::Input()} with the same arguments (`type` must be `image` on `$args`).
	 *
	 * @param array<string, mixed> $args
	 * @param mixed                $default_value
	 * @return string
	 */
	public function Image( $args, $default_value = '' ) {
		return $this->registry->getImageRenderer()->render( $args, $default_value );
	}

	/**
	 * @deprecated Use {@see self::Input()} with the same arguments (`type` must be `pricematrix` on `$args`).
	 *
	 * @param array<string, mixed> $args
	 * @param mixed                $default_value
	 * @return string
	 */
	public function Pricematrix( $args, $default_value = '' ) {
		return $this->registry->getPricematrixRenderer()->render( $args, $default_value );
	}

	/**
	 * @deprecated Use {@see self::Input()} with the same arguments (`type` must be `quantities` on `$args`).
	 *
	 * @param array<string, mixed> $args
	 * @param mixed                $default_value
	 * @return string
	 */
	public function Quantities( $args, $default_value = '' ) {
		return $this->registry->getQuantitiesRenderer()->render( $args, $default_value );
	}

	/**
	 * @deprecated Use {@see self::Input()} with the same arguments (`type` must be `section` on `$args`).
	 *
	 * @param array<string, mixed> $args
	 * @param mixed                $default_value
	 * @return string
	 */
	public function Section( $args, $default_value = '' ) {
		return $this->registry->getSectionRenderer()->render( $args, $default_value );
	}

	/**
	 * @deprecated Use {@see self::Input()} with the same arguments (`type` must be `audio` on `$args`).
	 *
	 * @param array<string, mixed> $args
	 * @param mixed                $default_value
	 * @return string
	 */
	public function Audio_video( $args, $default_value = '' ) {
		return $this->registry->getAudioVideoRenderer()->render( $args, $default_value );
	}

	/**
	 * @deprecated Use {@see self::Input()} with the same arguments (`type` must be `file` on `$args`).
	 *
	 * @param array<string, mixed> $args
	 * @param mixed                $default_files
	 * @return string
	 */
	public function File( $args, $default_files = '' ) {
		return $this->registry->getFileRenderer()->render( $args, $default_files );
	}

	/**
	 * @deprecated Use {@see self::Input()} with the same arguments (`type` must be `cropper` on `$args`).
	 *
	 * @param array<string, mixed> $args
	 * @param mixed                $selected_value
	 * @return string
	 */
	public function Cropper( $args, $selected_value = '' ) {
		return $this->registry->getCropperRenderer()->render( $args, $selected_value );
	}

	/**
	 * @deprecated Prefer {@see self::Input()} when the field `type` is registered in {@see InputRendererRegistry::render()}.
	 *             Otherwise inject or extend rendering via hooks; this shim remains for backward compatibility.
	 *
	 * @param array<string, mixed> $args
	 * @param mixed                $default_value
	 * @return string
	 */
	public function Custom( $args, $default_value = '' ) {
		return $this->registry->getCustomRenderer()->render( $args, $default_value );
	}

	/**
	 * @param string $setting_type
	 * @param string $key
	 * @param string $field_id
	 * @return mixed
	 */
	public function get_default_setting_value( $setting_type, $key, $field_id = '' ) {
		return $this->context->getDefaultSettingValue( $setting_type, $key, $field_id );
	}
}
