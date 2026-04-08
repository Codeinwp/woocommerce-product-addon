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

		// WooCommerce redirects to the product page with a "?added-to-cart" query
		// param or shows an inline notice. Either way, confirm no fatal occurred.
		await expect(
			page.locator( '.woocommerce-message, .wc-block-components-notice-banner' )
		).toBeVisible( { timeout: 15000 } );

		// Navigate to the cart and confirm the page loads without a fatal error.
		await page.goto( '/cart/' );

		// The cart table (classic) or cart block should be visible – a PHP fatal
		// would prevent the page from rendering any WooCommerce markup.
		await expect(
			page.locator( '.woocommerce-cart-form, .wp-block-woocommerce-cart' )
		).toBeVisible( { timeout: 15000 } );

		// The product we added must appear in the cart.
		await expect(
			page.locator( '.cart_item, .wc-block-cart-items__row' )
		).toBeVisible();
	} );
} );
