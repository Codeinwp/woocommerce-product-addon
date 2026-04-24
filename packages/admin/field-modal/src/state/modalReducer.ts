/**
 * Pure reducer for field modal UI state (sync transitions only).
 */
import type { ModalReducerAction, ModalReducerState } from './modalTypes';

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
			return { ...state, pickerOpen: action.open };
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
				selectedId: action.selectedId,
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
		case 'REMOVE_FIELD_ROW': {
			const next = state.fields.filter(
				( f ) => f.clientId !== action.clientId
			);
			const sel =
				state.selectedId === action.clientId ? null : state.selectedId;
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
				selectedId: sel,
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
