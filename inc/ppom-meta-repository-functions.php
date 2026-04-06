<?php
/**
 * Procedural accessor for PPOM_Meta_Repository (class lives in classes/).
 *
 * @package PPOM
 */

/**
 * Lazy accessor for the shared repository instance.
 *
 * @return PPOM_Meta_Repository
 */
function ppom_meta_repository() {
	return PPOM_Meta_Repository::instance();
}
