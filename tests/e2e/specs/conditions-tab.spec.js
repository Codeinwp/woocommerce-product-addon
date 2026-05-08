/**
 * WordPress dependencies
 */
import { test, expect } from '@wordpress/e2e-test-utils-playwright';

import { buildTextField } from '../fixtures/fields.js';
import { setPpomLicenseFixture } from '../fixtures/license.js';
import { createPpomGroup, createSimpleTextGroup } from '../fixtures/ppom.js';
import { saveFields } from '../utils.js';

function uniqueToken() {
	return `${ Date.now() }_${ Math.floor( Math.random() * 1e6 ) }`;
}

async function createTwoFieldGroup( requestUtils, { titlePrefix } ) {
	const token = uniqueToken();
	const controlledDataName = `${ titlePrefix }_a_${ token }`;
	const controllingDataName = `${ titlePrefix }_b_${ token }`;
	const fields = [
		buildTextField( {
			title: `${ titlePrefix } A ${ token }`,
			dataName: controlledDataName,
		} ),
		buildTextField( {
			title: `${ titlePrefix } B ${ token }`,
			dataName: controllingDataName,
		} ),
	];

	const { ppomId } = await createPpomGroup( requestUtils, {
		groupName: `Conditions Group ${ token }`,
		fields,
	} );

	return { ppomId, controlledDataName, controllingDataName };
}

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

async function openConditionsTab( dialog ) {
	await dialog.getByRole( 'tab', { name: /Conditions/i } ).click();
}

async function enableProConditions( requestUtils ) {
	await setPpomLicenseFixture( requestUtils, {
		valid: true,
		plan: 1,
		proInstalled: true,
	} );
}

async function lockConditions( requestUtils ) {
	await setPpomLicenseFixture( requestUtils, {
		valid: false,
		plan: 1,
		proInstalled: false,
	} );
}

async function saveFieldsAndRevisit( page, admin, ppomId ) {
	page.once( 'dialog', ( browserDialog ) => browserDialog.accept() );
	const reloaded = page.waitForEvent( 'load' );
	await saveFields( page );
	await reloaded;
	await visitReactModalGroup( admin, ppomId );
}

test.describe( 'React modal — Conditions tab', () => {
	test( 'renders the locked upsell preview when conditions Pro is unavailable', async ( {
		page,
		admin,
		requestUtils,
	} ) => {
		await lockConditions( requestUtils );

		const { ppomId } = await createSimpleTextGroup( requestUtils, {
			fieldsNumber: 1,
		} );

		await visitReactModalGroup( admin, ppomId );
		const dialog = await openFirstFieldReactModal( page );
		await openConditionsTab( dialog );

		await expect(
			dialog.getByRole( 'link', { name: 'Upgrade to Pro' } )
		).toBeVisible();
		await expect(
			dialog.getByRole( 'button', {
				name: 'Enable conditional logic',
			} )
		).toHaveCount( 0 );
	} );

	test( 'shows the disabled empty state when Pro is enabled and logic is off', async ( {
		page,
		admin,
		requestUtils,
	} ) => {
		await enableProConditions( requestUtils );

		const { ppomId } = await createSimpleTextGroup( requestUtils, {
			fieldsNumber: 1,
		} );

		await visitReactModalGroup( admin, ppomId );
		const dialog = await openFirstFieldReactModal( page );
		await openConditionsTab( dialog );

		await expect(
			dialog.getByText( 'Conditional logic is disabled' )
		).toBeVisible();
		await expect(
			dialog.getByRole( 'button', { name: 'Enable conditional logic' } )
		).toBeVisible();
		await expect(
			dialog.getByRole( 'button', { name: 'Add condition' } )
		).toHaveCount( 0 );
	} );

	test( 'enabling logic from the empty state reveals the rule editor', async ( {
		page,
		admin,
		requestUtils,
	} ) => {
		await enableProConditions( requestUtils );

		const { ppomId } = await createSimpleTextGroup( requestUtils, {
			fieldsNumber: 2,
		} );

		await visitReactModalGroup( admin, ppomId );
		const dialog = await openFirstFieldReactModal( page );
		await openConditionsTab( dialog );

		await dialog
			.getByRole( 'button', { name: 'Enable conditional logic' } )
			.click();

		await expect(
			dialog.getByLabel( 'Visibility', { exact: true } )
		).toBeVisible();
		await expect(
			dialog.getByLabel( 'Match mode', { exact: true } )
		).toBeVisible();
		await expect( dialog.locator( '[data-rule-key]' ) ).toHaveCount( 1 );
		await expect(
			dialog.getByRole( 'button', { name: 'Add condition' } )
		).toBeVisible();
	} );

	test( 'add condition appends a rule row and remove drops it back', async ( {
		page,
		admin,
		requestUtils,
	} ) => {
		await enableProConditions( requestUtils );

		const { ppomId } = await createSimpleTextGroup( requestUtils, {
			fieldsNumber: 2,
		} );

		await visitReactModalGroup( admin, ppomId );
		const dialog = await openFirstFieldReactModal( page );
		await openConditionsTab( dialog );

		await dialog
			.getByRole( 'button', { name: 'Enable conditional logic' } )
			.click();

		const ruleRows = dialog.locator( '[data-rule-key]' );
		await expect( ruleRows ).toHaveCount( 1 );

		await dialog.getByRole( 'button', { name: 'Add condition' } ).click();
		await expect( ruleRows ).toHaveCount( 2 );

		await dialog.getByRole( 'button', { name: 'Add condition' } ).click();
		await expect( ruleRows ).toHaveCount( 3 );

		await dialog
			.getByRole( 'button', { name: 'Remove condition' } )
			.first()
			.click();
		await expect( ruleRows ).toHaveCount( 2 );
	} );

	test( 'last remaining rule cannot be removed via the per-row trash button', async ( {
		page,
		admin,
		requestUtils,
	} ) => {
		await enableProConditions( requestUtils );

		const { ppomId } = await createSimpleTextGroup( requestUtils, {
			fieldsNumber: 1,
		} );

		await visitReactModalGroup( admin, ppomId );
		const dialog = await openFirstFieldReactModal( page );
		await openConditionsTab( dialog );

		await dialog
			.getByRole( 'button', { name: 'Enable conditional logic' } )
			.click();

		await expect( dialog.locator( '[data-rule-key]' ) ).toHaveCount( 1 );

		const removeButton = dialog
			.getByRole( 'button', { name: 'Remove condition' } )
			.first();
		await expect( removeButton ).toBeHidden();
	} );

	test( 'targets dropdown lists the other fields in the group by data name', async ( {
		page,
		admin,
		requestUtils,
	} ) => {
		await enableProConditions( requestUtils );

		const { ppomId, controllingDataName } = await createTwoFieldGroup(
			requestUtils,
			{ titlePrefix: 'cond_target' }
		);

		await visitReactModalGroup( admin, ppomId );
		const dialog = await openFirstFieldReactModal( page );
		await openConditionsTab( dialog );

		await dialog
			.getByRole( 'button', { name: 'Enable conditional logic' } )
			.click();

		const fieldSelect = dialog
			.getByLabel( 'Field', { exact: true } )
			.first();
		const optionValues = await fieldSelect.evaluate( ( node ) =>
			Array.from( node.querySelectorAll( 'option' ) ).map(
				( option ) => option.value
			)
		);

		expect( optionValues ).toContain( controllingDataName );
	} );

	test( 'visibility, bound, and rule selections persist through Save Fields', async ( {
		page,
		admin,
		requestUtils,
	} ) => {
		await enableProConditions( requestUtils );

		const { ppomId, controllingDataName } = await createTwoFieldGroup(
			requestUtils,
			{ titlePrefix: 'cond_persist' }
		);

		await visitReactModalGroup( admin, ppomId );
		let dialog = await openFirstFieldReactModal( page );
		await openConditionsTab( dialog );

		await dialog
			.getByRole( 'button', { name: 'Enable conditional logic' } )
			.click();

		await dialog
			.getByLabel( 'Visibility', { exact: true } )
			.selectOption( 'Hide' );
		await dialog
			.getByLabel( 'Match mode', { exact: true } )
			.selectOption( 'Any' );
		await dialog
			.getByLabel( 'Field', { exact: true } )
			.first()
			.selectOption( controllingDataName );
		await dialog
			.getByLabel( 'Operator', { exact: true } )
			.first()
			.selectOption( 'not' );

		await dialog.getByRole( 'button', { name: 'Update Field' } ).click();
		await expect( dialog ).toBeHidden();

		await saveFieldsAndRevisit( page, admin, ppomId );

		dialog = await openFirstFieldReactModal( page );
		await openConditionsTab( dialog );

		await expect(
			dialog.getByLabel( 'Visibility', { exact: true } )
		).toHaveValue( 'Hide' );
		await expect(
			dialog.getByLabel( 'Match mode', { exact: true } )
		).toHaveValue( 'Any' );
		await expect(
			dialog.getByLabel( 'Field', { exact: true } ).first()
		).toHaveValue( controllingDataName );
		await expect(
			dialog.getByLabel( 'Operator', { exact: true } ).first()
		).toHaveValue( 'not' );
	} );

} );
