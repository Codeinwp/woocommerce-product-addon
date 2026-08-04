import { test, expect } from '@wordpress/e2e-test-utils-playwright';

import {
	attachPpomGroupToProducts,
	buildTextField,
	createPpomGroup,
	createSimpleProduct,
} from '../fixtures/index.js';

test.describe( 'Admin bar PPOM menu', () => {
	test( 'ships scroll CSS for the field-group submenu (#523)', async ( {
		page,
		requestUtils,
	} ) => {
		const suffix = Date.now();
		const product = await createSimpleProduct( requestUtils, {
			name: `Admin Bar Scroll Product ${ suffix }`,
		} );
		const { ppomId } = await createPpomGroup( requestUtils, {
			groupName: `Admin Bar Scroll Group ${ suffix }`,
			fields: [
				buildTextField( {
					title: 'Any text',
					dataName: `adminbar_text_${ suffix }`,
				} ),
			],
		} );
		await attachPpomGroupToProducts( requestUtils, {
			ppomId,
			productIds: [ product.id ],
		} );

		await page.goto( `/?p=${ product.id }` );

		// A long field-group list must stop at half the viewport and scroll.
		// The node is built synthetically: `bar_menu()` loses the `$product`
		// global in wp-env, so the real node never renders (pre-existing bug).
		const submenu = await page.evaluate( () => {
			const node = document.createElement( 'li' );
			node.id = 'wp-admin-bar-ppom-setting-bar';
			node.className = 'menupop hover';
			const wrapper = document.createElement( 'div' );
			wrapper.className = 'ab-sub-wrapper';
			const list = document.createElement( 'ul' );
			list.className = 'ab-submenu';
			for ( let i = 0; i < 100; i++ ) {
				const item = document.createElement( 'li' );
				const link = document.createElement( 'a' );
				link.className = 'ab-item';
				link.textContent = `Apply Group ${ i }`;
				item.appendChild( link );
				list.appendChild( item );
			}
			wrapper.appendChild( list );
			node.appendChild( wrapper );
			document.getElementById( 'wpadminbar' ).appendChild( node );
			return {
				capsAtHalfViewport:
					Math.abs( wrapper.clientHeight - window.innerHeight / 2 ) <=
					2,
				scrollsOverflow: wrapper.scrollHeight > wrapper.clientHeight,
			};
		} );
		expect( submenu ).toEqual( {
			capsAtHalfViewport: true,
			scrollsOverflow: true,
		} );
	} );
} );
