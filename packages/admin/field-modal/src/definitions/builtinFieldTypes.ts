/**
 * Centralised string constants for built-in field types, section label keys,
 * tab IDs, and widget kinds.  Every consumer imports from here instead of
 * repeating raw string literals.
 */

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
// Section label keys (i18n dictionary lookups)
// ---------------------------------------------------------------------------

export const SectionLabelKey = {
	Basic: 'editorSectionBasic',
	FieldSettings: 'editorSectionFieldSettings',
	DefaultPrice: 'editorSectionDefaultPrice',
	Validation: 'editorSectionValidation',
	Display: 'editorSectionDisplay',
	Behavior: 'editorSectionBehavior',
	Constraints: 'editorSectionConstraints',
	DateCalendar: 'editorSectionDateCalendar',
	ImageDimensions: 'editorSectionImageDimensions',
	ImageSettings: 'editorSectionImageSettings',
	Media: 'editorSectionMedia',
	More: 'editorSectionMore',
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
