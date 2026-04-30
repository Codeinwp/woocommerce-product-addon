/**
 * Shared types for the admin field modal (REST payloads and UI state).
 */
import type { ComponentType, Dispatch, SetStateAction } from 'react';

/** Static UI strings owned by the React modal. */
export type I18nDict = Record< string, string >;

/** One field row in the modal; server may omit `clientId` until `withClientIds` runs. */
export type FieldRow = Record< string, unknown > & {
	clientId: string;
	type?: string;
	title?: string;
	data_name?: string;
	status?: string;
};

export interface CatalogItem {
	slug: string;
	title?: string;
	locked?: boolean;
	description?: string;
	icon?: string;
	[ key: string ]: unknown;
}

export interface CatalogGroup {
	id?: string | number;
	label?: string;
	title?: string;
	fields?: CatalogItem[];
	[ key: string ]: unknown;
}

/** REST upsell payload for field type picker sidebar. */
export interface ModalUpsellPayload {
	title?: string;
	intro?: string;
	features?: string[];
	cta_url?: string;
	cta_label?: string;
	[ key: string ]: unknown;
}

export type LicensePayload = Record< string, unknown >;

/** Runtime URLs supplied by PHP because they may be filtered or license-aware. */
export interface FieldModalLinks {
	cfrDocsUrl?: string;
	cfrUpgradeUrl?: string;
	cfrViewDemoUrl?: string;
	conditionUpgradeUrl?: string;
}

/** `GET ppom/v1/admin/field-groups/context` response. */
export interface FieldModalContextPayload {
	fields?: FieldRow[];
	catalog?: CatalogItem[];
	catalog_groups?: CatalogGroup[];
	group?: Record< string, unknown >;
	conditions_pro_enabled?: boolean;
	conditional_repeater_unlocked?: boolean;
	conditional_repeater_show_upsell?: boolean;
	upsell?: ModalUpsellPayload | null;
	license?: LicensePayload | null;
	links?: FieldModalLinks;
}

export interface ModalContextValue {
	builderFields: FieldRow[];
	conditionsProEnabled: boolean;
	conditionalRepeaterUnlocked: boolean;
	conditionalRepeaterShowUpsell: boolean;
	links: FieldModalLinks;
}

export interface FieldFormApiLike {
	AppField: ComponentType< any >;
	setFieldValue: (
		field: string,
		updater: unknown,
		opts?: { dontValidate?: boolean }
	) => void;
	state: {
		values: FieldRow;
	};
	reset: ( values?: FieldRow ) => void;
}

export type SchemaObject = Record< string, unknown >;

/** Props for typed field editors. */
export interface FieldEditorBaseProps {
	schema?: SchemaObject | null;
	values: FieldRow;
	onChange: Dispatch< SetStateAction< FieldRow | null > >;
	i18n: I18nDict;
	ppomFieldIndex: number;
	fieldType?: string;
	modalContext?: ModalContextValue | null;
	form?: FieldFormApiLike;
}

export type FieldEditorComponent = ComponentType< FieldEditorBaseProps >;

export interface GroupedFieldSectionsProps extends FieldEditorBaseProps {
	sections: Array< { label: string; keys: string[] } >;
	variant?: 'sectioned' | 'flat';
}

export interface SettingRowContext {
	values: FieldRow;
	onChange: Dispatch< SetStateAction< FieldRow | null > >;
	i18n: I18nDict;
	ppomFieldIndex: number;
	form?: FieldFormApiLike;
	builderFields?: FieldRow[];
	conditionsProEnabled?: boolean;
	conditionalRepeaterUnlocked?: boolean;
	conditionalRepeaterShowUpsell?: boolean;
	links?: FieldModalLinks;
}

export interface PpomFieldModalBoot {
	nonce: string;
	productmetaId?: number;
}

/** Loading / error surface for the modal body shell. */
export interface FieldModalBodyStatus {
	loading: boolean;
	error: string;
}

/** Picker step: passed through to `FieldPickerPanel`. */
export interface FieldModalPickerStepProps {
	i18n: I18nDict;
	catalogGroups: CatalogGroup[];
	pickerQuery: string;
	onPickerQueryChange: ( q: string ) => void;
	onPickType: ( slug: string ) => void;
	upsell?: ModalUpsellPayload | null;
	license?: LicensePayload | null;
}

/** Manage step: passed through to `FieldManagePanel`. */
export interface FieldModalManageStepProps {
	i18n: I18nDict;
	fields: FieldRow[];
	selectedId: string | null;
	editDraft: FieldRow | null;
	schemaLoading: boolean;
	/** Set when lazy `GET .../schema/{type}` fails or returns no schema. */
	schemaFetchError?: string;
	activeSchema: SchemaObject | null;
	TypedEditor: FieldEditorComponent | null;
	onEditDraftChange: Dispatch< SetStateAction< FieldRow | null > >;
	ppomFieldIndex: number;
	modalContext: ModalContextValue;
	onOpenPicker: () => void;
}

export interface FieldModalBodyProps {
	status: FieldModalBodyStatus;
	onDismissError: () => void;
	ctx: FieldModalContextPayload | null;
	pickerOpen: boolean;
	picker: FieldModalPickerStepProps;
	manage: FieldModalManageStepProps;
}
