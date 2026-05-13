/**
 * WordPress dependencies
 */
import { test, expect } from '@wordpress/e2e-test-utils-playwright';

import {
	attachPpomGroupToProducts,
	buildHtmlField,
	createPpomGroup,
	createSimpleProduct,
} from '../fixtures/index.js';

test.describe( 'HTML field', () => {
	/**
	 * The HTML field stores its content under the `html` key on a `section`
	 * field type. The frontend template runs the value through the `the_content`
	 * filter and prints it inside the field wrapper. This test confirms the raw
	 * HTML markup is rendered as actual DOM and that the hidden input that
	 * carries the value into the cart is present.
	 */
	test( 'renders configured HTML content on the product page', async ( {
		page,
		requestUtils,
	} ) => {
		const fieldId = `html_test_${ Date.now() }`;
		const headingText = `PPOM HTML Heading ${ Date.now() }`;
		const paragraphText = 'This paragraph is rendered from the HTML field.';
		const linkHref = 'https://example.com/ppom-html-link';
		const linkText = 'Inline link';
		const htmlContent = `<h2 class="ppom-html-test-heading">${ headingText }</h2>
<p class="ppom-html-test-paragraph">${ paragraphText } <a href="${ linkHref }">${ linkText }</a></p>`;

		const product = await createSimpleProduct( requestUtils );
		const { ppomId } = await createPpomGroup( requestUtils, {
			groupName: 'HTML Field Group',
			fields: [
				buildHtmlField( {
					title: '',
					dataName: fieldId,
					html: htmlContent,
				} ),
			],
		} );

		await attachPpomGroupToProducts( requestUtils, {
			ppomId,
			productIds: [ product.id ],
		} );

		await page.goto( `/?ppom_e2e_product_page=${ product.id }` );

		const heading = page.locator( 'h2.ppom-html-test-heading' );
		await expect( heading ).toBeVisible();
		await expect( heading ).toHaveText( headingText );

		const paragraph = page.locator( 'p.ppom-html-test-paragraph' );
		await expect( paragraph ).toBeVisible();
		await expect( paragraph ).toContainText( paragraphText );

		const link = paragraph.getByRole( 'link', { name: linkText } );
		await expect( link ).toHaveAttribute( 'href', linkHref );

		const hiddenInput = page.locator(
			`input[type="hidden"][name="ppom[fields][${ fieldId }]"]`
		);
		await expect( hiddenInput ).toHaveCount( 1 );
		await expect( hiddenInput ).toHaveAttribute( 'id', fieldId );
	} );
} );
