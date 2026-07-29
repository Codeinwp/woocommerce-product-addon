<?php
/**
 * Delete proof carried by files restored into a rendered form.
 *
 * @package ppom-pro
 */

require_once dirname( __DIR__, 2 ) . '/class-ppom-test-case.php';

use PPOM\FieldMarkup\FormAttributeContext;
use PPOM\FieldMarkup\InputRendererRegistry;
use PPOM\Files\Handler;

/**
 * @covers \PPOM\FieldMarkup\Renderers\FileRenderer
 */
class Test_File_Renderer_Delete_Token extends PPOM_Test_Case {

	/**
	 * @var InputRendererRegistry
	 */
	private $registry;

	/**
	 * @var array<int, string>
	 */
	private $artifacts = array();

	public function setUp(): void {
		parent::setUp();

		$this->registry = new InputRendererRegistry( new FormAttributeContext() );
	}

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
	 * The renderer skips files that are no longer on disk, so a stored upload has
	 * to exist for the restored markup to be produced at all.
	 *
	 * @param string $base Base name for the file.
	 *
	 * @return string
	 */
	private function store_upload( $base ) {
		$name = $base . '.' . wp_generate_password( 6, false ) . '.png';
		$path = Handler::get_dir_path() . $name;

		$this->artifacts[] = $path;

		$image = imagecreatetruecolor( 4, 4 );
		imagepng( $image, $path );
		imagedestroy( $image );

		return $name;
	}

	/**
	 * @param array<int, string> $file_names Stored uploads to restore.
	 *
	 * @return string
	 */
	private function render_restored( array $file_names ) {
		return $this->registry->render(
			'file',
			array(
				'id'           => 'artwork',
				'type'         => 'file',
				'data_name'    => 'artwork',
				'button_class' => 'btn',
				'button_label' => 'Upload',
				'file_cost'    => '0',
			),
			array_map(
				static function ( $name ) {
					return array( 'org' => $name );
				},
				$file_names
			)
		);
	}

	/**
	 * A shopper who uploaded to several file fields before having a session ends
	 * up with only one of those uploads recorded against the session the browser
	 * kept. Once the form is rendered again from the cart, the token is the only
	 * proof left for the others, so the restored input has to carry it or those
	 * files can never be removed.
	 *
	 * @return void
	 */
	public function test_restored_file_input_carries_a_token_the_delete_endpoint_accepts() {
		$file_name = $this->store_upload( 'restored-artwork' );

		$output = $this->render_restored( array( $file_name ) );

		$this->assertStringContainsString(
			'data-delete-token="' . Handler::file_delete_token( $file_name ) . '"',
			$output,
			'The restored input must carry the proof that authorises deleting this file.'
		);
	}

	/**
	 * The token belongs to one file, so two restored files must not share one.
	 *
	 * @return void
	 */
	public function test_each_restored_file_carries_its_own_token() {
		$first  = $this->store_upload( 'front' );
		$second = $this->store_upload( 'back' );

		$output = $this->render_restored( array( $first, $second ) );

		$this->assertStringContainsString( Handler::file_delete_token( $first ), $output );
		$this->assertStringContainsString( Handler::file_delete_token( $second ), $output );
	}
}
