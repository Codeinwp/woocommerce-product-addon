<?php
/**
 * Strategy for rendering a single PPOM field type to HTML.
 *
 * @package PPOM
 */

namespace PPOM\FieldMarkup\Contracts;

interface InputRendererInterface {

	/**
	 * @param array<string, mixed> $args Field meta / render arguments.
	 * @param mixed                $value Default, selected, or structured value (type-specific).
	 * @return string
	 */
	public function render( $args, $value );
}
