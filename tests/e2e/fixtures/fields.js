function buildField( type, { title, dataName, ...overrides } ) {
	return {
		type,
		title,
		data_name: dataName,
		description: '',
		placeholder: '',
		error_message: '',
		width: '12',
		visibility: 'everyone',
		visibility_role: '',
		status: 'on',
		...overrides,
	};
}

function buildOption( label, value, overrides = {} ) {
	return {
		option: label,
		id: value,
		price: '',
		...overrides,
	};
}

function buildTextField( args ) {
	return buildField( 'text', args );
}

function buildImageField( args ) {
	return buildField( 'image', {
		images: [],
		...args,
	} );
}

function buildSelectField( { options = [], ...args } ) {
	return buildField( 'select', {
		...args,
		options: options.map( ( option ) =>
			buildOption( option.label, option.value, option.overrides )
		),
	} );
}

function buildPriceMatrixField( { options = [], ...args } ) {
	return buildField( 'pricematrix', {
		...args,
		discount_type: 'base',
		options: options.map( ( option ) => ( {
			option: option.option,
			price: option.price ?? '',
			label: option.label ?? '',
			id: option.id ?? '',
			isfixed: option.isfixed ?? '',
		} ) ),
	} );
}

function buildPalettesField( { options = [], ...args } ) {
	return buildField( 'palettes', {
		...args,
		options: options.map( ( option ) => ( {
			option: option.option,
			price: option.price ?? '',
			label: option.label ?? '',
			id: option.id ?? '',
			isfixed: option.isfixed ?? '',
		} ) ),
	} );
}

function buildCheckboxField( { options = [], checked = [], ...args } ) {
	return buildField( 'checkbox', {
		...args,
		checked: checked.join( '\r\n' ),
		options: options.map( ( option ) =>
			buildOption( option.label, option.value, option.overrides )
		),
	} );
}

function buildFileField( args ) {
	return buildField( 'file', {
		file_size: '5', // 5MB default
		files_allowed: '1', // Allow 1 file by default
		file_types: 'jpg,png,pdf', // Common file types
		...args,
	} );
}

function buildHtmlField( { html = '', ...args } ) {
	return buildField( 'section', {
		html,
		...args,
	} );
}

export {
	buildCheckboxField,
	buildImageField,
	buildPalettesField,
	buildPriceMatrixField,
	buildSelectField,
	buildTextField,
	buildFileField,
	buildHtmlField,
};
