/**
 * Pure data helpers for the Bulk Quantity matrix editor.
 */
export type BulkQuantityRow = Record< string, string >;

export function parseOptionsRaw( raw: unknown ): BulkQuantityRow[] {
	if ( raw == null || raw === '' ) {
		return [];
	}
	if ( typeof raw === 'string' ) {
		try {
			const p = JSON.parse( raw ) as unknown;
			return parseOptionsRaw( p );
		} catch {
			return [];
		}
	}
	if ( Array.isArray( raw ) ) {
		return raw.map( ( row ) => {
			if ( ! row || typeof row !== 'object' || Array.isArray( row ) ) {
				return {};
			}
			const o: BulkQuantityRow = {};
			for ( const [ k, v ] of Object.entries( row ) ) {
				o[ k ] = v == null ? '' : String( v );
			}
			return o;
		} );
	}
	return [];
}

export function ensureRowKeys(
	row: BulkQuantityRow,
	columns: string[]
): BulkQuantityRow {
	const out: BulkQuantityRow = {};
	for ( const c of columns ) {
		out[ c ] = row[ c ] ?? '';
	}
	return out;
}

export function emptyBulkRow( columns: string[] ): BulkQuantityRow {
	const empty: BulkQuantityRow = {};
	for ( const c of columns ) {
		empty[ c ] = '';
	}
	return empty;
}
