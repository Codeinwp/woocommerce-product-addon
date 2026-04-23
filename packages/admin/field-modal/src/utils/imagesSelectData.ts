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

export type PriceType = 'fixed' | 'percent';

/** Price strings serialize the type as a trailing `%`. */
export function parsePriceType( price: string ): PriceType {
	return price.trim().endsWith( '%' ) ? 'percent' : 'fixed';
}

/** Numeric portion of the price, without any `%` suffix or surrounding whitespace. */
export function parsePriceAmount( price: string ): string {
	const trimmed = price.trim();
	return trimmed.endsWith( '%' ) ? trimmed.slice( 0, -1 ).trim() : trimmed;
}

/**
 * Rejoin an amount and a price type into the serialized `price` string.
 * An empty percent price is stored as `"%"` so the type survives re-parse
 * even before the user enters a number.
 */
export function formatPrice( amount: string, type: PriceType ): string {
	const clean = amount.trim();
	if ( type === 'percent' ) {
		return `${ clean }%`;
	}
	return clean;
}

/** Last path segment of an image URL, usable as a filename hint under the title. */
export function filenameFromUrl( url: string ): string {
	if ( ! url ) {
		return '';
	}
	try {
		const parsed = new URL( url, 'http://ppom.invalid' );
		const last = parsed.pathname.split( '/' ).filter( Boolean ).pop();
		return last ? decodeURIComponent( last ) : '';
	} catch {
		const parts = url.split( '/' ).filter( Boolean );
		return parts[ parts.length - 1 ] || '';
	}
}
