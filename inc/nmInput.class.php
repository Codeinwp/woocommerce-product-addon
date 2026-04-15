<?php
/**
 * Legacy bootstrap for field markup.
 *
 * The `NM_Form` class alias and the global `NMForm()` function are deprecated. New code
 * should use {@see \PPOM\FieldMarkup\FieldMarkupRenderer::get_instance()} directly.
 *
 * Implementation lives in {@see \PPOM\FieldMarkup\FieldMarkupRenderer}, exposed for backward
 * compatibility as the legacy `NM_Form` singleton via {@see NMForm()}.
 *
 * @package PPOM
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'ECHOABLE' ) ) {
	define( 'ECHOABLE', false );
}

class_alias( \PPOM\FieldMarkup\FieldMarkupRenderer::class, 'NM_Form' );

/**
 * Singleton accessor for field markup rendering.
 *
 * @deprecated Use {@see \PPOM\FieldMarkup\FieldMarkupRenderer::get_instance()} instead.
 *
 * @return \PPOM\FieldMarkup\FieldMarkupRenderer
 */
function NMForm() {
	return \PPOM\FieldMarkup\FieldMarkupRenderer::get_instance();
}
