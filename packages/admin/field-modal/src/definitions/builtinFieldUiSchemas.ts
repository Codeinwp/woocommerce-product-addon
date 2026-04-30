/**
 * React-owned field editor metadata for built-in definition-driven field types.
 *
 * PHP still owns save-time validation and persistence. These settings only
 * describe how the React modal renders controls for migrated slugs.
 */
import { __ } from '@wordpress/i18n';
import type { SettingMeta, SettingSchema } from './types';

const HALF_WIDTH = [ 'col-md-3', 'col-sm-12' ];

function setting(
	type: SettingMeta[ 'type' ],
	title: string,
	desc: string,
	extra: Partial< SettingMeta > = {}
): SettingMeta {
	return {
		type,
		title,
		desc,
		...extra,
	};
}

function titleSetting( desc?: string ): SettingMeta {
	return setting(
		'text',
		__( 'Title', 'woocommerce-product-addon' ),
		desc ||
			__( 'It will be shown as field label', 'woocommerce-product-addon' )
	);
}

function dataNameSetting( desc?: string ): SettingMeta {
	return setting(
		'text',
		__( 'Data name', 'woocommerce-product-addon' ),
		desc ||
			__(
				'REQUIRED: The identification name of this field, that you can insert into body email configuration. Note:Use only lowercase characters and underscores.',
				'woocommerce-product-addon'
			)
	);
}

function descriptionSetting( desc?: string ): SettingMeta {
	return setting(
		'textarea',
		__( 'Description', 'woocommerce-product-addon' ),
		desc ||
			__(
				'Small description, it will be display near name title.',
				'woocommerce-product-addon'
			)
	);
}

function placeholderSetting( desc?: string ): SettingMeta {
	return setting(
		'text',
		__( 'Placeholder', 'woocommerce-product-addon' ),
		desc || __( 'Optional.', 'woocommerce-product-addon' )
	);
}

function errorMessageSetting(): SettingMeta {
	return setting(
		'text',
		__( 'Error message', 'woocommerce-product-addon' ),
		__(
			'Insert the error message for validation.',
			'woocommerce-product-addon'
		)
	);
}

function classSetting( desc?: string ): SettingMeta {
	return setting(
		'text',
		__( 'Class', 'woocommerce-product-addon' ),
		desc ||
			__(
				'Insert an additional class(es) (separated by comma) for more personalization.',
				'woocommerce-product-addon'
			),
		{ col_classes: HALF_WIDTH }
	);
}

function widthOptions(): Record< number, string > {
	return {
		2: '2 Col',
		3: '3 Col',
		4: '4 Col',
		5: '5 Col',
		6: '6 Col',
		7: '7 Col',
		8: '8 Col',
		9: '9 Col',
		10: '10 Col',
		11: '11 Col',
		12: '12 Col',
	};
}

function widthSetting( desc?: string ): SettingMeta {
	return setting(
		'select',
		__( 'Width', 'woocommerce-product-addon' ),
		desc || __( 'Select width column.', 'woocommerce-product-addon' ),
		{
			options: widthOptions(),
			default: 12,
			col_classes: HALF_WIDTH,
		}
	);
}

function visibilityOptions(): Record< string, string > {
	return {
		everyone: __( 'Everyone', 'woocommerce-product-addon' ),
		guests: __( 'Only Guests', 'woocommerce-product-addon' ),
		members: __( 'Only Members', 'woocommerce-product-addon' ),
		roles: __( 'By Role', 'woocommerce-product-addon' ),
	};
}

function visibilitySetting(): SettingMeta {
	return setting(
		'select',
		__( 'Visibility', 'woocommerce-product-addon' ),
		__(
			'Set field visibility based on user.',
			'woocommerce-product-addon'
		),
		{
			options: visibilityOptions(),
			default: 'everyone',
			col_classes: HALF_WIDTH,
		}
	);
}

function visibilityRoleSetting(): SettingMeta {
	return setting(
		'text',
		__( 'User Roles', 'woocommerce-product-addon' ),
		__( 'Role separated by comma.', 'woocommerce-product-addon' ),
		{ hidden: true }
	);
}

function descTooltipSetting(): SettingMeta {
	return setting(
		'checkbox',
		__( 'Show tooltip', 'woocommerce-product-addon' ),
		__(
			'Show Description in Tooltip with Help Icon',
			'woocommerce-product-addon'
		),
		{ col_classes: HALF_WIDTH }
	);
}

function requiredSetting(): SettingMeta {
	return setting(
		'checkbox',
		__( 'Required', 'woocommerce-product-addon' ),
		__(
			'Select this if it must be required.',
			'woocommerce-product-addon'
		),
		{ col_classes: HALF_WIDTH }
	);
}

function logicSetting(): SettingMeta {
	return setting(
		'switch',
		__( 'Enable Conditions', 'woocommerce-product-addon' ),
		__(
			'Tick it to turn conditional logic to work below',
			'woocommerce-product-addon'
		)
	);
}

function conditionsSetting(): SettingMeta {
	return setting(
		'html-conditions',
		__( 'Conditions', 'woocommerce-product-addon' ),
		__(
			'Set rules to show or hide the field based on specific conditions',
			'woocommerce-product-addon'
		)
	);
}

function fixedFeeSetting(): SettingMeta {
	return setting(
		'checkbox',
		__( 'Fixed Fee', 'woocommerce-product-addon' ),
		__( 'Add one time fee to cart total.', 'woocommerce-product-addon' ),
		{ col_classes: HALF_WIDTH }
	);
}

function firstDayOptions(): Record< number, string > {
	return {
		0: 'Sunday',
		1: 'Monday',
		2: 'Tuesday',
		3: 'Wednesday',
		4: 'Thursday',
		5: 'Friday',
		6: 'Saturday',
	};
}

function dateFormatOptions(): Record< string, string > {
	return {
		'mm/dd/yy': 'Default - mm/dd/yyyy',
		'dd/mm/yy': 'dd/mm/yyyy',
		'yy-mm-dd': 'ISO 8601 - yy-mm-dd',
		'd M, y': 'Short - d M, y',
		'd MM, y': 'Medium - d MM, y',
		'd-MM-yy': 'Military date: d-MM-yy',
		'DD, d MM, yy': 'Full - DD, d MM, yy',
		"'day' d 'of' MM 'in the year' yy":
			"With text - 'day' d 'of' MM 'in the year' yy",
		"'Month' MM 'day' d 'in the year' yy":
			"With text - 'Month' January 'day' 7 'in the year' yy",
	};
}

function dividerStyleOptions(): Record< string, string > {
	return {
		style1: __( 'Style 1', 'woocommerce-product-addon' ),
		style2: __( 'Style 2', 'woocommerce-product-addon' ),
		style3: __( 'Style 3', 'woocommerce-product-addon' ),
		style4: __( 'Style 4', 'woocommerce-product-addon' ),
		style5: __( 'Style 5', 'woocommerce-product-addon' ),
	};
}

function dividerBorderOptions(): Record< string, string > {
	return {
		solid: __( 'Solid', 'woocommerce-product-addon' ),
		dotted: __( 'Dotted', 'woocommerce-product-addon' ),
		dashed: __( 'Dashed', 'woocommerce-product-addon' ),
		double: __( 'Double', 'woocommerce-product-addon' ),
		groove: __( 'Groove', 'woocommerce-product-addon' ),
		ridge: __( 'Ridge', 'woocommerce-product-addon' ),
		inset: __( 'Inset', 'woocommerce-product-addon' ),
		outset: __( 'Outset', 'woocommerce-product-addon' ),
	};
}

function textSettings(): SettingSchema {
	return {
		title: titleSetting(),
		data_name: dataNameSetting(),
		description: descriptionSetting(),
		placeholder: placeholderSetting(
			__( 'Optionally placeholder.', 'woocommerce-product-addon' )
		),
		error_message: errorMessageSetting(),
		maxlength: setting(
			'text',
			__( 'Max. Length', 'woocommerce-product-addon' ),
			__(
				'Max. characters allowed, leave blank for default',
				'woocommerce-product-addon'
			),
			{ col_classes: HALF_WIDTH }
		),
		minlength: setting(
			'text',
			__( 'Min. Length', 'woocommerce-product-addon' ),
			__(
				'Min. characters allowed, leave blank for default',
				'woocommerce-product-addon'
			),
			{ col_classes: HALF_WIDTH }
		),
		default_value: setting(
			'text',
			__( 'Set default value', 'woocommerce-product-addon' ),
			__(
				'Pre-defined value for text input',
				'woocommerce-product-addon'
			),
			{ col_classes: HALF_WIDTH }
		),
		price: setting(
			'text',
			__( 'Add-on Price', 'woocommerce-product-addon' ),
			__(
				'Price will be added as Add-on if text provided',
				'woocommerce-product-addon'
			),
			{ col_classes: HALF_WIDTH }
		),
		class: classSetting(),
		input_mask: setting(
			'text',
			__( 'Input Masking', 'woocommerce-product-addon' ),
			__(
				'Input masking ensures that users input data in predefined formats.',
				'woocommerce-product-addon'
			),
			{
				link: __(
					'<a href="https://docs.themeisle.com/article/2060-input-masking-documentation-for-field-configuration" target="_blank">Options</a>',
					'woocommerce-product-addon'
				),
				col_classes: HALF_WIDTH,
			}
		),
		width: widthSetting(
			__( 'Type field width in % e.g: 50%', 'woocommerce-product-addon' )
		),
		visibility: visibilitySetting(),
		visibility_role: visibilityRoleSetting(),
		onetime: setting(
			'checkbox',
			__( 'One Time Fee/Charge', 'woocommerce-product-addon' ),
			__(
				'Will not multiply with quantity',
				'woocommerce-product-addon'
			),
			{ col_classes: HALF_WIDTH }
		),
		use_regex: setting(
			'checkbox',
			__( 'Enable Regex Mask', 'woocommerce-product-addon' ),
			__(
				'Enabling this option allows the input mask field to interpret the provided pattern as a regular expression (regex).',
				'woocommerce-product-addon'
			),
			{
				link:
					'<a target="_blank" href="https://docs.themeisle.com/article/2060-input-masking-documentation-for-field-configuration">' +
					__( 'See More', 'woocommerce-product-addon' ) +
					'</a>',
				col_classes: HALF_WIDTH,
			}
		),
		desc_tooltip: descTooltipSetting(),
		required: requiredSetting(),
		logic: logicSetting(),
		conditions: conditionsSetting(),
	};
}

function textareaSettings(): SettingSchema {
	return {
		title: titleSetting(),
		data_name: dataNameSetting(),
		description: descriptionSetting(),
		placeholder: placeholderSetting(),
		error_message: errorMessageSetting(),
		default_value: setting(
			'text',
			__( 'Post ID', 'woocommerce-product-addon' ),
			__(
				'It will pull content from post. e.g: 22',
				'woocommerce-product-addon'
			)
		),
		max_length: setting(
			'text',
			__( 'Max. Length', 'woocommerce-product-addon' ),
			__(
				'Max. characters allowed, leave blank for default',
				'woocommerce-product-addon'
			),
			{ col_classes: HALF_WIDTH }
		),
		price: setting(
			'text',
			__( 'Add-on Price', 'woocommerce-product-addon' ),
			__(
				'Price will be added as Add-on if text provided',
				'woocommerce-product-addon'
			),
			{ col_classes: HALF_WIDTH }
		),
		class: classSetting(),
		width: widthSetting(
			__( 'Select width column', 'woocommerce-product-addon' )
		),
		visibility: setting(
			'select',
			__( 'Visibility', 'woocommerce-product-addon' ),
			__(
				'Set field visibility based on user.',
				'woocommerce-product-addon'
			),
			{
				options: visibilityOptions(),
				default: 'everyone',
			}
		),
		visibility_role: visibilityRoleSetting(),
		rich_editor: setting(
			'checkbox',
			__( 'Rich Editor', 'woocommerce-product-addon' ),
			__( 'Enable WordPress rich editor.', 'woocommerce-product-addon' ),
			{
				link:
					'<a target="_blank" href="https://codex.wordpress.org/Function_Reference/wp_editor">' +
					__( 'Editor', 'woocommerce-product-addon' ) +
					'</a>',
				col_classes: HALF_WIDTH,
			}
		),
		desc_tooltip: descTooltipSetting(),
		required: requiredSetting(),
		logic: logicSetting(),
		conditions: conditionsSetting(),
	};
}

function emailSettings(): SettingSchema {
	return {
		title: titleSetting(),
		data_name: dataNameSetting(),
		description: descriptionSetting(),
		placeholder: placeholderSetting(),
		error_message: errorMessageSetting(),
		class: classSetting(),
		width: widthSetting(),
		visibility: setting(
			'select',
			__( 'Visibility', 'woocommerce-product-addon' ),
			__(
				'Set field visibility based on user.',
				'woocommerce-product-addon'
			),
			{
				options: visibilityOptions(),
				default: 'everyone',
			}
		),
		visibility_role: visibilityRoleSetting(),
		desc_tooltip: descTooltipSetting(),
		required: requiredSetting(),
		logic: logicSetting(),
		conditions: conditionsSetting(),
	};
}

function numberSettings(): SettingSchema {
	return {
		title: titleSetting(),
		data_name: dataNameSetting(),
		description: descriptionSetting(),
		placeholder: placeholderSetting(
			__( 'Optionally placeholder.', 'woocommerce-product-addon' )
		),
		error_message: errorMessageSetting(),
		max: setting(
			'text',
			__( 'Max. values', 'woocommerce-product-addon' ),
			__(
				'Max. values allowed, leave blank for default',
				'woocommerce-product-addon'
			),
			{ col_classes: HALF_WIDTH }
		),
		min: setting(
			'text',
			__( 'Min. values', 'woocommerce-product-addon' ),
			__(
				'Min. values allowed, leave blank for default',
				'woocommerce-product-addon'
			),
			{ col_classes: HALF_WIDTH }
		),
		step: setting(
			'text',
			__( 'Steps', 'woocommerce-product-addon' ),
			__(
				'specified legal number intervals',
				'woocommerce-product-addon'
			),
			{ col_classes: HALF_WIDTH }
		),
		default_value: setting(
			'text',
			__( 'Set default value', 'woocommerce-product-addon' ),
			__(
				'Pre-defined value for text input',
				'woocommerce-product-addon'
			),
			{ col_classes: HALF_WIDTH }
		),
		class: classSetting(),
		width: widthSetting(),
		visibility: setting(
			'select',
			__( 'Visibility', 'woocommerce-product-addon' ),
			__(
				'Set field visibility based on user.',
				'woocommerce-product-addon'
			),
			{
				options: visibilityOptions(),
				default: 'everyone',
			}
		),
		visibility_role: visibilityRoleSetting(),
		desc_tooltip: descTooltipSetting(),
		required: requiredSetting(),
		logic: logicSetting(),
		conditions: conditionsSetting(),
	};
}

function hiddenSettings(): SettingSchema {
	return {
		title: titleSetting(
			__( 'Label will show in cart', 'woocommerce-product-addon' )
		),
		data_name: dataNameSetting(),
		field_value: setting(
			'text',
			__( 'Field value', 'woocommerce-product-addon' ),
			__(
				'you can pre-set the value of this hidden input.',
				'woocommerce-product-addon'
			)
		),
		visibility: setting(
			'select',
			__( 'Visibility', 'woocommerce-product-addon' ),
			__(
				'Set field visibility based on user.',
				'woocommerce-product-addon'
			),
			{
				options: visibilityOptions(),
				default: 'everyone',
			}
		),
		visibility_role: visibilityRoleSetting(),
		cart_display: setting(
			'checkbox',
			__( 'Show in Cart', 'woocommerce-product-addon' ),
			__( 'Display Field Value in Cart', 'woocommerce-product-addon' ),
			{ col_classes: HALF_WIDTH }
		),
	};
}

function selectSettings(): SettingSchema {
	return {
		title: titleSetting(),
		data_name: dataNameSetting(),
		description: descriptionSetting(),
		error_message: errorMessageSetting(),
		options: setting(
			'paired',
			__( 'Add options', 'woocommerce-product-addon' ),
			__(
				'Type option with price (optionally), Option ID should be unique and without spaces.',
				'woocommerce-product-addon'
			)
		),
		selected: setting(
			'options-dropdown',
			__( 'Selected option', 'woocommerce-product-addon' ),
			__(
				'Pick which option is pre-selected by default.',
				'woocommerce-product-addon'
			)
		),
		first_option: setting(
			'text',
			__( 'First option', 'woocommerce-product-addon' ),
			__(
				'Just for info e.g: Select your option.',
				'woocommerce-product-addon'
			),
			{ col_classes: HALF_WIDTH }
		),
		class: classSetting(),
		width: widthSetting(
			__( 'Select width column', 'woocommerce-product-addon' )
		),
		visibility: visibilitySetting(),
		visibility_role: visibilityRoleSetting(),
		desc_tooltip: descTooltipSetting(),
		onetime: fixedFeeSetting(),
		required: requiredSetting(),
		logic: logicSetting(),
		conditions: conditionsSetting(),
	};
}

function checkboxSettings(): SettingSchema {
	return {
		title: titleSetting(),
		data_name: dataNameSetting(),
		description: descriptionSetting(),
		error_message: errorMessageSetting(),
		options: setting(
			'paired',
			__( 'Add options', 'woocommerce-product-addon' ),
			__(
				'Type option with price (optionally)',
				'woocommerce-product-addon'
			)
		),
		class: classSetting(),
		width: widthSetting(
			__( 'Select width column.', 'woocommerce-product-addon' )
		),
		checked: setting(
			'textarea',
			__( 'Checked option(s)', 'woocommerce-product-addon' ),
			__(
				'Type option(s) name given in (Add Options) tab if you want already checked.',
				'woocommerce-product-addon'
			)
		),
		min_checked: setting(
			'text',
			__( 'Min. Checked option(s)', 'woocommerce-product-addon' ),
			__(
				'How many options can be checked by user e.g: 2. Leave blank for default.',
				'woocommerce-product-addon'
			),
			{ col_classes: HALF_WIDTH }
		),
		max_checked: setting(
			'text',
			__( 'Max. Checked option(s)', 'woocommerce-product-addon' ),
			__(
				'How many options can be checked by user e.g: 3. Leave blank for default.',
				'woocommerce-product-addon'
			),
			{ col_classes: HALF_WIDTH }
		),
		visibility: setting(
			'select',
			__( 'Visibility', 'woocommerce-product-addon' ),
			__(
				'Set field visibility based on user.',
				'woocommerce-product-addon'
			),
			{
				options: visibilityOptions(),
				default: 'everyone',
			}
		),
		visibility_role: visibilityRoleSetting(),
		desc_tooltip: descTooltipSetting(),
		onetime: fixedFeeSetting(),
		required: requiredSetting(),
		logic: logicSetting(),
		conditions: conditionsSetting(),
	};
}

function radioSettings(): SettingSchema {
	return {
		title: titleSetting(),
		data_name: dataNameSetting(),
		description: descriptionSetting(
			__(
				'Small description, it will be diplay near name title.',
				'woocommerce-product-addon'
			)
		),
		error_message: errorMessageSetting(),
		options: setting(
			'paired',
			__( 'Add options', 'woocommerce-product-addon' ),
			__(
				'Type option with price (optionally)',
				'woocommerce-product-addon'
			)
		),
		selected: setting(
			'options-dropdown',
			__( 'Selected option', 'woocommerce-product-addon' ),
			__(
				'Pick which option is pre-selected by default.',
				'woocommerce-product-addon'
			)
		),
		class: classSetting(),
		width: widthSetting(
			__( 'Select width column', 'woocommerce-product-addon' )
		),
		visibility: setting(
			'select',
			__( 'Visibility', 'woocommerce-product-addon' ),
			__(
				'Set field visibility based on user.',
				'woocommerce-product-addon'
			),
			{
				options: visibilityOptions(),
				default: 'everyone',
			}
		),
		visibility_role: visibilityRoleSetting(),
		desc_tooltip: descTooltipSetting(),
		onetime: fixedFeeSetting(),
		required: requiredSetting(),
		logic: logicSetting(),
		conditions: conditionsSetting(),
	};
}

function switcherSettings(): SettingSchema {
	return {
		title: titleSetting(),
		data_name: dataNameSetting(),
		description: descriptionSetting(
			__(
				'Small description, it will be diplay near name title.',
				'woocommerce-product-addon'
			)
		),
		error_message: errorMessageSetting(),
		options: setting(
			'paired-switch',
			__( 'Add options', 'woocommerce-product-addon' ),
			__(
				'Add options with label, price, weight, stock, option ID, and image URL or attachment ID for each choice.',
				'woocommerce-product-addon'
			)
		),
		selected: setting(
			'options-dropdown',
			__( 'Selected option', 'woocommerce-product-addon' ),
			__(
				'Pick which option is pre-selected by default.',
				'woocommerce-product-addon'
			)
		),
		marker_height: setting(
			'text',
			__( 'Marker height', 'woocommerce-product-addon' ),
			__(
				'Height of the switcher marker area (e.g. 80px).',
				'woocommerce-product-addon'
			),
			{ col_classes: HALF_WIDTH }
		),
		font_size: setting(
			'text',
			__( 'Label font size', 'woocommerce-product-addon' ),
			__(
				'Font size for option labels (e.g. 14px).',
				'woocommerce-product-addon'
			),
			{ col_classes: HALF_WIDTH }
		),
		switcher_color: setting(
			'color',
			__( 'Switcher color', 'woocommerce-product-addon' ),
			__(
				'Background color for the switcher track.',
				'woocommerce-product-addon'
			),
			{ col_classes: HALF_WIDTH }
		),
		price_size: setting(
			'text',
			__( 'Price font size', 'woocommerce-product-addon' ),
			__(
				'Font size for option prices (e.g. 12px).',
				'woocommerce-product-addon'
			),
			{ col_classes: HALF_WIDTH }
		),
		font_color: setting(
			'color',
			__( 'Font color', 'woocommerce-product-addon' ),
			__( 'Text color for option labels.', 'woocommerce-product-addon' ),
			{ col_classes: HALF_WIDTH }
		),
		marker_color: setting(
			'color',
			__( 'Marker color', 'woocommerce-product-addon' ),
			__(
				'Color of the active selection marker.',
				'woocommerce-product-addon'
			),
			{ col_classes: HALF_WIDTH }
		),
		class: classSetting(),
		width: widthSetting(
			__( 'Select width column', 'woocommerce-product-addon' )
		),
		visibility: setting(
			'select',
			__( 'Visibility', 'woocommerce-product-addon' ),
			__(
				'Set field visibility based on user.',
				'woocommerce-product-addon'
			),
			{
				options: visibilityOptions(),
				default: 'everyone',
			}
		),
		visibility_role: visibilityRoleSetting(),
		desc_tooltip: descTooltipSetting(),
		onetime: fixedFeeSetting(),
		required: requiredSetting(),
		logic: logicSetting(),
		conditions: conditionsSetting(),
	};
}

function dateSettings(): SettingSchema {
	return {
		title: titleSetting(),
		data_name: dataNameSetting(
			__(
				'REQUIRED: The identification name of this field, that you can insert into body email configuration. Note: Use only lowercase characters and underscores.',
				'woocommerce-product-addon'
			)
		),
		description: descriptionSetting(),
		placeholder: placeholderSetting(),
		error_message: errorMessageSetting(),
		class: classSetting(
			__(
				'Insert an additional class(es) (separate by comma) for more personalization.',
				'woocommerce-product-addon'
			)
		),
		width: widthSetting(),
		date_formats: setting(
			'select',
			__( 'Date format', 'woocommerce-product-addon' ),
			__(
				'[ This feature requires jQuery datePicker ] Select your preferred date format.',
				'woocommerce-product-addon'
			),
			{
				options: dateFormatOptions(),
				col_classes: HALF_WIDTH,
			}
		),
		default_value: setting(
			'text',
			__( 'Default Date', 'woocommerce-product-addon' ),
			__(
				'[ This feature requires jQuery datePicker ] The default highlighted date if the date field is blank.  Enter a date or use shortcode (examples: +10d, +17d, +1m +7d). Full dates should follow the same date format you have selected for this field.',
				'woocommerce-product-addon'
			),
			{
				link:
					'<a target="_blank" href="https://api.jqueryui.com/datepicker/#option-defaultDate">' +
					__( 'Example', 'woocommerce-product-addon' ) +
					'</a>',
				col_classes: HALF_WIDTH,
			}
		),
		min_date: setting(
			'text',
			__( 'Min Date', 'woocommerce-product-addon' ),
			__(
				'[ This feature requires jQuery datePicker ] The earliest selectable date. Enter a date or use shortcode (examples: +10d, +17d, +1m +7d). Full dates should follow the same date format you have selected for this field.',
				'woocommerce-product-addon'
			),
			{
				link:
					'<a target="_blank" href="https://api.jqueryui.com/datepicker/#option-minDate">' +
					__( 'Example', 'woocommerce-product-addon' ) +
					'</a>',
				col_classes: HALF_WIDTH,
			}
		),
		max_date: setting(
			'text',
			__( 'Max Date', 'woocommerce-product-addon' ),
			__(
				'[ This feature requires jQuery datePicker ] The maximum selectable date. Enter a date or use shortcode (examples: +10d, +17d, +1m +7d). Full dates should follow the same date format you have selected for this field.',
				'woocommerce-product-addon'
			),
			{
				link:
					'<a target="_blank" href="https://api.jqueryui.com/datepicker/#option-maxDate">' +
					__( 'Example', 'woocommerce-product-addon' ) +
					'</a>',
				col_classes: HALF_WIDTH,
			}
		),
		year_range: setting(
			'text',
			__( 'Year Range', 'woocommerce-product-addon' ),
			__(
				'[ This feature requires jQuery datePicker ] Years to allow date selections. Example: c-10:c+10.',
				'woocommerce-product-addon'
			),
			{
				link:
					'<a target="_blank" href="https://api.jqueryui.com/datepicker/#option-yearRange">' +
					__( 'Example', 'woocommerce-product-addon' ) +
					'</a>',
				col_classes: HALF_WIDTH,
			}
		),
		first_day_of_week: setting(
			'select',
			__( 'First day of week', 'woocommerce-product-addon' ),
			__(
				'[ This feature requires jQuery datePicker ] First day of the week to show on the popup calendar.',
				'woocommerce-product-addon'
			),
			{
				link:
					'<a target="_blank" href="https://api.jqueryui.com/datepicker/#option-firstDay">' +
					__( 'Example', 'woocommerce-product-addon' ) +
					'</a>',
				options: firstDayOptions(),
				default: 0,
				col_classes: HALF_WIDTH,
			}
		),
		visibility: visibilitySetting(),
		visibility_role: visibilityRoleSetting(),
		jquery_dp: setting(
			'checkbox',
			__( 'jQuery datePicker', 'woocommerce-product-addon' ),
			__(
				'Enable jQuery datePicker over HTML5 date field.',
				'woocommerce-product-addon'
			),
			{ col_classes: HALF_WIDTH }
		),
		no_weekends: setting(
			'checkbox',
			__( 'Disable Weekends', 'woocommerce-product-addon' ),
			__(
				'[ This feature requires jQuery datePicker ] Prevent display &amp; selection of weekends.',
				'woocommerce-product-addon'
			),
			{ col_classes: HALF_WIDTH }
		),
		past_dates: setting(
			'checkbox',
			__( 'Disable Past Dates', 'woocommerce-product-addon' ),
			__(
				"[ This feature requires jQuery datePicker ] Prevent selection of dates prior to today's date.",
				'woocommerce-product-addon'
			),
			{ col_classes: HALF_WIDTH }
		),
		desc_tooltip: descTooltipSetting(),
		required: requiredSetting(),
		logic: logicSetting(),
		conditions: conditionsSetting(),
	};
}

function timezoneSettings(): SettingSchema {
	return {
		title: titleSetting(),
		data_name: dataNameSetting(),
		description: descriptionSetting(),
		error_message: errorMessageSetting(),
		selected: setting(
			'text',
			__( 'Selected option', 'woocommerce-product-addon' ),
			__(
				'Type option name (given above) if you want already selected.',
				'woocommerce-product-addon'
			),
			{ col_classes: HALF_WIDTH }
		),
		first_option: setting(
			'text',
			__( 'First option', 'woocommerce-product-addon' ),
			__(
				'Just for info e.g: Select your option.',
				'woocommerce-product-addon'
			),
			{ col_classes: HALF_WIDTH }
		),
		regions: setting(
			'textarea',
			__( 'Regions', 'woocommerce-product-addon' ),
			__(
				'All,AFRICA, AMERICA, ANTARCTICA, ASIA, ATLANTIC, AUSTRALIA, EUROPE, INDIAN, PACIFIC',
				'woocommerce-product-addon'
			),
			{ default: 'All' }
		),
		class: classSetting(),
		width: widthSetting(
			__( 'Select width column', 'woocommerce-product-addon' )
		),
		visibility: visibilitySetting(),
		visibility_role: visibilityRoleSetting(),
		show_time: setting(
			'checkbox',
			__( 'Show Local Time', 'woocommerce-product-addon' ),
			__(
				'It will show current local time.',
				'woocommerce-product-addon'
			),
			{ col_classes: HALF_WIDTH }
		),
		desc_tooltip: descTooltipSetting(),
		required: requiredSetting(),
		logic: logicSetting(),
		conditions: conditionsSetting(),
	};
}

function colorSettings(): SettingSchema {
	return {
		title: titleSetting(),
		data_name: dataNameSetting(),
		description: descriptionSetting(),
		error_message: errorMessageSetting(),
		default_color: setting(
			'color',
			__( 'Default color', 'woocommerce-product-addon' ),
			__(
				'Define default color e.g: #effeff',
				'woocommerce-product-addon'
			),
			{ col_classes: HALF_WIDTH }
		),
		palettes_colors: setting(
			'text',
			__( 'Palettes colors', 'woocommerce-product-addon' ),
			__(
				'Color codes seperated by comma e.g: #125, #459, #78b',
				'woocommerce-product-addon'
			),
			{ col_classes: HALF_WIDTH }
		),
		palettes_width: setting(
			'text',
			__( 'Palettes width', 'woocommerce-product-addon' ),
			__( 'e.g: 500', 'woocommerce-product-addon' ),
			{ col_classes: HALF_WIDTH }
		),
		palettes_mode: setting(
			'select',
			__( 'Palettes mode', 'woocommerce-product-addon' ),
			__( 'Select Mode', 'woocommerce-product-addon' ),
			{
				options: {
					hsl: 'Hue, Saturation, Lightness',
					hsv: 'Hue, Saturation, Value',
				},
				col_classes: HALF_WIDTH,
			}
		),
		width: widthSetting(
			__( 'Select width column.', 'woocommerce-product-addon' )
		),
		visibility: visibilitySetting(),
		visibility_role: visibilityRoleSetting(),
		show_palettes: setting(
			'checkbox',
			__( 'Show palettes', 'woocommerce-product-addon' ),
			__(
				'Tick if need to show a group of common colors beneath the square',
				'woocommerce-product-addon'
			),
			{ col_classes: HALF_WIDTH }
		),
		show_onload: setting(
			'checkbox',
			__( 'Show on load', 'woocommerce-product-addon' ),
			__(
				'Display color picker by default, otherwise show on click',
				'woocommerce-product-addon'
			),
			{ col_classes: HALF_WIDTH }
		),
		desc_tooltip: descTooltipSetting(),
		required: requiredSetting(),
		logic: logicSetting(),
		conditions: conditionsSetting(),
	};
}

function dividerSettings(): SettingSchema {
	return {
		title: titleSetting(),
		data_name: dataNameSetting(),
		divider_styles: setting(
			'select',
			__( 'Select style', 'woocommerce-product-addon' ),
			__(
				'Select style you want to render',
				'woocommerce-product-addon'
			),
			{
				options: dividerStyleOptions(),
				col_classes: HALF_WIDTH,
			}
		),
		style1_border: setting(
			'select',
			__( 'Style border', 'woocommerce-product-addon' ),
			__( 'It will only apply on style 1.', 'woocommerce-product-addon' ),
			{
				options: dividerBorderOptions(),
				col_classes: HALF_WIDTH,
			}
		),
		divider_height: setting(
			'text',
			__( 'Divider height', 'woocommerce-product-addon' ),
			__(
				'Provide the divider height e.g: 3px.',
				'woocommerce-product-addon'
			),
			{ col_classes: HALF_WIDTH }
		),
		divider_txtsize: setting(
			'text',
			__( 'Font size', 'woocommerce-product-addon' ),
			__(
				'Provide divider text font size e.g: 18px',
				'woocommerce-product-addon'
			),
			{ col_classes: HALF_WIDTH }
		),
		divider_color: setting(
			'color',
			__( 'Divider color', 'woocommerce-product-addon' ),
			__( 'Choose the divider color.', 'woocommerce-product-addon' ),
			{ col_classes: HALF_WIDTH }
		),
		divider_txtclr: setting(
			'color',
			__( 'Divider text color', 'woocommerce-product-addon' ),
			__( 'Choose the divider text color.', 'woocommerce-product-addon' ),
			{ col_classes: HALF_WIDTH }
		),
	};
}

function leftRightOptions(): Record< string, string > {
	return {
		left: __( 'Left Side', 'woocommerce-product-addon' ),
		right: __( 'Right Side', 'woocommerce-product-addon' ),
	};
}

function topBottomOptions(): Record< string, string > {
	return {
		top: __( 'Top', 'woocommerce-product-addon' ),
		bottom: __( 'Bottom', 'woocommerce-product-addon' ),
	};
}

function variationLayoutOptions(): Record< string, string > {
	return {
		simple_view: __( 'Vertical Layout', 'woocommerce-product-addon' ),
		horizontal: __( 'Horizontal Layout', 'woocommerce-product-addon' ),
		grid: __( 'Grid Layout', 'woocommerce-product-addon' ),
	};
}

function fileSettings(): SettingSchema {
	return {
		title: titleSetting(),
		data_name: dataNameSetting(),
		description: descriptionSetting(),
		required: requiredSetting(),
		error_message: errorMessageSetting(),
		file_cost: setting(
			'text',
			__( 'File cost/price', 'woocommerce-product-addon' ),
			__( 'This will be added into cart', 'woocommerce-product-addon' ),
			{ col_classes: HALF_WIDTH }
		),
		class: classSetting(),
		width: widthSetting(),
		button_label_select: setting(
			'text',
			__( 'Button label (select files)', 'woocommerce-product-addon' ),
			__(
				'Type button label e.g: Select Files',
				'woocommerce-product-addon'
			),
			{ col_classes: HALF_WIDTH }
		),
		button_class: setting(
			'text',
			__( 'Button class', 'woocommerce-product-addon' ),
			__(
				'Type class for the file select button.',
				'woocommerce-product-addon'
			),
			{ col_classes: HALF_WIDTH }
		),
		visibility: visibilitySetting(),
		visibility_role: visibilityRoleSetting(),
		files_allowed: setting(
			'text',
			__( 'Files allowed', 'woocommerce-product-addon' ),
			__(
				'Type number of files allowed per upload by user, e.g: 3',
				'woocommerce-product-addon'
			),
			{ default: 1, col_classes: HALF_WIDTH }
		),
		file_types: setting(
			'text',
			__( 'File types', 'woocommerce-product-addon' ),
			__(
				'File types allowed separated by comma, e.g: jpg,png,pdf',
				'woocommerce-product-addon'
			),
			{ col_classes: HALF_WIDTH }
		),
		file_size: setting(
			'text',
			__( 'File size', 'woocommerce-product-addon' ),
			__(
				'Type size with units in kb|mb per file uploaded by user, e.g: 3mb',
				'woocommerce-product-addon'
			),
			{ col_classes: HALF_WIDTH }
		),
		min_img_h: setting(
			'text',
			__( 'Min. image height', 'woocommerce-product-addon' ),
			__(
				'Minimum image height in pixels.',
				'woocommerce-product-addon'
			),
			{ col_classes: HALF_WIDTH }
		),
		max_img_h: setting(
			'text',
			__( 'Max. image height', 'woocommerce-product-addon' ),
			__(
				'Maximum image height in pixels.',
				'woocommerce-product-addon'
			),
			{ col_classes: HALF_WIDTH }
		),
		min_img_w: setting(
			'text',
			__( 'Min. image width', 'woocommerce-product-addon' ),
			__( 'Minimum image width in pixels.', 'woocommerce-product-addon' ),
			{ col_classes: HALF_WIDTH }
		),
		max_img_w: setting(
			'text',
			__( 'Max. image width', 'woocommerce-product-addon' ),
			__( 'Maximum image width in pixels.', 'woocommerce-product-addon' ),
			{ col_classes: HALF_WIDTH }
		),
		img_dimension_error: setting(
			'text',
			__( 'Dimension error message', 'woocommerce-product-addon' ),
			__(
				'Error message shown when the uploaded image dimensions are invalid.',
				'woocommerce-product-addon'
			)
		),
		desc_tooltip: descTooltipSetting(),
		onetime: fixedFeeSetting(),
		logic: logicSetting(),
		conditions: conditionsSetting(),
	};
}

function daterangeSettings(): SettingSchema {
	return {
		title: titleSetting(),
		data_name: dataNameSetting(),
		description: descriptionSetting(),
		required: requiredSetting(),
		error_message: errorMessageSetting(),
		open_style: setting(
			'text',
			__( 'Open style', 'woocommerce-product-addon' ),
			__(
				'Type the date picker open style.',
				'woocommerce-product-addon'
			),
			{ col_classes: HALF_WIDTH }
		),
		date_formats: setting(
			'select',
			__( 'Date format', 'woocommerce-product-addon' ),
			__(
				'Select your preferred date format.',
				'woocommerce-product-addon'
			),
			{ options: dateFormatOptions(), col_classes: HALF_WIDTH }
		),
		tp_increment: setting(
			'text',
			__( 'Time increment', 'woocommerce-product-addon' ),
			__(
				'Minutes step for the time picker.',
				'woocommerce-product-addon'
			),
			{ col_classes: HALF_WIDTH }
		),
		start_date: setting(
			'text',
			__( 'Start date', 'woocommerce-product-addon' ),
			__(
				'Default start date for the range.',
				'woocommerce-product-addon'
			),
			{ col_classes: HALF_WIDTH }
		),
		end_date: setting(
			'text',
			__( 'End date', 'woocommerce-product-addon' ),
			__(
				'Default end date for the range.',
				'woocommerce-product-addon'
			),
			{ col_classes: HALF_WIDTH }
		),
		min_date: setting(
			'text',
			__( 'Min Date', 'woocommerce-product-addon' ),
			__( 'The earliest selectable date.', 'woocommerce-product-addon' ),
			{ col_classes: HALF_WIDTH }
		),
		max_date: setting(
			'text',
			__( 'Max Date', 'woocommerce-product-addon' ),
			__( 'The latest selectable date.', 'woocommerce-product-addon' ),
			{ col_classes: HALF_WIDTH }
		),
		class: classSetting(),
		width: widthSetting(),
		visibility: visibilitySetting(),
		visibility_role: visibilityRoleSetting(),
		time_picker: setting(
			'checkbox',
			__( 'Enable time picker', 'woocommerce-product-addon' ),
			__(
				'Show time controls for the date range.',
				'woocommerce-product-addon'
			),
			{ col_classes: HALF_WIDTH }
		),
		tp_24hours: setting(
			'checkbox',
			__( '24 hour time', 'woocommerce-product-addon' ),
			__( 'Use 24 hour time formatting.', 'woocommerce-product-addon' ),
			{ col_classes: HALF_WIDTH }
		),
		tp_seconds: setting(
			'checkbox',
			__( 'Enable seconds', 'woocommerce-product-addon' ),
			__( 'Allow users to pick seconds.', 'woocommerce-product-addon' ),
			{ col_classes: HALF_WIDTH }
		),
		drop_down: setting(
			'checkbox',
			__( 'Dropdowns', 'woocommerce-product-addon' ),
			__( 'Show month and year dropdowns.', 'woocommerce-product-addon' ),
			{ col_classes: HALF_WIDTH }
		),
		show_weeks: setting(
			'checkbox',
			__( 'Show week numbers', 'woocommerce-product-addon' ),
			__( 'Display calendar week numbers.', 'woocommerce-product-addon' ),
			{ col_classes: HALF_WIDTH }
		),
		auto_apply: setting(
			'checkbox',
			__( 'Auto apply', 'woocommerce-product-addon' ),
			__(
				'Apply the selected range automatically.',
				'woocommerce-product-addon'
			),
			{ col_classes: HALF_WIDTH }
		),
		desc_tooltip: descTooltipSetting(),
		logic: logicSetting(),
		conditions: conditionsSetting(),
	};
}

function sectionSettings(): SettingSchema {
	return {
		data_name: dataNameSetting(),
		description: descriptionSetting(),
		html: setting(
			'textarea',
			__( 'HTML content', 'woocommerce-product-addon' ),
			__(
				'Custom HTML displayed for this section.',
				'woocommerce-product-addon'
			)
		),
		width: widthSetting(),
		visibility: visibilitySetting(),
		visibility_role: visibilityRoleSetting(),
		desc_tooltip: descTooltipSetting(),
		cart_display: setting(
			'checkbox',
			__( 'Show in Cart', 'woocommerce-product-addon' ),
			__(
				'Display the section content in cart.',
				'woocommerce-product-addon'
			),
			{ col_classes: HALF_WIDTH }
		),
		logic: logicSetting(),
		conditions: conditionsSetting(),
	};
}

function measureSettings(): SettingSchema {
	return {
		title: titleSetting(),
		data_name: dataNameSetting(),
		description: descriptionSetting(),
		required: requiredSetting(),
		default_value: setting(
			'text',
			__( 'Default value', 'woocommerce-product-addon' ),
			__( 'Predefined starting value.', 'woocommerce-product-addon' ),
			{ col_classes: HALF_WIDTH }
		),
		'price-multiplier': setting(
			'text',
			__( 'Price multiplier', 'woocommerce-product-addon' ),
			__(
				'Multiply the price by the entered amount.',
				'woocommerce-product-addon'
			),
			{ col_classes: HALF_WIDTH }
		),
		price: setting(
			'text',
			__( 'Add-on Price', 'woocommerce-product-addon' ),
			__(
				'Base price added for this measurement field.',
				'woocommerce-product-addon'
			),
			{ col_classes: HALF_WIDTH }
		),
		error_message: errorMessageSetting(),
		max: setting(
			'text',
			__( 'Max. value', 'woocommerce-product-addon' ),
			__( 'Maximum allowed value.', 'woocommerce-product-addon' ),
			{ col_classes: HALF_WIDTH }
		),
		min: setting(
			'text',
			__( 'Min. value', 'woocommerce-product-addon' ),
			__( 'Minimum allowed value.', 'woocommerce-product-addon' ),
			{ col_classes: HALF_WIDTH }
		),
		step: setting(
			'text',
			__( 'Steps', 'woocommerce-product-addon' ),
			__(
				'Specified legal number intervals.',
				'woocommerce-product-addon'
			),
			{ col_classes: HALF_WIDTH }
		),
		class: classSetting(),
		width: widthSetting(),
		visibility: visibilitySetting(),
		visibility_role: visibilityRoleSetting(),
		desc_tooltip: descTooltipSetting(),
		logic: logicSetting(),
		conditions: conditionsSetting(),
	};
}

function phoneSettings(): SettingSchema {
	return {
		title: titleSetting(),
		data_name: dataNameSetting(),
		description: descriptionSetting(),
		placeholder: placeholderSetting(),
		required: requiredSetting(),
		default_country: setting(
			'text',
			__( 'Default country', 'woocommerce-product-addon' ),
			__(
				'Country code used when the field first loads.',
				'woocommerce-product-addon'
			),
			{ col_classes: HALF_WIDTH }
		),
		error_message: errorMessageSetting(),
		class: classSetting(),
		width: widthSetting(),
		visibility: visibilitySetting(),
		visibility_role: visibilityRoleSetting(),
		enable_search: setting(
			'checkbox',
			__( 'Enable search', 'woocommerce-product-addon' ),
			__(
				'Allow searching in the country picker.',
				'woocommerce-product-addon'
			),
			{ col_classes: HALF_WIDTH }
		),
		enable_material: setting(
			'checkbox',
			__( 'Enable material style', 'woocommerce-product-addon' ),
			__(
				'Use the material theme for the phone field.',
				'woocommerce-product-addon'
			),
			{ col_classes: HALF_WIDTH }
		),
		desc_tooltip: descTooltipSetting(),
		logic: logicSetting(),
		conditions: conditionsSetting(),
	};
}

function superlistSettings(): SettingSchema {
	return {
		title: titleSetting(),
		data_name: dataNameSetting(),
		description: descriptionSetting(),
		required: requiredSetting(),
		listoptions: setting(
			'textarea',
			__( 'List options', 'woocommerce-product-addon' ),
			__(
				'One option per line or CSV entry.',
				'woocommerce-product-addon'
			)
		),
		option_exclude: setting(
			'textarea',
			__( 'Exclude options', 'woocommerce-product-addon' ),
			__(
				'Options that should be hidden from the list.',
				'woocommerce-product-addon'
			)
		),
		selected: setting(
			'text',
			__( 'Selected option', 'woocommerce-product-addon' ),
			__( 'Default selected list value.', 'woocommerce-product-addon' )
		),
		error_message: errorMessageSetting(),
		width: widthSetting(),
		visibility: visibilitySetting(),
		visibility_role: visibilityRoleSetting(),
		desc_tooltip: descTooltipSetting(),
		logic: logicSetting(),
		conditions: conditionsSetting(),
	};
}

function texterSettings(): SettingSchema {
	return {
		title: titleSetting(),
		data_name: dataNameSetting(),
		description: descriptionSetting(),
		post_id: setting(
			'select',
			__( 'Personalization preview', 'woocommerce-product-addon' ),
			__(
				'Select the name of Personalization preview that you want your users to use.',
				'woocommerce-product-addon'
			),
			{
				options: {
					'': __( 'Select image', 'woocommerce-product-addon' ),
				},
				link: '<a href="post-new.php?post_type=nm_ppom_texter" target="_blank">Add new one</a>',
			}
		),
		button_title: setting(
			'text',
			__( 'Button label', 'woocommerce-product-addon' ),
			__(
				'Text displayed on the preview button.',
				'woocommerce-product-addon'
			),
			{ col_classes: HALF_WIDTH }
		),
		btn_color: setting(
			'color',
			__( 'Button text color', 'woocommerce-product-addon' ),
			__( 'Choose the button text color.', 'woocommerce-product-addon' ),
			{ col_classes: HALF_WIDTH }
		),
		btn_bg_color: setting(
			'color',
			__( 'Button background color', 'woocommerce-product-addon' ),
			__(
				'Choose the button background color.',
				'woocommerce-product-addon'
			),
			{ col_classes: HALF_WIDTH }
		),
		width: widthSetting(),
		visibility: visibilitySetting(),
		visibility_role: visibilityRoleSetting(),
		alignment: setting(
			'select',
			__( 'Alignment', 'woocommerce-product-addon' ),
			__(
				'Default text alignment for the preview.',
				'woocommerce-product-addon'
			),
			{
				options: {
					left: __( 'Left', 'woocommerce-product-addon' ),
					center: __( 'Center', 'woocommerce-product-addon' ),
					right: __( 'Right', 'woocommerce-product-addon' ),
				},
				col_classes: HALF_WIDTH,
			}
		),
		font_size: setting(
			'text',
			__( 'Font size', 'woocommerce-product-addon' ),
			__( 'Preview font size, e.g: 18px.', 'woocommerce-product-addon' ),
			{ col_classes: HALF_WIDTH }
		),
		font_family: setting(
			'text',
			__( 'Font family', 'woocommerce-product-addon' ),
			__(
				'Default font family for the preview text.',
				'woocommerce-product-addon'
			),
			{ col_classes: HALF_WIDTH }
		),
		font_color: setting(
			'color',
			__( 'Font color', 'woocommerce-product-addon' ),
			__(
				'Default font color for the preview text.',
				'woocommerce-product-addon'
			),
			{ col_classes: HALF_WIDTH }
		),
		desc_tooltip: descTooltipSetting(),
		logic: logicSetting(),
		conditions: conditionsSetting(),
	};
}

function domainSettings(): SettingSchema {
	return {
		title: titleSetting(),
		data_name: dataNameSetting(),
		description: descriptionSetting(),
		placeholder: placeholderSetting(),
		required: requiredSetting(),
		available_message: setting(
			'text',
			__( 'Available message', 'woocommerce-product-addon' ),
			__(
				'Message shown when the domain is available.',
				'woocommerce-product-addon'
			)
		),
		notavailable_message: setting(
			'text',
			__( 'Unavailable message', 'woocommerce-product-addon' ),
			__(
				'Message shown when the domain is unavailable.',
				'woocommerce-product-addon'
			)
		),
		button_label: setting(
			'text',
			__( 'Button label', 'woocommerce-product-addon' ),
			__(
				'Label used for the availability check button.',
				'woocommerce-product-addon'
			),
			{ col_classes: HALF_WIDTH }
		),
		button_class: setting(
			'text',
			__( 'Button class', 'woocommerce-product-addon' ),
			__(
				'Additional classes for the check button.',
				'woocommerce-product-addon'
			),
			{ col_classes: HALF_WIDTH }
		),
		width: widthSetting(),
		logic: logicSetting(),
		conditions: conditionsSetting(),
	};
}

function collapseSettings(): SettingSchema {
	return {
		title: titleSetting(),
		data_name: dataNameSetting(),
		collapse_type: setting(
			'text',
			__( 'Collapse type', 'woocommerce-product-addon' ),
			__(
				'Style or type identifier for the collapsible section.',
				'woocommerce-product-addon'
			),
			{ col_classes: HALF_WIDTH }
		),
		default_open: setting(
			'checkbox',
			__( 'Open by default', 'woocommerce-product-addon' ),
			__(
				'Expand the section when the page first loads.',
				'woocommerce-product-addon'
			),
			{ col_classes: HALF_WIDTH }
		),
		logic: logicSetting(),
		conditions: conditionsSetting(),
	};
}

function quantityOptionSettings(): SettingSchema {
	return {
		title: titleSetting(),
		data_name: dataNameSetting(),
		description: descriptionSetting(),
		placeholder: placeholderSetting(),
		required: requiredSetting(),
		unit_price: setting(
			'text',
			__( 'Unit price', 'woocommerce-product-addon' ),
			__(
				'Price applied per quantity step.',
				'woocommerce-product-addon'
			),
			{ col_classes: HALF_WIDTH }
		),
		default_value: setting(
			'text',
			__( 'Default value', 'woocommerce-product-addon' ),
			__(
				'Predefined quantity for the field.',
				'woocommerce-product-addon'
			),
			{ col_classes: HALF_WIDTH }
		),
		error_message: errorMessageSetting(),
		min: setting(
			'text',
			__( 'Min. value', 'woocommerce-product-addon' ),
			__( 'Minimum allowed quantity.', 'woocommerce-product-addon' ),
			{ col_classes: HALF_WIDTH }
		),
		max: setting(
			'text',
			__( 'Max. value', 'woocommerce-product-addon' ),
			__( 'Maximum allowed quantity.', 'woocommerce-product-addon' ),
			{ col_classes: HALF_WIDTH }
		),
		step: setting(
			'text',
			__( 'Step', 'woocommerce-product-addon' ),
			__(
				'Step interval for the quantity input.',
				'woocommerce-product-addon'
			),
			{ col_classes: HALF_WIDTH }
		),
		class: classSetting(),
		width: widthSetting(),
		visibility: visibilitySetting(),
		visibility_role: visibilityRoleSetting(),
		desc_tooltip: descTooltipSetting(),
		onetime: fixedFeeSetting(),
		logic: logicSetting(),
		conditions: conditionsSetting(),
	};
}

function qtyPackSettings(): SettingSchema {
	return {
		title: titleSetting(),
		data_name: dataNameSetting(),
		description: descriptionSetting(),
		options: setting(
			'paired-quantity',
			__( 'Add options', 'woocommerce-product-addon' ),
			__(
				'Type option with quantity and price information.',
				'woocommerce-product-addon'
			)
		),
		default_price: setting(
			'text',
			__( 'Default price', 'woocommerce-product-addon' ),
			__(
				'Fallback price when an option has no explicit value.',
				'woocommerce-product-addon'
			),
			{ col_classes: HALF_WIDTH }
		),
		pack_size: setting(
			'text',
			__( 'Pack size', 'woocommerce-product-addon' ),
			__(
				'Default quantity size for the pack.',
				'woocommerce-product-addon'
			),
			{ col_classes: HALF_WIDTH }
		),
		packsize_message: setting(
			'text',
			__( 'Pack size message', 'woocommerce-product-addon' ),
			__(
				'Helper text shown next to the pack size control.',
				'woocommerce-product-addon'
			)
		),
		width: widthSetting(),
		visibility: visibilitySetting(),
		visibility_role: visibilityRoleSetting(),
		desc_tooltip: descTooltipSetting(),
		logic: logicSetting(),
		conditions: conditionsSetting(),
	};
}

function chainedSettings(): SettingSchema {
	return {
		title: titleSetting(),
		data_name: dataNameSetting(),
		description: descriptionSetting(),
		error_message: errorMessageSetting(),
		options: setting(
			'chained_options',
			__( 'Add options', 'woocommerce-product-addon' ),
			__(
				'Add parent and child options for the chained select.',
				'woocommerce-product-addon'
			)
		),
		selected: setting(
			'text',
			__( 'Selected option', 'woocommerce-product-addon' ),
			__(
				'Type option name given in (Add Options) tab if you want already selected.',
				'woocommerce-product-addon'
			)
		),
		first_option: setting(
			'text',
			__( 'First option', 'woocommerce-product-addon' ),
			__(
				'Just for info e.g: Select your option.',
				'woocommerce-product-addon'
			),
			{ col_classes: HALF_WIDTH }
		),
		class: classSetting(),
		width: widthSetting(
			__( 'Select width column', 'woocommerce-product-addon' )
		),
		visibility: visibilitySetting(),
		visibility_role: visibilityRoleSetting(),
		desc_tooltip: descTooltipSetting(),
		onetime: fixedFeeSetting(),
		required: requiredSetting(),
		logic: logicSetting(),
		conditions: conditionsSetting(),
	};
}

function audioSettings(): SettingSchema {
	return {
		title: titleSetting(),
		data_name: dataNameSetting(),
		description: descriptionSetting(),
		audio: setting(
			'pre-audios',
			__( 'Select Audio/Video', 'woocommerce-product-addon' ),
			__(
				'Select audio or video from media library.',
				'woocommerce-product-addon'
			)
		),
		error_message: errorMessageSetting(),
		class: classSetting(),
		width: widthSetting(),
		visibility: visibilitySetting(),
		visibility_role: visibilityRoleSetting(),
		desc_tooltip: descTooltipSetting(),
		required: requiredSetting(),
		multiple_allowed: setting(
			'checkbox',
			__( 'Multiple selection?', 'woocommerce-product-addon' ),
			__(
				'Allow users to select more than one audio or video item.',
				'woocommerce-product-addon'
			),
			{ col_classes: HALF_WIDTH }
		),
		logic: logicSetting(),
		conditions: conditionsSetting(),
	};
}

function bulkQuantitySettings(): SettingSchema {
	return {
		title: titleSetting(),
		data_name: dataNameSetting(),
		description: descriptionSetting(),
		options: setting(
			'bulk-quantity',
			__( 'Bulk Quantity', 'woocommerce-product-addon' ),
			__( 'Type quantity range with price.', 'woocommerce-product-addon' )
		),
		fixed_prices: setting(
			'textarea',
			__( 'Fixed Prices', 'woocommerce-product-addon' ),
			__(
				'Variation name with price e.g: color | 50',
				'woocommerce-product-addon'
			)
		),
		label_quantity: setting(
			'text',
			__( 'Quantity Label', 'woocommerce-product-addon' ),
			__(
				'Label used for quantity. Default: Quantity',
				'woocommerce-product-addon'
			)
		),
		label_baseprice: setting(
			'text',
			__( 'Base Price Label', 'woocommerce-product-addon' ),
			__(
				'Label used for base price. Default: Base Price',
				'woocommerce-product-addon'
			)
		),
		label_total: setting(
			'text',
			__( 'Total Price Label', 'woocommerce-product-addon' ),
			__(
				'Label used for total price. Default: Total',
				'woocommerce-product-addon'
			)
		),
		label_fixed: setting(
			'text',
			__( 'Fixed Price Label', 'woocommerce-product-addon' ),
			__(
				'Label used for fixed price. Default: Fixed',
				'woocommerce-product-addon'
			)
		),
		hide_baseprice: setting(
			'checkbox',
			__( 'Hide Base Price', 'woocommerce-product-addon' ),
			__( 'Hide the base price row.', 'woocommerce-product-addon' ),
			{ col_classes: HALF_WIDTH }
		),
		show_pricerange: setting(
			'checkbox',
			__( 'Show price range', 'woocommerce-product-addon' ),
			__(
				'Show the calculated price range as the product price.',
				'woocommerce-product-addon'
			),
			{ col_classes: HALF_WIDTH }
		),
		logic: logicSetting(),
		conditions: conditionsSetting(),
	};
}

function cropperSettings(): SettingSchema {
	return {
		title: titleSetting(),
		data_name: dataNameSetting(),
		description: descriptionSetting(),
		options: setting(
			'paired-cropper',
			__( 'Viewport Size', 'woocommerce-product-addon' ),
			__( 'Add cropper viewport options.', 'woocommerce-product-addon' )
		),
		error_message: errorMessageSetting(),
		file_cost: setting(
			'text',
			__( 'File cost/price', 'woocommerce-product-addon' ),
			__( 'This will be added into cart', 'woocommerce-product-addon' ),
			{ col_classes: HALF_WIDTH }
		),
		selected: setting(
			'text',
			__( 'Selected option', 'woocommerce-product-addon' ),
			__(
				'Type option name if you want it selected by default.',
				'woocommerce-product-addon'
			),
			{ col_classes: HALF_WIDTH }
		),
		first_option: setting(
			'text',
			__( 'First option', 'woocommerce-product-addon' ),
			__(
				'Just for info e.g: Select your option.',
				'woocommerce-product-addon'
			),
			{ col_classes: HALF_WIDTH }
		),
		class: classSetting(),
		width: widthSetting(),
		button_label_select: setting(
			'text',
			__( 'Button label (select files)', 'woocommerce-product-addon' ),
			__(
				'Type button label e.g: Select Photos',
				'woocommerce-product-addon'
			),
			{ col_classes: HALF_WIDTH }
		),
		button_class: setting(
			'text',
			__( 'Button class', 'woocommerce-product-addon' ),
			__(
				'Type class for both select and upload buttons.',
				'woocommerce-product-addon'
			),
			{ col_classes: HALF_WIDTH }
		),
		files_allowed: setting(
			'text',
			__( 'Files allowed', 'woocommerce-product-addon' ),
			__(
				'Type number of files allowed per upload by user, e.g: 3',
				'woocommerce-product-addon'
			),
			{ default: 1, col_classes: HALF_WIDTH }
		),
		file_types: setting(
			'text',
			__( 'Image types', 'woocommerce-product-addon' ),
			__(
				'Image types allowed separated by comma, e.g: jpg,png',
				'woocommerce-product-addon'
			),
			{ default: 'jpg,png', col_classes: HALF_WIDTH }
		),
		file_size: setting(
			'text',
			__( 'Image size', 'woocommerce-product-addon' ),
			__(
				'Type size with units in kb|mb per uploaded file, e.g: 3mb',
				'woocommerce-product-addon'
			),
			{ default: '1mb', col_classes: HALF_WIDTH }
		),
		visibility: visibilitySetting(),
		visibility_role: visibilityRoleSetting(),
		viewport_type: setting(
			'select',
			__( 'Viewport type', 'woocommerce-product-addon' ),
			__( 'Select square or circle.', 'woocommerce-product-addon' ),
			{
				options: {
					square: __( 'Square', 'woocommerce-product-addon' ),
					circle: __( 'Circle', 'woocommerce-product-addon' ),
				},
				col_classes: HALF_WIDTH,
			}
		),
		boundary: setting(
			'text',
			__( 'Boundary height,width', 'woocommerce-product-addon' ),
			__(
				'Separated by comma h,w e.g: 200,200',
				'woocommerce-product-addon'
			),
			{ col_classes: HALF_WIDTH }
		),
		enforce_boundary: setting(
			'checkbox',
			__( 'Enforce Boundary', 'woocommerce-product-addon' ),
			__(
				'Restrict zoom so the image cannot be smaller than the viewport.',
				'woocommerce-product-addon'
			),
			{ col_classes: HALF_WIDTH }
		),
		resize: setting(
			'checkbox',
			__( 'Allow Resize', 'woocommerce-product-addon' ),
			__( 'Show cropping resize handles.', 'woocommerce-product-addon' ),
			{ col_classes: HALF_WIDTH }
		),
		enable_zoom: setting(
			'checkbox',
			__( 'Enable Zoom', 'woocommerce-product-addon' ),
			__( 'Enable zooming functionality.', 'woocommerce-product-addon' ),
			{ col_classes: HALF_WIDTH }
		),
		show_zoomer: setting(
			'checkbox',
			__( 'Show Zoomer', 'woocommerce-product-addon' ),
			__( 'Show the zoom slider.', 'woocommerce-product-addon' ),
			{ col_classes: HALF_WIDTH }
		),
		enable_exif: setting(
			'checkbox',
			__( 'Enable Exif', 'woocommerce-product-addon' ),
			__(
				'Read EXIF orientation data from the uploaded image.',
				'woocommerce-product-addon'
			),
			{ col_classes: HALF_WIDTH }
		),
		onetime_taxable: setting(
			'checkbox',
			__( 'Fee Taxable?', 'woocommerce-product-addon' ),
			__(
				'Calculate tax for the fixed fee.',
				'woocommerce-product-addon'
			),
			{ col_classes: HALF_WIDTH }
		),
		desc_tooltip: descTooltipSetting(),
		required: requiredSetting(),
		logic: logicSetting(),
		conditions: conditionsSetting(),
	};
}

function emojisSettings(): SettingSchema {
	return {
		title: titleSetting(),
		data_name: dataNameSetting(),
		description: descriptionSetting(),
		error_message: errorMessageSetting(),
		options: setting(
			'paired-palettes',
			__( 'Add colors', 'woocommerce-product-addon' ),
			__(
				'Type color code with price. To write a label, use #colorcode - White.',
				'woocommerce-product-addon'
			)
		),
		class: classSetting(),
		max_selected: setting(
			'number',
			__( 'Max selected', 'woocommerce-product-addon' ),
			__(
				'Max. selected, leave blank for default.',
				'woocommerce-product-addon'
			),
			{ col_classes: HALF_WIDTH }
		),
		width: widthSetting(),
		emojis_display_type: setting(
			'select',
			__( 'Input Type', 'woocommerce-product-addon' ),
			__( 'Set input type.', 'woocommerce-product-addon' ),
			{
				options: {
					textarea: __( 'Textarea', 'woocommerce-product-addon' ),
					text: __( 'Text', 'woocommerce-product-addon' ),
					standalone: __( 'Dropdown', 'woocommerce-product-addon' ),
				},
				col_classes: HALF_WIDTH,
			}
		),
		search_placeholder: setting(
			'text',
			__( 'Search Placeholder', 'woocommerce-product-addon' ),
			__( 'Set search placeholder.', 'woocommerce-product-addon' ),
			{ col_classes: HALF_WIDTH }
		),
		placeholder: placeholderSetting(
			__( 'Set placeholder.', 'woocommerce-product-addon' )
		),
		filters_position: setting(
			'select',
			__( 'Filters Position', 'woocommerce-product-addon' ),
			__( 'Set filters position.', 'woocommerce-product-addon' ),
			{ options: topBottomOptions(), col_classes: HALF_WIDTH }
		),
		search_position: setting(
			'select',
			__( 'Search Position', 'woocommerce-product-addon' ),
			__( 'Set search position.', 'woocommerce-product-addon' ),
			{ options: topBottomOptions(), col_classes: HALF_WIDTH }
		),
		picker_position: setting(
			'select',
			__( 'Emojis Picker Position', 'woocommerce-product-addon' ),
			__( 'Set emojis picker position.', 'woocommerce-product-addon' ),
			{
				options: {
					top: __( 'Top', 'woocommerce-product-addon' ),
					right: __( 'Right', 'woocommerce-product-addon' ),
					bottom: __( 'Bottom', 'woocommerce-product-addon' ),
				},
				col_classes: HALF_WIDTH,
			}
		),
		tones_Style: setting(
			'select',
			__( 'Tones Style', 'woocommerce-product-addon' ),
			__( 'Set tones style.', 'woocommerce-product-addon' ),
			{
				options: {
					bullet: __( 'Bullet', 'woocommerce-product-addon' ),
					radio: __( 'Radio', 'woocommerce-product-addon' ),
					square: __( 'Square', 'woocommerce-product-addon' ),
					checkbox: __( 'Checkbox', 'woocommerce-product-addon' ),
				},
				col_classes: HALF_WIDTH,
			}
		),
		recent_emojis: setting(
			'checkbox',
			__( 'Enable Recent Emojis', 'woocommerce-product-addon' ),
			__( 'Enable recent emojis.', 'woocommerce-product-addon' ),
			{ col_classes: HALF_WIDTH }
		),
		tones: setting(
			'checkbox',
			__( 'Enable Tones', 'woocommerce-product-addon' ),
			__( 'Enable tones.', 'woocommerce-product-addon' ),
			{ col_classes: HALF_WIDTH }
		),
		search: setting(
			'checkbox',
			__( 'Enable Search', 'woocommerce-product-addon' ),
			__( 'Enable search.', 'woocommerce-product-addon' ),
			{ col_classes: HALF_WIDTH }
		),
		visibility: visibilitySetting(),
		visibility_role: visibilityRoleSetting(),
		desc_tooltip: descTooltipSetting(),
		required: requiredSetting(),
		onetime: fixedFeeSetting(),
		onetime_taxable: setting(
			'checkbox',
			__( 'Fixed Fee Taxable?', 'woocommerce-product-addon' ),
			__( 'Calculate tax for fixed fee.', 'woocommerce-product-addon' ),
			{ col_classes: HALF_WIDTH }
		),
		logic: logicSetting(),
		conditions: conditionsSetting(),
	};
}

function fixedPriceSettings(): SettingSchema {
	return {
		title: titleSetting(),
		data_name: dataNameSetting(),
		description: descriptionSetting(),
		options: setting(
			'paired',
			__( 'Quantity', 'woocommerce-product-addon' ),
			__( 'Quantity and fixed price rows.', 'woocommerce-product-addon' ),
			{
				placeholders: [ 'Quantity e.g 1000', 'Fixed Price' ],
				types: [ 'number', 'number' ],
			}
		),
		first_option: setting(
			'text',
			__( 'First option', 'woocommerce-product-addon' ),
			__( 'Used for select view types.', 'woocommerce-product-addon' ),
			{ col_classes: HALF_WIDTH }
		),
		unit_plural: setting(
			'text',
			__( 'Unit plural', 'woocommerce-product-addon' ),
			__(
				'Enter the plural unit label, e.g: pieces.',
				'woocommerce-product-addon'
			),
			{ col_classes: HALF_WIDTH }
		),
		unit_single: setting(
			'text',
			__( 'Unit single', 'woocommerce-product-addon' ),
			__(
				'Enter the singular unit label, e.g: piece.',
				'woocommerce-product-addon'
			),
			{ col_classes: HALF_WIDTH }
		),
		view_type: setting(
			'select',
			__( 'Select type', 'woocommerce-product-addon' ),
			__( 'Select the field presentation.', 'woocommerce-product-addon' ),
			{
				options: {
					radio: __( 'Radio', 'woocommerce-product-addon' ),
					select: __( 'Select', 'woocommerce-product-addon' ),
				},
				col_classes: HALF_WIDTH,
			}
		),
		decimal_place: setting(
			'text',
			__( 'Decimal Places', 'woocommerce-product-addon' ),
			__(
				'Use larger decimal precision for very small prices.',
				'woocommerce-product-addon'
			),
			{ col_classes: HALF_WIDTH }
		),
		class: classSetting(),
		width: widthSetting(),
		logic: logicSetting(),
		conditions: conditionsSetting(),
	};
}

function imageSettings(): SettingSchema {
	return {
		title: titleSetting(),
		data_name: dataNameSetting(),
		description: descriptionSetting(),
		images: setting(
			'pre-images',
			__( 'Select images', 'woocommerce-product-addon' ),
			__(
				'Select images from media library.',
				'woocommerce-product-addon'
			)
		),
		error_message: errorMessageSetting(),
		selected: setting(
			'text',
			__( 'Selected image', 'woocommerce-product-addon' ),
			__(
				'Type option title if you want it already selected.',
				'woocommerce-product-addon'
			),
			{ col_classes: HALF_WIDTH }
		),
		selected_img_bordercolor: setting(
			'color',
			__( 'Selected Image Border Color', 'woocommerce-product-addon' ),
			__(
				'Change selected images border color, e.g: #fff',
				'woocommerce-product-addon'
			),
			{ col_classes: HALF_WIDTH }
		),
		image_width: setting(
			'text',
			__( 'Image Width', 'woocommerce-product-addon' ),
			__(
				'Change image width e.g: 50px or 50%.',
				'woocommerce-product-addon'
			),
			{ col_classes: HALF_WIDTH }
		),
		image_height: setting(
			'text',
			__( 'Image Height', 'woocommerce-product-addon' ),
			__(
				'Change image height e.g: 50px or 50%.',
				'woocommerce-product-addon'
			),
			{ col_classes: HALF_WIDTH }
		),
		class: classSetting(),
		width: widthSetting(),
		visibility: visibilitySetting(),
		visibility_role: visibilityRoleSetting(),
		legacy_view: setting(
			'checkbox',
			__( 'Enable legacy view', 'woocommerce-product-addon' ),
			__(
				'Turn on the old boxes view for images.',
				'woocommerce-product-addon'
			),
			{ col_classes: HALF_WIDTH }
		),
		show_popup: setting(
			'checkbox',
			__( 'Popup', 'woocommerce-product-addon' ),
			__( 'Show big image on hover.', 'woocommerce-product-addon' ),
			{ col_classes: HALF_WIDTH }
		),
		desc_tooltip: descTooltipSetting(),
		required: requiredSetting(),
		multiple_allowed: setting(
			'checkbox',
			__( 'Multiple selections?', 'woocommerce-product-addon' ),
			__(
				'Allow users to select more than one image.',
				'woocommerce-product-addon'
			),
			{ col_classes: HALF_WIDTH }
		),
		min_checked: setting(
			'text',
			__( 'Min. Image Select', 'woocommerce-product-addon' ),
			__(
				'How many images can be checked by user, e.g: 2.',
				'woocommerce-product-addon'
			),
			{ col_classes: HALF_WIDTH }
		),
		max_checked: setting(
			'text',
			__( 'Max. Image Select', 'woocommerce-product-addon' ),
			__(
				'How many images can be checked by user, e.g: 3.',
				'woocommerce-product-addon'
			),
			{ col_classes: HALF_WIDTH }
		),
		logic: logicSetting(),
		conditions: conditionsSetting(),
	};
}

function imageselectSettings(): SettingSchema {
	return {
		title: setting(
			'text',
			__( 'Titles', 'woocommerce-product-addon' ),
			__( 'It will be shown as field label', 'woocommerce-product-addon' )
		),
		data_name: dataNameSetting(),
		description: setting(
			'textarea',
			__( 'Description', 'woocommerce-product-addon' ),
			__(
				'Type description, it will display under the section heading.',
				'woocommerce-product-addon'
			)
		),
		required: requiredSetting(),
		images: setting(
			'imageselect',
			__( 'Select images', 'woocommerce-product-addon' ),
			__(
				'Select images from media library.',
				'woocommerce-product-addon'
			)
		),
		error_message: errorMessageSetting(),
		dropdown_height: setting(
			'text',
			__( 'DropDown Height', 'woocommerce-product-addon' ),
			__(
				'Set the dropdown height e.g: 300px.',
				'woocommerce-product-addon'
			),
			{ col_classes: HALF_WIDTH }
		),
		image_width: setting(
			'text',
			__( 'Images Width', 'woocommerce-product-addon' ),
			__(
				'Change the images width in px or %, e.g: 50% or 50px.',
				'woocommerce-product-addon'
			),
			{ col_classes: HALF_WIDTH }
		),
		image_height: setting(
			'text',
			__( 'Images Height', 'woocommerce-product-addon' ),
			__(
				'Change the images height in px, e.g: 50px.',
				'woocommerce-product-addon'
			),
			{ col_classes: HALF_WIDTH }
		),
		bg_color: setting(
			'color',
			__( 'Background Color', 'woocommerce-product-addon' ),
			__( 'Define color e.g: #effeff.', 'woocommerce-product-addon' ),
			{ col_classes: HALF_WIDTH }
		),
		position: setting(
			'select',
			__( 'Images Position', 'woocommerce-product-addon' ),
			__(
				'Select images position left or right.',
				'woocommerce-product-addon'
			),
			{
				options: leftRightOptions(),
				default: 'left',
				col_classes: HALF_WIDTH,
			}
		),
		width: widthSetting(),
		visibility: visibilitySetting(),
		visibility_role: visibilityRoleSetting(),
		first_option: setting(
			'text',
			__( 'First option', 'woocommerce-product-addon' ),
			__( 'Default first selected option.', 'woocommerce-product-addon' ),
			{ col_classes: HALF_WIDTH }
		),
		desc_tooltip: descTooltipSetting(),
		enable_gallery: setting(
			'checkbox',
			__( 'Enable Gallery', 'woocommerce-product-addon' ),
			__(
				'All selected images show in gallery.',
				'woocommerce-product-addon'
			),
			{ col_classes: HALF_WIDTH }
		),
		image_replace: setting(
			'checkbox',
			__( 'Enable Image Replace', 'woocommerce-product-addon' ),
			__(
				'Selected image replaces the main product image.',
				'woocommerce-product-addon'
			),
			{ col_classes: HALF_WIDTH }
		),
		logic: logicSetting(),
		conditions: conditionsSetting(),
	};
}

function palettesSettings(): SettingSchema {
	return {
		title: titleSetting(),
		data_name: dataNameSetting(),
		description: descriptionSetting(),
		error_message: errorMessageSetting(),
		selected_palette_bcolor: setting(
			'color',
			__( 'Selected Border Color', 'woocommerce-product-addon' ),
			__(
				'Change selected palette border color, e.g: #fff',
				'woocommerce-product-addon'
			),
			{ col_classes: HALF_WIDTH }
		),
		options: setting(
			'paired-palettes',
			__( 'Add colors', 'woocommerce-product-addon' ),
			__(
				'Type color code with price. To write label, use #colorcode - White',
				'woocommerce-product-addon'
			)
		),
		selected: setting(
			'text',
			__( 'Selected color', 'woocommerce-product-addon' ),
			__(
				'Type color code if you want one already selected.',
				'woocommerce-product-addon'
			),
			{ col_classes: HALF_WIDTH }
		),
		class: classSetting(),
		width: widthSetting(),
		max_selected: setting(
			'number',
			__( 'Max selected', 'woocommerce-product-addon' ),
			__(
				'Max. selected, leave blank for default.',
				'woocommerce-product-addon'
			),
			{ col_classes: HALF_WIDTH }
		),
		color_width: setting(
			'text',
			__( 'Color width', 'woocommerce-product-addon' ),
			__( 'Default is 50, e.g: 75', 'woocommerce-product-addon' ),
			{ col_classes: HALF_WIDTH }
		),
		color_height: setting(
			'text',
			__( 'Color height', 'woocommerce-product-addon' ),
			__( 'Default is 50, e.g: 100', 'woocommerce-product-addon' ),
			{ col_classes: HALF_WIDTH }
		),
		visibility: visibilitySetting(),
		visibility_role: visibilityRoleSetting(),
		multiple_allowed: setting(
			'checkbox',
			__( 'Multiple selections?', 'woocommerce-product-addon' ),
			__(
				'Allow users to select more than one palette.',
				'woocommerce-product-addon'
			),
			{ col_classes: HALF_WIDTH }
		),
		onetime: fixedFeeSetting(),
		circle: setting(
			'checkbox',
			__( 'Show as Circle', 'woocommerce-product-addon' ),
			__(
				'Display color palettes as circles.',
				'woocommerce-product-addon'
			),
			{ col_classes: HALF_WIDTH }
		),
		desc_tooltip: descTooltipSetting(),
		required: requiredSetting(),
		logic: logicSetting(),
		conditions: conditionsSetting(),
	};
}

function priceMatrixSettings(): SettingSchema {
	return {
		title: titleSetting(),
		data_name: dataNameSetting(),
		description: descriptionSetting(),
		discount_type: setting(
			'select',
			__( 'Discount On?', 'woocommerce-product-addon' ),
			__( 'Select discount option.', 'woocommerce-product-addon' ),
			{
				options: {
					both: __( 'Base & Option', 'woocommerce-product-addon' ),
					base: __( 'Only Base', 'woocommerce-product-addon' ),
				},
				col_classes: HALF_WIDTH,
			}
		),
		options: setting(
			'paired-pricematrix',
			__( 'Price matrix', 'woocommerce-product-addon' ),
			__( 'Type quantity range with price.', 'woocommerce-product-addon' )
		),
		qty_step: setting(
			'text',
			__( 'Quantity Step', 'woocommerce-product-addon' ),
			__( 'Quantity step e.g: 3', 'woocommerce-product-addon' ),
			{ col_classes: HALF_WIDTH }
		),
		visibility: visibilitySetting(),
		visibility_role: visibilityRoleSetting(),
		discount: setting(
			'checkbox',
			__( 'Apply as discount', 'woocommerce-product-addon' ),
			__(
				'Apply the matrix result as a discount.',
				'woocommerce-product-addon'
			),
			{ col_classes: HALF_WIDTH }
		),
		show_slider: setting(
			'checkbox',
			__( 'Enable Quantity Slider', 'woocommerce-product-addon' ),
			__(
				'Display a range slider for quantity under matrix.',
				'woocommerce-product-addon'
			),
			{ col_classes: HALF_WIDTH }
		),
		show_price_per_unit: setting(
			'checkbox',
			__( 'Show price per unit?', 'woocommerce-product-addon' ),
			__(
				'Calculate price against per unit and show along total.',
				'woocommerce-product-addon'
			),
			{ col_classes: HALF_WIDTH }
		),
		hide_matrix_table: setting(
			'checkbox',
			__( 'Hide Price Matrix?', 'woocommerce-product-addon' ),
			__( 'Hide the price matrix table.', 'woocommerce-product-addon' ),
			{ col_classes: HALF_WIDTH }
		),
		desc_tooltip: descTooltipSetting(),
		logic: logicSetting(),
		conditions: conditionsSetting(),
	};
}

function quantitiesSettings(): SettingSchema {
	return {
		title: titleSetting(),
		data_name: dataNameSetting(),
		description: descriptionSetting(),
		options: setting(
			'paired-quantity',
			__( 'Add options', 'woocommerce-product-addon' ),
			__(
				'Type option with price (optionally).',
				'woocommerce-product-addon'
			)
		),
		required: requiredSetting(),
		error_message: errorMessageSetting(),
		view_control: setting(
			'select',
			__( 'Variation Layout', 'woocommerce-product-addon' ),
			__(
				'Select variation layout design.',
				'woocommerce-product-addon'
			),
			{
				options: variationLayoutOptions(),
				default: 'simple_view',
				col_classes: HALF_WIDTH,
			}
		),
		default_price: setting(
			'text',
			__( 'Default Price', 'woocommerce-product-addon' ),
			__(
				'Default option price if no prices are given in options.',
				'woocommerce-product-addon'
			),
			{ col_classes: HALF_WIDTH }
		),
		min_qty: setting(
			'text',
			__( 'Min Quantity', 'woocommerce-product-addon' ),
			__( 'Enter min quantity allowed.', 'woocommerce-product-addon' ),
			{ col_classes: HALF_WIDTH }
		),
		max_qty: setting(
			'text',
			__( 'Max Quantity', 'woocommerce-product-addon' ),
			__( 'Enter max quantity allowed.', 'woocommerce-product-addon' ),
			{ col_classes: HALF_WIDTH }
		),
		class: classSetting(),
		width: widthSetting(),
		visibility: visibilitySetting(),
		visibility_role: visibilityRoleSetting(),
		desc_tooltip: descTooltipSetting(),
		enable_plusminus: setting(
			'checkbox',
			__( 'Enhance -/+ controls', 'woocommerce-product-addon' ),
			__( 'Add the -/+ buttons.', 'woocommerce-product-addon' ),
			{ col_classes: HALF_WIDTH }
		),
		manage_stock: setting(
			'checkbox',
			__( 'Manage Stock', 'woocommerce-product-addon' ),
			__(
				'Check and update stock against each variation.',
				'woocommerce-product-addon'
			),
			{ col_classes: HALF_WIDTH }
		),
		unlink_order_qty: setting(
			'checkbox',
			__( 'Unlink Order Quantity', 'woocommerce-product-addon' ),
			__(
				'Order quantity will not be controlled by this field.',
				'woocommerce-product-addon'
			),
			{ col_classes: HALF_WIDTH }
		),
		logic: logicSetting(),
		conditions: conditionsSetting(),
	};
}

function selectQtySettings(): SettingSchema {
	return {
		title: titleSetting(),
		data_name: dataNameSetting(),
		description: descriptionSetting(),
		options: setting(
			'paired',
			__( 'Add options', 'woocommerce-product-addon' ),
			__(
				'Type option with price and quantity data.',
				'woocommerce-product-addon'
			)
		),
		error_message: errorMessageSetting(),
		selected: setting(
			'text',
			__( 'Selected option', 'woocommerce-product-addon' ),
			__(
				'Type option name if you want it already selected.',
				'woocommerce-product-addon'
			),
			{ col_classes: HALF_WIDTH }
		),
		first_option: setting(
			'text',
			__( 'First option', 'woocommerce-product-addon' ),
			__( 'Default first selected option.', 'woocommerce-product-addon' ),
			{ col_classes: HALF_WIDTH }
		),
		option_label: setting(
			'text',
			__( 'Option label', 'woocommerce-product-addon' ),
			__(
				'Label used for the option column.',
				'woocommerce-product-addon'
			),
			{ col_classes: HALF_WIDTH }
		),
		qty_label: setting(
			'text',
			__( 'Quantity label', 'woocommerce-product-addon' ),
			__(
				'Label used for the quantity column.',
				'woocommerce-product-addon'
			),
			{ col_classes: HALF_WIDTH }
		),
		class: classSetting(),
		width: widthSetting(),
		visibility: visibilitySetting(),
		visibility_role: visibilityRoleSetting(),
		desc_tooltip: descTooltipSetting(),
		required: requiredSetting(),
		unlink_order_qty: setting(
			'checkbox',
			__( 'Unlink Order Quantity', 'woocommerce-product-addon' ),
			__(
				'Order quantity will not be controlled by this field.',
				'woocommerce-product-addon'
			),
			{ col_classes: HALF_WIDTH }
		),
		logic: logicSetting(),
		conditions: conditionsSetting(),
	};
}

function conditionalMetaSettings(): SettingSchema {
	return {
		title: titleSetting(),
		data_name: dataNameSetting(),
		description: descriptionSetting(),
		required: requiredSetting(),
		images: setting(
			'conditional-images',
			__( 'Select images', 'woocommerce-product-addon' ),
			__(
				'Select images from media library.',
				'woocommerce-product-addon'
			)
		),
		error_message: errorMessageSetting(),
		class: classSetting(),
		selected_optionclr: setting(
			'color',
			__( 'Selected option color', 'woocommerce-product-addon' ),
			__(
				'Color used for the selected conditional option.',
				'woocommerce-product-addon'
			),
			{ col_classes: HALF_WIDTH }
		),
		width: widthSetting(),
		visibility: visibilitySetting(),
		visibility_role: visibilityRoleSetting(),
		desc_tooltip: descTooltipSetting(),
		logic: logicSetting(),
		conditions: conditionsSetting(),
	};
}

function fontsSettings(): SettingSchema {
	return {
		title: titleSetting(),
		data_name: dataNameSetting(),
		description: descriptionSetting(),
		required: requiredSetting(),
		options: setting(
			'fonts_paired',
			__( 'Font Families', 'woocommerce-product-addon' ),
			__( 'Add available font options.', 'woocommerce-product-addon' )
		),
		first_option: setting(
			'text',
			__( 'First option', 'woocommerce-product-addon' ),
			__( 'Default first selected option.', 'woocommerce-product-addon' ),
			{ col_classes: HALF_WIDTH }
		),
		error_message: errorMessageSetting(),
		width: widthSetting(),
		desc_tooltip: descTooltipSetting(),
		custom_fonts: setting(
			'textarea',
			__( 'Custom fonts', 'woocommerce-product-addon' ),
			__(
				'Additional custom fonts available in the picker.',
				'woocommerce-product-addon'
			)
		),
		label_placeholder: setting(
			'text',
			__( 'Label placeholder', 'woocommerce-product-addon' ),
			__(
				'Placeholder shown in the preview input.',
				'woocommerce-product-addon'
			)
		),
		label_preview: setting(
			'text',
			__( 'Preview label', 'woocommerce-product-addon' ),
			__(
				'Label displayed above the preview area.',
				'woocommerce-product-addon'
			)
		),
		maxlength: setting(
			'text',
			__( 'Max. Length', 'woocommerce-product-addon' ),
			__( 'Maximum allowed characters.', 'woocommerce-product-addon' ),
			{ col_classes: HALF_WIDTH }
		),
		minlength: setting(
			'text',
			__( 'Min. Length', 'woocommerce-product-addon' ),
			__( 'Minimum required characters.', 'woocommerce-product-addon' ),
			{ col_classes: HALF_WIDTH }
		),
		default_font: setting(
			'text',
			__( 'Default font', 'woocommerce-product-addon' ),
			__(
				'Default font family for the preview.',
				'woocommerce-product-addon'
			),
			{ col_classes: HALF_WIDTH }
		),
		preview_hide: setting(
			'checkbox',
			__( 'Hide preview', 'woocommerce-product-addon' ),
			__(
				'Hide the preview area by default.',
				'woocommerce-product-addon'
			),
			{ col_classes: HALF_WIDTH }
		),
		preview_data_id: setting(
			'text',
			__( 'Preview data ID', 'woocommerce-product-addon' ),
			__(
				'Data attribute or selector for the preview source.',
				'woocommerce-product-addon'
			),
			{ col_classes: HALF_WIDTH }
		),
		preview_class: setting(
			'text',
			__( 'Preview class', 'woocommerce-product-addon' ),
			__(
				'Additional classes for the preview wrapper.',
				'woocommerce-product-addon'
			),
			{ col_classes: HALF_WIDTH }
		),
		preview_addtocart: setting(
			'checkbox',
			__( 'Preview on add to cart', 'woocommerce-product-addon' ),
			__(
				'Include the preview text in add to cart actions.',
				'woocommerce-product-addon'
			),
			{ col_classes: HALF_WIDTH }
		),
		preview_box_textcolor: setting(
			'color',
			__( 'Preview text color', 'woocommerce-product-addon' ),
			__(
				'Text color used inside the preview box.',
				'woocommerce-product-addon'
			),
			{ col_classes: HALF_WIDTH }
		),
		preview_box_bgcolor: setting(
			'color',
			__( 'Preview background color', 'woocommerce-product-addon' ),
			__(
				'Background color used inside the preview box.',
				'woocommerce-product-addon'
			),
			{ col_classes: HALF_WIDTH }
		),
		preview_box_bgcolor_datasource: setting(
			'text',
			__( 'Preview background datasource', 'woocommerce-product-addon' ),
			__(
				'Field or attribute used to drive the preview background color.',
				'woocommerce-product-addon'
			)
		),
		preview_box_textcolor_datasource: setting(
			'text',
			__( 'Preview text datasource', 'woocommerce-product-addon' ),
			__(
				'Field or attribute used to drive the preview text color.',
				'woocommerce-product-addon'
			)
		),
		disable_defaultfonts: setting(
			'checkbox',
			__( 'Disable default fonts', 'woocommerce-product-addon' ),
			__(
				'Hide the built-in font library.',
				'woocommerce-product-addon'
			),
			{ col_classes: HALF_WIDTH }
		),
		disable_fontselect: setting(
			'checkbox',
			__( 'Disable font select', 'woocommerce-product-addon' ),
			__(
				'Prevent changing the font from the preview UI.',
				'woocommerce-product-addon'
			),
			{ col_classes: HALF_WIDTH }
		),
		logic: logicSetting(),
		conditions: conditionsSetting(),
	};
}

function vqmatrixSettings(): SettingSchema {
	return {
		title: titleSetting(),
		data_name: dataNameSetting(),
		description: descriptionSetting(),
		required: requiredSetting(),
		options: setting(
			'vqmatrix-colunm',
			__( 'Matrix columns', 'woocommerce-product-addon' ),
			__( 'Matrix column options.', 'woocommerce-product-addon' )
		),
		row_options: setting(
			'vqmatrix-row',
			__( 'Matrix rows', 'woocommerce-product-addon' ),
			__( 'Matrix row options.', 'woocommerce-product-addon' )
		),
		vqmatrix_label: setting(
			'text',
			__( 'Matrix label', 'woocommerce-product-addon' ),
			__(
				'Heading shown above the variation matrix.',
				'woocommerce-product-addon'
			)
		),
		default_price: setting(
			'text',
			__( 'Default price', 'woocommerce-product-addon' ),
			__(
				'Fallback price when no matrix cell has a value.',
				'woocommerce-product-addon'
			),
			{ col_classes: HALF_WIDTH }
		),
		min_qty: setting(
			'text',
			__( 'Min Quantity', 'woocommerce-product-addon' ),
			__( 'Enter min quantity allowed.', 'woocommerce-product-addon' ),
			{ col_classes: HALF_WIDTH }
		),
		max_qty: setting(
			'text',
			__( 'Max Quantity', 'woocommerce-product-addon' ),
			__( 'Enter max quantity allowed.', 'woocommerce-product-addon' ),
			{ col_classes: HALF_WIDTH }
		),
		price_view: setting(
			'text',
			__( 'Price view', 'woocommerce-product-addon' ),
			__(
				'Display mode for matrix pricing.',
				'woocommerce-product-addon'
			),
			{ col_classes: HALF_WIDTH }
		),
		enable_plusminus: setting(
			'checkbox',
			__( 'Enhance -/+ controls', 'woocommerce-product-addon' ),
			__( 'Add the -/+ buttons.', 'woocommerce-product-addon' ),
			{ col_classes: HALF_WIDTH }
		),
		width: widthSetting(),
		visibility: visibilitySetting(),
		visibility_role: visibilityRoleSetting(),
		desc_tooltip: descTooltipSetting(),
		logic: logicSetting(),
		conditions: conditionsSetting(),
	};
}

export const builtinFieldUiSchemas: Record< string, SettingSchema > = {
	text: textSettings(),
	textcounter: textSettings(),
	textarea: textareaSettings(),
	email: emailSettings(),
	number: numberSettings(),
	hidden: hiddenSettings(),
	select: selectSettings(),
	checkbox: checkboxSettings(),
	radio: radioSettings(),
	switcher: switcherSettings(),
	date: dateSettings(),
	timezone: timezoneSettings(),
	color: colorSettings(),
	divider: dividerSettings(),
	file: fileSettings(),
	daterange: daterangeSettings(),
	section: sectionSettings(),
	measure: measureSettings(),
	phone: phoneSettings(),
	superlist: superlistSettings(),
	texter: texterSettings(),
	domain: domainSettings(),
	collapse: collapseSettings(),
	quantityoption: quantityOptionSettings(),
	qtypack: qtyPackSettings(),
	chained: chainedSettings(),
	conditional_meta: conditionalMetaSettings(),
	fonts: fontsSettings(),
	vqmatrix: vqmatrixSettings(),
	audio: audioSettings(),
	bulkquantity: bulkQuantitySettings(),
	cropper: cropperSettings(),
	emojis: emojisSettings(),
	fixedprice: fixedPriceSettings(),
	image: imageSettings(),
	imageselect: imageselectSettings(),
	palettes: palettesSettings(),
	pricematrix: priceMatrixSettings(),
	quantities: quantitiesSettings(),
	selectqty: selectQtySettings(),
};
