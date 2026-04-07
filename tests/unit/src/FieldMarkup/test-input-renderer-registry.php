<?php
/**
 * Unit tests for PPOM\FieldMarkup\InputRendererRegistry routing.
 *
 * @package ppom-pro
 */

use PPOM\FieldMarkup\FormAttributeContext;
use PPOM\FieldMarkup\InputRendererRegistry;
use PPOM\FieldMarkup\Renderers\RegularInputRenderer;
use PPOM\FieldMarkup\Renderers\SelectRenderer;
use PPOM\FieldMarkup\Renderers\TextareaRenderer;

/**
 * @covers \PPOM\FieldMarkup\InputRendererRegistry
 */
class Test_Input_Renderer_Registry extends WP_UnitTestCase {

	/**
	 * @var InputRendererRegistry
	 */
	private $registry;

	/**
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();
		$this->registry = new InputRendererRegistry( new FormAttributeContext() );
	}

	/**
	 * @return void
	 */
	public function test_render_unknown_type_returns_empty_string() {
		$this->assertSame( '', $this->registry->render( 'nonexistent_type_xyz', array(), null ) );
	}

	/**
	 * @return void
	 */
	public function test_get_regular_renderer_instance() {
		$this->assertInstanceOf( RegularInputRenderer::class, $this->registry->getRegularRenderer() );
	}

	/**
	 * @return void
	 */
	public function test_get_select_renderer_instance() {
		$this->assertInstanceOf( SelectRenderer::class, $this->registry->getSelectRenderer() );
	}

	/**
	 * @return void
	 */
	public function test_get_textarea_renderer_instance() {
		$this->assertInstanceOf( TextareaRenderer::class, $this->registry->getTextareaRenderer() );
	}

	/**
	 * @return void
	 */
	public function test_render_text_delegates_to_regular_renderer() {
		$output = $this->registry->render(
			'text',
			array(
				'type'      => 'text',
				'id'        => 'note',
				'data_name' => 'note',
				'title'     => 'Note',
			),
			'hello'
		);

		$this->assertIsString( $output );
		$this->assertStringContainsString( 'hello', $output );
	}
}
