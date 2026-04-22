export function errorMessage( e: unknown ): string {
	if ( e && typeof e === 'object' ) {
		const data =
			'data' in e ? ( e as { data?: unknown } ).data : null;
		if (
			data &&
			typeof data === 'object' &&
			'errors' in data &&
			Array.isArray( data.errors ) &&
			data.errors.length > 0
		) {
			return data.errors.join( ' ' );
		}
	}

	if ( e instanceof Error ) {
		return e.message;
	}
	return String( e );
}
