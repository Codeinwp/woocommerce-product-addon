/**
 * State, effects, and handlers for the PPOM React field modal.
 */
import type { Dispatch, SetStateAction } from 'react';
import { useCallback, useEffect, useMemo, useReducer } from '@wordpress/element';
import { bindPpomReactFieldModalOpenButtons } from '../adapters/wpAdminFieldModalAdapter';
import {
	fetchFieldModalContext,
	fetchFieldTypeSchema,
	saveFieldGroup,
} from '../services/fieldModalApi';
import { getFieldEditor } from '../editors/registry';
import { hasFieldUiDefinition } from '../definitions/registry';
import { newClientId, withClientIds, stripClientIds } from '../utils/clientIds';
import { readGroupFromForm } from '../utils/legacyGroupForm';
import { createInitialModalState, modalReducer } from '../state/modalReducer';
import type {
	FieldModalContextPayload,
	FieldRow,
	FieldEditorComponent,
	ModalContextValue,
} from '../types/fieldModal';

function errorMessage( e: unknown ): string {
	if ( e instanceof Error ) {
		return e.message;
	}
	return String( e );
}

export function useFieldModalController( productmetaId: number | undefined ) {
	const [ state, dispatch ] = useReducer( modalReducer, undefined, createInitialModalState );

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
			onOpen: ( entry ) => {
				dispatch( { type: 'OPEN', entry } );
				void loadContext();
			},
		} );
	}, [ loadContext ] );

	const editDraft = useMemo( () => {
		if ( ! state.selectedId ) {
			return null;
		}
		return (
			state.fields.find( ( f ) => f.clientId === state.selectedId ) ?? null
		);
	}, [ state.fields, state.selectedId ] );

	const fetchSchemaForType = useCallback( async ( type: string | undefined ) => {
		if ( ! type ) {
			return null;
		}
		const t = String( type ).toLowerCase();
		dispatch( { type: 'SET_SCHEMA_LOADING', loading: true } );
		try {
			const schema = await fetchFieldTypeSchema( t );
			if ( schema ) {
				dispatch( {
					type: 'SET_SCHEMA_FOR_TYPE',
					typeKey: t,
					schema,
				} );
			}
			return schema;
		} catch ( e ) {
			dispatch( {
				type: 'LOAD_CONTEXT_ERROR',
				message: errorMessage( e ),
			} );
			return null;
		} finally {
			dispatch( { type: 'SET_SCHEMA_LOADING', loading: false } );
		}
	}, [] );

	useEffect( () => {
		if ( ! state.open || ! editDraft?.type ) {
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

	const ctx = state.ctx;
	const i18n = ctx?.i18n || {};

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
			dispatch( { type: 'ADD_FIELD_ROW', row } );
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
		dispatch( { type: 'SET_SAVING', saving: true } );
		dispatch( { type: 'CLEAR_ERROR' } );
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
	}, [ ctx, state.fields, productmetaId ] );

	const closeModal = useCallback( () => {
		dispatch( { type: 'CLOSE' } );
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

	const activeSchema =
		editDraft && editDraft.type
			? state.schemasCache[ String( editDraft.type ).toLowerCase() ] ??
			  null
			: null;

	const modalContext: ModalContextValue = useMemo(
		() => ( {
			builderFields: state.fields,
			conditionsProEnabled: ctx?.conditions_pro_enabled === true,
			conditionalRepeaterUnlocked:
				ctx?.conditional_repeater_unlocked === true,
			conditionalRepeaterShowUpsell:
				ctx?.conditional_repeater_show_upsell === true,
		} ),
		[
			state.fields,
			ctx?.conditions_pro_enabled,
			ctx?.conditional_repeater_unlocked,
			ctx?.conditional_repeater_show_upsell,
		]
	);

	const typedEditorSlug =
		editDraft && editDraft.type ? String( editDraft.type ) : '';
	const TypedEditor: FieldEditorComponent | null =
		typedEditorSlug && ! hasFieldUiDefinition( typedEditorSlug )
			? getFieldEditor( typedEditorSlug )
			: null;

	const patchFieldRowFromForm: Dispatch<
		SetStateAction< FieldRow | null >
	> = useCallback(
		( action ) => {
			const current =
				state.selectedId == null
					? null
					: state.fields.find(
							( f ) => f.clientId === state.selectedId
					  ) ?? null;
			const row =
				typeof action === 'function'
					? ( action as ( p: FieldRow | null ) => FieldRow | null )(
							current
					  )
					: action;
			if ( ! row || typeof row !== 'object' || ! row.clientId ) {
				return;
			}
			dispatch( { type: 'PATCH_FIELD_ROW_FROM_FORM', row } );
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
		modalEntry: state.modalEntry,
		i18n,
		ppomFieldIndex,
		catalogGroups,
		fieldTypeLabel,
		activeSchema,
		modalContext,
		TypedEditor,
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
	};
}
