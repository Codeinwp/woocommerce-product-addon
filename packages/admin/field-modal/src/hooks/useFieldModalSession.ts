/**
 * Session controller for the PPOM React field modal workflow.
 */
import type { Dispatch, SetStateAction } from 'react';
import { useCallback, useMemo, useReducer } from '@wordpress/element';
import { getFieldUiDefinition } from '../definitions/registry';
import { createInitialModalState, modalReducer } from '../state/modalReducer';
import { errorMessage } from '../utils/errorMessage';
import { fieldModalI18n } from '../i18n';
import { useMountEffect } from './useMountEffect';
import {
	applyDraftDataNamePolicy,
	cloneFieldForDuplicate,
	createNewFieldRow,
	normalizeFieldRowsForSession,
	serializePersistedField,
} from '../utils/fieldRows';
import {
	defaultFieldModalSessionAdapters,
	type FieldModalSessionAdapters,
} from '../adapters/fieldModalSessionAdapters';
import type {
	CatalogGroup,
	FieldModalContextPayload,
	FieldRow,
	I18nDict,
	ModalContextValue,
	SchemaObject,
} from '../types/fieldModal';

function fieldDebugRow(
	field: FieldRow,
	index: number
): Record< string, unknown > {
	return {
		index: index + 1,
		clientId: field.clientId,
		type: field.type,
		title: field.title,
		data_name: field.data_name,
		hasDataName: String( field.data_name || '' ).trim() !== '',
	};
}

function logFieldModalDebug( message: string, data?: unknown ): void {
	if (
		typeof window === 'undefined' ||
		window.localStorage?.getItem( 'ppomFieldModalDebug' ) !== '1'
	) {
		return;
	}
	if ( typeof console === 'undefined' ) {
		return;
	}
	// eslint-disable-next-line no-console
	console.log( `[PPOM_FIELD_MODAL_DEBUG] ${ message }`, data );
}

function getFieldSaveValidationError(
	fields: FieldRow[],
	i18n: I18nDict
): string {
	for ( const [ index, field ] of fields.entries() ) {
		if ( ! Object.prototype.hasOwnProperty.call( field, 'data_name' ) ) {
			continue;
		}

		if ( String( field.data_name || '' ).trim() === '' ) {
			const title = String( field.title || field.type || '' ).trim();
			const fieldLabel = title
				? `field #${ index + 1 } (${ title })`
				: `field #${ index + 1 }`;
			logFieldModalDebug(
				'save validation failed',
				fieldDebugRow( field, index )
			);
			return `${ i18n.dataNameRequired }: ${ fieldLabel }`;
		}
	}

	return '';
}

function definitionNeedsServerSchema( slug: string ): boolean {
	return slug === 'texter';
}

function getLocalDefinitionSchema( type: unknown ): SchemaObject | null {
	const slug = typeof type === 'string' ? type.toLowerCase() : '';
	if ( ! slug ) {
		return null;
	}
	const definition = getFieldUiDefinition( slug );
	if ( ! definition?.settings || definitionNeedsServerSchema( slug ) ) {
		return null;
	}
	return {
		type: slug,
		settings: definition.settings,
		tabs: definition.tabs,
	};
}

function fieldAtOneBasedIndex(
	rows: FieldRow[],
	selectFieldIndex?: number
): FieldRow | null {
	if (
		typeof selectFieldIndex !== 'number' ||
		Number.isNaN( selectFieldIndex ) ||
		selectFieldIndex < 1
	) {
		return null;
	}
	return (
		rows.find( ( row ) => fieldClassicIndex( row ) === selectFieldIndex ) ??
		rows[ selectFieldIndex - 1 ] ??
		null
	);
}

function fieldClassicIndex(
	field: FieldRow | null | undefined
): number | undefined {
	const raw = field?.__classicFieldIndex;
	let parsed = NaN;
	if ( typeof raw === 'number' ) {
		parsed = raw;
	} else if ( raw ) {
		parsed = parseInt( String( raw ), 10 );
	}
	return Number.isFinite( parsed ) && parsed > 0 ? parsed : undefined;
}

function stripClientKey( field: FieldRow ): Omit< FieldRow, 'clientId' > {
	const { clientId, ...rest } = field;
	return rest;
}

function mergeClassicRowsWithBootstrapFallback(
	classicFields: Array< Omit< FieldRow, 'clientId' > >,
	bootstrapFields: FieldRow[] = []
): Array< Omit< FieldRow, 'clientId' > > {
	return classicFields.map( ( field, position ) => {
		const classicIndex = fieldClassicIndex( field as FieldRow );
		const fallback = bootstrapFields[
			classicIndex ? classicIndex - 1 : position
		] as Record< string, unknown > | undefined;
		if ( ! fallback ) {
			return field;
		}
		const merged: Record< string, unknown > = { ...fallback, ...field };
		Object.entries( fallback ).forEach( ( [ key, value ] ) => {
			const current = merged[ key ];
			if (
				Array.isArray( current ) &&
				current.length === 0 &&
				Array.isArray( value ) &&
				value.length > 0
			) {
				merged[ key ] = value;
			}
		} );
		return merged as Omit< FieldRow, 'clientId' >;
	} );
}

export interface FieldModalSessionState {
	open: boolean;
	pickerOpen: boolean;
	pickerQuery: string;
	loading: boolean;
	saving: boolean;
	error: string;
	ctx: FieldModalContextPayload | null;
	fields: FieldRow[];
	selectedId: string | null;
	editDraft: FieldRow | null;
	schemaLoading: boolean;
	schemaFetchError: string;
	modalEntry: 'picker' | 'manage';
}

export interface FieldModalSessionDerived {
	isDirty: boolean;
	isNewField: boolean;
	i18n: I18nDict;
	ppomFieldIndex: number;
	catalogGroups: CatalogGroup[];
	fieldTypeLabel: string;
	activeSchema: SchemaObject | null;
	modalContext: ModalContextValue;
}

export interface FieldModalSessionCommands {
	openPicker: () => void;
	showPicker: () => void;
	openEditor: ( selectFieldIndex?: number ) => void;
	copyField: ( sourceFieldIndex?: number ) => void;
	selectField: ( id: string | null ) => void;
	setPickerQuery: ( query: string ) => void;
	updateDraft: Dispatch< SetStateAction< FieldRow | null > >;
	addFieldOfType: ( slug: string ) => void;
	removeField: ( clientId: string ) => void;
	openLegacyEditor: () => void;
	save: () => void;
	close: () => void;
	clearError: () => void;
}

export interface FieldModalSession {
	state: FieldModalSessionState;
	derived: FieldModalSessionDerived;
	commands: FieldModalSessionCommands;
}

export function useFieldModalSession(
	productmetaId: number | undefined,
	adapters: FieldModalSessionAdapters = defaultFieldModalSessionAdapters
): FieldModalSession {
	const [ state, dispatch ] = useReducer(
		modalReducer,
		undefined,
		createInitialModalState
	);

	const i18n = fieldModalI18n;
	const ctx = state.ctx;
	const fields = useMemo(
		() =>
			state.fieldOrder
				.map( ( id ) => state.fieldsById[ id ] )
				.filter( Boolean ) as FieldRow[],
		[ state.fieldOrder, state.fieldsById ]
	);
	const committedFields = useMemo( () => {
		if ( ! state.activeDraft ) {
			return fields;
		}
		return fields.map( ( field ) =>
			field.clientId === state.activeDraft?.clientId
				? state.activeDraft
				: field
		);
	}, [ fields, state.activeDraft ] );

	const ensureSchemaForType = useCallback(
		async ( type: unknown ) => {
			const t = typeof type === 'string' ? type.toLowerCase() : '';
			if (
				! t ||
				getLocalDefinitionSchema( t ) ||
				state.schemasCache[ t ]
			) {
				return null;
			}
			dispatch( { type: 'SET_SCHEMA_FETCH_ERROR', message: '' } );
			dispatch( { type: 'SET_SCHEMA_LOADING', loading: true } );
			try {
				const schema = await adapters.transport.fetchSchema( t );
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
					message: i18n.schemaEmptyResponse,
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
		[ adapters.transport, i18n.schemaEmptyResponse, state.schemasCache ]
	);

	const loadContext = useCallback(
		async (
			selectFieldIndex: number | undefined,
			entry: 'picker' | 'manage'
		) => {
			dispatch( { type: 'LOAD_CONTEXT_START' } );
			try {
				const res =
					await adapters.transport.fetchContext( productmetaId );
				const classicFields = mergeClassicRowsWithBootstrapFallback(
					adapters.admin.getClassicBuilderFields(),
					res.fields || []
				);
				const normalized = normalizeFieldRowsForSession(
					classicFields || [],
					res.catalog || []
				);
				const { rows } = normalized;
				logFieldModalDebug(
					'context fields loaded into session',
					rows.map( fieldDebugRow )
				);
				const selectedRow =
					fieldAtOneBasedIndex( rows, selectFieldIndex ) ??
					( entry === 'manage' ? rows[ 0 ] ?? null : null );
				dispatch( {
					type: 'LOAD_CONTEXT_SUCCESS',
					ctx: res,
					fields: rows,
					cleanFieldSnapshots: normalized.cleanFieldSnapshots,
					dirtyClientIds: normalized.dirtyClientIds,
					selectedId: selectedRow?.clientId ?? null,
				} );
				if ( selectedRow ) {
					void ensureSchemaForType( selectedRow.type );
				}
			} catch ( e ) {
				dispatch( {
					type: 'LOAD_CONTEXT_ERROR',
					message: errorMessage( e ),
				} );
			}
		},
		[
			adapters.admin,
			adapters.transport,
			ensureSchemaForType,
			productmetaId,
		]
	);

	const openPicker = useCallback( () => {
		dispatch( { type: 'OPEN', entry: 'picker' } );
		void loadContext( undefined, 'picker' );
	}, [ loadContext ] );

	const showPicker = useCallback( () => {
		dispatch( { type: 'COMMIT_ACTIVE_DRAFT' } );
		dispatch( { type: 'SET_PICKER_OPEN', open: true } );
	}, [] );

	const openEditor = useCallback(
		( selectFieldIndex?: number ) => {
			dispatch( { type: 'OPEN', entry: 'manage' } );
			void loadContext( selectFieldIndex, 'manage' );
		},
		[ loadContext ]
	);

	const copyField = useCallback(
		async ( sourceFieldIndex?: number ) => {
			if (
				typeof sourceFieldIndex !== 'number' ||
				sourceFieldIndex < 1
			) {
				openEditor();
				return;
			}

			dispatch( { type: 'OPEN', entry: 'manage' } );
			dispatch( { type: 'LOAD_CONTEXT_START' } );
			try {
				const res =
					await adapters.transport.fetchContext( productmetaId );
				const classicFields = mergeClassicRowsWithBootstrapFallback(
					adapters.admin.getClassicBuilderFields(),
					res.fields || []
				);
				const normalized = normalizeFieldRowsForSession(
					classicFields || [],
					res.catalog || []
				);
				const { rows } = normalized;
				logFieldModalDebug(
					'context fields loaded for copy',
					rows.map( fieldDebugRow )
				);
				dispatch( {
					type: 'LOAD_CONTEXT_SUCCESS',
					ctx: res,
					fields: rows,
					cleanFieldSnapshots: normalized.cleanFieldSnapshots,
					dirtyClientIds: normalized.dirtyClientIds,
					selectedId: null,
				} );
				const source = fieldAtOneBasedIndex( rows, sourceFieldIndex );
				if ( ! source ) {
					return;
				}
				const dup = cloneFieldForDuplicate( source, rows );
				dispatch( {
					type: 'DUPLICATE_FIELD_ROW',
					sourceClientId: source.clientId,
					newRow: dup,
					snapshot: serializePersistedField( dup ),
				} );
				void ensureSchemaForType( dup.type );
			} catch ( e ) {
				dispatch( {
					type: 'LOAD_CONTEXT_ERROR',
					message: errorMessage( e ),
				} );
			}
		},
		[
			adapters.admin,
			adapters.transport,
			ensureSchemaForType,
			openEditor,
			productmetaId,
		]
	);

	useMountEffect( () =>
		adapters.admin.bindOpenTriggers( {
			onOpen: ( { entry, selectFieldIndex } ) => {
				if ( entry === 'copy' ) {
					copyField( selectFieldIndex );
					return;
				}
				if ( entry === 'picker' ) {
					openPicker();
					return;
				}
				openEditor( selectFieldIndex );
			},
		} )
	);

	const editDraft = state.activeDraft;

	const ppomFieldIndex =
		state.selectedId && fields.length
			? fields.findIndex( ( f ) => f.clientId === state.selectedId ) + 1
			: 0;

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
		getLocalDefinitionSchema( editDraft?.type ) ??
		( editDraft && editDraft.type
			? state.schemasCache[ String( editDraft.type ).toLowerCase() ] ??
			  null
			: null );

	const isDirty = useMemo( () => {
		return (
			state.dirtyClientIds.length > 0 ||
			state.removedPersistedClientIds.length > 0
		);
	}, [ state.dirtyClientIds, state.removedPersistedClientIds ] );

	const isNewField = useMemo( () => {
		if ( ! state.selectedId ) {
			return false;
		}
		return ! state.persistedClientIds.includes( state.selectedId );
	}, [ state.selectedId, state.persistedClientIds ] );

	const modalContext: ModalContextValue = useMemo(
		() => ( {
			builderFields: committedFields,
			conditionsProEnabled: ctx?.conditions_pro_enabled === true,
			conditionalRepeaterUnlocked:
				ctx?.conditional_repeater_unlocked === true,
			conditionalRepeaterShowUpsell:
				ctx?.conditional_repeater_show_upsell === true,
			links: ctx?.links || {},
		} ),
		[
			committedFields,
			ctx?.conditions_pro_enabled,
			ctx?.conditional_repeater_unlocked,
			ctx?.conditional_repeater_show_upsell,
			ctx?.links,
		]
	);

	const setPickerQuery = useCallback( ( query: string ) => {
		dispatch( { type: 'SET_PICKER_QUERY', query } );
	}, [] );

	const selectField = useCallback(
		( id: string | null ) => {
			dispatch( { type: 'COMMIT_ACTIVE_DRAFT' } );
			dispatch( { type: 'SET_SELECTED_ID', id } );
			const row = id
				? committedFields.find( ( field ) => field.clientId === id )
				: null;
			if ( row ) {
				void ensureSchemaForType( row.type );
			}
		},
		[ ensureSchemaForType, committedFields ]
	);

	const updateDraft: Dispatch< SetStateAction< FieldRow | null > > =
		useCallback(
			( action ) => {
				const current =
					state.selectedId === null || state.selectedId === undefined
						? null
						: state.activeDraft;
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
				const normalized = current
					? applyDraftDataNamePolicy( {
							previous: current,
							next: row,
							fields: committedFields,
							locked:
								state.dataNameLockedById[ row.clientId ] ===
								true,
					  } )
					: {
							row,
							locked:
								state.dataNameLockedById[ row.clientId ] ===
								true,
					  };
				const rowIndex = committedFields.findIndex(
					( field ) => field.clientId === row.clientId
				);
				logFieldModalDebug( 'form row changed', {
					before: current ? fieldDebugRow( current, rowIndex ) : null,
					after: fieldDebugRow( normalized.row, rowIndex ),
				} );
				dispatch( {
					type: 'SET_ACTIVE_DRAFT',
					row: normalized.row,
					snapshot: serializePersistedField( normalized.row ),
					dataNameLocked: normalized.locked,
				} );
				void ensureSchemaForType( normalized.row.type );
			},
			[
				committedFields,
				ensureSchemaForType,
				state.activeDraft,
				state.dataNameLockedById,
				state.selectedId,
			]
		);

	const addFieldOfType = useCallback(
		( slug: string ) => {
			const flat = ctx?.catalog || [];
			const entry = flat.find( ( c ) => c.slug === slug );
			if ( entry && entry.locked ) {
				return;
			}
			const title = ( entry?.title as string | undefined ) || slug;
			dispatch( { type: 'COMMIT_ACTIVE_DRAFT' } );
			const row = createNewFieldRow( {
				slug,
				title,
				existingRows: normalizeFieldRowsForSession(
					adapters.admin.getClassicBuilderFields() as FieldRow[],
					ctx?.catalog || []
				).rows,
			} );
			logFieldModalDebug(
				'field row added to session',
				fieldDebugRow( row, committedFields.length )
			);
			dispatch( {
				type: 'ADD_FIELD_ROW',
				row,
				snapshot: serializePersistedField( row ),
			} );
			void ensureSchemaForType( row.type );
		},
		[
			adapters.admin,
			committedFields.length,
			ctx?.catalog,
			ensureSchemaForType,
		]
	);

	const removeField = useCallback(
		( clientId: string ) => {
			dispatch( { type: 'COMMIT_ACTIVE_DRAFT' } );
			const nextFields = committedFields.filter(
				( field ) => field.clientId !== clientId
			);
			const nextSelected =
				state.selectedId === clientId
					? nextFields[ 0 ] ?? null
					: committedFields.find(
							( field ) => field.clientId === state.selectedId
					  ) ?? null;
			dispatch( { type: 'REMOVE_FIELD_ROW', clientId } );
			if ( nextSelected ) {
				void ensureSchemaForType( nextSelected.type );
			}
		},
		[ committedFields, ensureSchemaForType, state.selectedId ]
	);

	const openLegacyEditor = useCallback( () => {
		adapters.admin.openLegacyEditor( ppomFieldIndex );
	}, [ adapters.admin, ppomFieldIndex ] );

	const save = useCallback( async () => {
		if ( ! ctx ) {
			return;
		}
		dispatch( { type: 'CLEAR_ERROR' } );
		logFieldModalDebug(
			'save requested - current fields',
			committedFields.map( fieldDebugRow )
		);
		const saveValidationError = getFieldSaveValidationError(
			committedFields,
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
		try {
			dispatch( { type: 'COMMIT_ACTIVE_DRAFT' } );
			const active = state.activeDraft;
			if ( ! active ) {
				return;
			}
			const activeIndex = fieldClassicIndex( active );
			const activeDataName = String( active.data_name || '' ).trim();
			const duplicate = adapters.admin
				.getClassicBuilderFields()
				.find(
					( field ) =>
						String( field.data_name || '' ).trim() ===
							activeDataName &&
						fieldClassicIndex( field as FieldRow ) !== activeIndex
				);
			if ( activeDataName && duplicate ) {
				dispatch( {
					type: 'LOAD_CONTEXT_ERROR',
					message: `Data name already exists: ${ activeDataName }`,
				} );
				return;
			}
			const activePosition = fields.findIndex(
				( field ) => field.clientId === active.clientId
			);
			const previousField =
				activePosition > 0 ? fields[ activePosition - 1 ] : null;
			const insertAfterFieldIndex =
				state.modalEntry === 'manage' && ! activeIndex
					? fieldClassicIndex( previousField )
					: undefined;
			adapters.admin.commitFieldToClassicForm( stripClientKey( active ), {
				insertAfterFieldIndex,
			} );
			dispatch( { type: 'CLOSE' } );
		} catch ( e ) {
			dispatch( {
				type: 'LOAD_CONTEXT_ERROR',
				message: errorMessage( e ),
			} );
		} finally {
			dispatch( { type: 'SET_SAVING', saving: false } );
		}
	}, [
		adapters.admin,
		committedFields,
		ctx,
		fields,
		i18n,
		state.activeDraft,
		state.modalEntry,
	] );

	const close = useCallback( () => {
		dispatch( { type: 'CLOSE' } );
	}, [] );

	const clearError = useCallback( () => {
		dispatch( { type: 'CLEAR_ERROR' } );
	}, [] );

	return {
		state: {
			open: state.open,
			pickerOpen: state.pickerOpen,
			pickerQuery: state.pickerQuery,
			loading: state.loading,
			saving: state.saving,
			error: state.error,
			ctx,
			fields: committedFields,
			selectedId: state.selectedId,
			editDraft,
			schemaLoading: state.schemaLoading,
			schemaFetchError: state.schemaFetchError,
			modalEntry: state.modalEntry,
		},
		derived: {
			isDirty,
			isNewField,
			i18n,
			ppomFieldIndex,
			catalogGroups,
			fieldTypeLabel,
			activeSchema,
			modalContext,
		},
		commands: {
			openPicker,
			showPicker,
			openEditor,
			copyField,
			selectField,
			setPickerQuery,
			updateDraft,
			addFieldOfType,
			removeField,
			openLegacyEditor,
			save,
			close,
			clearError,
		},
	};
}
