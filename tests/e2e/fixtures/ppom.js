import { buildTextField } from './fields.js';
import { postBootstrapAction, uniqueSuffix } from './internal.js';

async function createPpomGroup(
	requestUtils,
	{ groupName, fields, productId = 0, settings = {} }
) {
	const payload = await postBootstrapAction(
		requestUtils,
		'ppom_e2e_create_ppom_group',
		{
			group_name: groupName,
			product_id: productId,
			fields,
			settings: {
				dynamic_price_hide: 'no',
				send_file_attachment: '',
				show_cart_thumb: 'no',
				aviary_api_key: '',
				productmeta_style: '',
				productmeta_js: '',
				...settings,
			},
		}
	);
	const ppomId = Number( payload.ppom_id ?? payload.productmeta_id );

	if ( ! Number.isInteger( ppomId ) || ppomId <= 0 ) {
		throw new Error(
			`PPOM bootstrap did not return a valid productmeta_id: ${ JSON.stringify(
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

async function attachPpomGroupToProducts(
	requestUtils,
	{ ppomId, productIds, productIdsInitial = [] }
) {
	return postBootstrapAction( requestUtils, 'ppom_e2e_attach_ppom_group', {
		ppom_id: ppomId,
		product_ids: productIds,
		product_ids_initial: productIdsInitial,
		category_slugs: [],
	} );
}

async function attachPpomGroupToCategories(
	requestUtils,
	{ ppomId, categorySlugs, productIdsInitial = [] }
) {
	return postBootstrapAction( requestUtils, 'ppom_e2e_attach_ppom_group', {
		ppom_id: ppomId,
		product_ids: [],
		product_ids_initial: productIdsInitial,
		category_slugs: categorySlugs,
	} );
}

async function getPpomAttachRowMeta( requestUtils, { ppomId } ) {
	return postBootstrapAction( requestUtils, 'ppom_e2e_get_ppom_attach_row', {
		ppom_id: ppomId,
	} );
}

export {
	attachPpomGroupToCategories,
	attachPpomGroupToProducts,
	createPpomGroup,
	createSimpleTextGroup,
	getPpomAttachRowMeta,
};
