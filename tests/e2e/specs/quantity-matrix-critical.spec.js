/**
 * External dependencies
 */
import { readFileSync } from 'node:fs';
import path from 'node:path';

/**
 * WordPress dependencies
 */
import { test, expect } from '@wordpress/e2e-test-utils-playwright';

import { clearCart } from '../utils';

const SEEDED_GROUP_PATH = path.join(
	process.cwd(),
	'tests/e2e/fixtures/generated/quantity-matrix-group.json'
);

function readSeededQuantityMatrixGroup() {
	try {
		return JSON.parse( readFileSync( SEEDED_GROUP_PATH, 'utf8' ) );
	} catch ( error ) {
		throw new Error(
			`Missing quantity-matrix fixture data at ${ SEEDED_GROUP_PATH }. Run "bash ./bin/e2e-after-setup.sh" after starting wp-env.`,
			{ cause: error }
		);
	}
}

test.describe( 'Quantity Matrix', () => {
	test( '@critical quantity limits and matrix pricing stay aligned', async ( {
		page,
	} ) => {
		const seededGroup = readSeededQuantityMatrixGroup();

		expect( seededGroup.status ).toBe( 'success' );

		await clearCart( page );
		await page.goto( seededGroup.product_permalink );

		const nativeQuantityInput = page.locator( 'input[name="quantity"]' );
		const quantityInput = page.locator(
			`input[name="ppom[fields][${ seededGroup.quantity_field_id }][seat]"]`
		);
		const addToCartButton = page.locator( 'form.cart' ).getByRole(
			'button',
			{
				name: 'Add to cart',
				exact: true,
			}
		);

		await expect( nativeQuantityInput ).toHaveAttribute( 'min', '2' );
		await expect( nativeQuantityInput ).toHaveAttribute( 'max', '4' );
		await expect( nativeQuantityInput ).toHaveAttribute( 'step', '2' );

		await quantityInput.fill( '2' );
		await quantityInput.press( 'Tab' );
		await expect( nativeQuantityInput ).toHaveValue( '2' );

		await addToCartButton.click();
		await page.waitForLoadState( 'networkidle' );
		await page.goto( '/cart/' );
		await expect(
			page.getByRole( 'spinbutton', {
				name: `Quantity of ${ seededGroup.product_name } in your cart.`,
			} )
		).toHaveValue( '2' );
		await expect( page.locator( 'body' ) ).toContainText( '$16.00' );

		await clearCart( page );
		await page.goto( seededGroup.product_permalink );

		await quantityInput.fill( '4' );
		await quantityInput.press( 'Tab' );
		await expect( nativeQuantityInput ).toHaveValue( '4' );

		await addToCartButton.click();
		await page.waitForLoadState( 'networkidle' );
		await page.goto( '/cart/' );
		await expect(
			page.getByRole( 'spinbutton', {
				name: `Quantity of ${ seededGroup.product_name } in your cart.`,
			} )
		).toHaveValue( '4' );
		await expect( page.locator( 'body' ) ).toContainText( '$28.00' );
	} );
} );
