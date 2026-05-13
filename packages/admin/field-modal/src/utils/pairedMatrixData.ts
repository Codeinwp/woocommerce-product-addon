/**
 * Pure data helpers for the paired-palettes / paired-pricematrix editor.
 */
export type MatrixOptionRow = Record< string, unknown >;

export function normalizeMatrixOptions( raw: unknown ): MatrixOptionRow[] {
	if ( Array.isArray( raw ) ) {
		return raw.map( ( o ) =>
			o && typeof o === 'object' && ! Array.isArray( o )
				? { ...( o as Record< string, unknown > ) }
				: {}
		);
	}
	if ( raw && typeof raw === 'object' && ! Array.isArray( raw ) ) {
		return Object.keys( raw as object ).map( ( k ) => {
			const v = ( raw as Record< string, unknown > )[ k ];
			if ( v && typeof v === 'object' && ! Array.isArray( v ) ) {
				return { ...( v as Record< string, unknown > ) };
			}
			return { option: k };
		} );
	}
	return [];
}

export function isMatrixFixedChecked( row: MatrixOptionRow ): boolean {
	const v = row.isfixed;
	return v === 'on' || v === true || v === '1' || v === 1;
}

export function emptyMatrixRow(): MatrixOptionRow {
	return {
		option: '',
		price: '',
		label: '',
		id: '',
		isfixed: '',
	};
}
