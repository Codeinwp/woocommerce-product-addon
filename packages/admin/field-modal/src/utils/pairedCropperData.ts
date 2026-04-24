/**
 * Pure data helpers for the Image Cropper viewport editor (paired-cropper schema).
 */
export type CropperViewportRow = {
	option: string;
	width: string;
	height: string;
	price: string;
};

export function emptyCropperRow(): CropperViewportRow {
	return { option: '', width: '', height: '', price: '' };
}

function coerceCropperRow( item: unknown ): CropperViewportRow {
	if ( ! item || typeof item !== 'object' || Array.isArray( item ) ) {
		return emptyCropperRow();
	}
	const o = item as Record< string, unknown >;
	return {
		option: String( o.option ?? '' ),
		width: String( o.width ?? '' ),
		height: String( o.height ?? '' ),
		price: String( o.price ?? '' ),
	};
}

/** Normalize stored options (associative object or array) into editable rows. */
export function normalizePairedCropperOptions(
	raw: unknown
): CropperViewportRow[] {
	if ( raw == null || raw === '' ) {
		return [];
	}
	if ( Array.isArray( raw ) ) {
		if ( raw.length === 0 ) {
			return [];
		}
		return raw.map( coerceCropperRow );
	}
	if ( typeof raw === 'object' ) {
		const rec = raw as Record< string, unknown >;
		const keys = Object.keys( rec ).sort(
			( a, b ) => Number( a ) - Number( b )
		);
		if ( keys.length === 0 ) {
			return [];
		}
		return keys.map( ( k ) => coerceCropperRow( rec[ k ] ) );
	}
	return [];
}

/** Persist as a JSON array of row objects (matches PHP `array` options). */
export function serializePairedCropperOptions(
	rows: CropperViewportRow[]
): unknown {
	if ( rows.length === 0 ) {
		return [];
	}
	return rows.map( ( r ) => ( {
		option: r.option,
		width: r.width,
		height: r.height,
		price: r.price,
	} ) );
}
