const bootstrapNonceCache = new WeakMap();

function uniqueSuffix() {
	return `${ Date.now() }-${ Math.floor( Math.random() * 100000 ) }`;
}

function appendAjaxValue( params, key, value ) {
	if ( value === undefined || value === null ) {
		return;
	}

	if ( Array.isArray( value ) || typeof value === 'object' ) {
		params.append( key, JSON.stringify( value ) );
		return;
	}

	params.append( key, String( value ) );
}

async function getE2EBootstrapNonce( requestUtils ) {
	if ( bootstrapNonceCache.has( requestUtils ) ) {
		return bootstrapNonceCache.get( requestUtils );
	}

	const response = await requestUtils.request.fetch(
		'wp-admin/admin-ajax.php?action=ppom_e2e_get_nonce',
		{
			failOnStatusCode: false,
		}
	);
	const payload = await response.json();

	if ( ! response.ok() || ! payload?.success || ! payload?.data?.nonce ) {
		throw new Error(
			`Failed to load E2E bootstrap nonce: ${ JSON.stringify( payload ) }`
		);
	}

	bootstrapNonceCache.set( requestUtils, payload.data.nonce );

	return payload.data.nonce;
}

async function postWpAjax( requestUtils, params ) {
	const response = await requestUtils.request.fetch(
		'wp-admin/admin-ajax.php',
		{
			method: 'POST',
			failOnStatusCode: false,
			headers: {
				'content-type':
					'application/x-www-form-urlencoded; charset=UTF-8',
			},
			data: params.toString(),
		}
	);
	const payload = await response.json();

	if ( ! response.ok() || ! payload?.success ) {
		throw new Error(
			`WordPress AJAX request failed: ${ JSON.stringify( payload ) }`
		);
	}

	return payload.data;
}

async function postBootstrapAction( requestUtils, action, payload = {} ) {
	const params = new URLSearchParams();

	params.append( 'action', action );
	params.append( '_ajax_nonce', await getE2EBootstrapNonce( requestUtils ) );

	Object.entries( payload ).forEach( ( [ key, value ] ) => {
		appendAjaxValue( params, key, value );
	} );

	return postWpAjax( requestUtils, params );
}

async function resetE2EState( requestUtils ) {
	return postBootstrapAction( requestUtils, 'ppom_e2e_reset_state' );
}

export {
	getE2EBootstrapNonce,
	postBootstrapAction,
	postWpAjax,
	resetE2EState,
	uniqueSuffix,
};
