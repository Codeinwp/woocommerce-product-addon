import { buildTextField } from './fields.js';
import {
	appendNestedParams,
	getAdminNonce,
	getAttachNonce,
	postAdminAjax,
	uniqueSuffix,
} from './internal.js';

async function createPpomGroup( requestUtils, { groupName, fields } ) {
	const formNonce = await getAdminNonce(
		requestUtils,
		'wp-admin/admin.php?page=ppom&action=new',
		'ppom_form_nonce'
	);

	const ppomFields = new URLSearchParams();
	fields.forEach( ( field, index ) => {
		appendNestedParams( ppomFields, `ppom[${ index + 1 }]`, field );
	} );

	const params = new URLSearchParams();
	params.append( 'action', 'ppom_save_form_meta' );
	params.append( 'ppom_form_nonce', formNonce );
	params.append( 'productmeta_id', '' );
	params.append( 'product_id', '0' );
	params.append( 'productmeta_name', groupName );
	params.append( 'dynamic_price_hide', 'no' );
	params.append( 'send_file_attachment', '' );
	params.append( 'show_cart_thumb', 'no' );
	params.append( 'aviary_api_key', '' );
	params.append( 'productmeta_style', '' );
	params.append( 'productmeta_js', '' );
	params.append( 'ppom', ppomFields.toString() );

	const payload = await postAdminAjax( requestUtils, params );
	const ppomId = Number( payload.productmeta_id );

	if ( ! Number.isInteger( ppomId ) || ppomId <= 0 ) {
		throw new Error(
			`PPOM save did not return a valid productmeta_id: ${ JSON.stringify(
				payload
			) }`
		);
	}

	return {
		...payload,
		ppomId,
	};
}

async function createSimpleTextGroup(
	requestUtils,
	{
		groupName,
		fieldsNumber = 2,
		titlePrefix = 'Test',
		dataNamePrefix = 'test',
	} = {}
) {
	const suffix = uniqueSuffix();
	const fields = Array.from( { length: fieldsNumber }, ( _, index ) =>
		buildTextField( {
			title: `${ titlePrefix } ${ index + 1 } ${ suffix }`,
			dataName: `${ dataNamePrefix }_${ index + 1 }_${ suffix }`,
		} )
	);

	return createPpomGroup( requestUtils, {
		groupName: groupName ?? `PPOM Group ${ suffix }`,
		fields,
	} );
}

async function attachPpomGroupToProducts( requestUtils, { ppomId, productIds } ) {
	const attachNonce = await getAttachNonce( requestUtils, ppomId );

	const params = new URLSearchParams();
	params.append( 'action', 'ppom_attach_ppoms' );
	params.append( 'ppom_attached_nonce', attachNonce );
	params.append( 'ppom_id', String( ppomId ) );
	params.append( 'ppom-attach-to-products-initial', '' );

	productIds.forEach( ( productId ) => {
		params.append( 'ppom-attach-to-products[]', String( productId ) );
	} );

	return postAdminAjax( requestUtils, params );
}

async function attachPpomGroupToCategories(
	requestUtils,
	{ ppomId, categorySlugs }
) {
	const attachNonce = await getAttachNonce( requestUtils, ppomId );

	const params = new URLSearchParams();
	params.append( 'action', 'ppom_attach_ppoms' );
	params.append( 'ppom_attached_nonce', attachNonce );
	params.append( 'ppom_id', String( ppomId ) );
	params.append( 'ppom-attach-to-products-initial', '' );

	categorySlugs.forEach( ( slug ) => {
		params.append( 'ppom-attach-to-categories[]', slug );
	} );

	return postAdminAjax( requestUtils, params );
}

export {
	attachPpomGroupToCategories,
	attachPpomGroupToProducts,
	createPpomGroup,
	createSimpleTextGroup,
};
