/**
 * Registers hand-authored `FieldUiDefinition` entries for built-in field types.
 */
import { builtinFieldUiSchemas } from './builtinFieldUiSchemas';
import { registerFieldUiDefinition } from './registry';
import type { FieldUiDefinition } from './types';

const SETTINGS_TAB = { id: 'settings', labelKey: 'settingsTab' };
const CONDITIONS_TAB = { id: 'conditions', labelKey: 'conditionsTab' };

function definition(
	slug: string,
	blocks: FieldUiDefinition[ 'blocks' ],
	tabs: FieldUiDefinition[ 'tabs' ] = [ SETTINGS_TAB, CONDITIONS_TAB ]
): FieldUiDefinition {
	return {
		slug,
		tabs,
		blocks,
		settings: builtinFieldUiSchemas[ slug ],
	};
}

const TEXT_BLOCKS: FieldUiDefinition[ 'blocks' ] = [
	{
		kind: 'section',
		id: 'basic',
		tab: 'settings',
		labelKey: 'editorSectionBasic',
		keys: [ 'title', 'data_name', 'description', 'placeholder' ],
	},
	{
		kind: 'section',
		id: 'defaults',
		tab: 'settings',
		labelKey: 'editorSectionDefaultPrice',
		keys: [ 'default_value', 'price' ],
		advanced: true,
	},
	{
		kind: 'section',
		id: 'validation',
		tab: 'settings',
		labelKey: 'editorSectionValidation',
		keys: [ 'error_message', 'maxlength', 'minlength' ],
		advanced: true,
	},
	{
		kind: 'section',
		id: 'display',
		tab: 'settings',
		labelKey: 'editorSectionDisplay',
		keys: [ 'class', 'input_mask', 'width', 'visibility', 'visibility_role' ],
		advanced: true,
	},
	{
		kind: 'section',
		id: 'behavior',
		tab: 'settings',
		labelKey: 'editorSectionBehavior',
		keys: [ 'onetime', 'use_regex', 'desc_tooltip', 'required' ],
		advanced: true,
	},
	{
		kind: 'section',
		id: 'conditions',
		tab: 'conditions',
		labelKey: 'conditionsTab',
		keys: [ 'logic', 'conditions' ],
	},
];

export function textLikeDefinition( slug: string ): FieldUiDefinition {
	return definition( slug, TEXT_BLOCKS );
}

const TEXTAREA_BLOCKS: FieldUiDefinition[ 'blocks' ] = [
	{
		kind: 'section',
		id: 'basic',
		tab: 'settings',
		labelKey: 'editorSectionBasic',
		keys: [ 'title', 'data_name', 'description', 'placeholder' ],
	},
	{
		kind: 'section',
		id: 'defaults',
		tab: 'settings',
		labelKey: 'editorSectionDefaultPrice',
		keys: [ 'price' ],
		advanced: true,
	},
	{
		kind: 'section',
		id: 'validation',
		tab: 'settings',
		labelKey: 'editorSectionValidation',
		keys: [ 'error_message', 'default_value', 'max_length' ],
		advanced: true,
	},
	{
		kind: 'section',
		id: 'display',
		tab: 'settings',
		labelKey: 'editorSectionDisplay',
		keys: [ 'class', 'width', 'visibility', 'visibility_role' ],
		advanced: true,
	},
	{
		kind: 'section',
		id: 'behavior',
		tab: 'settings',
		labelKey: 'editorSectionBehavior',
		keys: [ 'rich_editor', 'desc_tooltip', 'required' ],
		advanced: true,
	},
	{
		kind: 'section',
		id: 'conditions',
		tab: 'conditions',
		labelKey: 'conditionsTab',
		keys: [ 'logic', 'conditions' ],
	},
];

const EMAIL_BLOCKS: FieldUiDefinition[ 'blocks' ] = [
	{
		kind: 'section',
		id: 'basic',
		tab: 'settings',
		labelKey: 'editorSectionBasic',
		keys: [ 'title', 'data_name', 'description', 'placeholder' ],
	},
	{
		kind: 'section',
		id: 'validation',
		tab: 'settings',
		labelKey: 'editorSectionValidation',
		keys: [ 'error_message' ],
		advanced: true,
	},
	{
		kind: 'section',
		id: 'display',
		tab: 'settings',
		labelKey: 'editorSectionDisplay',
		keys: [ 'class', 'width', 'visibility', 'visibility_role' ],
		advanced: true,
	},
	{
		kind: 'section',
		id: 'behavior',
		tab: 'settings',
		labelKey: 'editorSectionBehavior',
		keys: [ 'desc_tooltip', 'required' ],
		advanced: true,
	},
	{
		kind: 'section',
		id: 'conditions',
		tab: 'conditions',
		labelKey: 'conditionsTab',
		keys: [ 'logic', 'conditions' ],
	},
];

const NUMBER_BLOCKS: FieldUiDefinition[ 'blocks' ] = [
	{
		kind: 'section',
		id: 'basic',
		tab: 'settings',
		labelKey: 'editorSectionBasic',
		keys: [ 'title', 'data_name', 'description', 'placeholder' ],
	},
	{
		kind: 'section',
		id: 'defaults',
		tab: 'settings',
		labelKey: 'editorSectionDefaultPrice',
		keys: [ 'default_value' ],
		advanced: true,
	},
	{
		kind: 'section',
		id: 'validation',
		tab: 'settings',
		labelKey: 'editorSectionValidation',
		keys: [ 'error_message', 'max', 'min', 'step' ],
		advanced: true,
	},
	{
		kind: 'section',
		id: 'display',
		tab: 'settings',
		labelKey: 'editorSectionDisplay',
		keys: [ 'class', 'width', 'visibility', 'visibility_role' ],
		advanced: true,
	},
	{
		kind: 'section',
		id: 'behavior',
		tab: 'settings',
		labelKey: 'editorSectionBehavior',
		keys: [ 'desc_tooltip', 'required' ],
		advanced: true,
	},
	{
		kind: 'section',
		id: 'conditions',
		tab: 'conditions',
		labelKey: 'conditionsTab',
		keys: [ 'logic', 'conditions' ],
	},
];

const HIDDEN_BLOCKS: FieldUiDefinition[ 'blocks' ] = [
	{
		kind: 'section',
		id: 'basic',
		tab: 'settings',
		labelKey: 'editorSectionBasic',
		keys: [ 'title', 'data_name', 'field_value' ],
	},
	{
		kind: 'section',
		id: 'display',
		tab: 'settings',
		labelKey: 'editorSectionDisplay',
		keys: [ 'visibility', 'visibility_role' ],
		advanced: true,
	},
	{
		kind: 'section',
		id: 'behavior',
		tab: 'settings',
		labelKey: 'editorSectionBehavior',
		keys: [ 'cart_display' ],
		advanced: true,
	},
];

const SELECT_BLOCKS: FieldUiDefinition[ 'blocks' ] = [
	{
		kind: 'section',
		id: 'basic',
		tab: 'settings',
		labelKey: 'editorSectionBasic',
		keys: [ 'title', 'data_name', 'description' ],
	},
	{
		kind: 'widget',
		id: 'options',
		tab: 'settings',
		widget: 'paired-options',
		ownedKeys: [ 'options' ],
		props: { variant: 'select' },
	},
	{
		kind: 'section',
		id: 'defaults',
		tab: 'settings',
		labelKey: 'editorSectionDefaultPrice',
		keys: [ 'selected', 'first_option' ],
		advanced: true,
	},
	{
		kind: 'section',
		id: 'validation',
		tab: 'settings',
		labelKey: 'editorSectionValidation',
		keys: [ 'error_message' ],
		advanced: true,
	},
	{
		kind: 'section',
		id: 'display',
		tab: 'settings',
		labelKey: 'editorSectionDisplay',
		keys: [ 'class', 'width', 'visibility', 'visibility_role' ],
		advanced: true,
	},
	{
		kind: 'section',
		id: 'behavior',
		tab: 'settings',
		labelKey: 'editorSectionBehavior',
		keys: [ 'desc_tooltip', 'onetime', 'required' ],
		advanced: true,
	},
	{
		kind: 'section',
		id: 'conditions',
		tab: 'conditions',
		labelKey: 'conditionsTab',
		keys: [ 'logic', 'conditions' ],
	},
];

const CHECKBOX_BLOCKS: FieldUiDefinition[ 'blocks' ] = [
	{
		kind: 'section',
		id: 'basic',
		tab: 'settings',
		labelKey: 'editorSectionBasic',
		keys: [ 'title', 'data_name', 'description' ],
	},
	{
		kind: 'widget',
		id: 'options',
		tab: 'settings',
		widget: 'paired-options',
		ownedKeys: [ 'options' ],
		props: { variant: 'checkbox' },
	},
	{
		kind: 'section',
		id: 'defaults',
		tab: 'settings',
		labelKey: 'editorSectionDefaultPrice',
		keys: [ 'checked', 'min_checked', 'max_checked' ],
		advanced: true,
	},
	{
		kind: 'section',
		id: 'validation',
		tab: 'settings',
		labelKey: 'editorSectionValidation',
		keys: [ 'error_message' ],
		advanced: true,
	},
	{
		kind: 'section',
		id: 'display',
		tab: 'settings',
		labelKey: 'editorSectionDisplay',
		keys: [ 'class', 'width', 'visibility', 'visibility_role' ],
		advanced: true,
	},
	{
		kind: 'section',
		id: 'behavior',
		tab: 'settings',
		labelKey: 'editorSectionBehavior',
		keys: [ 'desc_tooltip', 'onetime', 'required' ],
		advanced: true,
	},
	{
		kind: 'section',
		id: 'conditions',
		tab: 'conditions',
		labelKey: 'conditionsTab',
		keys: [ 'logic', 'conditions' ],
	},
];

const RADIO_BLOCKS: FieldUiDefinition[ 'blocks' ] = [
	{
		kind: 'section',
		id: 'basic',
		tab: 'settings',
		labelKey: 'editorSectionBasic',
		keys: [ 'title', 'data_name', 'description' ],
	},
	{
		kind: 'widget',
		id: 'options',
		tab: 'settings',
		widget: 'paired-options',
		ownedKeys: [ 'options' ],
		props: { variant: 'radio' },
	},
	{
		kind: 'section',
		id: 'defaults',
		tab: 'settings',
		labelKey: 'editorSectionDefaultPrice',
		keys: [ 'selected' ],
		advanced: true,
	},
	{
		kind: 'section',
		id: 'validation',
		tab: 'settings',
		labelKey: 'editorSectionValidation',
		keys: [ 'error_message' ],
		advanced: true,
	},
	{
		kind: 'section',
		id: 'display',
		tab: 'settings',
		labelKey: 'editorSectionDisplay',
		keys: [ 'class', 'width', 'visibility', 'visibility_role' ],
		advanced: true,
	},
	{
		kind: 'section',
		id: 'behavior',
		tab: 'settings',
		labelKey: 'editorSectionBehavior',
		keys: [ 'desc_tooltip', 'onetime', 'required' ],
		advanced: true,
	},
	{
		kind: 'section',
		id: 'conditions',
		tab: 'conditions',
		labelKey: 'conditionsTab',
		keys: [ 'logic', 'conditions' ],
	},
];

const DATE_BLOCKS: FieldUiDefinition[ 'blocks' ] = [
	{
		kind: 'section',
		id: 'basic',
		tab: 'settings',
		labelKey: 'editorSectionBasic',
		keys: [ 'title', 'data_name', 'description', 'placeholder' ],
	},
	{
		kind: 'section',
		id: 'validation',
		tab: 'settings',
		labelKey: 'editorSectionValidation',
		keys: [ 'error_message' ],
		advanced: true,
	},
	{
		kind: 'section',
		id: 'date',
		tab: 'settings',
		labelKey: 'editorSectionDateCalendar',
		keys: [ 'date_formats', 'default_value', 'min_date', 'max_date', 'year_range', 'first_day_of_week' ],
		advanced: true,
	},
	{
		kind: 'section',
		id: 'layout',
		tab: 'settings',
		labelKey: 'editorSectionDisplay',
		keys: [ 'class', 'width', 'visibility', 'visibility_role' ],
		advanced: true,
	},
	{
		kind: 'section',
		id: 'rules',
		tab: 'settings',
		labelKey: 'editorSectionBehavior',
		keys: [ 'jquery_dp', 'no_weekends', 'past_dates', 'desc_tooltip', 'required' ],
		advanced: true,
	},
	{
		kind: 'section',
		id: 'conditions',
		tab: 'conditions',
		labelKey: 'conditionsTab',
		keys: [ 'logic', 'conditions' ],
	},
];

const TIMEZONE_BLOCKS: FieldUiDefinition[ 'blocks' ] = [
	{
		kind: 'section',
		id: 'basic',
		tab: 'settings',
		labelKey: 'editorSectionBasic',
		keys: [ 'title', 'data_name', 'description' ],
	},
	{
		kind: 'section',
		id: 'options',
		tab: 'settings',
		labelKey: 'editorSectionDefaultPrice',
		keys: [ 'selected', 'first_option' ],
		advanced: true,
	},
	{
		kind: 'section',
		id: 'regions',
		tab: 'settings',
		labelKey: 'editorSectionMore',
		keys: [ 'regions', 'error_message' ],
		advanced: true,
	},
	{
		kind: 'section',
		id: 'layout',
		tab: 'settings',
		labelKey: 'editorSectionDisplay',
		keys: [ 'class', 'width', 'visibility', 'visibility_role' ],
		advanced: true,
	},
	{
		kind: 'section',
		id: 'behavior',
		tab: 'settings',
		labelKey: 'editorSectionBehavior',
		keys: [ 'show_time', 'desc_tooltip', 'required' ],
		advanced: true,
	},
	{
		kind: 'section',
		id: 'conditions',
		tab: 'conditions',
		labelKey: 'conditionsTab',
		keys: [ 'logic', 'conditions' ],
	},
];

const COLOR_BLOCKS: FieldUiDefinition[ 'blocks' ] = [
	{
		kind: 'section',
		id: 'basic',
		tab: 'settings',
		labelKey: 'editorSectionBasic',
		keys: [ 'title', 'data_name', 'description' ],
	},
	{
		kind: 'section',
		id: 'validation',
		tab: 'settings',
		labelKey: 'editorSectionValidation',
		keys: [ 'error_message' ],
		advanced: true,
	},
	{
		kind: 'section',
		id: 'palette',
		tab: 'settings',
		labelKey: 'editorSectionDefaultPrice',
		keys: [ 'default_color', 'palettes_colors', 'palettes_width', 'palettes_mode' ],
		advanced: true,
	},
	{
		kind: 'section',
		id: 'layout',
		tab: 'settings',
		labelKey: 'editorSectionDisplay',
		keys: [ 'width', 'visibility', 'visibility_role' ],
		advanced: true,
	},
	{
		kind: 'section',
		id: 'behavior',
		tab: 'settings',
		labelKey: 'editorSectionBehavior',
		keys: [ 'show_palettes', 'show_onload', 'desc_tooltip', 'required' ],
		advanced: true,
	},
	{
		kind: 'section',
		id: 'conditions',
		tab: 'conditions',
		labelKey: 'conditionsTab',
		keys: [ 'logic', 'conditions' ],
	},
];

const DIVIDER_BLOCKS: FieldUiDefinition[ 'blocks' ] = [
	{
		kind: 'section',
		id: 'basic',
		tab: 'settings',
		labelKey: 'editorSectionBasic',
		keys: [ 'title', 'data_name' ],
	},
	{
		kind: 'section',
		id: 'style',
		tab: 'settings',
		labelKey: 'editorSectionDisplay',
		keys: [ 'divider_styles', 'style1_border', 'divider_height', 'divider_txtsize', 'divider_color', 'divider_txtclr' ],
		advanced: true,
	},
];

function sectionBlock(
	id: string,
	tab: string,
	labelKey: string,
	keys: string[],
	advanced = false
): FieldUiDefinition[ 'blocks' ][ number ] {
	return {
		kind: 'section',
		id,
		tab,
		labelKey,
		keys,
		...( advanced ? { advanced: true as const } : {} ),
	};
}

function widgetBlock(
	id: string,
	tab: string,
	widget: string,
	ownedKeys: string[],
	props?: Record< string, unknown >,
	advanced = false
): FieldUiDefinition[ 'blocks' ][ number ] {
	return {
		kind: 'widget',
		id,
		tab,
		widget,
		ownedKeys,
		props,
		...( advanced ? { advanced: true as const } : {} ),
	};
}

function withConditions(
	...blocks: FieldUiDefinition[ 'blocks' ]
): FieldUiDefinition[ 'blocks' ] {
	return [
		...blocks,
		sectionBlock(
			'conditions',
			'conditions',
			'conditionsTab',
			[ 'logic', 'conditions' ]
		),
	];
}

const FILE_BLOCKS = withConditions(
	sectionBlock(
		'basic',
		'settings',
		'editorSectionBasic',
		[ 'title', 'data_name', 'description' ]
	),
	sectionBlock(
		'validation',
		'settings',
		'editorSectionValidation',
		[ 'error_message' ],
		true
	),
	sectionBlock(
		'pricing',
		'settings',
		'editorSectionDefaultPrice',
		[ 'file_cost' ],
		true
	),
	sectionBlock(
		'display',
		'settings',
		'editorSectionDisplay',
		[
			'class',
			'width',
			'button_label_select',
			'button_class',
			'visibility',
			'visibility_role',
		],
		true
	),
	sectionBlock(
		'media',
		'settings',
		'editorSectionMedia',
		[ 'files_allowed', 'file_types', 'file_size' ],
		true
	),
	sectionBlock(
		'dimensions',
		'settings',
		'editorSectionImageDimensions',
		[
			'min_img_h',
			'max_img_h',
			'min_img_w',
			'max_img_w',
			'img_dimension_error',
		],
		true
	),
	sectionBlock(
		'behavior',
		'settings',
		'editorSectionBehavior',
		[ 'desc_tooltip', 'onetime', 'required' ],
		true
	)
);

const DATERANGE_BLOCKS = withConditions(
	sectionBlock(
		'basic',
		'settings',
		'editorSectionBasic',
		[ 'title', 'data_name', 'description' ]
	),
	sectionBlock(
		'validation',
		'settings',
		'editorSectionValidation',
		[ 'error_message' ],
		true
	),
	sectionBlock(
		'date',
		'settings',
		'editorSectionDateCalendar',
		[
			'open_style',
			'date_formats',
			'tp_increment',
			'start_date',
			'end_date',
			'min_date',
			'max_date',
		],
		true
	),
	sectionBlock(
		'display',
		'settings',
		'editorSectionDisplay',
		[ 'class', 'width', 'visibility', 'visibility_role' ],
		true
	),
	sectionBlock(
		'behavior',
		'settings',
		'editorSectionBehavior',
		[
			'time_picker',
			'tp_24hours',
			'tp_seconds',
			'drop_down',
			'show_weeks',
			'auto_apply',
			'desc_tooltip',
			'required',
		],
		true
	)
);

const SECTION_BLOCKS = withConditions(
	sectionBlock(
		'basic',
		'settings',
		'editorSectionBasic',
		[ 'data_name', 'description', 'html' ]
	),
	sectionBlock(
		'display',
		'settings',
		'editorSectionDisplay',
		[ 'width', 'visibility', 'visibility_role' ],
		true
	),
	sectionBlock(
		'behavior',
		'settings',
		'editorSectionBehavior',
		[ 'desc_tooltip', 'cart_display' ],
		true
	)
);

const MEASURE_BLOCKS = withConditions(
	sectionBlock(
		'basic',
		'settings',
		'editorSectionBasic',
		[ 'title', 'data_name', 'description' ]
	),
	sectionBlock(
		'pricing',
		'settings',
		'editorSectionDefaultPrice',
		[ 'default_value', 'price-multiplier', 'price' ],
		true
	),
	sectionBlock(
		'validation',
		'settings',
		'editorSectionValidation',
		[ 'error_message', 'max', 'min', 'step' ],
		true
	),
	sectionBlock(
		'display',
		'settings',
		'editorSectionDisplay',
		[ 'class', 'width', 'visibility', 'visibility_role' ],
		true
	),
	sectionBlock(
		'behavior',
		'settings',
		'editorSectionBehavior',
		[ 'desc_tooltip', 'required' ],
		true
	)
);

const PHONE_BLOCKS = withConditions(
	sectionBlock(
		'basic',
		'settings',
		'editorSectionBasic',
		[
			'title',
			'data_name',
			'description',
			'placeholder',
		]
	),
	sectionBlock(
		'options',
		'settings',
		'editorSectionDefaultPrice',
		[ 'default_country' ],
		true
	),
	sectionBlock(
		'validation',
		'settings',
		'editorSectionValidation',
		[ 'error_message' ],
		true
	),
	sectionBlock(
		'display',
		'settings',
		'editorSectionDisplay',
		[ 'class', 'width', 'visibility', 'visibility_role' ],
		true
	),
	sectionBlock(
		'behavior',
		'settings',
		'editorSectionBehavior',
		[
			'enable_search',
			'enable_material',
			'desc_tooltip',
			'required',
		],
		true
	)
);

const SUPERLIST_BLOCKS = withConditions(
	sectionBlock(
		'basic',
		'settings',
		'editorSectionBasic',
		[ 'title', 'data_name', 'description' ]
	),
	sectionBlock(
		'options',
		'settings',
		'editorSectionDefaultPrice',
		[ 'listoptions', 'option_exclude', 'selected' ],
		true
	),
	sectionBlock(
		'validation',
		'settings',
		'editorSectionValidation',
		[ 'error_message' ],
		true
	),
	sectionBlock(
		'display',
		'settings',
		'editorSectionDisplay',
		[ 'width', 'visibility', 'visibility_role' ],
		true
	),
	sectionBlock(
		'behavior',
		'settings',
		'editorSectionBehavior',
		[ 'desc_tooltip', 'required' ],
		true
	)
);

const TEXTER_BLOCKS = withConditions(
	sectionBlock(
		'basic',
		'settings',
		'editorSectionBasic',
		[ 'title', 'data_name', 'description', 'post_id' ]
	),
	sectionBlock(
		'button',
		'settings',
		'editorSectionDisplay',
		[ 'button_title', 'btn_color', 'btn_bg_color', 'width' ],
		true
	),
	sectionBlock(
		'display',
		'settings',
		'editorSectionDisplay',
		[ 'visibility', 'visibility_role' ],
		true
	),
	sectionBlock(
		'behavior',
		'settings',
		'editorSectionBehavior',
		[
			'alignment',
			'font_size',
			'font_family',
			'font_color',
			'desc_tooltip',
		],
		true
	)
);

const DOMAIN_BLOCKS = withConditions(
	sectionBlock(
		'basic',
		'settings',
		'editorSectionBasic',
		[ 'title', 'data_name', 'description', 'placeholder' ]
	),
	sectionBlock(
		'messages',
		'settings',
		'editorSectionDefaultPrice',
		[ 'available_message', 'notavailable_message' ],
		true
	),
	sectionBlock(
		'display',
		'settings',
		'editorSectionDisplay',
		[ 'button_label', 'button_class', 'width' ],
		true
	),
	sectionBlock(
		'behavior',
		'settings',
		'editorSectionBehavior',
		[ 'required' ],
		true
	)
);

const COLLAPSE_BLOCKS = withConditions(
	sectionBlock(
		'basic',
		'settings',
		'editorSectionBasic',
		[ 'title', 'data_name' ]
	),
	sectionBlock(
		'behavior',
		'settings',
		'editorSectionBehavior',
		[ 'collapse_type', 'default_open' ],
		true
	)
);

const QUANTITYOPTION_BLOCKS = withConditions(
	sectionBlock(
		'basic',
		'settings',
		'editorSectionBasic',
		[
			'title',
			'data_name',
			'description',
			'placeholder',
		]
	),
	sectionBlock(
		'pricing',
		'settings',
		'editorSectionDefaultPrice',
		[ 'unit_price', 'default_value' ],
		true
	),
	sectionBlock(
		'validation',
		'settings',
		'editorSectionValidation',
		[ 'error_message', 'min', 'max', 'step' ],
		true
	),
	sectionBlock(
		'display',
		'settings',
		'editorSectionDisplay',
		[ 'class', 'width', 'visibility', 'visibility_role' ],
		true
	),
	sectionBlock(
		'behavior',
		'settings',
		'editorSectionBehavior',
		[ 'desc_tooltip', 'onetime', 'required' ],
		true
	)
);

const QTYPACK_BLOCKS = withConditions(
	sectionBlock(
		'basic',
		'settings',
		'editorSectionBasic',
		[ 'title', 'data_name', 'description' ]
	),
	widgetBlock(
		'options',
		'settings',
		'paired-quantity',
		[ 'options' ],
		{ fieldKey: 'options' }
	),
	sectionBlock(
		'pricing',
		'settings',
		'editorSectionDefaultPrice',
		[ 'default_price', 'pack_size', 'packsize_message' ],
		true
	),
	sectionBlock(
		'display',
		'settings',
		'editorSectionDisplay',
		[ 'width', 'visibility', 'visibility_role' ],
		true
	),
	sectionBlock(
		'behavior',
		'settings',
		'editorSectionBehavior',
		[ 'desc_tooltip' ],
		true
	)
);

const SWITCHER_BLOCKS = withConditions(
	sectionBlock(
		'basic',
		'settings',
		'editorSectionBasic',
		[ 'title', 'data_name', 'description' ]
	),
	widgetBlock(
		'options',
		'settings',
		'paired-switch',
		[ 'options' ]
	),
	sectionBlock(
		'validation',
		'settings',
		'editorSectionValidation',
		[ 'error_message' ],
		true
	),
	sectionBlock(
		'defaults',
		'settings',
		'editorSectionDefaultPrice',
		[ 'selected' ],
		true
	),
	sectionBlock(
		'appearance',
		'settings',
		'editorSectionDisplay',
		[
			'marker_height',
			'font_size',
			'switcher_color',
			'price_size',
			'font_color',
			'marker_color',
		],
		true
	),
	sectionBlock(
		'display',
		'settings',
		'editorSectionDisplay',
		[ 'class', 'width', 'visibility', 'visibility_role' ],
		true
	),
	sectionBlock(
		'behavior',
		'settings',
		'editorSectionBehavior',
		[ 'desc_tooltip', 'onetime', 'required' ],
		true
	)
);

const CHAINED_BLOCKS = withConditions(
	sectionBlock(
		'basic',
		'settings',
		'editorSectionBasic',
		[ 'title', 'data_name', 'description' ]
	),
	widgetBlock(
		'options',
		'settings',
		'chained-options',
		[ 'options' ]
	),
	sectionBlock(
		'validation',
		'settings',
		'editorSectionValidation',
		[ 'error_message' ],
		true
	),
	sectionBlock(
		'defaults',
		'settings',
		'editorSectionDefaultPrice',
		[ 'selected', 'first_option' ],
		true
	),
	sectionBlock(
		'display',
		'settings',
		'editorSectionDisplay',
		[ 'class', 'width', 'visibility', 'visibility_role' ],
		true
	),
	sectionBlock(
		'behavior',
		'settings',
		'editorSectionBehavior',
		[ 'desc_tooltip', 'onetime', 'required' ],
		true
	)
);

const CONDITIONAL_META_BLOCKS = withConditions(
	sectionBlock(
		'basic',
		'settings',
		'editorSectionBasic',
		[ 'title', 'data_name', 'description' ]
	),
	widgetBlock(
		'images',
		'settings',
		'conditional-images',
		[ 'images' ]
	),
	sectionBlock(
		'validation',
		'settings',
		'editorSectionValidation',
		[ 'error_message' ],
		true
	),
	sectionBlock(
		'appearance',
		'settings',
		'editorSectionDisplay',
		[ 'class', 'selected_optionclr', 'width' ],
		true
	),
	sectionBlock(
		'display',
		'settings',
		'editorSectionDisplay',
		[ 'visibility', 'visibility_role' ],
		true
	),
	sectionBlock(
		'behavior',
		'settings',
		'editorSectionBehavior',
		[ 'desc_tooltip', 'required' ],
		true
	)
);

const PREVIEW_TAB = { id: 'preview', labelKey: 'Preview' };

const FONTS_BLOCKS: FieldUiDefinition[ 'blocks' ] = [
	sectionBlock(
		'basic',
		'settings',
		'editorSectionBasic',
		[ 'title', 'data_name', 'description' ]
	),
	widgetBlock(
		'options',
		'settings',
		'fonts-paired',
		[ 'options' ]
	),
	sectionBlock(
		'defaults',
		'settings',
		'editorSectionDefaultPrice',
		[ 'first_option' ],
		true
	),
	sectionBlock(
		'validation',
		'settings',
		'editorSectionValidation',
		[ 'error_message' ],
		true
	),
	sectionBlock(
		'layout',
		'settings',
		'editorSectionDisplay',
		[ 'width', 'desc_tooltip' ],
		true
	),
	sectionBlock(
		'custom-fonts',
		'settings',
		'editorSectionDisplay',
		[ 'custom_fonts' ],
		true
	),
	sectionBlock(
		'behavior',
		'settings',
		'editorSectionBehavior',
		[ 'required' ],
		true
	),
	sectionBlock(
		'preview-basic',
		'preview',
		'Preview',
		[
			'label_placeholder',
			'label_preview',
			'maxlength',
			'minlength',
			'default_font',
			'preview_hide',
			'preview_data_id',
			'preview_class',
			'preview_addtocart',
		]
	),
	sectionBlock(
		'preview-colors',
		'preview',
		'editorSectionDisplay',
		[
			'preview_box_textcolor',
			'preview_box_bgcolor',
			'preview_box_bgcolor_datasource',
			'preview_box_textcolor_datasource',
			'disable_defaultfonts',
			'disable_fontselect',
		]
	),
	sectionBlock(
		'conditions',
		'conditions',
		'conditionsTab',
		[ 'logic', 'conditions' ]
	),
];

const VQMATRIX_BLOCKS = withConditions(
	sectionBlock(
		'basic',
		'settings',
		'editorSectionBasic',
		[ 'title', 'data_name', 'description' ]
	),
	widgetBlock(
		'matrix',
		'settings',
		'vqmatrix',
		[ 'options', 'row_options' ]
	),
	sectionBlock(
		'pricing',
		'settings',
		'editorSectionDefaultPrice',
		[
			'vqmatrix_label',
			'default_price',
			'min_qty',
			'max_qty',
			'price_view',
			'enable_plusminus',
		],
		true
	),
	sectionBlock(
		'display',
		'settings',
		'editorSectionDisplay',
		[ 'width', 'visibility', 'visibility_role' ],
		true
	),
	sectionBlock(
		'behavior',
		'settings',
		'editorSectionBehavior',
		[ 'desc_tooltip', 'required' ],
		true
	)
);

const AUDIO_BLOCKS = withConditions(
	sectionBlock(
		'basic',
		'settings',
		'editorSectionBasic',
		[ 'title', 'data_name', 'description' ]
	),
	widgetBlock(
		'audio',
		'settings',
		'audio-media',
		[ 'audio' ]
	),
	sectionBlock(
		'validation',
		'settings',
		'editorSectionValidation',
		[ 'error_message' ],
		true
	),
	sectionBlock(
		'display',
		'settings',
		'editorSectionDisplay',
		[ 'class', 'width', 'visibility', 'visibility_role' ],
		true
	),
	sectionBlock(
		'behavior',
		'settings',
		'editorSectionBehavior',
		[ 'desc_tooltip', 'required', 'multiple_allowed' ],
		true
	)
);

const BULKQUANTITY_BLOCKS = withConditions(
	sectionBlock(
		'basic',
		'settings',
		'editorSectionBasic',
		[ 'title', 'data_name', 'description' ]
	),
	widgetBlock(
		'bulk-quantity',
		'settings',
		'bulk-quantity',
		[ 'options' ]
	),
	sectionBlock(
		'display',
		'settings',
		'editorSectionDisplay',
		[
			'fixed_prices',
			'label_quantity',
			'label_baseprice',
			'label_total',
			'label_fixed',
			'hide_baseprice',
			'show_pricerange',
		],
		true
	)
);

const CROPPER_BLOCKS = withConditions(
	sectionBlock(
		'basic',
		'settings',
		'editorSectionBasic',
		[ 'title', 'data_name', 'description' ]
	),
	widgetBlock(
		'viewport',
		'settings',
		'paired-cropper',
		[ 'options' ],
		{ fieldKey: 'options' }
	),
	sectionBlock(
		'validation',
		'settings',
		'editorSectionValidation',
		[ 'error_message' ],
		true
	),
	sectionBlock(
		'defaults',
		'settings',
		'editorSectionDefaultPrice',
		[ 'file_cost', 'selected', 'first_option' ],
		true
	),
	sectionBlock(
		'display',
		'settings',
		'editorSectionDisplay',
		[
			'class',
			'width',
			'button_label_select',
			'button_class',
			'files_allowed',
			'file_types',
			'file_size',
			'visibility',
			'visibility_role',
		],
		true
	),
	sectionBlock(
		'media',
		'settings',
		'editorSectionMedia',
		[
			'viewport_type',
			'boundary',
			'enforce_boundary',
			'resize',
			'enable_zoom',
			'show_zoomer',
			'enable_exif',
			'onetime_taxable',
		],
		true
	),
	sectionBlock(
		'behavior',
		'settings',
		'editorSectionBehavior',
		[ 'desc_tooltip', 'required' ],
		true
	)
);

const EMOJIS_BLOCKS = withConditions(
	sectionBlock(
		'basic',
		'settings',
		'editorSectionBasic',
		[ 'title', 'data_name', 'description' ]
	),
	sectionBlock(
		'validation',
		'settings',
		'editorSectionValidation',
		[ 'error_message' ],
		true
	),
	widgetBlock(
		'options',
		'settings',
		'paired-palettes',
		[ 'options' ],
		undefined,
		true
	),
	sectionBlock(
		'display',
		'settings',
		'editorSectionDisplay',
		[
			'class',
			'max_selected',
			'width',
			'emojis_display_type',
			'search_placeholder',
			'placeholder',
			'filters_position',
			'search_position',
			'picker_position',
			'tones_Style',
			'recent_emojis',
			'tones',
			'search',
			'visibility',
			'visibility_role',
		],
		true
	),
	sectionBlock(
		'behavior',
		'settings',
		'editorSectionBehavior',
		[
			'desc_tooltip',
			'required',
			'onetime',
			'onetime_taxable',
		],
		true
	)
);

const FIXEDPRICE_BLOCKS = withConditions(
	sectionBlock(
		'basic',
		'settings',
		'editorSectionBasic',
		[ 'title', 'data_name', 'description' ]
	),
	widgetBlock(
		'options',
		'settings',
		'fixed-price-paired',
		[ 'options' ]
	),
	sectionBlock(
		'defaults',
		'settings',
		'editorSectionDefaultPrice',
		[ 'first_option', 'unit_plural', 'unit_single' ],
		true
	),
	sectionBlock(
		'display',
		'settings',
		'editorSectionDisplay',
		[ 'view_type', 'decimal_place', 'class', 'width' ],
		true
	)
);

const IMAGE_BLOCKS = withConditions(
	sectionBlock(
		'basic',
		'settings',
		'editorSectionBasic',
		[ 'title', 'data_name', 'description' ]
	),
	widgetBlock(
		'images',
		'settings',
		'image-media',
		[ 'images' ]
	),
	sectionBlock(
		'validation',
		'settings',
		'editorSectionValidation',
		[ 'error_message' ],
		true
	),
	sectionBlock(
		'defaults',
		'settings',
		'editorSectionDefaultPrice',
		[ 'selected' ],
		true
	),
	sectionBlock(
		'image-settings',
		'settings',
		'editorSectionImageSettings',
		[ 'selected_img_bordercolor', 'image_width', 'image_height' ],
		true
	),
	sectionBlock(
		'display',
		'settings',
		'editorSectionDisplay',
		[
			'class',
			'width',
			'visibility',
			'visibility_role',
			'legacy_view',
			'show_popup',
		],
		true
	),
	sectionBlock(
		'behavior',
		'settings',
		'editorSectionBehavior',
		[
			'desc_tooltip',
			'required',
			'multiple_allowed',
			'min_checked',
			'max_checked',
		],
		true
	)
);

const IMAGESELECT_BLOCKS = withConditions(
	sectionBlock(
		'basic',
		'settings',
		'editorSectionBasic',
		[ 'title', 'data_name', 'description' ]
	),
	widgetBlock(
		'images',
		'settings',
		'imageselect-media',
		[ 'images' ]
	),
	sectionBlock(
		'validation',
		'settings',
		'editorSectionValidation',
		[ 'error_message' ],
		true
	),
	sectionBlock(
		'defaults',
		'settings',
		'editorSectionDefaultPrice',
		[ 'first_option' ],
		true
	),
	sectionBlock(
		'display',
		'settings',
		'editorSectionDisplay',
		[
			'dropdown_height',
			'image_width',
			'image_height',
			'bg_color',
			'position',
			'width',
			'visibility',
			'visibility_role',
		],
		true
	),
	sectionBlock(
		'behavior',
		'settings',
		'editorSectionBehavior',
		[ 'desc_tooltip', 'enable_gallery', 'image_replace', 'required' ],
		true
	)
);

const PALETTES_BLOCKS = withConditions(
	sectionBlock(
		'basic',
		'settings',
		'editorSectionBasic',
		[ 'title', 'data_name', 'description' ]
	),
	sectionBlock(
		'validation',
		'settings',
		'editorSectionValidation',
		[ 'error_message' ],
		true
	),
	sectionBlock(
		'selected-border',
		'settings',
		'editorSectionDisplay',
		[ 'selected_palette_bcolor' ],
		true
	),
	widgetBlock(
		'options',
		'settings',
		'paired-palettes',
		[ 'options' ],
		undefined,
		true
	),
	sectionBlock(
		'defaults',
		'settings',
		'editorSectionDefaultPrice',
		[ 'selected' ],
		true
	),
	sectionBlock(
		'display',
		'settings',
		'editorSectionDisplay',
		[
			'class',
			'width',
			'max_selected',
			'color_width',
			'color_height',
			'visibility',
			'visibility_role',
		],
		true
	),
	sectionBlock(
		'behavior',
		'settings',
		'editorSectionBehavior',
		[
			'multiple_allowed',
			'onetime',
			'circle',
			'desc_tooltip',
			'required',
		],
		true
	)
);

const PRICEMATRIX_BLOCKS = withConditions(
	sectionBlock(
		'basic',
		'settings',
		'editorSectionBasic',
		[ 'title', 'data_name', 'description' ]
	),
	sectionBlock(
		'pricing',
		'settings',
		'editorSectionDefaultPrice',
		[ 'discount_type' ],
		true
	),
	widgetBlock(
		'options',
		'settings',
		'paired-pricematrix',
		[ 'options' ],
		undefined,
		true
	),
	sectionBlock(
		'display',
		'settings',
		'editorSectionDisplay',
		[
			'qty_step',
			'visibility',
			'visibility_role',
			'discount',
			'show_slider',
			'show_price_per_unit',
			'hide_matrix_table',
		],
		true
	),
	sectionBlock(
		'behavior',
		'settings',
		'editorSectionBehavior',
		[ 'desc_tooltip' ],
		true
	)
);

const QUANTITIES_BLOCKS = withConditions(
	sectionBlock(
		'basic',
		'settings',
		'editorSectionBasic',
		[ 'title', 'data_name', 'description' ]
	),
	widgetBlock(
		'options',
		'settings',
		'paired-quantity',
		[ 'options' ],
		{ fieldKey: 'options' }
	),
	sectionBlock(
		'validation',
		'settings',
		'editorSectionValidation',
		[ 'error_message' ],
		true
	),
	sectionBlock(
		'variation-layout',
		'settings',
		'editorSectionVariationLayout',
		[ 'view_control', 'default_price', 'min_qty', 'max_qty' ],
		true
	),
	sectionBlock(
		'display',
		'settings',
		'editorSectionDisplay',
		[ 'class', 'width', 'visibility', 'visibility_role' ],
		true
	),
	sectionBlock(
		'behavior',
		'settings',
		'editorSectionBehavior',
		[
			'desc_tooltip',
			'enable_plusminus',
			'manage_stock',
			'unlink_order_qty',
			'required',
		],
		true
	)
);

const SELECTQTY_BLOCKS = withConditions(
	sectionBlock(
		'basic',
		'settings',
		'editorSectionBasic',
		[ 'title', 'data_name', 'description' ]
	),
	widgetBlock(
		'options',
		'settings',
		'paired-options',
		[ 'options' ],
		{ variant: 'select' }
	),
	sectionBlock(
		'validation',
		'settings',
		'editorSectionValidation',
		[ 'error_message' ],
		true
	),
	sectionBlock(
		'defaults',
		'settings',
		'editorSectionDefaultPrice',
		[ 'selected', 'first_option', 'option_label', 'qty_label' ],
		true
	),
	sectionBlock(
		'display',
		'settings',
		'editorSectionDisplay',
		[ 'class', 'width', 'visibility', 'visibility_role' ],
		true
	),
	sectionBlock(
		'behavior',
		'settings',
		'editorSectionBehavior',
		[ 'desc_tooltip', 'required', 'unlink_order_qty' ],
		true
	)
);

export function registerBuiltinFieldUiDefinitions(): void {
	registerFieldUiDefinition( definition( 'text', TEXT_BLOCKS ) );
	registerFieldUiDefinition( definition( 'textcounter', TEXT_BLOCKS ) );
	registerFieldUiDefinition( definition( 'textarea', TEXTAREA_BLOCKS ) );
	registerFieldUiDefinition( definition( 'email', EMAIL_BLOCKS ) );
	registerFieldUiDefinition( definition( 'number', NUMBER_BLOCKS ) );
	registerFieldUiDefinition( definition( 'hidden', HIDDEN_BLOCKS, [ SETTINGS_TAB ] ) );
	registerFieldUiDefinition( definition( 'date', DATE_BLOCKS ) );
	registerFieldUiDefinition( definition( 'timezone', TIMEZONE_BLOCKS ) );
	registerFieldUiDefinition( definition( 'color', COLOR_BLOCKS ) );
	registerFieldUiDefinition( definition( 'divider', DIVIDER_BLOCKS, [ SETTINGS_TAB ] ) );
	registerFieldUiDefinition( definition( 'select', SELECT_BLOCKS ) );
	registerFieldUiDefinition( definition( 'checkbox', CHECKBOX_BLOCKS ) );
	registerFieldUiDefinition( definition( 'radio', RADIO_BLOCKS ) );
	registerFieldUiDefinition( definition( 'file', FILE_BLOCKS ) );
	registerFieldUiDefinition( definition( 'daterange', DATERANGE_BLOCKS ) );
	registerFieldUiDefinition( definition( 'section', SECTION_BLOCKS ) );
	registerFieldUiDefinition( definition( 'measure', MEASURE_BLOCKS ) );
	registerFieldUiDefinition( definition( 'phone', PHONE_BLOCKS ) );
	registerFieldUiDefinition( definition( 'superlist', SUPERLIST_BLOCKS ) );
	registerFieldUiDefinition( definition( 'texter', TEXTER_BLOCKS ) );
	registerFieldUiDefinition( definition( 'domain', DOMAIN_BLOCKS ) );
	registerFieldUiDefinition( definition( 'collapse', COLLAPSE_BLOCKS ) );
	registerFieldUiDefinition( definition( 'quantityoption', QUANTITYOPTION_BLOCKS ) );
	registerFieldUiDefinition( definition( 'qtypack', QTYPACK_BLOCKS ) );
	registerFieldUiDefinition( definition( 'switcher', SWITCHER_BLOCKS ) );
	registerFieldUiDefinition( definition( 'chained', CHAINED_BLOCKS ) );
	registerFieldUiDefinition( definition( 'conditional_meta', CONDITIONAL_META_BLOCKS ) );
	registerFieldUiDefinition(
		definition( 'fonts', FONTS_BLOCKS, [ SETTINGS_TAB, PREVIEW_TAB, CONDITIONS_TAB ] )
	);
	registerFieldUiDefinition( definition( 'vqmatrix', VQMATRIX_BLOCKS ) );
	registerFieldUiDefinition( definition( 'audio', AUDIO_BLOCKS ) );
	registerFieldUiDefinition(
		definition( 'bulkquantity', BULKQUANTITY_BLOCKS )
	);
	registerFieldUiDefinition( definition( 'cropper', CROPPER_BLOCKS ) );
	registerFieldUiDefinition( definition( 'emojis', EMOJIS_BLOCKS ) );
	registerFieldUiDefinition(
		definition( 'fixedprice', FIXEDPRICE_BLOCKS )
	);
	registerFieldUiDefinition( definition( 'image', IMAGE_BLOCKS ) );
	registerFieldUiDefinition(
		definition( 'imageselect', IMAGESELECT_BLOCKS )
	);
	registerFieldUiDefinition( definition( 'palettes', PALETTES_BLOCKS ) );
	registerFieldUiDefinition(
		definition( 'pricematrix', PRICEMATRIX_BLOCKS )
	);
	registerFieldUiDefinition(
		definition( 'quantities', QUANTITIES_BLOCKS )
	);
	registerFieldUiDefinition( definition( 'selectqty', SELECTQTY_BLOCKS ) );
}
