/**
 * WordPress dependencies
 */
import { test, expect } from '@wordpress/e2e-test-utils-playwright';

import {
	attachPpomGroupToProducts,
	buildTextField,
	createPpomGroup,
	createSimpleProduct,
} from '../fixtures/index.js';

test.describe( 'Cart Fee Price – formatted price strings', () => {
	/**
	 * Verifies that a text field whose addon price is prefixed with a currency
	 * symbol (e.g. "$10") can be added to the cart without a PHP fatal error.
	 *
	 * Regression for: TypeError: Unsupported operand types: int + string in
	 * inc/prices.php when ppom_price_get_cart_fee_total() processes a
	 * non-numeric price string.
	 */
	test( 'adds to cart without fatal error when cart-fee price has a currency symbol', async ( {
		page,
		requestUtils,
	} ) => {
		const fieldId = 'cart_fee_text_field';
		const product = await createSimpleProduct( requestUtils );

		const { ppomId } = await createPpomGroup( requestUtils, {
			groupName: 'Cart Fee Currency Symbol Group',
			fields: [
				buildTextField( {
					title: 'Your note',
					dataName: fieldId,
					price: '$10',
					onetime: 'on',
				} ),
			],
		} );

		await attachPpomGroupToProducts( requestUtils, {
			ppomId,
			productIds: [ product.id ],
		} );

		// Visit the product page and fill the text field.
		await page.goto( `/?p=${ product.id }` );

		const textInput = page.locator(
			`input[name="ppom[fields][${ fieldId }]"]`
		);
		await expect( textInput ).toBeVisible();
		await textInput.fill( 'Hello world' );

		// Add to cart – the page must not produce a PHP fatal error.
		await page.locator( 'button.single_add_to_cart_button' ).click();

		const successNotice = page.locator(
			'.woocommerce-message, .wc-block-components-notice-banner'
		);
		const cartShell = page.locator(
			'.woocommerce-cart-form, .wp-block-woocommerce-cart'
		);

		// WooCommerce may redirect to the cart, refresh the product page with an
		// "added-to-cart" param, or render an inline success notice. Consider any
		// of these a successful add-to-cart signal to avoid flakiness in headless
		// environments.
		await Promise.race( [
			successNotice.waitFor( { state: 'visible', timeout: 15000 } ),
			cartShell.waitFor( { state: 'visible', timeout: 15000 } ),
			page.waitForTimeout( 3000 ),
		] );

		// Navigate to the cart and confirm the page loads without a fatal error.
		await page.goto( '/cart/' );

		const cartEmptyState = page.locator(
			'.cart-empty, .wc-block-cart__empty-cart-view'
		);
		// The cart page must render either the cart table/block or the empty
		// state — a PHP fatal would prevent WooCommerce markup from rendering.
		await expect(
			page.locator(
				'.woocommerce-cart-form, .wp-block-woocommerce-cart, .cart-empty, .wc-block-cart__empty-cart-view'
			)
		).toBeVisible( { timeout: 15000 } );
	} );
} );
