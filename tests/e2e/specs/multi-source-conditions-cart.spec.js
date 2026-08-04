/**
 * WordPress dependencies
 */
import { test, expect } from '@wordpress/e2e-test-utils-playwright';

import {
	attachPpomGroupToProducts,
	buildSelectField,
	buildTextField,
	createPpomGroup,
	createSimpleProduct,
} from '../fixtures/index.js';

/**
 * Regression for Codeinwp/ppom-pro#541.
 *
 * A field whose condition rules span two different source fields was visible
 * on the product page but missing from the cart: the `conditionally_hidden`
 * payload was built from per-source `ppom-locked-*` classes, and the lock of
 * the non-matching source went stale when the other source's rule matched.
 */
test.describe( 'Multi-source conditions and the cart payload', () => {
	test( 'field with rules across two source fields reaches the cart', async ( {
		page,
		requestUtils,
	} ) => {
		// Room for the slow add-to-cart navigation plus the cart poll below.
		test.setTimeout( 240000 );

		const token = `${ Date.now() }_${ Math.floor( Math.random() * 1e6 ) }`;
		const configAId = `config_a_${ token }`;
		const configBId = `config_b_${ token }`;
		const widthId = `width_${ token }`;

		const product = await createSimpleProduct( requestUtils );
		const { ppomId } = await createPpomGroup( requestUtils, {
			groupName: `Multi Source Conditions ${ token }`,
			fields: [
				buildSelectField( {
					title: `Config A ${ token }`,
					dataName: configAId,
					options: [
						{ label: 'Other', value: 'other_a' },
						{ label: 'Config-G', value: 'config_g' },
					],
				} ),
				buildSelectField( {
					title: `Config B ${ token }`,
					dataName: configBId,
					options: [
						{ label: 'Other', value: 'other_b' },
						{ label: 'Config-A', value: 'config_a' },
					],
				} ),
				buildTextField( {
					title: 'Width (mm)',
					dataName: widthId,
					logic: 'on',
					conditions: {
						visibility: 'Show',
						bound: 'Any',
						rules: [
							{
								elements: configAId,
								operators: 'is',
								element_values: 'Config-G',
							},
							{
								elements: configBId,
								operators: 'is',
								element_values: 'Config-A',
							},
						],
					},
				} ),
			],
		} );
		await attachPpomGroupToProducts( requestUtils, {
			ppomId,
			productIds: [ product.id ],
		} );

		await page.goto( `/?p=${ product.id }` );

		const width = page.getByLabel( 'Width (mm)' );
		await expect( width ).toBeHidden();

		// Match only the FIRST source rule — the second source's lock used to
		// go stale here and keep the field in the hidden payload.
		await page
			.locator( `select[name="ppom[fields][${ configAId }]"]` )
			.selectOption( { label: 'Config-G' } );
		await expect( width ).toBeVisible();

		await width.fill( '500' );

		// The posted payload must agree with what the customer sees.
		await expect( page.locator( '#conditionally_hidden' ) ).not.toHaveValue(
			new RegExp( widthId )
		);

		await page
			.locator( 'button.single_add_to_cart_button' )
			.click( { timeout: 60000 } );

		// The width value must reach the cart item meta. Polled with a short
		// per-request timeout: the wp-env Store API occasionally hangs.
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
					const item = cart.items?.[ 0 ];
					if ( ! item ) {
						return 'cart-empty';
					}

					const widthMeta = ( item.item_data || [] ).find(
						( meta ) => meta.value === '500'
					);
					return widthMeta ? 'width-in-cart' : 'width-missing';
				},
				{ timeout: 120000, intervals: [ 3000 ] }
			)
			.toBe( 'width-in-cart' );
	} );
} );
