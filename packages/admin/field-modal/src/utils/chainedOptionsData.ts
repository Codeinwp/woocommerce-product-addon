/**
 * Pure data helpers for the Chained Options editor.
 */
export type ChainedOptionRow = {
	option: string;
	chained: string;
	features: string;
	id: string;
};

export function emptyChainedRow(): ChainedOptionRow {
	return {
		option: '',
		chained: '',
		features: '',
		id: '',
	};
}

function coerceChainedRow( item: unknown ): ChainedOptionRow {
	if ( ! item || typeof item !== 'object' || Array.isArray( item ) ) {
		return emptyChainedRow();
	}
	const row = item as Record< string, unknown >;
	return {
		option: String( row.option ?? '' ),
		chained: String( row.chained ?? '' ),
		features: String( row.features ?? '' ),
		id: String( row.id ?? '' ),
	};
}

export function normalizeChainedRows( raw: unknown ): ChainedOptionRow[] {
	if ( Array.isArray( raw ) ) {
		const rows = raw.map( coerceChainedRow );
		return rows.length > 0 ? rows : [ emptyChainedRow() ];
	}
	if ( raw && typeof raw === 'object' && ! Array.isArray( raw ) ) {
		const rows = Object.keys( raw as object ).map( ( key ) =>
			coerceChainedRow( ( raw as Record< string, unknown > )[ key ] )
		);
		return rows.length > 0 ? rows : [ emptyChainedRow() ];
	}
	return [ emptyChainedRow() ];
}

export function serializeChainedRows(
	rows: ChainedOptionRow[]
): ChainedOptionRow[] {
	const filtered = rows.filter(
		( row ) =>
			row.option.trim() !== '' ||
			row.chained.trim() !== '' ||
			row.features.trim() !== '' ||
			row.id.trim() !== ''
	);
	return filtered.length > 0 ? filtered : [];
}
