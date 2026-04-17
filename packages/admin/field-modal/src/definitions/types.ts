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
