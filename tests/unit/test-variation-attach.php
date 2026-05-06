<?php
/**
 * Coverage for the variation-product-attach feature.
 *
 * @package ppom-pro
 */

require_once __DIR__ . '/class-ppom-test-case.php';

use PPOM\Support\Helpers;

/**
 * @covers \PPOM\Support\Helpers::get_variation_rule_map
 * @covers \PPOM\Support\Helpers::update_variation_rule_map
 * @covers \PPOM\Support\Helpers::is_meta_group_active_for_variation
 * @covers \PPOM\Support\Helpers::filter_posted_ppom_fields_by_active_variation
 * @covers \PPOM\Support\Helpers::filter_ppom_payload_by_active_variation
 * @covers \NM_PersonalizedProduct_Admin::save_variation_attachments
 * @covers \NM_PersonalizedProduct_Admin::save_attach_selections_from_request
 */
class Test_Variation_Attach extends PPOM_Test_Case {

	private function make_variable_with_two_variations( $name = 'Belt' ) {
		$bundle = $this->create_variable_product_with_variation( array( 'name' => $name ) );

		$second = new WC_Product_Variation();
		$second->set_parent_id( $bundle['product']->get_id() );
		$second->set_status( 'publish' );
		$second->set_regular_price( '14' );
		$second->set_price( '14' );
		$second->save();

		return array(
			'product'    => $bundle['product'],
			'variation_a' => $bundle['variation'],
			'variation_b' => wc_get_product( $second->get_id() ),
		);
	}

	public function test_is_meta_group_active_when_no_restrictions_returns_true() {
		$bundle  = $this->make_variable_with_two_variations();
		$ppom_id = 42;

		$this->assertTrue(
			Helpers::is_meta_group_active_for_variation(
				$bundle['product']->get_id(),
				$ppom_id,
				$bundle['variation_a']->get_id()
			)
		);

		// variation_id = 0 with no restrictions still resolves to true (simple product / unselected).
		$this->assertTrue(
			Helpers::is_meta_group_active_for_variation(
				$bundle['product']->get_id(),
				$ppom_id,
				0
			)
		);
	}

	public function test_is_meta_group_active_returns_true_only_for_listed_variation() {
		$bundle  = $this->make_variable_with_two_variations();
		$ppom_id = 42;

		Helpers::update_variation_rule_map(
			$bundle['product']->get_id(),
			array( $ppom_id => array( $bundle['variation_a']->get_id() ) )
		);

		$this->assertTrue(
			Helpers::is_meta_group_active_for_variation(
				$bundle['product']->get_id(),
				$ppom_id,
				$bundle['variation_a']->get_id()
			)
		);
		$this->assertFalse(
			Helpers::is_meta_group_active_for_variation(
				$bundle['product']->get_id(),
				$ppom_id,
				$bundle['variation_b']->get_id()
			)
		);
		$this->assertFalse(
			Helpers::is_meta_group_active_for_variation(
				$bundle['product']->get_id(),
				$ppom_id,
				0
			)
		);
	}

	public function test_update_variation_rule_map_with_empty_array_clears_rules() {
		$bundle  = $this->make_variable_with_two_variations();
		$ppom_id = 42;

		Helpers::update_variation_rule_map(
			$bundle['product']->get_id(),
			array( $ppom_id => array( $bundle['variation_a']->get_id() ) )
		);
		$this->assertNotEmpty( Helpers::get_variation_rule_map( $bundle['product']->get_id() ) );

		Helpers::update_variation_rule_map( $bundle['product']->get_id(), array() );

		$this->assertSame( array(), Helpers::get_variation_rule_map( $bundle['product']->get_id() ) );
	}

	public function test_save_variation_attachments_writes_rule_map_without_direct_product_attachment() {
		$bundle  = $this->make_variable_with_two_variations();
		$ppom_id = 7;

		$result = NM_PersonalizedProduct_Admin::save_variation_attachments(
			$ppom_id,
			array( $bundle['variation_a']->get_id() ),
			array(),
			true
		);

		$this->assertSame( 0, $result['attached_products'] );
		$this->assertSame( 1, $result['updated_variations'] );

		$this->assertSame(
			array( $ppom_id => array( $bundle['variation_a']->get_id() ) ),
			Helpers::get_variation_rule_map( $bundle['product']->get_id() )
		);

		$this->assertSame( array(), $this->attached_groups_for_product( $bundle['product']->get_id() ) );
	}

	public function test_save_variation_attachments_splits_rules_by_parent_product() {
		$belt    = $this->make_variable_with_two_variations( 'Belt' );
		$skirt   = $this->make_variable_with_two_variations( 'Skirt' );
		$ppom_id = 8;

		NM_PersonalizedProduct_Admin::save_variation_attachments(
			$ppom_id,
			array( $belt['variation_a']->get_id(), $skirt['variation_b']->get_id() ),
			array(),
			true
		);

		$this->assertSame(
			array( $ppom_id => array( $belt['variation_a']->get_id() ) ),
			Helpers::get_variation_rule_map( $belt['product']->get_id() )
		);
		$this->assertSame(
			array( $ppom_id => array( $skirt['variation_b']->get_id() ) ),
			Helpers::get_variation_rule_map( $skirt['product']->get_id() )
		);
		$this->assertSame( array(), $this->attached_groups_for_product( $belt['product']->get_id() ) );
		$this->assertSame( array(), $this->attached_groups_for_product( $skirt['product']->get_id() ) );
	}

	public function test_per_product_semantics_other_product_not_gated() {
		$belt   = $this->make_variable_with_two_variations( 'Belt' );
		$skirt  = $this->make_variable_with_two_variations( 'Skirt' );
		$ppom_id = 99;

		// Restrict only on Belt; Skirt has no rules for this group.
		Helpers::update_variation_rule_map(
			$belt['product']->get_id(),
			array( $ppom_id => array( $belt['variation_a']->get_id() ) )
		);

		$this->assertTrue(
			Helpers::is_meta_group_active_for_variation(
				$skirt['product']->get_id(),
				$ppom_id,
				$skirt['variation_a']->get_id()
			)
		);
		$this->assertTrue(
			Helpers::is_meta_group_active_for_variation(
				$skirt['product']->get_id(),
				$ppom_id,
				$skirt['variation_b']->get_id()
			)
		);
		$this->assertFalse(
			Helpers::is_meta_group_active_for_variation(
				$belt['product']->get_id(),
				$ppom_id,
				$belt['variation_b']->get_id()
			)
		);
	}

	public function test_filter_posted_ppom_fields_strips_inactive_groups() {
		$bundle  = $this->make_variable_with_two_variations();
		$ppom_id = $this->insert_ppom_meta(
			array(
				array_merge(
					$this->build_text_field( 'restricted', 'Restricted' ),
					array( 'ppom_id' => 0 ),
				),
			),
			$bundle['product']->get_id()
		);

		// The PPOM meta layer stamps ppom_id back onto fields when read; refresh now.
		$ppom = new PPOM_Meta( $bundle['product']->get_id() );
		$this->assertNotEmpty( $ppom->fields );
		$resolved_ppom_id = absint( $ppom->fields[0]['ppom_id'] );
		$this->assertSame( $ppom_id, $resolved_ppom_id );

		Helpers::update_variation_rule_map(
			$bundle['product']->get_id(),
			array( $ppom_id => array( $bundle['variation_a']->get_id() ) )
		);

		$posted = array(
			'id'         => (string) $ppom_id,
			'restricted' => 'submitted-value',
		);

		$filtered_inactive = Helpers::filter_posted_ppom_fields_by_active_variation(
			$posted,
			$bundle['product']->get_id(),
			$bundle['variation_b']->get_id()
		);
		$this->assertArrayNotHasKey( 'restricted', $filtered_inactive );
		$this->assertArrayNotHasKey( 'id', $filtered_inactive );

		$filtered_active = Helpers::filter_posted_ppom_fields_by_active_variation(
			$posted,
			$bundle['product']->get_id(),
			$bundle['variation_a']->get_id()
		);
		$this->assertArrayHasKey( 'restricted', $filtered_active );
		$this->assertSame( 'submitted-value', $filtered_active['restricted'] );
		$this->assertSame( (string) $ppom_id, $filtered_active['id'] );
	}

	public function test_filter_ppom_payload_drops_inactive_option_prices() {
		$bundle  = $this->make_variable_with_two_variations();
		$ppom_id = $this->insert_ppom_meta(
			array( $this->build_text_field( 'restricted', 'Restricted' ) ),
			$bundle['product']->get_id()
		);

		Helpers::update_variation_rule_map(
			$bundle['product']->get_id(),
			array( $ppom_id => array( $bundle['variation_a']->get_id() ) )
		);

		$payload = array(
			'fields'            => array(
				'id'         => (string) $ppom_id,
				'restricted' => 'submitted-value',
			),
			'ppom_option_price' => wp_json_encode(
				array(
					array( 'data_name' => 'restricted', 'price' => 5 ),
					array( 'data_name' => 'unrelated',  'price' => 9 ),
				)
			),
		);

		$filtered = Helpers::filter_ppom_payload_by_active_variation(
			$payload,
			$bundle['product']->get_id(),
			$bundle['variation_b']->get_id()
		);

		$this->assertArrayNotHasKey( 'restricted', $filtered['fields'] );

		$decoded = json_decode( $filtered['ppom_option_price'], true );
		$this->assertSame( array( array( 'data_name' => 'unrelated', 'price' => 9 ) ), array_values( $decoded ) );
	}

	public function test_detach_clears_variation_rule_for_group() {
		$bundle  = $this->make_variable_with_two_variations();
		$ppom_id = 21;

		Helpers::update_variation_rule_map(
			$bundle['product']->get_id(),
			array( $ppom_id => array( $bundle['variation_a']->get_id() ) )
		);
		update_post_meta( $bundle['product']->get_id(), PPOM_PRODUCT_META_KEY, array( $ppom_id ) );

		$_POST['ppom-attach-to-products']         = array();
		$_POST['ppom-attach-to-products-initial'] = (string) $bundle['product']->get_id();
		$_POST['ppom-attach-to-variations']         = array();
		$_POST['ppom-attach-to-variations-initial'] = (string) $bundle['variation_a']->get_id();

		NM_PersonalizedProduct_Admin::save_attach_selections_from_request( $ppom_id, true );

		$this->assertSame(
			array(),
			Helpers::get_variation_rule_map( $bundle['product']->get_id() )
		);
	}

	public function test_direct_product_detach_preserves_variation_rule_for_group() {
		$bundle  = $this->make_variable_with_two_variations();
		$ppom_id = 22;

		Helpers::update_variation_rule_map(
			$bundle['product']->get_id(),
			array( $ppom_id => array( $bundle['variation_a']->get_id() ) )
		);
		update_post_meta( $bundle['product']->get_id(), PPOM_PRODUCT_META_KEY, array( $ppom_id ) );

		$_POST['ppom-attach-to-products']           = array();
		$_POST['ppom-attach-to-products-initial']   = (string) $bundle['product']->get_id();
		$_POST['ppom-attach-to-variations']         = array();
		$_POST['ppom-attach-to-variations-initial'] = '';

		NM_PersonalizedProduct_Admin::save_attach_selections_from_request( $ppom_id, true );

		$this->assertSame( array(), $this->attached_groups_for_product( $bundle['product']->get_id() ) );
		$this->assertSame(
			array( $ppom_id => array( $bundle['variation_a']->get_id() ) ),
			Helpers::get_variation_rule_map( $bundle['product']->get_id() )
		);
	}

	public function test_save_variation_attachments_keeps_remaining_variation_when_one_removed() {
		$bundle  = $this->make_variable_with_two_variations();
		$ppom_id = 51;

		NM_PersonalizedProduct_Admin::save_variation_attachments(
			$ppom_id,
			array( $bundle['variation_a']->get_id(), $bundle['variation_b']->get_id() ),
			array(),
			true
		);

		NM_PersonalizedProduct_Admin::save_variation_attachments(
			$ppom_id,
			array( $bundle['variation_b']->get_id() ),
			array( $bundle['variation_a']->get_id(), $bundle['variation_b']->get_id() ),
			true
		);

		$this->assertSame(
			array( $ppom_id => array( $bundle['variation_b']->get_id() ) ),
			Helpers::get_variation_rule_map( $bundle['product']->get_id() )
		);
	}

	public function test_detach_preserves_unrelated_groups_in_rule_map() {
		$bundle      = $this->make_variable_with_two_variations();
		$detach_ppom = 31;
		$keep_ppom   = 32;

		Helpers::update_variation_rule_map(
			$bundle['product']->get_id(),
			array(
				$detach_ppom => array( $bundle['variation_a']->get_id() ),
				$keep_ppom   => array( $bundle['variation_b']->get_id() ),
			)
		);
		update_post_meta(
			$bundle['product']->get_id(),
			PPOM_PRODUCT_META_KEY,
			array( $detach_ppom, $keep_ppom )
		);

		$_POST['ppom-attach-to-products']         = array();
		$_POST['ppom-attach-to-products-initial'] = (string) $bundle['product']->get_id();
		$_POST['ppom-attach-to-variations']         = array();
		$_POST['ppom-attach-to-variations-initial'] = (string) $bundle['variation_a']->get_id();

		NM_PersonalizedProduct_Admin::save_attach_selections_from_request( $detach_ppom, true );

		$this->assertSame(
			array( $keep_ppom => array( $bundle['variation_b']->get_id() ) ),
			Helpers::get_variation_rule_map( $bundle['product']->get_id() )
		);
	}

	public function test_product_selector_excludes_variation_only_parent_product() {
		$bundle  = $this->make_variable_with_two_variations();
		$ppom_id = 41;

		NM_PersonalizedProduct_Admin::save_variation_attachments(
			$ppom_id,
			array( $bundle['variation_a']->get_id() ),
			array(),
			true
		);

		$admin   = new NM_PersonalizedProduct_Admin();
		$options = $admin->get_wc_products( $ppom_id );
		$values  = array_map( 'absint', wp_list_pluck( $options['options'], 'value' ) );

		$this->assertNotContains( $bundle['product']->get_id(), $values );
	}

	public function test_variation_selector_loads_preselected_variation_rules() {
		$bundle  = $this->make_variable_with_two_variations();
		$ppom_id = 42;

		Helpers::update_variation_rule_map(
			$bundle['product']->get_id(),
			array( $ppom_id => array( $bundle['variation_a']->get_id() ) )
		);

		$admin   = new NM_PersonalizedProduct_Admin();
		$options = $admin->get_wc_variations( $ppom_id );
		$values  = array_map( 'absint', wp_list_pluck( $options['options'], 'value' ) );

		$this->assertContains( $bundle['variation_a']->get_id(), $values );
	}

	public function test_ppom_meta_includes_variation_rule_groups_for_current_parent_product() {
		$bundle            = $this->make_variable_with_two_variations();
		$direct_ppom_id    = $this->insert_ppom_meta(
			array( $this->build_text_field( 'direct', 'Direct' ) ),
			$bundle['product']->get_id()
		);
		$variation_ppom_id = $this->insert_ppom_meta(
			array( $this->build_text_field( 'variation_only', 'Variation Only' ) )
		);

		Helpers::update_variation_rule_map(
			$bundle['product']->get_id(),
			array( $variation_ppom_id => array( $bundle['variation_a']->get_id() ) )
		);

		$ppom = new PPOM_Meta( $bundle['product']->get_id() );

		$this->assertSame( array( $direct_ppom_id, $variation_ppom_id ), array_values( array_map( 'absint', (array) $ppom->meta_id ) ) );
		$this->assertSame(
			array( 'direct', 'variation_only' ),
			array_values( wp_list_pluck( $ppom->fields, 'data_name' ) )
		);
	}

	private function attached_groups_for_product( $product_id ) {
		$attached = get_post_meta( $product_id, PPOM_PRODUCT_META_KEY, true );
		if ( is_array( $attached ) ) {
			return array_values( array_filter( array_map( 'absint', $attached ) ) );
		}
		if ( is_numeric( $attached ) && (int) $attached > 0 ) {
			return array( absint( $attached ) );
		}
		return array();
	}

	public function test_save_variation_attachments_clears_only_variation_rule_when_all_variations_removed() {
		$bundle  = $this->make_variable_with_two_variations();
		$ppom_id = 71;

		Helpers::update_variation_rule_map(
			$bundle['product']->get_id(),
			array( $ppom_id => array( $bundle['variation_a']->get_id() ) )
		);
		update_post_meta( $bundle['product']->get_id(), PPOM_PRODUCT_META_KEY, array( $ppom_id ) );

		$_POST['ppom-attach-to-products']           = array( (string) $bundle['product']->get_id() );
		$_POST['ppom-attach-to-products-initial']   = (string) $bundle['product']->get_id();
		$_POST['ppom-attach-to-variations']         = array();
		$_POST['ppom-attach-to-variations-initial'] = (string) $bundle['variation_a']->get_id();

		NM_PersonalizedProduct_Admin::save_attach_selections_from_request( $ppom_id, true );

		$this->assertContains(
			$ppom_id,
			$this->attached_groups_for_product( $bundle['product']->get_id() ),
			'Removing variation selections must not remove a direct product attachment.'
		);
		$this->assertSame( array(), Helpers::get_variation_rule_map( $bundle['product']->get_id() ) );
	}

	public function test_save_variation_attachments_preserves_parent_attach_when_parent_is_in_direct_list() {
		$bundle  = $this->make_variable_with_two_variations();
		$ppom_id = 72;

		Helpers::update_variation_rule_map(
			$bundle['product']->get_id(),
			array( $ppom_id => array( $bundle['variation_a']->get_id() ) )
		);
		update_post_meta( $bundle['product']->get_id(), PPOM_PRODUCT_META_KEY, array( $ppom_id ) );

		$_POST['ppom-attach-to-products']           = array( (string) $bundle['product']->get_id() );
		$_POST['ppom-attach-to-products-initial']   = (string) $bundle['product']->get_id();
		$_POST['ppom-attach-to-variations']         = array();
		$_POST['ppom-attach-to-variations-initial'] = (string) $bundle['variation_a']->get_id();

		NM_PersonalizedProduct_Admin::save_attach_selections_from_request( $ppom_id, true );

		$this->assertContains(
			$ppom_id,
			$this->attached_groups_for_product( $bundle['product']->get_id() ),
			'Parent must remain attached when it is explicitly listed in the direct product list, even if all variations are removed.'
		);
		$this->assertSame( array(), Helpers::get_variation_rule_map( $bundle['product']->get_id() ) );
	}

	public function test_save_variation_attachments_no_detach_when_some_variations_remain() {
		$bundle  = $this->make_variable_with_two_variations();
		$ppom_id = 73;

		Helpers::update_variation_rule_map(
			$bundle['product']->get_id(),
			array( $ppom_id => array( $bundle['variation_a']->get_id(), $bundle['variation_b']->get_id() ) )
		);
		$_POST['ppom-attach-to-products']           = array();
		$_POST['ppom-attach-to-products-initial']   = '';
		$_POST['ppom-attach-to-variations']         = array( (string) $bundle['variation_b']->get_id() );
		$_POST['ppom-attach-to-variations-initial'] = $bundle['variation_a']->get_id() . ',' . $bundle['variation_b']->get_id();

		NM_PersonalizedProduct_Admin::save_attach_selections_from_request( $ppom_id, true );

		$this->assertSame(
			array( $ppom_id => array( $bundle['variation_b']->get_id() ) ),
			Helpers::get_variation_rule_map( $bundle['product']->get_id() )
		);
		$this->assertSame( array(), $this->attached_groups_for_product( $bundle['product']->get_id() ) );
	}

	public function test_save_variation_attachments_no_effect_when_group_had_no_variations() {
		$bundle  = $this->make_variable_with_two_variations();
		$ppom_id = 74;

		// Parent attached directly; never had a variation rule for this group.
		update_post_meta( $bundle['product']->get_id(), PPOM_PRODUCT_META_KEY, array( $ppom_id ) );

		$_POST['ppom-attach-to-products']           = array( (string) $bundle['product']->get_id() );
		$_POST['ppom-attach-to-products-initial']   = (string) $bundle['product']->get_id();
		$_POST['ppom-attach-to-variations']         = array();
		$_POST['ppom-attach-to-variations-initial'] = '';

		NM_PersonalizedProduct_Admin::save_attach_selections_from_request( $ppom_id, true );

		$this->assertContains(
			$ppom_id,
			$this->attached_groups_for_product( $bundle['product']->get_id() ),
			'A direct-only attachment must not be affected by the variation save path.'
		);
	}

	public function test_save_variation_attachments_preserves_existing_rules_when_license_invalid() {
		$bundle  = $this->make_variable_with_two_variations();
		$ppom_id = 75;

		Helpers::update_variation_rule_map(
			$bundle['product']->get_id(),
			array( $ppom_id => array( $bundle['variation_a']->get_id() ) )
		);

		$result = NM_PersonalizedProduct_Admin::save_variation_attachments(
			$ppom_id,
			array( $bundle['variation_b']->get_id() ),
			array( $bundle['variation_a']->get_id() ),
			false
		);

		$this->assertSame(
			array(
				'updated_variations' => 0,
				'attached_products'  => 0,
			),
			$result
		);
		$this->assertSame(
			array( $ppom_id => array( $bundle['variation_a']->get_id() ) ),
			Helpers::get_variation_rule_map( $bundle['product']->get_id() )
		);
	}

	public function test_save_attach_selections_from_request_preserves_variation_rules_when_license_invalid() {
		$bundle  = $this->make_variable_with_two_variations();
		$ppom_id = 76;

		Helpers::update_variation_rule_map(
			$bundle['product']->get_id(),
			array( $ppom_id => array( $bundle['variation_a']->get_id() ) )
		);

		$_POST['ppom-attach-to-products']           = array();
		$_POST['ppom-attach-to-products-initial']   = '';
		$_POST['ppom-attach-to-variations']         = array( (string) $bundle['variation_b']->get_id() );
		$_POST['ppom-attach-to-variations-initial'] = (string) $bundle['variation_a']->get_id();

		$result = NM_PersonalizedProduct_Admin::save_attach_selections_from_request( $ppom_id, false );

		$this->assertSame( 0, $result['variations'] );
		$this->assertSame(
			array( $ppom_id => array( $bundle['variation_a']->get_id() ) ),
			Helpers::get_variation_rule_map( $bundle['product']->get_id() )
		);
	}
}
