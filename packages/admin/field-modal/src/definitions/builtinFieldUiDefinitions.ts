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
		keys: [ 'title', 'data_name', 'description', 'placeholder', 'error_message' ],
	},
	{
		kind: 'section',
		id: 'validation',
		tab: 'settings',
		labelKey: 'editorSectionValidation',
		keys: [ 'maxlength', 'minlength' ],
	},
	{
		kind: 'section',
		id: 'defaults',
		tab: 'settings',
		labelKey: 'editorSectionDefaultPrice',
		keys: [ 'default_value', 'price' ],
	},
	{
		kind: 'section',
		id: 'display',
		tab: 'settings',
		labelKey: 'editorSectionDisplay',
		keys: [ 'class', 'input_mask', 'width', 'visibility', 'visibility_role' ],
	},
	{
		kind: 'section',
		id: 'behavior',
		tab: 'settings',
		labelKey: 'editorSectionBehavior',
		keys: [ 'onetime', 'use_regex', 'desc_tooltip', 'required' ],
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
		keys: [ 'title', 'data_name', 'description', 'placeholder', 'error_message' ],
	},
	{
		kind: 'section',
		id: 'defaults',
		tab: 'settings',
		labelKey: 'editorSectionDefaultPrice',
		keys: [ 'default_value', 'max_length', 'price' ],
	},
	{
		kind: 'section',
		id: 'display',
		tab: 'settings',
		labelKey: 'editorSectionDisplay',
		keys: [ 'class', 'width', 'visibility', 'visibility_role' ],
	},
	{
		kind: 'section',
		id: 'behavior',
		tab: 'settings',
		labelKey: 'editorSectionBehavior',
		keys: [ 'rich_editor', 'desc_tooltip', 'required' ],
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
		keys: [ 'title', 'data_name', 'description', 'placeholder', 'error_message' ],
	},
	{
		kind: 'section',
		id: 'display',
		tab: 'settings',
		labelKey: 'editorSectionDisplay',
		keys: [ 'class', 'width', 'visibility', 'visibility_role' ],
	},
	{
		kind: 'section',
		id: 'behavior',
		tab: 'settings',
		labelKey: 'editorSectionBehavior',
		keys: [ 'desc_tooltip', 'required' ],
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
		keys: [ 'title', 'data_name', 'description', 'placeholder', 'error_message' ],
	},
	{
		kind: 'section',
		id: 'validation',
		tab: 'settings',
		labelKey: 'editorSectionValidation',
		keys: [ 'max', 'min', 'step' ],
	},
	{
		kind: 'section',
		id: 'defaults',
		tab: 'settings',
		labelKey: 'editorSectionDefaultPrice',
		keys: [ 'default_value' ],
	},
	{
		kind: 'section',
		id: 'display',
		tab: 'settings',
		labelKey: 'editorSectionDisplay',
		keys: [ 'class', 'width', 'visibility', 'visibility_role' ],
	},
	{
		kind: 'section',
		id: 'behavior',
		tab: 'settings',
		labelKey: 'editorSectionBehavior',
		keys: [ 'desc_tooltip', 'required' ],
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
	},
	{
		kind: 'section',
		id: 'behavior',
		tab: 'settings',
		labelKey: 'editorSectionBehavior',
		keys: [ 'cart_display' ],
	},
];

const SELECT_BLOCKS: FieldUiDefinition[ 'blocks' ] = [
	{
		kind: 'section',
		id: 'basic',
		tab: 'settings',
		labelKey: 'editorSectionBasic',
		keys: [ 'title', 'data_name', 'description', 'error_message' ],
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
	},
	{
		kind: 'section',
		id: 'display',
		tab: 'settings',
		labelKey: 'editorSectionDisplay',
		keys: [ 'class', 'width', 'visibility', 'visibility_role' ],
	},
	{
		kind: 'section',
		id: 'behavior',
		tab: 'settings',
		labelKey: 'editorSectionBehavior',
		keys: [ 'desc_tooltip', 'onetime', 'required' ],
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
		keys: [ 'title', 'data_name', 'description', 'error_message' ],
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
	},
	{
		kind: 'section',
		id: 'display',
		tab: 'settings',
		labelKey: 'editorSectionDisplay',
		keys: [ 'class', 'width', 'visibility', 'visibility_role' ],
	},
	{
		kind: 'section',
		id: 'behavior',
		tab: 'settings',
		labelKey: 'editorSectionBehavior',
		keys: [ 'desc_tooltip', 'onetime', 'required' ],
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
		keys: [ 'title', 'data_name', 'description', 'error_message' ],
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
	},
	{
		kind: 'section',
		id: 'display',
		tab: 'settings',
		labelKey: 'editorSectionDisplay',
		keys: [ 'class', 'width', 'visibility', 'visibility_role' ],
	},
	{
		kind: 'section',
		id: 'behavior',
		tab: 'settings',
		labelKey: 'editorSectionBehavior',
		keys: [ 'desc_tooltip', 'onetime', 'required' ],
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
		keys: [ 'title', 'data_name', 'description', 'placeholder', 'error_message' ],
	},
	{
		kind: 'section',
		id: 'date',
		tab: 'settings',
		labelKey: 'editorSectionValidation',
		keys: [ 'date_formats', 'default_value', 'min_date', 'max_date', 'year_range', 'first_day_of_week' ],
	},
	{
		kind: 'section',
		id: 'layout',
		tab: 'settings',
		labelKey: 'editorSectionDisplay',
		keys: [ 'class', 'width', 'visibility', 'visibility_role' ],
	},
	{
		kind: 'section',
		id: 'rules',
		tab: 'settings',
		labelKey: 'editorSectionBehavior',
		keys: [ 'jquery_dp', 'no_weekends', 'past_dates', 'desc_tooltip', 'required' ],
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
		keys: [ 'title', 'data_name', 'description', 'error_message' ],
	},
	{
		kind: 'section',
		id: 'options',
		tab: 'settings',
		labelKey: 'editorSectionDefaultPrice',
		keys: [ 'selected', 'first_option', 'regions' ],
	},
	{
		kind: 'section',
		id: 'layout',
		tab: 'settings',
		labelKey: 'editorSectionDisplay',
		keys: [ 'class', 'width', 'visibility', 'visibility_role' ],
	},
	{
		kind: 'section',
		id: 'behavior',
		tab: 'settings',
		labelKey: 'editorSectionBehavior',
		keys: [ 'show_time', 'desc_tooltip', 'required' ],
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
		keys: [ 'title', 'data_name', 'description', 'error_message' ],
	},
	{
		kind: 'section',
		id: 'palette',
		tab: 'settings',
		labelKey: 'editorSectionDefaultPrice',
		keys: [ 'default_color', 'palettes_colors', 'palettes_width', 'palettes_mode' ],
	},
	{
		kind: 'section',
		id: 'layout',
		tab: 'settings',
		labelKey: 'editorSectionDisplay',
		keys: [ 'width', 'visibility', 'visibility_role' ],
	},
	{
		kind: 'section',
		id: 'behavior',
		tab: 'settings',
		labelKey: 'editorSectionBehavior',
		keys: [ 'show_palettes', 'show_onload', 'desc_tooltip', 'required' ],
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
	},
];

function sectionBlock(
	id: string,
	tab: string,
	labelKey: string,
	keys: string[]
): FieldUiDefinition[ 'blocks' ][ number ] {
	return {
		kind: 'section',
		id,
		tab,
		labelKey,
		keys,
	};
}

function widgetBlock(
	id: string,
	tab: string,
	widget: string,
	ownedKeys: string[],
	props?: Record< string, unknown >
): FieldUiDefinition[ 'blocks' ][ number ] {
	return {
		kind: 'widget',
		id,
		tab,
		widget,
		ownedKeys,
		props,
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
		[ 'title', 'data_name', 'description', 'error_message' ]
	),
	sectionBlock(
		'pricing',
		'settings',
		'editorSectionDefaultPrice',
		[ 'file_cost' ]
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
		]
	),
	sectionBlock(
		'media',
		'settings',
		'editorSectionMedia',
		[ 'files_allowed', 'file_types', 'file_size' ]
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
		]
	),
	sectionBlock(
		'behavior',
		'settings',
		'editorSectionBehavior',
		[ 'desc_tooltip', 'required', 'onetime' ]
	)
);

const DATERANGE_BLOCKS = withConditions(
	sectionBlock(
		'basic',
		'settings',
		'editorSectionBasic',
		[ 'title', 'data_name', 'description', 'error_message' ]
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
		]
	),
	sectionBlock(
		'display',
		'settings',
		'editorSectionDisplay',
		[ 'class', 'width', 'visibility', 'visibility_role' ]
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
		]
	)
);

const SECTION_BLOCKS = withConditions(
	sectionBlock(
		'basic',
		'settings',
		'editorSectionBasic',
		[ 'data_name', 'description', 'html', 'width' ]
	),
	sectionBlock(
		'display',
		'settings',
		'editorSectionDisplay',
		[ 'visibility', 'visibility_role' ]
	),
	sectionBlock(
		'behavior',
		'settings',
		'editorSectionBehavior',
		[ 'desc_tooltip', 'cart_display' ]
	)
);

const MEASURE_BLOCKS = withConditions(
	sectionBlock(
		'basic',
		'settings',
		'editorSectionBasic',
		[ 'title', 'data_name', 'description', 'error_message' ]
	),
	sectionBlock(
		'validation',
		'settings',
		'editorSectionValidation',
		[ 'max', 'min', 'step' ]
	),
	sectionBlock(
		'pricing',
		'settings',
		'editorSectionDefaultPrice',
		[ 'default_value', 'price-multiplier', 'price' ]
	),
	sectionBlock(
		'display',
		'settings',
		'editorSectionDisplay',
		[ 'class', 'width', 'visibility', 'visibility_role' ]
	),
	sectionBlock(
		'behavior',
		'settings',
		'editorSectionBehavior',
		[ 'desc_tooltip', 'required' ]
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
			'error_message',
		]
	),
	sectionBlock(
		'options',
		'settings',
		'editorSectionDefaultPrice',
		[ 'default_country' ]
	),
	sectionBlock(
		'display',
		'settings',
		'editorSectionDisplay',
		[ 'class', 'width', 'visibility', 'visibility_role' ]
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
		]
	)
);

const SUPERLIST_BLOCKS = withConditions(
	sectionBlock(
		'basic',
		'settings',
		'editorSectionBasic',
		[ 'title', 'data_name', 'description', 'error_message' ]
	),
	sectionBlock(
		'options',
		'settings',
		'editorSectionDefaultPrice',
		[ 'listoptions', 'option_exclude', 'selected' ]
	),
	sectionBlock(
		'display',
		'settings',
		'editorSectionDisplay',
		[ 'width', 'visibility', 'visibility_role' ]
	),
	sectionBlock(
		'behavior',
		'settings',
		'editorSectionBehavior',
		[ 'desc_tooltip', 'required' ]
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
		[ 'button_title', 'btn_color', 'btn_bg_color', 'width' ]
	),
	sectionBlock(
		'display',
		'settings',
		'editorSectionDisplay',
		[ 'visibility', 'visibility_role' ]
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
		]
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
		[ 'available_message', 'notavailable_message' ]
	),
	sectionBlock(
		'display',
		'settings',
		'editorSectionDisplay',
		[ 'button_label', 'button_class', 'width' ]
	),
	sectionBlock(
		'behavior',
		'settings',
		'editorSectionBehavior',
		[ 'required' ]
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
		[ 'collapse_type', 'default_open' ]
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
			'error_message',
		]
	),
	sectionBlock(
		'validation',
		'settings',
		'editorSectionValidation',
		[ 'min', 'max', 'step' ]
	),
	sectionBlock(
		'pricing',
		'settings',
		'editorSectionDefaultPrice',
		[ 'unit_price', 'default_value' ]
	),
	sectionBlock(
		'display',
		'settings',
		'editorSectionDisplay',
		[ 'class', 'width', 'visibility', 'visibility_role' ]
	),
	sectionBlock(
		'behavior',
		'settings',
		'editorSectionBehavior',
		[ 'required', 'desc_tooltip', 'onetime' ]
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
		[ 'default_price', 'pack_size', 'packsize_message' ]
	),
	sectionBlock(
		'display',
		'settings',
		'editorSectionDisplay',
		[ 'width', 'visibility', 'visibility_role' ]
	),
	sectionBlock(
		'behavior',
		'settings',
		'editorSectionBehavior',
		[ 'desc_tooltip' ]
	)
);

const SWITCHER_BLOCKS = withConditions(
	sectionBlock(
		'basic',
		'settings',
		'editorSectionBasic',
		[ 'title', 'data_name', 'description', 'error_message' ]
	),
	widgetBlock(
		'options',
		'settings',
		'paired-switch',
		[ 'options' ]
	),
	sectionBlock(
		'defaults',
		'settings',
		'editorSectionDefaultPrice',
		[ 'selected' ]
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
		]
	),
	sectionBlock(
		'display',
		'settings',
		'editorSectionDisplay',
		[ 'class', 'width', 'visibility', 'visibility_role' ]
	),
	sectionBlock(
		'behavior',
		'settings',
		'editorSectionBehavior',
		[ 'desc_tooltip', 'onetime', 'required' ]
	)
);

const CHAINED_BLOCKS = withConditions(
	sectionBlock(
		'basic',
		'settings',
		'editorSectionBasic',
		[ 'title', 'data_name', 'description', 'error_message' ]
	),
	widgetBlock(
		'options',
		'settings',
		'chained-options',
		[ 'options' ]
	),
	sectionBlock(
		'defaults',
		'settings',
		'editorSectionDefaultPrice',
		[ 'selected', 'first_option' ]
	),
	sectionBlock(
		'display',
		'settings',
		'editorSectionDisplay',
		[ 'class', 'width', 'visibility', 'visibility_role' ]
	),
	sectionBlock(
		'behavior',
		'settings',
		'editorSectionBehavior',
		[ 'desc_tooltip', 'onetime', 'required' ]
	)
);

const CONDITIONAL_META_BLOCKS = withConditions(
	sectionBlock(
		'basic',
		'settings',
		'editorSectionBasic',
		[ 'title', 'data_name', 'description', 'error_message' ]
	),
	widgetBlock(
		'images',
		'settings',
		'conditional-images',
		[ 'images' ]
	),
	sectionBlock(
		'appearance',
		'settings',
		'editorSectionDisplay',
		[ 'class', 'selected_optionclr', 'width' ]
	),
	sectionBlock(
		'display',
		'settings',
		'editorSectionDisplay',
		[ 'visibility', 'visibility_role' ]
	),
	sectionBlock(
		'behavior',
		'settings',
		'editorSectionBehavior',
		[ 'desc_tooltip' ]
	)
);

const PREVIEW_TAB = { id: 'preview', labelKey: 'Preview' };

const FONTS_BLOCKS: FieldUiDefinition[ 'blocks' ] = [
	sectionBlock(
		'basic',
		'settings',
		'editorSectionBasic',
		[
			'title',
			'data_name',
			'description',
			'error_message',
			'first_option',
			'width',
			'required',
			'desc_tooltip',
		]
	),
	widgetBlock(
		'options',
		'settings',
		'fonts-paired',
		[ 'options' ]
	),
	sectionBlock(
		'custom-fonts',
		'settings',
		'editorSectionDisplay',
		[ 'custom_fonts' ]
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
		]
	),
	sectionBlock(
		'display',
		'settings',
		'editorSectionDisplay',
		[ 'width', 'visibility', 'visibility_role' ]
	),
	sectionBlock(
		'behavior',
		'settings',
		'editorSectionBehavior',
		[ 'desc_tooltip' ]
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
}
