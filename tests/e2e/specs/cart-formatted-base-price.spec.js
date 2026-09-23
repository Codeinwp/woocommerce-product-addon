/**
 * WordPress dependencies
 */
import { test, expect } from '@wordpress/e2e-test-utils-playwright';

/**
 * Internal dependencies
 */
import {
	attachPpomGroupToProducts,
	buildSelectField,
	createPpomGroup,
	createSimpleProduct,
	setFormattedBasePrice,
} from '../fixtures/index.js';

test.describe( 'Cart totals with a formatted base price', () => {
	test.afterEach( async ( { page, requestUtils } ) => {
		await setFormattedBasePrice( requestUtils, { enabled: false } );

		// The admin's cart persists across specs; later cart specs read items[0].
		const cart = await page.request
			.get( '/?rest_route=/wc/store/v1/cart' )
			.catch( () => null );
		const nonce = cart?.headers()?.nonce;
		if ( nonce ) {
			await page.request
				.delete( '/?rest_route=/wc/store/v1/cart/items', {
					headers: { Nonce: nonce },
				} )
				.catch( () => null );
		}
	} );

	/**
	 * A currency integration or price filter can hand PPOM the cart base price as a
	 * formatted string such as "€ 10.00". Totals used to die with a TypeError, which
	 * the crash reporter turned into a blank page. Regression test for #720.
	 */
	test( 'add to cart survives a non-numeric base price and charges the option', async ( {
		page,
		requestUtils,
	} ) => {
		// Room for the slow add-to-cart navigation plus the cart poll below.
		test.setTimeout( 240000 );

		await setFormattedBasePrice( requestUtils, { enabled: true } );

		const product = await createSimpleProduct( requestUtils, {
			regular_price: '10',
		} );
		const { ppomId } = await createPpomGroup( requestUtils, {
			groupName: 'Formatted Base Price',
			fields: [
				buildSelectField( {
					title: 'Gift wrap',
					dataName: 'gift_wrap',
					options: [
						{ label: 'No wrap', value: 'no_wrap' },
						{
							label: 'Premium wrap',
							value: 'premium_wrap',
							overrides: { price: '5' },
						},
					],
				} ),
			],
		} );

		await attachPpomGroupToProducts( requestUtils, {
			ppomId,
			productIds: [ product.id ],
		} );

		await page.goto( `/?p=${ product.id }` );
		await page
			.locator( 'select#gift_wrap' )
			.selectOption( { value: 'Premium wrap' } );

		// Slow wp-env: the submit navigation can exceed the default timeout.
		await page
			.locator( 'button.single_add_to_cart_button' )
			.click( { timeout: 60000 } );

		// The crash reporter turns the fatal into a blank page, so a negative check is
		// not enough: WooCommerce's added-to-cart notice must actually render.
		await expect(
			page.locator(
				'.woocommerce-message, .wc-block-components-notice-banner'
			)
		).toBeVisible( { timeout: 60000 } );

		// Base 10 recovered from "€ 10.00" + option 5 = 15.00 (minor units).
		await expect
			.poll(
				async () => {
					const response = await page.request
						.get( '/?rest_route=/wc/store/v1/cart', {
							timeout: 15000,
						} )
						.catch( () => null );

					if ( ! response || ! response.ok() ) {
						return 'cart-request-failed';
					}

					// A crashed request comes back as an empty 200 body.
					const cart = await response.json().catch( () => null );
					return cart?.totals?.total_items ?? 'cart-response-empty';
				},
				{ timeout: 120000, intervals: [ 3000 ] }
			)
			.toBe( '1500' );
	} );
} );
