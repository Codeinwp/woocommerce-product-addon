/**
 * Typed registry for definition-driven complex field widgets (`FieldUiBlock` kind `widget`).
 *
 * Shape modeled on TanStack Table column defs and Tiptap extensions: each widget is a
 * small, self-describing unit — id, render, optional defaults/validators — rather than a
 * bespoke editor component. This is the "Lego brick" surface: definitions reference a
 * widget by id via `{ kind: 'widget', widget: '…' }`, the shell resolves and renders it.
 */
import type { ReactNode } from 'react';
import type { ValidatorFn, WidgetRenderContext } from '../definitions/types';

export type FieldWidgetRenderer = (
	ctx: WidgetRenderContext
) => ReactNode | null;

export interface FieldWidget {
	id: string;
	render: FieldWidgetRenderer;
	/** Keys on the field row that this widget owns (for schema-exclusion parity with definition `ownedKeys`). */
	ownedKeys?: string[];
	/** Optional defaults applied when a field of this type is first created. */
	defaults?: Record< string, unknown >;
	/** Optional client validators keyed by the owned field-row key they apply to. */
	clientValidators?: Record<
		string,
		{
			onChange?: ValidatorFn[];
			onBlur?: ValidatorFn[];
			onSubmit?: ValidatorFn[];
		}
	>;
}

const widgets = new Map< string, FieldWidget >();

function normalizeId( id: string | undefined | null ): string {
	return String( id || '' ).toLowerCase();
}

/**
 * Register a widget. Accepts either a full `FieldWidget` object (preferred) or a
 * `(ctx) => ReactNode` render function for back-compat with the initial shape.
 */
export function registerFieldWidget(
	idOrWidget: string | FieldWidget,
	render?: FieldWidgetRenderer
): void {
	if ( typeof idOrWidget === 'object' && idOrWidget !== null ) {
		const widget = idOrWidget;
		widgets.set( normalizeId( widget.id ), widget );
		return;
	}
	if ( typeof render !== 'function' ) {
		return;
	}
	widgets.set( normalizeId( idOrWidget ), {
		id: String( idOrWidget ),
		render,
	} );
}

export function getFieldWidget( id: string ): FieldWidget | undefined {
	return widgets.get( normalizeId( id ) );
}

export function renderFieldWidget(
	id: string,
	ctx: WidgetRenderContext
): ReactNode | null {
	const widget = widgets.get( normalizeId( id ) );
	return widget ? widget.render( ctx ) : null;
}
