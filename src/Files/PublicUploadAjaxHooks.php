<?php
/**
 * Guest-accessible and logged-in upload/delete/validation AJAX hooks.
 *
 * @package PPOM
 */

namespace PPOM\Files;

/**
 * @since 33.0.19
 */
final class PublicUploadAjaxHooks {

	/**
	 * @return void
	 */
	public static function register(): void {
		add_action( 'wp_ajax_nopriv_ppom_upload_file', 'ppom_upload_file' );
		add_action( 'wp_ajax_ppom_upload_file', 'ppom_upload_file' );
		add_action( 'wp_ajax_nopriv_ppom_delete_file', 'ppom_delete_file' );
		add_action( 'wp_ajax_ppom_delete_file', 'ppom_delete_file' );

		add_action( 'wp_ajax_ppom_ajax_validation', 'ppom_woocommerce_ajax_validate' );
		add_action( 'wp_ajax_nopriv_ppom_ajax_validation', 'ppom_woocommerce_ajax_validate' );
	}
}
