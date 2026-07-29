/**
 * External dependencies
 */
import path from 'path';

/**
 * WordPress dependencies
 */
import { test, expect } from '@wordpress/e2e-test-utils-playwright';

/**
 * External dependencies
 */
import { request as playwrightRequest } from '@playwright/test';

import {
	attachPpomGroupToProducts,
	buildFileField,
	buildTextField,
	createPpomGroup,
	createSimpleProduct,
} from '../fixtures/index.js';

test.describe( 'File Upload with Dynamic Nonce Refresh', () => {
	/**
	 * Test that file upload field renders correctly and nonce refresh functionality is available.
	 */
	test( 'should have nonce refresh functionality available', async ( {
		page,
		requestUtils,
	} ) => {
		const fieldId = 'file_upload_test';
		const product = await createSimpleProduct( requestUtils );
		const { ppomId } = await createPpomGroup( requestUtils, {
			groupName: 'File Upload Test',
			fields: [
				buildFileField( {
					title: 'Upload Your File',
					dataName: fieldId,
					file_size: '5',
					files_allowed: '1',
					file_types: 'txt,pdf,jpg,png',
				} ),
			],
		} );

		await attachPpomGroupToProducts( requestUtils, {
			ppomId,
			productIds: [ product.id ],
		} );

		// Navigate to product page
		await page.goto( `/?p=${ product.id }` );

		// Wait for PPOM fields to load
		await page.waitForSelector( `[data-data_name="${ fieldId }"]`, {
			timeout: 10000,
		} );

		// Verify the file upload field is visible
		const fileField = page.locator( `[data-data_name="${ fieldId }"]` );
		await expect( fileField ).toBeVisible();

		// Wait for file upload JavaScript to be fully loaded
		await page.waitForFunction(
			() => {
				return (
					typeof window.ppom_file_vars !== 'undefined' &&
					window.ppom_file_vars.rest_url &&
					window.ppom_file_vars.ppom_file_upload_nonce
				);
			},
			{ timeout: 10000 }
		);

		// Verify the REST endpoint URL is available
		const restUrl = await page.evaluate( () => {
			return window.ppom_file_vars?.rest_url;
		} );
		expect( restUrl ).toBeTruthy();
		expect( restUrl ).toContain( '/ppom/v1/nonces/file/' );

		// Verify initial nonce is present
		const initialNonce = await page.evaluate( () => {
			return window.ppom_file_vars?.ppom_file_upload_nonce;
		} );
		expect( initialNonce ).toBeTruthy();

		// Verify nonce refresh function exists
		const hasRefreshFunction = await page.evaluate( () => {
			return typeof window.ppom_refresh_file_nonces === 'function';
		} );
		expect( hasRefreshFunction ).toBe( true );
	} );

	/**
	 * HEIC uploads must succeed with the generic file-icon preview instead of
	 * erroring out. Regression test for Codeinwp/ppom-pro#546.
	 */
	test( 'user can upload a HEIC file and sees the file-icon preview', async ( {
		page,
		requestUtils,
	} ) => {
		const fieldId = 'file_heic_upload_test';
		const product = await createSimpleProduct( requestUtils );
		const { ppomId } = await createPpomGroup( requestUtils, {
			groupName: 'File HEIC Upload Test',
			fields: [
				buildFileField( {
					title: 'Upload Your HEIC File',
					dataName: fieldId,
					// plupload reads this raw: '5' would mean 5 *bytes*.
					file_size: '5mb',
					files_allowed: '1',
					file_types: 'heic,jpg,png',
				} ),
			],
		} );

		await attachPpomGroupToProducts( requestUtils, {
			ppomId,
			productIds: [ product.id ],
		} );

		await page.goto( `/?p=${ product.id }` );

		// plupload injects its file input inside the field container once ready.
		const fileInput = page.locator(
			`#ppom-file-container-${ fieldId } input[type=file]`
		);
		await fileInput.waitFor( { state: 'attached', timeout: 10000 } );

		// The bug surfaced as an alert() from the upload error path.
		const dialogs = [];
		page.on( 'dialog', ( dialog ) => {
			dialogs.push( dialog.message() );
			dialog.dismiss().catch( () => {} );
		} );

		await fileInput.setInputFiles(
			path.join( __dirname, '../../unit/fixtures/sample.heic' )
		);

		await expect(
			page.locator(
				`#filelist-${ fieldId } img[src*="images/file.png"]`
			)
		).toBeVisible( { timeout: 10000 } );

		expect( dialogs ).toEqual( [] );
	} );

	/**
	 * Regression: logged-in deletes failed with "Verification failed" when the
	 * refreshed nonce was minted for user 0. Page-load nonces are cached for
	 * 5 minutes, so the clock is advanced past the cache window to force the
	 * delete to go through a real REST refresh.
	 */
	test( 'logged-in user can delete an uploaded file after nonce refresh', async ( {
		page,
		requestUtils,
	} ) => {
		const fieldId = 'file_delete_test';
		const product = await createSimpleProduct( requestUtils );
		const { ppomId } = await createPpomGroup( requestUtils, {
			groupName: 'File Delete Test',
			fields: [
				buildFileField( {
					title: 'Upload Your File',
					dataName: fieldId,
					// plupload reads this raw: '5' would mean 5 *bytes*.
					file_size: '5mb',
					files_allowed: '1',
					file_types: 'png,jpg',
				} ),
			],
		} );

		await attachPpomGroupToProducts( requestUtils, {
			ppomId,
			productIds: [ product.id ],
		} );

		await page.clock.install();
		await page.goto( `/?p=${ product.id }` );

		const fileInput = page.locator(
			`#ppom-file-container-${ fieldId } input[type=file]`
		);
		await fileInput.waitFor( { state: 'attached', timeout: 10000 } );

		// Accept the "Are you sure?" confirmation.
		page.on( 'dialog', ( dialog ) => dialog.accept().catch( () => {} ) );

		await fileInput.setInputFiles( {
			name: 'pixel.png',
			mimeType: 'image/png',
			buffer: Buffer.from(
				'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==',
				'base64'
			),
		} );

		const deleteButton = page.locator(
			`#filelist-${ fieldId } .u_i_c_tools_del`
		);
		await deleteButton.waitFor( { state: 'visible', timeout: 10000 } );

		// Expire the 5-minute nonce cache so the delete must refresh first.
		await page.clock.fastForward( '06:00' );

		const [ refreshResponse, deleteResponse ] = await Promise.all( [
			page.waitForResponse( ( response ) =>
				response.url().includes( '/ppom/v1/nonces/file' )
			),
			page.waitForResponse(
				( response ) =>
					response.url().includes( 'admin-ajax.php' ) &&
					!! response
						.request()
						.postData()
						?.includes( 'ppom_delete_file' )
			),
			deleteButton.click(),
		] );

		// The refresh must authenticate, or it mints user-0 nonces.
		expect(
			refreshResponse.request().headers()[ 'x-wp-nonce' ]
		).toBeTruthy();
		expect( refreshResponse.ok() ).toBe( true );
		expect( await deleteResponse.text() ).toContain( 'File removed' );
	} );

	/**
	 * Regression for the reported missing-authorization issue: a visitor who
	 * knows another shopper's in-progress file name must not be able to delete
	 * it, even holding a perfectly valid delete nonce.
	 *
	 * This drives the real ajax endpoint. The unit tests exercise the ownership
	 * helpers directly and would stay green if the guard inside delete_file()
	 * were removed, so the authorization branch itself is only covered here.
	 */
	test( 'a different visitor cannot delete an uploaded file', async ( {
		page,
		requestUtils,
	}, testInfo ) => {
		const fieldId = 'file_owner_test';
		const product = await createSimpleProduct( requestUtils );
		const { ppomId } = await createPpomGroup( requestUtils, {
			groupName: 'File Ownership Test',
			fields: [
				buildFileField( {
					title: 'Upload Your File',
					dataName: fieldId,
					file_size: '5mb',
					files_allowed: '1',
					file_types: 'png,jpg',
				} ),
			],
		} );

		await attachPpomGroupToProducts( requestUtils, {
			ppomId,
			productIds: [ product.id ],
		} );

		await page.goto( `/?p=${ product.id }` );

		const fileInput = page.locator(
			`#ppom-file-container-${ fieldId } input[type=file]`
		);
		await fileInput.waitFor( { state: 'attached', timeout: 10000 } );

		page.on( 'dialog', ( dialog ) => dialog.accept().catch( () => {} ) );

		await fileInput.setInputFiles( {
			name: 'pixel.png',
			mimeType: 'image/png',
			buffer: Buffer.from(
				'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==',
				'base64'
			),
		} );

		// The stored name lands in the hidden `[org]` input — the same value the
		// plugin's own delete button sends, so read it from there.
		const storedName = page.locator(
			`input[name^="ppom[fields][${ fieldId }]"][name$="[org]"]`
		);
		await storedName.waitFor( { state: 'attached', timeout: 15000 } );

		const uploadedName = await storedName.inputValue();
		expect( uploadedName ).toBeTruthy();

		const baseURL =
			typeof testInfo.project.use.baseURL === 'string'
				? testInfo.project.use.baseURL
				: process.env.WP_BASE_URL;

		if ( ! baseURL ) {
			throw new Error( 'Playwright baseURL is required for this check.' );
		}

		// A separate visitor: no cookies, so no relation to the upload above.
		const attacker = await playwrightRequest.newContext( {
			baseURL,
			storageState: { cookies: [], origins: [] },
		} );

		try {
			const nonceResponse = await attacker.get(
				'?rest_route=/ppom/v1/nonces/file/'
			);
			const { ppom_file_delete_nonce: attackerNonce } =
				await nonceResponse.json();
			expect( attackerNonce ).toBeTruthy();

			const attempt = await attacker.post(
				'wp-admin/admin-ajax.php',
				{
					form: {
						action: 'ppom_delete_file',
						file_name: uploadedName,
						ppom_nonce: attackerNonce,
					},
					failOnStatusCode: false,
				}
			);

			expect( await attempt.text() ).toContain( 'Verification failed' );
		} finally {
			await attacker.dispose();
		}

		// The owner can still remove it, which also proves it survived above.
		const deleteButton = page.locator(
			`#filelist-${ fieldId } .u_i_c_tools_del`
		);
		await deleteButton.waitFor( { state: 'visible', timeout: 10000 } );

		const [ deleteResponse ] = await Promise.all( [
			page.waitForResponse(
				( response ) =>
					response.url().includes( 'admin-ajax.php' ) &&
					!! response
						.request()
						.postData()
						?.includes( 'ppom_delete_file' )
			),
			deleteButton.click(),
		] );

		expect( await deleteResponse.text() ).toContain( 'File removed' );
	} );

	/**
	 * The upload nonce is public, so anything can post to the endpoint. An upload
	 * naming a field that does not exist on the product used to be finalized,
	 * recorded as owned and answered with a preview, leaving a stored file behind
	 * that no form would ever reference.
	 */
	test( 'an upload naming an unknown field is rejected', async ( {
		requestUtils,
	}, testInfo ) => {
		// No PPOM group attached, so no data_name can resolve.
		const product = await createSimpleProduct( requestUtils );

		const baseURL =
			typeof testInfo.project.use.baseURL === 'string'
				? testInfo.project.use.baseURL
				: process.env.WP_BASE_URL;

		if ( ! baseURL ) {
			throw new Error( 'Playwright baseURL is required for this check.' );
		}

		const anonymous = await playwrightRequest.newContext( {
			baseURL,
			storageState: { cookies: [], origins: [] },
		} );

		try {
			const nonceResponse = await anonymous.get(
				'?rest_route=/ppom/v1/nonces/file/'
			);
			const { ppom_file_upload_nonce: uploadNonce } =
				await nonceResponse.json();
			expect( uploadNonce ).toBeTruthy();

			const response = await anonymous.post( 'wp-admin/admin-ajax.php', {
				multipart: {
					action: 'ppom_upload_file',
					ppom_nonce: uploadNonce,
					name: 'reject-me.txt',
					product_id: String( product.id ),
					data_name: 'field_that_does_not_exist',
					chunk: '0',
					chunks: '1',
					file: {
						name: 'reject-me.txt',
						mimeType: 'text/plain',
						buffer: Buffer.from( 'payload' ),
					},
				},
				failOnStatusCode: false,
			} );

			const body = await response.text();

			expect( body ).not.toContain( 'file_name' );
			expect( body ).toContain( 'error' );
		} finally {
			await anonymous.dispose();
		}
	} );

	/**
	 * Only file and cropper fields are given an uploader -- the loop over
	 * `ppom_input_vars.ppom_inputs` calls `ppom_setup_file_upload_input()` for
	 * those two types only -- so a
	 * text field's data_name is not something the form could have posted here.
	 * Checking only that the field exists let such a request through and stored a
	 * file no form can reference.
	 */
	test( 'an upload naming a field that cannot upload is rejected', async ( {
		requestUtils,
	}, testInfo ) => {
		const product = await createSimpleProduct( requestUtils );
		const { ppomId } = await createPpomGroup( requestUtils, {
			groupName: 'Non Upload Field Test',
			fields: [
				buildTextField( {
					title: 'Engraving',
					dataName: 'engraving_text',
				} ),
			],
		} );

		await attachPpomGroupToProducts( requestUtils, {
			ppomId,
			productIds: [ product.id ],
		} );

		const baseURL =
			typeof testInfo.project.use.baseURL === 'string'
				? testInfo.project.use.baseURL
				: process.env.WP_BASE_URL;

		if ( ! baseURL ) {
			throw new Error( 'Playwright baseURL is required for this check.' );
		}

		const anonymous = await playwrightRequest.newContext( {
			baseURL,
			storageState: { cookies: [], origins: [] },
		} );

		try {
			const nonceResponse = await anonymous.get(
				'?rest_route=/ppom/v1/nonces/file/'
			);
			const { ppom_file_upload_nonce: uploadNonce } =
				await nonceResponse.json();

			const response = await anonymous.post( 'wp-admin/admin-ajax.php', {
				multipart: {
					action: 'ppom_upload_file',
					ppom_nonce: uploadNonce,
					name: 'not-for-a-text-field.txt',
					product_id: String( product.id ),
					data_name: 'engraving_text',
					chunk: '0',
					chunks: '1',
					file: {
						name: 'not-for-a-text-field.txt',
						mimeType: 'text/plain',
						buffer: Buffer.from( 'payload' ),
					},
				},
				failOnStatusCode: false,
			} );

			const body = await response.text();

			expect( body ).not.toContain( 'file_name' );
			expect( body ).toContain( 'error' );
		} finally {
			await anonymous.dispose();
		}
	} );

	/**
	 * The concurrency specs post tokens themselves, and the browser delete above
	 * would still succeed through session ownership, so neither notices if the
	 * client stops forwarding the token. That forwarding is what carries the fix
	 * to real shoppers, so assert the request actually contains it.
	 */
	test( 'the browser forwards the upload token on delete', async ( {
		page,
		requestUtils,
	} ) => {
		const fieldId = 'file_token_forward_test';
		const product = await createSimpleProduct( requestUtils );
		const { ppomId } = await createPpomGroup( requestUtils, {
			groupName: 'Token Forwarding Test',
			fields: [
				buildFileField( {
					title: 'Upload Your File',
					dataName: fieldId,
					file_size: '5mb',
					files_allowed: '1',
					file_types: 'png,jpg',
				} ),
			],
		} );

		await attachPpomGroupToProducts( requestUtils, {
			ppomId,
			productIds: [ product.id ],
		} );

		await page.goto( `/?p=${ product.id }` );

		const fileInput = page.locator(
			`#ppom-file-container-${ fieldId } input[type=file]`
		);
		await fileInput.waitFor( { state: 'attached', timeout: 10000 } );

		page.on( 'dialog', ( dialog ) => dialog.accept().catch( () => {} ) );

		await fileInput.setInputFiles( {
			name: 'pixel.png',
			mimeType: 'image/png',
			buffer: Buffer.from(
				'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==',
				'base64'
			),
		} );

		// The token the server issued for this upload, read from the markup the
		// upload produced rather than from the response, so this also covers the
		// value surviving into the form.
		const storedInput = page.locator(
			`input[name^="ppom[fields][${ fieldId }]"][name$="[org]"]`
		);
		await storedInput.waitFor( { state: 'attached', timeout: 15000 } );

		const issuedToken = await storedInput.getAttribute(
			'data-delete-token'
		);
		expect( issuedToken ).toBeTruthy();

		const deleteButton = page.locator(
			`#filelist-${ fieldId } .u_i_c_tools_del`
		);
		await deleteButton.waitFor( { state: 'visible', timeout: 10000 } );

		const [ deleteResponse ] = await Promise.all( [
			page.waitForResponse(
				( response ) =>
					response.url().includes( 'admin-ajax.php' ) &&
					!! response
						.request()
						.postData()
						?.includes( 'ppom_delete_file' )
			),
			deleteButton.click(),
		] );

		const sentBody = deleteResponse.request().postData() || '';

		expect( sentBody ).toContain( 'ppom_delete_token' );
		expect( sentBody ).toContain( issuedToken );
		expect( await deleteResponse.text() ).toContain( 'File removed' );
	} );

	/**
	 * Covers the storage fallback only: when the input carries no token attribute,
	 * the delete still presents the copy this browser kept.
	 *
	 * This does not stand in for a form restored by the server. That markup is
	 * wrapped in `.u_i_c_box` rather than `.ppom-file-wrapper`, and the delete
	 * handler reads the file id only from the latter, so the click never reaches
	 * the endpoint at all — see the parked test below.
	 */
	test( 'the delete falls back to the token kept by the browser', async ( {
		page,
		requestUtils,
	} ) => {
		const fieldId = 'file_token_persist_test';
		const product = await createSimpleProduct( requestUtils );
		const { ppomId } = await createPpomGroup( requestUtils, {
			groupName: 'Token Persistence Test',
			fields: [
				buildFileField( {
					title: 'Upload Your File',
					dataName: fieldId,
					file_size: '5mb',
					files_allowed: '1',
					file_types: 'png,jpg',
				} ),
			],
		} );

		await attachPpomGroupToProducts( requestUtils, {
			ppomId,
			productIds: [ product.id ],
		} );

		await page.goto( `/?p=${ product.id }` );

		const fileInput = page.locator(
			`#ppom-file-container-${ fieldId } input[type=file]`
		);
		await fileInput.waitFor( { state: 'attached', timeout: 10000 } );

		page.on( 'dialog', ( dialog ) => dialog.accept().catch( () => {} ) );

		await fileInput.setInputFiles( {
			name: 'pixel.png',
			mimeType: 'image/png',
			buffer: Buffer.from(
				'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==',
				'base64'
			),
		} );

		const storedInput = page.locator(
			`input[name^="ppom[fields][${ fieldId }]"][name$="[org]"]`
		);
		await storedInput.waitFor( { state: 'attached', timeout: 15000 } );

		const fileName = await storedInput.inputValue();
		const issuedToken = await storedInput.getAttribute(
			'data-delete-token'
		);
		expect( issuedToken ).toBeTruthy();

		// Stand in for a form rendered again from the cart: the value is restored,
		// the token attribute is not.
		await storedInput.evaluate( ( node ) =>
			node.removeAttribute( 'data-delete-token' )
		);

		const deleteButton = page.locator(
			`#filelist-${ fieldId } .u_i_c_tools_del`
		);
		await deleteButton.waitFor( { state: 'visible', timeout: 10000 } );

		const [ deleteResponse ] = await Promise.all( [
			page.waitForResponse(
				( response ) =>
					response.url().includes( 'admin-ajax.php' ) &&
					!! response
						.request()
						.postData()
						?.includes( 'ppom_delete_file' )
			),
			deleteButton.click(),
		] );

		const sentBody = deleteResponse.request().postData() || '';

		expect(
			sentBody,
			`the token for ${ fileName } must survive the markup losing it`
		).toContain( issuedToken );
		expect( await deleteResponse.text() ).toContain( 'File removed' );
	} );

	/**
	 * Sets up a product with one file field and uploads a pixel to it.
	 *
	 * @param {Object} page         Playwright page.
	 * @param {Object} requestUtils WP request utils.
	 * @param {string} fieldId      Field data name.
	 * @param {string} groupName    PPOM group name.
	 * @return {Promise<Object>} Locators and the stored file name.
	 */
	async function uploadOneFile( page, requestUtils, fieldId, groupName ) {
		const product = await createSimpleProduct( requestUtils );
		const { ppomId } = await createPpomGroup( requestUtils, {
			groupName,
			fields: [
				buildFileField( {
					title: 'Upload Your File',
					dataName: fieldId,
					file_size: '5mb',
					files_allowed: '1',
					file_types: 'png,jpg',
				} ),
			],
		} );

		await attachPpomGroupToProducts( requestUtils, {
			ppomId,
			productIds: [ product.id ],
		} );

		await page.goto( `/?p=${ product.id }` );

		const fileInput = page.locator(
			`#ppom-file-container-${ fieldId } input[type=file]`
		);
		await fileInput.waitFor( { state: 'attached', timeout: 10000 } );

		page.on( 'dialog', ( dialog ) => dialog.accept().catch( () => {} ) );

		await fileInput.setInputFiles( {
			name: 'pixel.png',
			mimeType: 'image/png',
			buffer: Buffer.from(
				'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==',
				'base64'
			),
		} );

		const storedInput = page.locator(
			`input[name^="ppom[fields][${ fieldId }]"][name$="[org]"]`
		);
		await storedInput.waitFor( { state: 'attached', timeout: 15000 } );

		return {
			fileName: await storedInput.inputValue(),
			productId: product.id,
			// WordPress canonical-redirects `/?p=<id>` on GET, so this is now the
			// pretty permalink -- which a POST needs, as `/?p=<id>` answers 404.
			permalink: page.url(),
		};
	}

	/**
	 * `delete_file()` ends in `die( 0 )`, so a refusal is still HTTP 200. Clearing
	 * the kept token on such a response throws away what may be the shopper's only
	 * proof, turning a retryable refusal into a file that can never be removed.
	 */
	test( 'a refused delete keeps the token for a retry', async ( {
		page,
		requestUtils,
	} ) => {
		const fieldId = 'file_refused_delete_test';

		const { fileName } = await uploadOneFile(
			page,
			requestUtils,
			fieldId,
			'Refused Delete Test'
		);

		const tokenBefore = await page.evaluate(
			( name ) =>
				window.sessionStorage.getItem( 'ppom_delete_token_' + name ),
			fileName
		);
		expect( tokenBefore ).toBeTruthy();

		// Answer the delete the way the endpoint answers a refusal: 200, no removal.
		await page.route( '**/admin-ajax.php', async ( route ) => {
			const body = route.request().postData() || '';

			if ( body.includes( 'ppom_delete_file' ) ) {
				await route.fulfill( {
					status: 200,
					body: `Verification failed for file: ${ fileName }`,
				} );
				return;
			}

			await route.continue();
		} );

		const deleteButton = page.locator(
			`#filelist-${ fieldId } .u_i_c_tools_del`
		);
		await deleteButton.waitFor( { state: 'visible', timeout: 10000 } );
		await deleteButton.click();

		await page.waitForTimeout( 1000 );

		const tokenAfter = await page.evaluate(
			( name ) =>
				window.sessionStorage.getItem( 'ppom_delete_token_' + name ),
			fileName
		);

		expect(
			tokenAfter,
			'a refusal must not discard the proof needed to retry'
		).toBe( tokenBefore );
	} );

	/**
	 * Server-restored files are wrapped in `.u_i_c_box`
	 * (`FileRenderer` and `templates/frontend/inputs/file.php`) while the
	 * uploader wraps fresh ones
	 * in `.ppom-file-wrapper` (`add_thumb_box()`). The delete handler used to
	 * read the file id only from the latter and return without it, so the click on
	 * a restored file sent nothing at all.
	 *
	 * `templates/render-fields.php` repopulates the form from
	 * `$_POST['ppom']['fields']`, which is what happens when add-to-cart fails
	 * validation. Posting the product page with a stored file name reaches the same
	 * code, so the markup here is exactly what a shopper sees after such a reload.
	 */
	test( 'a genuinely restored file can be deleted', async ( {
		page,
		requestUtils,
	} ) => {
		const fieldId = 'file_real_restore_test';

		const { fileName, permalink } = await uploadOneFile(
			page,
			requestUtils,
			fieldId,
			'Real Restore Test'
		);

		// Canonical form, whatever the permalink setting: `/?p=<id>` answers 404
		// to a POST, the canonical URL does not.
		expect( permalink ).toBeTruthy();
		expect( permalink ).not.toContain( '?p=' );

		// Re-render the product page through POST, the way a failed add-to-cart
		// does, with this upload as the field's existing value.
		await page.evaluate(
			( { action, field, name } ) => {
				const form = document.createElement( 'form' );
				form.method = 'POST';
				form.action = action;

				const input = document.createElement( 'input' );
				input.type = 'hidden';
				input.name = `ppom[fields][${ field }][0][org]`;
				input.value = name;

				form.appendChild( input );
				document.body.appendChild( form );
				form.submit();
			},
			{ action: permalink, field: fieldId, name: fileName }
		);

		await page.waitForLoadState( 'load' );

		// Confirm this really is server-rendered markup before judging the click.
		const restoredBox = page.locator(
			`#filelist-${ fieldId } .u_i_c_box`
		);
		await restoredBox.waitFor( { state: 'attached', timeout: 10000 } );

		expect(
			await page.locator( '.ppom-file-wrapper' ).count(),
			'server-restored markup should not carry the uploader wrapper'
		).toBe( 0 );

		const deleteButton = page
			.locator( `#filelist-${ fieldId } .u_i_c_tools_del` )
			.first();
		await deleteButton.waitFor( { state: 'visible', timeout: 10000 } );

		page.on( 'dialog', ( dialog ) => dialog.accept().catch( () => {} ) );

		let requested = false;
		const seen = page
			.waitForRequest(
				( request ) =>
					request.url().includes( 'admin-ajax.php' ) &&
					!! request.postData()?.includes( 'ppom_delete_file' ),
				{ timeout: 5000 }
			)
			.then( () => {
				requested = true;
			} )
			.catch( () => {} );

		await deleteButton.click();
		await seen;

		expect(
			requested,
			'delete on a restored file must reach the endpoint'
		).toBe( true );
	} );

	/**
	 * Restored file ids come from each field's own array key, so the first file of
	 * every field is `u_i_c_0`. Looking the wrapper up by that id reaches whichever
	 * one comes first in the document, so deleting the second field's file used to
	 * strip the first field's value out of the form while leaving its file on the
	 * server.
	 */
	test( 'deleting one restored file leaves the other field alone', async ( {
		page,
		requestUtils,
	} ) => {
		const frontField = 'dup_front_field';
		const backField = 'dup_back_field';

		const product = await createSimpleProduct( requestUtils );
		const { ppomId } = await createPpomGroup( requestUtils, {
			groupName: 'Two File Fields Test',
			fields: [
				buildFileField( {
					title: 'Front',
					dataName: frontField,
					file_size: '5mb',
					files_allowed: '1',
					file_types: 'png,jpg',
				} ),
				buildFileField( {
					title: 'Back',
					dataName: backField,
					file_size: '5mb',
					files_allowed: '1',
					file_types: 'png,jpg',
				} ),
			],
		} );

		await attachPpomGroupToProducts( requestUtils, {
			ppomId,
			productIds: [ product.id ],
		} );

		await page.goto( `/?p=${ product.id }` );
		const permalink = page.url();

		page.on( 'dialog', ( dialog ) => dialog.accept().catch( () => {} ) );

		const pixel = {
			name: 'pixel.png',
			mimeType: 'image/png',
			buffer: Buffer.from(
				'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==',
				'base64'
			),
		};

		const uploaded = {};

		for ( const field of [ frontField, backField ] ) {
			const input = page.locator(
				`#ppom-file-container-${ field } input[type=file]`
			);
			await input.waitFor( { state: 'attached', timeout: 10000 } );
			await input.setInputFiles( pixel );

			const stored = page.locator(
				`input[name^="ppom[fields][${ field }]"][name$="[org]"]`
			);
			await stored.waitFor( { state: 'attached', timeout: 15000 } );
			uploaded[ field ] = await stored.inputValue();
		}

		// Restore both, the way a failed add-to-cart does.
		await page.evaluate(
			( { action, values } ) => {
				const form = document.createElement( 'form' );
				form.method = 'POST';
				form.action = action;

				Object.entries( values ).forEach( ( [ field, name ] ) => {
					const input = document.createElement( 'input' );
					input.type = 'hidden';
					input.name = `ppom[fields][${ field }][0][org]`;
					input.value = name;
					form.appendChild( input );
				} );

				document.body.appendChild( form );
				form.submit();
			},
			{ action: permalink, values: uploaded }
		);

		await page.waitForLoadState( 'load' );

		const frontBox = page.locator( `#filelist-${ frontField } .u_i_c_box` );
		const backBox = page.locator( `#filelist-${ backField } .u_i_c_box` );
		await frontBox.waitFor( { state: 'attached', timeout: 10000 } );
		await backBox.waitFor( { state: 'attached', timeout: 10000 } );

		// Remove the second field's file.
		const backDelete = page
			.locator( `#filelist-${ backField } .u_i_c_tools_del` )
			.first();
		await backDelete.waitFor( { state: 'visible', timeout: 10000 } );

		await Promise.all( [
			page.waitForResponse(
				( response ) =>
					response.url().includes( 'admin-ajax.php' ) &&
					!! response
						.request()
						.postData()
						?.includes( 'ppom_delete_file' )
			),
			backDelete.click(),
		] );

		await expect( backBox ).toHaveCount( 0 );

		await expect(
			frontBox,
			'the other field must keep its restored file'
		).toHaveCount( 1 );

		await expect(
			page.locator(
				`input[name^="ppom[fields][${ frontField }]"][name$="[org]"]`
			),
			'and keep it in the form submission'
		).toHaveCount( 1 );

		// The delete also swaps the thumbnail for a spinner while it runs, which is
		// another id lookup that can land on the wrong field.
		await expect(
			frontBox.locator( 'img' ).first(),
			'and keep its preview rather than a stuck spinner'
		).not.toHaveAttribute( 'src', /loading\.gif/ );
	} );

	test( 'should refresh nonce via REST endpoint', async ( {
		page,
		requestUtils,
	} ) => {
		const fieldId = 'file_nonce_refresh_test';
		const product = await createSimpleProduct( requestUtils );
		const { ppomId } = await createPpomGroup( requestUtils, {
			groupName: 'File Nonce Refresh Test',
			fields: [
				buildFileField( {
					title: 'Upload File for Nonce Test',
					dataName: fieldId,
					file_size: '5',
					files_allowed: '1',
					file_types: 'txt',
				} ),
			],
		} );

		await attachPpomGroupToProducts( requestUtils, {
			ppomId,
			productIds: [ product.id ],
		} );

		await page.goto( `/?p=${ product.id }` );

		await page.waitForSelector( `[data-data_name="${ fieldId }"]`, {
			timeout: 10000,
		} );

		// Wait for file upload JavaScript to be fully loaded
		await page.waitForFunction(
			() => {
				return (
					typeof window.ppom_file_vars !== 'undefined' &&
					typeof window.ppom_refresh_file_nonces === 'function'
				);
			},
			{ timeout: 10000 }
		);

		// Get the initial nonce
		const initialNonce = await page.evaluate( () => {
			return window.ppom_file_vars?.ppom_file_upload_nonce;
		} );
		expect( initialNonce ).toBeTruthy();

		// Manually call the nonce refresh function
		const refreshResult = await page.evaluate( async () => {
			if ( typeof window.ppom_refresh_file_nonces === 'function' ) {
				try {
					const result =
						await window.ppom_refresh_file_nonces();
					return {
						success: true,
						newNonce:
							window.ppom_file_vars?.ppom_file_upload_nonce,
						hasUploadNonce: result?.ppom_file_upload_nonce
							? true
							: false,
						hasDeleteNonce: result?.ppom_file_delete_nonce
							? true
							: false,
					};
				} catch ( error ) {
					return { success: false, error: error.message };
				}
			}
			return { success: false, error: 'Function not found' };
		} );

		// Verify the refresh worked
		expect( refreshResult.success ).toBe( true );
		expect( refreshResult.hasUploadNonce ).toBe( true );
		expect( refreshResult.hasDeleteNonce ).toBe( true );
		expect( refreshResult.newNonce ).toBeTruthy();
	} );

	test( 'should handle nonce refresh endpoint correctly', async ( {
		page,
		requestUtils,
	} ) => {
		const fieldId = 'file_endpoint_test';
		const product = await createSimpleProduct( requestUtils );
		const { ppomId } = await createPpomGroup( requestUtils, {
			groupName: 'File Endpoint Test',
			fields: [
				buildFileField( {
					title: 'Upload File',
					dataName: fieldId,
				} ),
			],
		} );

		await attachPpomGroupToProducts( requestUtils, {
			ppomId,
			productIds: [ product.id ],
		} );

		await page.goto( `/?p=${ product.id }` );

		await page.waitForSelector( `[data-data_name="${ fieldId }"]`, {
			timeout: 10000,
		} );

		// Wait for file upload JavaScript to be fully loaded
		await page.waitForFunction(
			() => {
				return (
					typeof window.ppom_file_vars !== 'undefined' &&
					window.ppom_file_vars.rest_url
				);
			},
			{ timeout: 10000 }
		);

		// Test the REST endpoint directly
		const nonceResponse = await page.evaluate( async () => {
			const restUrl = window.ppom_file_vars?.rest_url;
			if ( ! restUrl ) {
				return { error: 'No REST URL found' };
			}

			try {
				const response = await fetch( restUrl, {
					method: 'GET',
					credentials: 'same-origin',
					headers: {
						'Content-Type': 'application/json',
					},
				} );
				const data = await response.json();
				return {
					ok: response.ok,
					status: response.status,
					data,
				};
			} catch ( error ) {
				return { error: error.message };
			}
		} );

		// Verify the endpoint response
		expect( nonceResponse.ok ).toBe( true );
		expect( nonceResponse.status ).toBe( 200 );
		expect( nonceResponse.data.status ).toBe( 'success' );
		expect( nonceResponse.data.ppom_file_upload_nonce ).toBeTruthy();
		expect( nonceResponse.data.ppom_file_delete_nonce ).toBeTruthy();
	} );
} );
