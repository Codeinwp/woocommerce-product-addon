<?php
/**
 * Base for field-type renderers (shared context + output filter).
 *
 * @package PPOM
 */

namespace PPOM\FieldMarkup;

use PPOM\FieldMarkup\Contracts\InputRendererInterface;

abstract class AbstractInputRenderer implements InputRendererInterface {

	/**
	 * @var FormAttributeContext
	 */
	protected $context;

	/**
	 * @param FormAttributeContext $context
	 */
	public function __construct( $context ) {
		$this->context = $context;
	}

	/**
	 * @param string                 $html
	 * @param array<string, mixed>   $args
	 * @param mixed                  $value Passed to `nmforms_input_html`.
	 * @return string
	 */
	protected function applyOutputFilter( $html, $args, $value ) {
		return apply_filters( 'nmforms_input_html', $html, $args, $value );
	}

	/**
	 * @param mixed $args
	 * @return array<string, mixed>
	 */
	protected function coerceArgs( $args ) {
		return is_array( $args ) ? $args : array();
	}
}
