/**
 * Regression test for issue #722: TextCounter field settings were missing
 * from the React admin field modal because the textcounter slug was
 * registered with the plain `text` field's schema and block layout instead
 * of a dedicated definition that includes count_type, count_price,
 * textarea_row, counter_color, counter_bg_color, enable_textinput, and
 * enabled_space.
 */
import { test, expect } from '@wordpress/e2e-test-utils-playwright';

import { buildTextCounterField } from '../fixtures/fields.js';
import { setPpomLicenseFixture } from '../fixtures/license.js';
import { createPpomGroup } from '../fixtures/ppom.js';
import { saveFields } from '../utils.js';

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
	const toggle = dialog.getByRole( 'button', { name: /Advanced settings/i } );
	if ( ( await toggle.count() ) > 0 ) {
		await toggle.click();
	}
}

test.describe( 'React field modal — TextCounter settings (regression #722)', () => {
	test( 'TextCounter field shows all counter-specific settings in the modal', async ( {
		page,
		admin,
		requestUtils,
	} ) => {
		await setPpomLicenseFixture( requestUtils, {
			valid: true,
			plan: 1,
			proInstalled: true,
		} );

		const suffix = Date.now();
		const { ppomId } = await createPpomGroup( requestUtils, {
			groupName: `TextCounter React Modal ${ suffix }`,
			fields: [
				buildTextCounterField( {
					title: `Engraving ${ suffix }`,
					dataName: `engraving_${ suffix }`,
					countType: 'character',
					maxlength: '25',
					countPrice: '2',
				} ),
			],
		} );

		await visitReactModalGroup( admin, ppomId );

		const dialog = await openFirstFieldReactModal( page );

		// --- Field Settings section: count_type + maxlength ---
		await expect(
			dialog.getByText( 'Count Type', { exact: true } )
		).toBeVisible();
		await expect(
			dialog.getByText( 'Max. Character/Word', { exact: true } )
		).toBeVisible();
		await expect(
			dialog.getByText( 'Textarea Row', { exact: true } )
		).toBeVisible();

		// --- Field Settings section: checkboxes ---
		await expect(
			dialog.getByText( 'Enable Text Input', { exact: true } )
		).toBeVisible();
		await expect(
			dialog.getByText( 'Include Space', { exact: true } )
		).toBeVisible();

		// --- Pricing section: count_price ---
		await expect(
			dialog.getByText( 'Character/Word Price', { exact: true } )
		).toBeVisible();

		// The count_price input should reflect the saved value.
		await expect(
			dialog.getByLabel( 'Character/Word Price' )
		).toHaveValue( '2' );

		// --- Advanced: counter colors ---
		await openAdvancedSettings( dialog );

		await expect(
			dialog.getByText( 'Counter text Color', { exact: true } )
		).toBeVisible();
		await expect(
			dialog.getByText( 'Counter Background Color', { exact: true } )
		).toBeVisible();
	} );

	test( 'editing count_price in the React modal persists through classic save', async ( {
		page,
		admin,
		requestUtils,
	} ) => {
		await setPpomLicenseFixture( requestUtils, {
			valid: true,
			plan: 1,
			proInstalled: true,
		} );

		const suffix = Date.now();
		const { ppomId } = await createPpomGroup( requestUtils, {
			groupName: `TextCounter Persist ${ suffix }`,
			fields: [
				buildTextCounterField( {
					title: `Persist ${ suffix }`,
					dataName: `persist_${ suffix }`,
					countType: 'character',
					maxlength: '30',
					countPrice: '1',
				} ),
			],
		} );

		await visitReactModalGroup( admin, ppomId );

		let dialog = await openFirstFieldReactModal( page );

		// Change the per-character price.
		await dialog.getByLabel( 'Character/Word Price' ).fill( '5' );
		await dialog.getByRole( 'button', { name: 'Update Field' } ).click();
		await expect( dialog ).toBeHidden();

		// Persist via the classic Save Fields button.
		page.once( 'dialog', ( browserDialog ) => browserDialog.accept() );
		const reloaded = page.waitForEvent( 'load' );
		await saveFields( page );
		await reloaded;

		// Re-open the modal and verify the value persisted.
		await visitReactModalGroup( admin, ppomId );
		dialog = await openFirstFieldReactModal( page );
		await expect(
			dialog.getByLabel( 'Character/Word Price' )
		).toHaveValue( '5' );
	} );
} );
