/**
 * Pure helpers for the Conditional Field Repeater (Pro Plus) editor.
 */
import type { FieldRow } from '../types/fieldModal';

const ORIGIN_TYPES = new Set( [ 'number', 'quantities', 'qtypack' ] );

export interface OriginCandidate {
	value: string;
	title: string;
	type: string;
}

export function getCfrOriginCandidates(
	builderFields: FieldRow[],
	currentDataName: string
): OriginCandidate[] {
	const seen = new Set< string >();
	const out: OriginCandidate[] = [];
	const current = String( currentDataName || '' ).trim();
	for ( const f of builderFields ) {
		const t = String( f.type || '' ).toLowerCase();
		const dn = String( f.data_name || '' ).trim();
		if ( ! ORIGIN_TYPES.has( t ) || ! dn ) {
			continue;
		}
		if ( dn === current ) {
			continue;
		}
		if ( seen.has( dn ) ) {
			continue;
		}
		seen.add( dn );
		out.push( {
			value: dn,
			title: String( f.title || dn ),
			type: t,
		} );
	}
	return out;
}

export function readRepeaterForm(
	values: FieldRow
): Record< string, unknown > {
	const raw = values.cond_field_repeater;
	if ( raw && typeof raw === 'object' && ! Array.isArray( raw ) ) {
		return { ...( raw as Record< string, unknown > ) };
	}
	return {};
}
