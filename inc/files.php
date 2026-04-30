<?php
/**
 * File uploads and paths — compatibility wrappers.
 *
 * @package PPOM
 */

/**
 * @param string|false $sub_dir Subdirectory.
 * @return string|false
 */
function ppom_files_setup_get_directory( $sub_dir = false ) {
	return \PPOM\Files\Handler::files_setup_get_directory( $sub_dir );
}

/**
 * @param string|false $sub_dir Subdirectory.
 * @return string|false
 */
function ppom_get_dir_path( $sub_dir = false ) {
	return \PPOM\Files\Handler::get_dir_path( $sub_dir );
}

/**
 * @param bool $thumb Thumb URL.
 * @return string
 */
function ppom_get_dir_url( $thumb = false ) {
	return \PPOM\Files\Handler::get_dir_url( $thumb );
}

/**
 * @param string $file_path File full path.
 * @return bool
 */
function ppom_is_file_image( $file_path ) {
	return \PPOM\Files\Handler::is_file_image( $file_path );
}

/**
 * @param string   $file_name File name.
 * @param int      $product_id Product ID.
 * @param bool     $cropped Cropped.
 * @param int|null $size Size.
 * @return string
 */
function ppom_create_thumb_for_meta( $file_name, $product_id, $cropped = false, $size = null ) {
	return \PPOM\Files\Handler::create_thumb_for_meta( $file_name, $product_id, $cropped, $size );
}

/**
 * @param string $file_name File name.
 * @param string $file_ext Extension.
 * @return string
 */
function ppom_create_unique_file_name( $file_name, $file_ext ) {
	return \PPOM\Files\Handler::create_unique_file_name( $file_name, $file_ext );
}

/**
 * @param string $file_path_to_read Source path.
 * @param string $ppom_chunk_file_path Chunk path.
 * @param string $mode Write mode.
 * @return false|string
 */
function ppom_create_chunk_file( $file_path_to_read, $ppom_chunk_file_path, $mode ) {
	return \PPOM\Files\Handler::create_chunk_file( $file_path_to_read, $ppom_chunk_file_path, $mode );
}

/**
 * @return void
 */
function ppom_upload_file() {
	\PPOM\Files\Handler::upload_file();
}

/**
 * @return void
 */
function ppom_delete_file() {
	\PPOM\Files\Handler::delete_file();
}

/**
 * @param string $file_path Directory path.
 * @param string $image_name Image file name.
 * @param int    $thumb_size Thumb size.
 * @return string|false
 */
function ppom_create_image_thumb( $file_path, $image_name, $thumb_size ) {
	return \PPOM\Files\Handler::create_image_thumb( $file_path, $image_name, $thumb_size );
}

/**
 * @param string $file_name File name.
 * @param int    $order_id Order ID.
 * @param int    $product_id Product ID.
 * @return string
 */
function ppom_get_file_download_url( $file_name, $order_id, $product_id ) {
	return \PPOM\Files\Handler::get_file_download_url( $file_name, $order_id, $product_id );
}

/**
 * @param string               $file_name File name.
 * @param array<string, mixed> $settings Field meta.
 * @return string
 */
function ppom_uploaded_file_preview( $file_name, $settings ) {
	return \PPOM\Files\Handler::uploaded_file_preview( $file_name, $settings );
}

/**
 * @param string $file_name File name.
 * @return string
 */
function ppom_files_trim_name( $file_name ) {
	return \PPOM\Files\Handler::files_trim_name( $file_name );
}

/**
 * @param string $data Data URL payload.
 * @param string $file_name Target file name.
 * @return void
 */
function ppom_save_data_url_to_image( $data, $file_name ) {
	\PPOM\Files\Handler::save_data_url_to_image( $data, $file_name );
}

/**
 * @param array<string, mixed> $settings Field settings.
 * @return array<string, mixed>
 */
function ppom_get_croppie_options( $settings ) {
	return \PPOM\Files\Handler::get_croppie_options( $settings );
}

/**
 * @param array<string, mixed> $settings Field settings.
 * @return array<string, mixed>
 */
function ppom_get_viewport_settings( $settings ) {
	return \PPOM\Files\Handler::get_viewport_settings( $settings );
}

/**
 * @return void
 */
function ppom_files_removed_unused_images() {
	\PPOM\Files\Handler::files_removed_unused_images();
}

/**
 * @param string $date1 Date one.
 * @param string $date2 Date two.
 * @return int
 */
function ppom_files_uploaded_days_count( $date1, $date2 ) {
	return \PPOM\Files\Handler::files_uploaded_days_count( $date1, $date2 );
}

/**
 * @param string     $file_name File name.
 * @param int        $product_id Product ID.
 * @param array|null $cart_item Cart item.
 * @return string
 */
function ppom_file_get_name( $file_name, $product_id, $cart_item = null ) {
	return \PPOM\Files\Handler::file_get_name( $file_name, $product_id, $cart_item );
}

class_alias( \PPOM\Files\UploadErrors::class, 'UploadFileErrors' );
