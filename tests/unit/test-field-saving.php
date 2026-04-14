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
}
