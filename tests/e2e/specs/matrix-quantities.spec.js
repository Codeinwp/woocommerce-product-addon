/**
 * WordPress dependencies
 */
import { test, expect } from '@wordpress/e2e-test-utils-playwright';

import {
	attachPpomGroupToProducts,
	buildPriceMatrixField,
	buildQuantitiesField,
	createPpomGroup,
	createSimpleProduct,
} from '../fixtures/index.js';

/**
 * Regression for Codeinwp/ppom-pro#542.
 *
 * Priced quantities fields used to disappear from the product page when a
 * price matrix field was attached (a broken conflict guard rendered nothing).
 * They must stay visible, with their own option prices blanked so the matrix
 * drives pricing.
 */
test.describe( 'Quantities with price matrix', () => {
	test( 'priced quantities fields stay visible when a price matrix is attached', async ( {
		page,
		requestUtils,
	} ) => {
		const product = await createSimpleProduct( requestUtils );
		const { ppomId } = await createPpomGroup( requestUtils, {
			groupName: 'Matrix Quantities',
			fields: [
				buildQuantitiesField( {
					title: 'Kits',
					dataName: 'kits',
					options: [
						{
							label: 'Kit A',
							value: '_kit_a',
							overrides: { price: '999' },
						},
					],
				} ),
				buildPriceMatrixField( {
					title: 'Sets',
					dataName: 'sets',
					options: [
						{ option: '1-10', price: '80', id: 'r1' },
						{ option: '11-20', price: '72', id: 'r2' },
					],
				} ),
			],
		} );
		await attachPpomGroupToProducts( requestUtils, {
			ppomId,
			productIds: [ product.id ],
		} );

		await page.goto( `/?p=${ product.id }` );

		// The quantities input renders despite the attached price matrix.
		const qty = page.locator( 'input.ppom-quantity[data-label="Kit A"]' );
		await expect( qty ).toBeVisible();

		// Its own option price is zeroed — the matrix drives pricing.
		await expect( qty ).toHaveAttribute( 'data-price', '0' );
	} );
} );
