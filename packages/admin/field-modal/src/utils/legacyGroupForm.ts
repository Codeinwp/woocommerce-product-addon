/**
 * Read group meta from the classic PPOM save form (same page as the React modal).
 */
export function readGroupFromForm( baseGroup: Record<string, unknown> ): Record<string, unknown> {
	const form = document.querySelector( 'form.ppom-save-fields-meta' ) as HTMLFormElement | null;
	const next = { ...baseGroup };
	if ( ! form || ! form.elements ) {
		return next;
	}
	const get = ( name: string ) => {
		const el = form.elements.namedItem( name );
		if ( ! el || ! ( 'value' in el ) ) {
			return '';
		}
		return String( ( el as { value: string } ).value );
	};
	if ( get( 'productmeta_name' ) ) {
		next.productmeta_name = get( 'productmeta_name' );
	}
	const dph = get( 'dynamic_price_hide' );
	if ( dph ) {
		next.dynamic_price_display = dph;
	}
	const style = get( 'productmeta_style' );
	if ( style !== undefined && style !== '' ) {
		next.productmeta_style = style;
	}
	return next;
}
