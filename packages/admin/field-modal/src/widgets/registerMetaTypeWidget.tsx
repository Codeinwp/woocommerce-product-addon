/**
 * Thin declarative wrapper around `registerFieldWidget` for the common case:
 * "render editor X when `schema.settings[key].type === 'Y'`". Handles the
 * narrow-and-read boilerplate so widget registrations stay one-liner-shaped.
 */
import type { ReactNode } from 'react';
import type { I18nDict } from '../types/fieldModal';
import type { SettingMeta, WidgetRenderContext } from '../definitions/types';
import { readSettingMeta, readSettingTitle } from '../definitions/settingMeta';
import { registerFieldWidget } from './registry';

export interface MetaTypeWidgetRenderArgs {
	ctx: WidgetRenderContext;
	title: string;
	description: string;
	meta: SettingMeta;
	metaKey: string;
}

export interface MetaTypeWidgetSpec {
	id: string;
	ownedKeys: string[];
	/** Fixed schema key, or a resolver reading `ctx.widgetProps` (e.g. `fieldKey`). */
	metaKey: string | ( ( ctx: WidgetRenderContext ) => string );
	/** `meta.type` value that triggers the render. Dispatch returns null otherwise. */
	metaType: SettingMeta[ 'type' ];
	/** Fallback title when the schema carries none. */
	titleFallback: ( i18n: I18nDict, ctx: WidgetRenderContext ) => string;
	/** Optional second guard (e.g. vqmatrix needs a sibling `row_options` check). */
	extraGuard?: ( ctx: WidgetRenderContext ) => boolean;
	render: ( args: MetaTypeWidgetRenderArgs ) => ReactNode;
}

export function registerMetaTypeWidget( spec: MetaTypeWidgetSpec ): void {
	registerFieldWidget( {
		id: spec.id,
		ownedKeys: spec.ownedKeys,
		render: ( ctx ) => {
			const metaKey =
				typeof spec.metaKey === 'function'
					? spec.metaKey( ctx )
					: spec.metaKey;
			const meta = readSettingMeta( ctx.schema, metaKey );
			if ( ! meta || meta.type !== spec.metaType ) {
				return null;
			}
			if ( spec.extraGuard && ! spec.extraGuard( ctx ) ) {
				return null;
			}
			const title = readSettingTitle(
				ctx.schema,
				metaKey,
				spec.titleFallback( ctx.i18n, ctx )
			);
			const description = typeof meta.desc === 'string' ? meta.desc : '';
			return spec.render( { ctx, title, description, meta, metaKey } );
		},
	} );
}
