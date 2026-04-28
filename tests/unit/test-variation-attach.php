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

	public function test_save_variation_attachments_writes_rule_map_and_auto_attaches_parent() {
		$bundle  = $this->make_variable_with_two_variations();
		$ppom_id = 7;

		$result = NM_PersonalizedProduct_Admin::save_variation_attachments(
			$ppom_id,
			array( $bundle['variation_a']->get_id() ),
			array(),
			true
		);

		$this->assertSame( 1, $result['attached_products'] );
		$this->assertSame( 1, $result['updated_variations'] );

		$this->assertSame(
			array( $ppom_id => array( $bundle['variation_a']->get_id() ) ),
			Helpers::get_variation_rule_map( $bundle['product']->get_id() )
		);

		$attached = get_post_meta( $bundle['product']->get_id(), PPOM_PRODUCT_META_KEY, true );
		$attached = is_array( $attached ) ? array_map( 'absint', $attached ) : array( absint( $attached ) );
		$this->assertContains( $ppom_id, $attached );
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
}
