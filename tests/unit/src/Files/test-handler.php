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
	 * is_file_image returns true for a real PNG and false for non-image content.
	 *
	 * @return void
	 */
	public function test_is_file_image_returns_true_for_real_png_and_false_otherwise() {
		$dir = Handler::get_dir_path();

		$png_path = $dir . 'ppom-test-' . wp_generate_password( 6, false ) . '.png';
		$txt_path = $dir . 'ppom-test-' . wp_generate_password( 6, false ) . '.txt';

		$this->artifacts[] = $png_path;
		$this->artifacts[] = $txt_path;

		$gd = imagecreatetruecolor( 4, 4 );
		imagepng( $gd, $png_path );
		imagedestroy( $gd );

		file_put_contents( $txt_path, 'not an image, just text' );

		$this->assertTrue( Handler::is_file_image( $png_path ) );
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
		$this->assertTrue( Handler::is_file_image( $dest ) );
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

	/**
	 * Replaces WC()->session with a brand new guest session, i.e. simulates a
	 * different anonymous visitor arriving with no session cookie.
	 *
	 * @return void
	 */
	private function start_fresh_guest_session() {
		WC()->session = new WC_Session_Handler();
		WC()->session->init();
	}

	/**
	 * The uploader may delete their own in-progress upload, but a different
	 * anonymous visitor must not be able to claim it.
	 *
	 * Regression test for the missing-authorization report on ppom_delete_file:
	 * knowing the file name was previously enough to delete anybody's upload.
	 *
	 * @return void
	 */
	public function test_only_the_uploading_visitor_owns_an_uploaded_file() {
		$file_name = 'victim-artwork.abc123.png';

		$this->start_fresh_guest_session();
		Handler::remember_uploaded_file( $file_name );

		$this->assertTrue(
			Handler::owns_uploaded_file( $file_name ),
			'The visitor who uploaded the file must be able to delete it.'
		);

		$this->start_fresh_guest_session();

		$this->assertFalse(
			Handler::owns_uploaded_file( $file_name ),
			'A different anonymous visitor must not be able to delete an upload they did not make.'
		);
	}

	/**
	 * Removing a file must drop its ownership record too. Every upload gets a
	 * unique name, so a list that only ever grows would let this public flow
	 * inflate the visitor's serialized session row upload after upload.
	 *
	 * @return void
	 */
	public function test_forgetting_a_file_leaves_other_uploads_owned() {
		$this->start_fresh_guest_session();

		Handler::remember_uploaded_file( 'kept.aaa111.png' );
		Handler::remember_uploaded_file( 'removed.bbb222.png' );

		Handler::forget_uploaded_file( 'removed.bbb222.png' );

		$this->assertFalse(
			Handler::owns_uploaded_file( 'removed.bbb222.png' ),
			'A deleted upload must not stay in the ownership list.'
		);
		$this->assertTrue(
			Handler::owns_uploaded_file( 'kept.aaa111.png' ),
			'Forgetting one upload must not affect the others.'
		);
	}

	/**
	 * Upload and delete are two separate requests, so the ownership record is only
	 * useful if it is persisted against the visitor's session cookie rather than
	 * held in memory. A guest has no WooCommerce session cookie until something
	 * forces one, which is the most likely way this fix could silently stop
	 * legitimate shoppers from removing their own uploads.
	 *
	 * @return void
	 */
	public function test_remembered_upload_survives_into_the_next_request() {
		$file_name = 'persisted-artwork.abc123.png';

		$cookie = $this->capture_session_cookie(
			function () use ( $file_name ) {
				$this->start_fresh_guest_session();
				Handler::remember_uploaded_file( $file_name );
				WC()->session->save_data();
			}
		);

		$this->assertNotEmpty(
			$cookie,
			'Uploading must issue a session cookie, otherwise the record is discarded on shutdown.'
		);

		$this->with_session_cookie(
			$cookie,
			function () use ( $file_name ) {
				$this->assertTrue(
					Handler::owns_uploaded_file( $file_name ),
					'The uploader must still own the file on the follow-up delete request.'
				);
			}
		);
	}

	/**
	 * A shopper filling in several file fields owns every one of their uploads,
	 * and removing one must not revoke the others.
	 *
	 * @return void
	 */
	public function test_visitor_owns_each_of_their_uploads() {
		$this->start_fresh_guest_session();

		Handler::remember_uploaded_file( 'front.aaa111.png' );
		Handler::remember_uploaded_file( 'back.bbb222.png' );
		Handler::remember_uploaded_file( 'notes.ccc333.pdf' );

		$this->assertTrue( Handler::owns_uploaded_file( 'front.aaa111.png' ) );
		$this->assertTrue( Handler::owns_uploaded_file( 'back.bbb222.png' ) );
		$this->assertTrue( Handler::owns_uploaded_file( 'notes.ccc333.pdf' ) );
		$this->assertFalse( Handler::owns_uploaded_file( 'someone-else.ddd444.png' ) );
	}

	/**
	 * Logged-in customers get their session keyed by user ID rather than a guest
	 * hash, so the same ownership rule has to hold for them — including the fact
	 * that a second signed-in customer must not inherit the first one's uploads.
	 *
	 * @return void
	 */
	public function test_signed_in_customer_owns_only_their_own_upload() {
		$file_name = 'members-artwork.eee555.png';

		$first  = $this->factory->user->create( array( 'role' => 'customer' ) );
		$second = $this->factory->user->create( array( 'role' => 'customer' ) );

		wp_set_current_user( $first );
		$this->start_fresh_guest_session();
		Handler::remember_uploaded_file( $file_name );

		$this->assertTrue(
			Handler::owns_uploaded_file( $file_name ),
			'A signed-in customer must be able to remove their own upload.'
		);

		wp_set_current_user( $second );
		$this->start_fresh_guest_session();

		$this->assertFalse(
			Handler::owns_uploaded_file( $file_name ),
			'A different signed-in customer must not inherit that upload.'
		);
	}

	/**
	 * Shoppers routinely upload as a guest and only sign in later at checkout.
	 * WooCommerce migrates the guest session onto the user account at that point,
	 * and the ownership record has to come with it or the shopper loses the
	 * ability to remove a file they uploaded minutes earlier.
	 *
	 * @return void
	 */
	public function test_ownership_survives_signing_in_after_uploading_as_a_guest() {
		$file_name = 'guest-then-login.fff666.png';

		$cookie = $this->capture_session_cookie(
			function () use ( $file_name ) {
				$this->start_fresh_guest_session();
				Handler::remember_uploaded_file( $file_name );
				WC()->session->save_data();
			}
		);

		$this->assertNotEmpty( $cookie );

		$customer = $this->factory->user->create( array( 'role' => 'customer' ) );
		wp_set_current_user( $customer );

		$this->with_session_cookie(
			$cookie,
			function () use ( $file_name, $customer ) {
				// Guards against a vacuous pass: prove WooCommerce really did move the
				// guest session onto the account rather than leaving it untouched.
				$this->assertSame(
					(string) $customer,
					(string) WC()->session->get_customer_id(),
					'Expected the guest session to be migrated onto the signed-in customer.'
				);

				$this->assertTrue(
					Handler::owns_uploaded_file( $file_name ),
					'Signing in at checkout must not orphan an upload made as a guest.'
				);
			}
		);
	}

	/**
	 * Runs $callback with WooCommerce's set-cookie call intercepted, returning the
	 * [name, value] pair it would have sent to the browser. Returning false from
	 * the filter keeps wc_setcookie from touching real headers.
	 *
	 * @param callable $callback Code that should trigger the session cookie.
	 *
	 * @return array
	 */
	private function capture_session_cookie( callable $callback ) {
		$captured = array();

		$capture = static function ( $enabled, $name, $value ) use ( &$captured ) {
			$captured = array( $name, $value );

			return false;
		};

		add_filter( 'woocommerce_set_cookie_enabled', $capture, 10, 3 );

		try {
			$callback();
		} finally {
			remove_filter( 'woocommerce_set_cookie_enabled', $capture, 10 );
		}

		return $captured;
	}

	/**
	 * Rebuilds WC()->session as if a fresh request arrived carrying $cookie, which
	 * is what makes the follow-up delete request find the earlier upload.
	 *
	 * @param array    $cookie   [name, value] pair from capture_session_cookie().
	 * @param callable $callback Assertions to run against the restored session.
	 *
	 * @return void
	 */
	private function with_session_cookie( array $cookie, callable $callback ) {
		$_COOKIE[ $cookie[0] ] = $cookie[1];

		try {
			WC()->session = new WC_Session_Handler();
			WC()->session->init();

			$callback();
		} finally {
			unset( $_COOKIE[ $cookie[0] ] );
		}
	}

	/**
	 * get_field_meta_by_dataname() returns an empty string when a product has no
	 * matching field, and the AJAX upload handler hands that straight to the
	 * preview renderer. Indexing it used to raise a TypeError on PHP 8, turning
	 * an upload with an unknown product_id/data_name into a 500.
	 *
	 * @return void
	 */
	public function test_uploaded_file_preview_tolerates_missing_field_meta() {
		$dir  = Handler::get_dir_path();
		$name = 'preview-' . wp_generate_password( 6, false ) . '.png';
		$path = $dir . $name;

		$this->artifacts[] = $path;

		$gd = imagecreatetruecolor( 8, 8 );
		imagepng( $gd, $path );
		imagedestroy( $gd );

		$html = Handler::uploaded_file_preview( $name, '' );

		$this->assertIsString( $html );
		$this->assertStringContainsString( $name, $html );
	}
}
