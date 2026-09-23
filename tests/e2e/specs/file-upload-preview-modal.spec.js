/**
 * WordPress dependencies
 */
import { test, expect } from '@wordpress/e2e-test-utils-playwright';

/**
 * Internal dependencies
 */
import {
	attachPpomGroupToProducts,
	buildFileField,
	createPpomGroup,
	createSimpleProduct,
	setPpomSettings,
} from '../fixtures/index.js';

const PIXEL_PNG = Buffer.from(
	'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==',
	'base64'
);

test.describe( 'File upload preview modal', () => {
	/**
	 * The "view large" modal was emitted with every image upload and relied on
	 * the Bootstrap CSS to stay hidden. With Bootstrap disabled it showed up as a
	 * stray full-size image at the bottom of the page.
	 * Regression test for Codeinwp/ppom-pro#696.
	 */
	test( 'no stray full-size image appears after upload when Bootstrap is disabled', async ( {
		page,
		requestUtils,
	} ) => {
		await setPpomSettings( requestUtils, { ppom_disable_bootstrap: 'yes' } );

		const fieldId = 'file_preview_modal_test';
		const product = await createSimpleProduct( requestUtils );
		const { ppomId } = await createPpomGroup( requestUtils, {
			groupName: 'File Preview Modal Test',
			fields: [
				buildFileField( {
					title: 'Upload Your Image',
					dataName: fieldId,
					// plupload reads this raw: '5' would mean 5 *bytes*.
					file_size: '5mb',
					files_allowed: '1',
					file_types: 'jpg,png',
				} ),
			],
		} );

		await attachPpomGroupToProducts( requestUtils, {
			ppomId,
			productIds: [ product.id ],
		} );

		await page.goto( `/?p=${ product.id }` );

		// The reproduction condition: the plugin's Bootstrap CSS is not on the page.
		await expect(
			page.locator( 'link[href*="bootstrap.modal.css"]' )
		).toHaveCount( 0 );

		const fileInput = page.locator(
			`#ppom-file-container-${ fieldId } input[type=file]`
		);
		await fileInput.waitFor( { state: 'attached', timeout: 10000 } );

		await fileInput.setInputFiles( {
			name: 'pixel.png',
			mimeType: 'image/png',
			buffer: PIXEL_PNG,
		} );

		// Upload finished: the thumbnail and its Delete button are in the file list.
		await expect(
			page.locator( `#filelist-${ fieldId } .u_i_c_tools_del` )
		).toBeVisible( { timeout: 10000 } );

		// The modal markup must not exist, so nothing can render at the bottom of the page.
		await expect( page.locator( '.ppom-modals' ) ).toHaveCount( 0 );

		// Uploads are renamed with a unique suffix, so match the upload directory instead of
		// the file name. The only visible upload image is the thumbnail inside the field.
		await expect(
			page.locator( 'img[src*="/ppom_files/"]:visible' )
		).toHaveCount( 1 );
	} );
} );
