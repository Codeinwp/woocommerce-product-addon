/**
 * WordPress dependencies
 */
import { test, expect } from '@wordpress/e2e-test-utils-playwright';

import {
	attachPpomGroupToProducts,
	buildSelectField,
	createPpomGroup,
	createSimpleProduct,
	setPpomLicenseFixture,
} from '../fixtures/index.js';

/**
 * Regression coverage for #337: a Select field revealed by conditional
 * logic must not end up with an empty value — it should show its default
 * (explicitly configured via `selected`, or the first option otherwise).
 *
 * Repro shape mirrors the issue report: Select 1 controls Select 2 via
 * Show + is, Select 3 is unconditioned.
 */
async function ensureFreeTier( requestUtils ) {
	await setPpomLicenseFixture( requestUtils, {
		valid: true,
		plan: 1,
		proInstalled: false,
	} );
}

function uniqueToken() {
	return `${ Date.now() }_${ Math.floor( Math.random() * 1e6 ) }`;
}

function selectByName( page, dataName ) {
	return page.locator( `select[name="ppom[fields][${ dataName }]"]` );
}

async function createConditionalSelectProduct(
	requestUtils,
	{ token, conditionalOverrides = {} }
) {
	const controllingId = `select_one_${ token }`;
	const conditionalId = `select_two_${ token }`;
	const plainId = `select_three_${ token }`;

	const product = await createSimpleProduct( requestUtils );
	const { ppomId } = await createPpomGroup( requestUtils, {
		groupName: `Conditional Select Default ${ token }`,
		fields: [
			buildSelectField( {
				title: `Select One ${ token }`,
				dataName: controllingId,
				options: [
					{ label: '1', value: 'opt_1' },
					{ label: '2', value: 'opt_2' },
					{ label: '3', value: 'opt_3' },
				],
			} ),
			buildSelectField( {
				title: `Select Two ${ token }`,
				dataName: conditionalId,
				options: [
					{ label: '1.1', value: 'opt_1_1' },
					{ label: '2.1', value: 'opt_2_1' },
					{ label: '3.1', value: 'opt_3_1' },
				],
				logic: 'on',
				conditions: {
					visibility: 'Show',
					bound: 'All',
					rules: [
						{
							elements: controllingId,
							operators: 'is',
							element_values: '2',
						},
					],
				},
				...conditionalOverrides,
			} ),
			buildSelectField( {
				title: `Select Three ${ token }`,
				dataName: plainId,
				options: [
					{ label: '1.1', value: 'opt_1_1' },
					{ label: '1.2', value: 'opt_1_2' },
					{ label: '1.3', value: 'opt_1_3' },
				],
			} ),
		],
	} );

	await attachPpomGroupToProducts( requestUtils, {
		ppomId,
		productIds: [ product.id ],
	} );

	return { product, controllingId, conditionalId, plainId };
}

test.describe( 'Conditional select default value (#337)', () => {
	test( 'free: a conditionally revealed Select shows a non-empty value across show/hide cycles', async ( {
		page,
		requestUtils,
	} ) => {
		await ensureFreeTier( requestUtils );

		const token = uniqueToken();
		const { product, controllingId, conditionalId } =
			await createConditionalSelectProduct( requestUtils, { token } );

		await page.goto( `/?p=${ product.id }` );

		const controllingSelect = selectByName( page, controllingId );
		const conditionalSelect = selectByName( page, conditionalId );

		await expect( conditionalSelect ).toBeHidden();

		await controllingSelect.selectOption( { label: '2' } );
		await expect( conditionalSelect ).toBeVisible();
		await expect( conditionalSelect ).toHaveValue( '1.1' );

		// Hide it again, then re-show — the value must be restored, not empty.
		await controllingSelect.selectOption( { label: '1' } );
		await expect( conditionalSelect ).toBeHidden();

		await controllingSelect.selectOption( { label: '2' } );
		await expect( conditionalSelect ).toBeVisible();
		await expect( conditionalSelect ).toHaveValue( '1.1' );
	} );

	test( 'free: a conditionally revealed Select restores its configured default option', async ( {
		page,
		requestUtils,
	} ) => {
		await ensureFreeTier( requestUtils );

		const token = uniqueToken();
		const { product, controllingId, conditionalId } =
			await createConditionalSelectProduct( requestUtils, {
				token,
				conditionalOverrides: { selected: '2.1' },
			} );

		await page.goto( `/?p=${ product.id }` );

		const controllingSelect = selectByName( page, controllingId );
		const conditionalSelect = selectByName( page, conditionalId );

		await expect( conditionalSelect ).toBeHidden();

		await controllingSelect.selectOption( { label: '2' } );
		await expect( conditionalSelect ).toBeVisible();
		await expect( conditionalSelect ).toHaveValue( '2.1' );

		await controllingSelect.selectOption( { label: '3' } );
		await expect( conditionalSelect ).toBeHidden();

		await controllingSelect.selectOption( { label: '2' } );
		await expect( conditionalSelect ).toBeVisible();
		await expect( conditionalSelect ).toHaveValue( '2.1' );
	} );
} );
