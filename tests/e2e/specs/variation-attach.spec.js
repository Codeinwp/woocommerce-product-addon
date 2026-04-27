import { test, expect } from '@wordpress/e2e-test-utils-playwright';

import {
	attachPpomGroupToVariations,
	buildTextField,
	createPpomGroup,
	createProductVariation,
	createVariableProduct,
	setPpomLicenseFixture,
} from '../fixtures/index.js';

test.describe( 'Variation Attach', () => {
	test( 'PPOM group renders only for the allowed variation', async ( {
		page,
		requestUtils,
	} ) => {
		await setPpomLicenseFixture( requestUtils, { valid: true } );

		const suffix = Date.now();
		const product = await createVariableProduct( requestUtils, {
			name: `Variation Attach Product ${ suffix }`,
			attributes: [
				{
					name: 'Size',
					options: [ 'Small', 'Large' ],
				},
			],
		} );
		const small = await createProductVariation( requestUtils, {
			productId: product.id,
			regular_price: '10',
			attributes: { size: 'Small' },
		} );
		const large = await createProductVariation( requestUtils, {
			productId: product.id,
			regular_price: '20',
			attributes: { size: 'Large' },
		} );

		const { ppomId } = await createPpomGroup( requestUtils, {
			groupName: `Variation Attach Group ${ suffix }`,
			fields: [
				buildTextField( {
					title: 'Engraving',
					dataName: `engraving_${ suffix }`,
				} ),
			],
		} );

		const attachResult = await attachPpomGroupToVariations( requestUtils, {
			ppomId,
			variationIds: [ small.id ],
		} );

		expect( attachResult.attached_products ).toBe( 1 );
		expect( attachResult.updated_variations ).toBe( 1 );

		await page.goto( `/?p=${ product.id }` );

		const ppomGroup = page.locator(
			`[data-ppom-group-id="${ ppomId }"]`
		);
		const variationSelect = page.locator( 'select[name="attribute_size"]' );
		const addToCart = page.locator( 'button.single_add_to_cart_button' );

		await expect( variationSelect ).toBeVisible();
		await expect( ppomGroup ).toHaveClass( /ppom-variation-rule-group/ );
		await expect( ppomGroup ).toHaveAttribute(
			'data-ppom-allowed-variations',
			String( small.id )
		);
		await expect( ppomGroup ).toHaveAttribute( 'aria-hidden', 'true' );

		await variationSelect.selectOption( 'Small' );
		await expect( addToCart ).toBeEnabled();
		await expect( ppomGroup ).toHaveAttribute( 'aria-hidden', 'false' );
		await expect( page.getByLabel( 'Engraving' ) ).toBeVisible();

		await variationSelect.selectOption( 'Large' );
		await expect( addToCart ).toBeEnabled();
		await expect( ppomGroup ).toHaveAttribute( 'aria-hidden', 'true' );
		await expect( page.getByLabel( 'Engraving' ) ).toBeHidden();

		// Sanity: the unrestricted "large" variation exists and is selectable.
		expect( large.id ).toBeGreaterThan( 0 );
	} );
} );
