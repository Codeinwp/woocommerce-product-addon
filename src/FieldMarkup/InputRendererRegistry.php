<?php
/**
 * Maps field type string to renderer (simple factory).
 *
 * @package PPOM
 */

namespace PPOM\FieldMarkup;

use PPOM\FieldMarkup\Renderers\AudioVideoRenderer;
use PPOM\FieldMarkup\Renderers\CheckboxRenderer;
use PPOM\FieldMarkup\Renderers\CropperRenderer;
use PPOM\FieldMarkup\Renderers\CustomRenderer;
use PPOM\FieldMarkup\Renderers\FileRenderer;
use PPOM\FieldMarkup\Renderers\ImageRenderer;
use PPOM\FieldMarkup\Renderers\MeasureInputRenderer;
use PPOM\FieldMarkup\Renderers\PalettesRenderer;
use PPOM\FieldMarkup\Renderers\PricematrixRenderer;
use PPOM\FieldMarkup\Renderers\QuantitiesRenderer;
use PPOM\FieldMarkup\Renderers\RadioRenderer;
use PPOM\FieldMarkup\Renderers\RegularInputRenderer;
use PPOM\FieldMarkup\Renderers\SectionRenderer;
use PPOM\FieldMarkup\Renderers\SelectRenderer;
use PPOM\FieldMarkup\Renderers\TextareaRenderer;
use PPOM\FieldMarkup\Renderers\TimezoneRenderer;

final class InputRendererRegistry {

	/**
	 * @var RegularInputRenderer
	 */
	private $regular;

	/**
	 * @var MeasureInputRenderer
	 */
	private $measure;

	/**
	 * @var TextareaRenderer
	 */
	private $textarea;

	/**
	 * @var SelectRenderer
	 */
	private $select;

	/**
	 * @var TimezoneRenderer
	 */
	private $timezone;

	/**
	 * @var CheckboxRenderer
	 */
	private $checkbox;

	/**
	 * @var RadioRenderer
	 */
	private $radio;

	/**
	 * @var PalettesRenderer
	 */
	private $palettes;

	/**
	 * @var ImageRenderer
	 */
	private $image;

	/**
	 * @var PricematrixRenderer
	 */
	private $pricematrix;

	/**
	 * @var QuantitiesRenderer
	 */
	private $quantities;

	/**
	 * @var SectionRenderer
	 */
	private $section;

	/**
	 * @var AudioVideoRenderer
	 */
	private $audio;

	/**
	 * @var FileRenderer
	 */
	private $file;

	/**
	 * @var CropperRenderer
	 */
	private $cropper;

	/**
	 * @var CustomRenderer
	 */
	private $custom;

	/**
	 * @param FormAttributeContext $context
	 */
	public function __construct( $context ) {
		$this->regular     = new RegularInputRenderer( $context );
		$this->measure     = new MeasureInputRenderer( $context );
		$this->textarea    = new TextareaRenderer( $context );
		$this->select      = new SelectRenderer( $context );
		$this->timezone    = new TimezoneRenderer( $context );
		$this->checkbox    = new CheckboxRenderer( $context );
		$this->radio       = new RadioRenderer( $context );
		$this->palettes    = new PalettesRenderer( $context );
		$this->image       = new ImageRenderer( $context );
		$this->pricematrix = new PricematrixRenderer( $context );
		$this->quantities  = new QuantitiesRenderer( $context );
		$this->section     = new SectionRenderer( $context );
		$this->audio       = new AudioVideoRenderer( $context );
		$this->file        = new FileRenderer( $context );
		$this->cropper     = new CropperRenderer( $context );
		$this->custom      = new CustomRenderer( $context );
	}

	/**
	 * @param string $type
	 * @param mixed  $args
	 * @param mixed  $value
	 * @return string
	 */
	public function render( $type, $args, $value ) {
		if ( ! is_array( $args ) ) {
			$args = array();
		}

		switch ( $type ) {
			case 'text':
			case 'date':
			case 'daterange':
			case 'datetime-local':
			case 'email':
			case 'number':
			case 'color':
				return $this->regular->render( $args, $value );

			case 'measure':
				return $this->measure->render( $args, $value );

			case 'textarea':
				return $this->textarea->render( $args, $value );

			case 'select':
				return $this->select->render( $args, $value );

			case 'timezone':
				return $this->timezone->render( $args, $value );

			case 'checkbox':
				return $this->checkbox->render( $args, $value );

			case 'radio':
				return $this->radio->render( $args, $value );

			case 'palettes':
				return $this->palettes->render( $args, $value );

			case 'image':
				return $this->image->render( $args, $value );

			case 'pricematrix':
				return $this->pricematrix->render( $args, $value );

			case 'quantities':
				return $this->quantities->render( $args, $value );

			case 'section':
				return $this->section->render( $args, $value );

			case 'audio':
				return $this->audio->render( $args, $value );

			case 'file':
				return $this->file->render( $args, $value );

			case 'cropper':
				return $this->cropper->render( $args, $value );

			default:
				return '';
		}
	}

	/**
	 * @return RegularInputRenderer
	 */
	public function getRegularRenderer() {
		return $this->regular;
	}

	/**
	 * @return MeasureInputRenderer
	 */
	public function getMeasureRenderer() {
		return $this->measure;
	}

	/**
	 * @return TextareaRenderer
	 */
	public function getTextareaRenderer() {
		return $this->textarea;
	}

	/**
	 * @return SelectRenderer
	 */
	public function getSelectRenderer() {
		return $this->select;
	}

	/**
	 * @return TimezoneRenderer
	 */
	public function getTimezoneRenderer() {
		return $this->timezone;
	}

	/**
	 * @return CheckboxRenderer
	 */
	public function getCheckboxRenderer() {
		return $this->checkbox;
	}

	/**
	 * @return RadioRenderer
	 */
	public function getRadioRenderer() {
		return $this->radio;
	}

	/**
	 * @return PalettesRenderer
	 */
	public function getPalettesRenderer() {
		return $this->palettes;
	}

	/**
	 * @return ImageRenderer
	 */
	public function getImageRenderer() {
		return $this->image;
	}

	/**
	 * @return PricematrixRenderer
	 */
	public function getPricematrixRenderer() {
		return $this->pricematrix;
	}

	/**
	 * @return QuantitiesRenderer
	 */
	public function getQuantitiesRenderer() {
		return $this->quantities;
	}

	/**
	 * @return SectionRenderer
	 */
	public function getSectionRenderer() {
		return $this->section;
	}

	/**
	 * @return AudioVideoRenderer
	 */
	public function getAudioVideoRenderer() {
		return $this->audio;
	}

	/**
	 * @return FileRenderer
	 */
	public function getFileRenderer() {
		return $this->file;
	}

	/**
	 * @return CropperRenderer
	 */
	public function getCropperRenderer() {
		return $this->cropper;
	}

	/**
	 * @return CustomRenderer
	 */
	public function getCustomRenderer() {
		return $this->custom;
	}
}
