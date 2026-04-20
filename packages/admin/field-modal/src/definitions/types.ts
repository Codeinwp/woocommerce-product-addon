/**
 * Field UI definition contracts (layout + ownership). PHP schema supplies metadata only.
 */
import type { Dispatch, SetStateAction } from 'react';
import type { FieldRow, I18nDict, ModalContextValue, SchemaObject } from '../types/fieldModal';

export type FieldTabId = string;

export type FieldUiBlock =
	| {
			kind: 'section';
			id: string;
			tab: FieldTabId;
			labelKey: string;
			keys: string[];
	  }
	| {
			kind: 'widget';
			id: string;
			tab: FieldTabId;
			widget: string;
			ownedKeys: string[];
			props?: Record< string, unknown >;
	  };

export type ExplicitExclusion = {
	key: string;
	reason: string;
};

/**
 * Recognized `type` discriminators for `SettingMeta`. Extend this list when a new
 * setting-rendering type is introduced on the TS side. PHP-sourced schemas may still
 * carry unknown types — that's why the field is typed as `SettingMetaType | string`.
 */
export type SettingMetaType =
	| 'text'
	| 'textarea'
	| 'checkbox'
	| 'select'
	| 'color'
	| 'html-conditions'
	| 'paired'
	| 'paired-switch'
	| 'paired-quantity'
	| 'chained_options'
	| 'fonts_paired'
	| 'conditional-images'
	| 'vqmatrix-colunm'
	| 'vqmatrix-row';

/**
 * One setting's metadata entry. `type` selects the renderer; the rest are UI hints.
 * An index signature is kept so PHP-sourced schemas with extra fields pass through.
 */
export interface SettingMeta {
	type: SettingMetaType | string;
	title: string;
	desc?: string;
	default?: unknown;
	options?: Record< string, string > | Record< number, string >;
	col_classes?: string[];
	link?: string;
	hidden?: boolean;
	[ key: string ]: unknown;
}

export type SettingSchema = Record< string, SettingMeta >;

export type ValidatorFn = ( ctx: {
	value: unknown;
	activeClientId: string;
	field: FieldRow;
	builderFields: FieldRow[];
	mergedBuilderFields: FieldRow[];
} ) => string | void;

export interface FieldUiDefinition {
	slug: string;
	tabs: Array< {
		id: FieldTabId;
		labelKey: string;
	} >;
	blocks: FieldUiBlock[];
	settings?: SettingSchema;
	exclusions?: ExplicitExclusion[];
	clientValidators?: Record<
		string,
		{
			onChange?: ValidatorFn[];
			onBlur?: ValidatorFn[];
			onSubmit?: ValidatorFn[];
		}
	>;
}

export interface WidgetRenderContext {
	activeClientId: string;
	field: FieldRow;
	builderFields: FieldRow[];
	mergedBuilderFields: FieldRow[];
	schema: SchemaObject | null;
	i18n: I18nDict;
	ppomFieldIndex: number;
	modalContext: ModalContextValue | null;
	updateField: Dispatch< SetStateAction< FieldRow | null > >;
	/** Props from `FieldUiBlock` when `kind === 'widget'`. */
	widgetProps?: Record< string, unknown >;
}
