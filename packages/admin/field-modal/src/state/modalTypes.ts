/**
 * Reducer-owned modal state for the PPOM React field modal.
 */
import type { FieldModalContextPayload, FieldRow } from '../types/fieldModal';

export interface ModalReducerState {
	open: boolean;
	pickerOpen: boolean;
	pickerQuery: string;
	loading: boolean;
	saving: boolean;
	error: string;
	ctx: FieldModalContextPayload | null;
	fields: FieldRow[];
	cleanFieldSnapshots: Record< string, string >;
	persistedClientIds: string[];
	dirtyClientIds: string[];
	removedPersistedClientIds: string[];
	selectedId: string | null;
	schemasCache: Record< string, Record< string, unknown > >;
	schemaLoading: boolean;
	/** Lazy REST schema fetch failed for the current field type (distinct from global `error`). */
	schemaFetchError: string;
	modalEntry: 'picker' | 'manage';
}

export type ModalReducerAction =
	| { type: 'OPEN'; entry: 'picker' | 'manage' }
	| { type: 'CLOSE' }
	| { type: 'SET_PICKER_OPEN'; open: boolean }
	| { type: 'SET_PICKER_QUERY'; query: string }
	| { type: 'LOAD_CONTEXT_START' }
	| {
			type: 'LOAD_CONTEXT_SUCCESS';
			ctx: FieldModalContextPayload;
			fields: FieldRow[];
			cleanFieldSnapshots: Record< string, string >;
			selectedId: string | null;
	  }
	| { type: 'LOAD_CONTEXT_ERROR'; message: string }
	| { type: 'CLEAR_ERROR' }
	| { type: 'SET_SCHEMA_FETCH_ERROR'; message: string }
	| {
			type: 'SET_SCHEMA_FOR_TYPE';
			typeKey: string;
			schema: Record< string, unknown > | null;
	  }
	| { type: 'SET_SCHEMA_LOADING'; loading: boolean }
	| { type: 'SET_CTX'; ctx: FieldModalContextPayload | null }
	| { type: 'SET_FIELDS'; fields: FieldRow[] }
	| { type: 'SET_SELECTED_ID'; id: string | null }
	| { type: 'PATCH_FIELD_ROW_FROM_FORM'; row: FieldRow; snapshot: string }
	| {
			type: 'ADD_FIELD_ROW';
			row: FieldRow;
			snapshot: string;
	  }
	| { type: 'REMOVE_FIELD_ROW'; clientId: string }
	| { type: 'SET_SAVING'; saving: boolean }
	| { type: 'SET_MODAL_ENTRY'; entry: 'picker' | 'manage' };
