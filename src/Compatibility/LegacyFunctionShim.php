<?php
/**
 * Compatibility helpers for code migrating from global functions to services.
 *
 * @package PPOM
 */

namespace PPOM\Compatibility;

use PPOM\Data\FieldGroupRepository;

/**
 * @since 33.0.19
 */
final class LegacyFunctionShim {

	/**
	 * Preferred accessor for field-group table operations from new code.
	 *
	 * @return FieldGroupRepository
	 */
	public static function field_groups(): FieldGroupRepository {
		return FieldGroupRepository::instance();
	}
}
