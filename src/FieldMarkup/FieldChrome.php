<?php
/**
 * Shared wrapper / label class resolution for field markup.
 *
 * @package PPOM
 */

namespace PPOM\FieldMarkup;

final class FieldChrome {

	/**
	 * @param FormAttributeContext   $context
	 * @param string                 $id
	 * @param array<string, mixed>   $args
	 * @return string
	 */
	public static function inputWrapperClass( $context, $id, $args ) {
		$class = $context->getDefaultSettingValue( 'global', 'input_wrapper_class', $id );

		return apply_filters( 'ppom_input_wrapper_class', $class, $args );
	}
}
