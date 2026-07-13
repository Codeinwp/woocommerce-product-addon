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

	/**
	 * Ensure download URLs resolve without moving files that are already confirmed.
	 *
	 * @return void
	 */
	public function testGetFileDownloadUrlReturnsExistingConfirmedUrl() {
		$order_id   = 456;
		$product_id = 88;
		$file_name  = 'existing.txt';
		$confirmed  = ppom_get_dir_path( 'confirmed/' . $order_id ) . $product_id . '-' . $file_name;

		file_put_contents( $confirmed, 'already confirmed' );

		$url = ppom_get_file_download_url( $file_name, $order_id, $product_id );

		$this->assertFileExists( $confirmed );
		$this->assertSame(
			ppom_get_dir_url() . 'confirmed/' . $order_id . '/' . $product_id . '-' . $file_name,
			$url
		);

		unlink( $confirmed );
	}

	/**
	 * Ensure a confirmed file wins over a base pool file, without moving the pool file.
	 *
	 * The base file may belong to another order (e.g. an Order Again cart
	 * awaiting checkout), so resolving an already-confirmed order's URL must
	 * not consume it.
	 *
	 * @return void
	 */
	public function testGetFileDownloadUrlPrefersConfirmedFileAndLeavesBaseFileAlone() {
		$order_id   = 789;
		$product_id = 66;
		$file_name  = 'shared.txt';
		$base_path  = ppom_get_dir_path() . $file_name;
		$confirmed  = ppom_get_dir_path( 'confirmed/' . $order_id ) . $product_id . '-' . $file_name;

		file_put_contents( $base_path, 'pending re-order upload' );
		file_put_contents( $confirmed, 'already confirmed' );

		$url = ppom_get_file_download_url( $file_name, $order_id, $product_id );

		$this->assertFileExists( $base_path );
		$this->assertFileExists( $confirmed );
		$this->assertSame(
			ppom_get_dir_url() . 'confirmed/' . $order_id . '/' . $product_id . '-' . $file_name,
			$url
		);

		unlink( $base_path );
		unlink( $confirmed );
	}

	/**
	 * Runs a callable while recording every PHP warning/notice it raises.
	 *
	 * wp_get_image_mime() swallows converted warning-exceptions in its own
	 * try/catch, so PHPUnit's convertWarningsToExceptions cannot see them —
	 * a recording handler observes them the way a real page render does.
	 *
	 * @param callable $callback Code under test.
	 *
	 * @return array{0: mixed, 1: array<int, string>} Callback result and raised warnings.
	 */
	private function captureWarnings( $callback ) {
		$warnings = array();
		set_error_handler(
			function ( $errno, $errstr ) use ( &$warnings ) {
				$warnings[] = $errstr;
				return true;
			},
			E_WARNING | E_NOTICE
		);

		try {
			$result = $callback();
		} finally {
			restore_error_handler();
		}

		return array( $result, $warnings );
	}

	/**
	 * Ensure probing a missing file reports "not an image" without PHP warnings.
	 *
	 * @return void
	 */
	public function testIsFileImageReturnsFalseQuietlyForMissingPath() {
		list( $result, $warnings ) = $this->captureWarnings(
			function () {
				return ppom_is_file_image( ppom_get_dir_path() . 'no-such-file.txt' );
			}
		);

		$this->assertFalse( (bool) $result );
		$this->assertSame( array(), $warnings );
	}

	/**
	 * Ensure order meta HTML for a non-image upload falls back to the generic
	 * file icon without probing a thumbnail URL over HTTP (no PHP warnings).
	 *
	 * @return void
	 */
	public function testGenerateHtmlForFilesUsesGenericIconForNonImageUpload() {
		$order_id   = 654;
		$product_id = 77;
		$file_name  = 'doc.txt';
		$confirmed  = ppom_get_dir_path( 'confirmed/' . $order_id ) . $product_id . '-' . $file_name;

		file_put_contents( $confirmed, 'text data' );

		$item = new class( $order_id, $product_id ) {
			/**
			 * @var int
			 */
			private $order_id;

			/**
			 * @var int
			 */
			private $product_id;

			/**
			 * @param int $order_id   Order ID.
			 * @param int $product_id Product ID.
			 */
			public function __construct( $order_id, $product_id ) {
				$this->order_id   = $order_id;
				$this->product_id = $product_id;
			}

			/**
			 * @return int
			 */
			public function get_order_id() {
				return $this->order_id;
			}

			/**
			 * @return int
			 */
			public function get_product_id() {
				return $this->product_id;
			}
		};

		list( $html, $warnings ) = $this->captureWarnings(
			function () use ( $file_name, $item ) {
				return \PPOM\Support\Helpers::generate_html_for_files( $file_name, 'file', $item );
			}
		);

		$this->assertSame( array(), $warnings );
		$this->assertStringContainsString( 'images/file.png', $html );
		$this->assertStringContainsString( 'confirmed/' . $order_id . '/' . $product_id . '-' . $file_name, $html );

		unlink( $confirmed );
	}

	/**
	 * Ensure order meta HTML for an image upload keeps its real thumbnail
	 * (and does not silently degrade to the generic file icon).
	 *
	 * @return void
	 */
	public function testGenerateHtmlForFilesUsesRealThumbnailForImageUpload() {
		$order_id   = 655;
		$product_id = 78;
		$file_name  = 'photo-' . wp_generate_password( 6, false ) . '.png';
		$thumb_path = ppom_get_dir_path( 'thumbs' ) . $file_name;
		$confirmed  = ppom_get_dir_path( 'confirmed/' . $order_id ) . $product_id . '-' . $file_name;

		$gd = imagecreatetruecolor( 4, 4 );
		imagepng( $gd, $thumb_path );
		imagedestroy( $gd );
		file_put_contents( $confirmed, 'image payload placeholder' );

		$item = new class( $order_id, $product_id ) {
			/**
			 * @var int
			 */
			private $order_id;

			/**
			 * @var int
			 */
			private $product_id;

			/**
			 * @param int $order_id   Order ID.
			 * @param int $product_id Product ID.
			 */
			public function __construct( $order_id, $product_id ) {
				$this->order_id   = $order_id;
				$this->product_id = $product_id;
			}

			/**
			 * @return int
			 */
			public function get_order_id() {
				return $this->order_id;
			}

			/**
			 * @return int
			 */
			public function get_product_id() {
				return $this->product_id;
			}
		};

		list( $html, $warnings ) = $this->captureWarnings(
			function () use ( $file_name, $item ) {
				return \PPOM\Support\Helpers::generate_html_for_files( $file_name, 'file', $item );
			}
		);

		$this->assertSame( array(), $warnings );
		$this->assertStringContainsString( 'thumbs/' . $file_name, $html );
		$this->assertStringNotContainsString( 'images/file.png', $html );

		unlink( $thumb_path );
		unlink( $confirmed );
	}

	/**
	 * Ensure croppie settings normalize circle boundaries and boolean flags.
	 *
	 * @return void
	 */
	public function testGetCroppieOptionsNormalizesBoundaryAndFlags() {
		$options = ppom_get_croppie_options(
			array(
				'viewport_type'    => 'circle',
				'boundary'         => '250,300',
				'enable_exif'      => 'on',
				'enforce_boundary' => 'on',
				'enable_zoom'      => 'on',
				'show_zoomer'      => 'on',
				'options'          => array(
					array(
						'width'  => '150',
						'height' => '90',
					),
				),
			)
		);

		$this->assertSame(
			array(
				'width'  => '150',
				'height' => '90',
				'type'   => 'circle',
			),
			$options['viewport']
		);
		$this->assertSame(
			array(
				'height' => '250',
				'width'  => '250',
			),
			$options['boundary']
		);
		$this->assertTrue( $options['enableExif'] );
		$this->assertTrue( $options['enforceBoundary'] );
		$this->assertTrue( $options['enableZoom'] );
		$this->assertTrue( $options['showZoomer'] );
	}
}
