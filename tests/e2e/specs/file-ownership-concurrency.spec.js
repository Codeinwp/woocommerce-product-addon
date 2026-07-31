/**
 * External dependencies
 */
import { request as playwrightRequest } from '@playwright/test';

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

const FIELD = 'concurrent_upload';

/**
 * The contract these tests hold the endpoints to, whatever backs it: every file
 * a shopper uploads can be deleted by that shopper, and by nobody else.
 *
 * A product can carry several file fields and each one gets its own uploader
 * (`js/file-upload.js:338`), so uploads genuinely overlap. Driving the endpoints
 * directly is the only way to make that overlap reliable in a test.
 */
async function getNonces( context ) {
	const response = await context.get( '?rest_route=/ppom/v1/nonces/file/' );

	return response.json();
}

async function uploadFile( context, { productId, name, nonce } ) {
	const response = await context.post( 'wp-admin/admin-ajax.php', {
		multipart: {
			action: 'ppom_upload_file',
			ppom_nonce: nonce,
			name,
			product_id: String( productId ),
			data_name: FIELD,
			chunk: '0',
			chunks: '1',
			file: {
				name,
				mimeType: 'text/plain',
				buffer: Buffer.from( name ),
			},
		},
		failOnStatusCode: false,
	} );

	return response.json();
}

async function deleteFile( context, { fileName, nonce, token } ) {
	const form = {
		action: 'ppom_delete_file',
		file_name: fileName,
		ppom_nonce: nonce,
	};

	if ( token ) {
		form.ppom_delete_token = token;
	}

	const response = await context.post( 'wp-admin/admin-ajax.php', {
		form,
		failOnStatusCode: false,
	} );

	return response.text();
}

async function createProductWithFileField( requestUtils, groupName ) {
	const product = await createSimpleProduct( requestUtils );
	const { ppomId } = await createPpomGroup( requestUtils, {
		groupName,
		fields: [
			buildFileField( {
				title: 'Artwork',
				dataName: FIELD,
				file_size: '5mb',
				files_allowed: '10',
				file_types: 'txt',
			} ),
		],
	} );

	await attachPpomGroupToProducts( requestUtils, {
		ppomId,
		productIds: [ product.id ],
	} );

	return product;
}

function resolveBaseURL( testInfo ) {
	const baseURL =
		typeof testInfo.project.use.baseURL === 'string'
			? testInfo.project.use.baseURL
			: process.env.WP_BASE_URL;

	if ( ! baseURL ) {
		throw new Error( 'Playwright baseURL is required for these checks.' );
	}

	return baseURL;
}

test.describe( 'PPOM upload ownership under concurrency', () => {
	/**
	 * A shopper who already has a session uploads to several fields at once.
	 * Ownership used to be one array in the session row, and every request wrote
	 * that whole row back, so the last write dropped the other names and their
	 * uploader was refused deletion of files it had just uploaded.
	 */
	test( 'every file in a parallel batch can be deleted by its uploader', async ( {
		requestUtils,
	}, testInfo ) => {
		const product = await createProductWithFileField(
			requestUtils,
			'Concurrent Upload Test'
		);

		const shopper = await playwrightRequest.newContext( {
			baseURL: resolveBaseURL( testInfo ),
			storageState: { cookies: [], origins: [] },
		} );

		try {
			const { ppom_file_upload_nonce: uploadNonce } = await getNonces(
				shopper
			);

			// One upload first, so this shopper already has a session.
			const first = await uploadFile( shopper, {
				productId: product.id,
				name: 'first.txt',
				nonce: uploadNonce,
			} );

			// Then a batch at once, as separate file fields do.
			const batch = await Promise.all(
				[ 1, 2, 3, 4 ].map( ( index ) =>
					uploadFile( shopper, {
						productId: product.id,
						name: `batch${ index }.txt`,
						nonce: uploadNonce,
					} )
				)
			);

			const uploads = [ first, ...batch ];
			uploads.forEach( ( upload ) =>
				expect( upload.file_name ).toBeTruthy()
			);

			const { ppom_file_delete_nonce: deleteNonce } = await getNonces(
				shopper
			);

			for ( const upload of uploads ) {
				const body = await deleteFile( shopper, {
					fileName: upload.file_name,
					nonce: deleteNonce,
					token: upload.delete_token,
				} );

				expect(
					body,
					`${ upload.file_name } must be deletable by its uploader`
				).toContain( 'File removed' );
			}
		} finally {
			await shopper.dispose();
		}
	} );

	/**
	 * WooCommerce only forces a session cookie on the order-pay endpoint, so a
	 * shopper with an empty cart reaches a product page without one. Parallel
	 * uploads then each minted their own session and sent their own cookie; the
	 * browser kept the last, and everything recorded against the others was
	 * unreachable.
	 */
	test( 'parallel uploads from a visitor with no session are all deletable', async ( {
		requestUtils,
	}, testInfo ) => {
		const product = await createProductWithFileField(
			requestUtils,
			'Cookieless Upload Test'
		);

		const shopper = await playwrightRequest.newContext( {
			baseURL: resolveBaseURL( testInfo ),
			storageState: { cookies: [], origins: [] },
		} );

		try {
			const { ppom_file_upload_nonce: uploadNonce } = await getNonces(
				shopper
			);

			// No warm-up upload: nothing has established a session yet.
			const uploads = await Promise.all(
				[ 1, 2, 3 ].map( ( index ) =>
					uploadFile( shopper, {
						productId: product.id,
						name: `cold${ index }.txt`,
						nonce: uploadNonce,
					} )
				)
			);

			const { ppom_file_delete_nonce: deleteNonce } = await getNonces(
				shopper
			);

			for ( const upload of uploads ) {
				expect( upload.file_name ).toBeTruthy();

				const body = await deleteFile( shopper, {
					fileName: upload.file_name,
					nonce: deleteNonce,
					token: upload.delete_token,
				} );

				expect(
					body,
					`${ upload.file_name } must be deletable by its uploader`
				).toContain( 'File removed' );
			}
		} finally {
			await shopper.dispose();
		}
	} );

	/**
	 * The reported vulnerability must stay closed under the token scheme: holding
	 * a valid nonce, and even a valid token for a file of your own, must not let
	 * you delete somebody else's upload.
	 */
	test( 'another visitor cannot delete an upload, with or without a token of their own', async ( {
		requestUtils,
	}, testInfo ) => {
		const product = await createProductWithFileField(
			requestUtils,
			'Cross Visitor Token Test'
		);
		const baseURL = resolveBaseURL( testInfo );

		const victim = await playwrightRequest.newContext( {
			baseURL,
			storageState: { cookies: [], origins: [] },
		} );
		const attacker = await playwrightRequest.newContext( {
			baseURL,
			storageState: { cookies: [], origins: [] },
		} );

		try {
			const { ppom_file_upload_nonce: victimUploadNonce } =
				await getNonces( victim );
			const victimUpload = await uploadFile( victim, {
				productId: product.id,
				name: 'victim-artwork.txt',
				nonce: victimUploadNonce,
			} );
			expect( victimUpload.file_name ).toBeTruthy();

			// The attacker uploads too, so they hold a genuine token of their own.
			const {
				ppom_file_upload_nonce: attackerUploadNonce,
				ppom_file_delete_nonce: attackerDeleteNonce,
			} = await getNonces( attacker );
			const attackerUpload = await uploadFile( attacker, {
				productId: product.id,
				name: 'attacker-own.txt',
				nonce: attackerUploadNonce,
			} );
			expect( attackerUpload.delete_token ).toBeTruthy();

			const withoutToken = await deleteFile( attacker, {
				fileName: victimUpload.file_name,
				nonce: attackerDeleteNonce,
			} );
			expect( withoutToken ).toContain( 'Verification failed' );

			const withOwnToken = await deleteFile( attacker, {
				fileName: victimUpload.file_name,
				nonce: attackerDeleteNonce,
				token: attackerUpload.delete_token,
			} );
			expect( withOwnToken ).toContain( 'Verification failed' );

			// Still there, so the victim can remove it themselves.
			const { ppom_file_delete_nonce: victimDeleteNonce } =
				await getNonces( victim );
			const byOwner = await deleteFile( victim, {
				fileName: victimUpload.file_name,
				nonce: victimDeleteNonce,
				token: victimUpload.delete_token,
			} );
			expect( byOwner ).toContain( 'File removed' );
		} finally {
			await victim.dispose();
			await attacker.dispose();
		}
	} );

	/**
	 * Pages cached before this shipped run JavaScript that sends no token, so the
	 * session check has to keep working on its own.
	 */
	test( 'a delete with no token still works through the session', async ( {
		requestUtils,
	}, testInfo ) => {
		const product = await createProductWithFileField(
			requestUtils,
			'Session Fallback Test'
		);

		const shopper = await playwrightRequest.newContext( {
			baseURL: resolveBaseURL( testInfo ),
			storageState: { cookies: [], origins: [] },
		} );

		try {
			const { ppom_file_upload_nonce: uploadNonce } = await getNonces(
				shopper
			);
			const upload = await uploadFile( shopper, {
				productId: product.id,
				name: 'legacy-client.txt',
				nonce: uploadNonce,
			} );
			expect( upload.file_name ).toBeTruthy();

			const { ppom_file_delete_nonce: deleteNonce } = await getNonces(
				shopper
			);

			const body = await deleteFile( shopper, {
				fileName: upload.file_name,
				nonce: deleteNonce,
			} );

			expect( body ).toContain( 'File removed' );
		} finally {
			await shopper.dispose();
		}
	} );
} );
