const COMBINING_MARKS = /[̀-ͯ]/g;

/**
 * Convert a human title into a `data_name`-safe identifier.
 *
 * Lowercase ASCII letters/digits and underscores only, with diacritics
 * stripped and repeats collapsed. Mirrors the legacy admin behavior.
 */
export function slugifyDataName( title: string ): string {
	return String( title )
		.normalize( 'NFKD' )
		.replace( COMBINING_MARKS, '' )
		.toLowerCase()
		.replace( /[^a-z0-9]+/g, '_' )
		.replace( /__+/g, '_' )
		.replace( /^_+|_+$/g, '' );
}
