import { postBootstrapAction } from './internal.js';

/**
 * Set allowlisted PPOM plugin settings for the current test.
 *
 * Values are reverted by the bootstrap reset path, so tests do not leak the
 * toggle into the rest of the suite.
 *
 * @param {import('@wordpress/e2e-test-utils-playwright').RequestUtils} requestUtils Authenticated request utils.
 * @param {Object<string, string>} settings Setting id => value ('' clears it).
 * @return {Promise<{ applied: Object<string, string>, conditions_mode: string }>} Applied settings and resolved conditions mode.
 */
export async function setPpomSettings( requestUtils, settings ) {
	return postBootstrapAction( requestUtils, 'ppom_e2e_set_ppom_settings', {
		settings,
	} );
}

/**
 * Enable or disable the "Legacy Conditions Script" option, which swaps
 * `ppom-conditions-v2.js` for the legacy `ppom-conditions.js` engine.
 *
 * @param {import('@wordpress/e2e-test-utils-playwright').RequestUtils} requestUtils Authenticated request utils.
 * @param {boolean} [enabled=true] Whether legacy conditions should be used.
 * @return {Promise<{ applied: Object<string, string>, conditions_mode: string }>} Applied settings and resolved conditions mode.
 */
export async function setLegacyConditionsScript(
	requestUtils,
	enabled = true
) {
	return setPpomSettings( requestUtils, {
		ppom_new_conditions: enabled ? 'yes' : '',
	} );
}
