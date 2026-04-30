<?php
/**
 * Registers admin REST routes for the React field modal on rest_api_init.
 *
 * @package PPOM
 * @subpackage Admin\FieldModal
 */

namespace PPOM\Admin\FieldModal;

/**
 * Hooks `rest_api_init` to register admin field-modal REST routes.
 *
 * @internal
 */
final class FieldModalRegistrar {

	/**
	 * Registers the `rest_api_init` callback.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Instantiates the REST controller and registers its routes.
	 *
	 * @return void
	 */
	public function register_routes() {
		$controller = new FieldModalRestController();
		$controller->register_routes();
	}
}
