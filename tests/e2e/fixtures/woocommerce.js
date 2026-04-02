import { postBootstrapAction, uniqueSuffix } from './internal.js';

function normalizeCategoryIds( overrides = {} ) {
	if ( Array.isArray( overrides.categoryIds ) ) {
		return overrides.categoryIds;
	}

	if ( Array.isArray( overrides.category_ids ) ) {
		return overrides.category_ids;
	}

	if ( Array.isArray( overrides.categories ) ) {
		return overrides.categories
			.map( ( category ) => Number( category?.id ) )
			.filter( Number.isInteger );
	}

	return [];
}

async function createProductCategory( requestUtils, overrides = {} ) {
	const suffix = uniqueSuffix();

	return postBootstrapAction(
		requestUtils,
		'ppom_e2e_create_product_category',
		{
			name: overrides.name ?? `Category ${ suffix }`,
			slug: overrides.slug ?? `ppom-e2e-cat-${ suffix }`,
		}
	);
}

async function createSimpleProduct( requestUtils, overrides = {} ) {
	const suffix = uniqueSuffix();

	return postBootstrapAction( requestUtils, 'ppom_e2e_create_simple_product', {
		name: overrides.name ?? `PPOM E2E Product ${ suffix }`,
		status: overrides.status ?? 'publish',
		regular_price: overrides.regular_price ?? '9.99',
		category_ids: normalizeCategoryIds( overrides ),
	} );
}

async function createVariableProduct( requestUtils, overrides = {} ) {
	const suffix = uniqueSuffix();

	return postBootstrapAction(
		requestUtils,
		'ppom_e2e_create_variable_product',
		{
			name: overrides.name ?? `PPOM Variable Product ${ suffix }`,
			status: overrides.status ?? 'publish',
			category_ids: normalizeCategoryIds( overrides ),
			attributes: Array.isArray( overrides.attributes )
				? overrides.attributes
				: [],
			default_attributes:
				typeof overrides.defaultAttributes === 'object' &&
				overrides.defaultAttributes !== null
					? overrides.defaultAttributes
					: {},
		}
	);
}

async function createProductVariation( requestUtils, overrides = {} ) {
	if ( ! Number.isInteger( Number( overrides.productId ) ) ) {
		throw new Error( 'createProductVariation requires a numeric productId.' );
	}

	return postBootstrapAction(
		requestUtils,
		'ppom_e2e_create_product_variation',
		{
			product_id: overrides.productId,
			status: overrides.status ?? 'publish',
			regular_price: overrides.regular_price ?? '12.99',
			attributes:
				typeof overrides.attributes === 'object' &&
				overrides.attributes !== null
					? overrides.attributes
					: {},
		}
	);
}

export {
	createProductCategory,
	createProductVariation,
	createSimpleProduct,
	createVariableProduct,
};
