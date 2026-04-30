/**
 * Centralised string constants for built-in field types, section labels, tab IDs,
 * and widget kinds. Every consumer imports from here instead of repeating raw
 * string literals.
 */
import { __ } from '@wordpress/i18n';

// ---------------------------------------------------------------------------
// Field type slugs
// ---------------------------------------------------------------------------

export const BuiltinFieldType = {
	Text: 'text',
	Textcounter: 'textcounter',
	Textarea: 'textarea',
	Email: 'email',
	Number: 'number',
	Hidden: 'hidden',
	Date: 'date',
	Timezone: 'timezone',
	Color: 'color',
	Divider: 'divider',
	Select: 'select',
	Checkbox: 'checkbox',
	Radio: 'radio',
	File: 'file',
	Daterange: 'daterange',
	Section: 'section',
	Measure: 'measure',
	Phone: 'phone',
	Superlist: 'superlist',
	Texter: 'texter',
	Domain: 'domain',
	Collapse: 'collapse',
	Quantityoption: 'quantityoption',
	Qtypack: 'qtypack',
	Switcher: 'switcher',
	Chained: 'chained',
	ConditionalMeta: 'conditional_meta',
	Fonts: 'fonts',
	Vqmatrix: 'vqmatrix',
	Audio: 'audio',
	Bulkquantity: 'bulkquantity',
	Cropper: 'cropper',
	Emojis: 'emojis',
	Fixedprice: 'fixedprice',
	Image: 'image',
	Imageselect: 'imageselect',
	Palettes: 'palettes',
	Pricematrix: 'pricematrix',
	Quantities: 'quantities',
	Selectqty: 'selectqty',
} as const;

export type BuiltinFieldTypeValue =
	( typeof BuiltinFieldType )[ keyof typeof BuiltinFieldType ];

// ---------------------------------------------------------------------------
// Section labels
// ---------------------------------------------------------------------------

export const SectionLabelKey = {
	Basic: __( 'Basic', 'woocommerce-product-addon' ),
	FieldSettings: __( 'Field Settings', 'woocommerce-product-addon' ),
	DefaultPrice: __( 'Pricing', 'woocommerce-product-addon' ),
	Validation: __( 'Validation', 'woocommerce-product-addon' ),
	Display: __( 'Display & layout', 'woocommerce-product-addon' ),
	Behavior: __( 'Behavior', 'woocommerce-product-addon' ),
	Constraints: __( 'Constraints', 'woocommerce-product-addon' ),
	DateCalendar: __( 'Date & calendar', 'woocommerce-product-addon' ),
	ImageDimensions: __( 'Image dimensions', 'woocommerce-product-addon' ),
	ImageSettings: __( 'Image Settings', 'woocommerce-product-addon' ),
	Media: __( 'Media & layout', 'woocommerce-product-addon' ),
	More: __( 'More options', 'woocommerce-product-addon' ),
} as const;

export type SectionLabelKeyValue =
	( typeof SectionLabelKey )[ keyof typeof SectionLabelKey ];

// ---------------------------------------------------------------------------
// Tab IDs
// ---------------------------------------------------------------------------

export const FieldTab = {
	Settings: 'settings',
	Conditions: 'conditions',
	Preview: 'preview',
} as const;

export type FieldTabValue = ( typeof FieldTab )[ keyof typeof FieldTab ];

// ---------------------------------------------------------------------------
// Widget kinds
// ---------------------------------------------------------------------------

export const WidgetKind = {
	PairedOptions: 'paired-options',
	PairedQuantity: 'paired-quantity',
	PairedSwitch: 'paired-switch',
	PairedCropper: 'paired-cropper',
	PairedPalettes: 'paired-palettes',
	PairedPricematrix: 'paired-pricematrix',
	FixedPricePaired: 'fixed-price-paired',
	ChainedOptions: 'chained-options',
	ConditionalImages: 'conditional-images',
	FontsPaired: 'fonts-paired',
	Vqmatrix: 'vqmatrix',
	AudioMedia: 'audio-media',
	BulkQuantity: 'bulk-quantity',
	ImageMedia: 'image-media',
	ImageselectMedia: 'imageselect-media',
} as const;

export type WidgetKindValue = ( typeof WidgetKind )[ keyof typeof WidgetKind ];
