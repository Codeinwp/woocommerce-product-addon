/**
 * Regression coverage for the warning reported in the field-group support thread:
 * a group attached to both a product and that product's category triggered
 * "Undefined array key 'ppom_id'" warnings in classes/form.class.php on PHP 8.x
 * for sibling products that inherited the group only via the category.
 *
 * Root cause: PPOM_Meta::get_fields() never stamps ppom_id onto each field, so
 * legacy/imported groups (whose JSON predates the WPML save filter that injects
 * the key) flow into PPOM_Form::ppom_fields_render() and trip array_filter
 * on `$field['ppom_id']`. The legacy fixture below reproduces that JSON shape.
 */

import { test, expect } from '@wordpress/e2e-test-utils-playwright';

import {
	attachPpomGroupToCategories,
	attachPpomGroupToProducts,
	createLegacyPpomGroup,
	createProductCategory,
	createSimpleProduct,
} from '../fixtures/index.js';

const PHP_WARNING_MARKER = 'Undefined array key &quot;ppom_id&quot;';

function legacyTextField( { title, dataName } ) {
	// Mirrors a stored field row that never went through the WPML save filter:
	// no `ppom_id` key, mirroring legacy/imported data we have seen in the wild.
	return {
		type: 'text',
		title,
		data_name: dataName,
		description: '',
		placeholder: '',
		error_message: '',
		width: '12',
		visibility: 'everyone',
		visibility_role: '',
		status: 'on',
	};
}

test.describe( 'Field group attached to product and category (legacy fields)', () => {
	test( 'product inheriting the group only via category renders without ppom_id warnings', async ( {
		page,
		requestUtils,
	} ) => {
		const suffix = Date.now();
		const category = await createProductCategory( requestUtils, {
			name: `Dual Attach Category ${ suffix }`,
			slug: `ppom-dual-attach-${ suffix }`,
		} );
		const directProduct = await createSimpleProduct( requestUtils, {
			name: `Dual Attach Direct Product ${ suffix }`,
			categories: [ { id: category.id } ],
		} );
		const inheritedProduct = await createSimpleProduct( requestUtils, {
			name: `Dual Attach Inherited Product ${ suffix }`,
			categories: [ { id: category.id } ],
		} );

		const dataName = `dual_attach_note_${ suffix }`;
		const { ppomId } = await createLegacyPpomGroup( requestUtils, {
			groupName: `Dual Attach Legacy Group ${ suffix }`,
			fields: [
				legacyTextField( {
					title: 'Personalization Note',
					dataName,
				} ),
			],
		} );

		await attachPpomGroupToProducts( requestUtils, {
			ppomId,
			productIds: [ directProduct.id ],
		} );
		await attachPpomGroupToCategories( requestUtils, {
			ppomId,
			categorySlugs: [ category.slug ],
		} );

		for ( const product of [ directProduct, inheritedProduct ] ) {
			const response = await page.goto( `/?p=${ product.id }` );
			expect( response ).not.toBeNull();
			expect(
				response.status(),
				`unexpected HTTP status for product ${ product.id }`
			).toBe( 200 );

			await expect(
				page.locator( `.ppom-id-${ ppomId }` ).first()
			).toBeVisible();
			await expect(
				page.locator(
					`input[name="ppom[fields][${ dataName }]"]`
				)
			).toBeVisible();

			const html = await page.content();
			expect(
				html.includes( PHP_WARNING_MARKER ),
				`product ${ product.id } page contains PHP warning: ${ PHP_WARNING_MARKER }`
			).toBeFalsy();
		}
	} );

	test( 'inherited-only product renders the legacy field exactly once', async ( {
		page,
		requestUtils,
	} ) => {
		const suffix = Date.now();
		const category = await createProductCategory( requestUtils, {
			name: `Single Field Category ${ suffix }`,
			slug: `ppom-single-field-cat-${ suffix }`,
		} );
		const product = await createSimpleProduct( requestUtils, {
			name: `Single Field Inherited Product ${ suffix }`,
			categories: [ { id: category.id } ],
		} );

		const dataName = `inherited_only_${ suffix }`;
		const { ppomId } = await createLegacyPpomGroup( requestUtils, {
			groupName: `Inherited-Only Legacy Group ${ suffix }`,
			fields: [
				legacyTextField( {
					title: 'Inscription',
					dataName,
				} ),
			],
		} );

		await attachPpomGroupToCategories( requestUtils, {
			ppomId,
			categorySlugs: [ category.slug ],
		} );

		await page.goto( `/?p=${ product.id }` );

		const inputs = page.locator(
			`input[name="ppom[fields][${ dataName }]"]`
		);
		await expect( inputs ).toHaveCount( 1 );

		const html = await page.content();
		expect( html.includes( PHP_WARNING_MARKER ) ).toBeFalsy();
	} );
} );
