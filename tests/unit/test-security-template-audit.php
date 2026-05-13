<?php
/**
 * Storefront and admin template escaping audit.
 *
 * Two distinct buckets here, kept in one file so the trust model is reviewable in one
 * place:
 *
 *  1. Admin-trust design checks (X1, X2, X4, X5) — codify deliberate decisions that
 *     follow PPOM's documented model: HTML produced by admin-defined labels, custom
 *     attribute filters, and admin-saved attachment URLs is rendered without sink
 *     escaping. Any change here must be a deliberate trust-model change, not a stray
 *     edit. These tests pass and stay green; the prose here is the audit trail.
 *
 *  2. Real escaping fixes (X3) — locked-in regression guards for output that *is* now
 *     escaped at the sink. The matching template was updated alongside this test.
 *
 * @group security-template-audit
 *
 * @package ppom-pro
 */

require_once __DIR__ . '/class-ppom-test-case.php';

class Test_Security_Template_Audit extends PPOM_Test_Case {

	/**
	 * X1 — Storefront select template echoes the admin-defined field label without
	 * `esc_html`. PPOM allows admins to embed HTML inside labels (consistent across
	 * radio.php, email.php, number.php, palettes.php, pricematrix.php). This test
	 * exists to flag any accidental tightening; intentional changes must update both
	 * this assertion and the cross-template convention.
	 *
	 * @return void
	 */
	public function testStorefrontSelectTemplateEchoesRawFieldLabel() {
		$path = PPOM_PATH . '/templates/frontend/inputs/select.php';
		$this->assertFileExists( $path );
		$src = (string) file_get_contents( $path );
		$this->assertStringContainsString( 'echo $fm->field_label()', $src );
		$this->assertStringNotContainsString( 'echo esc_html( $fm->field_label()', $src );
	}

	/**
	 * X2 — `ppom_fe_form_element_custom_attr` is documented to return a fully formed
	 * HTML attribute string (`name="value"`). Sink-side escaping would corrupt valid
	 * filter output, so escaping is the filter caller's responsibility. This guard
	 * exists so any accidental `esc_attr` wrapping at the sink is caught.
	 *
	 * @return void
	 */
	public function testStorefrontSelectTemplateConcatenatesCustomAttributesRaw() {
		$path = PPOM_PATH . '/templates/frontend/inputs/select.php';
		$src  = (string) file_get_contents( $path );
		$this->assertStringContainsString( "apply_filters( 'ppom_fe_form_element_custom_attr'", $src );
		$this->assertStringContainsString( 'echo $key . \'="\' . $val . \'"\';', $src );
	}

	/**
	 * X3 — Palettes border color is rendered inside a `<style>` block. Even though
	 * the value is admin-defined, an unescaped color value would allow CSS injection
	 * if any admin saves a malformed value. This test asserts the template now
	 * sanitizes the value via `sanitize_hex_color` AND escapes it at the sink, and
	 * that the previous unsanitized concatenation is gone.
	 *
	 * @return void
	 */
	public function testPalettesTemplateSanitizesBorderColorBeforeEmittingStyleBlock() {
		$path = PPOM_PATH . '/templates/frontend/inputs/palettes.php';
		$src  = (string) file_get_contents( $path );

		$this->assertStringContainsString( 'selected_palette_bclr', $src );
		$this->assertStringContainsString( '<style>', $src );
		$this->assertStringContainsString( 'sanitize_hex_color( $selected_palette_bclr )', $src );
		$this->assertStringContainsString( 'esc_attr( $selected_palette_bclr )', $src );
		$this->assertStringNotContainsString( "border: 2px solid ' . \$selected_palette_bclr . ' !important", $src );
	}

	/**
	 * X4 — Audio template applies `the_content` to the attachment URL so that media
	 * shortcodes (e.g. `[audio]`) hooked on `the_content` produce the player markup.
	 * Escaping the URL here would defeat the shortcode pipeline; the URL is sourced
	 * from the WordPress media library, not from buyer input.
	 *
	 * @return void
	 */
	public function testAudioTemplateRunsTheContentOnAttachmentUrl() {
		$path = PPOM_PATH . '/templates/frontend/inputs/audio.php';
		$src  = (string) file_get_contents( $path );
		$this->assertStringContainsString( "apply_filters( 'the_content', \$audio_url )", $src );
	}

	/**
	 * X5 — Legacy field-builder textarea output uses `esc_html` for the stored body.
	 * Regression guard: textarea content is buyer-editable so it must stay escaped
	 * at the sink even if the surrounding builder is refactored.
	 *
	 * @return void
	 */
	public function testFieldsBuilderTextareaUsesEscHtmlForBodyContent() {
		$path = PPOM_PATH . '/classes/fields.class.php';
		$src  = (string) file_get_contents( $path );
		$this->assertStringContainsString( "case 'textarea':", $src );
		$this->assertStringContainsString( "'>' . esc_html( \$values ) . '</textarea>'", $src );
	}
}
