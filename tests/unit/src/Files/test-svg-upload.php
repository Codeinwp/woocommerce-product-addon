<?php
/**
 * Tests SVG uploads: the sanitizer and the ppom_upload_file AJAX action.
 *
 * @package ppom
 */

use PPOM\Files\SvgSanitizer;

/**
 * Thrown from the ppom_file_upload filter to capture the response before the handler's die().
 */
class PPOM_Test_Upload_Response extends Exception {

	/**
	 * Response the handler was about to print.
	 *
	 * @var array<string, mixed>
	 */
	public $response;

	/**
	 * @param array<string, mixed> $response Upload response.
	 */
	public function __construct( array $response ) {
		parent::__construct( 'upload finished' );
		$this->response = $response;
	}
}

/**
 * @covers \PPOM\Files\SvgSanitizer
 * @covers \PPOM\Files\Handler::upload_file
 */
class Test_Svg_Upload extends WP_Ajax_UnitTestCase {

	private const OPEN = '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">';

	/**
	 * Product the upload field is attached to.
	 *
	 * @var int
	 */
	private $product_id = 0;

	public function setUp(): void {
		parent::setUp();

		wp_set_current_user( 0 );
		$_POST    = array();
		$_GET     = array();
		$_REQUEST = array();

		add_filter( 'ppom_file_upload', array( $this, 'capture_upload_response' ) );
		// No network calls from the admin_init that _handleAjax() fires.
		remove_action( 'admin_init', '_maybe_update_core' );
		remove_action( 'admin_init', '_maybe_update_plugins' );
		remove_action( 'admin_init', '_maybe_update_themes' );
		// Session cookies cannot be sent once PHPUnit has printed output.
		add_filter( 'woocommerce_set_cookie_enabled', '__return_false' );
	}

	public function tearDown(): void {
		foreach ( glob( ppom_get_dir_path() . 'ppom-ajax-*' ) ?: array() as $path ) {
			unlink( $path );
		}

		$_POST    = array();
		$_GET     = array();
		$_REQUEST = array();
		parent::tearDown();
	}

	/**
	 * @param array<string, mixed> $response Upload response.
	 *
	 * @throws PPOM_Test_Upload_Response Always, to stop before the handler's die().
	 */
	public function capture_upload_response( array $response ): array {
		throw new PPOM_Test_Upload_Response( $response );
	}

	/**
	 * Attach a group with one upload field to a new product.
	 *
	 * @param string|null $file_types The field's "File types" setting; null leaves it unset.
	 * @param string      $type       Field type.
	 */
	private function create_product_with_file_field( ?string $file_types, string $type = 'file' ): void {
		$product = new WC_Product_Simple();
		$product->set_name( 'Upload Product' );
		$product->set_status( 'publish' );
		$product->set_regular_price( '10' );
		$this->product_id = (int) $product->save();

		$field = array(
			'type'      => $type,
			'title'     => 'Artwork',
			'data_name' => 'artwork',
			'file_size' => '1mb',
		);
		if ( null !== $file_types ) {
			$field['file_types'] = $file_types;
		}

		$fields = array( $field );
		$row    = array(
			'productmeta_name'       => 'Upload Group',
			'productmeta_validation' => 'no',
			'dynamic_price_display'  => 'no',
			'send_file_attachment'   => '',
			'show_cart_thumb'        => 'no',
			'aviary_api_key'         => '',
			'productmeta_style'      => '',
			'productmeta_categories' => '',
			'the_meta'               => wp_json_encode( $fields ),
			'productmeta_created'    => current_time( 'mysql' ),
		);

		$meta_id = ppom_meta_repository()->insert_group( $row, array_fill( 0, count( $row ), '%s' ) );
		ppom_admin_update_ppom_meta_only( $meta_id, $fields );
		update_post_meta( $this->product_id, PPOM_PRODUCT_META_KEY, $meta_id );
	}

	/**
	 * Send one upload chunk as a guest and return the decoded response.
	 *
	 * @param string $name   Posted file name.
	 * @param int    $chunk  Chunk index.
	 * @param int    $chunks Total chunks; 0 for a single-request upload.
	 *
	 * @return array<string, mixed>
	 */
	private function send_chunk( string $name, int $chunk, int $chunks ): array {
		$_POST = array(
			'ppom_nonce' => wp_create_nonce( 'ppom_uploading_file_action' ),
			'name'       => $name,
			'chunk'      => $chunk,
			'chunks'     => $chunks,
			'product_id' => $this->product_id,
			'data_name'  => 'artwork',
		);

		try {
			$this->_handleAjax( 'ppom_upload_file' );
		} catch ( WPAjaxDieContinueException $e ) {
			return (array) json_decode( $this->_last_response, true );
		} catch ( PPOM_Test_Upload_Response $e ) {
			ob_end_clean();
			return $e->response;
		}

		$this->fail( 'Upload request did not finish.' );
	}

	/**
	 * Upload in two chunks with $content as the body; the CLI has no php://input to send it.
	 *
	 * @param string $name    Posted file name.
	 * @param string $content File body.
	 *
	 * @return array<string, mixed>
	 */
	private function upload( string $name, string $content ): array {
		$this->send_chunk( $name, 0, 2 );

		// Any chunk name, so a missing sanitizer fails the caller's assertions, not this one.
		$parts = glob( ppom_get_dir_path() . pathinfo( $name, PATHINFO_FILENAME ) . '.*.part*' ) ?: array();
		$this->assertCount( 1, $parts, 'First chunk was not stored.' );
		file_put_contents( $parts[0], $content );

		return $this->send_chunk( $name, 1, 2 );
	}

	/**
	 * Each payload carries an "alert" or "evil" marker that must not survive.
	 *
	 * @return array<string, array{0: string}>
	 */
	public function dangerous_svg_provider(): array {
		return array(
			'script element'          => array( self::OPEN . '<script>alert(1)</script><rect/></svg>' ),
			'event handler'           => array( self::OPEN . '<rect onload="alert(1)" onclick="alert(2)"/></svg>' ),
			'foreignObject'           => array( self::OPEN . '<foreignObject><iframe xmlns="http://www.w3.org/1999/xhtml" src="javascript:alert(1)"/></foreignObject></svg>' ),
			'html namespace script'   => array( self::OPEN . '<h:script xmlns:h="http://www.w3.org/1999/xhtml">alert(1)</h:script></svg>' ),
			'xsl element'             => array( '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xsl="http://www.w3.org/1999/XSL/Transform"><xsl:element name="script" namespace="http://www.w3.org/2000/svg">alert(1)</xsl:element></svg>' ),
			'stylesheet pi before'    => array( '<?xml version="1.0"?><?xml-stylesheet type="text/xsl" href="evil.svg"?>' . self::OPEN . '<rect/></svg>' ),
			'stylesheet pi inside'    => array( self::OPEN . '<?xml-stylesheet type="text/xsl" href="evil.svg"?><rect/></svg>' ),
			'javascript link'         => array( self::OPEN . '<a href="javascript:alert(1)"><text>x</text></a></svg>' ),
			'tab-split javascript'    => array( self::OPEN . '<use xlink:href="java&#x09;script:alert(1)"/></svg>' ),
			'remote use'              => array( self::OPEN . '<use href="https://evil.example/x.svg#a"/></svg>' ),
			'remote image'            => array( self::OPEN . '<image href="https://evil.example/x.png"/></svg>' ),
			'svg data uri image'      => array( self::OPEN . '<image href="data:image/svg+xml;base64,ZXZpbA=="/></svg>' ),
			'animate href'            => array( self::OPEN . '<a><animate attributeName="href" to="javascript:alert(1)"/><text>x</text></a></svg>' ),
			'set href'                => array( self::OPEN . '<use><set attributeName="href" to="javascript:alert(1)"/></use></svg>' ),
			'style element'           => array( self::OPEN . '<style>@import url(https://evil.example/a.css);</style><rect/></svg>' ),
			'style element escapes'   => array( self::OPEN . '<style>rect{fill:\\75 rl(https://evil.example/p.svg#g)}</style><rect/></svg>' ),
			'style element image-set' => array( self::OPEN . '<style>rect{background:image-set("https://evil.example/p.png" 1x)}</style><rect/></svg>' ),
			'remote url in style'     => array( self::OPEN . '<rect style="fill:url(https://evil.example/p.svg#g)"/></svg>' ),
			'remote url in attribute' => array( self::OPEN . '<rect fill="url( \'https://evil.example/p.svg#g\' )"/></svg>' ),
			'comment'                 => array( self::OPEN . '<!-- evil --><rect/></svg>' ),
			'duplicate local name'    => array( '<svg xmlns="http://www.w3.org/2000/svg" xmlns:f="urn:f" onload="alert(1)" f:onload="x"><rect f:onclick="y" onclick="alert(2)"/></svg>' ),
			'css escaped url'         => array( self::OPEN . '<rect style="fill:\\75 rl(https://evil.example/p.svg#g)"/></svg>' ),
			'image-set in style'      => array( self::OPEN . '<rect style="background-image:image-set(\'https://evil.example/p.png\' 1x)"/></svg>' ),
			'webkit image-set'        => array( self::OPEN . '<rect style="-webkit-mask-image:-webkit-image-set(\'https://evil.example/p.png\' 1x)"/></svg>' ),
			'svg without svg ns'      => array( '<svg><script>alert(1)</script></svg>' ),
		);
	}

	/**
	 * @dataProvider dangerous_svg_provider
	 *
	 * @param string $svg Payload.
	 */
	public function test_sanitizer_strips_script_and_remote_content( string $svg ): void {
		$clean = SvgSanitizer::clean( $svg );

		$this->assertIsString( $clean );
		$this->assertStringStartsWith( '<svg', $clean );
		foreach ( array( 'alert', 'evil', 'script', 'foreignObject', 'animate', '<set' ) as $marker ) {
			$this->assertStringNotContainsStringIgnoringCase( $marker, $clean );
		}
	}

	/**
	 * @return array<string, array{0: string}>
	 */
	public function rejected_svg_provider(): array {
		return array(
			'empty'              => array( '' ),
			'not xml'            => array( 'hello world' ),
			'html document'      => array( '<html><body><script>alert(1)</script></body></html>' ),
			'internal entity'    => array( '<!DOCTYPE svg [<!ENTITY x "&#60;script&#62;alert(1)&#60;/script&#62;">]>' . self::OPEN . '&x;</svg>' ),
			'benign doctype'     => array( '<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">' . self::OPEN . '<rect/></svg>' ),
			'undeclared entity'  => array( self::OPEN . '&x;</svg>' ),
		);
	}

	/**
	 * @dataProvider rejected_svg_provider
	 *
	 * @param string $svg Payload.
	 */
	public function test_sanitizer_rejects_non_svg_and_dtd_input( string $svg ): void {
		$this->assertNull( SvgSanitizer::clean( $svg ) );
	}

	public function test_sanitizer_keeps_drawing_markup_and_case_sensitive_attributes(): void {
		$svg = '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 10 10" preserveAspectRatio="xMidYMid meet">'
			. '<style>rect{fill:red}</style>'
			. '<defs><linearGradient id="g" gradientUnits="userSpaceOnUse"><stop offset="0" stop-color="#f00"/></linearGradient></defs>'
			. '<circle cx="5" cy="5" r="4" fill="url(#g)" style="stroke:#000;stroke-width:1"/>'
			. '<use xlink:href="#g"/>'
			. '<image href="data:image/png;base64,iVBORw0KGgo="/>'
			. '<text x="1" y="9" xml:space="preserve">Hi &amp; bye</text>'
			. '</svg>';

		$clean = SvgSanitizer::clean( $svg );

		$this->assertIsString( $clean );
		foreach ( array( 'rect{fill:red}', 'viewBox="0 0 10 10"', 'preserveAspectRatio="xMidYMid meet"', '<linearGradient id="g" gradientUnits="userSpaceOnUse">', 'fill="url(#g)"', 'style="stroke:#000;stroke-width:1"', 'xlink:href="#g"', 'href="data:image/png;base64,iVBORw0KGgo="', 'xml:space="preserve"', 'Hi &amp; bye' ) as $kept ) {
			$this->assertStringContainsString( $kept, $clean );
		}
	}

	/**
	 * Field type, its "File types" setting (null = unset), upload name, and whether it is accepted.
	 *
	 * @return array<string, array{0: string, 1: string|null, 2: string, 3: bool}>
	 */
	public function field_file_types_provider(): array {
		return array(
			'default file field refuses svg'  => array( 'file', null, 'ppom-ajax-logo.svg', false ),
			'default file field refuses png'  => array( 'file', null, 'ppom-ajax-photo.png', false ),
			'default cropper refuses svg'     => array( 'cropper', null, 'ppom-ajax-logo.svg', false ),
			'default cropper refuses pdf'     => array( 'cropper', null, 'ppom-ajax-photo.pdf', false ),
			'opted-in svg is taken'           => array( 'file', 'jpg, svg, pdf', 'ppom-ajax-logo.svg', true ),
			'setting ignores case and spaces' => array( 'file', ' JPG , SVG ', 'ppom-ajax-logo.svg', true ),
		);
	}

	/**
	 * @dataProvider field_file_types_provider
	 *
	 * @param string      $type       Field type.
	 * @param string|null $file_types The field's "File types" setting.
	 * @param string      $name       Posted file name.
	 * @param bool        $allowed    Whether the first chunk is accepted.
	 */
	public function test_field_file_types_gate_the_extension( string $type, ?string $file_types, string $name, bool $allowed ): void {
		$this->create_product_with_file_field( $file_types, $type );

		// A first chunk: refused before any bytes are written, not by the sanitizer.
		$response = $this->send_chunk( $name, 0, 2 );

		$parts = glob( ppom_get_dir_path() . pathinfo( $name, PATHINFO_FILENAME ) . '.*' ) ?: array();
		if ( $allowed ) {
			$this->assertArrayNotHasKey( 'status', $response );
			$this->assertCount( 1, $parts );
		} else {
			$this->assertSame( 'File type not valid - ' . pathinfo( $name, PATHINFO_EXTENSION ), $response['message'] ?? '' );
			$this->assertCount( 0, $parts );
		}
	}

	public function test_non_svg_markup_is_rejected_and_removed(): void {
		$this->create_product_with_file_field( 'jpg,svg' );

		$response = $this->upload( 'ppom-ajax-logo.svg', '<html><body><script>alert(1)</script></body></html>' );

		$this->assertSame( 'error', $response['status'] ?? '' );
		$this->assertSame( 'File type not valid - svg', $response['message'] ?? '' );
		$this->assertCount( 0, glob( ppom_get_dir_path() . 'ppom-ajax-logo.*' ) ?: array() );
	}

	public function test_svg_is_stored_sanitized_on_a_field_that_lists_svg(): void {
		$this->create_product_with_file_field( 'jpg,png,svg' );

		$response = $this->upload(
			'ppom-ajax-logo.svg',
			'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10" onload="alert(1)"><script>alert(2)</script><circle cx="5" cy="5" r="4"/></svg>'
		);

		$this->assertArrayHasKey( 'file_name', $response );
		$this->assertStringEndsWith( '.svg', $response['file_name'] );

		$stored = (string) file_get_contents( ppom_get_dir_path() . $response['file_name'] );
		$this->assertStringContainsString( '<circle', $stored );
		$this->assertStringNotContainsString( 'script', $stored );
		$this->assertStringNotContainsString( 'onload', $stored );
	}

	public function test_other_svg_mime_extensions_are_sanitized_too(): void {
		$this->create_product_with_file_field( 'svgz' );
		$filter = static function ( array $types ): array {
			return $types + array( 'svgz' => 'image/svg+xml' );
		};
		add_filter( 'ppom_custom_allowed_mime_types', $filter );

		$response = $this->upload( 'ppom-ajax-logo.svgz', '<html><body><script>alert(1)</script></body></html>' );

		$this->assertSame( 'File type not valid - svgz', $response['message'] ?? '' );
		$this->assertCount( 0, glob( ppom_get_dir_path() . 'ppom-ajax-logo.*' ) ?: array() );
	}

	/**
	 * @return array<string, array{0: string}>
	 */
	public function chunk_name_provider(): array {
		return array(
			'svg' => array( 'ppom-ajax-logo.svg' ),
			'txt' => array( 'ppom-ajax-notes.txt' ),
		);
	}

	/**
	 * @dataProvider chunk_name_provider
	 *
	 * @param string $name Posted file name.
	 */
	public function test_partial_chunk_does_not_keep_its_extension( string $name ): void {
		$this->create_product_with_file_field( 'txt,svg' );

		$this->send_chunk( $name, 0, 2 );

		$base = ppom_get_dir_path() . pathinfo( $name, PATHINFO_FILENAME );
		$this->assertCount( 1, glob( $base . '.*.part.bin' ) ?: array() );
		$this->assertCount( 0, glob( $base . '.*.' . pathinfo( $name, PATHINFO_EXTENSION ) . '.part*' ) ?: array() );
	}
}
