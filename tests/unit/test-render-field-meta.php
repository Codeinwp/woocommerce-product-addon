<?php
/**
 * Class Test_Render_Field_Meta
 *
 * Regression coverage for PPOM_Fields_Meta::render_field_meta() — specifically
 * the case where the input class isn't registered (e.g. a Pro field type while
 * Pro is deactivated), so the settings schema is empty and the legacy
 * implementation rendered no title/data_name inputs, causing the form
 * round-trip to silently drop those keys on save.
 *
 * @package ppom-pro
 */

require_once __DIR__ . '/class-ppom-test-case.php';

/**
 * @covers PPOM_Fields_Meta::render_field_meta
 */
class Test_Render_Field_Meta extends WP_UnitTestCase {

	/**
	 * @var PPOM_Fields_Meta
	 */
	private $form_meta;

	/**
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();
		require_once PPOM_PATH . '/classes/fields.class.php';
		$this->form_meta = new PPOM_Fields_Meta();
	}

	/**
	 * Empty schema + array $save_meta with title/data_name: hidden inputs are
	 * emitted so the form round-trip preserves those keys on save.
	 *
	 * @return void
	 */
	public function test_emits_hidden_title_and_data_name_when_schema_is_empty() {
		$html = $this->form_meta->render_field_meta(
			array(),
			'cropper',
			7,
			array(
				'type'      => 'cropper',
				'title'     => 'Product Image',
				'data_name' => 'product_image',
			)
		);

		$this->assertStringContainsString(
			'name="ppom[7][title]"',
			$html,
			'Hidden title input must be present so the row keeps its title across save when Pro is deactivated.'
		);
		$this->assertStringContainsString( 'value="Product Image"', $html );

		$this->assertStringContainsString(
			'name="ppom[7][data_name]"',
			$html,
			'Hidden data_name input must be present so the row keeps its identity across save.'
		);
		$this->assertStringContainsString( 'value="product_image"', $html );
	}

	/**
	 * Empty schema with no save_meta (default ''): no extra hidden inputs are
	 * synthesized — there's nothing to preserve.
	 *
	 * @return void
	 */
	public function test_does_not_emit_preservation_inputs_without_save_meta() {
		$html = $this->form_meta->render_field_meta( array(), 'cropper', 9 );

		$this->assertStringNotContainsString( 'name="ppom[9][title]"', $html );
		$this->assertStringNotContainsString( 'name="ppom[9][data_name]"', $html );
	}

	/**
	 * Empty schema with partial save_meta (only title): only the title hidden
	 * is emitted, not a synthetic empty data_name.
	 *
	 * @return void
	 */
	public function test_emits_only_keys_present_in_save_meta() {
		$html = $this->form_meta->render_field_meta(
			array(),
			'palettes',
			3,
			array(
				'type'  => 'palettes',
				'title' => 'Palette',
			)
		);

		$this->assertStringContainsString( 'name="ppom[3][title]"', $html );
		$this->assertStringNotContainsString( 'name="ppom[3][data_name]"', $html );
	}

	/**
	 * Non-empty schema: the preservation block is skipped because the regular
	 * settings loop already renders the inputs. Verifies we don't double-emit.
	 *
	 * @return void
	 */
	public function test_does_not_emit_preservation_inputs_when_schema_present() {
		$schema = array(
			'title' => array(
				'type'  => 'text',
				'title' => 'Title',
			),
		);

		$html = $this->form_meta->render_field_meta(
			$schema,
			'text',
			1,
			array(
				'type'      => 'text',
				'title'     => 'Some Title',
				'data_name' => 'some_title',
			)
		);

		// Only one hidden input named ppom[1][title] should appear when schema
		// is present (rendered by the legitimate loop, not duplicated by the
		// preservation guard).
		$this->assertSame(
			1,
			substr_count( $html, 'name="ppom[1][title]"' ),
			'Schema present means the regular loop renders the title input; the preservation guard must not duplicate it.'
		);
	}

	/**
	 * Empty schema + save_meta with title containing special chars: value is
	 * escaped via esc_attr so the hidden input is well-formed and not XSS-able.
	 *
	 * @return void
	 */
	public function test_escapes_preserved_values() {
		$html = $this->form_meta->render_field_meta(
			array(),
			'cropper',
			5,
			array(
				'title'     => 'Quote " and <tag>',
				'data_name' => 'safe_name',
			)
		);

		// The literal raw `"` inside the title value would break the attribute.
		// esc_attr converts it to `&quot;`.
		$this->assertStringContainsString( 'value="Quote &quot; and &lt;tag&gt;"', $html );
	}
}
