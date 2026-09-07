<?php
/**
 * Regression coverage for file / cropper field type + size defaulting.
 *
 * A file (or cropper) field saved with an empty `file_types` value used to
 * reach the frontend uploader as `undefined`, which crashes plupload during
 * init (see #658). Both localization paths must default `file_types` /
 * `file_size` so the crash cannot regress:
 *   - PPOM_FRONTEND_SCRIPTS::load_scripts_by_product_id() (legacy)
 *   - PPOM\Arrays\Settings::get_js_input_vars() (new)
 *
 * @package ppom-pro
 */

class Test_File_Field_Defaults_Regression extends PPOM_Test_Case {

	/**
	 * @param array<string, mixed> $overrides Field overrides.
	 * @return array<string, mixed>
	 */
	private function file_field( $overrides = array() ) {
		return array_merge(
			array(
				'type'       => 'file',
				'title'      => 'File Input',
				'data_name'  => 'file_input',
				'visibility' => 'everyone',
				'status'     => 'on',
			),
			$overrides
		);
	}

	/**
	 * @param array<string, mixed> $overrides Field overrides.
	 * @return array<string, mixed>
	 */
	private function cropper_field( $overrides = array() ) {
		return array_merge(
			array(
				'type'       => 'cropper',
				'title'      => 'Cropper Input',
				'data_name'  => 'cropper_input',
				'visibility' => 'everyone',
				'status'     => 'on',
			),
			$overrides
		);
	}

	/**
	 * Runs the legacy frontend loader for a product and returns the captured
	 * `ppom_input_vars` payload (mirrors the bulkquantity regression test).
	 *
	 * @param int $product_id Product ID.
	 * @return array<string, mixed>
	 */
	private function capture_frontend_input_vars( $product_id ) {
		PPOM_FRONTEND_SCRIPTS::init();
		$reflection = new ReflectionClass( 'PPOM_FRONTEND_SCRIPTS' );

		$get_scripts = $reflection->getMethod( 'get_scripts' );
		$get_scripts->setAccessible( true );
		PPOM_SCRIPTS::register_scripts( $get_scripts->invoke( null ) );

		$get_styles = $reflection->getMethod( 'get_styles' );
		$get_styles->setAccessible( true );
		PPOM_SCRIPTS::register_styles( $get_styles->invoke( null ) );

		$captured = null;
		$filter   = static function ( $vars ) use ( &$captured ) {
			$captured = $vars;
			return $vars;
		};
		add_filter( 'ppom_input_vars', $filter, 10, 1 );

		try {
			PPOM_FRONTEND_SCRIPTS::load_scripts_by_product_id( $product_id );
		} finally {
			remove_filter( 'ppom_input_vars', $filter, 10 );
		}

		$this->assertIsArray( $captured );
		return $captured;
	}

	/**
	 * Empty file field types/size are defaulted by the legacy loader so the
	 * uploader never receives an undefined `file_types` (#658).
	 *
	 * @return void
	 */
	public function test_frontend_loader_defaults_empty_file_field_types_and_size() {
		$product = $this->create_simple_product();
		$this->insert_ppom_meta( array( $this->file_field() ), $product->get_id() );

		$captured = $this->capture_frontend_input_vars( $product->get_id() );

		foreach ( array( 'ppom_inputs', 'field_meta' ) as $key ) {
			$this->assertSame( 'file', $captured[ $key ][0]['type'] );
			$this->assertSame( 'jpg,pdf,zip', $captured[ $key ][0]['file_types'] );
			$this->assertSame( '1mb', $captured[ $key ][0]['file_size'] );
		}
	}

	/**
	 * Cropper is image-only: empty types default to jpg,png (its builder
	 * default), never the file field's jpg,pdf,zip.
	 *
	 * @return void
	 */
	public function test_frontend_loader_defaults_empty_cropper_to_image_types() {
		$product = $this->create_simple_product();
		$this->insert_ppom_meta( array( $this->cropper_field() ), $product->get_id() );

		$captured = $this->capture_frontend_input_vars( $product->get_id() );

		$this->assertSame( 'cropper', $captured['ppom_inputs'][0]['type'] );
		$this->assertSame( 'jpg,png', $captured['ppom_inputs'][0]['file_types'] );
		$this->assertSame( '1mb', $captured['ppom_inputs'][0]['file_size'] );
	}

	/**
	 * Explicit values are preserved, not overwritten by the defaults.
	 *
	 * @return void
	 */
	public function test_frontend_loader_preserves_explicit_file_types_and_size() {
		$product = $this->create_simple_product();
		$this->insert_ppom_meta(
			array( $this->file_field( array( 'file_types' => 'png,webp', 'file_size' => '8mb' ) ) ),
			$product->get_id()
		);

		$captured = $this->capture_frontend_input_vars( $product->get_id() );

		$this->assertSame( 'png,webp', $captured['ppom_inputs'][0]['file_types'] );
		$this->assertSame( '8mb', $captured['ppom_inputs'][0]['file_size'] );
	}

	/**
	 * The new Settings::get_js_input_vars() path defaults the same way.
	 *
	 * @return void
	 */
	public function test_get_js_input_vars_defaults_file_and_cropper_types() {
		$product = $this->create_simple_product();
		$this->insert_ppom_meta(
			array( $this->file_field(), $this->cropper_field() ),
			$product->get_id()
		);

		$vars = \PPOM\Arrays\Settings::get_js_input_vars( $product );

		$this->assertIsArray( $vars );

		$by_type = array();
		foreach ( $vars['ppom_inputs'] as $field ) {
			$by_type[ $field['type'] ] = $field;
		}

		$this->assertSame( 'jpg,pdf,zip', $by_type['file']['file_types'] );
		$this->assertSame( '1mb', $by_type['file']['file_size'] );
		$this->assertSame( 'jpg,png', $by_type['cropper']['file_types'] );
		$this->assertSame( '1mb', $by_type['cropper']['file_size'] );
	}
}
