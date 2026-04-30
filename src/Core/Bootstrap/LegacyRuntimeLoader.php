<?php
/**
 * Loads legacy procedural and class files in the historical require order.
 *
 * @package PPOM
 */

namespace PPOM\Core\Bootstrap;

/**
 * @since 33.0.19
 */
final class LegacyRuntimeLoader {

	/**
	 * Require runtime files (same sequence as the original bootstrap).
	 *
	 * @return void
	 */
	public static function load(): void {
		require_once PPOM_PATH . '/inc/functions.php';
		require_once PPOM_PATH . '/inc/validation.php';
		require_once PPOM_PATH . '/inc/deprecated.php';
		require_once PPOM_PATH . '/inc/arrays.php';
		require_once PPOM_PATH . '/inc/hooks.php';
		require_once PPOM_PATH . '/inc/woocommerce.php';
		require_once PPOM_PATH . '/inc/admin.php';
		require_once PPOM_PATH . '/inc/files.php';
		require_once PPOM_PATH . '/inc/nmInput.class.php';
		require_once PPOM_PATH . '/inc/prices.php';

		require_once PPOM_PATH . '/classes/attach-popup/container-item.class.php';
		require_once PPOM_PATH . '/classes/attach-popup/container-view.class.php';
		require_once PPOM_PATH . '/classes/attach-popup/select-component.class.php';

		require_once PPOM_PATH . '/classes/input.class.php';
		require_once PPOM_PATH . '/classes/fields.class.php';
		require_once PPOM_PATH . '/classes/template-library.class.php';
		require_once PPOM_PATH . '/classes/ppom.class.php';
		require_once PPOM_PATH . '/classes/class-ppom-meta-repository.php';
		require_once PPOM_PATH . '/inc/ppom-meta-repository-functions.php';
		require_once PPOM_PATH . '/classes/plugin.class.php';
		require_once PPOM_PATH . '/classes/scripts.class.php';
		require_once PPOM_PATH . '/classes/frontend-scripts.class.php';
		require_once PPOM_PATH . '/backend/settings-panel.class.php';
		require_once PPOM_PATH . '/backend/options.php';
		require_once PPOM_PATH . '/inc/rest.class.php';

		require_once PPOM_PATH . '/classes/form.class.php';
		require_once PPOM_PATH . '/classes/input-meta.class.php';
		require_once PPOM_PATH . '/classes/legacy-meta.class.php';
		require_once PPOM_PATH . '/classes/integrations/elementor/elementor.class.php';
	}
}
