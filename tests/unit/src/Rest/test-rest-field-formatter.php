<?php
/**
 * Tests for PPOM\Rest\RestFieldFormatter.
 *
 * @package ppom-pro
 */

use PPOM\Rest\RestFieldFormatter;

/**
 * @covers \PPOM\Rest\RestFieldFormatter
 */
class Test_Rest_Field_Formatter extends WP_UnitTestCase {

	/**
	 * @var RestFieldFormatter
	 */
	private $formatter;

	/**
	 * @var callable|null
	 */
	private $ppom_rest_fields_callback;

	/**
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();
		$this->formatter = new RestFieldFormatter();
	}

	/**
	 * @return void
	 */
	public function tearDown(): void {
		if ( null !== $this->ppom_rest_fields_callback ) {
			remove_filter( 'ppom_rest_fields', $this->ppom_rest_fields_callback );
			$this->ppom_rest_fields_callback = null;
		}
		parent::tearDown();
	}

	/**
	 * @return void
	 */
	public function test_filter_required_keys_only_empty_input_returns_empty_list() {
		$this->assertSame( array(), $this->formatter->filter_required_keys_only( array() ) );
		$this->assertSame( array(), $this->formatter->filter_required_keys_only( null ) );
	}

	/**
	 * @return void
	 */
	public function test_filter_required_keys_only_maps_text_field_keys() {
		$input = array(
			array(
				'title'       => 'Label',
				'type'        => 'text',
				'data_name'   => 'note',
				'description' => 'Desc',
				'required'    => 'yes',
				'placeholder' => 'Type here',
				'options'     => array( 'ignored' ),
				'extra'       => 'stripped',
			),
		);

		$out = $this->formatter->filter_required_keys_only( $input );

		$this->assertCount( 1, $out );
		$this->assertSame(
			array(
				'title'       => 'Label',
				'type'        => 'text',
				'data_name'   => 'note',
				'description' => 'Desc',
				'required'    => 'yes',
				'placeholder' => 'Type here',
				'options'     => array( 'ignored' ),
			),
			$out[0]
		);
	}

	/**
	 * @return void
	 */
	public function test_filter_required_keys_only_select_uses_options_image_uses_images() {
		$input = array(
			array(
				'type'      => 'select',
				'title'     => 'S',
				'data_name' => 's',
				'options'   => array( 'a' => 'A' ),
			),
			array(
				'type'      => 'image',
				'title'     => 'I',
				'data_name' => 'i',
				'images'    => array( 'url' => 'https://example.test/x.png' ),
			),
			array(
				'type'      => 'imageselect',
				'title'     => 'IS',
				'data_name' => 'is',
				'images'    => array( 'thumb' => 'y.png' ),
			),
		);

		$out = $this->formatter->filter_required_keys_only( $input );

		$this->assertSame( array( 'a' => 'A' ), $out[0]['options'] );
		$this->assertSame( array( 'url' => 'https://example.test/x.png' ), $out[1]['options'] );
		$this->assertSame( array( 'thumb' => 'y.png' ), $out[2]['options'] );
	}

	/**
	 * @return void
	 */
	public function test_ppom_rest_fields_filter_runs_last() {
		$this->ppom_rest_fields_callback = function ( $fields ) {
			$fields[] = array(
				'title'       => 'Injected',
				'type'        => 'text',
				'data_name'   => 'injected',
				'description' => '',
				'required'    => '',
				'placeholder' => '',
				'options'     => '',
			);

			return $fields;
		};

		add_filter( 'ppom_rest_fields', $this->ppom_rest_fields_callback, 10, 2 );

		$out = $this->formatter->filter_required_keys_only(
			array(
				array(
					'type'      => 'text',
					'title'     => 'One',
					'data_name' => 'one',
				),
			)
		);

		$this->assertCount( 2, $out );
		$this->assertSame( 'injected', $out[1]['data_name'] );
	}
}
