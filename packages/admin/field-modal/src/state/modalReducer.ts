/**
 * Pure reducer for field modal UI state (sync transitions only).
 */
import type { FieldRow } from '../types/fieldModal';
import type { ModalReducerAction, ModalReducerState } from './modalTypes';

function nextSelectedIdAfterRemoval(
	fieldOrder: string[],
	removedClientId: string,
	currentSelectedId: string | null
): string | null {
	if ( currentSelectedId !== removedClientId ) {
		return currentSelectedId;
	}

	const removedIndex = fieldOrder.findIndex(
		( id ) => id === removedClientId
	);
	const nextOrder = fieldOrder.filter( ( id ) => id !== removedClientId );

	return nextOrder[ removedIndex ] ?? nextOrder[ removedIndex - 1 ] ?? null;
}

function rowsById( rows: FieldRow[] ): Record< string, FieldRow > {
	return Object.fromEntries( rows.map( ( row ) => [ row.clientId, row ] ) );
}

function dirtyIdsAfterDraftChange(
	state: ModalReducerState,
	row: FieldRow,
	snapshot: string
): string[] {
	const cleanSnapshot = state.cleanFieldSnapshots[ row.clientId ];
	const isClean = cleanSnapshot !== undefined && cleanSnapshot === snapshot;
	return isClean
		? state.dirtyClientIds.filter(
				( clientId ) => clientId !== row.clientId
		  )
		: Array.from( new Set( [ ...state.dirtyClientIds, row.clientId ] ) );
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
		fieldsById: {},
		fieldOrder: [],
		cleanFieldSnapshots: {},
		persistedClientIds: [],
		dirtyClientIds: [],
		removedPersistedClientIds: [],
		selectedId: null,
		activeDraft: null,
		dataNameLockedById: {},
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
				activeDraft: action.open ? null : state.activeDraft,
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
				fieldsById: rowsById( action.fields ),
				fieldOrder: action.fields.map( ( field ) => field.clientId ),
				cleanFieldSnapshots: action.cleanFieldSnapshots,
				persistedClientIds: action.fields.map(
					( field ) => field.clientId
				),
				dirtyClientIds: action.dirtyClientIds,
				removedPersistedClientIds: [],
				selectedId: state.pickerOpen ? null : action.selectedId,
				activeDraft:
					state.pickerOpen || ! action.selectedId
						? null
						: action.fields.find(
								( field ) =>
									field.clientId === action.selectedId
						  ) ?? null,
				dataNameLockedById: {},
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
		case 'SET_SELECTED_ID':
			if ( action.id && ! state.fieldsById[ action.id ] ) {
				return state;
			}
			return {
				...state,
				selectedId: action.id,
				activeDraft: action.id ? state.fieldsById[ action.id ] : null,
				schemaFetchError: '',
			};
		case 'SET_ACTIVE_DRAFT': {
			return {
				...state,
				activeDraft: { ...action.row },
				dirtyClientIds: dirtyIdsAfterDraftChange(
					state,
					action.row,
					action.snapshot
				),
				dataNameLockedById: {
					...state.dataNameLockedById,
					[ action.row.clientId ]: action.dataNameLocked,
				},
			};
		}
		case 'COMMIT_ACTIVE_DRAFT':
			if ( ! state.activeDraft ) {
				return state;
			}
			return {
				...state,
				fieldsById: {
					...state.fieldsById,
					[ state.activeDraft.clientId ]: state.activeDraft,
				},
			};
		case 'ADD_FIELD_ROW':
			return {
				...state,
				fieldsById: {
					...state.fieldsById,
					[ action.row.clientId ]: action.row,
				},
				fieldOrder: [ ...state.fieldOrder, action.row.clientId ],
				cleanFieldSnapshots: {
					...state.cleanFieldSnapshots,
					[ action.row.clientId ]: action.snapshot,
				},
				selectedId: action.row.clientId,
				activeDraft: action.row,
				pickerOpen: false,
				pickerQuery: '',
			};
		case 'DUPLICATE_FIELD_ROW': {
			const idx = state.fieldOrder.findIndex(
				( id ) => id === action.sourceClientId
			);
			const next = [ ...state.fieldOrder ];
			if ( idx < 0 ) {
				next.push( action.newRow.clientId );
			} else {
				next.splice( idx + 1, 0, action.newRow.clientId );
			}
			return {
				...state,
				fieldsById: {
					...state.fieldsById,
					[ action.newRow.clientId ]: action.newRow,
				},
				fieldOrder: next,
				cleanFieldSnapshots: {
					...state.cleanFieldSnapshots,
					[ action.newRow.clientId ]: action.snapshot,
				},
				selectedId: action.newRow.clientId,
				activeDraft: action.newRow,
				pickerOpen: false,
				pickerQuery: '',
			};
		}
		case 'REMOVE_FIELD_ROW': {
			const selectedId = nextSelectedIdAfterRemoval(
				state.fieldOrder,
				action.clientId,
				state.selectedId
			);
			const { [ action.clientId ]: _removedRow, ...nextFieldsById } =
				state.fieldsById;
			const { [ action.clientId ]: _removedSnapshot, ...snapshots } =
				state.cleanFieldSnapshots;
			const { [ action.clientId ]: _removedLock, ...dataNameLockedById } =
				state.dataNameLockedById;
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
				fieldsById: nextFieldsById,
				fieldOrder: state.fieldOrder.filter(
					( id ) => id !== action.clientId
				),
				cleanFieldSnapshots: snapshots,
				dirtyClientIds: state.dirtyClientIds.filter(
					( clientId ) => clientId !== action.clientId
				),
				removedPersistedClientIds,
				selectedId,
				activeDraft: selectedId ? state.fieldsById[ selectedId ] : null,
				dataNameLockedById,
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
