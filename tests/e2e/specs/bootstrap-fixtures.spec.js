import { request as playwrightRequest } from '@playwright/test';
import { test, expect } from '@wordpress/e2e-test-utils-playwright';

import {
	attachPpomGroupToProducts,
	buildTextField,
	createPpomGroup,
	createProductVariation,
	createVariableProduct,
} from '../fixtures/index.js';
import { getE2EBootstrapNonce } from '../fixtures/internal.js';

async function postAjax( requestContext, params ) {
	const response = await requestContext.fetch( 'wp-admin/admin-ajax.php', {
		method: 'POST',
		failOnStatusCode: false,
		headers: {
			'content-type':
				'application/x-www-form-urlencoded; charset=UTF-8',
		},
		data: params.toString(),
	} );

	return {
		response,
		payload: await response.json(),
	};
}

test.describe( 'Bootstrap Fixtures', () => {
	test( 'creates a variable product fixture and renders PPOM fields', async ( {
		page,
		requestUtils,
	} ) => {
		const suffix = Date.now();
		const product = await createVariableProduct( requestUtils, {
			name: `Fixture Variable Product ${ suffix }`,
			attributes: [
				{
					name: 'Size',
					options: [ 'Small', 'Large' ],
				},
			],
			defaultAttributes: {
				size: 'Small',
			},
		} );

		await createProductVariation( requestUtils, {
			productId: product.id,
			regular_price: '14.99',
			attributes: {
				size: 'Small',
			},
		} );

		const { ppomId } = await createPpomGroup( requestUtils, {
			groupName: `Variable Product Group ${ suffix }`,
			fields: [
				buildTextField( {
					title: 'Monogram',
					dataName: `monogram_${ suffix }`,
				} ),
			],
		} );

		await attachPpomGroupToProducts( requestUtils, {
			ppomId,
			productIds: [ product.id ],
		} );

		await page.goto( `/?p=${ product.id }` );

		const variationSelect = page.locator( 'select[name="attribute_size"]' );

		await expect( variationSelect ).toBeVisible();
		await variationSelect.selectOption( 'Small' );

		await expect( page.locator( 'button.single_add_to_cart_button' ) ).toBeEnabled();
		await expect( page.getByLabel( 'Monogram' ) ).toBeVisible();
	} );

	test( 'enforces bootstrap nonce and capability checks', async ( {
		requestUtils,
	}, testInfo ) => {
		const validNonce = await getE2EBootstrapNonce( requestUtils );
		const successParams = new URLSearchParams();

		successParams.append( 'action', 'ppom_e2e_create_product_category' );
		successParams.append( '_ajax_nonce', validNonce );
		successParams.append( 'name', `Bootstrap Category ${ Date.now() }` );

		const successResult = await postAjax(
			requestUtils.request,
			successParams
		);

		expect( successResult.response.ok() ).toBeTruthy();
		expect( successResult.payload?.success ).toBeTruthy();
		expect( successResult.payload?.data?.id ).toBeGreaterThan( 0 );

		const invalidNonceParams = new URLSearchParams();

		invalidNonceParams.append( 'action', 'ppom_e2e_create_product_category' );
		invalidNonceParams.append( '_ajax_nonce', 'invalid-nonce' );
		invalidNonceParams.append(
			'name',
			`Invalid Nonce Category ${ Date.now() }`
		);

		const invalidNonceResult = await postAjax(
			requestUtils.request,
			invalidNonceParams
		);

		expect( invalidNonceResult.response.status() ).toBe( 403 );
		expect( invalidNonceResult.payload?.success ).toBeFalsy();
		expect( invalidNonceResult.payload?.data?.message ).toContain(
			'Invalid or missing PPOM E2E bootstrap nonce'
		);

		const baseURL =
			typeof testInfo.project.use.baseURL === 'string'
				? testInfo.project.use.baseURL
				: process.env.WP_BASE_URL;

		if ( ! baseURL ) {
			throw new Error( 'Playwright baseURL is required for bootstrap auth checks.' );
		}

		const anonymousRequest = await playwrightRequest.newContext( {
			baseURL,
			storageState: {
				cookies: [],
				origins: [],
			},
		} );

		try {
			const response = await anonymousRequest.fetch(
				'wp-admin/admin-ajax.php?action=ppom_e2e_get_nonce',
				{
					failOnStatusCode: false,
				}
			);
			const payload = await response.json();

			expect( response.status() ).toBe( 403 );
			expect( payload?.success ).toBeFalsy();
			expect( payload?.data?.message ).toContain( 'not allowed' );
		} finally {
			await anonymousRequest.dispose();
		}
	} );
} );
