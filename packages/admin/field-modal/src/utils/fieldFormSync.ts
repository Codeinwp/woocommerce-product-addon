/**
 * Bridge helpers for syncing FieldRow values into a TanStack Form store.
 */
import type { FieldFormApiLike, FieldRow } from '../types/fieldModal';

/** Stable JSON for comparing field rows regardless of key insertion order. */
export function stableStringifyFieldRow( value: unknown ): string {
	if ( value === null || typeof value !== 'object' ) {
		return JSON.stringify( value );
	}
	if ( Array.isArray( value ) ) {
		return (
			'[' +
			value
				.map( ( item ) => stableStringifyFieldRow( item ) )
				.join( ',' ) +
			']'
		);
	}
	const obj = value as Record< string, unknown >;
	const keys = Object.keys( obj ).sort();
	return (
		'{' +
		keys
			.map(
				( k ) =>
					JSON.stringify( k ) +
					':' +
					stableStringifyFieldRow( obj[ k ] )
			)
			.join( ',' ) +
		'}'
	);
}

export function applyFieldRowToForm(
	form: FieldFormApiLike,
	nextRow: FieldRow
): void {
	const currentRow = form.state.values as FieldRow;
	const keys = new Set( [
		...Object.keys( currentRow || {} ),
		...Object.keys( nextRow || {} ),
	] );

	keys.forEach( ( key ) => {
		const hasNext = Object.prototype.hasOwnProperty.call( nextRow, key );
		const currentValue = currentRow[ key ];
		const nextValue = hasNext ? nextRow[ key ] : undefined;
		if ( Object.is( currentValue, nextValue ) ) {
			return;
		}
		form.setFieldValue( key, nextValue );
	} );
}
