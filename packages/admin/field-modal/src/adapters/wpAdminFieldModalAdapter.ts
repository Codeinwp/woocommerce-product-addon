/**
 * Binds classic PPOM admin triggers to the React field modal opener.
 */
import type { FieldRow } from '../types/fieldModal';

export type FieldModalEntryMode = 'picker' | 'manage' | 'copy';
export type ClassicFieldRow = Omit< FieldRow, 'clientId' >;

export interface FieldModalOpenPayload {
	entry: FieldModalEntryMode;
	selectFieldIndex?: number;
}

export interface BindPpomReactFieldModalOpenOptions {
	onOpen: ( payload: FieldModalOpenPayload ) => void;
}

type FieldValue = string | string[] | Record< string, unknown >;

function parseRowFieldIndex( btn: HTMLElement ): number | undefined {
	const rawId = btn.getAttribute( 'id' );
	const parsed = rawId ? parseInt( rawId, 10 ) : NaN;
	return Number.isFinite( parsed ) && parsed > 0 ? parsed : undefined;
}

function asString( value: unknown, fallback = '' ): string {
	if ( value === null || value === undefined ) {
		return fallback;
	}
	if ( typeof value === 'boolean' ) {
		return value ? 'on' : '';
	}
	return String( value );
}

function isPlainObject( value: unknown ): value is Record< string, unknown > {
	return (
		typeof value === 'object' && value !== null && ! Array.isArray( value )
	);
}

function createHiddenInput( name: string, value: unknown ): HTMLInputElement {
	const input = document.createElement( 'input' );
	input.type = 'hidden';
	input.name = name;
	input.value = asString( value );
	return input;
}

function createFieldIndexInput( value: number ): HTMLInputElement {
	const input = document.createElement( 'input' );
	input.type = 'hidden';
	input.id = 'field_index';
	input.value = String( value );
	return input;
}

function fieldIndexFromRow( row: ClassicFieldRow ): number | undefined {
	const raw = row.__classicFieldIndex;
	let parsed = NaN;
	if ( typeof raw === 'number' ) {
		parsed = raw;
	} else if ( raw ) {
		parsed = parseInt( String( raw ), 10 );
	}
	return Number.isFinite( parsed ) && parsed > 0 ? parsed : undefined;
}

function withoutInternalKeys( field: ClassicFieldRow ): ClassicFieldRow {
	return Object.fromEntries(
		Object.entries( field ).filter(
			( [ key ] ) => ! key.startsWith( '__' )
		)
	) as ClassicFieldRow;
}

function appendHiddenInputs(
	parent: HTMLElement,
	name: string,
	value: unknown
): void {
	if ( value === undefined ) {
		return;
	}

	if ( Array.isArray( value ) ) {
		value.forEach( ( item, index ) => {
			appendHiddenInputs( parent, `${ name }[${ index }]`, item );
		} );
		return;
	}

	if ( isPlainObject( value ) ) {
		Object.entries( value ).forEach( ( [ key, nestedValue ] ) => {
			appendHiddenInputs( parent, `${ name }[${ key }]`, nestedValue );
		} );
		return;
	}

	parent.appendChild( createHiddenInput( name, value ) );
}

function appendHiddenInputsForIndex(
	parent: HTMLElement,
	index: number,
	key: string,
	value: unknown
): void {
	appendHiddenInputs( parent, `ppom[${ index }][${ key }]`, value );
}

function legacyClassicValue(
	field: ClassicFieldRow,
	key: string,
	value: unknown
): unknown {
	if (
		String( field.type || '' ) === 'bulkquantity' &&
		key === 'options' &&
		Array.isArray( value )
	) {
		return JSON.stringify( value );
	}
	return value;
}

function parsePpomName(
	name: string
): { index: number; keys: string[] } | null {
	const match = name.match( /^ppom\[(\d+)\]((?:\[[^\]]*\])*)$/ );
	if ( ! match ) {
		return null;
	}
	const index = parseInt( match[ 1 ], 10 );
	if ( ! Number.isFinite( index ) || index < 1 ) {
		return null;
	}
	const keys = Array.from( match[ 2 ].matchAll( /\[([^\]]*)\]/g ) ).map(
		( item ) => item[ 1 ]
	);
	return { index, keys };
}

function assignNested(
	target: Record< string, unknown >,
	keys: string[],
	value: FieldValue
): void {
	if ( keys.length === 0 || keys[ 0 ] === '' ) {
		return;
	}
	let cursor = target;
	keys.forEach( ( key, position ) => {
		const isLast = position === keys.length - 1;
		if ( isLast ) {
			const previous = cursor[ key ];
			if ( previous === undefined ) {
				cursor[ key ] = value;
			} else if ( Array.isArray( previous ) ) {
				cursor[ key ] = [ ...previous, value as string ];
			} else {
				cursor[ key ] = [ previous as string, value as string ];
			}
			return;
		}
		if ( ! isPlainObject( cursor[ key ] ) ) {
			cursor[ key ] = {};
		}
		cursor = cursor[ key ] as Record< string, unknown >;
	} );
}

function normalizeSerializedValue( value: unknown ): unknown {
	if ( Array.isArray( value ) ) {
		return value.map( normalizeSerializedValue );
	}
	if ( ! isPlainObject( value ) ) {
		return value;
	}
	const entries = Object.entries( value );
	const numericEntries = entries
		.map( ( [ key, nestedValue ] ) => ( {
			index: parseInt( key, 10 ),
			key,
			value: nestedValue,
		} ) )
		.filter(
			( entry ) =>
				String( entry.index ) === entry.key &&
				Number.isFinite( entry.index ) &&
				entry.index >= 0
		);
	if ( numericEntries.length === entries.length ) {
		return numericEntries
			.sort( ( a, b ) => a.index - b.index )
			.map( ( entry ) => normalizeSerializedValue( entry.value ) );
	}
	return Object.fromEntries(
		entries.map( ( [ key, nestedValue ] ) => [
			key,
			normalizeSerializedValue( nestedValue ),
		] )
	);
}

function normalizeSerializedRow( row: ClassicFieldRow ): ClassicFieldRow {
	return Object.fromEntries(
		Object.entries( row ).map( ( [ key, value ] ) => [
			key,
			normalizeSerializedValue( value ),
		] )
	) as ClassicFieldRow;
}

function inputValue(
	element: HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement
): FieldValue | null {
	if ( element instanceof HTMLInputElement ) {
		if (
			( element.type === 'checkbox' || element.type === 'radio' ) &&
			! element.checked
		) {
			return null;
		}
		return element.value || ( element.type === 'checkbox' ? 'on' : '' );
	}
	if ( element instanceof HTMLSelectElement && element.multiple ) {
		return Array.from( element.selectedOptions ).map(
			( option ) => option.value
		);
	}
	return element.value;
}

function serializeClassicFieldModal( index: number ): ClassicFieldRow {
	const modal = document.getElementById( `ppom_field_model_${ index }` );
	if ( ! modal ) {
		throw new Error( `PPOM field modal #${ index } was not found.` );
	}
	const row: ClassicFieldRow = { __classicFieldIndex: index };
	const controls = modal.querySelectorAll(
		'input[name], select[name], textarea[name]'
	);
	controls.forEach( ( control ) => {
		const element = control as
			| HTMLInputElement
			| HTMLSelectElement
			| HTMLTextAreaElement;
		const parsed = parsePpomName( element.name );
		if ( ! parsed || parsed.index !== index ) {
			return;
		}
		const value = inputValue( element );
		if ( value === null ) {
			return;
		}
		assignNested( row, parsed.keys, value );
	} );
	if ( ! row.type ) {
		throw new Error(
			`PPOM field modal #${ index } could not be serialized.`
		);
	}
	return normalizeSerializedRow( row );
}

function getTableBody(): HTMLTableSectionElement {
	const tableBody = document.querySelector(
		'.ppom_field_table tbody'
	) as HTMLTableSectionElement | null;
	if ( ! tableBody ) {
		throw new Error( 'PPOM field builder table was not found.' );
	}
	return tableBody;
}

function getSaveModel(): HTMLElement {
	const saveModel = document.querySelector(
		'.ppom-save-fields-meta .ppom_save_fields_model'
	) as HTMLElement | null;
	if ( ! saveModel ) {
		throw new Error( 'PPOM field builder form was not found.' );
	}
	return saveModel;
}

function currentFieldIndexes(): number[] {
	return Array.from(
		getTableBody().querySelectorAll( "tr[id^='ppom_sort_id_']" )
	)
		.map( ( row ) => parseInt( row.id.replace( 'ppom_sort_id_', '' ), 10 ) )
		.filter( ( index ) => Number.isFinite( index ) && index > 0 );
}

function applyTableRowFallbacks(
	field: ClassicFieldRow,
	index: number
): ClassicFieldRow {
	const row = document.getElementById( `ppom_sort_id_${ index }` );
	if ( ! row ) {
		return field;
	}
	return {
		...field,
		data_name:
			asString( field.data_name ) ||
			row.querySelector( '.ppom_meta_field_id' )?.textContent?.trim() ||
			'',
		type:
			asString( field.type ) ||
			row.querySelector( '.ppom_meta_field_type' )?.textContent?.trim() ||
			'',
		title:
			asString( field.title ) ||
			row
				.querySelector( '.ppom_meta_field_title' )
				?.textContent?.trim() ||
			'',
		placeholder:
			asString( field.placeholder ) ||
			row
				.querySelector( '.ppom_meta_field_plchlder' )
				?.textContent?.trim() ||
			'',
	};
}

function nextFieldIndex(): number {
	const fieldIndex = document.getElementById(
		'field_index'
	) as HTMLInputElement | null;
	const parsed = fieldIndex ? parseInt( fieldIndex.value, 10 ) : NaN;
	const maxExisting = Math.max( 0, ...currentFieldIndexes() );
	return Math.max( Number.isFinite( parsed ) ? parsed : 1, maxExisting + 1 );
}

function setNextFieldIndex( next: number ): void {
	const saveModel = getSaveModel();
	let input = document.getElementById(
		'field_index'
	) as HTMLInputElement | null;
	if ( ! input ) {
		input = createFieldIndexInput( next );
		saveModel.appendChild( input );
		return;
	}
	const parsed = parseInt( input.value || '1', 10 );
	input.value = String(
		Math.max( Number.isFinite( parsed ) ? parsed : 1, next )
	);
}

function removeTopLevelControls(
	modal: HTMLElement,
	index: number,
	key: string
): void {
	modal
		.querySelectorAll( 'input[name], select[name], textarea[name]' )
		.forEach( ( control ) => {
			const element = control as
				| HTMLInputElement
				| HTMLSelectElement
				| HTMLTextAreaElement;
			const parsed = parsePpomName( element.name );
			if (
				parsed &&
				parsed.index === index &&
				parsed.keys[ 0 ] === key
			) {
				element.remove();
			}
		} );
}

function setScalarControls(
	modal: HTMLElement,
	index: number,
	key: string,
	value: unknown
): boolean {
	const name = `ppom[${ index }][${ key }]`;
	const controls = Array.from(
		modal.querySelectorAll(
			`input[name="${ CSS.escape( name ) }"], select[name="${ CSS.escape(
				name
			) }"], textarea[name="${ CSS.escape( name ) }"]`
		)
	) as Array< HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement >;
	if ( controls.length === 0 ) {
		return false;
	}
	controls.forEach( ( control ) => {
		if (
			control instanceof HTMLInputElement &&
			control.type === 'checkbox'
		) {
			control.checked = asString( value ) === 'on' || value === true;
			return;
		}
		control.value = asString( value );
	} );
	return true;
}

function patchClassicFieldModal(
	modal: HTMLElement,
	index: number,
	field: ClassicFieldRow
): void {
	const cleanField = withoutInternalKeys( field );
	Object.entries( cleanField ).forEach( ( [ key, value ] ) => {
		if ( value === undefined ) {
			return;
		}
		value = legacyClassicValue( cleanField, key, value );
		if ( Array.isArray( value ) || isPlainObject( value ) ) {
			removeTopLevelControls( modal, index, key );
			appendHiddenInputsForIndex( modal, index, key, value );
			return;
		}
		if ( ! setScalarControls( modal, index, key, value ) ) {
			appendHiddenInputsForIndex( modal, index, key, value );
		}
	} );

	const title = asString( cleanField.title );
	const dataName = asString( cleanField.data_name );
	const heading = modal.querySelector( 'header h3' );
	if ( heading && cleanField.type ) {
		if ( heading.firstChild ) {
			heading.firstChild.textContent = asString( cleanField.type );
		} else {
			heading.prepend(
				document.createTextNode( asString( cleanField.type ) )
			);
		}
	}
	const dataNameReader = modal.querySelector( '.ppom-dataname-reader' );
	if ( dataNameReader ) {
		dataNameReader.textContent = `(${ dataName })`;
	}
	if ( title ) {
		modal.setAttribute( 'aria-label', title );
	}
}

function rewriteClassicModalIndex(
	modal: HTMLElement,
	index: number,
	type: string
): void {
	modal.id = `ppom_field_model_${ index }`;
	Array.from( modal.classList )
		.filter( ( className ) => /^ppom_sort_id_\d+$/.test( className ) )
		.forEach( ( className ) => modal.classList.remove( className ) );
	modal.classList.add( 'ppom_sort_id_' + index );
	modal.querySelectorAll( '[name]' ).forEach( ( element ) => {
		const control = element as
			| HTMLInputElement
			| HTMLSelectElement
			| HTMLTextAreaElement;
		if ( control.name ) {
			control.name = control.name.replace(
				/^ppom\[[^\]]*\]/,
				`ppom[${ index }]`
			);
		}
	} );
	modal.querySelectorAll( '[data-field-index]' ).forEach( ( element ) => {
		element.setAttribute( 'data-field-index', String( index ) );
	} );
	modal.querySelectorAll( '[data-field-no]' ).forEach( ( element ) => {
		element.setAttribute( 'data-field-no', String( index ) );
	} );
	modal.querySelectorAll( '[data-field-type]' ).forEach( ( element ) => {
		element.setAttribute( 'data-field-type', type );
	} );
}

function createClassicModalForField(
	field: ClassicFieldRow,
	index: number,
	sourceIndex?: number
): HTMLElement {
	const type = getFieldValue( field, 'type' );
	const source = sourceIndex
		? document.getElementById( `ppom_field_model_${ sourceIndex }` )
		: document.querySelector( `.ppom-field-${ CSS.escape( type ) }` );
	if ( ! source ) {
		throw new Error(
			`PPOM classic field template for "${ type }" was not found.`
		);
	}
	const modal = source.cloneNode( true ) as HTMLElement;
	modal.style.display = 'none';
	rewriteClassicModalIndex( modal, index, type );
	modal.querySelectorAll( '.ppom-add-field' ).forEach( ( element ) => {
		element.classList.remove( 'ppom-add-field' );
		element.classList.add(
			'ppom-update-field',
			'ppom-add-fields-js-action'
		);
	} );
	patchClassicFieldModal( modal, index, {
		...field,
		__classicFieldIndex: index,
	} );
	return modal;
}

function getFieldValue(
	field: ClassicFieldRow,
	key: string,
	fallback = ''
): string {
	return asString( field[ key ], fallback );
}

function getRequiredText( field: ClassicFieldRow ): string {
	const required = field.required;
	const isRequired = required === true || asString( required ) === 'on';
	const vars = (
		window as Window & {
			ppom_vars?: { i18n?: Record< string, string > };
		}
	 ).ppom_vars;

	if ( isRequired ) {
		return vars?.i18n?.yes || 'Yes';
	}
	return vars?.i18n?.no || 'No';
}

function getSelectFieldText(): string {
	const vars = (
		window as Window & {
			ppom_vars?: { i18n?: Record< string, string > };
		}
	 ).ppom_vars;
	return vars?.i18n?.selectField || 'Select field';
}

function appendCell(
	row: HTMLTableRowElement,
	tag: 'td' | 'th',
	className: string,
	child: Node
): HTMLElement {
	const cell = document.createElement( tag );
	cell.className = className;
	cell.appendChild( child );
	row.appendChild( cell );
	return cell;
}

function textSpan( className: string, text: string ): HTMLSpanElement {
	const span = document.createElement( 'span' );
	span.className = className;
	span.textContent = text;
	return span;
}

function createActionButton(
	className: string,
	iconClass: string,
	index: number,
	title: string,
	fieldType?: string
): HTMLButtonElement {
	const button = document.createElement( 'button' );
	button.type = 'button';
	button.className = `button ${ className }`;
	button.id = String( index );
	button.title = title;
	if ( fieldType ) {
		button.dataset.fieldType = fieldType;
	}
	if ( className === 'ppom-edit-field' ) {
		button.dataset.modalId = `ppom_field_model_${ index }`;
	}

	const icon = document.createElement( 'span' );
	icon.className = `dashicons ${ iconClass }`;
	icon.setAttribute( 'aria-hidden', 'true' );
	button.appendChild( icon );

	const label = document.createElement( 'span' );
	label.className = 'screen-reader-text';
	label.textContent = title;
	button.appendChild( label );

	return button;
}

function createFieldRow(
	field: ClassicFieldRow,
	index: number
): HTMLTableRowElement {
	const row = document.createElement( 'tr' );
	row.className = `row_no_${ index }`;
	row.id = `ppom_sort_id_${ index }`;

	const orderIcon = document.createElement( 'span' );
	orderIcon.className = 'ppom-sortable-handle dashicons dashicons-move';
	orderIcon.setAttribute( 'aria-hidden', 'true' );
	appendCell( row, 'td', 'order column-order', orderIcon );

	const checkboxWrapper = document.createElement( 'span' );
	const checkboxLabel = document.createElement( 'label' );
	checkboxLabel.className = 'screen-reader-text';
	checkboxLabel.htmlFor = `ppom-field-cb-${ index }`;
	checkboxLabel.textContent = getSelectFieldText();
	checkboxWrapper.appendChild( checkboxLabel );
	const checkbox = document.createElement( 'input' );
	checkbox.type = 'checkbox';
	checkbox.id = `ppom-field-cb-${ index }`;
	checkbox.value = String( index );
	checkboxWrapper.appendChild( checkbox );
	appendCell( row, 'th', 'check-column', checkboxWrapper );

	const status = getFieldValue( field, 'status', 'on' ) || 'on';
	const statusWrapper = document.createElement( 'div' );
	statusWrapper.className = 'onoffswitch';
	const statusCheckbox = document.createElement( 'input' );
	statusCheckbox.type = 'checkbox';
	statusCheckbox.className = 'onoffswitch-checkbox';
	statusCheckbox.id = `ppom-onoffswitch-${ index }`;
	statusCheckbox.tabIndex = 0;
	statusCheckbox.checked = status === 'on';
	statusWrapper.appendChild( statusCheckbox );
	const statusLabel = document.createElement( 'label' );
	statusLabel.className = 'onoffswitch-label';
	statusLabel.htmlFor = statusCheckbox.id;
	statusLabel.appendChild( textSpan( 'onoffswitch-inner', '' ) );
	statusLabel.appendChild( textSpan( 'onoffswitch-switch', '' ) );
	statusWrapper.appendChild( statusLabel );
	statusWrapper.appendChild(
		createHiddenInput( `ppom[${ index }][status]`, status )
	);
	appendCell( row, 'td', 'status column-status', statusWrapper );

	appendCell(
		row,
		'td',
		'data_name column-data_name',
		textSpan( 'ppom_meta_field_id', getFieldValue( field, 'data_name' ) )
	);
	appendCell(
		row,
		'td',
		'type column-type',
		textSpan( 'ppom_meta_field_type', getFieldValue( field, 'type' ) )
	);
	appendCell(
		row,
		'td',
		'title column-title',
		textSpan( 'ppom_meta_field_title', getFieldValue( field, 'title' ) )
	);
	appendCell(
		row,
		'td',
		'placeholder column-placeholder',
		textSpan(
			'ppom_meta_field_plchlder',
			getFieldValue( field, 'placeholder' )
		)
	);
	appendCell(
		row,
		'td',
		'required column-required',
		textSpan( 'ppom_meta_field_req', getRequiredText( field ) )
	);

	const actions = document.createElement( 'span' );
	const fieldType = getFieldValue( field, 'type' );
	actions.appendChild(
		createActionButton(
			'ppom_copy_field',
			'dashicons-admin-page',
			index,
			'Copy Field',
			fieldType
		)
	);
	actions.appendChild( document.createTextNode( ' ' ) );
	actions.appendChild(
		createActionButton(
			'ppom-edit-field',
			'dashicons-edit',
			index,
			'Edit Field'
		)
	);
	actions.appendChild( document.createTextNode( ' ' ) );
	actions.appendChild(
		createActionButton(
			'ppom-delete-field',
			'dashicons-trash',
			index,
			'Delete Field'
		)
	);
	appendCell( row, 'td', 'actions column-actions', actions );

	return row;
}

function markClassicFormDirty(): void {
	const marker = document.createElement( 'button' );
	marker.type = 'button';
	marker.className = 'ppom-add-fields-js-action';
	marker.hidden = true;
	document.body.appendChild( marker );
	marker.click();
	marker.remove();
}

export function getClassicBuilderFields(): ClassicFieldRow[] {
	return currentFieldIndexes().map( ( index ) =>
		applyTableRowFallbacks( serializeClassicFieldModal( index ), index )
	);
}

export function commitFieldToClassicForm(
	field: ClassicFieldRow,
	options: { insertAfterFieldIndex?: number } = {}
): void {
	const saveModel = getSaveModel();
	const tableBody = getTableBody();
	const existingIndex = fieldIndexFromRow( field );
	const fieldIndex = existingIndex ?? nextFieldIndex();
	const rowField = { ...field, __classicFieldIndex: fieldIndex };
	const existingModal = document.getElementById(
		`ppom_field_model_${ fieldIndex }`
	);
	const existingRow = document.getElementById(
		`ppom_sort_id_${ fieldIndex }`
	);

	if ( existingModal && existingRow ) {
		patchClassicFieldModal( existingModal, fieldIndex, rowField );
		existingRow.replaceWith( createFieldRow( rowField, fieldIndex ) );
	} else {
		const modal = createClassicModalForField(
			rowField,
			fieldIndex,
			options.insertAfterFieldIndex
		);
		const row = createFieldRow( rowField, fieldIndex );
		const sourceModal = options.insertAfterFieldIndex
			? document.getElementById(
					`ppom_field_model_${ options.insertAfterFieldIndex }`
			  )
			: null;
		const sourceRow = options.insertAfterFieldIndex
			? document.getElementById(
					`ppom_sort_id_${ options.insertAfterFieldIndex }`
			  )
			: null;
		const fieldIndexInput = document.getElementById( 'field_index' );
		if ( sourceModal ) {
			sourceModal.insertAdjacentElement( 'afterend', modal );
		} else {
			saveModel.insertBefore( modal, fieldIndexInput );
		}
		if ( sourceRow ) {
			sourceRow.insertAdjacentElement( 'afterend', row );
		} else {
			tableBody.querySelector( 'tr.no-items' )?.remove();
			tableBody.appendChild( row );
		}
		setNextFieldIndex( fieldIndex + 1 );
	}
	markClassicFormDirty();
}

/**
 * Subscribes to `.ppom-react-field-modal-open` header buttons and to per-row
 * `.ppom-edit-field` / `.ppom_copy_field` buttons in the classic field table.
 * The per-row clicks are delegated at the document level in the capture phase
 * so they run before the jQuery handlers that would otherwise open or clone
 * the legacy inline modal.
 *
 * @param opts Adapter callbacks.
 * @return Cleanup that removes listeners.
 */
export function bindPpomReactFieldModalOpenButtons(
	opts: BindPpomReactFieldModalOpenOptions
): () => void {
	const headerButtons = document.querySelectorAll(
		'.ppom-react-field-modal-open'
	);
	const headerHandlers: Array< { btn: Element; onClick: () => void } > = [];
	headerButtons.forEach( ( btn ) => {
		const onClick = () => {
			const mode = btn.getAttribute( 'data-ppom-react-mode' );
			const fromPicker = mode === 'picker';
			opts.onOpen( { entry: fromPicker ? 'picker' : 'manage' } );
		};
		btn.addEventListener( 'click', onClick );
		headerHandlers.push( { btn, onClick } );
	} );

	const onRowFieldClickCapture = ( event: Event ) => {
		const target = event.target as HTMLElement | null;
		if ( ! target || typeof target.closest !== 'function' ) {
			return;
		}
		const editBtn = target.closest(
			'.ppom-edit-field'
		) as HTMLElement | null;
		const copyBtn = target.closest(
			'.ppom_copy_field'
		) as HTMLElement | null;
		const btn = editBtn || copyBtn;
		if ( ! btn || btn.classList.contains( 'ppom-is-pro-field' ) ) {
			return;
		}
		event.preventDefault();
		event.stopImmediatePropagation();
		opts.onOpen( {
			entry: copyBtn ? 'copy' : 'manage',
			selectFieldIndex: parseRowFieldIndex( btn ),
		} );
	};
	document.addEventListener( 'click', onRowFieldClickCapture, true );

	return () => {
		headerHandlers.forEach( ( { btn, onClick } ) =>
			btn.removeEventListener( 'click', onClick )
		);
		document.removeEventListener( 'click', onRowFieldClickCapture, true );
	};
}
