/**
 * WordPress dependencies
 */
import { test, expect } from '@wordpress/e2e-test-utils-playwright';

import {
	attachPpomGroupToProducts,
	buildCropperField,
	buildFileField,
	buildSelectField,
	buildTextField,
	createPpomGroup,
	createPpomShortcodePage,
	createSimpleProduct,
	setLegacyConditionsScript,
	setPpomLicenseFixture,
} from '../fixtures/index.js';

/**
 * Regression coverage for #735: two PPOM forms on one page stay independent.
 */

const PNG_1X1 = {
	name: 'pixel.png',
	mimeType: 'image/png',
	buffer: Buffer.from(
		'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==',
		'base64'
	),
};

function uniqueToken() {
	return `${ Date.now() }_${ Math.floor( Math.random() * 1e6 ) }`;
}

/**
 * The `.ppom-wrapper` of one product; both forms reuse the same ids.
 *
 * @param {import('@playwright/test').Page} page      Playwright page.
 * @param {number}                          productId Product id.
 * @return {import('@playwright/test').Locator} Form locator.
 */
function formFor( page, productId ) {
	return page.locator( '.ppom-wrapper', {
		has: page.locator( `[name="ppom_product_id"][value="${ productId }"]` ),
	} );
}

/**
 * Render the given fields for two products on one shortcode page.
 *
 * @param {Object}                          args              Arguments.
 * @param {import('@playwright/test').Page} args.page         Playwright page.
 * @param {Object}                          args.requestUtils Request utils.
 * @param {string}                          args.token        Unique token.
 * @param {Array<Object>}                   args.fields       PPOM fields.
 * @return {Promise<{formA: import('@playwright/test').Locator, formB: import('@playwright/test').Locator}>} Both forms.
 */
async function openTwoForms( { page, requestUtils, token, fields } ) {
	const productA = await createSimpleProduct( requestUtils );
	const productB = await createSimpleProduct( requestUtils );

	const { ppomId } = await createPpomGroup( requestUtils, {
		groupName: `Multi-form ${ token }`,
		fields,
	} );

	await attachPpomGroupToProducts( requestUtils, {
		ppomId,
		productIds: [ productA.id, productB.id ],
	} );

	const { permalink } = await createPpomShortcodePage( requestUtils, {
		productIds: [ productA.id, productB.id ],
		title: `Multi-form ${ token }`,
	} );

	await page.goto( permalink );
	page.on( 'dialog', ( dialog ) => dialog.accept().catch( () => {} ) );

	const formA = formFor( page, productA.id );
	const formB = formFor( page, productB.id );

	await expect( formA ).toBeVisible();
	await expect( formB ).toBeVisible();

	return { formA, formB };
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

		const { formA, formB } = await openTwoForms( {
			page,
			requestUtils,
			token,
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

		// Not getByLabel(): duplicate ids make labels resolve to form A.
		const targetA = formA.locator(
			`.ppom-field-wrapper[data-data_name="${ targetId }"]`
		);
		const targetB = formB.locator(
			`.ppom-field-wrapper[data-data_name="${ targetId }"]`
		);
		const hiddenA = formA.locator( '#conditionally_hidden' );
		const hiddenB = formB.locator( '#conditionally_hidden' );

		await expect( targetA ).toBeHidden();
		await expect( targetB ).toBeHidden();
		await expect( hiddenA ).toHaveValue( targetId );
		await expect( hiddenB ).toHaveValue( targetId );

		await formA
			.locator( `select[name="ppom[fields][${ triggerId }]"]` )
			.selectOption( { label: revealOption.label } );

		await expect( targetA ).toBeVisible();
		await expect( hiddenA ).toHaveValue( '' );
		await expect( targetB ).toBeHidden();
		await expect( hiddenB ).toHaveValue( targetId );

		await formB
			.locator( `select[name="ppom[fields][${ triggerId }]"]` )
			.selectOption( { label: revealOption.label } );

		await expect( targetB ).toBeVisible();
		await expect( hiddenB ).toHaveValue( '' );
		await expect( targetA ).toBeVisible();
		await expect( hiddenA ).toHaveValue( '' );

		const fileInputA = formA.locator(
			`#ppom-file-container-${ uploadId } input[type=file]`
		);
		const fileInputB = formB.locator(
			`#ppom-file-container-${ uploadId } input[type=file]`
		);
		const previewA = formA.locator(
			`#filelist-${ uploadId } .u_i_c_tools_del`
		);
		const previewB = formB.locator(
			`#filelist-${ uploadId } .u_i_c_tools_del`
		);

		await fileInputA.setInputFiles( { ...PNG_1X1, name: 'form-a.png' } );
		await expect( previewA ).toHaveCount( 1, { timeout: 10000 } );
		await expect( previewB ).toHaveCount( 0 );

		await fileInputB.setInputFiles( { ...PNG_1X1, name: 'form-b.png' } );
		await expect( previewB ).toHaveCount( 1, { timeout: 10000 } );
		await expect( previewA ).toHaveCount( 1 );

		await previewA.click();
		await expect( previewA ).toHaveCount( 0 );
		await expect( previewB ).toHaveCount( 1 );
	} );

	test( 'hiding resets and showing restores defaults only in the changed form', async ( {
		page,
		requestUtils,
	} ) => {
		const token = uniqueToken();
		const triggerId = `trigger_${ token }`;
		const targetId = `target_${ token }`;
		const choiceId = `choice_${ token }`;
		const revealOption = { label: 'Reveal', value: 'reveal' };
		const hideOption = { label: 'Hide', value: 'hide' };
		const revealRule = {
			visibility: 'Show',
			bound: 'All',
			rules: [
				{
					elements: triggerId,
					operators: 'is',
					element_values: revealOption.label,
				},
			],
		};

		const { formA, formB } = await openTwoForms( {
			page,
			requestUtils,
			token,
			fields: [
				buildSelectField( {
					title: `Trigger ${ token }`,
					dataName: triggerId,
					options: [ hideOption, revealOption ],
				} ),
				buildTextField( {
					title: `Target ${ token }`,
					dataName: targetId,
					logic: 'on',
					conditions: revealRule,
				} ),
				buildSelectField( {
					title: `Choice ${ token }`,
					dataName: choiceId,
					selected: 'Two',
					options: [
						{ label: 'One', value: 'one' },
						{ label: 'Two', value: 'two' },
					],
					logic: 'on',
					conditions: revealRule,
				} ),
			],
		} );

		const triggerA = formA.locator(
			`select[name="ppom[fields][${ triggerId }]"]`
		);
		const triggerB = formB.locator(
			`select[name="ppom[fields][${ triggerId }]"]`
		);
		const textA = formA.locator(
			`input[name="ppom[fields][${ targetId }]"]`
		);
		const textB = formB.locator(
			`input[name="ppom[fields][${ targetId }]"]`
		);
		const choiceA = formA.locator(
			`select[name="ppom[fields][${ choiceId }]"]`
		);
		const choiceB = formB.locator(
			`select[name="ppom[fields][${ choiceId }]"]`
		);

		// Showing in B restores B's default only; an unscoped `#id` hits A.
		await triggerB.selectOption( { label: revealOption.label } );
		await expect( choiceB ).toBeVisible();
		await expect( choiceB ).toHaveValue( 'Two' );
		await expect( choiceA ).toBeHidden();
		await expect( choiceA ).not.toHaveValue( 'Two' );

		await triggerA.selectOption( { label: revealOption.label } );
		await expect( choiceA ).toHaveValue( 'Two' );

		await textA.fill( 'value A' );
		await textB.fill( 'value B' );

		// Hiding in B resets B only.
		await triggerB.selectOption( { label: hideOption.label } );
		await expect( textB ).toBeHidden();
		await expect( textB ).toHaveValue( '' );
		await expect( textA ).toHaveValue( 'value A' );

		await triggerB.selectOption( { label: revealOption.label } );
		await textB.fill( 'value B' );

		// Hiding in A resets A only.
		await triggerA.selectOption( { label: hideOption.label } );
		await expect( textA ).toBeHidden();
		await expect( textA ).toHaveValue( '' );
		await expect( textB ).toHaveValue( 'value B' );
	} );

	test( 'cropper upload shows its preview only in its own form', async ( {
		page,
		requestUtils,
	} ) => {
		const token = uniqueToken();
		const cropperId = `cropper_${ token }`;
		const pageErrors = [];
		page.on( 'pageerror', ( error ) => pageErrors.push( error.message ) );

		await setPpomLicenseFixture( requestUtils, { valid: true, plan: 1 } );

		const { formA, formB } = await openTwoForms( {
			page,
			requestUtils,
			token,
			fields: [
				buildCropperField( {
					title: `Cropper ${ token }`,
					dataName: cropperId,
					options: [
						{
							label: 'Square',
							value: 'square',
							width: '100',
							height: '100',
						},
					],
				} ),
			],
		} );

		await formB
			.locator( `#ppom-file-container-${ cropperId } input[type=file]` )
			.setInputFiles( PNG_1X1 );

		const croppieB = formB.locator(
			`.ppom-croppie-wrapper-${ cropperId } .croppie-container`
		);
		await expect( croppieB ).toHaveCount( 1, { timeout: 10000 } );
		await expect(
			formA.locator(
				`.ppom-croppie-wrapper-${ cropperId } .croppie-container`
			)
		).toHaveCount( 0 );
		await expect(
			formB.locator(
				`input[name^="ppom[fields][${ cropperId }]"][name$="[cropped]"]`
			)
		).toHaveCount( 1 );
		expect( pageErrors ).toEqual( [] );
	} );

	test.describe( 'block theme', () => {
		test.beforeEach( async ( { requestUtils } ) => {
			await requestUtils.activateTheme( 'twentytwentyfive' );
		} );

		test.afterEach( async ( { requestUtils } ) => {
			await requestUtils.activateTheme( 'twentytwentyone' );
		} );

		test( 'shortcode forms still get a working uploader', async ( {
			page,
			requestUtils,
		} ) => {
			const token = uniqueToken();
			const uploadId = `upload_${ token }`;

			const { formA, formB } = await openTwoForms( {
				page,
				requestUtils,
				token,
				fields: [
					buildFileField( {
						title: `Upload ${ token }`,
						dataName: uploadId,
						file_size: '5mb',
						files_allowed: '1',
						file_types: 'png,jpg',
					} ),
				],
			} );

			await formB
				.locator(
					`#ppom-file-container-${ uploadId } input[type=file]`
				)
				.setInputFiles( PNG_1X1 );

			await expect(
				formB.locator( `#filelist-${ uploadId } .u_i_c_tools_del` )
			).toHaveCount( 1, { timeout: 10000 } );
			await expect(
				formA.locator( `#filelist-${ uploadId } .u_i_c_tools_del` )
			).toHaveCount( 0 );
		} );
	} );

	test.describe( 'legacy conditions script', () => {
		test.beforeEach( async ( { requestUtils } ) => {
			await setPpomLicenseFixture( requestUtils, {
				valid: true,
				plan: 1,
			} );
			const applied = await setLegacyConditionsScript(
				requestUtils,
				true
			);
			expect( applied.conditions_mode ).toBe( 'legacy' );
		} );

		test.afterEach( async ( { requestUtils } ) => {
			const applied = await setLegacyConditionsScript(
				requestUtils,
				false
			);
			expect( applied.conditions_mode ).toBe( 'new' );
		} );

		test( 'a revealed upload field keeps one uploader per form', async ( {
			page,
			requestUtils,
		} ) => {
			const token = uniqueToken();
			const triggerId = `trigger_${ token }`;
			const uploadId = `upload_${ token }`;
			const revealOption = { label: 'Reveal', value: 'reveal' };

			const { formA, formB } = await openTwoForms( {
				page,
				requestUtils,
				token,
				fields: [
					buildSelectField( {
						title: `Trigger ${ token }`,
						dataName: triggerId,
						options: [
							{ label: 'Hide', value: 'hide' },
							revealOption,
						],
					} ),
					buildFileField( {
						title: `Upload ${ token }`,
						dataName: uploadId,
						file_size: '5mb',
						files_allowed: '1',
						file_types: 'png,jpg',
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
				],
			} );

			await formA
				.locator( `select[name="ppom[fields][${ triggerId }]"]` )
				.selectOption( { label: revealOption.label } );

			const uploadA = formA.locator(
				`.ppom-field-wrapper[data-data_name="${ uploadId }"]`
			);
			await expect( uploadA ).toBeVisible();

			// One shim per form: a re-setup must not bind a second uploader.
			for ( const form of [ formA, formB ] ) {
				await expect(
					form.locator(
						`#ppom-file-container-${ uploadId } input[type=file]`
					)
				).toHaveCount( 1 );
			}

			await formA
				.locator(
					`#ppom-file-container-${ uploadId } input[type=file]`
				)
				.setInputFiles( PNG_1X1 );

			await expect(
				formA.locator( `#filelist-${ uploadId } .u_i_c_tools_del` )
			).toHaveCount( 1, { timeout: 10000 } );
			await expect(
				formB.locator( `#filelist-${ uploadId } .u_i_c_tools_del` )
			).toHaveCount( 0 );
		} );
	} );
} );
