<?php
/**
 * Tests for PPOM\FieldMarkup\FormAttributeContext.
 *
 * @package ppom-pro
 */

use PPOM\FieldMarkup\FormAttributeContext;

/**
 * @covers \PPOM\FieldMarkup\FormAttributeContext
 */
class Test_Form_Attribute_Context extends WP_UnitTestCase {

	/**
	 * @var FormAttributeContext
	 */
	private $context;

	/**
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();
		$this->context = new FormAttributeContext();
	}

	/**
	 * @return void
	 */
	public function test_get_attribute_value_uses_explicit_args() {
		$args = array(
			'type'        => 'text',
			'placeholder' => 'Explicit ph',
		);

		$this->assertSame( 'text', $this->context->getAttributeValue( 'type', $args ) );
		$this->assertSame( 'Explicit ph', $this->context->getAttributeValue( 'placeholder', $args ) );
	}

	/**
	 * @return void
	 */
	public function test_get_attribute_value_falls_back_to_type_defaults() {
		$args = array( 'type' => 'textarea' );

		$this->assertSame( 6, $this->context->getAttributeValue( 'cols', $args ) );
		$this->assertSame( 3, $this->context->getAttributeValue( 'rows', $args ) );
		$this->assertSame( '', $this->context->getAttributeValue( 'placeholder', $args ) );
	}

	/**
	 * When `type` is not in $args, the resolver uses global default `text` internally, but the
	 * `type` attribute itself is read from per-type defaults (`text` has no `type` key), so the
	 * resolved value is empty unless passed explicitly.
	 *
	 * @return void
	 */
	public function test_get_attribute_value_type_empty_when_not_in_args() {
		$this->assertSame( '', $this->context->getAttributeValue( 'type', array() ) );
	}

	/**
	 * With empty $args, `type` resolves to global default `text`; attributes fall back to the
	 * `text` type defaults only (not the `global` block), so `placeholder` is the text default.
	 *
	 * @return void
	 */
	public function test_get_attribute_value_uses_type_defaults_when_args_empty() {
		$this->assertSame( '', $this->context->getAttributeValue( 'placeholder', array() ) );
	}

	/**
	 * @return void
	 */
	public function test_get_default_setting_value_global_keys() {
		$this->assertSame( 'text', $this->context->getDefaultSettingValue( 'global', 'type' ) );
		$this->assertSame( 'form-group', $this->context->getDefaultSettingValue( 'global', 'input_wrapper_class' ) );
		$this->assertSame( 'form-control-label', $this->context->getDefaultSettingValue( 'global', 'label_class' ) );
	}

	/**
	 * @return void
	 */
	public function test_get_attribute_value_normalizes_non_array_args() {
		$this->assertSame( '', $this->context->getAttributeValue( 'placeholder', 'not-an-array' ) );
	}
}
