import { uniqueSuffix } from './internal.js';

function rethrowMissingRoute( error, route, entityName ) {
	if ( error?.code === 'rest_no_route' ) {
		throw new Error(
			`Failed to create ${ entityName }: WooCommerce REST route "${ route }" is unavailable in this E2E environment.`
		);
	}

	throw error;
}

async function createProductCategory( requestUtils, overrides = {} ) {
	const suffix = uniqueSuffix();
	let category;

	try {
		category = await requestUtils.rest( {
			method: 'POST',
			path: '/wc/v3/products/categories',
			data: {
				name: overrides.name ?? `Category ${ suffix }`,
				slug: overrides.slug ?? `ppom-e2e-cat-${ suffix }`,
				...overrides,
			},
		} );
	} catch ( error ) {
		rethrowMissingRoute(
			error,
			'/wc/v3/products/categories',
			'product category'
		);
	}

	return category;
}

async function createSimpleProduct( requestUtils, overrides = {} ) {
	const suffix = uniqueSuffix();
	let product;

	try {
		product = await requestUtils.rest( {
			method: 'POST',
			path: '/wc/v3/products',
			data: {
				name: overrides.name ?? `PPOM E2E Product ${ suffix }`,
				type: 'simple',
				status: 'publish',
				regular_price: '9.99',
				...overrides,
			},
		} );
	} catch ( error ) {
		rethrowMissingRoute( error, '/wc/v3/products', 'product' );
	}

	return product;
}

export { createProductCategory, createSimpleProduct };
