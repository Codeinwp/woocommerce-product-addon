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
			__(
				'It will be shown as field label',
				'woocommerce-product-addon'
			)
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
		desc ||
			__( 'Select width column.', 'woocommerce-product-addon' ),
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
		'checkbox',
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
			__( 'Will not multiply with quantity', 'woocommerce-product-addon' ),
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
			__( 'It will pull content from post. e.g: 22', 'woocommerce-product-addon' )
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
			__( 'Max. values allowed, leave blank for default', 'woocommerce-product-addon' ),
			{ col_classes: HALF_WIDTH }
		),
		min: setting(
			'text',
			__( 'Min. values', 'woocommerce-product-addon' ),
			__( 'Min. values allowed, leave blank for default', 'woocommerce-product-addon' ),
			{ col_classes: HALF_WIDTH }
		),
		step: setting(
			'text',
			__( 'Steps', 'woocommerce-product-addon' ),
			__( 'specified legal number intervals', 'woocommerce-product-addon' ),
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
			__( 'Just for info e.g: Select your option.', 'woocommerce-product-addon' ),
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
			__( 'Type option with price (optionally)', 'woocommerce-product-addon' )
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
			__( 'Small description, it will be diplay near name title.', 'woocommerce-product-addon' )
		),
		error_message: errorMessageSetting(),
		options: setting(
			'paired',
			__( 'Add options', 'woocommerce-product-addon' ),
			__( 'Type option with price (optionally)', 'woocommerce-product-addon' )
		),
		selected: setting(
			'text',
			__( 'Selected option', 'woocommerce-product-addon' ),
			__(
				'Type option name given in (Add Options) tab if you want already selected.',
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
			'text',
			__( 'Selected option', 'woocommerce-product-addon' ),
			__(
				'Type option name given in (Add Options) tab if you want already selected.',
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
			__( 'Just for info e.g: Select your option.', 'woocommerce-product-addon' ),
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
			__( 'It will show current local time.', 'woocommerce-product-addon' ),
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
			__( 'Define default color e.g: #effeff', 'woocommerce-product-addon' ),
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
			__( 'Provide the divider height e.g: 3px.', 'woocommerce-product-addon' ),
			{ col_classes: HALF_WIDTH }
		),
		divider_txtsize: setting(
			'text',
			__( 'Font size', 'woocommerce-product-addon' ),
			__( 'Provide divider text font size e.g: 18px', 'woocommerce-product-addon' ),
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
};
