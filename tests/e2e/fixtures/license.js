import { postBootstrapAction } from './internal.js';

/**
 * Read the current PPOM E2E license fixture from the server.
 *
 * @param {import('@wordpress/e2e-test-utils-playwright').RequestUtils} requestUtils Authenticated request utils.
 * @return {Promise<{ status: string, plan: number }>}
 */
export async function getPpomLicenseFixture( requestUtils ) {
	return postBootstrapAction( requestUtils, 'ppom_e2e_read_license_fixture', {} );
}

/**
 * Control PPOM E2E license stubs (product_ppom_license_status / product_ppom_license_plan).
 *
 * @param {import('@wordpress/e2e-test-utils-playwright').RequestUtils} requestUtils Authenticated request utils.
 * @param {object} options
 * @param {boolean} options.valid When true, filters report a valid license.
 * @param {number} [options.plan=1] Plan tier 1 (Essential) through 3 (VIP) when valid.
 * @return {Promise<{ status: string, plan: number }>} Resolved fixture from the server.
 */
export async function setPpomLicenseFixture( requestUtils, { valid, plan = 1 } ) {
	return postBootstrapAction( requestUtils, 'ppom_e2e_set_license_fixture', {
		status: valid ? 'valid' : 'invalid',
		plan: String( plan ),
	} );
}
