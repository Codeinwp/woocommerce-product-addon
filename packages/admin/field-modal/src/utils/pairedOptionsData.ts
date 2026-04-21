/**
 * Pure data helpers for paired-options editors (select, radio, checkbox, switcher variants).
 */
export type PairedOptionRow = Record< string, unknown >;

export type PairedOptionsVariant =
	| 'select'
	| 'radio'
	| 'checkbox'
	| 'switcher';

export function normalizePairedOptionsArray(
	raw: unknown
): PairedOptionRow[] {
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

export function emptyPairedOptionRow(
	variant: PairedOptionsVariant
): PairedOptionRow {
	const base: PairedOptionRow = {
		option: '',
		price: '',
		weight: '',
		stock: '',
		id: '',
	};
	if ( variant === 'checkbox' ) {
		base.discount = '';
		base.tooltip = '';
	}
	if ( variant === 'switcher' ) {
		base.image = '';
	}
	return base;
}
