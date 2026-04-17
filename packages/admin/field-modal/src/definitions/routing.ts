/**
 * Resolves which shell renders a field type: definition-driven, legacy React, classic PHP, or unknown.
 */
import { getFieldEditor } from '../editors/registry';
import { hasFieldUiDefinition } from './registry';

/** Explicit opt-in to classic PHP modal only (starts empty per MIGRATION.md). */
export const legacyOnlySlugs = new Set< string >();

export type FieldModalRoute =
	| { kind: 'definition'; slug: string }
	| { kind: 'legacyReact'; slug: string }
	| { kind: 'legacyPhp'; slug: string }
	| { kind: 'unknown'; slug: string };

/**
 * Transitional routing (Phases 0–5): definition → legacy React editor → classic PHP → unknown.
 */
export function resolveFieldModalRoute(
	slug: string | undefined,
	opts?: { transitionalLegacyReact?: boolean }
): FieldModalRoute {
	const transitional = opts?.transitionalLegacyReact !== false;
	const s = slug ? String( slug ).toLowerCase() : '';
	if ( ! s ) {
		return { kind: 'unknown', slug: '' };
	}
	if ( hasFieldUiDefinition( s ) ) {
		return { kind: 'definition', slug: s };
	}
	if ( transitional && getFieldEditor( s ) ) {
		return { kind: 'legacyReact', slug: s };
	}
	if ( legacyOnlySlugs.has( s ) ) {
		return { kind: 'legacyPhp', slug: s };
	}
	return { kind: 'unknown', slug: s };
}
