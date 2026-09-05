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

function buildTextareaField( args ) {
	return buildField( 'textarea', args );
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

function buildQuantitiesField( { options = [], ...args } ) {
	return buildField( 'quantities', {
		view_control: 'simple_view',
		default_price: '',
		min_qty: '',
		max_qty: '',
		required: '',
		...args,
		options: options.map( ( option ) =>
			buildOption( option.label, option.value, {
				min: '',
				max: '',
				stock: '',
				default: '',
				weight: '',
				...option.overrides,
			} )
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

function buildDateField( {
	jqueryDp = 'on',
	dateFormats = 'mm/dd/yy',
	yearRange = 'c-10:c+10',
	minDate = '',
	maxDate = '',
	pastDates = '',
	noWeekends = '',
	firstDayOfWeek = '0',
	...args
} ) {
	return buildField( 'date', {
		...args,
		jquery_dp: jqueryDp,
		date_formats: dateFormats,
		year_range: yearRange,
		min_date: minDate,
		max_date: maxDate,
		past_dates: pastDates,
		no_weekends: noWeekends,
		first_day_of_week: firstDayOfWeek,
	} );
}

function buildTextCounterField( {
	countType = 'character',
	maxlength = '50',
	enableTextInput = '',
	textareaRow = '3',
	countPrice = '',
	countSpace = '',
	counterColor = '',
	counterBgColor = '',
	...args
} ) {
	return buildField( 'textcounter', {
		...args,
		count_type: countType,
		maxlength,
		enable_textinput: enableTextInput,
		textarea_row: textareaRow,
		count_price: countPrice,
		enabled_space: countSpace,
		counter_color: counterColor,
		counter_bg_color: counterBgColor,
	} );
}

export {
	buildCheckboxField,
	buildDateField,
	buildImageField,
	buildPalettesField,
	buildPriceMatrixField,
	buildQuantitiesField,
	buildSelectField,
	buildTextField,
	buildTextareaField,
	buildFileField,
	buildHtmlField,
	buildTextCounterField,
};
