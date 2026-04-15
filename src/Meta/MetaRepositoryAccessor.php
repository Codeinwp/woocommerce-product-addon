<?php
/**
 * Accessor for the shared PPOM_Meta_Repository instance.
 *
 * @package PPOM
 */

namespace PPOM\Meta;

use PPOM_Meta_Repository;

/**
 * @internal
 */
final class MetaRepositoryAccessor {

	/**
	 * Lazy accessor for the shared repository instance.
	 *
	 * @return PPOM_Meta_Repository
	 */
	public static function instance() {
		return PPOM_Meta_Repository::instance();
	}
}
