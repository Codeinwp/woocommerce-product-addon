<?php
/**
 * Hook registration contract for PPOM modules.
 *
 * @package PPOM
 */

namespace PPOM\Core;

/**
 * Registers WordPress or WooCommerce hooks owned by a module.
 *
 * @since 33.0.19
 */
interface RegisterHooks {

	/**
	 * Register actions and filters owned by this module.
	 *
	 * @return void
	 */
	public function register(): void;
}
