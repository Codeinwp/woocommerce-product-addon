/**
 * External dependencies
 */
import { execFileSync } from 'node:child_process';

/**
 * WordPress dependencies
 */
import { test, expect } from '@wordpress/e2e-test-utils-playwright';

import { clearCart } from '../utils';

const RUN_SUFFIX = Date.now().toString();

function seedQuantityMatrixGroup() {
	const output = execFileSync(
		'./node_modules/.bin/wp-env',
		[
			'run',
			'cli',
			'wp',
			'eval-file',
			'./wp-content/plugins/woocommerce-product-addon/tests/e2e/fixtures/create-quantity-matrix-group.php',
			RUN_SUFFIX,
		],
		{
			cwd: process.cwd(),
			encoding: 'utf8',
		}
	).trim();
	const jsonLine = output
		.split( '\n' )
		.map( ( line ) => line.trim() )
		.filter( Boolean )
		.at( -1 );

	return JSON.parse( jsonLine );
}

test.describe( 'Quantity Matrix', () => {
	test( '@critical quantity limits and matrix pricing stay aligned', async ( {
		page,
	} ) => {
		const seededGroup = seedQuantityMatrixGroup();

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
				name: 'Quantity of Product 1 in your cart.',
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
				name: 'Quantity of Product 1 in your cart.',
			} )
		).toHaveValue( '4' );
		await expect( page.locator( 'body' ) ).toContainText( '$28.00' );
	} );
} );
