/**
 * WordPress dependencies
 */
import { test, expect } from '@wordpress/e2e-test-utils-playwright';

import {
	addNewField,
	fillFieldNameAndId,
	pickFieldTypeInModal,
	saveFieldInModal,
	saveFields,
} from '../utils';

const RUN_SUFFIX = Date.now();
const GROUP_TITLE = `Critical Checkout Flow ${ RUN_SUFFIX }`;
const FIELD_TITLE = 'Engraving';
const FIELD_ID = `checkout_critical_text_${ RUN_SUFFIX }`;
const FIELD_ERROR = 'Please enter your engraving';
const FIELD_PRICE = '5';
const FIELD_VALUE = 'Hello from PPOM';

async function ensureCashOnDeliveryEnabled( admin, page ) {
	await admin.visitAdminPage(
		'admin.php?page=wc-settings&tab=checkout&section=cod'
	);

	const codToggle = page.getByLabel( 'Enable cash on delivery payments' );

	if ( ! ( await codToggle.isChecked() ) ) {
		await codToggle.check();
		await page.getByRole( 'button', { name: 'Save changes' } ).click();
		await expect( codToggle ).toBeChecked();
	}
}

function parsePrice( priceText ) {
	const amount = priceText.match( /-?\d[\d,.]*/ )?.[ 0 ];

	if ( ! amount ) {
		throw new Error( `Unable to parse price from "${ priceText }"` );
	}

	return Number( amount.replaceAll( ',', '' ) );
}

function formatPrice( priceText, amount ) {
	return priceText.replace(
		/-?\d[\d,.]*/,
		Number( amount ).toFixed( 2 )
	);
}

async function clearCart( page ) {
	await page.goto( '/cart/' );
	await page.waitForLoadState( 'networkidle' );

	const removeButtons = page.getByRole( 'button', {
		name: /Remove .* from cart/,
	} );

	while ( await removeButtons.count() ) {
		const buttonCount = await removeButtons.count();
		await removeButtons.first().click();
		await expect( removeButtons ).toHaveCount( buttonCount - 1 );
	}
}

async function attachGroupToProduct( page, productName ) {
	await page.getByText( 'Attach to Products' ).click( { force: true } );
	await page.waitForLoadState( 'networkidle' );

	const productSelector = page.locator(
		'select[name="ppom-attach-to-products\\[\\]"]'
	);

	await productSelector.selectOption( { label: productName } );

	const productId = await productSelector.inputValue();

	await page.getByRole( 'button', { name: 'Save', exact: true } ).click();
	await page.waitForLoadState( 'networkidle' );

	return productId;
}

async function fillCheckoutAddress( page ) {
	const emailField = page.getByRole( 'textbox', {
		name: 'Email address',
		exact: true,
	} );

	await expect( emailField ).toBeVisible();
	await emailField.fill( 'ppom@example.com' );

	const editBillingAddress = page.getByRole( 'button', {
		name: 'Edit billing address',
	} );

	if ( await editBillingAddress.count() ) {
		await editBillingAddress.click();
	}

	await page
		.getByRole( 'textbox', { name: 'First name', exact: true } )
		.fill( 'Test' );
	await page
		.getByRole( 'textbox', { name: 'Last name', exact: true } )
		.fill( 'Buyer' );
	await page
		.getByRole( 'textbox', { name: 'Address', exact: true } )
		.fill( '123 Test Street' );
	await page
		.getByRole( 'textbox', { name: 'City', exact: true } )
		.fill( 'Cluj' );
	await page
		.getByRole( 'textbox', { name: 'ZIP Code', exact: true } )
		.fill( '12345' );
}

test.describe( 'Critical Checkout Flow', () => {
	test( '@critical required priced text field validates, updates totals, and persists to the order', async ( {
		page,
		admin,
	} ) => {
		await ensureCashOnDeliveryEnabled( admin, page );
		await clearCart( page );

		await admin.visitAdminPage( 'admin.php?page=ppom' );
		await page.getByRole( 'link', { name: 'Add New Group' } ).click();
		await page.getByRole( 'textbox' ).fill( GROUP_TITLE );

		await addNewField( page );
		await pickFieldTypeInModal( page, 'text' );
		await fillFieldNameAndId( page, 1, FIELD_TITLE, FIELD_ID );
		await page
			.locator( 'input[name="ppom\\[1\\]\\[price\\]"]' )
			.fill( FIELD_PRICE );
		await page
			.locator( 'input[name="ppom\\[1\\]\\[error_message\\]"]' )
			.fill( FIELD_ERROR );
		await page
			.locator( 'input[name="ppom\\[1\\]\\[required\\]"]' )
			.check( { force: true } );

		await saveFieldInModal( page, 1 );
		await saveFields( page );
		await page.waitForLoadState( 'networkidle' );
		await page.reload();

		const productId = await attachGroupToProduct( page, 'Product 1' );

		await page.goto( `/?p=${ productId }` );

		const priceText = await page.locator( '.summary .price' ).first().innerText();
		const expectedTotal = formatPrice(
			priceText,
			parsePrice( priceText ) + Number( FIELD_PRICE )
		);

		const addToCartButton = page.getByRole( 'button', {
			name: 'Add to cart',
		} );

		await addToCartButton.click();
		await expect( page.locator( 'body' ) ).toContainText(
			`${ FIELD_TITLE }: ${ FIELD_ERROR }`
		);

		const engravingInput = page.locator(
			`input[name="ppom[fields][${ FIELD_ID }]"]`
		);

		await engravingInput.fill( FIELD_VALUE );
		await engravingInput.press( 'Tab' );
		await addToCartButton.click();

		await page.goto( '/checkout/' );
		await fillCheckoutAddress( page );

		const cashOnDelivery = page.getByRole( 'radio', {
			name: 'Cash on delivery',
		} );

		await expect( cashOnDelivery ).toBeVisible();
		await cashOnDelivery.check();
		await expect( page.locator( 'body' ) ).toContainText( expectedTotal );

		await page.getByRole( 'button', { name: 'Place Order' } ).click();

		await expect( page ).toHaveURL( /\/checkout\/order-received\/\d+\// );
		await expect(
			page.getByText( 'Thank you. Your order has been received.' )
		).toBeVisible();
		await expect( page.locator( 'body' ) ).toContainText( expectedTotal );
		await expect( page.locator( 'body' ) ).toContainText( FIELD_TITLE );
		await expect( page.locator( 'body' ) ).toContainText( FIELD_VALUE );
	} );
} );
