/**
 * Pure data helpers for the ImagesSelect editor (image / imageselect / conditional-meta variants).
 */
export type ImageOptionRow = {
	link: string;
	id: string | number;
	title: string;
	price: string;
	stock: string;
	meta_id?: string;
	url?: string;
	description?: string;
};

export type ImagesSelectVariant = 'image' | 'imageselect' | 'conditional-meta';

export function emptyImageRow(): ImageOptionRow {
	return {
		link: '',
		id: '',
		title: '',
		price: '',
		stock: '',
		meta_id: '',
		url: '',
		description: '',
	};
}

function coerceImageRow( item: unknown ): ImageOptionRow {
	if ( ! item || typeof item !== 'object' || Array.isArray( item ) ) {
		return emptyImageRow();
	}
	const o = item as Record< string, unknown >;
	return {
		link: String( o.link ?? '' ),
		id: String( o.id ?? '' ),
		title: String( o.title ?? '' ),
		price: String( o.price ?? '' ),
		stock: String( o.stock ?? '' ),
		meta_id: String( o.meta_id ?? '' ),
		url: String( o.url ?? '' ),
		description: String( o.description ?? '' ),
	};
}

export function normalizeImagesArray( raw: unknown ): ImageOptionRow[] {
	if ( Array.isArray( raw ) ) {
		return raw.map( coerceImageRow );
	}
	if ( raw && typeof raw === 'object' && ! Array.isArray( raw ) ) {
		return Object.keys( raw as object ).map( ( k ) => {
			const v = ( raw as Record< string, unknown > )[ k ];
			if ( v && typeof v === 'object' && ! Array.isArray( v ) ) {
				return coerceImageRow( v );
			}
			return { ...emptyImageRow(), link: k };
		} );
	}
	return [];
}
