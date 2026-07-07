/**
 * External dependencies
 */
import path from 'path';

/**
 * WordPress dependencies
 */
import { test, expect } from '@wordpress/e2e-test-utils-playwright';

import {
	attachPpomGroupToProducts,
	buildFileField,
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
