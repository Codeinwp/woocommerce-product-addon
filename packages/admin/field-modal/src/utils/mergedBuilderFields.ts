/**
 * Full builder graph read model: canonical `fields[]` with the active row replaced by live form values.
 *
 * @see MIGRATION.md — read model split (mounted row vs siblings).
 */
import type { FieldRow } from '../types/fieldModal';

/**
 * @param builderFields Reducer-owned canonical field rows (siblings + active snapshot).
 * @param activeClientId Stable row id from `clientId`.
 * @param activeRow Live values for the mounted row (typically TanStack Form `values`).
 */
export function mergeBuilderFieldsWithActive(
	builderFields: FieldRow[],
	activeClientId: string,
	activeRow: FieldRow | null
): FieldRow[] {
	if ( ! activeRow ) {
		return builderFields;
	}
	return builderFields.map( ( f ) =>
		f.clientId === activeClientId ? { ...activeRow } : f
	);
}
