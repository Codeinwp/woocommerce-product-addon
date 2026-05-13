/**
 * WordPress dependencies
 */
import { test, expect } from '@wordpress/e2e-test-utils-playwright';

import {
	attachPpomGroupToProducts,
	buildCheckboxField,
	createPpomGroup,
	createSimpleProduct,
} from '../fixtures/index.js';

const CHECKBOX_OPTIONS = {
	CHECKED_OPTION_1: 'yes',
	CHECKED_OPTION_2: 'no',
	UNCHECKED_OPTION_1: 'maybe',
};

test.describe( 'Checkbox', () => {
	/***
	 * Create a simple checkbox with 3 options. Two will be marked as checked by default. Check if the selection is respected on rendering.
	 */
	test( 'check default selected options', async ( {
		page,
		requestUtils,
	} ) => {
		const fieldId = 'checkbox_test';
		const product = await createSimpleProduct( requestUtils );
		const { ppomId } = await createPpomGroup( requestUtils, {
			groupName: 'Default Value for Checkbox',
			fields: [
				buildCheckboxField( {
					title: 'Checkbox Default values',
					dataName: fieldId,
					checked: [
						CHECKBOX_OPTIONS.CHECKED_OPTION_1,
						CHECKBOX_OPTIONS.CHECKED_OPTION_2,
					],
					options: [
						{
							label: CHECKBOX_OPTIONS.CHECKED_OPTION_1,
							value: CHECKBOX_OPTIONS.CHECKED_OPTION_1,
						},
						{
							label: CHECKBOX_OPTIONS.CHECKED_OPTION_2,
							value: CHECKBOX_OPTIONS.CHECKED_OPTION_2,
						},
						{
							label: CHECKBOX_OPTIONS.UNCHECKED_OPTION_1,
							value: CHECKBOX_OPTIONS.UNCHECKED_OPTION_1,
						},
					],
				} ),
			],
		} );

		await attachPpomGroupToProducts( requestUtils, {
			ppomId,
			productIds: [ product.id ],
		} );

		await page.goto( `/?ppom_e2e_product_page=${ product.id }` );

		await expect(
			page.locator(
				`input[name="ppom[fields][${ fieldId }][]"][value="${ CHECKBOX_OPTIONS.CHECKED_OPTION_1 }"]`
			)
		).toBeChecked();
		await expect(
			page.locator(
				`input[name="ppom[fields][${ fieldId }][]"][value="${ CHECKBOX_OPTIONS.CHECKED_OPTION_2 }"]`
			)
		).toBeChecked();
		await expect(
			page.locator(
				`input[name="ppom[fields][${ fieldId }][]"][value="${ CHECKBOX_OPTIONS.UNCHECKED_OPTION_1 }"]`
			)
		).not.toBeChecked();
	} );
} );
