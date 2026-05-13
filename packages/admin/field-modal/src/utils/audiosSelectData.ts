/**
 * Pure data helpers for the AudiosSelect (audio + video media) editor.
 */
export type AudioOptionRow = {
	link: string;
	id: string;
	title: string;
	price: string;
};

export function emptyAudioRow(): AudioOptionRow {
	return { link: '', id: '', title: '', price: '' };
}

function coerceAudioRow( item: unknown ): AudioOptionRow {
	if ( ! item || typeof item !== 'object' || Array.isArray( item ) ) {
		return emptyAudioRow();
	}
	const o = item as Record< string, unknown >;
	return {
		link: String( o.link ?? '' ),
		id: String( o.id ?? '' ),
		title: String( o.title ?? '' ),
		price: String( o.price ?? '' ),
	};
}

export function normalizeAudioArray( raw: unknown ): AudioOptionRow[] {
	if ( Array.isArray( raw ) ) {
		return raw.map( coerceAudioRow );
	}
	if ( raw && typeof raw === 'object' && ! Array.isArray( raw ) ) {
		return Object.keys( raw as object ).map( ( k ) => {
			const v = ( raw as Record< string, unknown > )[ k ];
			if ( v && typeof v === 'object' && ! Array.isArray( v ) ) {
				return coerceAudioRow( v );
			}
			return { link: k, id: '', title: '', price: '' };
		} );
	}
	return [];
}
