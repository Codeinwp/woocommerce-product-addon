<?php
/**
 * Callback for `nmform_attribute_value` (classes string, select name[]).
 *
 * @package PPOM
 */

namespace PPOM\FieldMarkup;

final class AttributeAdjustmentFilter {

	/**
	 * @var FormAttributeContext
	 */
	private $context;

	/**
	 * @param FormAttributeContext $context
	 */
	public function __construct( $context ) {
		$this->context = $context;
	}

	/**
	 * @param mixed  $attr_value
	 * @param string $attr
	 * @param mixed  $args
	 * @return mixed
	 */
	public function adjust( $attr_value, $attr, $args ) {
		if ( ! is_array( $args ) ) {
			$args = array();
		}

		switch ( $attr ) {
			case 'classes':
				if ( ! is_array( $attr_value ) ) {
					break;
				}
				$type = $this->context->getAttributeValue( 'type', $args );
				if ( 'image' !== $type ) {
					$attr_value[] = 'ppom-input';
				}
				$attr_value[] = $type;
				$attr_value   = implode( ' ', $attr_value );
				break;

			case 'name':
				$type     = $this->context->getAttributeValue( 'type', $args );
				$multiple = $this->context->getAttributeValue( 'multiple', $args );
				if ( 'select' === $type && $multiple ) {
					$attr_value .= '[]';
				}
				break;
		}

		return $attr_value;
	}
}
