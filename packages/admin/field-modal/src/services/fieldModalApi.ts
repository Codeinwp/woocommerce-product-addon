/**
 * REST transport for the React field modal (admin field groups).
 *
 * Only read endpoints are exposed here. Persistence still flows through
 * the classic builder's hidden form via `commitFieldToClassicForm`, which
 * the legacy `admin-ajax.php?action=ppom_save_form_meta` handler then
 * writes to the PPOM meta table — see this folder's AGENTS.md for the
 * compatibility rationale.
 */
import apiFetch from '@wordpress/api-fetch';
import type { FieldModalContextPayload } from '../types/fieldModal';

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
