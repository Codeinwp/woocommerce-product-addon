/**
 * Field modal: grouped picker, typed or schema fallback editor, save via admin REST.
 */
import { useState, useEffect, useCallback, Fragment } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import {
	Modal,
	ModalOverlay,
	ModalContent,
	ModalHeader,
	ModalBody,
	ModalFooter,
	ModalCloseButton,
	Button,
	Box,
	VStack,
	Text,
	Spinner,
	Alert,
	AlertIcon,
	HStack,
	Skeleton,
} from '@chakra-ui/react';
import { FieldTypePicker } from './FieldTypePicker';
import { FieldSettingsForm } from './FieldSettingsForm';
import { getFieldEditor } from './editors/registry';

function newClientId() {
	if ( typeof window !== 'undefined' && window.crypto && window.crypto.randomUUID ) {
		return window.crypto.randomUUID();
	}
	return `f-${ Date.now() }-${ Math.random().toString( 16 ).slice( 2 ) }`;
}

function withClientIds( rows ) {
	return ( rows || [] ).map( ( row ) => ( {
		clientId: newClientId(),
		...row,
	} ) );
}

function stripClientIds( rows ) {
	return rows.map( ( { clientId, ...rest } ) => rest );
}

function readGroupFromForm( baseGroup ) {
	const form = document.querySelector( 'form.ppom-save-fields-meta' );
	const next = { ...baseGroup };
	if ( ! form || ! form.elements ) {
		return next;
	}
	const get = ( name ) => {
		const el = form.elements.namedItem( name );
		return el && 'value' in el ? String( el.value ) : '';
	};
	if ( get( 'productmeta_name' ) ) {
		next.productmeta_name = get( 'productmeta_name' );
	}
	const dph = get( 'dynamic_price_hide' );
	if ( dph ) {
		next.dynamic_price_display = dph;
	}
	const style = get( 'productmeta_style' );
	if ( style !== undefined && style !== '' ) {
		next.productmeta_style = style;
	}
	const js = get( 'productmeta_js' );
	if ( js !== undefined && js !== '' ) {
		next.productmeta_js = js;
	}
	return next;
}

/**
 * @param {number|undefined} selectFieldIndex 1-based row index from PPOM table (matches button#id).
 */
export function App( { productmetaId } ) {
	const [ open, setOpen ] = useState( false );
	const [ pickerOpen, setPickerOpen ] = useState( false );
	const [ pickerQuery, setPickerQuery ] = useState( '' );
	const [ loading, setLoading ] = useState( false );
	const [ saving, setSaving ] = useState( false );
	const [ error, setError ] = useState( '' );
	const [ ctx, setCtx ] = useState( null );
	const [ fields, setFields ] = useState( [] );
	const [ selectedId, setSelectedId ] = useState( null );
	const [ editDraft, setEditDraft ] = useState( null );
	const [ schemasCache, setSchemasCache ] = useState( {} );
	const [ schemaLoading, setSchemaLoading ] = useState( false );
	/** `picker` when opened via Add New Field; `manage` when opened via Manage Fields. */
	const [ modalEntry, setModalEntry ] = useState( 'manage' );

	const loadContext = useCallback(
		async ( selectFieldIndex ) => {
			setLoading( true );
			setError( '' );
			setSchemasCache( {} );
			try {
				const res = await apiFetch( {
					path: `ppom/v1/admin/field-groups/context?productmeta_id=${ encodeURIComponent(
						String( productmetaId )
					) }`,
				} );
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
				setError( e.message || String( e ) );
			} finally {
				setLoading( false );
			}
		},
		[ productmetaId ]
	);

	const fetchSchemaForType = useCallback( async ( type ) => {
		if ( ! type ) {
			return null;
		}
		const t = String( type ).toLowerCase();
		setSchemaLoading( true );
		try {
			const res = await apiFetch( {
				path: `ppom/v1/admin/field-groups/schema/${ encodeURIComponent( t ) }`,
			} );
			const schema = res && res.schema ? res.schema : null;
			if ( schema ) {
				setSchemasCache( ( prev ) => ( { ...prev, [ t ]: schema } ) );
			}
			return schema;
		} catch ( e ) {
			setError( e.message || String( e ) );
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
		const handlers = [];
		buttons.forEach( ( btn ) => {
			const onClick = () => {
				const mode = btn.getAttribute( 'data-ppom-react-mode' );
				const fromPicker = mode === 'picker';
				setModalEntry( fromPicker ? 'picker' : 'manage' );
				setPickerOpen( fromPicker );
				setPickerQuery( '' );
				setOpen( true );
				loadContext();
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
		fetchSchemaForType( t );
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

	const openLegacyEditor = () => {
		if ( ppomFieldIndex > 0 ) {
			document
				.querySelector(
					`button.ppom-edit-field[id="${ String( ppomFieldIndex ) }"]`
				)
				?.click();
		}
	};

	const addFieldOfType = ( slug ) => {
		const flat = ctx?.catalog || [];
		const entry = flat.find( ( c ) => c.slug === slug );
		if ( entry && entry.locked ) {
			return;
		}
		const title = entry?.title || slug;
		const row = {
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
	};

	const removeField = ( clientId ) => {
		setFields( ( prev ) => prev.filter( ( f ) => f.clientId !== clientId ) );
		if ( selectedId === clientId ) {
			setSelectedId( null );
		}
	};

	const handleSave = async () => {
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
			let path;
			if ( productmetaId > 0 ) {
				path = `ppom/v1/admin/field-groups/${ productmetaId }`;
				await apiFetch( { path, method: 'PUT', data: payload } );
			} else {
				path = 'ppom/v1/admin/field-groups';
				const res = await apiFetch( {
					path,
					method: 'POST',
					data: payload,
				} );
				if ( res.redirect_to ) {
					window.location.assign( res.redirect_to );
					return;
				}
			}
			window.location.reload();
		} catch ( e ) {
			setError( e.message || String( e ) );
		} finally {
			setSaving( false );
		}
	};

	const closeModal = () => {
		setOpen( false );
		setPickerOpen( false );
		setPickerQuery( '' );
		setModalEntry( 'manage' );
	};

	const catalogGroups =
		ctx?.catalog_groups && ctx.catalog_groups.length > 0
			? ctx.catalog_groups
			: [];

	const activeSchema =
		editDraft && editDraft.type
			? schemasCache[ String( editDraft.type ).toLowerCase() ]
			: null;

	const modalContext = {
		builderFields: fields,
		conditionsProEnabled: ctx?.conditions_pro_enabled === true,
	};

	const TypedEditor =
		editDraft && editDraft.type
			? getFieldEditor( editDraft.type )
			: null;

	return (
		<Fragment>
			<Modal
				isOpen={ open }
				onClose={ closeModal }
				variant="ppom"
				scrollBehavior="inside"
				closeOnOverlayClick={ ! saving }
				closeOnEsc={ ! saving }
				motionPreset="slideInBottom"
			>
				<ModalOverlay bg="blackAlpha.600" backdropFilter="blur(2px)" />
				<ModalContent
					maxW={ { base: '96vw', md: 'min(90vw, 52rem)', lg: '56rem' } }
					w="full"
					maxH="min(90vh, 56rem)"
					my={ 4 }
					display="flex"
					flexDirection="column"
				>
					<ModalHeader flexShrink={ 0 }>
						{ i18n.newFieldModal || 'PPOM fields' }
					</ModalHeader>
					<ModalCloseButton isDisabled={ saving } />
					<ModalBody
						flex="1"
						overflowY="auto"
						minH={ 0 }
						py={ 2 }
						px={ { base: 3, md: 5 } }
					>
						{ loading && (
							<HStack py={ 8 } justify="center">
								<Spinner size="md" />
								<Text>{ i18n.loading || 'Loading…' }</Text>
							</HStack>
						) }
						{ error && (
							<Alert status="error" mb={ 3 } borderRadius="md">
								<AlertIcon />
								{ error }
							</Alert>
						) }
						{ ! loading && ctx && pickerOpen && (
							<VStack align="stretch" spacing={ 3 }>
								<HStack justify="flex-end">
									<Button
										size="sm"
										variant="outline"
										onClick={ () => {
											setPickerQuery( '' );
											if ( modalEntry === 'picker' ) {
												closeModal();
											} else {
												setPickerOpen( false );
											}
										} }
									>
										{ modalEntry === 'picker'
											? i18n.cancelFieldPicker || 'Cancel'
											: i18n.back || 'Back' }
									</Button>
								</HStack>
								{ catalogGroups.length > 0 ? (
									<FieldTypePicker
										catalogGroups={ catalogGroups }
										query={ pickerQuery }
										onQueryChange={ setPickerQuery }
										onPick={ addFieldOfType }
										upsell={ ctx.upsell }
										license={ ctx.license }
										i18n={ i18n }
									/>
								) : (
									<Alert status="warning">
										<AlertIcon />
										{ i18n.noFieldTypes || 'No field types are available.' }
									</Alert>
								) }
							</VStack>
						) }
						{ ! loading && ctx && ! pickerOpen && (
							<Box minH="220px">
								{ fields.length === 0 && (
									<VStack
										align="stretch"
										spacing={ 4 }
										p={ { base: 2, md: 3 } }
										pt={ 0 }
									>
										<Text fontSize="sm" color="gray.600" lineHeight="1.6">
											{ i18n.manageFieldsEmpty ||
												'No fields yet. Use Add field above (classic) or choose a type below.' }
										</Text>
										<Button
											size="sm"
											colorScheme="blue"
											alignSelf="flex-start"
											onClick={ () => {
												setPickerQuery( '' );
												setPickerOpen( true );
											} }
										>
											{ i18n.openAddFieldType || 'Choose field type' }
										</Button>
									</VStack>
								) }
								{ fields.length > 0 && selectedId && editDraft && (
									<VStack align="stretch" spacing={ 3 }>
										{ schemaLoading && ! activeSchema && (
											<VStack spacing={ 2 } align="stretch">
												<Skeleton height="36px" />
												<Skeleton height="36px" />
												<Skeleton height="72px" />
											</VStack>
										) }
										{ activeSchema && TypedEditor && (
											<TypedEditor
												schema={ activeSchema }
												values={ editDraft }
												onChange={ setEditDraft }
												i18n={ i18n }
												ppomFieldIndex={ ppomFieldIndex }
												modalContext={ modalContext }
											/>
										) }
										{ activeSchema && ! TypedEditor && (
											<FieldSettingsForm
												schema={ activeSchema }
												values={ editDraft }
												onChange={ setEditDraft }
												fieldType={ editDraft.type || '' }
												i18n={ i18n }
												ppomFieldIndex={ ppomFieldIndex }
												modalContext={ modalContext }
												isFallback
											/>
										) }
										{ ! schemaLoading && ! activeSchema && editDraft.type && (
											<Alert status="info">
												<AlertIcon />
												{ i18n.unsupportedControl }
											</Alert>
										) }
										</VStack>
								) }
							</Box>
						) }
					</ModalBody>
					<ModalFooter
						flexShrink={ 0 }
						display="flex"
						flexWrap="wrap"
						gap={ 3 }
						justifyContent="space-between"
						alignItems="center"
					>
						<HStack flexWrap="wrap" spacing={ 3 } alignItems="center">
							{ ! pickerOpen && selectedId && editDraft && (
								<>
									{ modalEntry === 'picker' && (
										<Button
											variant="link"
											size="sm"
											colorScheme="blue"
											onClick={ () => {
												setPickerQuery( '' );
												setPickerOpen( true );
											} }
										>
											{ i18n.backToFieldTypes ||
												'Back to field types' }
										</Button>
									) }
									<Button
										variant="link"
										size="sm"
										onClick={ openLegacyEditor }
									>
										{ i18n.openLegacyModal }
									</Button>
									<Button
										variant="link"
										size="sm"
										colorScheme="red"
										onClick={ () => removeField( selectedId ) }
									>
										{ i18n.remove || 'Remove' }
									</Button>
								</>
							) }
						</HStack>
						<HStack spacing={ 2 }>
							<Button
								variant="ghost"
								onClick={ closeModal }
								isDisabled={ saving }
							>
								{ i18n.close || 'Close' }
							</Button>
							<Button
								colorScheme="blue"
								onClick={ handleSave }
								isLoading={ saving }
								isDisabled={ loading || ! ctx || pickerOpen }
							>
								{ i18n.save || 'Save' }
							</Button>
						</HStack>
					</ModalFooter>
				</ModalContent>
			</Modal>
		</Fragment>
	);
}
