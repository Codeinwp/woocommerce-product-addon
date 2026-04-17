/**
 * Registers hand-authored `FieldUiDefinition` entries (scalars + paired choice types).
 */
import { registerFieldUiDefinition } from './registry';
import type { FieldUiDefinition } from './types';

const SETTINGS_TAB = { id: 'settings', labelKey: 'settingsTab' };
const CONDITIONS_TAB = { id: 'conditions', labelKey: 'conditionsTab' };

const TEXT_LIKE_BLOCKS: FieldUiDefinition[ 'blocks' ] = [
	{
		kind: 'section',
		id: 'basic',
		tab: 'settings',
		labelKey: 'editorSectionBasic',
		keys: [
			'title',
			'data_name',
			'description',
			'placeholder',
			'error_message',
		],
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
		keys: [ 'class', 'input_mask', 'width', 'visibility' ],
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
	return {
		slug,
		tabs: [ SETTINGS_TAB, CONDITIONS_TAB ],
		blocks: TEXT_LIKE_BLOCKS,
	};
}

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
		keys: [ 'class', 'width', 'visibility' ],
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
		id: 'display',
		tab: 'settings',
		labelKey: 'editorSectionDisplay',
		keys: [ 'class', 'width', 'visibility', 'visibility_role' ],
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
		keys: [
			'title',
			'data_name',
			'description',
			'placeholder',
			'error_message',
		],
	},
	{
		kind: 'section',
		id: 'date',
		tab: 'settings',
		labelKey: 'editorSectionValidation',
		keys: [
			'date_formats',
			'default_value',
			'min_date',
			'max_date',
			'year_range',
			'first_day_of_week',
		],
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
		keys: [
			'jquery_dp',
			'no_weekends',
			'past_dates',
			'desc_tooltip',
			'required',
		],
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
		keys: [
			'title',
			'data_name',
			'description',
			'error_message',
		],
	},
	{
		kind: 'section',
		id: 'opts',
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
		keys: [
			'default_color',
			'palettes_colors',
			'palettes_width',
			'palettes_mode',
		],
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
		keys: [
			'divider_styles',
			'style1_border',
			'divider_height',
			'divider_txtsize',
			'divider_color',
			'divider_txtclr',
		],
	},
];

export function registerBuiltinFieldUiDefinitions(): void {
	registerFieldUiDefinition( textLikeDefinition( 'text' ) );
	registerFieldUiDefinition( textLikeDefinition( 'textarea' ) );
	registerFieldUiDefinition( textLikeDefinition( 'email' ) );
	registerFieldUiDefinition( textLikeDefinition( 'number' ) );
	registerFieldUiDefinition( textLikeDefinition( 'hidden' ) );
	registerFieldUiDefinition( textLikeDefinition( 'textcounter' ) );
	registerFieldUiDefinition( {
		slug: 'date',
		tabs: [ SETTINGS_TAB, CONDITIONS_TAB ],
		blocks: DATE_BLOCKS,
	} );
	registerFieldUiDefinition( {
		slug: 'timezone',
		tabs: [ SETTINGS_TAB, CONDITIONS_TAB ],
		blocks: TIMEZONE_BLOCKS,
	} );
	registerFieldUiDefinition( {
		slug: 'color',
		tabs: [ SETTINGS_TAB, CONDITIONS_TAB ],
		blocks: COLOR_BLOCKS,
	} );
	registerFieldUiDefinition( {
		slug: 'divider',
		tabs: [ SETTINGS_TAB ],
		blocks: DIVIDER_BLOCKS,
	} );
	registerFieldUiDefinition( {
		slug: 'select',
		tabs: [ SETTINGS_TAB, CONDITIONS_TAB ],
		blocks: SELECT_BLOCKS,
	} );
	registerFieldUiDefinition( {
		slug: 'checkbox',
		tabs: [ SETTINGS_TAB, CONDITIONS_TAB ],
		blocks: CHECKBOX_BLOCKS,
	} );
	registerFieldUiDefinition( {
		slug: 'radio',
		tabs: [ SETTINGS_TAB, CONDITIONS_TAB ],
		blocks: RADIO_BLOCKS,
	} );
}
