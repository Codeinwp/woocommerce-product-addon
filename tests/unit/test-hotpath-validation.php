<?php
/**
 * Hotpath: server-side add-to-cart validation via ppom_check_validation().
 * Runs on every add-to-cart submission and on the validation AJAX endpoint.
 *
 * @package ppom-pro
 */

require_once __DIR__ . '/class-ppom-test-case.php';

class Test_Hotpath_Validation extends PPOM_Test_Case {

	/**
	 * Tracer: a required text field with no posted value fails validation
	 * and surfaces exactly one error notice; filling it in lets validation pass.
	 *
	 * @return void
	 */
	public function testRequiredTextFieldFailsValidationWhenEmptyAndPassesWhenFilled() {
		$product = $this->create_simple_product();

		$this->insert_ppom_meta(
			array(
				$this->build_text_field(
					'engraving',
					'Engraving',
					array( 'required' => 'on' )
				),
			),
			$product->get_id()
		);

		$_POST['ppom'] = array(
			'fields' => array( 'engraving' => '' ),
		);

		$this->assertFalse( ppom_check_validation( $product->get_id(), $_POST ) );
		$this->assertSame( 1, wc_notice_count( 'error' ) );

		wc_clear_notices();

		$_POST['ppom'] = array(
			'fields' => array( 'engraving' => 'My text' ),
		);

		$this->assertTrue( ppom_check_validation( $product->get_id(), $_POST ) );
		$this->assertSame( 0, wc_notice_count( 'error' ) );
	}

	/**
	 * field_requires_add_to_cart_schema_checks gates the validation loop:
	 * fields without 'required' / min_checked / max_checked are skipped
	 * (pure cosmetic/price fields short-circuit).
	 *
	 * @return void
	 */
	public function testFieldRequiresAddToCartSchemaChecksSkipsFieldsWithoutSchemaRules() {
		$optional_text = array(
			'type'      => 'text',
			'data_name' => 'note',
		);

		$required_text = array(
			'type'      => 'text',
			'data_name' => 'note',
			'required'  => 'on',
		);

		$checkbox_min = array(
			'type'        => 'checkbox',
			'data_name'   => 'extras',
			'min_checked' => '1',
		);

		$missing_dataname = array(
			'type'     => 'text',
			'required' => 'on',
		);

		$this->assertFalse( \PPOM\WooCommerce\Product\ProductHandler::field_requires_add_to_cart_schema_checks( $optional_text ) );
		$this->assertTrue( \PPOM\WooCommerce\Product\ProductHandler::field_requires_add_to_cart_schema_checks( $required_text ) );
		$this->assertTrue( \PPOM\WooCommerce\Product\ProductHandler::field_requires_add_to_cart_schema_checks( $checkbox_min ) );
		$this->assertFalse( \PPOM\WooCommerce\Product\ProductHandler::field_requires_add_to_cart_schema_checks( $missing_dataname ) );
	}

	/**
	 * A required field that the client reports as conditionally hidden must
	 * NOT block add-to-cart — required state is bypassed for hidden fields.
	 *
	 * @return void
	 */
	public function testRequiredFieldHiddenByConditionIsSkippedDuringValidation() {
		$product = $this->create_simple_product();

		$this->insert_ppom_meta(
			array(
				$this->build_text_field(
					'engraving',
					'Engraving',
					array( 'required' => 'on' )
				),
			),
			$product->get_id()
		);

		$_POST['ppom'] = array(
			'fields'               => array( 'engraving' => '' ),
			'conditionally_hidden' => 'engraving',
		);

		$this->assertTrue( ppom_check_validation( $product->get_id(), $_POST ) );
		$this->assertSame( 0, wc_notice_count( 'error' ) );
	}

	/**
	 * A product with no attached PPOM fields short-circuits validation and
	 * returns the upstream $passed value unchanged (no DB writes, no notices).
	 *
	 * @return void
	 */
	public function testProductWithoutPpomFieldsReturnsUpstreamPassedValueUnchanged() {
		$product = $this->create_simple_product();

		$this->assertTrue( ppom_check_validation( $product->get_id(), array(), true ) );
		$this->assertFalse( ppom_check_validation( $product->get_id(), array(), false ) );
		$this->assertSame( 0, wc_notice_count( 'error' ) );
	}

	/**
	 * A required field passes when its conditionally_hidden list mentions a
	 * different field — only the specifically-hidden field is bypassed.
	 *
	 * @return void
	 */
	public function testRequiredFieldStillEnforcedWhenAnotherFieldIsConditionallyHidden() {
		$product = $this->create_simple_product();

		$this->insert_ppom_meta(
			array(
				$this->build_text_field(
					'engraving',
					'Engraving',
					array( 'required' => 'on' )
				),
				$this->build_text_field( 'note', 'Note' ),
			),
			$product->get_id()
		);

		$_POST['ppom'] = array(
			'fields'               => array(
				'engraving' => '',
				'note'      => 'just a note',
			),
			'conditionally_hidden' => 'note',
		);

		$this->assertFalse( ppom_check_validation( $product->get_id(), $_POST ) );
		$this->assertSame( 1, wc_notice_count( 'error' ) );
	}
}
