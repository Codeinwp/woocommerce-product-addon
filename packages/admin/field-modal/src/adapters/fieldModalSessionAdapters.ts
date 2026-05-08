/**
 * Default side-effect adapters for the field modal session.
 */
import {
	bindPpomReactFieldModalOpenButtons,
	commitFieldToClassicForm,
	getClassicBuilderFields,
} from './wpAdminFieldModalAdapter';
import {
	fetchFieldModalContext,
	fetchFieldTypeSchema,
	saveFieldGroup,
} from '../services/fieldModalApi';
import type { FieldModalContextPayload, FieldRow } from '../types/fieldModal';
import type { FieldModalOpenPayload } from './wpAdminFieldModalAdapter';

export interface FieldModalTransportAdapter {
	fetchContext: (
		productmetaId: number | undefined
	) => Promise< FieldModalContextPayload >;
	fetchSchema: (
		type: string
	) => Promise< Record< string, unknown > | null >;
	saveGroup: ( args: {
		productmetaId: number | undefined;
		group: Record< string, unknown >;
		fields: FieldRow[];
	} ) => Promise< { redirect_to?: string } | void >;
}

export interface FieldModalAdminAdapter {
	bindOpenTriggers: ( opts: {
		onOpen: ( payload: FieldModalOpenPayload ) => void;
	} ) => () => void;
	openLegacyEditor: ( fieldIndex: number ) => void;
	getClassicBuilderFields: () => Array< Omit< FieldRow, 'clientId' > >;
	commitFieldToClassicForm: (
		field: Omit< FieldRow, 'clientId' >,
		options?: { insertAfterFieldIndex?: number }
	) => void;
}

export interface FieldModalNavigationAdapter {
	redirect: ( url: string ) => void;
	reload: () => void;
}

export interface FieldModalSessionAdapters {
	transport: FieldModalTransportAdapter;
	admin: FieldModalAdminAdapter;
	navigation: FieldModalNavigationAdapter;
}

export const defaultFieldModalSessionAdapters: FieldModalSessionAdapters = {
	transport: {
		fetchContext: async ( productmetaId ) => {
			return fetchFieldModalContext( productmetaId );
		},
		fetchSchema: fetchFieldTypeSchema,
		saveGroup: saveFieldGroup,
	},
	admin: {
		bindOpenTriggers: bindPpomReactFieldModalOpenButtons,
		getClassicBuilderFields,
		commitFieldToClassicForm,
		openLegacyEditor: ( fieldIndex ) => {
			if ( fieldIndex <= 0 ) {
				return;
			}
			const el = document.querySelector(
				`button.ppom-edit-field[id="${ String( fieldIndex ) }"]`
			);
			( el as HTMLElement | null )?.click();
		},
	},
	navigation: {
		redirect: ( url ) => window.location.assign( url ),
		reload: () => window.location.reload(),
	},
};
