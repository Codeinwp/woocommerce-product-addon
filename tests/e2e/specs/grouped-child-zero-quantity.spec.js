/**
 * WordPress dependencies
 */
import { test, expect } from '@wordpress/e2e-test-utils-playwright';

import {
	createGroupedProduct,
	createSimpleProducts,
} from '../fixtures/index.js';

/**
 * Regression for Codeinwp/woocommerce-product-addon#719.
 *
 * Grouped children (like Mix and Match children) render a quantity input
 * with a zero minimum and an empty value: "0" means "not selected". PPOM's
 * quantity-input filter must not raise that to 1 on products it does not
 * configure.
 */
test.describe( 'Grouped child quantity inputs', () => {
	test( 'keep a zero minimum and empty value when PPOM has no limits', async ( {
		page,
		requestUtils,
	} ) => {
		const children = await createSimpleProducts( requestUtils, [
			{ regular_price: '10.00' },
			{ regular_price: '10.00' },
			{ regular_price: '10.00' },
		] );
		const grouped = await createGroupedProduct( requestUtils, {
			childIds: children.map( ( child ) => child.id ),
		} );
		expect( grouped.children ).toHaveLength( 3 );

		await page.goto( `/?p=${ grouped.id }` );

		for ( const child of children ) {
			const qty = page.locator(
				`input[name="quantity[${ child.id }]"]`
			);
			await expect( qty ).toHaveAttribute( 'min', '0' );
			await expect( qty ).toHaveValue( '' );
		}
	} );
} );
