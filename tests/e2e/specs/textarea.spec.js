/**
 * WordPress dependencies
 */
import { test, expect } from '@wordpress/e2e-test-utils-playwright';

import {
	attachPpomGroupToProducts,
	buildTextareaField,
	createPpomGroup,
	createSimpleProduct,
} from '../fixtures/index.js';

test.describe( 'Textarea', () => {
	/**
	 * Test that placeholder is visible when textarea is empty and hidden when text is entered.
	 */
	test( 'placeholder should be visible when empty and hidden when filled', async ( {
		page,
		requestUtils,
	} ) => {
		const fieldId = 'textarea_placeholder_test';
		const placeholderText = 'Enter your message here...';
		const testText = 'This is some test content';

		const product = await createSimpleProduct( requestUtils );
		const { ppomId } = await createPpomGroup( requestUtils, {
			groupName: 'Textarea Placeholder Test',
			fields: [
				buildTextareaField( {
					title: 'Message',
					dataName: fieldId,
					placeholder: placeholderText,
				} ),
			],
		} );

		await attachPpomGroupToProducts( requestUtils, {
			ppomId,
			productIds: [ product.id ],
		} );

		await page.goto( `/?ppom_e2e_product_page=${ product.id }` );

		const textarea = page.locator(
			`textarea[name="ppom[fields][${ fieldId }]"]`
		);

		await expect( textarea ).toBeVisible();

		await expect( textarea ).toHaveAttribute(
			'placeholder',
			placeholderText
		);

		await expect( textarea ).toHaveValue( '' );

		const placeholderVisible = await textarea.evaluate( ( el ) => {
			const style = window.getComputedStyle( el, '::placeholder' );
			return style.opacity !== '0' && style.display !== 'none';
		} );
		expect( placeholderVisible ).toBe( true );

		await textarea.fill( testText );

		await expect( textarea ).toHaveValue( testText );

		const placeholderHiddenWithContent = await textarea.evaluate(
			( el ) => {
				return el.value !== '';
			}
		);
		expect( placeholderHiddenWithContent ).toBe( true );

		await textarea.clear();

		await expect( textarea ).toHaveValue( '' );

		const placeholderVisibleAfterClear = await textarea.evaluate(
			( el ) => {
				const style = window.getComputedStyle( el, '::placeholder' );
				return style.opacity !== '0' && style.display !== 'none';
			}
		);
		expect( placeholderVisibleAfterClear ).toBe( true );
	} );

	/**
	 * Test that textarea with whitespace-only content is treated as empty for validation
	 * but doesn't break placeholder visibility.
	 */
	test( 'placeholder behavior with whitespace-only content', async ( {
		page,
		requestUtils,
	} ) => {
		const fieldId = 'textarea_whitespace_test';
		const placeholderText = 'Enter details...';

		const product = await createSimpleProduct( requestUtils );
		const { ppomId } = await createPpomGroup( requestUtils, {
			groupName: 'Textarea Whitespace Test',
			fields: [
				buildTextareaField( {
					title: 'Details',
					dataName: fieldId,
					placeholder: placeholderText,
				} ),
			],
		} );

		await attachPpomGroupToProducts( requestUtils, {
			ppomId,
			productIds: [ product.id ],
		} );

		await page.goto( `/?ppom_e2e_product_page=${ product.id }` );

		const textarea = page.locator(
			`textarea[name="ppom[fields][${ fieldId }]"]`
		);

		await expect( textarea ).toBeVisible();

		await expect( textarea ).toHaveValue( '' );

		await textarea.fill( '   \n\t   ' );

		const hasWhitespace = await textarea.evaluate(
			( el ) => el.value.trim() === '' && el.value !== ''
		);
		expect( hasWhitespace ).toBe( true );

		await textarea.clear();
		await expect( textarea ).toHaveValue( '' );
	} );

	/**
	 * Test textarea with default value - placeholder should not be visible when there's a default value.
	 */
	test( 'placeholder should not be visible with default value', async ( {
		page,
		requestUtils,
	} ) => {
		const fieldId = 'textarea_default_value_test';
		const placeholderText = 'Enter your text...';
		const defaultValue = 'This is the default content';

		const product = await createSimpleProduct( requestUtils );
		const { ppomId } = await createPpomGroup( requestUtils, {
			groupName: 'Textarea Default Value Test',
			fields: [
				buildTextareaField( {
					title: 'Content',
					dataName: fieldId,
					placeholder: placeholderText,
					default_value: defaultValue,
				} ),
			],
		} );

		await attachPpomGroupToProducts( requestUtils, {
			ppomId,
			productIds: [ product.id ],
		} );

		await page.goto( `/?ppom_e2e_product_page=${ product.id }` );

		const textarea = page.locator(
			`textarea[name="ppom[fields][${ fieldId }]"]`
		);

		await expect( textarea ).toBeVisible();

		await expect( textarea ).toHaveValue( defaultValue );

		await expect( textarea ).toHaveAttribute(
			'placeholder',
			placeholderText
		);

		await textarea.clear();

		await expect( textarea ).toHaveValue( '' );

		const placeholderVisible = await textarea.evaluate( ( el ) => {
			const style = window.getComputedStyle( el, '::placeholder' );
			return style.opacity !== '0' && style.display !== 'none';
		} );
		expect( placeholderVisible ).toBe( true );
	} );
} );
