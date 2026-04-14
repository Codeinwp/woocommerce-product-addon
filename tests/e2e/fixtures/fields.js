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

function buildSelectField( { options = [], ...args } ) {
	return buildField( 'select', {
		...args,
		options: options.map( ( option ) =>
			buildOption( option.label, option.value, option.overrides )
		),
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

export { buildCheckboxField, buildSelectField, buildTextField };
