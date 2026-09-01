/**
 * WordPress dependencies
 */
import { test, expect } from '@wordpress/e2e-test-utils-playwright';

import {
	attachPpomGroupToProducts,
	buildCheckboxField,
	buildNumberField,
	buildSelectField,
	buildTextField,
	createPpomGroup,
	createSimpleProduct,
	setLegacyConditionsScript,
	setPpomLicenseFixture,
} from '../fixtures/index.js';

/**
 * Coverage for the "Legacy Conditions Script" setting (`ppom_new_conditions`),
 * which swaps `ppom-conditions-v2.js` for the legacy `ppom-conditions.js`
 * engine. In legacy mode PHP hides Show-conditioned wrappers with the
 * `ppom-c-hide` class, so the engine has to toggle that class — jQuery
 * `show()`/`hide()` alone cannot beat `visibility: hidden`.
 */
function uniqueToken() {
	return `${ Date.now() }_${ Math.floor( Math.random() * 1e6 ) }`;
}

function selectByName( page, dataName ) {
	return page.locator( `select[name="ppom[fields][${ dataName }]"]` );
}

/**
 * Build a group whose only conditional field is driven by `rules`, attach it to
 * a fresh product, and open that product page.
 *
 * @param {Object} args
 * @param {import('@playwright/test').Page} args.page Playwright page.
 * @param {import('@wordpress/e2e-test-utils-playwright').RequestUtils} args.requestUtils Authenticated request utils.
 * @param {string} args.groupName Field group title.
 * @param {Object[]} args.sourceFields Fields the rules point at.
 * @param {Object[]} args.rules Condition rules for the target field.
 * @param {string} [args.visibility='Show'] Condition visibility.
 * @param {string} [args.bound='All'] Rule binding.
 * @param {string} args.targetDataName Target field data_name.
 * @return {Promise<void>}
 */
async function openConditionalProduct( {
	page,
	requestUtils,
	groupName,
	sourceFields,
	rules,
	visibility = 'Show',
	bound = 'All',
	targetDataName,
} ) {
	const product = await createSimpleProduct( requestUtils );
	const { ppomId } = await createPpomGroup( requestUtils, {
		groupName,
		fields: [
			...sourceFields,
			buildTextField( {
				title: 'Output',
				dataName: targetDataName,
				logic: 'on',
				conditions: { visibility, bound, rules },
			} ),
		],
	} );

	await attachPpomGroupToProducts( requestUtils, {
		ppomId,
		productIds: [ product.id ],
	} );

	await page.goto( `/?p=${ product.id }` );
}

test.describe( 'Legacy conditions script', () => {
	test.beforeEach( async ( { requestUtils } ) => {
		await setPpomLicenseFixture( requestUtils, {
			valid: true,
			plan: 1,
			proInstalled: false,
		} );

		const applied = await setLegacyConditionsScript( requestUtils, true );
		expect( applied.conditions_mode ).toBe( 'legacy' );
	} );

	test.afterEach( async ( { requestUtils } ) => {
		const applied = await setLegacyConditionsScript( requestUtils, false );
		expect( applied.conditions_mode ).toBe( 'new' );
	} );

	test( 'legacy: the legacy engine is the one loaded on the product page', async ( {
		page,
		requestUtils,
	} ) => {
		const token = uniqueToken();
		const controllingId = `size_${ token }`;
		const outputId = `size_notes_${ token }`;
		const product = await createSimpleProduct( requestUtils );
		const { ppomId } = await createPpomGroup( requestUtils, {
			groupName: `LC Script ${ token }`,
			fields: [
				buildSelectField( {
					title: `Size ${ token }`,
					dataName: controllingId,
					options: [
						{ label: 'Small', value: 'small' },
						{ label: 'Large', value: 'large' },
					],
				} ),
				buildTextField( {
					title: 'Size notes',
					dataName: outputId,
					logic: 'on',
					conditions: {
						visibility: 'Show',
						bound: 'All',
						rules: [
							{
								elements: controllingId,
								operators: 'is',
								element_values: 'Large',
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

		await expect(
			page.locator( 'script[src*="ppom-conditions.js"]' )
		).toHaveCount( 1 );
		await expect(
			page.locator( 'script[src*="ppom-conditions-v2.js"]' )
		).toHaveCount( 0 );
	} );

	test( 'legacy: Show + is reveals and re-hides the target as the controlling Select changes', async ( {
		page,
		requestUtils,
	} ) => {
		const optionOne = { label: 'Option 1', value: 'option_1' };
		const optionTwo = { label: 'Option 2', value: 'option_2' };
		const token = uniqueToken();
		const controllingId = `legacy_show_${ token }`;
		const outputId = `legacy_show_out_${ token }`;
		const product = await createSimpleProduct( requestUtils );
		const { ppomId } = await createPpomGroup( requestUtils, {
			groupName: `LC Show ${ token }`,
			fields: [
				buildSelectField( {
					title: `Controller ${ token }`,
					dataName: controllingId,
					options: [ optionOne, optionTwo ],
				} ),
				buildTextField( {
					title: 'Output',
					dataName: outputId,
					logic: 'on',
					conditions: {
						visibility: 'Show',
						bound: 'All',
						rules: [
							{
								elements: controllingId,
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

		const output = page.getByLabel( 'Output' );
		const hiddenFields = page.locator( '#conditionally_hidden' );
		await expect( output ).toBeHidden();
		await expect( hiddenFields ).toHaveValue( outputId );

		const controllingSelect = selectByName( page, controllingId );
		await controllingSelect.selectOption( { label: optionTwo.label } );
		await expect( controllingSelect ).toHaveValue( optionTwo.label );

		await expect( output ).toBeVisible();
		await expect( hiddenFields ).toHaveValue( '' );

		// Reverting the controller must put the target back out of view.
		await controllingSelect.selectOption( { label: optionOne.label } );
		await expect( output ).toBeHidden();
		await expect( hiddenFields ).toHaveValue( outputId );
	} );

	test( 'legacy: Hide + is removes and restores the target as the controlling Select changes', async ( {
		page,
		requestUtils,
	} ) => {
		const noOpt = { label: 'No', value: 'no' };
		const yesOpt = { label: 'Yes', value: 'yes' };
		const token = uniqueToken();
		const controllingId = `legacy_hide_${ token }`;
		const outputId = `legacy_hide_out_${ token }`;
		const product = await createSimpleProduct( requestUtils );
		const { ppomId } = await createPpomGroup( requestUtils, {
			groupName: `LC Hide ${ token }`,
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
								element_values: yesOpt.label,
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

		const output = page.getByLabel( 'Gift message' );
		const controllingSelect = selectByName( page, controllingId );

		await expect( controllingSelect ).toHaveValue( noOpt.label );
		await expect( output ).toBeVisible();

		await controllingSelect.selectOption( { label: yesOpt.label } );
		await expect( output ).toBeHidden();

		await controllingSelect.selectOption( { label: noOpt.label } );
		await expect( output ).toBeVisible();
	} );
	test( 'legacy: has any value operator evaluates instead of being ignored', async ( {
		page,
		requestUtils,
	} ) => {
		const token = uniqueToken();
		const sourceId = `legacy_any_src_${ token }`;

		await openConditionalProduct( {
			page,
			requestUtils,
			groupName: `LC Any ${ token }`,
			targetDataName: `legacy_any_out_${ token }`,
			sourceFields: [
				buildTextField( {
					title: `Engraving ${ token }`,
					dataName: sourceId,
				} ),
			],
			rules: [
				{
					elements: sourceId,
					operators: 'any',
					element_values: '',
				},
			],
		} );

		const output = page.getByLabel( 'Output' );
		const source = page.locator(
			`input[name="ppom[fields][${ sourceId }]"]`
		);

		await expect( output ).toBeHidden();

		await source.fill( 'Happy birthday' );
		await source.dispatchEvent( 'change' );
		await expect( output ).toBeVisible();

		await source.fill( '' );
		await source.dispatchEvent( 'change' );
		await expect( output ).toBeHidden();
	} );

	test( 'legacy: is empty operator evaluates instead of being ignored', async ( {
		page,
		requestUtils,
	} ) => {
		const token = uniqueToken();
		const sourceId = `legacy_empty_src_${ token }`;

		await openConditionalProduct( {
			page,
			requestUtils,
			groupName: `LC Empty ${ token }`,
			targetDataName: `legacy_empty_out_${ token }`,
			sourceFields: [
				buildTextField( {
					title: `Engraving ${ token }`,
					dataName: sourceId,
				} ),
			],
			rules: [
				{
					elements: sourceId,
					operators: 'empty',
					element_values: '',
				},
			],
		} );

		const output = page.getByLabel( 'Output' );
		const source = page.locator(
			`input[name="ppom[fields][${ sourceId }]"]`
		);

		// An untouched source is empty, so the target starts out revealed.
		await expect( output ).toBeVisible();

		await source.fill( 'Happy birthday' );
		await source.dispatchEvent( 'change' );
		await expect( output ).toBeHidden();

		await source.fill( '' );
		await source.dispatchEvent( 'change' );
		await expect( output ).toBeVisible();
	} );

	test( 'legacy: greater than compares the real value of a Number source', async ( {
		page,
		requestUtils,
	} ) => {
		const token = uniqueToken();
		const sourceId = `legacy_qty_${ token }`;

		await openConditionalProduct( {
			page,
			requestUtils,
			groupName: `LC Greater ${ token }`,
			targetDataName: `legacy_qty_out_${ token }`,
			sourceFields: [
				buildNumberField( {
					title: `Copies ${ token }`,
					dataName: sourceId,
				} ),
			],
			rules: [
				{
					elements: sourceId,
					operators: 'greater than',
					element_values: '10',
				},
			],
		} );

		const output = page.getByLabel( 'Output' );
		const source = page.locator(
			`input[name="ppom[fields][${ sourceId }]"]`
		);

		await expect( output ).toBeHidden();

		await source.fill( '25' );
		await source.dispatchEvent( 'change' );
		await expect( output ).toBeVisible();

		await source.fill( '3' );
		await source.dispatchEvent( 'change' );
		await expect( output ).toBeHidden();
	} );

	test( 'legacy: is not on a Checkbox source looks at the whole selection', async ( {
		page,
		requestUtils,
	} ) => {
		const token = uniqueToken();
		const sourceId = `legacy_cb_${ token }`;
		const optionA = { label: 'Gift wrap', value: 'gift_wrap' };
		const optionB = { label: 'Express', value: 'express' };

		await openConditionalProduct( {
			page,
			requestUtils,
			groupName: `LC Checkbox Not ${ token }`,
			targetDataName: `legacy_cb_out_${ token }`,
			sourceFields: [
				buildCheckboxField( {
					title: `Extras ${ token }`,
					dataName: sourceId,
					options: [ optionA, optionB ],
				} ),
			],
			rules: [
				{
					elements: sourceId,
					operators: 'not',
					element_values: optionA.label,
				},
			],
		} );

		const output = page.getByLabel( 'Output' );
		const boxes = page.locator(
			`input[name="ppom[fields][${ sourceId }][]"]`
		);

		// Nothing selected: "is not Gift wrap" holds.
		await expect( output ).toBeVisible();

		// Gift wrap selected alongside another box must still count as a match
		// for the rule value, so the target hides.
		await boxes.nth( 0 ).check();
		await boxes.nth( 1 ).check();
		await expect( output ).toBeHidden();

		await boxes.nth( 0 ).uncheck();
		await expect( output ).toBeVisible();
	} );

	test( 'legacy: an unknown operator never matches', async ( {
		page,
		requestUtils,
	} ) => {
		const token = uniqueToken();
		const sourceId = `legacy_unknown_${ token }`;

		await openConditionalProduct( {
			page,
			requestUtils,
			groupName: `LC Unknown ${ token }`,
			targetDataName: `legacy_unknown_out_${ token }`,
			sourceFields: [
				buildTextField( {
					title: `Notes ${ token }`,
					dataName: sourceId,
				} ),
			],
			rules: [
				{
					elements: sourceId,
					operators: 'operator-from-the-future',
					element_values: 'anything',
				},
			],
		} );

		const output = page.getByLabel( 'Output' );
		const source = page.locator(
			`input[name="ppom[fields][${ sourceId }]"]`
		);

		await expect( output ).toBeHidden();

		await source.fill( 'anything' );
		await source.dispatchEvent( 'change' );
		await expect( output ).toBeHidden();
	} );
	test( 'legacy: PRO-only contains operator stays inert without a Pro license', async ( {
		page,
		requestUtils,
	} ) => {
		const token = uniqueToken();
		const sourceId = `legacy_contains_free_${ token }`;

		await openConditionalProduct( {
			page,
			requestUtils,
			groupName: `LC Contains Free ${ token }`,
			targetDataName: `legacy_contains_free_out_${ token }`,
			sourceFields: [
				buildTextField( {
					title: `Message ${ token }`,
					dataName: sourceId,
				} ),
			],
			rules: [
				{
					elements: sourceId,
					operators: 'contains',
					element_values: '',
					element_constant: 'urgent',
				},
			],
		} );

		const output = page.getByLabel( 'Output' );
		const source = page.locator(
			`input[name="ppom[fields][${ sourceId }]"]`
		);

		await expect( output ).toBeHidden();

		await source.fill( 'this is urgent' );
		await source.dispatchEvent( 'change' );
		await expect( output ).toBeHidden();
	} );

	test( 'legacy: PRO-only odd-number operator stays inert without a Pro license', async ( {
		page,
		requestUtils,
	} ) => {
		const token = uniqueToken();
		const sourceId = `legacy_odd_free_${ token }`;

		await openConditionalProduct( {
			page,
			requestUtils,
			groupName: `LC Odd Free ${ token }`,
			targetDataName: `legacy_odd_free_out_${ token }`,
			sourceFields: [
				buildNumberField( {
					title: `Copies ${ token }`,
					dataName: sourceId,
				} ),
			],
			rules: [
				{
					elements: sourceId,
					operators: 'odd-number',
					element_values: '',
				},
			],
		} );

		const output = page.getByLabel( 'Output' );
		const source = page.locator(
			`input[name="ppom[fields][${ sourceId }]"]`
		);

		await expect( output ).toBeHidden();

		// This operator carries no rule payload, so only the server-side Pro
		// flag can keep it from running on a free install.
		await source.fill( '7' );
		await source.dispatchEvent( 'change' );
		await expect( output ).toBeHidden();
	} );

	test( 'legacy: PRO odd-number operator evaluates with Pro installed and licensed', async ( {
		page,
		requestUtils,
	} ) => {
		await setPpomLicenseFixture( requestUtils, {
			valid: true,
			plan: 3,
			proInstalled: true,
		} );

		const token = uniqueToken();
		const sourceId = `legacy_odd_pro_${ token }`;

		await openConditionalProduct( {
			page,
			requestUtils,
			groupName: `LC Odd Pro ${ token }`,
			targetDataName: `legacy_odd_pro_out_${ token }`,
			sourceFields: [
				buildNumberField( {
					title: `Copies ${ token }`,
					dataName: sourceId,
				} ),
			],
			rules: [
				{
					elements: sourceId,
					operators: 'odd-number',
					element_values: '',
				},
			],
		} );

		const output = page.getByLabel( 'Output' );
		const source = page.locator(
			`input[name="ppom[fields][${ sourceId }]"]`
		);

		await expect( output ).toBeHidden();

		await source.fill( '7' );
		await source.dispatchEvent( 'change' );
		await expect( output ).toBeVisible();

		await source.fill( '8' );
		await source.dispatchEvent( 'change' );
		await expect( output ).toBeHidden();
	} );

	test( 'legacy: a bare RegEx pattern keeps its slash instead of being read as delimiters', async ( {
		page,
		requestUtils,
	} ) => {
		await setPpomLicenseFixture( requestUtils, {
			valid: true,
			plan: 3,
			proInstalled: true,
		} );

		const token = uniqueToken();
		const sourceId = `legacy_regex_bare_${ token }`;

		await openConditionalProduct( {
			page,
			requestUtils,
			groupName: `LC Regex Bare ${ token }`,
			targetDataName: `legacy_regex_bare_out_${ token }`,
			sourceFields: [
				buildTextField( {
					title: `Path ${ token }`,
					dataName: sourceId,
				} ),
			],
			rules: [
				{
					elements: sourceId,
					operators: 'regex',
					element_values: '',
					element_constant: 'foo/bar',
				},
			],
		} );

		const output = page.getByLabel( 'Output' );
		const source = page.locator(
			`input[name="ppom[fields][${ sourceId }]"]`
		);

		// `bar` alone must not match: the pattern is `foo/bar`, not `/bar/`.
		await source.fill( 'bar' );
		await source.dispatchEvent( 'change' );
		await expect( output ).toBeHidden();

		await source.fill( 'foo/bar' );
		await source.dispatchEvent( 'change' );
		await expect( output ).toBeVisible();
	} );

	test( 'legacy: a slash-delimited RegEx pattern still applies its flags', async ( {
		page,
		requestUtils,
	} ) => {
		await setPpomLicenseFixture( requestUtils, {
			valid: true,
			plan: 3,
			proInstalled: true,
		} );

		const token = uniqueToken();
		const sourceId = `legacy_regex_flags_${ token }`;

		await openConditionalProduct( {
			page,
			requestUtils,
			groupName: `LC Regex Flags ${ token }`,
			targetDataName: `legacy_regex_flags_out_${ token }`,
			sourceFields: [
				buildTextField( {
					title: `Code ${ token }`,
					dataName: sourceId,
				} ),
			],
			rules: [
				{
					elements: sourceId,
					operators: 'regex',
					element_values: '',
					element_constant: '/^abc$/i',
				},
			],
		} );

		const output = page.getByLabel( 'Output' );
		const source = page.locator(
			`input[name="ppom[fields][${ sourceId }]"]`
		);

		await source.fill( 'xabc' );
		await source.dispatchEvent( 'change' );
		await expect( output ).toBeHidden();

		await source.fill( 'ABC' );
		await source.dispatchEvent( 'change' );
		await expect( output ).toBeVisible();
	} );

	test( 'legacy: typing keeps its own value in a revealed conditional field', async ( {
		page,
		requestUtils,
	} ) => {
		const token = uniqueToken();
		const sourceId = `legacy_keep_src_${ token }`;
		const targetId = `legacy_keep_out_${ token }`;

		await openConditionalProduct( {
			page,
			requestUtils,
			groupName: `LC Keep ${ token }`,
			targetDataName: targetId,
			sourceFields: [
				buildTextField( {
					title: `Engraving ${ token }`,
					dataName: sourceId,
				} ),
			],
			rules: [
				{
					elements: sourceId,
					operators: 'any',
					element_values: '',
				},
			],
		} );

		const output = page.getByLabel( 'Output' );
		const source = page.locator(
			`input[name="ppom[fields][${ sourceId }]"]`
		);

		await source.pressSequentially( 'Ana' );
		await expect( output ).toBeVisible();

		await output.pressSequentially( 'Keep me' );
		await expect( output ).toHaveValue( 'Keep me' );

		// Recalculation runs on every keystroke; the revealed target must not be
		// reset by the lifecycle events those recalculations would emit.
		await source.press( 'End' );
		await source.pressSequentially( 'stasia' );
		await expect( output ).toHaveValue( 'Keep me' );
		await expect( source ).toHaveValue( 'Anastasia' );
	} );

	test( 'legacy: lifecycle events fire only when visibility actually flips', async ( {
		page,
		requestUtils,
	} ) => {
		const token = uniqueToken();
		const sourceId = `legacy_events_src_${ token }`;

		await openConditionalProduct( {
			page,
			requestUtils,
			groupName: `LC Events ${ token }`,
			targetDataName: `legacy_events_out_${ token }`,
			sourceFields: [
				buildTextField( {
					title: `Engraving ${ token }`,
					dataName: sourceId,
				} ),
			],
			rules: [
				{
					elements: sourceId,
					operators: 'any',
					element_values: '',
				},
			],
		} );

		const output = page.getByLabel( 'Output' );
		const source = page.locator(
			`input[name="ppom[fields][${ sourceId }]"]`
		);

		await page.evaluate( () => {
			window.ppomLifecycleEvents = [];
			window.jQuery( document ).on(
				'ppom_field_shown ppom_field_hidden',
				( event ) => window.ppomLifecycleEvents.push( event.type )
			);
		} );

		await source.pressSequentially( 'A' );
		await expect( output ).toBeVisible();
		expect(
			await page.evaluate( () => window.ppomLifecycleEvents )
		).toEqual( [ 'ppom_field_shown' ] );

		// Six more keystrokes, same visibility: no further events.
		await source.pressSequentially( 'nastas' );
		expect(
			await page.evaluate( () => window.ppomLifecycleEvents )
		).toEqual( [ 'ppom_field_shown' ] );

		await source.fill( '' );
		await source.dispatchEvent( 'change' );
		await expect( output ).toBeHidden();
		expect(
			await page.evaluate( () => window.ppomLifecycleEvents )
		).toEqual( [ 'ppom_field_shown', 'ppom_field_hidden' ] );
	} );

	test( 'legacy: not-contains requires every selected value to satisfy the rule', async ( {
		page,
		requestUtils,
	} ) => {
		await setPpomLicenseFixture( requestUtils, {
			valid: true,
			plan: 3,
			proInstalled: true,
		} );

		const token = uniqueToken();
		const sourceId = `legacy_ncontains_${ token }`;

		await openConditionalProduct( {
			page,
			requestUtils,
			groupName: `LC Not Contains ${ token }`,
			targetDataName: `legacy_ncontains_out_${ token }`,
			sourceFields: [
				buildCheckboxField( {
					title: `Extras ${ token }`,
					dataName: sourceId,
					options: [
						{ label: 'foo', value: 'foo' },
						{ label: 'bar', value: 'bar' },
					],
				} ),
			],
			rules: [
				{
					elements: sourceId,
					operators: 'not-contains',
					element_values: '',
					element_constant: 'foo',
				},
			],
		} );

		const output = page.getByLabel( 'Output' );
		const boxes = page.locator(
			`input[name="ppom[fields][${ sourceId }][]"]`
		);

		await boxes.nth( 1 ).check();
		await expect( output ).toBeVisible();

		// `foo` is now part of the selection, so "does not contain foo" is false
		// even though `bar` on its own would satisfy it.
		await boxes.nth( 0 ).check();
		await expect( output ).toBeHidden();

		await boxes.nth( 0 ).uncheck();
		await expect( output ).toBeVisible();
	} );
} );
