<?php
/**
 * Class Test_Field_Saving
 *
 * @package ppom-pro
 */

require_once __DIR__ . '/class-ppom-test-case.php';

class Test_Field_Saving extends PPOM_Test_Case {

	/**
     * Test if the saved categories are in a backward compatible format and tags in a serialized format.
     */
    public function test_saving_categories_compatibilities() {
        $field_id = $this->insert_ppom_meta(
            array(
                $this->build_text_field( 'test_cat', 'Test Cat' ),
            ),
            0,
            array(
                'productmeta_name'       => 'Test Multiple Categories',
                'productmeta_validation' => '',
                'productmeta_style'      => 'selector { }',
                'productmeta_categories' => "accessories\r\nclothing",
                'productmeta_tags'       => 'a:1:{i:0;s:8:"test-tag";}',
            )
        );

        NM_PersonalizedProduct_Admin::save_categories_and_tags( $field_id, ['accessories', 'clothing', 'test-cat'], false );

        $saved_data = $this->get_ppom_meta_row( $field_id );
       
        $expected_categories = "accessories\r\nclothing\r\ntest-cat";
        $this->assertEquals( $expected_categories, $saved_data['productmeta_categories'] );
        
        // Tags should not be changed.
        $expected_tags = 'a:1:{i:0;s:8:"test-tag";}';
        $this->assertEquals( $expected_tags, $saved_data['productmeta_tags'] );
    }

    /**
     * Detaching all tags submits an empty array; stored tags must clear (see PR #527).
     *
     * @return void
     */
    public function test_saving_empty_tags_array_clears_column() {
        $field_id = $this->insert_ppom_meta(
            array(
                $this->build_text_field( 'test_cat', 'Test Cat' ),
            ),
            0,
            array(
                'productmeta_name'       => 'Test Tags Clear',
                'productmeta_validation' => '',
                'productmeta_style'      => 'selector { }',
                'productmeta_categories' => 'accessories',
                'productmeta_tags'       => 'a:1:{i:0;s:8:"test-tag";}',
            )
        );

        NM_PersonalizedProduct_Admin::save_categories_and_tags( $field_id, array( 'accessories' ), array() );

        $saved_data = $this->get_ppom_meta_row( $field_id );

        $this->assertSame( 'accessories', $saved_data['productmeta_categories'] );
        $this->assertSame( '', $saved_data['productmeta_tags'] );
    }

	/**
	 * Pro-only fields are not registered after Pro is disabled, so the editor
	 * submits only a partial row shell. The save path must keep the stored row.
	 *
	 * @return void
	 */
	public function test_unavailable_pro_field_shell_preserves_existing_saved_definition() {
		if ( isset( \PPOM()->inputs['fonts'] ) ) {
			$this->markTestSkipped( 'Fonts input is registered; this regression requires the free-only unavailable-field path.' );
		}

		$field_id = $this->insert_ppom_meta(
			array(
				1 => array(
					'type'       => 'fonts',
					'title'      => 'Engraving Font',
					'data_name'  => 'engraving_font',
					'options'    => array(
						array(
							'dataname'  => 'roboto',
							'font_name' => 'Roboto',
						),
					),
					'conditions' => array(
						'visibility' => 'Show',
						'bound'      => 'All',
						'rules'      => array(
							array(
								'elements'       => 'variation_id',
								'operators'      => 'is',
								'element_values' => '123',
							),
						),
					),
					'status'     => 'on',
				),
			)
		);

		$method = new ReflectionMethod( \PPOM\Admin\Manager::class, 'preserve_unavailable_field_rows' );
		$method->setAccessible( true );

		$preserved = $method->invoke(
			null,
			array(
				1 => array(
					'type'   => 'fonts',
					'status' => '',
				),
			),
			$field_id
		);

		$this->assertSame( 'fonts', $preserved[1]['type'] );
		$this->assertSame( 'Engraving Font', $preserved[1]['title'] );
		$this->assertSame( 'engraving_font', $preserved[1]['data_name'] );
		$this->assertSame( 'roboto', $preserved[1]['options'][0]['dataname'] );
		$this->assertSame( 'Roboto', $preserved[1]['options'][0]['font_name'] );
		$this->assertSame( 'variation_id', $preserved[1]['conditions']['rules'][0]['elements'] );
		$this->assertSame( '', $preserved[1]['status'] );
	}
}
