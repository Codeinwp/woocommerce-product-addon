/**
 * WordPress dependencies
 */
import { test, expect } from '@wordpress/e2e-test-utils-playwright';

import {
	attachPpomGroupToProducts,
	buildFileField,
	buildSelectField,
	buildTextField,
	createPpomGroup,
	createPpomShortcodePage,
	createSimpleProduct,
} from '../fixtures/index.js';

/**
 * Regression coverage for issue #735: a page rendering more than one PPOM
 * form (via the `[ppom product_id="X"]` shortcode) must keep each form's
 * condition evaluation, `#conditionally_hidden` value, and file-upload state
 * fully independent, even when both forms share the same field group and
 * therefore the same field data_names and DOM ids.
 */

function uniqueToken() {
	return `${ Date.now() }_${ Math.floor( Math.random() * 1e6 ) }`;
}

/**
 * Locate a specific `.ppom-wrapper` by the product id its hidden
 * `ppom_product_id` input carries. Both wrappers on the page reuse the same
 * field data_names and element ids, so every other locator in this file is
 * scoped through the wrapper returned here rather than searched page-wide.
 */
function formFor( page, productId ) {
	return page.locator( '.ppom-wrapper', {
		has: page.locator( `[name="ppom_product_id"][value="${ productId }"]` ),
	} );
}

test.describe( 'Multiple PPOM forms on one page', () => {
	test( 'condition visibility, #conditionally_hidden, and file upload/delete stay independent per form', async ( {
		page,
		requestUtils,
	} ) => {
		const token = uniqueToken();
		const triggerId = `trigger_${ token }`;
		const targetId = `target_${ token }`;
		const uploadId = `upload_${ token }`;
		const revealOption = { label: 'Reveal', value: 'reveal' };
		const otherOption = { label: 'Hide', value: 'hide' };

		const productA = await createSimpleProduct( requestUtils );
		const productB = await createSimpleProduct( requestUtils );

		const { ppomId } = await createPpomGroup( requestUtils, {
			groupName: `Multi-form conditions ${ token }`,
			fields: [
				buildSelectField( {
					title: `Trigger ${ token }`,
					dataName: triggerId,
					options: [ otherOption, revealOption ],
				} ),
				buildTextField( {
					title: `Target ${ token }`,
					dataName: targetId,
					required: 'on',
					logic: 'on',
					conditions: {
						visibility: 'Show',
						bound: 'All',
						rules: [
							{
								elements: triggerId,
								operators: 'is',
								element_values: revealOption.label,
							},
						],
					},
				} ),
				buildFileField( {
					title: `Upload ${ token }`,
					dataName: uploadId,
					file_size: '5mb',
					files_allowed: '1',
					file_types: 'png,jpg',
				} ),
			],
		} );

		await attachPpomGroupToProducts( requestUtils, {
			ppomId,
			productIds: [ productA.id, productB.id ],
		} );

		const { permalink } = await createPpomShortcodePage( requestUtils, {
			productIds: [ productA.id, productB.id ],
			title: `Multi-form conditions ${ token }`,
		} );

		await page.goto( permalink );
		page.on( 'dialog', ( dialog ) => dialog.accept().catch( () => {} ) );

		const formA = formFor( page, productA.id );
		const formB = formFor( page, productB.id );

		await expect( formA ).toBeVisible();
		await expect( formB ).toBeVisible();

		// Not `getByLabel()`: PPOM renders `id="<data_name>"` on the field and
		// a matching `<label for="...">` with no per-form suffix, so with two
		// forms the browser's native id/label association always resolves to
		// the *first* form's control — a scoped locator can't override that
		// (it's a same-document getElementById lookup, not a DOM-subtree
		// query). The field wrapper's `data-data_name` attribute is a normal
		// attribute selector, so it resolves correctly within each scope.
		const targetA = formA.locator(
			`.ppom-field-wrapper[data-data_name="${ targetId }"]`
		);
		const targetB = formB.locator(
			`.ppom-field-wrapper[data-data_name="${ targetId }"]`
		);
		const hiddenA = formA.locator( '#conditionally_hidden' );
		const hiddenB = formB.locator( '#conditionally_hidden' );

		// Both forms start with the default (non-revealing) option: the
		// required target is hidden and reported in each form's own
		// #conditionally_hidden — not just the first form's.
		await expect( targetA ).toBeHidden();
		await expect( targetB ).toBeHidden();
		await expect( hiddenA ).toHaveValue( targetId );
		await expect( hiddenB ).toHaveValue( targetId );

		// Revealing the target in form A must not touch form B.
		await formA
			.locator( `select[name="ppom[fields][${ triggerId }]"]` )
			.selectOption( { label: revealOption.label } );

		await expect( targetA ).toBeVisible();
		await expect( hiddenA ).toHaveValue( '' );
		await expect( targetB ).toBeHidden();
		await expect( hiddenB ).toHaveValue( targetId );

		// Revealing form B afterwards must not hide form A again.
		await formB
			.locator( `select[name="ppom[fields][${ triggerId }]"]` )
			.selectOption( { label: revealOption.label } );

		await expect( targetB ).toBeVisible();
		await expect( hiddenB ).toHaveValue( '' );
		await expect( targetA ).toBeVisible();
		await expect( hiddenA ).toHaveValue( '' );

		// File upload: each form's "Select files" control must bind its own
		// uploader instead of only the first form's button working. Both
		// forms render the same `#ppom-file-container-<uploadId>` id, but
		// scoping the locator through formA/formB (each rooted at one
		// specific `.ppom-wrapper`) resolves it within that form only.
		const fileInputA = formA.locator(
			`#ppom-file-container-${ uploadId } input[type=file]`
		);
		const fileInputB = formB.locator(
			`#ppom-file-container-${ uploadId } input[type=file]`
		);

		await fileInputA.setInputFiles( {
			name: 'form-a.png',
			mimeType: 'image/png',
			buffer: Buffer.from(
				'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==',
				'base64'
			),
		} );

		const previewA = formA.locator(
			`#filelist-${ uploadId } .u_i_c_tools_del`
		);
		const previewB = formB.locator(
			`#filelist-${ uploadId } .u_i_c_tools_del`
		);

		await expect( previewA ).toHaveCount( 1, { timeout: 10000 } );
		await expect( previewB ).toHaveCount( 0 );

		await fileInputB.setInputFiles( {
			name: 'form-b.png',
			mimeType: 'image/png',
			buffer: Buffer.from(
				'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==',
				'base64'
			),
		} );

		await expect( previewB ).toHaveCount( 1, { timeout: 10000 } );
		// Form A's own preview from the earlier upload must still be there,
		// untouched by form B's upload.
		await expect( previewA ).toHaveCount( 1 );

		// Deleting form A's file must not remove form B's.
		await previewA.click();
		await expect( previewA ).toHaveCount( 0 );
		await expect( previewB ).toHaveCount( 1 );
	} );
} );
