<?php
/**
 * Class Test_Field_Saving
 *
 * @package ppom-pro
 */

class Test_Field_Saving extends WP_UnitTestCase {

	/**
     * Test if the saved categories are in a backward compatible format and tags in a serialized format.
     */
    public function test_saving_categories_compatibilities() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . PPOM_TABLE_META;
        $data = array(
            'productmeta_name' => 'Test Multiple Categories',
            'productmeta_validation' => '',
            'dynamic_price_display' => 'no',
            'send_file_attachment' => '',
            'show_cart_thumb' => '',
            'aviary_api_key' => '',
            'productmeta_style' => 'selector { }',
            'productmeta_js' => '',
            'productmeta_categories' => "accessories\r\nclothing",
            'the_meta' => '{"1":{"type":"text","title":"Test Cat","data_name":"test_cat","description":"","placeholder":"","error_message":"","maxlength":"","minlength":"","default_value":"","price":"","class":"","input_mask":"","width":"12","visibility":"everyone","visibility_role":"","conditions":{"visibility":"Show","bound":"All","rules":[{"elements":"test_cat","operators":"is","element_values":""}]},"status":"on","ppom_id":"63"}}',
            'productmeta_created' => '2024-10-16 11:38:45',
            'productmeta_tags' => 'a:1:{i:0;s:8:"test-tag";}'
        );

        // Insert data into the table
        $result = $wpdb->insert($table_name, $data);
        $this->assertNotFalse( $result );
        
        $field_id = $wpdb->insert_id;

        NM_PersonalizedProduct_Admin::save_categories_and_tags( $field_id, ['accessories', 'clothing', 'test-cat'], false );
        
        $saved_data = $wpdb->get_row( $wpdb->prepare( "SELECT productmeta_categories, productmeta_tags FROM $table_name WHERE productmeta_id = %d", $field_id ), ARRAY_A );
       
        $expected_categories = "accessories\r\nclothing\r\ntest-cat";
        $this->assertEquals( $expected_categories, $saved_data['productmeta_categories'] );
        
        // Tags should not be changed.
        $expected_tags = 'a:1:{i:0;s:8:"test-tag";}';
        $this->assertEquals( $expected_tags, $saved_data['productmeta_tags'] );
    }
}
