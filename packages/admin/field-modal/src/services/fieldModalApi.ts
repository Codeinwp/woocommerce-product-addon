/**
 * REST transport for the React field modal (admin field groups).
 */
import apiFetch from '@wordpress/api-fetch';
import type { FieldModalContextPayload } from '../types/fieldModal';
import { stripClientIds } from '../utils/clientIds';
import type { FieldRow } from '../types/fieldModal';

export async function fetchFieldModalContext(
	productmetaId: number | undefined
): Promise< FieldModalContextPayload > {
	const res = ( await apiFetch( {
		path: `ppom/v1/admin/field-groups/context?productmeta_id=${ encodeURIComponent(
			String( productmetaId ?? 0 )
		) }`,
	} ) ) as FieldModalContextPayload;
	return res;
}

export async function fetchFieldTypeSchema(
	type: string
): Promise< Record< string, unknown > | null > {
	const t = String( type ).toLowerCase();
	const res = ( await apiFetch( {
		path: `ppom/v1/admin/field-groups/schema/${ encodeURIComponent( t ) }`,
	} ) ) as { schema?: Record< string, unknown > };
	const schema = res && res.schema ? res.schema : null;
	return schema;
}

export async function saveFieldGroup( args: {
	productmetaId: number | undefined;
	group: Record< string, unknown >;
	fields: FieldRow[];
} ): Promise< { redirect_to?: string } | void > {
	const { productmetaId, group, fields } = args;
	const payload = {
		group,
		fields: stripClientIds( fields ),
	};
	if ( ( productmetaId ?? 0 ) > 0 ) {
		await apiFetch( {
			path: `ppom/v1/admin/field-groups/${ productmetaId }`,
			method: 'PUT',
			data: payload,
		} );
		return;
	}
	const res = ( await apiFetch( {
		path: 'ppom/v1/admin/field-groups',
		method: 'POST',
		data: payload,
	} ) ) as { redirect_to?: string };
	return res;
}
