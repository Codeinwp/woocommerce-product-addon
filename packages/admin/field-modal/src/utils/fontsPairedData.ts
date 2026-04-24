/**
 * Pure data helpers for the Fonts Picker option editor.
 */
export type FontOptionRow = {
	fontfamily: string;
	fontdisplay: string;
	is_customfont: string;
};

export function emptyFontRow(): FontOptionRow {
	return {
		fontfamily: '',
		fontdisplay: '',
		is_customfont: '',
	};
}

function coerceFontRow( item: unknown ): FontOptionRow {
	if ( ! item || typeof item !== 'object' || Array.isArray( item ) ) {
		return emptyFontRow();
	}
	const row = item as Record< string, unknown >;
	return {
		fontfamily: String( row.fontfamily ?? '' ),
		fontdisplay: String( row.fontdisplay ?? '' ),
		is_customfont: String( row.is_customfont ?? '' ),
	};
}

export function normalizeFontRows( raw: unknown ): FontOptionRow[] {
	if ( Array.isArray( raw ) ) {
		const rows = raw.map( coerceFontRow );
		return rows.length > 0 ? rows : [ emptyFontRow() ];
	}
	if ( raw && typeof raw === 'object' && ! Array.isArray( raw ) ) {
		const entries = Object.keys( raw as object ).map( ( key ) =>
			coerceFontRow( ( raw as Record< string, unknown > )[ key ] )
		);
		return entries.length > 0 ? entries : [ emptyFontRow() ];
	}
	return [ emptyFontRow() ];
}

export function serializeFontRows( rows: FontOptionRow[] ): FontOptionRow[] {
	const filtered = rows.filter(
		( row ) =>
			row.fontfamily.trim() !== '' ||
			row.fontdisplay.trim() !== '' ||
			row.is_customfont === 'on'
	);
	return filtered.length > 0 ? filtered : [];
}
