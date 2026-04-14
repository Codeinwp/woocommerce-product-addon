/**
 * Stable client-side IDs for field rows in the modal (before save).
 */
import type { FieldRow } from '../types/fieldModal';

export function newClientId(): string {
	if ( typeof window !== 'undefined' && window.crypto && window.crypto.randomUUID ) {
		return window.crypto.randomUUID();
	}
	return `f-${ Date.now() }-${ Math.random().toString( 16 ).slice( 2 ) }`;
}

export function withClientIds( rows: FieldRow[] | undefined ): FieldRow[] {
	return ( rows || [] ).map( ( row ) => ( {
		...row,
		clientId: newClientId(),
	} ) );
}

export function stripClientIds( rows: FieldRow[] ): Omit< FieldRow, 'clientId' >[] {
	return rows.map( ( { clientId, ...rest } ) => rest );
}
