<?php
/**
 * Unit tests for data-name validation functionality.
 *
 * @package ppom-pro
 */

class Test_DataName_Validation extends PPOM_Test_Case {

	/**
	 * Creates a mock PPOM_Meta instance with test fields.
	 *
	 * @param array $fields Array of field definitions.
	 * @return PPOM_Meta Mock instance with populated fields.
	 */
	private function create_ppom_meta_with_fields( $fields ) {
		$mock = $this->getMockBuilder( 'PPOM_Meta' )
			->disableOriginalConstructor()
			->onlyMethods( array() )
			->getMock();

		// Use reflection to set protected/private properties
		$reflection = new ReflectionClass( 'PPOM_Meta' );
		$fields_property = $reflection->getProperty( 'fields' );
		$fields_property->setAccessible( true );
		$fields_property->setValue( $mock, $fields );

		return $mock;
	}

	/**
	 * Test basic validation scenarios: no fields, empty, single, multiple unique, and duplicates.
	 *
	 * @return void
	 */
	public function test_basic_validation_scenarios() {
		// No fields
		$mock = $this->create_ppom_meta_with_fields( null );
		$this->assertFalse( $mock->has_unique_datanames() );

		// Empty fields array
		$mock = $this->create_ppom_meta_with_fields( array() );
		$this->assertFalse( $mock->has_unique_datanames() );

		// Single valid field
		$fields = array(
			array(
				'type'      => 'text',
				'data_name' => 'customer_name',
			),
		);
		$mock = $this->create_ppom_meta_with_fields( $fields );
		$this->assertTrue( $mock->has_unique_datanames() );

		// Multiple unique fields
		$fields = array(
			array(
				'type'      => 'text',
				'data_name' => 'first_name',
			),
			array(
				'type'      => 'text',
				'data_name' => 'last_name',
			),
			array(
				'type'      => 'email',
				'data_name' => 'customer_email',
			),
		);
		$mock = $this->create_ppom_meta_with_fields( $fields );
		$this->assertTrue( $mock->has_unique_datanames() );

		// Duplicate data_names
		$fields = array(
			array(
				'type'      => 'text',
				'data_name' => 'customer_name',
			),
			array(
				'type'      => 'text',
				'data_name' => 'customer_email',
			),
			array(
				'type'      => 'text',
				'data_name' => 'customer_name',
			),
		);
		$mock = $this->create_ppom_meta_with_fields( $fields );
		$this->assertFalse( $mock->has_unique_datanames() );
	}

	/**
	 * Test handling of empty or whitespace-only data_name values, which should be considered invalid.
	 *
	 * @return void
	 */
	public function test_empty_data_name() {
		$fields = array(
			// Missing data_name
			array(
				'type' => 'text',
			),
			array(
				'type'      => 'text',
				'data_name' => '',
			),
			array(
				'type'      => 'text',
				'data_name' => '   ',
			),
			array(
				'type'      => 'text',
				'data_name' => 'customer_name',
			),
			array(
				'type'      => 'email',
				'data_name' => 'email_address',
			),
		);

		$mock = $this->create_ppom_meta_with_fields( $fields );
		$this->assertTrue( $mock->has_unique_datanames() );
	}

    /**
     * Test that excluded field types (pricematrix, collapse, section, divider) are not included in uniqueness checks.
     *
     * @return void
     */
    public function test_excluded_field_types() {
        $fields = array(
            array(
                'type' => 'pricematrix',
                'data_name' => 'price_matrix',
            ),
            array(
                'type' => 'collapse',
                'data_name' => 'collapse_section',
            ),
            array(
                'type' => 'section',
                'data_name' => 'section_header',
            ),
            array(
                'type' => 'divider',
                'data_name' => 'divider_line',
            ),
            array(
                'type' => 'text',
                'data_name' => 'customer_name',
            ),
        );

        $mock = $this->create_ppom_meta_with_fields( $fields );
        $this->assertTrue( $mock->has_unique_datanames() );
    }
}
