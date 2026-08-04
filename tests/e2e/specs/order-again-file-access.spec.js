/**
 * WordPress dependencies
 */
import { test, expect } from '@wordpress/e2e-test-utils-playwright';

import {
	attachPpomGroupToProducts,
	buildFileField,
	createPpomGroup,
	createSimpleProduct,
	setOrderStatus,
	setupCheckout,
} from '../fixtures/index.js';

const FIELD_ID = 'order_again_upload';

/**
 * Build a My Account view-order URL that works with any permalink structure.
 *
 * @param {string} myAccountUrl My Account page permalink.
 * @param {number} orderId      Order ID.
 *
 * @return {string}
 */
function viewOrderUrl( myAccountUrl, orderId ) {
	if ( myAccountUrl.includes( '?' ) ) {
		return `${ myAccountUrl }&view-order=${ orderId }`;
	}

	return `${ myAccountUrl }view-order/${ orderId }/`;
}

/**
 * Place a Cash on Delivery order for the current session cart via the
 * Store API, which shares cookies with the page session.
 *
 * @param {import('@playwright/test').Page} page Playwright page.
 *
 * @return {Promise<{orderId: number, status: number}>}
 */
async function placeStoreApiOrder( page ) {
	const result = await page.evaluate( async () => {
		// rest_route works regardless of the permalink structure.
		const cartResponse = await fetch( '/?rest_route=/wc/store/v1/cart', {
			credentials: 'same-origin',
		} );
		const nonce = cartResponse.headers.get( 'Nonce' );

		const address = {
			first_name: 'PPOM',
			last_name: 'Tester',
			address_1: '1 Test Street',
			city: 'New York',
			state: 'NY',
			postcode: '10001',
			country: 'US',
			email: 'ppom-e2e@example.com',
			phone: '555-0100',
		};

		const response = await fetch( '/?rest_route=/wc/store/v1/checkout', {
			method: 'POST',
			credentials: 'same-origin',
			headers: {
				'Content-Type': 'application/json',
				Nonce: nonce,
			},
			body: JSON.stringify( {
				billing_address: address,
				shipping_address: address,
				payment_method: 'cod',
			} ),
		} );

		const raw = await response.text();
		let data = null;
		try {
			data = JSON.parse( raw.slice( raw.indexOf( '{' ) ) );
		} catch ( error ) {
			data = null;
		}

		return {
			status: response.status,
			orderId: data?.order_id ?? 0,
			message: data?.message ?? raw.slice( 0, 300 ),
		};
	} );

	expect(
		result.status,
		`Store API checkout failed: ${ result.message }`
	).toBe( 200 );
	expect( result.orderId ).toBeGreaterThan( 0 );

	return result;
}

/**
 * Assert the order view page renders a working download link that points at
 * the order's own confirmed upload directory.
 *
 * @param {import('@playwright/test').Page} page         Playwright page.
 * @param {string}                          myAccountUrl My Account page permalink.
 * @param {number}                          orderId      Order to inspect.
 *
 * @return {Promise<string>} The download URL.
 */
async function expectWorkingFileLink( page, myAccountUrl, orderId ) {
	await page.goto( viewOrderUrl( myAccountUrl, orderId ) );

	const link = page.locator(
		`a[href*="ppom_files/confirmed/${ orderId }/"]`
	);
	await expect( link.first() ).toBeVisible();

	const href = await link.first().getAttribute( 'href' );
	const fetchStatus = await page.evaluate( async ( url ) => {
		const response = await fetch( url, { credentials: 'same-origin' } );
		return response.status;
	}, href );

	expect( fetchStatus ).toBe( 200 );

	return href;
}

test.describe( 'Order Again keeps uploaded files accessible', () => {
	test( 'both the original and the re-ordered order keep working file links', async ( {
		page,
		requestUtils,
	} ) => {
		const checkoutSetup = await setupCheckout( requestUtils );
		const myAccountUrl = checkoutSetup?.myaccount_url;
		expect(
			myAccountUrl,
			`setupCheckout returned: ${ JSON.stringify( checkoutSetup ) }`
		).toBeTruthy();

		const product = await createSimpleProduct( requestUtils, {
			virtual: true,
		} );
		const { ppomId } = await createPpomGroup( requestUtils, {
			groupName: 'Order Again File Access',
			fields: [
				buildFileField( {
					title: 'Upload Your Design',
					dataName: FIELD_ID,
					file_size: '1mb',
					files_allowed: '1',
					file_types: 'txt',
				} ),
			],
		} );
		await attachPpomGroupToProducts( requestUtils, {
			ppomId,
			productIds: [ product.id ],
		} );

		// Upload a file through the real plupload flow.
		await page.goto( `/?p=${ product.id }` );
		await page.waitForSelector( `#selectfiles-${ FIELD_ID }` );

		const fileChooserPromise = page.waitForEvent( 'filechooser' );
		await page.click( `#selectfiles-${ FIELD_ID }` );
		const fileChooser = await fileChooserPromise;
		await fileChooser.setFiles( {
			name: 'design.txt',
			mimeType: 'text/plain',
			buffer: Buffer.from( 'ppom order again e2e' ),
		} );

		// The hidden field appears once the AJAX upload completes.
		await page.waitForSelector(
			`input[name^="ppom[fields][${ FIELD_ID }]"]`,
			{ state: 'attached' }
		);

		// Add to cart and place the first order.
		await page.click( 'button.single_add_to_cart_button' );
		await page.waitForSelector( '.woocommerce-message' );

		const { orderId: firstOrderId } = await placeStoreApiOrder( page );

		// The first order owns its upload.
		const firstHref = await expectWorkingFileLink(
			page,
			myAccountUrl,
			firstOrderId
		);

		// Order Again requires a completed order.
		await setOrderStatus( requestUtils, firstOrderId, 'completed' );

		await page.goto( viewOrderUrl( myAccountUrl, firstOrderId ) );
		await page.click( 'a[href*="order_again"]' );
		await page.waitForSelector( '.woocommerce-cart-form, .wp-block-woocommerce-cart, .woocommerce-message, .wc-block-components-notice-banner' );

		// Rendering the original order mid-reorder must not consume the
		// restored upload the pending cart references.
		await expectWorkingFileLink( page, myAccountUrl, firstOrderId );

		const { orderId: secondOrderId } = await placeStoreApiOrder( page );
		expect( secondOrderId ).not.toBe( firstOrderId );

		// The re-ordered order gets its own working copy (previously an
		// empty href and an empty confirmed directory).
		const secondHref = await expectWorkingFileLink(
			page,
			myAccountUrl,
			secondOrderId
		);
		expect( secondHref ).not.toBe( firstHref );

		// And the original order still resolves after everything.
		await expectWorkingFileLink( page, myAccountUrl, firstOrderId );
	} );
} );
