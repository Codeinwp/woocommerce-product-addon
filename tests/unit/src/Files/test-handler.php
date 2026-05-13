<?php
/**
 * Unit tests for PPOM\Files\Handler — file path/name primitives and image helpers.
 *
 * @package ppom-pro
 */

require_once dirname( __DIR__, 2 ) . '/class-ppom-test-case.php';

use PPOM\Files\Handler;

/**
 * @covers \PPOM\Files\Handler
 */
class Test_Files_Handler extends PPOM_Test_Case {

	/**
	 * Track artifacts to clean up.
	 *
	 * @var array<int, string>
	 */
	private $artifacts = array();

	public function tearDown(): void {
		foreach ( $this->artifacts as $path ) {
			if ( $path && file_exists( $path ) ) {
				@unlink( $path );
			}
		}
		$this->artifacts = array();

		parent::tearDown();
	}

	/**
	 * create_unique_file_name embeds the 6-char hash slug between the base name and extension.
	 *
	 * @return void
	 */
	public function test_create_unique_file_name_inserts_hashed_segment_before_extension() {
		$unique = Handler::create_unique_file_name( 'invoice', 'pdf' );

		$this->assertMatchesRegularExpression( '/^invoice\.[a-f0-9]{6}\.pdf$/', $unique );
	}

	/**
	 * Two consecutive calls with the same input must differ — the seed includes
	 * microtime + wp_rand so collisions are exceedingly unlikely.
	 *
	 * @return void
	 */
	public function test_create_unique_file_name_avoids_repeating_same_value() {
		$a = Handler::create_unique_file_name( 'image', 'png' );
		$b = Handler::create_unique_file_name( 'image', 'png' );

		$this->assertNotSame( $a, $b );
	}

	/**
	 * When a candidate file already exists in the target dir, wp_unique_filename
	 * must disambiguate the result (collision suffix or new hash).
	 *
	 * @return void
	 */
	public function test_create_unique_file_name_disambiguates_when_target_already_exists() {
		$dir = Handler::get_dir_path();

		add_filter( 'wp_hash', array( $this, 'force_stable_wp_hash' ), 10, 1 );
		try {
			$first  = Handler::create_unique_file_name( 'paper', 'txt', $dir );
			$path   = $dir . $first;
			$this->artifacts[] = $path;
			file_put_contents( $path, 'placeholder' );

			$second = Handler::create_unique_file_name( 'paper', 'txt', $dir );
		} finally {
			remove_filter( 'wp_hash', array( $this, 'force_stable_wp_hash' ), 10 );
		}

		$this->assertNotSame( $first, $second );
		$this->assertFileExists( $path );
	}

	/**
	 * Stable wp_hash so create_unique_file_name produces a deterministic basename
	 * for the collision test (otherwise the two calls already differ via the seed).
	 *
	 * @return string
	 */
	public function force_stable_wp_hash() {
		return 'abcdef1234567890';
	}

	/**
	 * file_get_name namespaces the file with `{product_id}-` to avoid cross-product
	 * collisions in the confirmed/edits directories.
	 *
	 * @return void
	 */
	public function test_file_get_name_prefixes_with_product_id() {
		$this->assertSame( '42-photo.jpg', Handler::file_get_name( 'photo.jpg', 42 ) );
		$this->assertSame( '0-noop.txt', Handler::file_get_name( 'noop.txt', 0 ) );
	}

	/**
	 * The ppom_file_name_prefix filter can replace the namespaced name entirely.
	 *
	 * @return void
	 */
	public function test_file_get_name_filter_can_override_result() {
		$filter = static function () {
			return 'overridden.bin';
		};
		add_filter( 'ppom_file_name_prefix', $filter );

		try {
			$this->assertSame( 'overridden.bin', Handler::file_get_name( 'orig.txt', 11 ) );
		} finally {
			remove_filter( 'ppom_file_name_prefix', $filter );
		}
	}

	/**
	 * is_file_image returns the mime type for a real PNG and false for non-image content.
	 *
	 * @return void
	 */
	public function test_is_file_image_returns_mime_for_real_png_and_false_otherwise() {
		$dir = Handler::get_dir_path();

		$png_path = $dir . 'ppom-test-' . wp_generate_password( 6, false ) . '.png';
		$txt_path = $dir . 'ppom-test-' . wp_generate_password( 6, false ) . '.txt';

		$this->artifacts[] = $png_path;
		$this->artifacts[] = $txt_path;

		$gd = imagecreatetruecolor( 4, 4 );
		imagepng( $gd, $png_path );
		imagedestroy( $gd );

		file_put_contents( $txt_path, 'not an image, just text' );

		$this->assertSame( 'image/png', Handler::is_file_image( $png_path ) );
		$this->assertFalse( Handler::is_file_image( $txt_path ) );
	}

	/**
	 * save_data_url_to_image strips the data-URI prefix and writes the binary payload
	 * into the cropped/ subdirectory.
	 *
	 * @return void
	 */
	public function test_save_data_url_to_image_writes_decoded_binary_to_cropped_dir() {
		$cropped_dir = Handler::get_dir_path( 'cropped' );
		$file_name   = 'data-url-' . wp_generate_password( 6, false ) . '.png';
		$dest        = $cropped_dir . $file_name;

		$this->artifacts[] = $dest;

		$gd = imagecreatetruecolor( 2, 2 );
		ob_start();
		imagepng( $gd );
		$binary = ob_get_clean();
		imagedestroy( $gd );

		$data_url = 'data:image/png;base64,' . base64_encode( $binary );

		Handler::save_data_url_to_image( $data_url, $file_name );

		$this->assertFileExists( $dest );
		$this->assertSame( $binary, file_get_contents( $dest ) );
		$this->assertSame( 'image/png', Handler::is_file_image( $dest ) );
	}

	/**
	 * files_trim_name is a passthrough today but is used everywhere — pin the behavior
	 * so we notice if a future change starts truncating or escaping.
	 *
	 * @return void
	 */
	public function test_files_trim_name_returns_value_unchanged() {
		$this->assertSame( 'hello.txt', Handler::files_trim_name( 'hello.txt' ) );
		$this->assertSame( '', Handler::files_trim_name( '' ) );
		$this->assertSame( 'spaces in name.png', Handler::files_trim_name( 'spaces in name.png' ) );
	}

	/**
	 * files_uploaded_days_count returns the absolute day difference regardless of input order.
	 *
	 * @return void
	 */
	public function test_files_uploaded_days_count_is_symmetric_and_uses_abs() {
		$a = '2024-01-01 00:00:00';
		$b = '2024-01-08 00:00:00';

		$this->assertEqualsWithDelta( 7.0, Handler::files_uploaded_days_count( $a, $b ), 0.0001 );
		$this->assertEqualsWithDelta( 7.0, Handler::files_uploaded_days_count( $b, $a ), 0.0001 );
		$this->assertEqualsWithDelta( 0.0, Handler::files_uploaded_days_count( $a, $a ), 0.0001 );
	}

	/**
	 * create_image_thumb resizes a source image into the `thumbs/` sibling directory.
	 *
	 * @return void
	 */
	public function test_create_image_thumb_writes_thumb_for_image_source() {
		$dir       = Handler::get_dir_path();
		$thumb_dir = $dir . 'thumbs/';
		wp_mkdir_p( $thumb_dir );

		$name = 'src-' . wp_generate_password( 6, false ) . '.png';

		$source = $dir . $name;
		$thumb  = $thumb_dir . $name;

		$this->artifacts[] = $source;
		$this->artifacts[] = $thumb;

		$gd = imagecreatetruecolor( 60, 60 );
		imagepng( $gd, $source );
		imagedestroy( $gd );

		$destination = Handler::create_image_thumb( $dir, $name, 32 );

		$this->assertSame( $thumb, $destination );
		$this->assertFileExists( $thumb );

		$size = getimagesize( $thumb );
		$this->assertLessThanOrEqual( 32, $size[0] );
		$this->assertLessThanOrEqual( 32, $size[1] );
	}

	/**
	 * get_dir_url switches between the base and the thumbs subdirectory.
	 *
	 * @return void
	 */
	public function test_get_dir_url_returns_thumbs_subpath_when_requested() {
		$base   = Handler::get_dir_url( false );
		$thumbs = Handler::get_dir_url( true );

		$this->assertStringEndsWith( '/' . PPOM_UPLOAD_DIR_NAME . '/', $base );
		$this->assertStringEndsWith( '/' . PPOM_UPLOAD_DIR_NAME . '/thumbs/', $thumbs );
	}

	/**
	 * The ppom_dir_url filter can rewrite the resolved URL (e.g. CDN override).
	 *
	 * @return void
	 */
	public function test_get_dir_url_respects_filter_override() {
		$filter = static function () {
			return 'https://cdn.example/ppom/';
		};
		add_filter( 'ppom_dir_url', $filter );

		try {
			$this->assertSame( 'https://cdn.example/ppom/', Handler::get_dir_url() );
		} finally {
			remove_filter( 'ppom_dir_url', $filter );
		}
	}
}
