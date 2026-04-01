function uniqueSuffix() {
	return `${ Date.now() }-${ Math.floor( Math.random() * 100000 ) }`;
}

function appendNestedParams( params, prefix, value ) {
	if ( value === undefined || value === null ) {
		return;
	}

	if ( Array.isArray( value ) ) {
		value.forEach( ( item, index ) => {
			appendNestedParams( params, `${ prefix }[${ index }]`, item );
		} );
		return;
	}

	if ( typeof value === 'object' ) {
		Object.entries( value ).forEach( ( [ key, nestedValue ] ) => {
			appendNestedParams( params, `${ prefix }[${ key }]`, nestedValue );
		} );
		return;
	}

	params.append( prefix, String( value ) );
}

async function getAdminNonce( requestUtils, path, fieldName ) {
	const response = await requestUtils.request.get( path, {
		failOnStatusCode: false,
	} );
	const html = await response.text();

	if ( ! response.ok() ) {
		const summary = html.replace( /\s+/g, ' ' ).trim().slice( 0, 160 );
		const environmentHint = html.includes(
			'Sorry, you are not allowed to access this page.'
		)
			? ' Ensure WooCommerce and PPOM are active in the E2E environment.'
			: '';

		throw new Error(
			`Failed to load admin nonce source "${ path }": ${ response.status() } ${ response.statusText() }. ${ summary }${ environmentHint }`
		);
	}

	const matcher = new RegExp(
		`name=["']${ fieldName }["'][^>]*value=["']([^"']+)["']`
	);
	const nonce = html.match( matcher )?.[ 1 ];

	if ( ! nonce ) {
		const summary = html.replace( /\s+/g, ' ' ).trim().slice( 0, 160 );

		throw new Error(
			`Failed to find admin nonce "${ fieldName }" on "${ path }". Response started with: ${ summary }`
		);
	}

	return nonce;
}

async function getAttachNonce( requestUtils, ppomId ) {
	return getAdminNonce(
		requestUtils,
		`wp-admin/admin-ajax.php?action=ppom_get_products&ppom_id=${ ppomId }`,
		'ppom_attached_nonce'
	);
}

async function postAdminAjax( requestUtils, params ) {
	const response = await requestUtils.request.fetch(
		'wp-admin/admin-ajax.php',
		{
			method: 'POST',
			failOnStatusCode: true,
			headers: {
				'content-type':
					'application/x-www-form-urlencoded; charset=UTF-8',
			},
			data: params.toString(),
		}
	);
	const payload = await response.json();

	if ( payload?.status !== 'success' ) {
		throw new Error(
			`Admin AJAX request failed: ${ JSON.stringify( payload ) }`
		);
	}

	return payload;
}

export {
	appendNestedParams,
	getAdminNonce,
	getAttachNonce,
	postAdminAjax,
	uniqueSuffix,
};
