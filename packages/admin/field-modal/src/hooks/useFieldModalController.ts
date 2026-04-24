/**
 * State, effects, and handlers for the PPOM React field modal.
 */
import type { Dispatch, SetStateAction } from 'react';
import {
	useCallback,
	useEffect,
	useMemo,
	useReducer,
} from '@wordpress/element';
import { bindPpomReactFieldModalOpenButtons } from '../adapters/wpAdminFieldModalAdapter';
import {
	fetchFieldModalContext,
	fetchFieldTypeSchema,
	saveFieldGroup,
} from '../services/fieldModalApi';
import { getFieldUiDefinition } from '../definitions/registry';
import { newClientId, stripClientIds, withClientIds } from '../utils/clientIds';
import { readGroupFromForm } from '../utils/legacyGroupForm';
import { createInitialModalState, modalReducer } from '../state/modalReducer';
import { errorMessage } from '../utils/errorMessage';
import { stableStringifyFieldRow } from '../utils/fieldFormSync';
import { fieldModalI18n } from '../i18n';
import type {
	FieldModalContextPayload,
	FieldRow,
	I18nDict,
	ModalContextValue,
} from '../types/fieldModal';

function getFieldSaveValidationError(
	fields: FieldRow[],
	i18n: I18nDict
): string {
	for ( const field of fields ) {
		if ( ! Object.prototype.hasOwnProperty.call( field, 'data_name' ) ) {
			continue;
		}

		if ( String( field.data_name || '' ).trim() === '' ) {
			return i18n.dataNameRequired;
		}
	}

	return '';
}

function serializePersistedFields( fields: FieldRow[] ): string {
	return stableStringifyFieldRow( stripClientIds( fields ) );
}

function serializePersistedField( field: FieldRow ): string {
	return serializePersistedFields( [ field ] );
}

function definitionNeedsServerSchema( slug: string ): boolean {
	return slug === 'texter';
}

export function useFieldModalController( productmetaId: number | undefined ) {
	const [ state, dispatch ] = useReducer(
		modalReducer,
		undefined,
		createInitialModalState
	);

	const loadContext = useCallback(
		async ( selectFieldIndex?: number ) => {
			dispatch( { type: 'LOAD_CONTEXT_START' } );
			try {
				const res = await fetchFieldModalContext( productmetaId );
				const rows = withClientIds( res.fields || [] );
				let nextSelected: string | null = null;
				if (
					typeof selectFieldIndex === 'number' &&
					! Number.isNaN( selectFieldIndex ) &&
					selectFieldIndex >= 1
				) {
					const row = rows[ selectFieldIndex - 1 ];
					nextSelected = row ? row.clientId : null;
				}
				dispatch( {
					type: 'LOAD_CONTEXT_SUCCESS',
					ctx: res,
					fields: rows,
					cleanFieldSnapshots: Object.fromEntries(
						rows.map( ( row ) => [
							row.clientId,
							serializePersistedField( row ),
						] )
					),
					selectedId: nextSelected,
				} );
			} catch ( e ) {
				dispatch( {
					type: 'LOAD_CONTEXT_ERROR',
					message: errorMessage( e ),
				} );
			}
		},
		[ productmetaId ]
	);

	useEffect( () => {
		return bindPpomReactFieldModalOpenButtons( {
			onOpen: ( { entry, selectFieldIndex } ) => {
				dispatch( { type: 'OPEN', entry } );
				void loadContext( selectFieldIndex );
			},
		} );
	}, [ loadContext ] );

	const editDraft = useMemo( () => {
		if ( ! state.selectedId ) {
			return null;
		}
		return (
			state.fields.find( ( f ) => f.clientId === state.selectedId ) ??
			null
		);
	}, [ state.fields, state.selectedId ] );

	const localDefinitionSchema = useMemo( () => {
		const slug =
			editDraft && editDraft.type
				? String( editDraft.type ).toLowerCase()
				: '';
		if ( ! slug ) {
			return null;
		}
		const definition = getFieldUiDefinition( slug );
		if ( ! definition?.settings ) {
			return null;
		}
		if ( definitionNeedsServerSchema( slug ) ) {
			return null;
		}
		return {
			type: slug,
			settings: definition.settings,
			tabs: definition.tabs,
		};
	}, [ editDraft?.type ] );

	const ctx = state.ctx;
	const i18n = fieldModalI18n;
	const isDirty = useMemo( () => {
		return (
			state.dirtyClientIds.length > 0 ||
			state.removedPersistedClientIds.length > 0
		);
	}, [ state.dirtyClientIds, state.removedPersistedClientIds ] );

	const fetchSchemaForType = useCallback(
		async ( type: string | undefined ) => {
			if ( ! type ) {
				return null;
			}
			const t = String( type ).toLowerCase();
			dispatch( { type: 'SET_SCHEMA_FETCH_ERROR', message: '' } );
			dispatch( { type: 'SET_SCHEMA_LOADING', loading: true } );
			const emptyMsg = i18n.schemaEmptyResponse;
			try {
				const schema = await fetchFieldTypeSchema( t );
				if ( schema ) {
					dispatch( {
						type: 'SET_SCHEMA_FOR_TYPE',
						typeKey: t,
						schema,
					} );
					return schema;
				}
				dispatch( {
					type: 'SET_SCHEMA_FETCH_ERROR',
					message: emptyMsg,
				} );
				return null;
			} catch ( e ) {
				dispatch( {
					type: 'SET_SCHEMA_FETCH_ERROR',
					message: errorMessage( e ),
				} );
				return null;
			} finally {
				dispatch( { type: 'SET_SCHEMA_LOADING', loading: false } );
			}
		},
		[ i18n.schemaEmptyResponse ]
	);

	useEffect( () => {
		if ( ! state.open || ! editDraft?.type || localDefinitionSchema ) {
			return;
		}
		const t = String( editDraft.type ).toLowerCase();
		if ( state.schemasCache[ t ] ) {
			return;
		}
		void fetchSchemaForType( t );
	}, [
		state.open,
		editDraft?.type,
		localDefinitionSchema,
		state.schemasCache,
		fetchSchemaForType,
	] );

	useEffect( () => {
		if ( ! state.open || state.pickerOpen || state.loading ) {
			return;
		}
		if ( state.fields.length === 0 ) {
			if ( state.selectedId !== null ) {
				dispatch( { type: 'SET_SELECTED_ID', id: null } );
			}
			return;
		}
		const stillThere =
			state.selectedId &&
			state.fields.some( ( f ) => f.clientId === state.selectedId );
		if ( stillThere ) {
			return;
		}
		dispatch( { type: 'SET_SELECTED_ID', id: state.fields[ 0 ].clientId } );
	}, [
		state.open,
		state.pickerOpen,
		state.loading,
		state.fields,
		state.selectedId,
	] );

	const ppomFieldIndex =
		state.selectedId && state.fields.length
			? state.fields.findIndex(
					( f ) => f.clientId === state.selectedId
			  ) + 1
			: 0;

	const openLegacyEditor = useCallback( () => {
		if ( ppomFieldIndex > 0 ) {
			const el = document.querySelector(
				`button.ppom-edit-field[id="${ String( ppomFieldIndex ) }"]`
			);
			( el as HTMLElement | null )?.click();
		}
	}, [ ppomFieldIndex ] );

	const addFieldOfType = useCallback(
		( slug: string ) => {
			const flat = ctx?.catalog || [];
			const entry = flat.find( ( c ) => c.slug === slug );
			if ( entry && entry.locked ) {
				return;
			}
			const title = ( entry?.title as string | undefined ) || slug;
			const row: FieldRow = {
				clientId: newClientId(),
				type: slug,
				title,
				data_name: '',
				status: 'on',
			};
			dispatch( {
				type: 'ADD_FIELD_ROW',
				row,
				snapshot: serializePersistedField( row ),
			} );
		},
		[ ctx?.catalog ]
	);

	const removeField = useCallback( ( clientId: string ) => {
		dispatch( { type: 'REMOVE_FIELD_ROW', clientId } );
	}, [] );

	const handleSave = useCallback( async () => {
		if ( ! ctx ) {
			return;
		}
		dispatch( { type: 'CLEAR_ERROR' } );
		const saveValidationError = getFieldSaveValidationError(
			state.fields,
			i18n
		);
		if ( saveValidationError ) {
			dispatch( {
				type: 'LOAD_CONTEXT_ERROR',
				message: saveValidationError,
			} );
			return;
		}

		dispatch( { type: 'SET_SAVING', saving: true } );
		const group = readGroupFromForm( ctx.group || {} );
		try {
			const res = await saveFieldGroup( {
				productmetaId,
				group,
				fields: state.fields,
			} );
			if ( res && typeof res === 'object' && res.redirect_to ) {
				window.location.assign( res.redirect_to );
				return;
			}
			window.location.reload();
		} catch ( e ) {
			dispatch( {
				type: 'LOAD_CONTEXT_ERROR',
				message: errorMessage( e ),
			} );
		} finally {
			dispatch( { type: 'SET_SAVING', saving: false } );
		}
	}, [ ctx, state.fields, productmetaId, i18n ] );

	const closeModal = useCallback( () => {
		dispatch( { type: 'CLOSE' } );
	}, [] );

	const clearError = useCallback( () => {
		dispatch( { type: 'CLEAR_ERROR' } );
	}, [] );

	const catalogGroups = useMemo(
		() =>
			ctx?.catalog_groups && ctx.catalog_groups.length > 0
				? ctx.catalog_groups
				: [],
		[ ctx?.catalog_groups ]
	);

	const fieldTypeLabel = useMemo( () => {
		const raw = editDraft?.type;
		if ( ! raw || typeof raw !== 'string' ) {
			return '';
		}
		const slug = raw.toLowerCase();
		const flat = ctx?.catalog;
		if ( Array.isArray( flat ) ) {
			for ( const c of flat ) {
				if (
					c &&
					typeof c.slug === 'string' &&
					c.slug.toLowerCase() === slug &&
					typeof c.title === 'string' &&
					c.title.trim() !== ''
				) {
					return c.title;
				}
			}
		}
		for ( const g of catalogGroups ) {
			const groupFields = g.fields;
			if ( ! Array.isArray( groupFields ) ) {
				continue;
			}
			for ( const c of groupFields ) {
				if (
					c &&
					typeof c.slug === 'string' &&
					c.slug.toLowerCase() === slug &&
					typeof c.title === 'string' &&
					c.title.trim() !== ''
				) {
					return c.title;
				}
			}
		}
		return raw;
	}, [ editDraft?.type, ctx?.catalog, catalogGroups ] );

	const activeSchema = localDefinitionSchema
		? localDefinitionSchema
		: editDraft && editDraft.type
		? state.schemasCache[ String( editDraft.type ).toLowerCase() ] ?? null
		: null;

	const modalContext: ModalContextValue = useMemo(
		() => ( {
			builderFields: state.fields,
			conditionsProEnabled: ctx?.conditions_pro_enabled === true,
			conditionalRepeaterUnlocked:
				ctx?.conditional_repeater_unlocked === true,
			conditionalRepeaterShowUpsell:
				ctx?.conditional_repeater_show_upsell === true,
			links: ctx?.links || {},
		} ),
		[
			state.fields,
			ctx?.conditions_pro_enabled,
			ctx?.conditional_repeater_unlocked,
			ctx?.conditional_repeater_show_upsell,
			ctx?.links,
		]
	);

	const patchFieldRowFromForm: Dispatch< SetStateAction< FieldRow | null > > =
		useCallback(
			( action ) => {
				const current =
					state.selectedId === null ||
					state.selectedId === undefined
						? null
						: state.fields.find(
								( f ) => f.clientId === state.selectedId
						  ) ?? null;
				const row =
					typeof action === 'function'
						? (
								action as (
									p: FieldRow | null
								) => FieldRow | null
						   )( current )
						: action;
				if ( ! row || typeof row !== 'object' || ! row.clientId ) {
					return;
				}
				dispatch( {
					type: 'PATCH_FIELD_ROW_FROM_FORM',
					row,
					snapshot: serializePersistedField( row ),
				} );
			},
			[ state.fields, state.selectedId ]
		);

	return {
		open: state.open,
		pickerOpen: state.pickerOpen,
		pickerQuery: state.pickerQuery,
		loading: state.loading,
		saving: state.saving,
		error: state.error,
		ctx,
		fields: state.fields,
		selectedId: state.selectedId,
		editDraft,
		schemasCache: state.schemasCache,
		schemaLoading: state.schemaLoading,
		schemaFetchError: state.schemaFetchError,
		modalEntry: state.modalEntry,
		isDirty,
		i18n,
		ppomFieldIndex,
		catalogGroups,
		fieldTypeLabel,
		activeSchema,
		modalContext,
		TypedEditor: null,
		setPickerOpen: ( open: boolean ) =>
			dispatch( { type: 'SET_PICKER_OPEN', open } ),
		setPickerQuery: ( query: string ) =>
			dispatch( { type: 'SET_PICKER_QUERY', query } ),
		setSelectedId: ( id: string | null ) =>
			dispatch( { type: 'SET_SELECTED_ID', id } ),
		setEditDraft: patchFieldRowFromForm,
		loadContext,
		fetchSchemaForType,
		openLegacyEditor,
		addFieldOfType,
		removeField,
		handleSave,
		closeModal,
		clearError,
	};
}
