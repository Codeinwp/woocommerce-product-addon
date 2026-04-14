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

test.describe( 'Conditions', () => {
	/***
	 * Create two select fields (with two options each) and one text field. Display the text field if the second option of the second select is selected.
	 */
	test( 'conditions with Select input', async ( { page, requestUtils } ) => {
		const optionOne = { label: 'Option 1', value: 'option_1' };
		const optionTwo = { label: 'Option 2', value: 'option_2' };
		const randomNumber = Math.floor( Math.random() * 1000 );
		const firstSelectFieldId = `select_test_${ randomNumber }`;
		const secondSelectFieldId = `select_test_${ randomNumber + 1 }`;
		const product = await createSimpleProduct( requestUtils );
		const { ppomId } = await createPpomGroup( requestUtils, {
			groupName: 'Test Group Field with Select Conditions',
			fields: [
				buildSelectField( {
					title: `Select Input ${ randomNumber }`,
					dataName: firstSelectFieldId,
					options: [ optionOne, optionTwo ],
				} ),
				buildSelectField( {
					title: `Select Input ${ randomNumber + 1 }`,
					dataName: secondSelectFieldId,
					options: [ optionOne, optionTwo ],
				} ),
				buildTextField( {
					title: 'Output',
					dataName: 'output_test',
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

		const controllingSelect = page.locator(
			`select[name="ppom[fields][${ secondSelectFieldId }]"]`
		);

		await controllingSelect.selectOption( { label: optionTwo.label } );
		await expect( controllingSelect ).toHaveValue( optionTwo.label );

		await expect( page.getByLabel( 'Output' ) ).toBeVisible();
	} );
} );
