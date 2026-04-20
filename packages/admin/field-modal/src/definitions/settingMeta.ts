/**
 * Typed accessors for `WidgetRenderContext.schema` — narrows the loose
 * `SchemaObject` (wire shape: `Record<string, unknown>`) into `SettingSchema` /
 * `SettingMeta` so widgets and editors can read setting metadata without
 * re-implementing the same `typeof x === 'object'` dance at every call site.
 */
import type { SchemaObject } from '../types/fieldModal';
import type { SettingMeta, SettingSchema } from './types';

function isPlainObject( v: unknown ): v is Record< string, unknown > {
	return typeof v === 'object' && v !== null && ! Array.isArray( v );
}

export function readSettingSchema(
	schema: SchemaObject | null | undefined
): SettingSchema {
	if ( ! isPlainObject( schema ) ) {
		return {};
	}
	const settings = ( schema as { settings?: unknown } ).settings;
	if ( ! isPlainObject( settings ) ) {
		return {};
	}
	return settings as SettingSchema;
}

export function readSettingMeta(
	schema: SchemaObject | null | undefined,
	key: string
): SettingMeta | undefined {
	const meta = readSettingSchema( schema )[ key ];
	return isPlainObject( meta ) ? ( meta as SettingMeta ) : undefined;
}

export function readSettingTitle(
	schema: SchemaObject | null | undefined,
	key: string,
	fallback: string
): string {
	const meta = readSettingMeta( schema, key );
	if ( meta && typeof meta.title === 'string' && meta.title.length > 0 ) {
		return meta.title;
	}
	return fallback;
}

export function readSettingDesc(
	schema: SchemaObject | null | undefined,
	key: string
): string {
	const meta = readSettingMeta( schema, key );
	return meta && typeof meta.desc === 'string' ? meta.desc : '';
}
