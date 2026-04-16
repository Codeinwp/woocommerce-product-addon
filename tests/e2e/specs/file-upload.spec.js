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
