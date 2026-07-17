/**
 * WordPress dependencies
 */
import { test, expect } from '@wordpress/e2e-test-utils-playwright';

import {
	attachPpomGroupToProducts,
	buildCheckboxField,
	buildSelectField,
	createPpomGroup,
	createSimpleProduct,
} from '../fixtures/index.js';

/**
 * Regression for Codeinwp/ppom-pro#572.
 *
 * The Pro CSV import strips empty meta keys, so re-imported checkbox fields
 * have no `checked` key at all. The conditions engine crashed on
 * `field.checked.split()` every time such a field was re-shown, killing
 * conditional logic after a few show/hide cycles.
 */
test.describe( 'Checkbox conditions on import-shaped meta', () => {
	test( 'conditional checkbox fields keep toggling when meta has no checked key', async ( {
		page,
		requestUtils,
	} ) => {
		const token = `${ Date.now() }_${ Math.floor( Math.random() * 1e6 ) }`;
		const sourceId = `switch_${ token }`;

		// Simulate the import: checkbox metas without a `checked` key.
		const cb1 = buildCheckboxField( {
			title: 'Check One',
			dataName: `check_1_${ token }`,
			logic: 'on',
			conditions: {
				visibility: 'Show',
				bound: 'All',
				rules: [
					{
						elements: sourceId,
						operators: 'is',
						element_values: 'First',
					},
				],
			},
			options: [
				{ label: 'A', value: 'a' },
				{ label: 'B', value: 'b' },
			],
		} );
		const cb2 = buildCheckboxField( {
			title: 'Check Two',
			dataName: `check_2_${ token }`,
			logic: 'on',
			conditions: {
				visibility: 'Show',
				bound: 'All',
				rules: [
					{
						elements: sourceId,
						operators: 'is',
						element_values: 'Second',
					},
				],
			},
			options: [
				{ label: 'C', value: 'c' },
				{ label: 'D', value: 'd' },
			],
		} );
		delete cb1.checked;
		delete cb2.checked;

		const product = await createSimpleProduct( requestUtils );
		const { ppomId } = await createPpomGroup( requestUtils, {
			groupName: `Checkbox Conditions Import ${ token }`,
			fields: [
				buildSelectField( {
					title: `Switch ${ token }`,
					dataName: sourceId,
					options: [
						{ label: 'First', value: 'first' },
						{ label: 'Second', value: 'second' },
						{ label: 'Third', value: 'third' },
					],
				} ),
				cb1,
				cb2,
			],
		} );
		await attachPpomGroupToProducts( requestUtils, {
			ppomId,
			productIds: [ product.id ],
		} );

		const pageErrors = [];
		page.on( 'pageerror', ( error ) => pageErrors.push( error.message ) );

		await page.goto( `/?p=${ product.id }` );

		const source = page.locator(
			`select[name="ppom[fields][${ sourceId }]"]`
		);
		const check1 = page
			.locator( `input[name="ppom[fields][check_1_${ token }][]"]` )
			.first();
		const check2 = page
			.locator( `input[name="ppom[fields][check_2_${ token }][]"]` )
			.first();

		// The issue's click sequence: First, Second, Third, First, Second, First.
		const sequence = [
			[ 'First', true, false ],
			[ 'Second', false, true ],
			[ 'Third', false, false ],
			[ 'First', true, false ],
			[ 'Second', false, true ],
			[ 'First', true, false ],
		];

		for ( const [ value, cb1Visible, cb2Visible ] of sequence ) {
			await source.selectOption( { label: value } );
			await ( cb1Visible
				? expect( check1 ).toBeVisible()
				: expect( check1 ).toBeHidden() );
			await ( cb2Visible
				? expect( check2 ).toBeVisible()
				: expect( check2 ).toBeHidden() );
		}

		// The conditions engine must survive the whole cycle without throwing.
		expect( pageErrors ).toEqual( [] );
	} );
} );
