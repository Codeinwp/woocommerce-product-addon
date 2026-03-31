/**
 * WordPress dependencies
 */
import { test, expect } from '@wordpress/e2e-test-utils-playwright';

import {
	addNewField,
	addNewOptionInModal,
	attachGroupToProduct,
	clearCart,
	enableConditionsInModal,
	fillFieldNameAndId,
	fillOptionNameAndValue,
	pickFieldTypeInModal,
	saveFieldInModal,
	saveFields,
	switchToConditionsModalTab,
} from '../utils';

const RUN_SUFFIX = Date.now();

test.describe( 'Conditions', () => {
	/***
	 * Create two select fields (with two options each) and one text field. Display the text field if the second option of the second select is selected.
	 */
	test( 'conditions with Select input', async ( { page, admin } ) => {
		await admin.visitAdminPage( 'admin.php?page=ppom' );

		await page.getByRole( 'link', { name: 'Add New Group' } ).click();
		await page
			.getByRole( 'textbox' )
			.fill( 'Test Group Field with Select Conditions' );

		const randomNumber = Math.floor( Math.random() * 1000 );
		const numOfInputFields = 2;

		for ( let i = 1; i <= numOfInputFields; i++ ) {
			await page.getByRole( 'button', { name: 'Add field' } ).click();
			await pickFieldTypeInModal( page, 'select' );

			await fillFieldNameAndId(
				page,
				i,
				`Select Input ${ randomNumber + i - 1 }`,
				`select_test_${ randomNumber + i - 1 }`
			);

			await page
				.locator( `#ppom_field_model_${ i }` )
				.getByText( 'Add Options', { exact: true } )
				.click();

			await fillOptionNameAndValue( page, i, 0, 'Option 1', 'option_1' );
			await addNewOptionInModal( page, i );
			await fillOptionNameAndValue( page, i, 1, 'Option 2', 'option_2' );

			await saveFieldInModal( page, i );
		}

		await addNewField( page );
		await pickFieldTypeInModal( page, 'text' );
		await fillFieldNameAndId(
			page,
			numOfInputFields + 1,
			'Output',
			'output_test'
		);

		await saveFields( page );
		await page.waitForLoadState( 'networkidle' );
		await page.reload();

		await page
			.locator(
				`#ppom_sort_id_${ numOfInputFields + 1 } .ppom-edit-field`
			)
			.click();

		await switchToConditionsModalTab( page, numOfInputFields + 1 );
		await enableConditionsInModal( page, numOfInputFields + 1 );

		await page
			.locator(
				`select[name="ppom\\[${
					numOfInputFields + 1
				}\\]\\[conditions\\]\\[rules\\]\\[0\\]\\[elements\\]"]`
			)
			.selectOption( { index: 1 } );

		await page
			.locator(
				`select[name="ppom\\[${
					numOfInputFields + 1
				}\\]\\[conditions\\]\\[rules\\]\\[0\\]\\[operators\\]"]`
			)
			.selectOption( { index: 0 } );

		await page
			.locator(
				`select[name="ppom\\[${
					numOfInputFields + 1
				}\\]\\[conditions\\]\\[rules\\]\\[0\\]\\[element_values\\]"]`
			)
			.selectOption( { index: 1 } );

		await saveFieldInModal( page, numOfInputFields + 1 );
		await saveFields( page );

		await page.waitForLoadState( 'networkidle' );

		await page.reload();

		const productId = await attachGroupToProduct( page, 'Product 1' );

		await page.goto( `/?p=${ productId }` );

		await expect( page.getByLabel( 'Output' ) ).toBeHidden();

		await page
			.locator(
				`select[name="ppom[fields][select_test_${ randomNumber + 1 }]"]`
			)
			.selectOption( { index: 1 } );

		await expect( page.getByLabel( 'Output' ) ).toBeVisible();
	} );

	test( '@critical hidden required fields stop blocking only after the condition hides them', async ( {
		page,
		admin,
	} ) => {
		const selectFieldId = `condition_gate_${ RUN_SUFFIX }`;
		const outputFieldId = `conditional_output_${ RUN_SUFFIX }`;
		const outputError = 'Please enter the revealed value';

		await clearCart( page );
		await admin.visitAdminPage( 'admin.php?page=ppom' );

		await page.getByRole( 'link', { name: 'Add New Group' } ).click();
		await page
			.getByRole( 'textbox' )
			.fill( `Required Conditions ${ RUN_SUFFIX }` );

		await addNewField( page );
		await pickFieldTypeInModal( page, 'select' );
		await fillFieldNameAndId( page, 1, 'Visibility Gate', selectFieldId );

		await page
			.locator( '#ppom_field_model_1' )
			.getByText( 'Add Options', { exact: true } )
			.click();
		await fillOptionNameAndValue( page, 1, 0, 'Hide output', 'hide_output' );
		await addNewOptionInModal( page, 1 );
		await fillOptionNameAndValue( page, 1, 1, 'Show output', 'show_output' );
		await saveFieldInModal( page, 1 );

		await addNewField( page );
		await pickFieldTypeInModal( page, 'text' );
		await fillFieldNameAndId( page, 2, 'Conditional Output', outputFieldId );
		await page
			.locator( 'input[name="ppom\\[2\\]\\[error_message\\]"]' )
			.fill( outputError );
		await page
			.locator( 'input[name="ppom\\[2\\]\\[required\\]"]' )
			.check( { force: true } );
		await saveFieldInModal( page, 2 );

		await saveFields( page );
		await page.waitForLoadState( 'networkidle' );
		await page.reload();

		await page.locator( '#ppom_sort_id_2 .ppom-edit-field' ).click();
		await switchToConditionsModalTab( page, 2 );
		await enableConditionsInModal( page, 2 );

		await page
			.locator(
				'select[name="ppom\\[2\\]\\[conditions\\]\\[rules\\]\\[0\\]\\[elements\\]"]'
			)
			.selectOption( selectFieldId );
		await page
			.locator(
				'select[name="ppom\\[2\\]\\[conditions\\]\\[rules\\]\\[0\\]\\[operators\\]"]'
			)
			.selectOption( { index: 0 } );
		await page
			.locator(
				'select[name="ppom\\[2\\]\\[conditions\\]\\[rules\\]\\[0\\]\\[element_values\\]"]'
			)
			.selectOption( { index: 1 } );

		await saveFieldInModal( page, 2 );
		await saveFields( page );
		await page.waitForLoadState( 'networkidle' );
		await page.reload();

		const productId = await attachGroupToProduct( page, 'Product 1' );

		await page.goto( `/?p=${ productId }` );
		await expect( page.getByLabel( 'Conditional Output' ) ).toBeHidden();

		const addToCartButton = page.locator( 'form.cart' ).getByRole(
			'button',
			{
				name: 'Add to cart',
				exact: true,
			}
		);

		await addToCartButton.click();
		await page.waitForLoadState( 'networkidle' );
		await page.goto( '/cart/' );
		await expect( page.getByText( 'Product 1' ) ).toBeVisible();
		await expect( page.locator( 'body' ) ).not.toContainText( outputError );

		await clearCart( page );
		await page.goto( `/?p=${ productId }` );
		await page
			.locator( `select[name="ppom[fields][${ selectFieldId }]"]` )
			.selectOption( { index: 1 } );

		const outputInput = page.locator(
			`input[name="ppom[fields][${ outputFieldId }]"]`
		);

		await expect( outputInput ).toBeVisible();
		await addToCartButton.click();
		await expect( page.locator( 'body' ) ).toContainText(
			`Conditional Output: ${ outputError }`
		);

		await outputInput.fill( 'Condition satisfied' );
		await addToCartButton.click();
		await page.waitForLoadState( 'networkidle' );
		await page.goto( '/cart/' );

		await expect( page.getByText( 'Product 1' ) ).toBeVisible();
		await expect( page.locator( 'body' ) ).toContainText(
			'Condition satisfied'
		);
	} );
} );
