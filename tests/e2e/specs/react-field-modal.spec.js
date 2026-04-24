/**
 * WordPress dependencies
 */
import { test, expect } from '@wordpress/e2e-test-utils-playwright';

import { createSimpleGroupField } from '../utils';

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

test.describe( 'React field modal (opt-in)', () => {
	test( 'picker entry opens FieldTypePicker (Add New Field)', async ( {
		page,
		admin,
	} ) => {
		const { ppomId } = await createSimpleGroupField( admin, page, 1 );

		await visitReactModalGroup( admin, ppomId );

		await page.getByRole( 'button', { name: 'Add New Field' } ).click();

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
	} ) => {
		const { ppomId } = await createSimpleGroupField( admin, page, 1 );

		await visitReactModalGroup( admin, ppomId );

		const dialog = await openFirstFieldReactModal( page );
		await expect(
			dialog.getByRole( 'button', { name: 'Add field', exact: true } )
		).toHaveCount( 0 );
	} );

	test( 'per-field edit button shows Settings tab for definition-driven text field', async ( {
		page,
		admin,
	} ) => {
		const { ppomId } = await createSimpleGroupField( admin, page, 1 );

		await visitReactModalGroup( admin, ppomId );

		const dialog = await openFirstFieldReactModal( page );
		await expect(
			dialog.getByRole( 'tab', { name: /Settings/i } )
		).toBeVisible();
	} );

	test( 'opening an existing field can close without confirmation when unchanged', async ( {
		page,
		admin,
	} ) => {
		const { ppomId } = await createSimpleGroupField( admin, page, 1 );

		await visitReactModalGroup( admin, ppomId );

		const dialog = await openFirstFieldReactModal( page );
		await dialog.getByRole( 'button', { name: 'Cancel' } ).click();

		await expect( dialog ).toBeHidden();
	} );

	test( 'editing an existing field requires close confirmation', async ( {
		page,
		admin,
	} ) => {
		const { ppomId } = await createSimpleGroupField( admin, page, 1 );

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
	} ) => {
		const { ppomId } = await createSimpleGroupField( admin, page, 1 );

		await visitReactModalGroup( admin, ppomId );

		await page.getByRole( 'button', { name: 'Add New Field' } ).click();
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
	} ) => {
		const { ppomId } = await createSimpleGroupField( admin, page, 1 );

		await visitReactModalGroup( admin, ppomId );

		await page.getByRole( 'button', { name: 'Add New Field' } ).click();
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
} );
