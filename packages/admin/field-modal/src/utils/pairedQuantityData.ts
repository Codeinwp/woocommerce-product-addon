/**
 * Pure data helpers for the Variation Quantity matrix editor (paired-quantity schema).
 */
export type QuantityOptionRow = {
	option: string;
	price: string;
	weight: string;
	defaultQty: string;
	min: string;
	max: string;
	stock: string;
};

export function emptyQuantityRow(): QuantityOptionRow {
	return {
		option: '',
		price: '',
		weight: '',
		defaultQty: '',
		min: '',
		max: '',
		stock: '',
	};
}

function coerceQuantityRow( item: unknown ): QuantityOptionRow {
	if ( ! item || typeof item !== 'object' || Array.isArray( item ) ) {
		return emptyQuantityRow();
	}
	const o = item as Record< string, unknown >;
	return {
		option: String( o.option ?? '' ),
		price: String( o.price ?? '' ),
		weight: String( o.weight ?? '' ),
		defaultQty: String( o.default ?? '' ),
		min: String( o.min ?? '' ),
		max: String( o.max ?? '' ),
		stock: String( o.stock ?? '' ),
	};
}

/** Normalize stored options (associative object or array) into editable rows. */
export function normalizePairedQuantityOptions(
	raw: unknown
): QuantityOptionRow[] {
	if ( raw == null || raw === '' ) {
		return [ emptyQuantityRow() ];
	}
	if ( Array.isArray( raw ) ) {
		if ( raw.length === 0 ) {
			return [ emptyQuantityRow() ];
		}
		return raw.map( coerceQuantityRow );
	}
	if ( typeof raw === 'object' ) {
		const rec = raw as Record< string, unknown >;
		const keys = Object.keys( rec ).sort(
			( a, b ) => Number( a ) - Number( b )
		);
		if ( keys.length === 0 ) {
			return [ emptyQuantityRow() ];
		}
		return keys.map( ( k ) => coerceQuantityRow( rec[ k ] ) );
	}
	return [ emptyQuantityRow() ];
}

/** Persist with PHP key `default` for default quantity. */
export function serializePairedQuantityOptions(
	rows: QuantityOptionRow[]
): unknown {
	const filtered = rows.filter(
		( r ) =>
			r.option.trim() !== '' ||
			r.price.trim() !== '' ||
			r.weight.trim() !== '' ||
			r.defaultQty.trim() !== '' ||
			r.min.trim() !== '' ||
			r.max.trim() !== '' ||
			r.stock.trim() !== ''
	);
	if ( filtered.length === 0 ) {
		return [];
	}
	return filtered.map( ( r ) => ( {
		option: r.option,
		price: r.price,
		weight: r.weight,
		default: r.defaultQty,
		min: r.min,
		max: r.max,
		stock: r.stock,
	} ) );
}
