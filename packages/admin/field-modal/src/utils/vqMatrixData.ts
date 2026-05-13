/**
 * Pure data helpers for the Variation Matrix editor (priced columns + matrix rows).
 */
export type MatrixColumnRow = {
	option: string;
	img_id: string;
	price: string;
	min: string;
	max: string;
	option_id: string;
};

export type MatrixRowRow = {
	option: string;
	img_id: string;
	option_id: string;
};

export function emptyColumnRow(): MatrixColumnRow {
	return {
		option: '',
		img_id: '',
		price: '',
		min: '',
		max: '',
		option_id: '',
	};
}

export function emptyRowRow(): MatrixRowRow {
	return { option: '', img_id: '', option_id: '' };
}

function coerceColumnRow( item: unknown ): MatrixColumnRow {
	if ( ! item || typeof item !== 'object' || Array.isArray( item ) ) {
		return emptyColumnRow();
	}
	const row = item as Record< string, unknown >;
	return {
		option: String( row.option ?? '' ),
		img_id: String( row.img_id ?? '' ),
		price: String( row.price ?? '' ),
		min: String( row.min ?? '' ),
		max: String( row.max ?? '' ),
		option_id: String( row.option_id ?? '' ),
	};
}

function coerceRowRow( item: unknown ): MatrixRowRow {
	if ( ! item || typeof item !== 'object' || Array.isArray( item ) ) {
		return emptyRowRow();
	}
	const row = item as Record< string, unknown >;
	return {
		option: String( row.option ?? '' ),
		img_id: String( row.img_id ?? '' ),
		option_id: String( row.option_id ?? '' ),
	};
}

export function normalizeColumnRows( raw: unknown ): MatrixColumnRow[] {
	if ( Array.isArray( raw ) ) {
		const rows = raw.map( coerceColumnRow );
		return rows.length > 0 ? rows : [ emptyColumnRow() ];
	}
	if ( raw && typeof raw === 'object' && ! Array.isArray( raw ) ) {
		const rows = Object.keys( raw as object ).map( ( key ) =>
			coerceColumnRow( ( raw as Record< string, unknown > )[ key ] )
		);
		return rows.length > 0 ? rows : [ emptyColumnRow() ];
	}
	return [ emptyColumnRow() ];
}

export function normalizeRowRows( raw: unknown ): MatrixRowRow[] {
	if ( Array.isArray( raw ) ) {
		const rows = raw.map( coerceRowRow );
		return rows.length > 0 ? rows : [ emptyRowRow() ];
	}
	if ( raw && typeof raw === 'object' && ! Array.isArray( raw ) ) {
		const rows = Object.keys( raw as object ).map( ( key ) =>
			coerceRowRow( ( raw as Record< string, unknown > )[ key ] )
		);
		return rows.length > 0 ? rows : [ emptyRowRow() ];
	}
	return [ emptyRowRow() ];
}

export function serializeColumnRows(
	rows: MatrixColumnRow[]
): MatrixColumnRow[] {
	return rows.length > 0 ? rows : [];
}

export function serializeRowRows( rows: MatrixRowRow[] ): MatrixRowRow[] {
	return rows.length > 0 ? rows : [];
}
