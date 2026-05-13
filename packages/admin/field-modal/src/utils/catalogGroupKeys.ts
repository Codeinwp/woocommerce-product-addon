import type { CatalogGroup } from '../types/fieldModal';

/**
 * Stable React list key for catalog groups (REST may omit or duplicate `id`).
 */
export function catalogGroupStableKey(
	group: CatalogGroup,
	index: number
): string {
	if (
		group.id !== undefined &&
		group.id !== null &&
		String( group.id ).length > 0
	) {
		return String( group.id );
	}
	return `${ String( group.label ?? group.title ?? 'group' ) }-${ index }`;
}
