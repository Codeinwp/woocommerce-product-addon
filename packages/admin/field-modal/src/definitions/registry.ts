/**
 * Registry of `FieldUiDefinition` entries keyed by field type slug.
 */
import type { FieldUiDefinition } from './types';

const definitions = new Map< string, FieldUiDefinition >();

export function registerFieldUiDefinition( def: FieldUiDefinition ): void {
	definitions.set( String( def.slug || '' ).toLowerCase(), def );
}

export function getFieldUiDefinition(
	slug: string | undefined
): FieldUiDefinition | undefined {
	if ( ! slug ) {
		return undefined;
	}
	return definitions.get( String( slug ).toLowerCase() );
}

export function hasFieldUiDefinition( slug: string | undefined ): boolean {
	return getFieldUiDefinition( slug ) !== undefined;
}

export function getRegisteredFieldUiSlugs(): string[] {
	return [ ...definitions.keys() ].sort();
}
