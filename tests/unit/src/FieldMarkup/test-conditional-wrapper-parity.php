<?php
/**
 * Initial conditional visibility across the two PPOM input renderers.
 *
 * With the current conditional engine, a field whose `Show` rule is not yet
 * satisfied must start hidden. The outer wrapper carries `ppom-c-hide`, which
 * the stylesheet hides, plus the `ppom-cond-*` classes the script uses to find
 * the field. Both renderers must emit them.
 *
 * @package ppom-pro
 */

require_once dirname( __DIR__, 2 ) . '/class-ppom-test-case.php';

use PPOM\FieldMarkup\FieldMarkupRenderer;
use PPOM\WooCommerce\Product\ProductHandler;

/**
 * @covers \PPOM\Hooks\Callbacks::input_main_wrapper_class
 */
class Test_Conditional_Wrapper_Parity extends PPOM_Test_Case {

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
	 * Returns the opening wrapper tag for one field.
	 *
	 * @param string $html      Rendered markup.
	 * @param string $data_name Field data name.
	 *
	 * @return string
	 */
	private function wrapper_tag( $html, $data_name ) {
		preg_match( '#<div[^>]*data-data_name=["\']?' . preg_quote( $data_name, '#' ) . '\b[^>]*>#', $html, $m );

		return isset( $m[0] ) ? $m[0] : '';
	}

	/**
	 * Builds a product whose `extra` field shows only when `trigger` is Yes.
	 *
	 * @return WC_Product
	 */
	private function create_product_with_conditional_field() {
		$product = $this->create_simple_product();

		$trigger = $this->build_select_field(
			'trigger',
			'Trigger',
			array(
				array( 'option' => 'No', 'id' => 'no', 'price' => '' ),
				array( 'option' => 'Yes', 'id' => 'yes', 'price' => '' ),
			)
		);

		$extra = $this->build_text_field(
			'extra',
			'Extra',
			array(
				'logic'      => 'on',
				'conditions' => array(
					'visibility' => 'Show',
					'bound'      => 'All',
					'rules'      => array(
						array( 'elements' => 'trigger', 'operators' => 'is', 'element_values' => 'Yes' ),
					),
				),
			)
		);

		$this->insert_ppom_meta( array( $trigger, $extra ), $product->get_id() );

		return $product;
	}

	/**
	 * An unsatisfied Show rule starts hidden in both renderers.
	 *
	 * @return void
	 */
	public function test_unsatisfied_show_rule_starts_hidden_in_both_renderers() {
		$product = $this->create_product_with_conditional_field();

		$rendered = array(
			'default' => $this->render_modern( $product->get_id() ),
			'legacy'  => $this->render_legacy( $product->get_id() ),
		);

		foreach ( $rendered as $mode => $html ) {
			$wrapper = $this->wrapper_tag( $html, 'extra' );

			$this->assertNotSame( '', $wrapper, "no wrapper rendered in {$mode} renderer" );
			$this->assertStringContainsString( 'ppom-c-hide', $wrapper, "field not hidden on load in {$mode} renderer" );
			$this->assertStringContainsString( 'ppom-cond-trigger', $wrapper, "condition class missing in {$mode} renderer" );
		}
	}

	/**
	 * A field with no conditional rule is never hidden on load.
	 *
	 * @return void
	 */
	public function test_field_without_conditions_is_not_hidden_in_either_renderer() {
		$product = $this->create_product_with_conditional_field();

		$rendered = array(
			'default' => $this->render_modern( $product->get_id() ),
			'legacy'  => $this->render_legacy( $product->get_id() ),
		);

		foreach ( $rendered as $mode => $html ) {
			$wrapper = $this->wrapper_tag( $html, 'trigger' );

			$this->assertNotSame( '', $wrapper, "no wrapper rendered in {$mode} renderer" );
			$this->assertStringNotContainsString( 'ppom-c-hide', $wrapper, "plain field hidden in {$mode} renderer" );
			$this->assertStringContainsString( 'ppom-c-show', $wrapper, "plain field not marked visible in {$mode} renderer" );
		}
	}

	/**
	 * A `Hide` rule starts visible. Only an unsatisfied `Show` rule starts hidden.
	 *
	 * @return void
	 */
	public function test_hide_rule_starts_visible_in_both_renderers() {
		$product = $this->create_simple_product();

		$trigger = $this->build_select_field(
			'trigger',
			'Trigger',
			array(
				array( 'option' => 'No', 'id' => 'no', 'price' => '' ),
				array( 'option' => 'Yes', 'id' => 'yes', 'price' => '' ),
			)
		);

		$extra = $this->build_text_field(
			'extra',
			'Extra',
			array(
				'logic'      => 'on',
				'conditions' => array(
					'visibility' => 'Hide',
					'bound'      => 'All',
					'rules'      => array(
						array( 'elements' => 'trigger', 'operators' => 'is', 'element_values' => 'Yes' ),
					),
				),
			)
		);

		$this->insert_ppom_meta( array( $trigger, $extra ), $product->get_id() );

		$rendered = array(
			'default' => $this->render_modern( $product->get_id() ),
			'legacy'  => $this->render_legacy( $product->get_id() ),
		);

		foreach ( $rendered as $mode => $html ) {
			$wrapper = $this->wrapper_tag( $html, 'extra' );

			$this->assertStringNotContainsString( 'ppom-c-hide', $wrapper, "Hide rule started hidden in {$mode} renderer" );
			$this->assertStringContainsString( 'ppom-c-show', $wrapper, "Hide rule not marked visible in {$mode} renderer" );
		}
	}

	/**
	 * The misspelled wrapper hook still runs, so anything already using it works.
	 *
	 * @return void
	 */
	public function test_deprecated_misspelled_wrapper_filter_still_runs() {
		$product = $this->create_product_with_conditional_field();

		$filter = static function ( $classes ) {
			return $classes . ' third-party-marker';
		};
		add_filter( 'ppom_field_main_wapper_class', $filter );

		$html = $this->render_legacy( $product->get_id() );

		remove_filter( 'ppom_field_main_wapper_class', $filter );

		$wrapper = $this->wrapper_tag( $html, 'extra' );

		$this->assertStringContainsString( 'third-party-marker', $wrapper );
		$this->assertStringContainsString( 'ppom-c-hide', $wrapper );
	}

	/**
	 * A condition target cannot break out of the class attribute.
	 *
	 * The callback appends each saved rule target as `ppom-cond-{target}`, so a
	 * crafted or imported field group puts stored data into the class attribute.
	 * Both renderers must escape it at the output boundary.
	 *
	 * @return void
	 */
	public function test_condition_target_cannot_break_out_of_the_class_attribute() {
		$product = $this->create_simple_product();

		$trigger = $this->build_select_field(
			'trigger',
			'Trigger',
			array(
				array( 'option' => 'No', 'id' => 'no', 'price' => '' ),
				array( 'option' => 'Yes', 'id' => 'yes', 'price' => '' ),
			)
		);

		$extra = $this->build_text_field(
			'extra',
			'Extra',
			array(
				'logic'      => 'on',
				'conditions' => array(
					'visibility' => 'Show',
					'bound'      => 'All',
					'rules'      => array(
						array(
							'elements'       => 'trigger" data-injected="1',
							'operators'      => 'is',
							'element_values' => 'Yes',
						),
					),
				),
			)
		);

		$this->insert_ppom_meta( array( $trigger, $extra ), $product->get_id() );

		$rendered = array(
			'default' => $this->render_modern( $product->get_id() ),
			'legacy'  => $this->render_legacy( $product->get_id() ),
		);

		foreach ( $rendered as $mode => $html ) {
			$this->assertStringNotContainsString(
				'trigger" data-injected',
				$html,
				"condition target broke out of the class attribute in {$mode} renderer"
			);
			$this->assertStringContainsString(
				'trigger&quot; data-injected',
				$html,
				"condition target not escaped in {$mode} renderer"
			);
		}
	}
}
