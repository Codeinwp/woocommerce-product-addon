/**
 * WordPress dependencies
 */
import { test, expect } from '@wordpress/e2e-test-utils-playwright';

import { buildPalettesField } from '../fixtures/fields.js';
import { setPpomLicenseFixture } from '../fixtures/license.js';
import { createPpomGroup, createSimpleTextGroup } from '../fixtures/ppom.js';

async function visitReactModalGroup( admin, ppomId ) {
	await admin.visitAdminPage(
		`admin.php?page=ppom&productmeta_id=${ ppomId }&do_meta=edit&ppom_react_modal=1`
	);
}

async function openFirstFieldReactModal( page ) {
	await page.locator( '#ppom_sort_id_1 .ppom-edit-field' ).click();
	const dialog = page.getByRole( 'dialog' ).first();
	await expect( dialog ).toBeVisible();
	return dialog;
}

async function openAdvancedSettings( dialog ) {
	const toggle = dialog.getByText( /Show advanced settings/i );
	if ( ( await toggle.count() ) > 0 ) {
		await toggle.click();
	}
}

async function optionValues( dialog ) {
	return dialog.locator( 'input[placeholder="Option"]' ).evaluateAll(
		( inputs ) => inputs.map( ( input ) => input.value )
	);
}

test.describe( 'React field modal (opt-in)', () => {
	test( 'picker entry opens FieldTypePicker (Add field)', async ( {
		page,
		admin,
		requestUtils,
	} ) => {
		const { ppomId } = await createSimpleTextGroup( requestUtils, {
			fieldsNumber: 1,
		} );

		await visitReactModalGroup( admin, ppomId );

		await expect(
			page.locator( 'button[data-modal-id="ppom_fields_model_id"]' )
		).toHaveCount( 0 );

		const addFieldButton = page.getByRole( 'button', {
			name: 'Add field',
			exact: true,
		} );
		await expect( addFieldButton ).toHaveClass( /btn-primary/ );
		await addFieldButton.click();

		const dialog = page.getByRole( 'dialog' ).first();
		await expect( dialog ).toBeVisible();

		await expect( dialog.getByRole( 'tab', { name: 'Text' } ) ).toBeVisible();
		await dialog.getByRole( 'tab', { name: 'Text' } ).click();
		await expect(
			dialog.getByRole( 'button', { name: /Text Input/i } ).first()
		).toBeVisible();
	} );

	test( 'per-field edit button opens sidebar without inner Add field CTA', async ( {
		page,
		admin,
		requestUtils,
	} ) => {
		const { ppomId } = await createSimpleTextGroup( requestUtils, {
			fieldsNumber: 1,
		} );

		await visitReactModalGroup( admin, ppomId );

		const dialog = await openFirstFieldReactModal( page );
		await expect(
			dialog.getByRole( 'button', { name: 'Add field', exact: true } )
		).toHaveCount( 0 );
	} );

	test( 'per-field edit button shows Settings tab for definition-driven text field', async ( {
		page,
		admin,
		requestUtils,
	} ) => {
		const { ppomId } = await createSimpleTextGroup( requestUtils, {
			fieldsNumber: 1,
		} );

		await visitReactModalGroup( admin, ppomId );

		const dialog = await openFirstFieldReactModal( page );
		await expect(
			dialog.getByRole( 'tab', { name: /Settings/i } )
		).toBeVisible();
	} );

	test( 'opening an existing field can close without confirmation when unchanged', async ( {
		page,
		admin,
		requestUtils,
	} ) => {
		const { ppomId } = await createSimpleTextGroup( requestUtils, {
			fieldsNumber: 1,
		} );

		await visitReactModalGroup( admin, ppomId );

		const dialog = await openFirstFieldReactModal( page );
		await dialog.getByRole( 'button', { name: 'Cancel' } ).click();

		await expect( dialog ).toBeHidden();
	} );

	test( 'editing an existing field requires close confirmation', async ( {
		page,
		admin,
		requestUtils,
	} ) => {
		const { ppomId } = await createSimpleTextGroup( requestUtils, {
			fieldsNumber: 1,
		} );

		await visitReactModalGroup( admin, ppomId );

		const dialog = await openFirstFieldReactModal( page );
		await dialog.getByLabel( 'Title' ).fill( 'Changed React modal title' );
		await dialog.getByRole( 'button', { name: 'Cancel' } ).click();

		await expect( dialog ).toBeVisible();
		await expect(
			dialog.getByRole( 'button', { name: 'Confirm' } )
		).toBeVisible();
	} );

	test( 'selecting a new field type can close without confirmation when unchanged', async ( {
		page,
		admin,
		requestUtils,
	} ) => {
		const { ppomId } = await createSimpleTextGroup( requestUtils, {
			fieldsNumber: 1,
		} );

		await visitReactModalGroup( admin, ppomId );

		await page.getByRole( 'button', { name: 'Add field', exact: true } ).click();
		const dialog = page.getByRole( 'dialog' ).first();
		await expect( dialog ).toBeVisible();

		await dialog.getByRole( 'tab', { name: 'Text' } ).click();
		await dialog
			.locator( 'button[aria-label^="Text Input"]' )
			.last()
			.click();
		await dialog.getByRole( 'button', { name: 'Cancel' } ).click();

		await expect( dialog ).toBeHidden();
	} );

	test( 'editing a newly selected field requires close confirmation', async ( {
		page,
		admin,
		requestUtils,
	} ) => {
		const { ppomId } = await createSimpleTextGroup( requestUtils, {
			fieldsNumber: 1,
		} );

		await visitReactModalGroup( admin, ppomId );

		await page.getByRole( 'button', { name: 'Add field', exact: true } ).click();
		const dialog = page.getByRole( 'dialog' ).first();
		await expect( dialog ).toBeVisible();

		await dialog.getByRole( 'tab', { name: 'Text' } ).click();
		await dialog
			.locator( 'button[aria-label^="Text Input"]' )
			.last()
			.click();
		await dialog.getByLabel( 'Title' ).fill( 'New edited field' );
		await dialog.getByRole( 'button', { name: 'Cancel' } ).click();

		await expect( dialog ).toBeVisible();
		await expect(
			dialog.getByRole( 'button', { name: 'Confirm' } )
		).toBeVisible();
	} );

	test( 'matrix-style options reorder, add, and remove', async ( {
		page,
		admin,
		requestUtils,
	} ) => {
		await setPpomLicenseFixture( requestUtils, {
			valid: true,
			plan: 3,
			proInstalled: true,
		} );

		const { ppomId } = await createPpomGroup( requestUtils, {
			groupName: 'React modal matrix options',
			fields: [
				buildPalettesField( {
					title: 'Palette Options',
					dataName: 'palette_options',
					options: [
						{
							option: 'Small',
							price: '10',
							label: 'Small label',
							id: 'small',
						},
						{
							option: 'Large',
							price: '20',
							label: 'Large label',
							id: 'large',
						},
					],
				} ),
			],
		} );

		await visitReactModalGroup( admin, ppomId );

		let dialog = await openFirstFieldReactModal( page );
		await openAdvancedSettings( dialog );

		await expect(
			dialog.locator( 'input[placeholder="Option"]' )
		).toHaveCount( 2 );
		expect( await optionValues( dialog ) ).toEqual( [ 'Small', 'Large' ] );

		const dragHandles = dialog.getByRole( 'button', {
			name: 'Drag to reorder',
		} );
		await dragHandles.nth( 1 ).focus();
		await page.keyboard.press( 'ArrowUp' );
		expect( await optionValues( dialog ) ).toEqual( [ 'Large', 'Small' ] );

		await dialog.getByRole( 'button', { name: 'Add option' } ).click();
		await expect(
			dialog.locator( 'input[placeholder="Option"]' )
		).toHaveCount( 3 );
		await dialog
			.locator( 'input[placeholder="Option"]' )
			.nth( 2 )
			.click();
		await page.keyboard.type( 'Medium' );
		await expect(
			dialog.locator( 'input[placeholder="Option"]' ).nth( 2 )
		).toHaveValue( 'Medium' );

		await dialog
			.getByRole( 'button', { name: 'Remove' } )
			.first()
			.click();
		expect( await optionValues( dialog ) ).toEqual( [ 'Small', 'Medium' ] );
	} );
} );
