/**
 * Resolves which shell renders a field type: definition-driven, classic PHP, or unknown.
 */
import { hasFieldUiDefinition } from './registry';

/** Explicit opt-in to classic PHP modal only (starts empty per MIGRATION.md). */
export const legacyOnlySlugs = new Set< string >();

export type FieldModalRoute =
	| { kind: 'definition'; slug: string }
	| { kind: 'legacyPhp'; slug: string }
	| { kind: 'unknown'; slug: string };

/**
 * Current routing: definition-driven editor → classic PHP → unknown.
 */
export function resolveFieldModalRoute(
	slug: string | undefined
): FieldModalRoute {
	const s = slug ? String( slug ).toLowerCase() : '';
	if ( ! s ) {
		return { kind: 'unknown', slug: '' };
	}
	if ( hasFieldUiDefinition( s ) ) {
		return { kind: 'definition', slug: s };
	}
	if ( legacyOnlySlugs.has( s ) ) {
		return { kind: 'legacyPhp', slug: s };
	}
	return { kind: 'unknown', slug: s };
}
