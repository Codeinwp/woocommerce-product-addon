/**
 * WordPress dependencies
 */
import { test, expect } from '@wordpress/e2e-test-utils-playwright';

import {
	attachPpomGroupToProducts,
	buildQuantitiesField,
	buildSelectField,
	createPpomGroup,
	createSimpleProduct,
} from '../fixtures/index.js';

/**
 * Regression for Codeinwp/ppom-pro#547.
 *
 * A priced quantities field drives the order quantity. Select option prices
 * are one-off charges and must not be multiplied by that quantity — neither
 * in the frontend price table nor in the server-side cart total.
 */
test.describe( 'Select option price with quantities field', () => {
	async function setupProduct( requestUtils ) {
		const product = await createSimpleProduct( requestUtils, {
			regular_price: '10.00',
		} );
		const { ppomId } = await createPpomGroup( requestUtils, {
			groupName: 'Quantities Option Price',
			settings: { dynamic_price_hide: 'all_option' },
			fields: [
				buildQuantitiesField( {
					title: 'Years',
					dataName: 'years',
					options: [
						{
							label: 'year1',
							value: '_year1',
							overrides: { price: '100' },
						},
					],
				} ),
				buildSelectField( {
					title: 'Membership',
					dataName: 'membership',
					options: [
						{
							label: 'one time fee',
							value: 'one_time_fee',
							overrides: { price: '1200' },
						},
						{
							label: 'monthly',
							value: 'monthly',
							overrides: { price: '100' },
						},
					],
				} ),
			],
		} );
		await attachPpomGroupToProducts( requestUtils, {
			ppomId,
			productIds: [ product.id ],
		} );

		return product;
	}

	async function fillOptions( page ) {
		await page
			.locator( 'select#membership' )
			.selectOption( { value: 'one time fee' } );

		const qty = page.locator( 'input.ppom-quantity[data-label="year1"]' );
		await qty.fill( '2' );
		await qty.dispatchEvent( 'change' );
	}

	test( 'price table does not multiply the select option by the quantities total', async ( {
		page,
		requestUtils,
	} ) => {
		const product = await setupProduct( requestUtils );

		await page.goto( `/?p=${ product.id }` );
		await fillOptions( page );

		const table = page.locator( '#ppom-price-container' );

		// Quantities line scales with its own quantity: 100 x 2.
		await expect(
			table.locator( 'tr.ppom-quantities-price' )
		).toContainText( 'x 2' );

		// Select option is charged once: 1200 x 1, not 1200 x 2.
		const selectRow = table.locator( 'tr.ppom-variable-price' );
		await expect( selectRow ).toContainText( 'x 1' );
		await expect( selectRow ).toContainText( '1,200.00' );

		// Option total: 100 x 2 + 1200 = 1,400.
		await expect(
			table.locator( 'tr.ppom-option-total-price' )
		).toContainText( '1,400.00' );
	} );

	test( 'cart total charges the select option once', async ( {
		page,
		requestUtils,
	} ) => {
		// Room for the slow add-to-cart navigation plus the cart poll below.
		test.setTimeout( 240000 );

		const product = await setupProduct( requestUtils );

		await page.goto( `/?p=${ product.id }` );
		await fillOptions( page );

		// Slow wp-env: the submit navigation can exceed the default timeout.
		await page
			.locator( 'button.single_add_to_cart_button' )
			.click( { timeout: 60000 } );

		// Base 10 + quantities 100 x 2 + select 1200 = 1410.00 (minor units).
		// Polled with a short per-request timeout: the wp-env Store API
		// occasionally hangs on a single request. Sentinel return values keep
		// env failures distinguishable from a wrong total in the report.
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

					const cart = await response.json();
					return cart.totals?.total_items ?? 'totals-missing';
				},
				{ timeout: 120000, intervals: [ 3000 ] }
			)
			.toBe( '141000' );
	} );
} );
