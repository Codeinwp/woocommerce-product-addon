<?php
/**
 * Upload-constraint notice parity across the two PPOM input renderers.
 *
 * PPOM picks a renderer with the `ppom_enable_legacy_inputs_rendering` option:
 * the legacy renderers under `src/FieldMarkup/Renderers/`, or the default
 * templates under `templates/frontend/inputs/`. Both must tell the shopper
 * which formats are accepted and how large a file can be, before a file is
 * chosen.
 *
 * @package ppom-pro
 */

require_once dirname( __DIR__, 2 ) . '/class-ppom-test-case.php';

use PPOM\FieldMarkup\FieldMarkupRenderer;
use PPOM\WooCommerce\Product\ProductHandler;

/**
 * @covers \PPOM\FieldMarkup\Renderers\FileRenderer
 * @covers \PPOM\FieldMarkup\Renderers\CropperRenderer
 */
class Test_Upload_Notice_Parity extends PPOM_Test_Case {

	/**
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->reset_field_markup_renderer();
		NMForm();
	}

	/**
	 * @return void
	 */
	public function tearDown(): void {
		$this->reset_field_markup_renderer();

		parent::tearDown();
	}

	/**
	 * Clears the FieldMarkupRenderer singleton so no suite inherits this one.
	 *
	 * @return void
	 */
	private function reset_field_markup_renderer() {
		$instance = new ReflectionProperty( FieldMarkupRenderer::class, 'instance' );
		$instance->setAccessible( true );
		$instance->setValue( null, null );
	}

	/**
	 * Renders a product's fields through the default template path.
	 *
	 * @param int $product_id Product id.
	 *
	 * @return string
	 */
	private function render_modern( $product_id ) {
		ob_start();
		ProductHandler::template_base_inputs_rendering( $product_id );

		return (string) ob_get_clean();
	}

	/**
	 * Renders a product's fields through the legacy renderer path.
	 *
	 * @param int $product_id Product id.
	 *
	 * @return string
	 */
	private function render_legacy( $product_id ) {
		ob_start();
		ProductHandler::show_fields_on_product( $product_id );

		return (string) ob_get_clean();
	}

	/**
	 * A file field states its accepted formats and size limit in both renderers.
	 *
	 * @return void
	 */
	public function test_file_field_states_accepted_formats_and_max_size_in_both_renderers() {
		$product = $this->create_simple_product();
		$field   = $this->build_file_field(
			'artwork',
			'Artwork',
			array(
				'file_types'          => 'jpg,png,pdf',
				'file_size'           => '5mb',
				// The legacy template reads these two keys directly.
				'button_label_select' => '',
				'files_allowed'       => '',
			)
		);
		$this->insert_ppom_meta( array( $field ), $product->get_id() );

		$rendered = array(
			'default' => $this->render_modern( $product->get_id() ),
			'legacy'  => $this->render_legacy( $product->get_id() ),
		);

		foreach ( $rendered as $mode => $html ) {
			$this->assertStringContainsString( 'Accepted formats: JPG,PNG,PDF.', $html, "accepted formats missing in {$mode} renderer" );
			$this->assertStringContainsString( 'Max size: 5MB', $html, "max size missing in {$mode} renderer" );
		}
	}

	/**
	 * A cropper field states its accepted formats and size limit in both renderers.
	 *
	 * The notice must name the formats the uploader really enforces, which come
	 * from the field meta.
	 *
	 * @return void
	 */
	public function test_cropper_field_states_accepted_formats_and_max_size_in_both_renderers() {
		$product = $this->create_simple_product();
		$field   = $this->build_cropper_field(
			'photo',
			'Photo',
			array(),
			array(
				'file_types'          => 'jpg,png',
				'file_size'           => '2mb',
				// The legacy template reads these two keys directly.
				'button_label_select' => '',
				'files_allowed'       => '',
			)
		);
		$this->insert_ppom_meta( array( $field ), $product->get_id() );

		$rendered = array(
			'default' => $this->render_modern( $product->get_id() ),
			'legacy'  => $this->render_legacy( $product->get_id() ),
		);

		foreach ( $rendered as $mode => $html ) {
			$this->assertStringContainsString( 'Accepted formats: JPG,PNG.', $html, "accepted formats missing in {$mode} renderer" );
			$this->assertStringContainsString( 'Max size: 2MB', $html, "max size missing in {$mode} renderer" );
		}
	}

	/**
	 * Blank constraints produce no notice in either renderer.
	 *
	 * The legacy template substitutes its own upload defaults before the renderer
	 * sees the field. Those defaults must not reach the notice, because they are
	 * not what the uploader enforces.
	 *
	 * @return void
	 */
	public function test_blank_file_constraints_produce_no_notice_in_either_renderer() {
		$product = $this->create_simple_product();
		$field   = $this->build_file_field(
			'artwork',
			'Artwork',
			array(
				'file_types'          => '',
				'file_size'           => '',
				'button_label_select' => '',
				'files_allowed'       => '',
			)
		);
		$this->insert_ppom_meta( array( $field ), $product->get_id() );

		$rendered = array(
			'default' => $this->render_modern( $product->get_id() ),
			'legacy'  => $this->render_legacy( $product->get_id() ),
		);

		foreach ( $rendered as $mode => $html ) {
			$this->assertStringNotContainsString( 'Accepted formats:', $html, "unexpected formats notice in {$mode} renderer" );
			$this->assertStringNotContainsString( 'Max size:', $html, "unexpected size notice in {$mode} renderer" );
		}
	}

	/**
	 * Blank cropper constraints produce no notice in either renderer.
	 *
	 * @return void
	 */
	public function test_blank_cropper_constraints_produce_no_notice_in_either_renderer() {
		$product = $this->create_simple_product();
		$field   = $this->build_cropper_field(
			'photo',
			'Photo',
			array(),
			array(
				'file_types'          => '',
				'file_size'           => '',
				'button_label_select' => '',
				'files_allowed'       => '',
			)
		);
		$this->insert_ppom_meta( array( $field ), $product->get_id() );

		$rendered = array(
			'default' => $this->render_modern( $product->get_id() ),
			'legacy'  => $this->render_legacy( $product->get_id() ),
		);

		foreach ( $rendered as $mode => $html ) {
			$this->assertStringNotContainsString( 'Accepted formats:', $html, "unexpected formats notice in {$mode} renderer" );
			$this->assertStringNotContainsString( 'Max size:', $html, "unexpected size notice in {$mode} renderer" );
		}
	}

	/**
	 * A size with no formats prints only the size sentence, in both renderers.
	 *
	 * @return void
	 */
	public function test_size_without_formats_prints_only_the_size_in_either_renderer() {
		$product = $this->create_simple_product();
		$field   = $this->build_file_field(
			'artwork',
			'Artwork',
			array(
				'file_types'          => '',
				'file_size'           => '4mb',
				'button_label_select' => '',
				'files_allowed'       => '',
			)
		);
		$this->insert_ppom_meta( array( $field ), $product->get_id() );

		$rendered = array(
			'default' => $this->render_modern( $product->get_id() ),
			'legacy'  => $this->render_legacy( $product->get_id() ),
		);

		foreach ( $rendered as $mode => $html ) {
			$this->assertStringContainsString( 'Max size: 4MB', $html, "max size missing in {$mode} renderer" );
			$this->assertStringNotContainsString( 'Accepted formats:', $html, "unexpected formats notice in {$mode} renderer" );
		}
	}

	/**
	 * The notice honours the field meta filters in both renderers.
	 *
	 * `PPOM_InputManager::get_meta_value()` runs every value through
	 * `ppom_field_meta_value`, so an integration that rewrites `file_types`
	 * must change what both renderers print.
	 *
	 * This asserts parity between the two renderers, not that the notice agrees
	 * with what the uploader enforces. The uploader config in
	 * `classes/frontend-scripts.class.php` reads the saved field directly and
	 * skips these filters, so a filtered notice can name formats the uploader
	 * rejects. That gap predates this change and affects the default renderer
	 * too, so it belongs in its own fix.
	 *
	 * @return void
	 */
	public function test_field_meta_filter_reaches_the_notice_in_both_renderers() {
		$product = $this->create_simple_product();
		$field   = $this->build_file_field(
			'artwork',
			'Artwork',
			array(
				'file_types'          => 'jpg,png,pdf',
				'file_size'           => '5mb',
				'button_label_select' => '',
				'files_allowed'       => '',
			)
		);
		$this->insert_ppom_meta( array( $field ), $product->get_id() );

		$filter = static function ( $value, $key ) {
			return 'file_types' === $key ? 'svg' : $value;
		};
		add_filter( 'ppom_field_meta_value', $filter, 10, 2 );

		$rendered = array(
			'default' => $this->render_modern( $product->get_id() ),
			'legacy'  => $this->render_legacy( $product->get_id() ),
		);

		remove_filter( 'ppom_field_meta_value', $filter, 10 );

		foreach ( $rendered as $mode => $html ) {
			$this->assertStringContainsString( 'Accepted formats: SVG.', $html, "filtered formats missing in {$mode} renderer" );
			$this->assertStringNotContainsString( 'JPG,PNG,PDF', $html, "unfiltered formats leaked into {$mode} renderer" );
		}
	}
}
