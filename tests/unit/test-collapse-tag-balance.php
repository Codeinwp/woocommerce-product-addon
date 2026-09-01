<?php
/**
 * Div tag-balance coverage for Collapse fields in both field renderers.
 *
 * PPOM ships two renderers, picked by the `ppom_enable_legacy_inputs_rendering`
 * option: the legacy template `templates/render-fields.php` and the default
 * `PPOM_Form::ppom_fields_render()`. A Collapse start marker opens a
 * `.collapsed-child` pane that has to wrap every field up to the next marker,
 * so both renderers must emit balanced markup for the same field sets.
 *
 * @package ppom-pro
 */

require_once __DIR__ . '/class-ppom-test-case.php';

use PPOM\FieldMarkup\FieldMarkupRenderer;
use PPOM\Support\Helpers;

/**
 * @covers \PPOM\Support\Helpers::load_template
 * @covers \PPOM_Form::ppom_fields_render
 */
class Test_Collapse_Tag_Balance extends PPOM_Test_Case {

	/**
	 * Re-creates the FieldMarkupRenderer singleton.
	 *
	 * Its constructor registers the `nmform_attribute_value` filter that flattens
	 * the renderers' `classes` array. WP_UnitTestCase restores the global hook
	 * arrays between tests while the singleton survives, so without this the
	 * filter is missing from the second test onwards.
	 *
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
	 * Field sets exercised against both renderers.
	 *
	 * @return array<string, array<int, array>>
	 */
	private function field_sets() {
		return array(
			'no collapse'                => array( $this->text( 'a' ) ),
			'collapse only'              => array( $this->collapse( 'c1' ) ),
			'field then collapse last'   => array( $this->text( 'a' ), $this->collapse( 'c1' ) ),
			'one collapse + field'       => array( $this->collapse( 'c1' ), $this->text( 'a' ) ),
			'two collapse + fields'      => array( $this->collapse( 'c1' ), $this->text( 'a' ), $this->collapse( 'c2' ), $this->text( 'b' ) ),
			'three collapse + fields'    => array( $this->collapse( 'c1' ), $this->text( 'a' ), $this->collapse( 'c2' ), $this->text( 'b' ), $this->collapse( 'c3' ), $this->text( 'c' ) ),
			'consecutive collapse'       => array( $this->collapse( 'c1' ), $this->collapse( 'c2' ), $this->text( 'a' ) ),
			'two fields per collapse'    => array( $this->collapse( 'c1' ), $this->text( 'a' ), $this->text( 'b' ), $this->collapse( 'c2' ), $this->text( 'c' ), $this->text( 'd' ) ),
			'collapse then end'          => array( $this->collapse( 'c1' ), $this->text( 'a' ), $this->collapse_end() ),
			'field after collapse end'   => array( $this->collapse( 'c1' ), $this->text( 'a' ), $this->collapse_end(), $this->text( 'b' ) ),
			'skipped field last'         => array( $this->collapse( 'c1' ), $this->text( 'a' ), $this->text( '', array( 'title' => '' ) ) ),
			'skipped field only'         => array( $this->collapse( 'c1' ), $this->text( '', array( 'title' => '' ) ) ),
		);
	}

	/**
	 * The legacy template must emit balanced markup for every field set.
	 *
	 * @return void
	 */
	public function test_legacy_template_markup_is_balanced() {
		$deltas = array();
		foreach ( $this->field_sets() as $label => $fields ) {
			$deltas[ $label ] = $this->div_delta( $this->render_legacy( $fields ) );
		}

		$this->assertSame( array_fill_keys( array_keys( $this->field_sets() ), 0 ), $deltas );
	}

	/**
	 * The default renderer must emit balanced markup for every field set.
	 *
	 * @return void
	 */
	public function test_default_renderer_markup_is_balanced() {
		$deltas = array();
		foreach ( $this->field_sets() as $label => $fields ) {
			$deltas[ $label ] = $this->div_delta( $this->render_default( $fields ) );
		}

		$this->assertSame( array_fill_keys( array_keys( $this->field_sets() ), 0 ), $deltas );
	}

	/**
	 * Both renderers must leave the Collapse pane open for the fields that follow.
	 *
	 * @return void
	 */
	public function test_collapse_pane_wraps_the_following_fields() {
		$fields = array( $this->collapse( 'c1' ), $this->text( 'a' ) );

		foreach ( array( $this->render_legacy( $fields ), $this->render_default( $fields ) ) as $html ) {
			$this->assertStringContainsString( '<div class="collapsed-child">', $html );
			$this->assertStringNotContainsString( '<div class="collapsed-child"></div>', $html );
			$this->assertMatchesRegularExpression(
				'#<div class="collapsed-child">\s*<div[^>]*data-data_name#',
				$html,
				'The field must be rendered inside the Collapse pane.'
			);
		}
	}

	/**
	 * Every Collapse section must sit at the same depth inside the collapse
	 * container, so the accordion script and its `>` selectors reach them all.
	 *
	 * @return void
	 */
	public function test_every_collapse_section_stays_inside_the_container() {
		$fields = array( $this->collapse( 'c1' ), $this->text( 'a' ), $this->collapse( 'c2' ), $this->text( 'b' ), $this->collapse( 'c3' ), $this->text( 'c' ) );

		// The legacy template renders its own `.ppom-section-collapse` wrapper, so
		// its sections sit one level deeper than the default renderer's fragment.
		$this->assertSame( array( 1, 1, 1 ), $this->div_depths( $this->render_legacy( $fields ), '<h4 ' ) );
		$this->assertSame( array( 0, 0, 0 ), $this->div_depths( $this->render_default( $fields ), '<h4 ' ) );
	}

	/**
	 * Returns the nesting depth of every occurrence of a needle, counting only
	 * `div` tags. A negative depth means an unmatched closing tag was reached.
	 *
	 * @param string $html   Markup.
	 * @param string $needle Opening tag to locate.
	 *
	 * @return array<int, int>
	 */
	private function div_depths( $html, $needle ) {
		preg_match_all( '#<div\b|</div>|' . preg_quote( $needle, '#' ) . '#i', $html, $matches, PREG_OFFSET_CAPTURE );

		$depth  = 0;
		$depths = array();
		foreach ( $matches[0] as $match ) {
			if ( 0 === stripos( $match[0], '</div' ) ) {
				--$depth;
				$this->assertGreaterThanOrEqual( 0, $depth, 'An unmatched </div> was emitted.' );
			} elseif ( 0 === stripos( $match[0], '<div' ) ) {
				++$depth;
			} else {
				$depths[] = $depth;
			}
		}

		$this->assertSame( 0, $depth, 'The markup left a <div> unclosed.' );

		return $depths;
	}

	/**
	 * Renders templates/render-fields.php for a given field set.
	 *
	 * @param array $fields Field meta.
	 *
	 * @return string
	 */
	private function render_legacy( array $fields ) {
		$product = $this->create_simple_product();

		ob_start();
		Helpers::load_template(
			'render-fields.php',
			array(
				'ppom_settings'    => array(),
				'product'          => $product,
				'ppom_fields_meta' => $fields,
				'ppom_id'          => 1,
				'args'             => null,
			)
		);

		return ob_get_clean();
	}

	/**
	 * Renders PPOM_Form::ppom_fields_render() for a given field set.
	 *
	 * @param array $fields Field meta.
	 *
	 * @return string
	 */
	private function render_default( array $fields ) {
		$product = $this->create_simple_product();
		$meta_id = $this->insert_ppom_meta( $fields, $product->get_id() );

		$form = new PPOM_Form( $product, null );

		ob_start();
		$form->ppom_fields_render( $meta_id );

		return ob_get_clean();
	}

	/**
	 * Returns the closing-minus-opening `div` delta of a markup fragment.
	 *
	 * @param string $html Markup.
	 *
	 * @return int
	 */
	private function div_delta( $html ) {
		return preg_match_all( '#</div>#i', $html ) - preg_match_all( '/<div\b/i', $html );
	}

	/**
	 * Builds a Collapse start marker.
	 *
	 * @param string $name Data name.
	 *
	 * @return array
	 */
	private function collapse( $name ) {
		return array(
			'type'          => 'collapse',
			'title'         => strtoupper( $name ),
			'data_name'     => $name,
			'collapse_type' => 'start',
		);
	}

	/**
	 * Builds a Collapse end marker.
	 *
	 * @return array
	 */
	private function collapse_end() {
		return array(
			'type'          => 'collapse',
			'title'         => 'END',
			'data_name'     => 'cend',
			'collapse_type' => 'end',
		);
	}

	/**
	 * Builds a plain text field.
	 *
	 * @param string $name      Data name.
	 * @param array  $overrides Field overrides.
	 *
	 * @return array
	 */
	private function text( $name, $overrides = array() ) {
		return array_merge(
			array(
				'type'      => 'text',
				'title'     => strtoupper( $name ),
				'data_name' => $name,
			),
			$overrides
		);
	}
}
