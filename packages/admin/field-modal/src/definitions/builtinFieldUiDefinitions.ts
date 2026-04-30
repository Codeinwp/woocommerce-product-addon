/**
 * Registers hand-authored `FieldUiDefinition` entries for built-in field types.
 */
import { builtinFieldUiSchemas } from './builtinFieldUiSchemas';
import {
	BuiltinFieldType,
	FieldTab,
	SectionLabelKey,
	WidgetKind,
} from './builtinFieldTypes';
import { registerFieldUiDefinition } from './registry';
import type { FieldUiDefinition } from './types';

const SETTINGS_TAB = {
	id: FieldTab.Settings,
	labelKey: 'settingsTab' as const,
};
const CONDITIONS_TAB = {
	id: FieldTab.Conditions,
	labelKey: 'conditionsTab' as const,
};

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
		tab: FieldTab.Settings,
		labelKey: SectionLabelKey.Basic,
		keys: [ 'title', 'data_name', 'description', 'placeholder' ],
	},
	{
		kind: 'section',
		id: 'field-settings',
		tab: FieldTab.Settings,
		labelKey: SectionLabelKey.FieldSettings,
		keys: [ 'required', 'default_value' ],
	},
	{
		kind: 'section',
		id: 'pricing',
		tab: FieldTab.Settings,
		labelKey: SectionLabelKey.DefaultPrice,
		keys: [ 'price', 'onetime' ],
	},
	{
		kind: 'section',
		id: 'validation',
		tab: FieldTab.Settings,
		labelKey: SectionLabelKey.Validation,
		keys: [ 'error_message', 'maxlength', 'minlength' ],
		advanced: true,
	},
	{
		kind: 'section',
		id: 'display',
		tab: FieldTab.Settings,
		labelKey: SectionLabelKey.Display,
		keys: [
			'class',
			'input_mask',
			'width',
			'visibility',
			'visibility_role',
		],
		advanced: true,
	},
	{
		kind: 'section',
		id: 'behavior',
		tab: FieldTab.Settings,
		labelKey: SectionLabelKey.Behavior,
		keys: [ 'use_regex', 'desc_tooltip' ],
		advanced: true,
	},
	{
		kind: 'section',
		id: 'conditions',
		tab: FieldTab.Conditions,
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
		tab: FieldTab.Settings,
		labelKey: SectionLabelKey.Basic,
		keys: [ 'title', 'data_name', 'description', 'placeholder' ],
	},
	{
		kind: 'section',
		id: 'field-settings',
		tab: FieldTab.Settings,
		labelKey: SectionLabelKey.FieldSettings,
		keys: [ 'required', 'default_value' ],
	},
	{
		kind: 'section',
		id: 'pricing',
		tab: FieldTab.Settings,
		labelKey: SectionLabelKey.DefaultPrice,
		keys: [ 'price' ],
	},
	{
		kind: 'section',
		id: 'validation',
		tab: FieldTab.Settings,
		labelKey: SectionLabelKey.Validation,
		keys: [ 'error_message', 'max_length' ],
		advanced: true,
	},
	{
		kind: 'section',
		id: 'display',
		tab: FieldTab.Settings,
		labelKey: SectionLabelKey.Display,
		keys: [ 'class', 'width', 'visibility', 'visibility_role' ],
		advanced: true,
	},
	{
		kind: 'section',
		id: 'behavior',
		tab: FieldTab.Settings,
		labelKey: SectionLabelKey.Behavior,
		keys: [ 'rich_editor', 'desc_tooltip' ],
		advanced: true,
	},
	{
		kind: 'section',
		id: 'conditions',
		tab: FieldTab.Conditions,
		labelKey: 'conditionsTab',
		keys: [ 'logic', 'conditions' ],
	},
];

const EMAIL_BLOCKS: FieldUiDefinition[ 'blocks' ] = [
	{
		kind: 'section',
		id: 'basic',
		tab: FieldTab.Settings,
		labelKey: SectionLabelKey.Basic,
		keys: [ 'title', 'data_name', 'description', 'placeholder' ],
	},
	{
		kind: 'section',
		id: 'field-settings',
		tab: FieldTab.Settings,
		labelKey: SectionLabelKey.FieldSettings,
		keys: [ 'required' ],
	},
	{
		kind: 'section',
		id: 'validation',
		tab: FieldTab.Settings,
		labelKey: SectionLabelKey.Validation,
		keys: [ 'error_message' ],
		advanced: true,
	},
	{
		kind: 'section',
		id: 'display',
		tab: FieldTab.Settings,
		labelKey: SectionLabelKey.Display,
		keys: [ 'class', 'width', 'visibility', 'visibility_role' ],
		advanced: true,
	},
	{
		kind: 'section',
		id: 'behavior',
		tab: FieldTab.Settings,
		labelKey: SectionLabelKey.Behavior,
		keys: [ 'desc_tooltip' ],
		advanced: true,
	},
	{
		kind: 'section',
		id: 'conditions',
		tab: FieldTab.Conditions,
		labelKey: 'conditionsTab',
		keys: [ 'logic', 'conditions' ],
	},
];

const NUMBER_BLOCKS: FieldUiDefinition[ 'blocks' ] = [
	{
		kind: 'section',
		id: 'basic',
		tab: FieldTab.Settings,
		labelKey: SectionLabelKey.Basic,
		keys: [ 'title', 'data_name', 'description', 'placeholder' ],
	},
	{
		kind: 'section',
		id: 'field-settings',
		tab: FieldTab.Settings,
		labelKey: SectionLabelKey.FieldSettings,
		keys: [ 'required', 'default_value' ],
	},
	{
		kind: 'section',
		id: 'constraints',
		tab: FieldTab.Settings,
		labelKey: SectionLabelKey.Constraints,
		keys: [ 'min', 'max', 'step' ],
	},
	{
		kind: 'section',
		id: 'validation',
		tab: FieldTab.Settings,
		labelKey: SectionLabelKey.Validation,
		keys: [ 'error_message' ],
		advanced: true,
	},
	{
		kind: 'section',
		id: 'display',
		tab: FieldTab.Settings,
		labelKey: SectionLabelKey.Display,
		keys: [ 'class', 'width', 'visibility', 'visibility_role' ],
		advanced: true,
	},
	{
		kind: 'section',
		id: 'behavior',
		tab: FieldTab.Settings,
		labelKey: SectionLabelKey.Behavior,
		keys: [ 'desc_tooltip' ],
		advanced: true,
	},
	{
		kind: 'section',
		id: 'conditions',
		tab: FieldTab.Conditions,
		labelKey: 'conditionsTab',
		keys: [ 'logic', 'conditions' ],
	},
];

const HIDDEN_BLOCKS: FieldUiDefinition[ 'blocks' ] = [
	{
		kind: 'section',
		id: 'basic',
		tab: FieldTab.Settings,
		labelKey: SectionLabelKey.Basic,
		keys: [ 'title', 'data_name', 'field_value' ],
	},
	{
		kind: 'section',
		id: 'display',
		tab: FieldTab.Settings,
		labelKey: SectionLabelKey.Display,
		keys: [ 'visibility', 'visibility_role' ],
		advanced: true,
	},
	{
		kind: 'section',
		id: 'behavior',
		tab: FieldTab.Settings,
		labelKey: SectionLabelKey.Behavior,
		keys: [ 'cart_display' ],
		advanced: true,
	},
];

const SELECT_BLOCKS: FieldUiDefinition[ 'blocks' ] = [
	{
		kind: 'section',
		id: 'basic',
		tab: FieldTab.Settings,
		labelKey: SectionLabelKey.Basic,
		keys: [ 'title', 'data_name', 'description' ],
	},
	{
		kind: 'widget',
		id: 'options',
		tab: FieldTab.Settings,
		widget: WidgetKind.PairedOptions,
		ownedKeys: [ 'options' ],
		props: { variant: 'select' },
	},
	{
		kind: 'section',
		id: 'field-settings',
		tab: FieldTab.Settings,
		labelKey: SectionLabelKey.FieldSettings,
		keys: [ 'required', 'onetime', 'selected', 'first_option' ],
	},
	{
		kind: 'section',
		id: 'validation',
		tab: FieldTab.Settings,
		labelKey: SectionLabelKey.Validation,
		keys: [ 'error_message' ],
		advanced: true,
	},
	{
		kind: 'section',
		id: 'display',
		tab: FieldTab.Settings,
		labelKey: SectionLabelKey.Display,
		keys: [ 'class', 'width', 'visibility', 'visibility_role' ],
		advanced: true,
	},
	{
		kind: 'section',
		id: 'behavior',
		tab: FieldTab.Settings,
		labelKey: SectionLabelKey.Behavior,
		keys: [ 'desc_tooltip' ],
		advanced: true,
	},
	{
		kind: 'section',
		id: 'conditions',
		tab: FieldTab.Conditions,
		labelKey: 'conditionsTab',
		keys: [ 'logic', 'conditions' ],
	},
];

const CHECKBOX_BLOCKS: FieldUiDefinition[ 'blocks' ] = [
	{
		kind: 'section',
		id: 'basic',
		tab: FieldTab.Settings,
		labelKey: SectionLabelKey.Basic,
		keys: [ 'title', 'data_name', 'description' ],
	},
	{
		kind: 'widget',
		id: 'options',
		tab: FieldTab.Settings,
		widget: WidgetKind.PairedOptions,
		ownedKeys: [ 'options' ],
		props: { variant: 'checkbox' },
	},
	{
		kind: 'section',
		id: 'field-settings',
		tab: FieldTab.Settings,
		labelKey: SectionLabelKey.FieldSettings,
		keys: [ 'required', 'onetime', 'checked' ],
	},
	{
		kind: 'section',
		id: 'constraints',
		tab: FieldTab.Settings,
		labelKey: SectionLabelKey.Constraints,
		keys: [ 'min_checked', 'max_checked' ],
	},
	{
		kind: 'section',
		id: 'validation',
		tab: FieldTab.Settings,
		labelKey: SectionLabelKey.Validation,
		keys: [ 'error_message' ],
		advanced: true,
	},
	{
		kind: 'section',
		id: 'display',
		tab: FieldTab.Settings,
		labelKey: SectionLabelKey.Display,
		keys: [ 'class', 'width', 'visibility', 'visibility_role' ],
		advanced: true,
	},
	{
		kind: 'section',
		id: 'behavior',
		tab: FieldTab.Settings,
		labelKey: SectionLabelKey.Behavior,
		keys: [ 'desc_tooltip' ],
		advanced: true,
	},
	{
		kind: 'section',
		id: 'conditions',
		tab: FieldTab.Conditions,
		labelKey: 'conditionsTab',
		keys: [ 'logic', 'conditions' ],
	},
];

const RADIO_BLOCKS: FieldUiDefinition[ 'blocks' ] = [
	{
		kind: 'section',
		id: 'basic',
		tab: FieldTab.Settings,
		labelKey: SectionLabelKey.Basic,
		keys: [ 'title', 'data_name', 'description' ],
	},
	{
		kind: 'widget',
		id: 'options',
		tab: FieldTab.Settings,
		widget: WidgetKind.PairedOptions,
		ownedKeys: [ 'options' ],
		props: { variant: 'radio' },
	},
	{
		kind: 'section',
		id: 'field-settings',
		tab: FieldTab.Settings,
		labelKey: SectionLabelKey.FieldSettings,
		keys: [ 'required', 'onetime', 'selected' ],
	},
	{
		kind: 'section',
		id: 'validation',
		tab: FieldTab.Settings,
		labelKey: SectionLabelKey.Validation,
		keys: [ 'error_message' ],
		advanced: true,
	},
	{
		kind: 'section',
		id: 'display',
		tab: FieldTab.Settings,
		labelKey: SectionLabelKey.Display,
		keys: [ 'class', 'width', 'visibility', 'visibility_role' ],
		advanced: true,
	},
	{
		kind: 'section',
		id: 'behavior',
		tab: FieldTab.Settings,
		labelKey: SectionLabelKey.Behavior,
		keys: [ 'desc_tooltip' ],
		advanced: true,
	},
	{
		kind: 'section',
		id: 'conditions',
		tab: FieldTab.Conditions,
		labelKey: 'conditionsTab',
		keys: [ 'logic', 'conditions' ],
	},
];

const DATE_BLOCKS: FieldUiDefinition[ 'blocks' ] = [
	{
		kind: 'section',
		id: 'basic',
		tab: FieldTab.Settings,
		labelKey: SectionLabelKey.Basic,
		keys: [ 'title', 'data_name', 'description', 'placeholder' ],
	},
	{
		kind: 'section',
		id: 'field-settings',
		tab: FieldTab.Settings,
		labelKey: SectionLabelKey.FieldSettings,
		keys: [ 'required', 'date_formats', 'default_value' ],
	},
	{
		kind: 'section',
		id: 'validation',
		tab: FieldTab.Settings,
		labelKey: SectionLabelKey.Validation,
		keys: [ 'error_message' ],
		advanced: true,
	},
	{
		kind: 'section',
		id: 'date',
		tab: FieldTab.Settings,
		labelKey: SectionLabelKey.DateCalendar,
		keys: [ 'min_date', 'max_date', 'year_range', 'first_day_of_week' ],
		advanced: true,
	},
	{
		kind: 'section',
		id: 'layout',
		tab: FieldTab.Settings,
		labelKey: SectionLabelKey.Display,
		keys: [ 'class', 'width', 'visibility', 'visibility_role' ],
		advanced: true,
	},
	{
		kind: 'section',
		id: 'rules',
		tab: FieldTab.Settings,
		labelKey: SectionLabelKey.Behavior,
		keys: [ 'jquery_dp', 'no_weekends', 'past_dates', 'desc_tooltip' ],
		advanced: true,
	},
	{
		kind: 'section',
		id: 'conditions',
		tab: FieldTab.Conditions,
		labelKey: 'conditionsTab',
		keys: [ 'logic', 'conditions' ],
	},
];

const TIMEZONE_BLOCKS: FieldUiDefinition[ 'blocks' ] = [
	{
		kind: 'section',
		id: 'basic',
		tab: FieldTab.Settings,
		labelKey: SectionLabelKey.Basic,
		keys: [ 'title', 'data_name', 'description' ],
	},
	{
		kind: 'section',
		id: 'field-settings',
		tab: FieldTab.Settings,
		labelKey: SectionLabelKey.FieldSettings,
		keys: [ 'required', 'selected', 'first_option' ],
	},
	{
		kind: 'section',
		id: 'regions',
		tab: FieldTab.Settings,
		labelKey: SectionLabelKey.More,
		keys: [ 'regions', 'error_message' ],
		advanced: true,
	},
	{
		kind: 'section',
		id: 'layout',
		tab: FieldTab.Settings,
		labelKey: SectionLabelKey.Display,
		keys: [
			'show_time',
			'desc_tooltip',
			'class',
			'width',
			'visibility',
			'visibility_role',
		],
		advanced: true,
	},
	{
		kind: 'section',
		id: 'conditions',
		tab: FieldTab.Conditions,
		labelKey: 'conditionsTab',
		keys: [ 'logic', 'conditions' ],
	},
];

const COLOR_BLOCKS: FieldUiDefinition[ 'blocks' ] = [
	{
		kind: 'section',
		id: 'basic',
		tab: FieldTab.Settings,
		labelKey: SectionLabelKey.Basic,
		keys: [ 'title', 'data_name', 'description' ],
	},
	{
		kind: 'section',
		id: 'field-settings',
		tab: FieldTab.Settings,
		labelKey: SectionLabelKey.FieldSettings,
		keys: [ 'required', 'default_color' ],
	},
	{
		kind: 'section',
		id: 'validation',
		tab: FieldTab.Settings,
		labelKey: SectionLabelKey.Validation,
		keys: [ 'error_message' ],
		advanced: true,
	},
	{
		kind: 'section',
		id: 'palette',
		tab: FieldTab.Settings,
		labelKey: SectionLabelKey.DefaultPrice,
		keys: [ 'palettes_colors', 'palettes_width', 'palettes_mode' ],
		advanced: true,
	},
	{
		kind: 'section',
		id: 'layout',
		tab: FieldTab.Settings,
		labelKey: SectionLabelKey.Display,
		keys: [ 'width', 'visibility', 'visibility_role' ],
		advanced: true,
	},
	{
		kind: 'section',
		id: 'behavior',
		tab: FieldTab.Settings,
		labelKey: SectionLabelKey.Behavior,
		keys: [ 'show_palettes', 'show_onload', 'desc_tooltip' ],
		advanced: true,
	},
	{
		kind: 'section',
		id: 'conditions',
		tab: FieldTab.Conditions,
		labelKey: 'conditionsTab',
		keys: [ 'logic', 'conditions' ],
	},
];

const DIVIDER_BLOCKS: FieldUiDefinition[ 'blocks' ] = [
	{
		kind: 'section',
		id: 'basic',
		tab: FieldTab.Settings,
		labelKey: SectionLabelKey.Basic,
		keys: [ 'title', 'data_name' ],
	},
	{
		kind: 'section',
		id: 'style',
		tab: FieldTab.Settings,
		labelKey: SectionLabelKey.Display,
		keys: [
			'divider_styles',
			'style1_border',
			'divider_height',
			'divider_txtsize',
			'divider_color',
			'divider_txtclr',
		],
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
		sectionBlock( 'conditions', FieldTab.Conditions, 'conditionsTab', [
			'logic',
			'conditions',
		] ),
	];
}

const FILE_BLOCKS = withConditions(
	sectionBlock( 'basic', FieldTab.Settings, SectionLabelKey.Basic, [
		'title',
		'data_name',
		'description',
	] ),
	sectionBlock(
		'field-settings',
		FieldTab.Settings,
		SectionLabelKey.FieldSettings,
		[ 'required', 'onetime', 'file_cost' ]
	),
	sectionBlock(
		'constraints',
		FieldTab.Settings,
		SectionLabelKey.Constraints,
		[ 'files_allowed', 'file_types', 'file_size' ]
	),
	sectionBlock(
		'validation',
		FieldTab.Settings,
		SectionLabelKey.Validation,
		[ 'error_message' ],
		true
	),
	sectionBlock(
		'display',
		FieldTab.Settings,
		SectionLabelKey.Display,
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
		'dimensions',
		FieldTab.Settings,
		SectionLabelKey.ImageDimensions,
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
		FieldTab.Settings,
		SectionLabelKey.Behavior,
		[ 'desc_tooltip' ],
		true
	)
);

const DATERANGE_BLOCKS = withConditions(
	sectionBlock( 'basic', FieldTab.Settings, SectionLabelKey.Basic, [
		'title',
		'data_name',
		'description',
	] ),
	sectionBlock(
		'field-settings',
		FieldTab.Settings,
		SectionLabelKey.FieldSettings,
		[ 'required', 'date_formats' ]
	),
	sectionBlock(
		'validation',
		FieldTab.Settings,
		SectionLabelKey.Validation,
		[ 'error_message' ],
		true
	),
	sectionBlock(
		'date',
		FieldTab.Settings,
		SectionLabelKey.DateCalendar,
		[
			'open_style',
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
		FieldTab.Settings,
		SectionLabelKey.Display,
		[ 'class', 'width', 'visibility', 'visibility_role' ],
		true
	),
	sectionBlock(
		'behavior',
		FieldTab.Settings,
		SectionLabelKey.Behavior,
		[
			'time_picker',
			'tp_24hours',
			'tp_seconds',
			'drop_down',
			'show_weeks',
			'auto_apply',
			'desc_tooltip',
		],
		true
	)
);

const SECTION_BLOCKS = withConditions(
	sectionBlock( 'basic', FieldTab.Settings, SectionLabelKey.Basic, [
		'data_name',
		'description',
		'html',
	] ),
	sectionBlock(
		'display',
		FieldTab.Settings,
		SectionLabelKey.Display,
		[ 'width', 'visibility', 'visibility_role' ],
		true
	),
	sectionBlock(
		'behavior',
		FieldTab.Settings,
		SectionLabelKey.Behavior,
		[ 'desc_tooltip', 'cart_display' ],
		true
	)
);

const MEASURE_BLOCKS = withConditions(
	sectionBlock( 'basic', FieldTab.Settings, SectionLabelKey.Basic, [
		'title',
		'data_name',
		'description',
	] ),
	sectionBlock(
		'field-settings',
		FieldTab.Settings,
		SectionLabelKey.FieldSettings,
		[ 'required', 'default_value' ]
	),
	sectionBlock( 'pricing', FieldTab.Settings, SectionLabelKey.DefaultPrice, [
		'price',
		'price-multiplier',
	] ),
	sectionBlock(
		'constraints',
		FieldTab.Settings,
		SectionLabelKey.Constraints,
		[ 'min', 'max', 'step' ]
	),
	sectionBlock(
		'validation',
		FieldTab.Settings,
		SectionLabelKey.Validation,
		[ 'error_message' ],
		true
	),
	sectionBlock(
		'display',
		FieldTab.Settings,
		SectionLabelKey.Display,
		[ 'class', 'width', 'visibility', 'visibility_role' ],
		true
	),
	sectionBlock(
		'behavior',
		FieldTab.Settings,
		SectionLabelKey.Behavior,
		[ 'desc_tooltip' ],
		true
	)
);

const PHONE_BLOCKS = withConditions(
	sectionBlock( 'basic', FieldTab.Settings, SectionLabelKey.Basic, [
		'title',
		'data_name',
		'description',
		'placeholder',
	] ),
	sectionBlock(
		'field-settings',
		FieldTab.Settings,
		SectionLabelKey.FieldSettings,
		[ 'required', 'default_country' ]
	),
	sectionBlock(
		'validation',
		FieldTab.Settings,
		SectionLabelKey.Validation,
		[ 'error_message' ],
		true
	),
	sectionBlock(
		'display',
		FieldTab.Settings,
		SectionLabelKey.Display,
		[ 'class', 'width', 'visibility', 'visibility_role' ],
		true
	),
	sectionBlock(
		'behavior',
		FieldTab.Settings,
		SectionLabelKey.Behavior,
		[ 'enable_search', 'enable_material', 'desc_tooltip' ],
		true
	)
);

const SUPERLIST_BLOCKS = withConditions(
	sectionBlock( 'basic', FieldTab.Settings, SectionLabelKey.Basic, [
		'title',
		'data_name',
		'description',
	] ),
	sectionBlock(
		'field-settings',
		FieldTab.Settings,
		SectionLabelKey.FieldSettings,
		[ 'required', 'listoptions', 'selected' ]
	),
	sectionBlock(
		'options',
		FieldTab.Settings,
		SectionLabelKey.DefaultPrice,
		[ 'option_exclude' ],
		true
	),
	sectionBlock(
		'validation',
		FieldTab.Settings,
		SectionLabelKey.Validation,
		[ 'error_message' ],
		true
	),
	sectionBlock(
		'display',
		FieldTab.Settings,
		SectionLabelKey.Display,
		[ 'width', 'visibility', 'visibility_role' ],
		true
	),
	sectionBlock(
		'behavior',
		FieldTab.Settings,
		SectionLabelKey.Behavior,
		[ 'desc_tooltip' ],
		true
	)
);

const TEXTER_BLOCKS = withConditions(
	sectionBlock( 'basic', FieldTab.Settings, SectionLabelKey.Basic, [
		'title',
		'data_name',
		'description',
		'post_id',
	] ),
	sectionBlock(
		'button',
		FieldTab.Settings,
		SectionLabelKey.Display,
		[ 'button_title', 'btn_color', 'btn_bg_color', 'width' ],
		true
	),
	sectionBlock(
		'display',
		FieldTab.Settings,
		SectionLabelKey.Display,
		[ 'visibility', 'visibility_role' ],
		true
	),
	sectionBlock(
		'behavior',
		FieldTab.Settings,
		SectionLabelKey.Behavior,
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
	sectionBlock( 'basic', FieldTab.Settings, SectionLabelKey.Basic, [
		'title',
		'data_name',
		'description',
		'placeholder',
	] ),
	sectionBlock(
		'field-settings',
		FieldTab.Settings,
		SectionLabelKey.FieldSettings,
		[ 'required' ]
	),
	sectionBlock(
		'messages',
		FieldTab.Settings,
		SectionLabelKey.DefaultPrice,
		[ 'available_message', 'notavailable_message' ],
		true
	),
	sectionBlock(
		'display',
		FieldTab.Settings,
		SectionLabelKey.Display,
		[ 'button_label', 'button_class', 'width' ],
		true
	)
);

const COLLAPSE_BLOCKS = withConditions(
	sectionBlock( 'basic', FieldTab.Settings, SectionLabelKey.Basic, [
		'title',
		'data_name',
	] ),
	sectionBlock(
		'behavior',
		FieldTab.Settings,
		SectionLabelKey.Behavior,
		[ 'collapse_type', 'default_open' ],
		true
	)
);

const QUANTITYOPTION_BLOCKS = withConditions(
	sectionBlock( 'basic', FieldTab.Settings, SectionLabelKey.Basic, [
		'title',
		'data_name',
		'description',
		'placeholder',
	] ),
	sectionBlock(
		'field-settings',
		FieldTab.Settings,
		SectionLabelKey.FieldSettings,
		[ 'required', 'default_value' ]
	),
	sectionBlock( 'pricing', FieldTab.Settings, SectionLabelKey.DefaultPrice, [
		'unit_price',
		'onetime',
	] ),
	sectionBlock(
		'constraints',
		FieldTab.Settings,
		SectionLabelKey.Constraints,
		[ 'min', 'max', 'step' ]
	),
	sectionBlock(
		'validation',
		FieldTab.Settings,
		SectionLabelKey.Validation,
		[ 'error_message' ],
		true
	),
	sectionBlock(
		'display',
		FieldTab.Settings,
		SectionLabelKey.Display,
		[ 'class', 'width', 'visibility', 'visibility_role' ],
		true
	),
	sectionBlock(
		'behavior',
		FieldTab.Settings,
		SectionLabelKey.Behavior,
		[ 'desc_tooltip' ],
		true
	)
);

const QTYPACK_BLOCKS = withConditions(
	sectionBlock( 'basic', FieldTab.Settings, SectionLabelKey.Basic, [
		'title',
		'data_name',
		'description',
	] ),
	widgetBlock(
		'options',
		FieldTab.Settings,
		WidgetKind.PairedQuantity,
		[ 'options' ],
		{ fieldKey: 'options' }
	),
	sectionBlock(
		'pricing',
		FieldTab.Settings,
		SectionLabelKey.DefaultPrice,
		[ 'default_price', 'pack_size', 'packsize_message' ],
		true
	),
	sectionBlock(
		'display',
		FieldTab.Settings,
		SectionLabelKey.Display,
		[ 'width', 'visibility', 'visibility_role' ],
		true
	),
	sectionBlock(
		'behavior',
		FieldTab.Settings,
		SectionLabelKey.Behavior,
		[ 'desc_tooltip' ],
		true
	)
);

const SWITCHER_BLOCKS = withConditions(
	sectionBlock( 'basic', FieldTab.Settings, SectionLabelKey.Basic, [
		'title',
		'data_name',
		'description',
	] ),
	widgetBlock( 'options', FieldTab.Settings, WidgetKind.PairedSwitch, [
		'options',
	] ),
	sectionBlock(
		'field-settings',
		FieldTab.Settings,
		SectionLabelKey.FieldSettings,
		[ 'required', 'onetime', 'selected' ]
	),
	sectionBlock(
		'validation',
		FieldTab.Settings,
		SectionLabelKey.Validation,
		[ 'error_message' ],
		true
	),
	sectionBlock(
		'appearance',
		FieldTab.Settings,
		SectionLabelKey.Display,
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
		FieldTab.Settings,
		SectionLabelKey.Display,
		[ 'class', 'width', 'visibility', 'visibility_role' ],
		true
	),
	sectionBlock(
		'behavior',
		FieldTab.Settings,
		SectionLabelKey.Behavior,
		[ 'desc_tooltip' ],
		true
	)
);

const CHAINED_BLOCKS = withConditions(
	sectionBlock( 'basic', FieldTab.Settings, SectionLabelKey.Basic, [
		'title',
		'data_name',
		'description',
	] ),
	widgetBlock( 'options', FieldTab.Settings, WidgetKind.ChainedOptions, [
		'options',
	] ),
	sectionBlock(
		'field-settings',
		FieldTab.Settings,
		SectionLabelKey.FieldSettings,
		[ 'required', 'onetime', 'selected', 'first_option' ]
	),
	sectionBlock(
		'validation',
		FieldTab.Settings,
		SectionLabelKey.Validation,
		[ 'error_message' ],
		true
	),
	sectionBlock(
		'display',
		FieldTab.Settings,
		SectionLabelKey.Display,
		[ 'class', 'width', 'visibility', 'visibility_role' ],
		true
	),
	sectionBlock(
		'behavior',
		FieldTab.Settings,
		SectionLabelKey.Behavior,
		[ 'desc_tooltip' ],
		true
	)
);

const CONDITIONAL_META_BLOCKS = withConditions(
	sectionBlock( 'basic', FieldTab.Settings, SectionLabelKey.Basic, [
		'title',
		'data_name',
		'description',
	] ),
	widgetBlock( 'images', FieldTab.Settings, WidgetKind.ConditionalImages, [
		'images',
	] ),
	sectionBlock(
		'field-settings',
		FieldTab.Settings,
		SectionLabelKey.FieldSettings,
		[ 'required' ]
	),
	sectionBlock(
		'validation',
		FieldTab.Settings,
		SectionLabelKey.Validation,
		[ 'error_message' ],
		true
	),
	sectionBlock(
		'appearance',
		FieldTab.Settings,
		SectionLabelKey.Display,
		[ 'class', 'selected_optionclr', 'width' ],
		true
	),
	sectionBlock(
		'display',
		FieldTab.Settings,
		SectionLabelKey.Display,
		[ 'visibility', 'visibility_role' ],
		true
	),
	sectionBlock(
		'behavior',
		FieldTab.Settings,
		SectionLabelKey.Behavior,
		[ 'desc_tooltip' ],
		true
	)
);

const PREVIEW_TAB = { id: FieldTab.Preview, labelKey: 'Preview' };

const FONTS_BLOCKS: FieldUiDefinition[ 'blocks' ] = [
	sectionBlock( 'basic', FieldTab.Settings, SectionLabelKey.Basic, [
		'title',
		'data_name',
		'description',
	] ),
	widgetBlock( 'options', FieldTab.Settings, WidgetKind.FontsPaired, [
		'options',
	] ),
	sectionBlock(
		'field-settings',
		FieldTab.Settings,
		SectionLabelKey.FieldSettings,
		[ 'required', 'first_option' ]
	),
	sectionBlock(
		'validation',
		FieldTab.Settings,
		SectionLabelKey.Validation,
		[ 'error_message' ],
		true
	),
	sectionBlock(
		'layout',
		FieldTab.Settings,
		SectionLabelKey.Display,
		[ 'width', 'desc_tooltip' ],
		true
	),
	sectionBlock(
		'custom-fonts',
		FieldTab.Settings,
		SectionLabelKey.Display,
		[ 'custom_fonts' ],
		true
	),
	sectionBlock( 'preview-basic', FieldTab.Preview, 'Preview', [
		'label_placeholder',
		'label_preview',
		'maxlength',
		'minlength',
		'default_font',
		'preview_hide',
		'preview_data_id',
		'preview_class',
		'preview_addtocart',
	] ),
	sectionBlock( 'preview-colors', FieldTab.Preview, SectionLabelKey.Display, [
		'preview_box_textcolor',
		'preview_box_bgcolor',
		'preview_box_bgcolor_datasource',
		'preview_box_textcolor_datasource',
		'disable_defaultfonts',
		'disable_fontselect',
	] ),
	sectionBlock( 'conditions', FieldTab.Conditions, 'conditionsTab', [
		'logic',
		'conditions',
	] ),
];

const VQMATRIX_BLOCKS = withConditions(
	sectionBlock( 'basic', FieldTab.Settings, SectionLabelKey.Basic, [
		'title',
		'data_name',
		'description',
	] ),
	widgetBlock( 'matrix', FieldTab.Settings, WidgetKind.Vqmatrix, [
		'options',
		'row_options',
	] ),
	sectionBlock(
		'field-settings',
		FieldTab.Settings,
		SectionLabelKey.FieldSettings,
		[ 'required', 'vqmatrix_label', 'default_price' ]
	),
	sectionBlock(
		'constraints',
		FieldTab.Settings,
		SectionLabelKey.Constraints,
		[ 'min_qty', 'max_qty' ]
	),
	sectionBlock(
		'pricing',
		FieldTab.Settings,
		SectionLabelKey.DefaultPrice,
		[ 'price_view', 'enable_plusminus' ],
		true
	),
	sectionBlock(
		'display',
		FieldTab.Settings,
		SectionLabelKey.Display,
		[ 'width', 'visibility', 'visibility_role' ],
		true
	),
	sectionBlock(
		'behavior',
		FieldTab.Settings,
		SectionLabelKey.Behavior,
		[ 'desc_tooltip' ],
		true
	)
);

const AUDIO_BLOCKS = withConditions(
	sectionBlock( 'basic', FieldTab.Settings, SectionLabelKey.Basic, [
		'title',
		'data_name',
		'description',
	] ),
	widgetBlock( 'audio', FieldTab.Settings, WidgetKind.AudioMedia, [
		'audio',
	] ),
	sectionBlock(
		'field-settings',
		FieldTab.Settings,
		SectionLabelKey.FieldSettings,
		[ 'required' ]
	),
	sectionBlock(
		'validation',
		FieldTab.Settings,
		SectionLabelKey.Validation,
		[ 'error_message' ],
		true
	),
	sectionBlock(
		'display',
		FieldTab.Settings,
		SectionLabelKey.Display,
		[ 'class', 'width', 'visibility', 'visibility_role' ],
		true
	),
	sectionBlock(
		'behavior',
		FieldTab.Settings,
		SectionLabelKey.Behavior,
		[ 'desc_tooltip', 'multiple_allowed' ],
		true
	)
);

const BULKQUANTITY_BLOCKS = withConditions(
	sectionBlock( 'basic', FieldTab.Settings, SectionLabelKey.Basic, [
		'title',
		'data_name',
		'description',
	] ),
	widgetBlock( 'bulk-quantity', FieldTab.Settings, WidgetKind.BulkQuantity, [
		'options',
	] ),
	sectionBlock(
		'display',
		FieldTab.Settings,
		SectionLabelKey.Display,
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
	sectionBlock( 'basic', FieldTab.Settings, SectionLabelKey.Basic, [
		'title',
		'data_name',
		'description',
	] ),
	widgetBlock(
		'viewport',
		FieldTab.Settings,
		WidgetKind.PairedCropper,
		[ 'options' ],
		{ fieldKey: 'options' }
	),
	sectionBlock(
		'field-settings',
		FieldTab.Settings,
		SectionLabelKey.FieldSettings,
		[ 'required', 'selected', 'first_option' ]
	),
	sectionBlock( 'pricing', FieldTab.Settings, SectionLabelKey.DefaultPrice, [
		'file_cost',
	] ),
	sectionBlock(
		'constraints',
		FieldTab.Settings,
		SectionLabelKey.Constraints,
		[ 'files_allowed', 'file_types', 'file_size', 'viewport_type' ]
	),
	sectionBlock(
		'validation',
		FieldTab.Settings,
		SectionLabelKey.Validation,
		[ 'error_message' ],
		true
	),
	sectionBlock(
		'display',
		FieldTab.Settings,
		SectionLabelKey.Display,
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
		FieldTab.Settings,
		SectionLabelKey.Media,
		[
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
		FieldTab.Settings,
		SectionLabelKey.Behavior,
		[ 'desc_tooltip' ],
		true
	)
);

const EMOJIS_BLOCKS = withConditions(
	sectionBlock( 'basic', FieldTab.Settings, SectionLabelKey.Basic, [
		'title',
		'data_name',
		'description',
	] ),
	sectionBlock(
		'field-settings',
		FieldTab.Settings,
		SectionLabelKey.FieldSettings,
		[ 'required', 'onetime', 'emojis_display_type' ]
	),
	sectionBlock(
		'validation',
		FieldTab.Settings,
		SectionLabelKey.Validation,
		[ 'error_message' ],
		true
	),
	widgetBlock(
		'options',
		FieldTab.Settings,
		WidgetKind.PairedPalettes,
		[ 'options' ],
		undefined,
		true
	),
	sectionBlock(
		'display',
		FieldTab.Settings,
		SectionLabelKey.Display,
		[
			'class',
			'max_selected',
			'width',
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
		FieldTab.Settings,
		SectionLabelKey.Behavior,
		[ 'desc_tooltip', 'onetime_taxable' ],
		true
	)
);

const FIXEDPRICE_BLOCKS = withConditions(
	sectionBlock( 'basic', FieldTab.Settings, SectionLabelKey.Basic, [
		'title',
		'data_name',
		'description',
	] ),
	widgetBlock( 'options', FieldTab.Settings, WidgetKind.FixedPricePaired, [
		'options',
	] ),
	sectionBlock(
		'field-settings',
		FieldTab.Settings,
		SectionLabelKey.FieldSettings,
		[ 'view_type', 'first_option', 'unit_plural', 'unit_single' ]
	),
	sectionBlock(
		'display',
		FieldTab.Settings,
		SectionLabelKey.Display,
		[ 'decimal_place', 'class', 'width' ],
		true
	)
);

const IMAGE_BLOCKS = withConditions(
	sectionBlock( 'basic', FieldTab.Settings, SectionLabelKey.Basic, [
		'title',
		'data_name',
		'description',
	] ),
	widgetBlock( 'images', FieldTab.Settings, WidgetKind.ImageMedia, [
		'images',
	] ),
	sectionBlock(
		'field-settings',
		FieldTab.Settings,
		SectionLabelKey.FieldSettings,
		[ 'required', 'selected', 'multiple_allowed' ]
	),
	sectionBlock(
		'constraints',
		FieldTab.Settings,
		SectionLabelKey.Constraints,
		[ 'min_checked', 'max_checked' ]
	),
	sectionBlock(
		'validation',
		FieldTab.Settings,
		SectionLabelKey.Validation,
		[ 'error_message' ],
		true
	),
	sectionBlock(
		'image-settings',
		FieldTab.Settings,
		SectionLabelKey.ImageSettings,
		[ 'selected_img_bordercolor', 'image_width', 'image_height' ],
		true
	),
	sectionBlock(
		'display',
		FieldTab.Settings,
		SectionLabelKey.Display,
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
		FieldTab.Settings,
		SectionLabelKey.Behavior,
		[ 'desc_tooltip' ],
		true
	)
);

const IMAGESELECT_BLOCKS = withConditions(
	sectionBlock( 'basic', FieldTab.Settings, SectionLabelKey.Basic, [
		'title',
		'data_name',
		'description',
	] ),
	widgetBlock( 'images', FieldTab.Settings, WidgetKind.ImageselectMedia, [
		'images',
	] ),
	sectionBlock(
		'field-settings',
		FieldTab.Settings,
		SectionLabelKey.FieldSettings,
		[ 'required', 'first_option', 'enable_gallery', 'image_replace' ]
	),
	sectionBlock(
		'validation',
		FieldTab.Settings,
		SectionLabelKey.Validation,
		[ 'error_message' ],
		true
	),
	sectionBlock(
		'display',
		FieldTab.Settings,
		SectionLabelKey.Display,
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
		FieldTab.Settings,
		SectionLabelKey.Behavior,
		[ 'desc_tooltip' ],
		true
	)
);

const PALETTES_BLOCKS = withConditions(
	sectionBlock( 'basic', FieldTab.Settings, SectionLabelKey.Basic, [
		'title',
		'data_name',
		'description',
	] ),
	widgetBlock(
		'options',
		FieldTab.Settings,
		WidgetKind.PairedPalettes,
		[ 'options' ],
		undefined,
		false
	),
	sectionBlock(
		'field-settings',
		FieldTab.Settings,
		SectionLabelKey.FieldSettings,
		[ 'required', 'onetime', 'multiple_allowed', 'selected' ]
	),
	sectionBlock(
		'validation',
		FieldTab.Settings,
		SectionLabelKey.Validation,
		[ 'error_message' ],
		true
	),
	sectionBlock(
		'selected-border',
		FieldTab.Settings,
		SectionLabelKey.Display,
		[ 'selected_palette_bcolor' ],
		true
	),
	sectionBlock(
		'display',
		FieldTab.Settings,
		SectionLabelKey.Display,
		[
			'class',
			'width',
			'max_selected',
			'color_width',
			'color_height',
			'circle',
			'visibility',
			'visibility_role',
		],
		true
	),
	sectionBlock(
		'behavior',
		FieldTab.Settings,
		SectionLabelKey.Behavior,
		[ 'desc_tooltip' ],
		true
	)
);

const PRICEMATRIX_BLOCKS = withConditions(
	sectionBlock( 'basic', FieldTab.Settings, SectionLabelKey.Basic, [
		'title',
		'data_name',
		'description',
	] ),
	sectionBlock(
		'field-settings',
		FieldTab.Settings,
		SectionLabelKey.FieldSettings,
		[ 'discount_type' ]
	),
	widgetBlock( 'options', FieldTab.Settings, WidgetKind.PairedPricematrix, [
		'options',
	] ),
	sectionBlock(
		'display',
		FieldTab.Settings,
		SectionLabelKey.Display,
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
		FieldTab.Settings,
		SectionLabelKey.Behavior,
		[ 'desc_tooltip' ],
		true
	)
);

const QUANTITIES_BLOCKS = withConditions(
	sectionBlock( 'basic', FieldTab.Settings, SectionLabelKey.Basic, [
		'title',
		'data_name',
		'description',
	] ),
	widgetBlock(
		'options',
		FieldTab.Settings,
		WidgetKind.PairedQuantity,
		[ 'options' ],
		{ fieldKey: 'options' }
	),
	sectionBlock(
		'field-settings',
		FieldTab.Settings,
		SectionLabelKey.FieldSettings,
		[ 'required', 'view_control', 'default_price' ]
	),
	sectionBlock(
		'constraints',
		FieldTab.Settings,
		SectionLabelKey.Constraints,
		[ 'min_qty', 'max_qty' ]
	),
	sectionBlock(
		'validation',
		FieldTab.Settings,
		SectionLabelKey.Validation,
		[ 'error_message' ],
		true
	),
	sectionBlock(
		'display',
		FieldTab.Settings,
		SectionLabelKey.Display,
		[ 'class', 'width', 'visibility', 'visibility_role' ],
		true
	),
	sectionBlock(
		'behavior',
		FieldTab.Settings,
		SectionLabelKey.Behavior,
		[
			'desc_tooltip',
			'enable_plusminus',
			'manage_stock',
			'unlink_order_qty',
		],
		true
	)
);

const SELECTQTY_BLOCKS = withConditions(
	sectionBlock( 'basic', FieldTab.Settings, SectionLabelKey.Basic, [
		'title',
		'data_name',
		'description',
	] ),
	widgetBlock(
		'options',
		FieldTab.Settings,
		WidgetKind.PairedOptions,
		[ 'options' ],
		{ variant: 'select' }
	),
	sectionBlock(
		'field-settings',
		FieldTab.Settings,
		SectionLabelKey.FieldSettings,
		[ 'required', 'selected', 'first_option' ]
	),
	sectionBlock(
		'validation',
		FieldTab.Settings,
		SectionLabelKey.Validation,
		[ 'error_message' ],
		true
	),
	sectionBlock(
		'defaults',
		FieldTab.Settings,
		SectionLabelKey.DefaultPrice,
		[ 'option_label', 'qty_label' ],
		true
	),
	sectionBlock(
		'display',
		FieldTab.Settings,
		SectionLabelKey.Display,
		[ 'class', 'width', 'visibility', 'visibility_role' ],
		true
	),
	sectionBlock(
		'behavior',
		FieldTab.Settings,
		SectionLabelKey.Behavior,
		[ 'desc_tooltip', 'unlink_order_qty' ],
		true
	)
);

export function registerBuiltinFieldUiDefinitions(): void {
	registerFieldUiDefinition(
		definition( BuiltinFieldType.Text, TEXT_BLOCKS )
	);
	registerFieldUiDefinition(
		definition( BuiltinFieldType.Textcounter, TEXT_BLOCKS )
	);
	registerFieldUiDefinition(
		definition( BuiltinFieldType.Textarea, TEXTAREA_BLOCKS )
	);
	registerFieldUiDefinition(
		definition( BuiltinFieldType.Email, EMAIL_BLOCKS )
	);
	registerFieldUiDefinition(
		definition( BuiltinFieldType.Number, NUMBER_BLOCKS )
	);
	registerFieldUiDefinition(
		definition( BuiltinFieldType.Hidden, HIDDEN_BLOCKS, [ SETTINGS_TAB ] )
	);
	registerFieldUiDefinition(
		definition( BuiltinFieldType.Date, DATE_BLOCKS )
	);
	registerFieldUiDefinition(
		definition( BuiltinFieldType.Timezone, TIMEZONE_BLOCKS )
	);
	registerFieldUiDefinition(
		definition( BuiltinFieldType.Color, COLOR_BLOCKS )
	);
	registerFieldUiDefinition(
		definition( BuiltinFieldType.Divider, DIVIDER_BLOCKS, [ SETTINGS_TAB ] )
	);
	registerFieldUiDefinition(
		definition( BuiltinFieldType.Select, SELECT_BLOCKS )
	);
	registerFieldUiDefinition(
		definition( BuiltinFieldType.Checkbox, CHECKBOX_BLOCKS )
	);
	registerFieldUiDefinition(
		definition( BuiltinFieldType.Radio, RADIO_BLOCKS )
	);
	registerFieldUiDefinition(
		definition( BuiltinFieldType.File, FILE_BLOCKS )
	);
	registerFieldUiDefinition(
		definition( BuiltinFieldType.Daterange, DATERANGE_BLOCKS )
	);
	registerFieldUiDefinition(
		definition( BuiltinFieldType.Section, SECTION_BLOCKS )
	);
	registerFieldUiDefinition(
		definition( BuiltinFieldType.Measure, MEASURE_BLOCKS )
	);
	registerFieldUiDefinition(
		definition( BuiltinFieldType.Phone, PHONE_BLOCKS )
	);
	registerFieldUiDefinition(
		definition( BuiltinFieldType.Superlist, SUPERLIST_BLOCKS )
	);
	registerFieldUiDefinition(
		definition( BuiltinFieldType.Texter, TEXTER_BLOCKS )
	);
	registerFieldUiDefinition(
		definition( BuiltinFieldType.Domain, DOMAIN_BLOCKS )
	);
	registerFieldUiDefinition(
		definition( BuiltinFieldType.Collapse, COLLAPSE_BLOCKS )
	);
	registerFieldUiDefinition(
		definition( BuiltinFieldType.Quantityoption, QUANTITYOPTION_BLOCKS )
	);
	registerFieldUiDefinition(
		definition( BuiltinFieldType.Qtypack, QTYPACK_BLOCKS )
	);
	registerFieldUiDefinition(
		definition( BuiltinFieldType.Switcher, SWITCHER_BLOCKS )
	);
	registerFieldUiDefinition(
		definition( BuiltinFieldType.Chained, CHAINED_BLOCKS )
	);
	registerFieldUiDefinition(
		definition( BuiltinFieldType.ConditionalMeta, CONDITIONAL_META_BLOCKS )
	);
	registerFieldUiDefinition(
		definition( BuiltinFieldType.Fonts, FONTS_BLOCKS, [
			SETTINGS_TAB,
			PREVIEW_TAB,
			CONDITIONS_TAB,
		] )
	);
	registerFieldUiDefinition(
		definition( BuiltinFieldType.Vqmatrix, VQMATRIX_BLOCKS )
	);
	registerFieldUiDefinition(
		definition( BuiltinFieldType.Audio, AUDIO_BLOCKS )
	);
	registerFieldUiDefinition(
		definition( BuiltinFieldType.Bulkquantity, BULKQUANTITY_BLOCKS )
	);
	registerFieldUiDefinition(
		definition( BuiltinFieldType.Cropper, CROPPER_BLOCKS )
	);
	registerFieldUiDefinition(
		definition( BuiltinFieldType.Emojis, EMOJIS_BLOCKS )
	);
	registerFieldUiDefinition(
		definition( BuiltinFieldType.Fixedprice, FIXEDPRICE_BLOCKS )
	);
	registerFieldUiDefinition(
		definition( BuiltinFieldType.Image, IMAGE_BLOCKS )
	);
	registerFieldUiDefinition(
		definition( BuiltinFieldType.Imageselect, IMAGESELECT_BLOCKS )
	);
	registerFieldUiDefinition(
		definition( BuiltinFieldType.Palettes, PALETTES_BLOCKS )
	);
	registerFieldUiDefinition(
		definition( BuiltinFieldType.Pricematrix, PRICEMATRIX_BLOCKS )
	);
	registerFieldUiDefinition(
		definition( BuiltinFieldType.Quantities, QUANTITIES_BLOCKS )
	);
	registerFieldUiDefinition(
		definition( BuiltinFieldType.Selectqty, SELECTQTY_BLOCKS )
	);
}
