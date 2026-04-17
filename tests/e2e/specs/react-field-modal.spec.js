/**
 * WordPress dependencies
 */
import { test, expect } from '@wordpress/e2e-test-utils-playwright';

import { createSimpleGroupField } from '../utils';

test.describe( 'React field modal (opt-in)', () => {
	test( 'picker entry opens FieldTypePicker (Add New Field)', async ( {
		page,
		admin,
	} ) => {
		const { ppomId } = await createSimpleGroupField( admin, page, 1 );

		await admin.visitAdminPage(
			`admin.php?page=ppom&productmeta_id=${ ppomId }&do_meta=edit&ppom_react_modal=1`
		);

		await page.getByRole( 'button', { name: 'Add New Field' } ).click();

		const dialog = page.getByRole( 'dialog' ).first();
		await expect( dialog ).toBeVisible();

		await expect( dialog.getByRole( 'tab', { name: 'Text' } ) ).toBeVisible();
		await dialog.getByRole( 'tab', { name: 'Text' } ).click();
		await expect(
			dialog.getByRole( 'button', { name: /Text Input/i } ).first()
		).toBeVisible();
	} );

	test( 'manage entry opens sidebar without inner Add field CTA', async ( {
		page,
		admin,
	} ) => {
		const { ppomId } = await createSimpleGroupField( admin, page, 1 );

		await admin.visitAdminPage(
			`admin.php?page=ppom&productmeta_id=${ ppomId }&do_meta=edit&ppom_react_modal=1`
		);

		await page.getByRole( 'button', { name: 'Manage Fields' } ).click();

		const dialog = page.getByRole( 'dialog' ).first();
		await expect( dialog ).toBeVisible();
		await expect(
			dialog.getByRole( 'button', { name: 'Add field', exact: true } )
		).toHaveCount( 0 );
	} );

	test( 'manage entry shows Settings tab for definition-driven text field', async ( {
		page,
		admin,
	} ) => {
		const { ppomId } = await createSimpleGroupField( admin, page, 1 );

		await admin.visitAdminPage(
			`admin.php?page=ppom&productmeta_id=${ ppomId }&do_meta=edit&ppom_react_modal=1`
		);

		await page.getByRole( 'button', { name: 'Manage Fields' } ).click();

		const dialog = page.getByRole( 'dialog' ).first();
		await expect( dialog ).toBeVisible();
		await expect(
			dialog.getByRole( 'tab', { name: /Settings/i } )
		).toBeVisible();
	} );
} );
