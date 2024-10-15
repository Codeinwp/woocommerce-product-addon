<?php
/**
 * Class Test_File_Upload
 *
 * @package ppom-pro
 */

class Test_File_Upload extends WP_UnitTestCase {

	/**
     * Test ppom_create_unique_file_name function.
     */
    public function test_ppom_create_unique_file_name() {
        $file_name = 'example';
        $file_ext = 'jpg';

        $unique_file_name = ppom_create_unique_file_name($file_name, $file_ext);

        // Check if the file name contains the original name
        $this->assertStringContainsString($file_name, $unique_file_name);

        // Check if the file name contains the extension
        $this->assertStringContainsString($file_ext, $unique_file_name);

        // Check if the file name contains the unique hash
        $this->assertMatchesRegularExpression('/\.[a-zA-Z0-9+\/=]+\./', $unique_file_name);
    }

    /**
     * Test ppom_create_unique_file_name generates different names on subsequent calls.
     */
    public function test_ppom_create_unique_file_name_is_unique() {
        $file_name = 'example';
        $file_ext = 'jpg';

        $unique_file_name_1 = ppom_create_unique_file_name($file_name, $file_ext);
        $unique_file_name_2 = ppom_create_unique_file_name($file_name, $file_ext);

        // Check if the two generated file names are different
        $this->assertNotEquals($unique_file_name_1, $unique_file_name_2);
    }

    /**
     * Test ppom_create_chunk_file function.
     */
    public function test_ppom_create_chunk_file_success() {
        $file_path_to_read = tempnam(sys_get_temp_dir(), 'read');
        $ppom_chunk_file_path = tempnam(sys_get_temp_dir(), 'chunk');
        $mode = 'wb';


        file_put_contents($file_path_to_read, 'test data');

        $error = ppom_create_chunk_file($file_path_to_read, $ppom_chunk_file_path, $mode);

        $this->assertFalse($error);

        // Check if the chunk file contains the correct data
        $this->assertFileExists($ppom_chunk_file_path);
        $this->assertEquals('test data', file_get_contents($ppom_chunk_file_path));

        // Clean up
        unlink($file_path_to_read);
        unlink($ppom_chunk_file_path);
    }

    public function test_ppom_create_chunk_file_input_error() {
        $file_path_to_read = 'non_existent_file';
        $ppom_chunk_file_path = tempnam(sys_get_temp_dir(), 'chunk');
        $mode = 'wb';

        $error = @ppom_create_chunk_file($file_path_to_read, $ppom_chunk_file_path, $mode);

        $this->assertEquals(UploadFileErrors::OPEN_INPUT, $error);

        // Clean up
        unlink($ppom_chunk_file_path);
    }

    public function test_ppom_create_chunk_file_output_error() {
        $file_path_to_read = tempnam(sys_get_temp_dir(), 'read');
        $ppom_chunk_file_path = '/invalid/path/chunk';
        $mode = 'wb';


        file_put_contents($file_path_to_read, 'test data');
        $error = @ppom_create_chunk_file($file_path_to_read, $ppom_chunk_file_path, $mode);

        $this->assertEquals(UploadFileErrors::OPEN_OUTPUT, $error);

        // Clean up
        unlink($file_path_to_read);
    }
}
