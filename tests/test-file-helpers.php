<?php
/**
 * Class Test_File_Helpers
 *
 * @package ppom-pro
 */

require_once __DIR__ . '/class-ppom-test-case.php';

class Test_File_Helpers extends PPOM_Test_Case {

	/**
	 * Ensure base uploads move into the confirmed order directory with product prefix.
	 *
	 * @return void
	 */
	public function testGetFileDownloadUrlMovesBaseFileToConfirmedDirectoryWithProductPrefix() {
		$order_id   = 123;
		$product_id = 55;
		$file_name  = 'sample.txt';
		$base_dir   = ppom_get_dir_path();

		$this->assertNotEmpty( $base_dir );

		$confirmed_dir = ppom_get_dir_path( 'confirmed/' . $order_id );
		$base_path     = $base_dir . $file_name;
		$confirmed     = $confirmed_dir . $product_id . '-' . $file_name;

		file_put_contents( $base_path, 'sample data' );

		$url = ppom_get_file_download_url( $file_name, $order_id, $product_id );

		$this->assertFileDoesNotExist( $base_path );
		$this->assertFileExists( $confirmed );
		$this->assertSame(
			ppom_get_dir_url() . 'confirmed/' . $order_id . '/' . $product_id . '-' . $file_name,
			$url
		);

		unlink( $confirmed );
	}

	/**
	 * Ensure viewport settings use the first configured size and sensible defaults.
	 *
	 * @return void
	 */
	public function testGetViewportSettingsUsesConfiguredDimensionsAndDefaults() {
		$configured = ppom_get_viewport_settings(
			array(
				'viewport_type' => 'circle',
				'options'       => array(
					array(
						'width'  => '120',
						'height' => '80',
					),
					array(
						'width'  => '200',
						'height' => '150',
					),
				),
			)
		);

		$defaults = ppom_get_viewport_settings( array() );

		$this->assertSame(
			array(
				'width'  => '120',
				'height' => '80',
				'type'   => 'circle',
			),
			$configured
		);
		$this->assertSame(
			array(
				'width'  => 300,
				'height' => 300,
				'type'   => 'square',
			),
			$defaults
		);
	}
}
