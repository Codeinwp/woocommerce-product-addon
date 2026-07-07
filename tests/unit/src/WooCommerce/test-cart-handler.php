<?php
/**
 * Unit tests for PPOM\WooCommerce\Cart\CartHandler helpers.
 *
 * @package ppom-pro
 */

use PPOM\WooCommerce\Cart\CartHandler;

/**
 * @covers \PPOM\WooCommerce\Cart\CartHandler
 */
class Test_Cart_Handler extends PPOM_Test_Case {

	/**
	 * @return void
	 */
	public function test_legacy_option_prices_aggregate_display_quantity_sums_when_include_empty() {
		$qty = CartHandler::legacy_option_prices_aggregate_display_quantity(
			array(
				array( 'include' => '', 'quantity' => 2 ),
				array( 'include' => '', 'quantity' => 3 ),
			)
		);

		$this->assertSame( 5, $qty );
	}

	/**
	 * @return void
	 */
	public function test_legacy_option_prices_aggregate_display_quantity_resets_to_one_when_include_on() {
		$qty = CartHandler::legacy_option_prices_aggregate_display_quantity(
			array(
				array( 'include' => '', 'quantity' => 4 ),
				array( 'include' => 'on', 'quantity' => 99 ),
			)
		);

		$this->assertSame( 1, $qty );
	}

	/**
	 * @return void
	 */
	public function test_should_skip_cart_meta_for_empty_display_text_types_only_strict_empty_string() {
		$this->assertTrue( CartHandler::should_skip_cart_meta_for_empty_display( 'text', '' ) );
		$this->assertFalse( CartHandler::should_skip_cart_meta_for_empty_display( 'text', '0' ) );
		$this->assertFalse( CartHandler::should_skip_cart_meta_for_empty_display( 'number', 0 ) );
	}

	/**
	 * @return void
	 */
	public function test_should_skip_cart_meta_for_empty_display_other_types_use_empty() {
		$this->assertTrue( CartHandler::should_skip_cart_meta_for_empty_display( 'select', '' ) );
		$this->assertTrue( CartHandler::should_skip_cart_meta_for_empty_display( 'select', '0' ) );
		$this->assertFalse( CartHandler::should_skip_cart_meta_for_empty_display( 'select', 'ok' ) );
	}

	/**
	 * @return void
	 */
	public function test_legacy_option_prices_matrix_context_starts_from_cart_quantity() {
		$ctx = CartHandler::legacy_option_prices_matrix_context( array(), 3 );

		$this->assertSame( 0, $ctx['total_quantities'] );
		$this->assertSame( 3.0, $ctx['matrix_order_qty'] );
	}

	/**
	 * @return void
	 */
	public function test_legacy_option_prices_matrix_context_accumulates_quantities_apply_rows() {
		$ctx = CartHandler::legacy_option_prices_matrix_context(
			array(
				array( 'apply' => 'quantities', 'quantity' => 2 ),
				array( 'apply' => 'quantities', 'quantity' => 5 ),
			),
			1
		);

		$this->assertSame( 7, $ctx['total_quantities'] );
		$this->assertSame( 7, $ctx['matrix_order_qty'] );
	}

	/**
	 * @return void
	 */
	public function test_apply_price_matrix_chunk_replaces_org_when_not_discount_matrix() {
		$out = CartHandler::apply_price_matrix_chunk_to_line_components(
			array( 'price' => '42.00' ),
			100,
			0,
			0,
			0,
			false,
			2
		);

		$this->assertTrue( $out['replace_org_price'] );
		$this->assertSame( '42.00', $out['org_price'] );
		$this->assertSame( 0, $out['discount_delta'] );
	}

	/**
	 * @return void
	 */
	public function test_apply_price_matrix_chunk_percent_discount_on_base_only() {
		$out = CartHandler::apply_price_matrix_chunk_to_line_components(
			array(
				'discount' => 'base',
				'percent'  => '10%',
			),
			100,
			50,
			0,
			25,
			false,
			1
		);

		$this->assertFalse( $out['replace_org_price'] );
		$this->assertEqualsWithDelta( 12.5, (float) $out['discount_delta'], 0.0001 );
	}

	/**
	 * @return void
	 */
	public function test_apply_price_matrix_chunk_percent_discount_on_both_includes_options_and_quantities() {
		$out = CartHandler::apply_price_matrix_chunk_to_line_components(
			array(
				'discount' => 'both',
				'percent'  => '10%',
			),
			100,
			20,
			5,
			15,
			false,
			1
		);

		$this->assertFalse( $out['replace_org_price'] );
		$this->assertEqualsWithDelta( 14.0, (float) $out['discount_delta'], 0.0001 );
	}

	/**
	 * @return void
	 */
	public function test_apply_price_matrix_chunk_fixed_discount_without_usebaseprice() {
		$out = CartHandler::apply_price_matrix_chunk_to_line_components(
			array(
				'discount' => 'yes',
				'price'    => 8,
			),
			100,
			0,
			0,
			0,
			false,
			4
		);

		$this->assertFalse( $out['replace_org_price'] );
		$this->assertSame( 8, $out['discount_delta'] );
	}

	/**
	 * @return void
	 */
	public function test_apply_price_matrix_chunk_fixed_discount_divides_by_order_qty_with_usebaseprice() {
		$out = CartHandler::apply_price_matrix_chunk_to_line_components(
			array(
				'discount' => 'yes',
				'price'    => 10,
			),
			100,
			0,
			0,
			0,
			true,
			2
		);

		$this->assertFalse( $out['replace_org_price'] );
		$this->assertSame( 5.0, (float) $out['discount_delta'] );
	}

	/**
	 * @return void
	 */
	public function test_apply_legacy_quantities_exclude_base_adjustment_zeros_base_and_scales_options() {
		$adj = CartHandler::apply_legacy_quantities_exclude_base_adjustment( 10, false, 50, 3, 4 );

		$this->assertSame( 0, $adj['ppom_item_org_price'] );
		$this->assertSame( 12, $adj['total_option_price'] );
	}

	/**
	 * @return void
	 */
	public function test_apply_legacy_quantities_exclude_base_adjustment_noop_when_base_included() {
		$adj = CartHandler::apply_legacy_quantities_exclude_base_adjustment( 10, true, 50, 3, 4 );

		$this->assertSame( 50, $adj['ppom_item_org_price'] );
		$this->assertSame( 3, $adj['total_option_price'] );
	}

	/**
	 * @return void
	 */
	public function test_apply_legacy_measure_multiplier_to_org_price() {
		$this->assertSame( 20, CartHandler::apply_legacy_measure_multiplier_to_org_price( 10, 2 ) );
		$this->assertSame( 10, CartHandler::apply_legacy_measure_multiplier_to_org_price( 10, 1 ) );
	}

	/**
	 * @return void
	 */
	public function test_legacy_cart_line_total_from_components() {
		$this->assertEquals(
			115,
			CartHandler::legacy_cart_line_total_from_components( 100, 20, 5, 10 )
		);
	}

	/**
	 * @param array<string, mixed> $overrides State overrides.
	 *
	 * @return array<string, mixed>
	 */
	private function legacy_pricing_state( array $overrides = array() ) {
		return array_merge(
			array(
				'ppom_item_org_price'          => 100,
				'ppom_item_order_qty'          => 2,
				'total_option_price'           => 0,
				'ppon_onetime_cost'            => 0,
				'ppom_quantities_price'        => 0,
				'ppom_quantities_usebaseprice' => false,
				'ppom_quantities_include_base' => false,
				'ppomm_measures'               => 1,
			),
			$overrides
		);
	}

	/**
	 * @return void
	 */
	public function test_accumulate_legacy_option_price_row_variable_adds_resolved_price() {
		$ctx = array(
			'matrix_found'        => array(),
			'option_prices'       => array(),
			'resolved_line_price' => 4.5,
		);

		$next = CartHandler::accumulate_legacy_option_price_row(
			array( 'apply' => 'variable', 'price' => 999 ),
			$this->legacy_pricing_state(),
			$ctx
		);

		$this->assertEqualsWithDelta( 4.5, (float) $next['total_option_price'], 0.0001 );
	}

	/**
	 * @return void
	 */
	public function test_accumulate_legacy_option_price_row_onetime_adds_resolved_price() {
		$next = CartHandler::accumulate_legacy_option_price_row(
			array( 'apply' => 'onetime', 'price' => 0 ),
			$this->legacy_pricing_state(),
			array(
				'option_prices'       => array(),
				'resolved_line_price' => 12,
			)
		);

		$this->assertEqualsWithDelta( 12.0, (float) $next['ppon_onetime_cost'], 0.0001 );
	}

	/**
	 * @return void
	 */
	public function test_accumulate_legacy_option_price_row_quantities_uses_matrix_price_when_non_discount_matrix() {
		$next = CartHandler::accumulate_legacy_option_price_row(
			array(
				'apply'    => 'quantities',
				'price'    => 1,
				'quantity' => 3,
			),
			$this->legacy_pricing_state(),
			array(
				'matrix_found'  => array( 'price' => 10 ),
				'option_prices' => array(),
			)
		);

		$this->assertEqualsWithDelta( 30.0, (float) $next['ppom_quantities_price'], 0.0001 );
	}

	/**
	 * @return void
	 */
	public function test_accumulate_legacy_option_price_row_quantities_ignores_matrix_when_discount_matrix() {
		$next = CartHandler::accumulate_legacy_option_price_row(
			array(
				'apply'    => 'quantities',
				'price'    => 4,
				'quantity' => 2,
			),
			$this->legacy_pricing_state(),
			array(
				'matrix_found'  => array(
					'price'    => 99,
					'discount' => 'both',
				),
				'option_prices' => array(),
			)
		);

		$this->assertEqualsWithDelta( 8.0, (float) $next['ppom_quantities_price'], 0.0001 );
	}

	/**
	 * @return void
	 */
	public function test_accumulate_legacy_option_price_row_quantities_respects_filter_to_skip_option_price() {
		add_filter( 'ppom_quantities_use_option_price', '__return_false' );

		$next = CartHandler::accumulate_legacy_option_price_row(
			array(
				'apply'    => 'quantities',
				'price'    => 99,
				'quantity' => 5,
			),
			$this->legacy_pricing_state(),
			array(
				'matrix_found'  => array(),
				'option_prices' => array( array( 'apply' => 'quantities' ) ),
			)
		);

		remove_filter( 'ppom_quantities_use_option_price', '__return_false' );

		$this->assertSame( 0, (int) $next['ppom_quantities_price'] );
	}

	/**
	 * @return void
	 */
	public function test_accumulate_legacy_option_price_row_quantities_sets_include_base_flag() {
		$next = CartHandler::accumulate_legacy_option_price_row(
			array(
				'apply'    => 'quantities',
				'price'    => 1,
				'quantity' => 1,
				'include'  => 'on',
			),
			$this->legacy_pricing_state(),
			array(
				'option_prices' => array(),
			)
		);

		$this->assertTrue( $next['ppom_quantities_include_base'] );
	}

	/**
	 * @return void
	 */
	public function test_accumulate_legacy_option_price_row_bulkquantity_adds_base_and_usebase_flag() {
		$next = CartHandler::accumulate_legacy_option_price_row(
			array(
				'apply'          => 'bulkquantity',
				'price'          => 2,
				'quantity'       => 3,
				'base'           => 1.5,
				'usebase_price'  => 'yes',
			),
			$this->legacy_pricing_state(),
			array( 'option_prices' => array() )
		);

		$this->assertEqualsWithDelta( 7.5, (float) $next['ppom_quantities_price'], 0.0001 );
		$this->assertTrue( $next['ppom_quantities_usebaseprice'] );
	}

	/**
	 * @return void
	 */
	public function test_accumulate_legacy_option_price_row_fixedprice_overrides_org_and_order_qty() {
		$next = CartHandler::accumulate_legacy_option_price_row(
			array(
				'apply'      => 'fixedprice',
				'unitprice'  => 55,
			),
			$this->legacy_pricing_state( array( 'ppom_item_org_price' => 20 ) ),
			array( 'option_prices' => array() )
		);

		$this->assertSame( 55, (int) $next['ppom_item_org_price'] );
		$this->assertSame( 1, (int) $next['ppom_item_order_qty'] );
	}

	/**
	 * @return void
	 */
	public function test_accumulate_legacy_option_price_row_measure_multiplies_accumulator() {
		$next = CartHandler::accumulate_legacy_option_price_row(
			array(
				'apply'             => 'measure',
				'qty'               => 2,
				'price_multiplier'  => 3,
			),
			$this->legacy_pricing_state(),
			array( 'option_prices' => array() )
		);

		$this->assertSame( 6.0, (float) $next['ppomm_measures'] );
	}

	/**
	 * @return void
	 */
	public function test_accumulate_legacy_option_price_row_noop_for_unknown_apply() {
		$before = $this->legacy_pricing_state( array( 'total_option_price' => 9 ) );
		$next   = CartHandler::accumulate_legacy_option_price_row(
			array( 'apply' => 'custom_future_type' ),
			$before,
			array( 'option_prices' => array() )
		);

		$this->assertSame( $before, $next );
	}

	/**
	 * @return void
	 */
	public function test_accumulate_legacy_option_price_row_noop_when_apply_missing() {
		$before = $this->legacy_pricing_state();
		$next   = CartHandler::accumulate_legacy_option_price_row(
			array( 'price' => 5 ),
			$before,
			array( 'option_prices' => array() )
		);

		$this->assertSame( $before, $next );
	}

	/**
	 * Regression: `Helpers::generate_cart_meta()` writes `ppom_has_quantities`
	 * as a scalar int alongside the regular meta arrays. The conversion helper
	 * must treat that sentinel as a hidden row instead of dereferencing it like
	 * an array (which previously raised "Trying to access array offset on int"
	 * via `$meta['type']` in CartHandler::add_item_meta).
	 *
	 * @return void
	 */
	public function test_convert_ppom_meta_entry_to_item_meta_row_handles_scalar_quantities_sentinel() {
		$row = CartHandler::convert_ppom_meta_entry_to_item_meta_row( 'ppom_has_quantities', 5 );

		$this->assertIsArray( $row );
		$this->assertSame( 'ppom_has_quantities', $row['name'] );
		$this->assertSame( 5, $row['value'] );
		$this->assertTrue( $row['hidden'] );
	}

	/**
	 * @return void
	 */
	public function test_convert_ppom_meta_entry_to_item_meta_row_skips_empty_display_for_text_field() {
		$row = CartHandler::convert_ppom_meta_entry_to_item_meta_row(
			'engraving',
			array(
				'name'    => 'Engraving',
				'value'   => '',
				'display' => '',
				'type'    => 'text',
			)
		);

		$this->assertNull( $row );
	}

	/**
	 * @return void
	 */
	public function test_convert_ppom_meta_entry_to_item_meta_row_keeps_named_value_row() {
		$row = CartHandler::convert_ppom_meta_entry_to_item_meta_row(
			'engraving',
			array(
				'name'    => 'Engraving',
				'value'   => 'Hello',
				'display' => 'Hello',
				'type'    => 'text',
			)
		);

		$this->assertIsArray( $row );
		$this->assertSame( 'Engraving', $row['name'] );
		$this->assertSame( 'Hello', $row['value'] );
		$this->assertFalse( $row['hidden'] );
	}

	/**
	 * Defensive: `make_meta_data()` doesn't set `type` on every branch
	 * (e.g. cropper variants). Missing `type` must not crash the loop.
	 *
	 * @return void
	 */
	public function test_convert_ppom_meta_entry_to_item_meta_row_tolerates_missing_type_key() {
		$row = CartHandler::convert_ppom_meta_entry_to_item_meta_row(
			'cropper_field',
			array(
				'name'  => 'Cropper',
				'value' => 'somefile.jpg',
			)
		);

		$this->assertIsArray( $row );
		$this->assertSame( 'Cropper', $row['name'] );
	}

	/**
	 * Edge case: when nothing is filled, `$total_qty` is 0. We still want a
	 * hidden row keyed by `ppom_has_quantities`, not a crash and not a null
	 * skip — downstream code reads this sentinel via `$cart_item meta`.
	 *
	 * @return void
	 */
	public function test_convert_ppom_meta_entry_to_item_meta_row_handles_scalar_zero_sentinel() {
		$row = CartHandler::convert_ppom_meta_entry_to_item_meta_row( 'ppom_has_quantities', 0 );

		$this->assertIsArray( $row );
		$this->assertSame( 'ppom_has_quantities', $row['name'] );
		$this->assertSame( 0, $row['value'] );
		$this->assertTrue( $row['hidden'] );
	}

	/**
	 * Filters / extensions may push string scalars (e.g. an addon flag) into
	 * the meta map. They must be handled the same way as int scalars rather
	 * than dereferenced like arrays.
	 *
	 * @return void
	 */
	public function test_convert_ppom_meta_entry_to_item_meta_row_handles_string_scalar() {
		$row = CartHandler::convert_ppom_meta_entry_to_item_meta_row( 'ppom_addon_flag', 'on' );

		$this->assertIsArray( $row );
		$this->assertSame( 'ppom_addon_flag', $row['name'] );
		$this->assertSame( 'on', $row['value'] );
		$this->assertTrue( $row['hidden'] );
	}

	/**
	 * Boolean / null scalars must also fall through the scalar-sentinel
	 * branch instead of attempting array access.
	 *
	 * @return void
	 */
	public function test_convert_ppom_meta_entry_to_item_meta_row_handles_boolean_and_null_scalars() {
		$bool_row = CartHandler::convert_ppom_meta_entry_to_item_meta_row( 'ppom_flag', true );
		$null_row = CartHandler::convert_ppom_meta_entry_to_item_meta_row( 'ppom_flag', null );

		$this->assertIsArray( $bool_row );
		$this->assertSame( true, $bool_row['value'] );
		$this->assertTrue( $bool_row['hidden'] );

		$this->assertIsArray( $null_row );
		$this->assertNull( $null_row['value'] );
		$this->assertTrue( $null_row['hidden'] );
	}

	/**
	 * "0"-as-string in a text field is a valid customer entry (e.g. quantity
	 * of zero rendered as text) and must NOT be skipped, even though plain
	 * `empty('0')` is true. That contract belongs to the `text` / `textarea`
	 * / `number` types via `should_skip_cart_meta_for_empty_display()`.
	 *
	 * @return void
	 */
	public function test_convert_ppom_meta_entry_to_item_meta_row_keeps_text_zero_value() {
		$row = CartHandler::convert_ppom_meta_entry_to_item_meta_row(
			'engraving',
			array(
				'name'    => 'Engraving',
				'value'   => '0',
				'display' => '0',
				'type'    => 'text',
			)
		);

		$this->assertIsArray( $row );
		$this->assertSame( 'Engraving', $row['name'] );
		$this->assertSame( '0', $row['value'] );
	}

	/**
	 * Non-text types (e.g. `select`) treat an empty `display` as "no choice
	 * made" and skip the row entirely.
	 *
	 * @return void
	 */
	public function test_convert_ppom_meta_entry_to_item_meta_row_skips_select_with_empty_display() {
		$row = CartHandler::convert_ppom_meta_entry_to_item_meta_row(
			'size',
			array(
				'name'    => 'Size',
				'value'   => '',
				'display' => '',
				'type'    => 'select',
			)
		);

		$this->assertNull( $row );
	}

	/**
	 * Non-scalar values must be serialised so WooCommerce can persist them
	 * onto the cart line — this matches the legacy behavior the cart pipeline
	 * has always produced.
	 *
	 * @return void
	 */
	public function test_convert_ppom_meta_entry_to_item_meta_row_json_encodes_non_scalar_value() {
		$nested = array(
			'a' => 1,
			'b' => array( 'c' => 2 ),
		);

		$row = CartHandler::convert_ppom_meta_entry_to_item_meta_row(
			'matrix_field',
			array(
				'name'    => 'Matrix',
				'value'   => $nested,
				'display' => 'Matrix display',
				'type'    => 'select',
			)
		);

		$this->assertIsArray( $row );
		$this->assertSame( 'Matrix', $row['name'] );
		$this->assertSame( wp_json_encode( $nested ), $row['value'] );
		$this->assertSame( 'Matrix display', $row['display'] );
	}

	/**
	 * Upstream-set `hidden => true` on the entry must survive through the
	 * conversion (used by quantity-bearing fields and the `hidden` field
	 * type to keep their rows out of the visible cart line).
	 *
	 * @return void
	 */
	public function test_convert_ppom_meta_entry_to_item_meta_row_preserves_hidden_flag_from_entry() {
		$row = CartHandler::convert_ppom_meta_entry_to_item_meta_row(
			'secret',
			array(
				'name'    => 'Secret',
				'value'   => 'shh',
				'display' => 'shh',
				'type'    => 'hidden',
				'hidden'  => true,
			)
		);

		$this->assertIsArray( $row );
		$this->assertSame( 'Secret', $row['name'] );
		$this->assertTrue( $row['hidden'] );
	}

	/**
	 * When `display` is omitted, it falls back to `value`. That fallback
	 * also drives the empty-display skip check, so we assert the fallback
	 * keeps a non-empty entry visible.
	 *
	 * @return void
	 */
	public function test_convert_ppom_meta_entry_to_item_meta_row_display_falls_back_to_value() {
		$row = CartHandler::convert_ppom_meta_entry_to_item_meta_row(
			'note',
			array(
				'name'  => 'Note',
				'value' => 'Hello world',
				'type'  => 'text',
			)
		);

		$this->assertIsArray( $row );
		$this->assertSame( 'Hello world', $row['display'] );
	}

	/**
	 * Non-scalar fallback display values must be serialised so WooCommerce cart
	 * rendering receives a string-safe `display` value on PHP 8.1+.
	 *
	 * @return void
	 */
	public function test_convert_ppom_meta_entry_to_item_meta_row_display_fallback_json_encodes_non_scalar_value() {
		$nested = array(
			'first'  => 'One',
			'second' => array( 'Two' ),
		);

		$row = CartHandler::convert_ppom_meta_entry_to_item_meta_row(
			'matrix_field',
			array(
				'name'  => 'Matrix',
				'value' => $nested,
				'type'  => 'text',
			)
		);

		$this->assertIsArray( $row );
		$this->assertSame( wp_json_encode( $nested ), $row['display'] );
	}

	/**
	 * Empty array entries (`make_meta_data()` produces these for cropper /
	 * image rows whose value is empty in cart context) must not crash and
	 * must produce a fall-through row keyed by the cart key — preserving
	 * legacy behavior of the original `else` branch.
	 *
	 * @return void
	 */
	public function test_convert_ppom_meta_entry_to_item_meta_row_handles_empty_array_entry() {
		$row = CartHandler::convert_ppom_meta_entry_to_item_meta_row(
			'cropper_field',
			array()
		);

		$this->assertIsArray( $row );
		$this->assertSame( 'cropper_field', $row['name'] );
	}

	/**
	 * HTML in the meta `name` would be unsafe to render as a cart-line
	 * label. The conversion helper must strip tags via `wp_strip_all_tags`,
	 * matching the prior add_item_meta() behavior.
	 *
	 * @return void
	 */
	public function test_convert_ppom_meta_entry_to_item_meta_row_strips_html_from_name() {
		$row = CartHandler::convert_ppom_meta_entry_to_item_meta_row(
			'engraving',
			array(
				'name'    => '<b onclick="x">Engraving</b>',
				'value'   => 'Hi',
				'display' => 'Hi',
				'type'    => 'text',
			)
		);

		$this->assertIsArray( $row );
		$this->assertSame( 'Engraving', $row['name'] );
	}

	/**
	 * Slashes added by WordPress' magic-quotes-style escaping should be
	 * stripped from the rendered name (legacy `stripslashes` step).
	 *
	 * @return void
	 */
	public function test_convert_ppom_meta_entry_to_item_meta_row_unescapes_slashes_in_name() {
		$row = CartHandler::convert_ppom_meta_entry_to_item_meta_row(
			'engraving',
			array(
				'name'    => "Customer\\'s note",
				'value'   => 'Hi',
				'display' => 'Hi',
				'type'    => 'text',
			)
		);

		$this->assertIsArray( $row );
		$this->assertSame( "Customer's note", $row['name'] );
	}

	/**
	 * The `ppom_show_option_price_cart` filter, when truthy, appends a
	 * formatted price to the meta value. This proves the conversion helper
	 * still honors the legacy filter contract through the new code path.
	 *
	 * @return void
	 */
	public function test_convert_ppom_meta_entry_to_item_meta_row_appends_price_when_filter_enabled() {
		add_filter( 'ppom_show_option_price_cart', '__return_true' );

		try {
			$row = CartHandler::convert_ppom_meta_entry_to_item_meta_row(
				'engraving',
				array(
					'name'    => 'Engraving',
					'value'   => 'Hello',
					'display' => 'Hello',
					'type'    => 'text',
					'price'   => 5,
				)
			);
		} finally {
			remove_filter( 'ppom_show_option_price_cart', '__return_true' );
		}

		$this->assertIsArray( $row );
		$this->assertStringContainsString( 'Hello', $row['value'] );
		// Format-agnostic: only assert the numeric part survived in the suffix.
		$this->assertStringContainsString( '5', $row['value'] );
		$this->assertNotSame( 'Hello', $row['value'] );
	}

	/**
	 * Without a `name` key, the entry falls through to the second branch and
	 * uses the cart key itself as the row name — preserving the original
	 * `else` branch of the legacy loop.
	 *
	 * @return void
	 */
	public function test_convert_ppom_meta_entry_to_item_meta_row_uses_key_when_name_missing() {
		$row = CartHandler::convert_ppom_meta_entry_to_item_meta_row(
			'fallback_key',
			array(
				'value'   => 'something',
				'display' => 'something',
				'type'    => 'text',
			)
		);

		$this->assertIsArray( $row );
		$this->assertSame( 'fallback_key', $row['name'] );
		$this->assertFalse( $row['hidden'] );
	}

	/**
	 * Regression: PHPUnit upgrades scalar→array access into a real failure,
	 * so calling the helper with the documented sentinel shape must not
	 * raise any PHP warnings/notices. This asserts the bug we fixed (the
	 * "Trying to access array offset on int" notice from CartHandler.php:632)
	 * stays fixed even if the conversion logic is later refactored.
	 *
	 * @return void
	 */
	public function test_convert_ppom_meta_entry_to_item_meta_row_does_not_warn_on_scalar() {
		$caught = null;
		set_error_handler( function ( $errno, $errstr ) use ( &$caught ) {
			$caught = compact( 'errno', 'errstr' );
			return true;
		} );

		try {
			CartHandler::convert_ppom_meta_entry_to_item_meta_row( 'ppom_has_quantities', 5 );
		} finally {
			restore_error_handler();
		}

		$this->assertNull(
			$caught,
			$caught
				? sprintf( 'Got unexpected PHP notice: [%d] %s', $caught['errno'], $caught['errstr'] )
				: ''
		);
	}

	/**
	 * Regression: undercharge when a session-restored cart line arrives with
	 * `fields` but no `ppom_option_price` (e.g. dropped by a caching layer or a
	 * client that never attached it). `update_cart_fees()` must recompute the
	 * addon price server-side from `fields` via `Helpers::compute_option_price_from_fields()`,
	 * mirroring `ProductHandler::validate_product()`'s equivalent legacy-mode
	 * fallback, instead of silently pricing the line at the bare product price.
	 *
	 * @return void
	 */
	public function test_update_cart_fees_computes_price_from_fields_when_option_price_missing() {
		$product    = $this->create_simple_product( array( 'regular_price' => '10' ) );
		$product_id = $product->get_id();

		$meta_id = $this->insert_ppom_meta(
			array(
				$this->build_select_field(
					'size',
					'Size',
					array(
						array( 'option' => 'Small', 'price' => '' ),
						array( 'option' => 'Large', 'price' => '5.00' ),
					)
				),
			),
			$product_id
		);

		$cart_items = array(
			'data'         => $product,
			'variation_id' => 0,
			'quantity'     => 1,
		);

		$values = array(
			'ppom' => array(
				'fields' => array(
					'id'   => (string) $meta_id,
					'size' => 'Large',
				),
			),
		);

		$result = CartHandler::update_cart_fees( $cart_items, $values );

		$this->assertSame( 15.0, (float) $result['data']->get_price() );
	}
}
