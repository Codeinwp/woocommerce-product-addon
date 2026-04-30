<?php
/**
 * Simple service registry for the application shell.
 *
 * @package PPOM
 */

namespace PPOM\Core;

/**
 * Holds named services for composition and future modules.
 *
 * @since 33.0.19
 */
class ServiceRegistry {

	/**
	 * @var array<string, object>
	 */
	private $services = array();

	/**
	 * Stores a service under a string id.
	 *
	 * @param string $id      Service identifier.
	 * @param object $service Service instance.
	 * @return void
	 */
	public function set( string $id, $service ): void {
		$this->services[ $id ] = $service;
	}

	/**
	 * Retrieves a stored service or null.
	 *
	 * @param string $id Service identifier.
	 * @return object|null
	 */
	public function get( string $id ) {
		return $this->services[ $id ] ?? null;
	}

	/**
	 * Whether a service id is registered.
	 *
	 * @param string $id Service identifier.
	 * @return bool
	 */
	public function has( string $id ): bool {
		return isset( $this->services[ $id ] );
	}
}
