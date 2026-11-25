/**
 * WordPress dependencies
 */
import { test, expect } from '@wordpress/e2e-test-utils-playwright';

/**
 * Internal dependencies
 */
import {
	attachPpomGroupToProducts,
	buildTextField,
	createPpomGroup,
	createSimpleProduct,
	setPpomSettings,
} from '../fixtures/index.js';

test.describe( 'Price table v2 with a text field value', () => {
	test.beforeEach( async ( { requestUtils } ) => {
		const { applied } = await setPpomSettings( requestUtils, {
			ppom_price_table_v2: 'yes',
		} );
		expect( applied.ppom_price_table_v2 ).toBe( 'yes' );
	} );

	test.afterEach( async ( { requestUtils } ) => {
		await setPpomSettings( requestUtils, { ppom_price_table_v2: '' } );
	} );

	test( 'shows markup from a URL-supplied text value as text', async ( {
		page,
		requestUtils,
	} ) => {
		const product = await createSimpleProduct( requestUtils, {
			regular_price: '10.00',
		} );
		const { ppomId } = await createPpomGroup( requestUtils, {
			groupName: 'Price Table V2 Text Value',
			fields: [
				buildTextField( {
					title: 'Engraving',
					dataName: 'engraving',
					price: '5',
				} ),
			],
		} );
		await attachPpomGroupToProducts( requestUtils, {
			ppomId,
			productIds: [ product.id ],
		} );

		const markup = '<img src=x onerror="window.ppomInjected=true">';
		const entityEncoded = markup
			.replace( /</g, '&lt;' )
			.replace( />/g, '&gt;' );

		await page.goto(
			`/?p=${ product.id }&engraving=${ encodeURIComponent(
				entityEncoded
			) }`
		);

		const input = page.locator( 'input#engraving' );
		await expect( input ).toHaveValue( markup );
		await input.dispatchEvent( 'change' );

		const table = page.locator( '#ppom-price-container' );
		await expect(
			table.locator( 'th.ppom-label-item', { hasText: 'Engraving' } )
		).toContainText( `Engraving [${ markup }]` );
		await expect( table.locator( 'img' ) ).toHaveCount( 0 );
		expect(
			await page.evaluate( () => window.ppomInjected )
		).toBeUndefined();
	} );
} );
