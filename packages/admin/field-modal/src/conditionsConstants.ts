/**
 * Mirrors js/admin/ppom-admin.js condition operator ↔ field-type compatibility.
 */

export const FIELD_COMPATIBLE_WITH_SELECT_OPTIONS = [
	'select',
	'radio',
	'switcher',
	'image',
	'conditional_meta',
];
export const FIELDS_COMPATIBLE_WITH_TEXT = [ 'text', 'textarea', 'date', 'email' ];
export const FIELDS_COMPATIBLE_WITH_NUMBERS = [
	...FIELD_COMPATIBLE_WITH_SELECT_OPTIONS,
	'number',
];

export const OPERATOR_COMPARISON_VALUE_FIELD_TYPE = {
	select: [
		...FIELD_COMPATIBLE_WITH_SELECT_OPTIONS,
		'checkbox',
		'imageselect',
	],
};

export const COMPARISON_VALUE_CAN_USE_SELECT = [
	'is',
	'not',
	'greater than',
	'less than',
];

export const HIDE_COMPARISON_INPUT_FIELD = [
	'any',
	'empty',
	'odd-number',
	'even-number',
];

export const OPERATORS_FIELD_COMPATIBILITY: Record< string, string[] > = {
	is: [
		...FIELD_COMPATIBLE_WITH_SELECT_OPTIONS,
		...FIELDS_COMPATIBLE_WITH_TEXT,
		...FIELDS_COMPATIBLE_WITH_NUMBERS,
		'checkbox',
		'imageselect',
	],
	not: [
		...FIELD_COMPATIBLE_WITH_SELECT_OPTIONS,
		...FIELDS_COMPATIBLE_WITH_TEXT,
		...FIELDS_COMPATIBLE_WITH_NUMBERS,
		'checkbox',
		'imageselect',
	],
	'greater than': FIELDS_COMPATIBLE_WITH_NUMBERS,
	'less than': FIELDS_COMPATIBLE_WITH_NUMBERS,
	'even-number': FIELDS_COMPATIBLE_WITH_NUMBERS,
	'odd-number': FIELDS_COMPATIBLE_WITH_NUMBERS,
	between: FIELDS_COMPATIBLE_WITH_NUMBERS,
	'number-multiplier': FIELDS_COMPATIBLE_WITH_NUMBERS,
	any: [
		...FIELD_COMPATIBLE_WITH_SELECT_OPTIONS,
		...FIELDS_COMPATIBLE_WITH_TEXT,
		...FIELDS_COMPATIBLE_WITH_NUMBERS,
	],
	empty: [
		...FIELD_COMPATIBLE_WITH_SELECT_OPTIONS,
		...FIELDS_COMPATIBLE_WITH_TEXT,
		...FIELDS_COMPATIBLE_WITH_NUMBERS,
	],
	contains: [
		...FIELD_COMPATIBLE_WITH_SELECT_OPTIONS,
		...FIELDS_COMPATIBLE_WITH_TEXT,
	],
	'not-contains': [
		...FIELD_COMPATIBLE_WITH_SELECT_OPTIONS,
		...FIELDS_COMPATIBLE_WITH_TEXT,
	],
	regex: [
		...FIELD_COMPATIBLE_WITH_SELECT_OPTIONS,
		...FIELDS_COMPATIBLE_WITH_TEXT,
	],
};

export const PRO_OPERATOR_VALUES = new Set( [
	'contains',
	'not-contains',
	'regex',
	'between',
	'number-multiplier',
	'even-number',
	'odd-number',
] );
