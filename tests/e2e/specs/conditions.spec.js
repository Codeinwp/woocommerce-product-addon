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
	setPpomLicenseFixture,
} from '../fixtures/index.js';

/**
 * Free-tier conditions were re-enabled in c890ad6 — the modal no longer
 * swaps in the locked upsell preview, and `is`/`not`/`greater than`/
 * `less than`/`any`/`empty` should all evaluate on the storefront for users
 * without a Pro license.
 *
 * Tests in this file run against the default e2e license fixture (valid
 * Essential plan, no Pro installed) which mirrors a real free install.
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

function textInputByName( page, dataName ) {
	return page.locator( `input[name="ppom[fields][${ dataName }]"]` );
}

test.describe( 'Conditions', () => {
	test( 'free: Show + is operator reveals the target when the controlling Select matches', async ( {
		page,
		requestUtils,
	} ) => {
		await ensureFreeTier( requestUtils );

		const optionOne = { label: 'Option 1', value: 'option_1' };
		const optionTwo = { label: 'Option 2', value: 'option_2' };
		const token = uniqueToken();
		const firstSelectFieldId = `select_test_${ token }`;
		const secondSelectFieldId = `select_test_${ token }_b`;
		const product = await createSimpleProduct( requestUtils );
		const { ppomId } = await createPpomGroup( requestUtils, {
			groupName: `Conditions Show+Is ${ token }`,
			fields: [
				buildSelectField( {
					title: `Select Input A ${ token }`,
					dataName: firstSelectFieldId,
					options: [ optionOne, optionTwo ],
				} ),
				buildSelectField( {
					title: `Select Input B ${ token }`,
					dataName: secondSelectFieldId,
					options: [ optionOne, optionTwo ],
				} ),
				buildTextField( {
					title: 'Output',
					dataName: `output_${ token }`,
					logic: 'on',
					conditions: {
						visibility: 'Show',
						bound: 'All',
						rules: [
							{
								elements: secondSelectFieldId,
								operators: 'is',
								element_values: optionTwo.label,
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

		await expect( page.getByLabel( 'Output' ) ).toBeHidden();

		const controllingSelect = selectByName( page, secondSelectFieldId );
		await controllingSelect.selectOption( { label: optionTwo.label } );
		await expect( controllingSelect ).toHaveValue( optionTwo.label );

		await expect( page.getByLabel( 'Output' ) ).toBeVisible();
	} );

	test( 'free: Show + not operator reveals the target when the controlling Select does NOT match', async ( {
		page,
		requestUtils,
	} ) => {
		await ensureFreeTier( requestUtils );

		const optionOne = { label: 'Red', value: 'red' };
		const optionTwo = { label: 'Blue', value: 'blue' };
		const token = uniqueToken();
		const controllingId = `color_${ token }`;
		const outputId = `extra_${ token }`;
		const product = await createSimpleProduct( requestUtils );
		const { ppomId } = await createPpomGroup( requestUtils, {
			groupName: `Conditions Show+Not ${ token }`,
			fields: [
				buildSelectField( {
					title: `Color ${ token }`,
					dataName: controllingId,
					options: [ optionOne, optionTwo ],
				} ),
				buildTextField( {
					title: 'Extra notes',
					dataName: outputId,
					logic: 'on',
					conditions: {
						visibility: 'Show',
						bound: 'All',
						rules: [
							{
								elements: controllingId,
								operators: 'not',
								element_values: optionOne.label,
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

		const controllingSelect = selectByName( page, controllingId );

		// First option is the implicit default — `not Red` is false, output stays hidden.
		await expect( controllingSelect ).toHaveValue( optionOne.label );
		await expect( page.getByLabel( 'Extra notes' ) ).toBeHidden();

		await controllingSelect.selectOption( { label: optionTwo.label } );
		await expect( page.getByLabel( 'Extra notes' ) ).toBeVisible();

		await controllingSelect.selectOption( { label: optionOne.label } );
		await expect( page.getByLabel( 'Extra notes' ) ).toBeHidden();
	} );

	test( 'free: Hide visibility removes the target when the rule matches', async ( {
		page,
		requestUtils,
	} ) => {
		await ensureFreeTier( requestUtils );

		const noOpt = { label: 'No', value: 'no' };
		const yesOpt = { label: 'Yes', value: 'yes' };
		const token = uniqueToken();
		const controllingId = `gift_${ token }`;
		const outputId = `gift_message_${ token }`;
		const product = await createSimpleProduct( requestUtils );
		const { ppomId } = await createPpomGroup( requestUtils, {
			groupName: `Conditions Hide+Is ${ token }`,
			fields: [
				buildSelectField( {
					title: `Gift wrap ${ token }`,
					dataName: controllingId,
					options: [ noOpt, yesOpt ],
				} ),
				buildTextField( {
					title: 'Gift message',
					dataName: outputId,
					logic: 'on',
					conditions: {
						visibility: 'Hide',
						bound: 'All',
						rules: [
							{
								elements: controllingId,
								operators: 'is',
								element_values: noOpt.label,
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

		// Default selected option is the first one (No) — Hide rule matches → output hidden.
		await expect( page.getByLabel( 'Gift message' ) ).toBeHidden();

		await selectByName( page, controllingId ).selectOption( {
			label: yesOpt.label,
		} );
		await expect( page.getByLabel( 'Gift message' ) ).toBeVisible();
	} );

	test( 'free: bound=Any shows the target when any rule matches', async ( {
		page,
		requestUtils,
	} ) => {
		await ensureFreeTier( requestUtils );

		const a1 = { label: 'A1', value: 'a1' };
		const a2 = { label: 'A2', value: 'a2' };
		const b1 = { label: 'B1', value: 'b1' };
		const b2 = { label: 'B2', value: 'b2' };
		const token = uniqueToken();
		const firstId = `first_${ token }`;
		const secondId = `second_${ token }`;
		const outputId = `union_${ token }`;
		const product = await createSimpleProduct( requestUtils );
		const { ppomId } = await createPpomGroup( requestUtils, {
			groupName: `Conditions Any ${ token }`,
			fields: [
				buildSelectField( {
					title: `First ${ token }`,
					dataName: firstId,
					options: [ a1, a2 ],
				} ),
				buildSelectField( {
					title: `Second ${ token }`,
					dataName: secondId,
					options: [ b1, b2 ],
				} ),
				buildTextField( {
					title: 'Union output',
					dataName: outputId,
					logic: 'on',
					conditions: {
						visibility: 'Show',
						bound: 'Any',
						rules: [
							{
								elements: firstId,
								operators: 'is',
								element_values: a2.label,
							},
							{
								elements: secondId,
								operators: 'is',
								element_values: b2.label,
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

		const output = page.getByLabel( 'Union output' );
		await expect( output ).toBeHidden();

		await selectByName( page, secondId ).selectOption( {
			label: b2.label,
		} );
		await expect( output ).toBeVisible();

		await selectByName( page, secondId ).selectOption( {
			label: b1.label,
		} );
		await expect( output ).toBeHidden();

		await selectByName( page, firstId ).selectOption( {
			label: a2.label,
		} );
		await expect( output ).toBeVisible();
	} );

	test( 'free: bound=All only shows when every rule matches', async ( {
		page,
		requestUtils,
	} ) => {
		await ensureFreeTier( requestUtils );

		const sizeS = { label: 'S', value: 's' };
		const sizeL = { label: 'L', value: 'l' };
		const finishMatte = { label: 'Matte', value: 'matte' };
		const finishGloss = { label: 'Gloss', value: 'gloss' };
		const token = uniqueToken();
		const sizeId = `size_${ token }`;
		const finishId = `finish_${ token }`;
		const outputId = `combo_${ token }`;
		const product = await createSimpleProduct( requestUtils );
		const { ppomId } = await createPpomGroup( requestUtils, {
			groupName: `Conditions All ${ token }`,
			fields: [
				buildSelectField( {
					title: `Size ${ token }`,
					dataName: sizeId,
					options: [ sizeS, sizeL ],
				} ),
				buildSelectField( {
					title: `Finish ${ token }`,
					dataName: finishId,
					options: [ finishMatte, finishGloss ],
				} ),
				buildTextField( {
					title: 'Premium combo note',
					dataName: outputId,
					logic: 'on',
					conditions: {
						visibility: 'Show',
						bound: 'All',
						rules: [
							{
								elements: sizeId,
								operators: 'is',
								element_values: sizeL.label,
							},
							{
								elements: finishId,
								operators: 'is',
								element_values: finishGloss.label,
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

		const output = page.getByLabel( 'Premium combo note' );
		await expect( output ).toBeHidden();

		await selectByName( page, sizeId ).selectOption( {
			label: sizeL.label,
		} );
		await expect( output ).toBeHidden();

		await selectByName( page, finishId ).selectOption( {
			label: finishGloss.label,
		} );
		await expect( output ).toBeVisible();

		await selectByName( page, sizeId ).selectOption( {
			label: sizeS.label,
		} );
		await expect( output ).toBeHidden();
	} );

	test( 'free: any + empty operators react to text input being filled or cleared', async ( {
		page,
		requestUtils,
	} ) => {
		await ensureFreeTier( requestUtils );

		const token = uniqueToken();
		const inputId = `note_${ token }`;
		const filledId = `filled_${ token }`;
		const emptyId = `empty_${ token }`;
		const product = await createSimpleProduct( requestUtils );
		const { ppomId } = await createPpomGroup( requestUtils, {
			groupName: `Conditions Any/Empty ${ token }`,
			fields: [
				buildTextField( {
					title: `Note ${ token }`,
					dataName: inputId,
				} ),
				buildTextField( {
					title: 'Visible when filled',
					dataName: filledId,
					logic: 'on',
					conditions: {
						visibility: 'Show',
						bound: 'All',
						rules: [
							{
								elements: inputId,
								operators: 'any',
								element_values: '',
							},
						],
					},
				} ),
				buildTextField( {
					title: 'Visible when empty',
					dataName: emptyId,
					logic: 'on',
					conditions: {
						visibility: 'Show',
						bound: 'All',
						rules: [
							{
								elements: inputId,
								operators: 'empty',
								element_values: '',
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

		const filled = page.getByLabel( 'Visible when filled' );
		const empty = page.getByLabel( 'Visible when empty' );

		await expect( filled ).toBeHidden();
		await expect( empty ).toBeVisible();

		// Conditions v2 binds the text-input handler to `keyup` on .ppom-wrapper,
		// so pressSequentially is required — fill() bypasses the keyboard.
		const note = textInputByName( page, inputId );
		await note.click();
		await note.pressSequentially( 'hello' );
		await expect( filled ).toBeVisible();
		await expect( empty ).toBeHidden();

		await note.selectText();
		await note.press( 'Backspace' );
		await expect( filled ).toBeHidden();
		await expect( empty ).toBeVisible();
	} );

	test( 'free: PRO-only contains operator does not activate at runtime', async ( {
		page,
		requestUtils,
	} ) => {
		await ensureFreeTier( requestUtils );

		const token = uniqueToken();
		const inputId = `term_${ token }`;
		const outputId = `match_${ token }`;
		const product = await createSimpleProduct( requestUtils );
		const { ppomId } = await createPpomGroup( requestUtils, {
			groupName: `Conditions Pro Locked ${ token }`,
			fields: [
				buildTextField( {
					title: `Search term ${ token }`,
					dataName: inputId,
				} ),
				buildTextField( {
					title: 'Match output',
					dataName: outputId,
					logic: 'on',
					conditions: {
						visibility: 'Show',
						bound: 'All',
						rules: [
							{
								elements: inputId,
								operators: 'contains',
								element_constant: 'needle',
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

		const output = page.getByLabel( 'Match output' );
		await expect( output ).toBeHidden();

		const term = textInputByName( page, inputId );
		await term.click();
		await term.pressSequentially( 'needle in haystack' );

		// The PRO-only constant isn't serialized into the DOM on free, so
		// `contains` has nothing to compare against and the field stays hidden.
		await expect( output ).toBeHidden();

		const wrapper = page.locator( `.ppom-input-${ outputId }` );
		await expect( wrapper ).toHaveCount( 1 );
		const constantAttr = await wrapper.getAttribute(
			'data-cond-constant-val-1'
		);
		expect( constantAttr ).toBeNull();
	} );
} );
