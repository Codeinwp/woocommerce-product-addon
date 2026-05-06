/**
 * Pure reducer for field modal UI state (sync transitions only).
 */
import type { FieldRow } from '../types/fieldModal';
import type { ModalReducerAction, ModalReducerState } from './modalTypes';

function fieldExists( fields: FieldRow[], clientId: string | null ): boolean {
	return fields.some( ( field ) => field.clientId === clientId );
}

function nextSelectedIdAfterRemoval(
	fields: FieldRow[],
	removedClientId: string,
	currentSelectedId: string | null
): string | null {
	if ( currentSelectedId !== removedClientId ) {
		return currentSelectedId;
	}

	const removedIndex = fields.findIndex(
		( field ) => field.clientId === removedClientId
	);
	const nextFields = fields.filter(
		( field ) => field.clientId !== removedClientId
	);

	return (
		nextFields[ removedIndex ]?.clientId ??
		nextFields[ removedIndex - 1 ]?.clientId ??
		null
	);
}

export function createInitialModalState(): ModalReducerState {
	return {
		open: false,
		pickerOpen: false,
		pickerQuery: '',
		loading: false,
		saving: false,
		error: '',
		ctx: null,
		fields: [],
		cleanFieldSnapshots: {},
		persistedClientIds: [],
		dirtyClientIds: [],
		removedPersistedClientIds: [],
		selectedId: null,
		schemasCache: {},
		schemaLoading: false,
		schemaFetchError: '',
		modalEntry: 'manage',
	};
}

export function modalReducer(
	state: ModalReducerState,
	action: ModalReducerAction
): ModalReducerState {
	switch ( action.type ) {
		case 'OPEN':
			return {
				...state,
				open: true,
				pickerOpen: action.entry === 'picker',
				pickerQuery: '',
				modalEntry: action.entry,
			};
		case 'CLOSE':
			return {
				...createInitialModalState(),
			};
		case 'SET_PICKER_OPEN':
			return {
				...state,
				pickerOpen: action.open,
				selectedId: action.open ? null : state.selectedId,
			};
		case 'SET_PICKER_QUERY':
			return { ...state, pickerQuery: action.query };
		case 'LOAD_CONTEXT_START':
			return {
				...state,
				loading: true,
				error: '',
				schemasCache: {},
				schemaFetchError: '',
			};
		case 'LOAD_CONTEXT_SUCCESS':
			return {
				...state,
				loading: false,
				ctx: action.ctx,
				fields: action.fields,
				cleanFieldSnapshots: action.cleanFieldSnapshots,
				persistedClientIds: action.fields.map(
					( field ) => field.clientId
				),
				dirtyClientIds: [],
				removedPersistedClientIds: [],
				selectedId: state.pickerOpen ? null : action.selectedId,
				schemaFetchError: '',
			};
		case 'LOAD_CONTEXT_ERROR':
			return {
				...state,
				loading: false,
				error: action.message,
			};
		case 'CLEAR_ERROR':
			return { ...state, error: '' };
		case 'SET_SCHEMA_FETCH_ERROR':
			return { ...state, schemaFetchError: action.message };
		case 'SET_SCHEMA_FOR_TYPE': {
			if ( ! action.schema ) {
				return state;
			}
			const t = action.typeKey.toLowerCase();
			return {
				...state,
				schemasCache: { ...state.schemasCache, [ t ]: action.schema },
				schemaFetchError: '',
			};
		}
		case 'SET_SCHEMA_LOADING':
			return { ...state, schemaLoading: action.loading };
		case 'SET_CTX':
			return { ...state, ctx: action.ctx };
		case 'SET_FIELDS':
			return { ...state, fields: action.fields };
		case 'SET_SELECTED_ID':
			if ( action.id && ! fieldExists( state.fields, action.id ) ) {
				return state;
			}
			return {
				...state,
				selectedId: action.id,
				schemaFetchError: '',
			};
		case 'PATCH_FIELD_ROW_FROM_FORM': {
			const id = action.row.clientId;
			const nextFields = state.fields.map( ( f ) =>
				f.clientId === id ? { ...action.row } : f
			);
			const cleanSnapshot = state.cleanFieldSnapshots[ id ];
			const dirtyClientIds =
				cleanSnapshot !== undefined && cleanSnapshot === action.snapshot
					? state.dirtyClientIds.filter(
							( clientId ) => clientId !== id
					  )
					: Array.from( new Set( [ ...state.dirtyClientIds, id ] ) );
			return {
				...state,
				fields: nextFields,
				dirtyClientIds,
			};
		}
		case 'ADD_FIELD_ROW':
			return {
				...state,
				fields: [ ...state.fields, action.row ],
				cleanFieldSnapshots: {
					...state.cleanFieldSnapshots,
					[ action.row.clientId ]: action.snapshot,
				},
				selectedId: action.row.clientId,
				pickerOpen: false,
				pickerQuery: '',
			};
		case 'DUPLICATE_FIELD_ROW': {
			const idx = state.fields.findIndex(
				( f ) => f.clientId === action.sourceClientId
			);
			const next = [ ...state.fields ];
			if ( idx < 0 ) {
				next.push( action.newRow );
			} else {
				next.splice( idx + 1, 0, action.newRow );
			}
			return {
				...state,
				fields: next,
				cleanFieldSnapshots: {
					...state.cleanFieldSnapshots,
					[ action.newRow.clientId ]: action.snapshot,
				},
				selectedId: action.newRow.clientId,
				pickerOpen: false,
				pickerQuery: '',
			};
		}
		case 'REMOVE_FIELD_ROW': {
			const selectedId = nextSelectedIdAfterRemoval(
				state.fields,
				action.clientId,
				state.selectedId
			);
			const next = state.fields.filter(
				( f ) => f.clientId !== action.clientId
			);
			const { [ action.clientId ]: _removedSnapshot, ...snapshots } =
				state.cleanFieldSnapshots;
			const removedPersistedClientIds = state.persistedClientIds.includes(
				action.clientId
			)
				? Array.from(
						new Set( [
							...state.removedPersistedClientIds,
							action.clientId,
						] )
				  )
				: state.removedPersistedClientIds;
			return {
				...state,
				fields: next,
				cleanFieldSnapshots: snapshots,
				dirtyClientIds: state.dirtyClientIds.filter(
					( clientId ) => clientId !== action.clientId
				),
				removedPersistedClientIds,
				selectedId,
			};
		}
		case 'SET_SAVING':
			return { ...state, saving: action.saving };
		case 'SET_MODAL_ENTRY':
			return { ...state, modalEntry: action.entry };
		default:
			return state;
	}
}
