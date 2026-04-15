/**
 * State, effects, and handlers for the PPOM React field modal.
 */
import { useState, useEffect, useCallback, useMemo } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { getFieldEditor } from '../editors/registry';
import { newClientId, withClientIds, stripClientIds } from '../utils/clientIds';
import { readGroupFromForm } from '../utils/legacyGroupForm';
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
	const [ open, setOpen ] = useState( false );
	const [ pickerOpen, setPickerOpen ] = useState( false );
	const [ pickerQuery, setPickerQuery ] = useState( '' );
	const [ loading, setLoading ] = useState( false );
	const [ saving, setSaving ] = useState( false );
	const [ error, setError ] = useState( '' );
	const [ ctx, setCtx ] = useState< FieldModalContextPayload | null >( null );
	const [ fields, setFields ] = useState< FieldRow[] >( [] );
	const [ selectedId, setSelectedId ] = useState< string | null >( null );
	const [ editDraft, setEditDraft ] = useState< FieldRow | null >( null );
	const [ schemasCache, setSchemasCache ] = useState<
		Record< string, Record< string, unknown > >
	>( {} );
	const [ schemaLoading, setSchemaLoading ] = useState( false );
	/** `picker` when opened via Add New Field; `manage` when opened via Manage Fields. */
	const [ modalEntry, setModalEntry ] = useState< 'picker' | 'manage' >( 'manage' );

	const loadContext = useCallback(
		async ( selectFieldIndex?: number ) => {
			setLoading( true );
			setError( '' );
			setSchemasCache( {} );
			try {
				const res = ( await apiFetch( {
					path: `ppom/v1/admin/field-groups/context?productmeta_id=${ encodeURIComponent(
						String( productmetaId )
					) }`,
				} ) ) as FieldModalContextPayload;
				setCtx( res );
				const rows = withClientIds( res.fields || [] );
				setFields( rows );
				if (
					typeof selectFieldIndex === 'number' &&
					! Number.isNaN( selectFieldIndex ) &&
					selectFieldIndex >= 1
				) {
					const row = rows[ selectFieldIndex - 1 ];
					setSelectedId( row ? row.clientId : null );
				} else {
					setSelectedId( null );
				}
			} catch ( e ) {
				setError( errorMessage( e ) );
			} finally {
				setLoading( false );
			}
		},
		[ productmetaId ]
	);

	const fetchSchemaForType = useCallback( async ( type: string | undefined ) => {
		if ( ! type ) {
			return null;
		}
		const t = String( type ).toLowerCase();
		setSchemaLoading( true );
		try {
			const res = ( await apiFetch( {
				path: `ppom/v1/admin/field-groups/schema/${ encodeURIComponent( t ) }`,
			} ) ) as { schema?: Record< string, unknown > };
			const schema = res && res.schema ? res.schema : null;
			if ( schema ) {
				setSchemasCache( ( prev ) => ( { ...prev, [ t ]: schema } ) );
			}
			return schema;
		} catch ( e ) {
			setError( errorMessage( e ) );
			return null;
		} finally {
			setSchemaLoading( false );
		}
	}, [] );

	useEffect( () => {
		const buttons = document.querySelectorAll( '.ppom-react-field-modal-open' );
		if ( ! buttons.length ) {
			return undefined;
		}
		const handlers: Array< { btn: Element; onClick: () => void } > = [];
		buttons.forEach( ( btn ) => {
			const onClick = () => {
				const mode = btn.getAttribute( 'data-ppom-react-mode' );
				const fromPicker = mode === 'picker';
				setModalEntry( fromPicker ? 'picker' : 'manage' );
				setPickerOpen( fromPicker );
				setPickerQuery( '' );
				setOpen( true );
				void loadContext();
			};
			btn.addEventListener( 'click', onClick );
			handlers.push( { btn, onClick } );
		} );
		return () => {
			handlers.forEach( ( { btn, onClick } ) =>
				btn.removeEventListener( 'click', onClick )
			);
		};
	}, [ loadContext ] );

	useEffect( () => {
		if ( ! open ) {
			return;
		}
		const sel = fields.find( ( f ) => f.clientId === selectedId );
		if ( sel ) {
			setEditDraft( { ...sel } );
		} else {
			setEditDraft( null );
		}
	}, [ open, selectedId, fields ] );

	useEffect( () => {
		if ( ! open || ! editDraft?.type ) {
			return;
		}
		const t = String( editDraft.type ).toLowerCase();
		if ( schemasCache[ t ] ) {
			return;
		}
		void fetchSchemaForType( t );
	}, [ open, editDraft?.type, schemasCache, fetchSchemaForType ] );

	/** Manage mode: no sidebar — keep a valid selection when the list changes. */
	useEffect( () => {
		if ( ! open || pickerOpen || loading ) {
			return;
		}
		if ( fields.length === 0 ) {
			if ( selectedId !== null ) {
				setSelectedId( null );
			}
			return;
		}
		const stillThere =
			selectedId &&
			fields.some( ( f ) => f.clientId === selectedId );
		if ( stillThere ) {
			return;
		}
		setSelectedId( fields[ 0 ].clientId );
	}, [ open, pickerOpen, loading, fields, selectedId ] );

	const i18n = ctx?.i18n || {};

	const ppomFieldIndex =
		selectedId && fields.length
			? fields.findIndex( ( f ) => f.clientId === selectedId ) + 1
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
			setFields( ( prev ) => [ ...prev, row ] );
			setSelectedId( row.clientId );
			setPickerOpen( false );
			setPickerQuery( '' );
		},
		[ ctx?.catalog ]
	);

	const removeField = useCallback(
		( clientId: string ) => {
			setFields( ( prev ) => prev.filter( ( f ) => f.clientId !== clientId ) );
			if ( selectedId === clientId ) {
				setSelectedId( null );
			}
		},
		[ selectedId ]
	);

	const handleSave = useCallback( async () => {
		if ( ! ctx ) {
			return;
		}
		const merged = fields.map( ( f ) =>
			f.clientId === selectedId && editDraft ? { ...editDraft } : f
		);
		setSaving( true );
		setError( '' );
		const group = readGroupFromForm( ctx.group || {} );
		const payload = {
			group,
			fields: stripClientIds( merged ),
		};
		try {
			let path: string;
			if ( ( productmetaId ?? 0 ) > 0 ) {
				path = `ppom/v1/admin/field-groups/${ productmetaId }`;
				await apiFetch( { path, method: 'PUT', data: payload } );
			} else {
				path = 'ppom/v1/admin/field-groups';
				const res = ( await apiFetch( {
					path,
					method: 'POST',
					data: payload,
				} ) ) as { redirect_to?: string };
				if ( res.redirect_to ) {
					window.location.assign( res.redirect_to );
					return;
				}
			}
			window.location.reload();
		} catch ( e ) {
			setError( errorMessage( e ) );
		} finally {
			setSaving( false );
		}
	}, [ ctx, fields, selectedId, editDraft, productmetaId ] );

	const closeModal = useCallback( () => {
		setOpen( false );
		setPickerOpen( false );
		setPickerQuery( '' );
		setModalEntry( 'manage' );
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
			? schemasCache[ String( editDraft.type ).toLowerCase() ] ?? null
			: null;

	const modalContext: ModalContextValue = useMemo(
		() => ( {
			builderFields: fields,
			conditionsProEnabled: ctx?.conditions_pro_enabled === true,
			conditionalRepeaterUnlocked:
				ctx?.conditional_repeater_unlocked === true,
			conditionalRepeaterShowUpsell:
				ctx?.conditional_repeater_show_upsell === true,
		} ),
		[
			fields,
			ctx?.conditions_pro_enabled,
			ctx?.conditional_repeater_unlocked,
			ctx?.conditional_repeater_show_upsell,
		]
	);

	const typedEditorSlug =
		editDraft && editDraft.type ? String( editDraft.type ) : '';
	const TypedEditor: FieldEditorComponent | null =
		typedEditorSlug ? getFieldEditor( typedEditorSlug ) : null;

	return {
		open,
		pickerOpen,
		pickerQuery,
		loading,
		saving,
		error,
		ctx,
		fields,
		selectedId,
		editDraft,
		schemasCache,
		schemaLoading,
		modalEntry,
		i18n,
		ppomFieldIndex,
		catalogGroups,
		fieldTypeLabel,
		activeSchema,
		modalContext,
		TypedEditor,
		setPickerOpen,
		setPickerQuery,
		setSelectedId,
		setEditDraft,
		loadContext,
		fetchSchemaForType,
		openLegacyEditor,
		addFieldOfType,
		removeField,
		handleSave,
		closeModal,
	};
}
