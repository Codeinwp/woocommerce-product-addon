/**
 * Section groupings for schema-driven (fallback) field editors — mirrors Text/Textarea/Select editors.
 */
import { SectionLabelKey } from './definitions/builtinFieldTypes';
import { classifySettingTab } from './schemaTabs';
import { isReactModalExcludedSchemaKey } from './schema/reactModalExcludedKeys';
import type { I18nDict } from './types/fieldModal';

/** Ordered section definitions; each setting key appears in at most one section (first match wins). */
export const FALLBACK_FIELD_SECTION_BLUEPRINT = [
	{
		labelKey: SectionLabelKey.Basic,
		keys: [ 'title', 'data_name', 'description', 'placeholder' ],
		advanced: false,
	},
	{
		labelKey: SectionLabelKey.FieldSettings,
		keys: [
			'required',
			'default_value',
			'selected',
			'first_option',
			'checked',
			'default_color',
			'date_formats',
			'emojis_display_type',
			'view_type',
			'view_control',
			'viewport_type',
			'multiple_allowed',
			'discount_type',
		],
		advanced: false,
	},
	{
		labelKey: SectionLabelKey.DefaultPrice,
		keys: [
			'price',
			'file_cost',
			'unit_price',
			'onetime',
			'default_price',
			'vqmatrix_label',
		],
		advanced: false,
	},
	{
		labelKey: SectionLabelKey.Constraints,
		keys: [
			'min',
			'max',
			'step',
			'min_checked',
			'max_checked',
			'min_qty',
			'max_qty',
			'max_selected',
			'files_allowed',
			'file_types',
			'file_size',
		],
		advanced: false,
	},
	{
		labelKey: SectionLabelKey.Validation,
		keys: [ 'error_message', 'maxlength', 'minlength', 'max_length' ],
		advanced: true,
	},
	{
		labelKey: SectionLabelKey.DateCalendar,
		keys: [
			'min_date',
			'max_date',
			'year_range',
			'first_day_of_week',
			'open_style',
			'tp_increment',
			'start_date',
			'end_date',
			'time_picker',
			'tp_24hours',
			'tp_seconds',
			'drop_down',
			'show_weeks',
			'auto_apply',
		],
		advanced: true,
	},
	{
		labelKey: SectionLabelKey.Display,
		keys: [
			'class',
			'input_mask',
			'width',
			'visibility',
			'button_label_select',
			'button_class',
			'palettes_colors',
			'palettes_width',
			'palettes_mode',
			'selected_palette_bcolor',
			'color_width',
			'color_height',
			'show_palettes',
			'show_onload',
		],
		advanced: true,
	},
	{
		labelKey: SectionLabelKey.Media,
		keys: [
			'options',
			'boundary',
			'enforce_boundary',
			'resize',
			'enable_zoom',
			'show_zoomer',
			'enable_exif',
			'onetime_taxable',
			'hide_matrix_table',
			'show_slider',
			'show_price_per_unit',
		],
		advanced: true,
	},
	{
		labelKey: SectionLabelKey.Behavior,
		keys: [
			'jquery_dp',
			'no_weekends',
			'past_dates',
			'use_regex',
			'rich_editor',
			'desc_tooltip',
			'circle',
			'qty_step',
			'discount',
		],
		advanced: true,
	},
	{
		labelKey: SectionLabelKey.ImageDimensions,
		keys: [
			'min_img_h',
			'max_img_h',
			'min_img_w',
			'max_img_w',
			'img_dimension_error',
		],
		imageTabOverride: true,
		advanced: true,
	},
];

function tabsClassHasImageDimensions(
	meta: Record< string, unknown > | undefined
): boolean {
	const tc =
		meta && Array.isArray( meta.tabs_class )
			? ( meta.tabs_class as string[] )
			: [];
	return tc.join( ' ' ).includes( 'ppom_handle_image_dimension_tab' );
}

export function buildFallbackGroupedSections(
	schema:
		| { settings?: Record< string, Record< string, unknown > > }
		| null
		| undefined,
	fieldType: string,
	i18n: I18nDict
): Array< { label: string; keys: string[]; advanced?: boolean } > {
	const settings =
		schema && schema.settings && typeof schema.settings === 'object'
			? schema.settings
			: {};

	/** Preserve schema key order for overflow. */
	const fieldKeysOrdered: string[] = [];
	Object.keys( settings ).forEach( ( key ) => {
		if ( isReactModalExcludedSchemaKey( key ) ) {
			return;
		}
		const meta = settings[ key ] as Record< string, unknown > | undefined;
		if ( ! meta || typeof meta !== 'object' ) {
			return;
		}
		if ( classifySettingTab( key, meta, fieldType ) === 'fields' ) {
			fieldKeysOrdered.push( key );
		}
	} );

	const fieldKeySet = new Set( fieldKeysOrdered );
	const assigned = new Set();

	const sections: Array< {
		label: string;
		keys: string[];
		advanced?: boolean;
	} > = [];

	for ( const block of FALLBACK_FIELD_SECTION_BLUEPRINT ) {
		const keys: string[] = [];

		if ( block.imageTabOverride ) {
			for ( const k of block.keys ) {
				if ( fieldKeySet.has( k ) && ! assigned.has( k ) ) {
					keys.push( k );
					assigned.add( k );
				}
			}
			for ( const k of fieldKeysOrdered ) {
				if ( assigned.has( k ) ) {
					continue;
				}
				if ( tabsClassHasImageDimensions( settings[ k ] ) ) {
					keys.push( k );
					assigned.add( k );
				}
			}
		} else {
			for ( const k of block.keys ) {
				if ( fieldKeySet.has( k ) && ! assigned.has( k ) ) {
					keys.push( k );
					assigned.add( k );
				}
			}
		}

		if ( keys.length === 0 ) {
			continue;
		}

		const label = ( i18n && i18n[ block.labelKey ] ) || block.labelKey;
		const adv =
			'advanced' in block && typeof block.advanced === 'boolean'
				? block.advanced
				: true;
		sections.push( { label, keys, advanced: adv } );
	}

	const overflow = fieldKeysOrdered.filter( ( k ) => ! assigned.has( k ) );
	if ( overflow.length > 0 ) {
		const label =
			( i18n && i18n[ SectionLabelKey.More ] ) || 'More options';
		sections.push( { label, keys: overflow, advanced: true } );
	}

	return sections;
}
