/**
 * Static UI strings owned by the React field modal.
 *
 * Field catalog/schema labels remain server-owned so PPOM Pro and PHP filters
 * can inject field/provider copy without the free React bundle knowing it.
 */
import { __ } from '@wordpress/i18n';
import type { I18nDict } from './types/fieldModal';

export const modalStrings: I18nDict = {
	newFieldModal: __( 'PPOM fields', 'woocommerce-product-addon' ),
	fieldModalTitleWithType: __( '%1$s · %2$s', 'woocommerce-product-addon' ),
	selectFieldType: __( 'Select field type', 'woocommerce-product-addon' ),
	save: __( 'Save', 'woocommerce-product-addon' ),
	close: __( 'Close', 'woocommerce-product-addon' ),
	back: __( 'Back', 'woocommerce-product-addon' ),
	cancel: __( 'Cancel', 'woocommerce-product-addon' ),
	cancelConfirm: __( 'Confirm', 'woocommerce-product-addon' ),
	dismiss: __( 'Dismiss', 'woocommerce-product-addon' ),
	loading: __( 'Loading…', 'woocommerce-product-addon' ),
	addField: __( 'Add field', 'woocommerce-product-addon' ),
	editField: __( 'Edit field', 'woocommerce-product-addon' ),
	dataName: __( 'Data name', 'woocommerce-product-addon' ),
	dataNameRequired: __(
		'Data Name must be required',
		'woocommerce-product-addon'
	),
	dataNameExists: __(
		'Data Name already exists',
		'woocommerce-product-addon'
	),
	title: __( 'Title', 'woocommerce-product-addon' ),
	fieldType: __( 'Field type', 'woocommerce-product-addon' ),
	remove: __( 'Remove', 'woocommerce-product-addon' ),
	fieldsList: __( 'Fields', 'woocommerce-product-addon' ),
	searchFieldTypes: __( 'Search field types…', 'woocommerce-product-addon' ),
	noTypesMatch: __(
		'No types match your search.',
		'woocommerce-product-addon'
	),
	noFieldTypes: __(
		'No field types are available.',
		'woocommerce-product-addon'
	),
	selectOrAddHint: __(
		'Select a field from the list or add a new one.',
		'woocommerce-product-addon'
	),
	allTab: __( 'All', 'woocommerce-product-addon' ),
	proBadge: __( 'PRO', 'woocommerce-product-addon' ),
	openLegacyModal: __( 'Open classic editor', 'woocommerce-product-addon' ),
	manageFieldsEmpty: __(
		'No fields yet. Use Add field above or choose a field type below.',
		'woocommerce-product-addon'
	),
	manageFieldsEmptyRight: __(
		'Choose a field type from the list on the left.',
		'woocommerce-product-addon'
	),
	openAddFieldType: __( 'Choose field type', 'woocommerce-product-addon' ),
	unknownFieldTypeTitle: __(
		'This field opens in the classic editor.',
		'woocommerce-product-addon'
	),
	unknownFieldTypeHint: __(
		'This field type is not available in the React editor yet.',
		'woocommerce-product-addon'
	),
	fieldModalEditorUnavailable: __(
		'Settings for this field could not be loaded in this editor. Reload the page or try again.',
		'woocommerce-product-addon'
	),
	schemaEmptyResponse: __(
		'No field settings were returned for this type. Check that the field type is registered.',
		'woocommerce-product-addon'
	),
	unsupportedControl: __(
		'This control is not available in the React field editor yet.',
		'woocommerce-product-addon'
	),
};

export const guideStrings: I18nDict = {
	fieldGuideEmptyTitle: __( 'Field Guide', 'woocommerce-product-addon' ),
	fieldGuideEmptyBody: __(
		'Hover over any field to see what it does and when to use it.',
		'woocommerce-product-addon'
	),
	fieldGuideRegion: __( 'Field guide', 'woocommerce-product-addon' ),
	examplesLabel: __( 'Examples:', 'woocommerce-product-addon' ),
	featuresLabel: __( 'Features:', 'woocommerce-product-addon' ),
	notRightForLabel: __(
		'Not the right field if:',
		'woocommerce-product-addon'
	),
};

export const editorSectionStrings: I18nDict = {
	fieldsTab: __( 'Fields', 'woocommerce-product-addon' ),
	settingsTab: __( 'Settings', 'woocommerce-product-addon' ),
	conditionsTab: __( 'Conditions', 'woocommerce-product-addon' ),
	repeaterTab: __( 'Conditional Repeater', 'woocommerce-product-addon' ),
	showAdvancedSettings: __(
		'Show advanced settings',
		'woocommerce-product-addon'
	),
	hideAdvancedSettings: __(
		'Hide advanced settings',
		'woocommerce-product-addon'
	),
	editorSectionBasic: __( 'Basic', 'woocommerce-product-addon' ),
	editorSectionFieldSettings: __(
		'Field Settings',
		'woocommerce-product-addon'
	),
	editorSectionDefaultPrice: __( 'Pricing', 'woocommerce-product-addon' ),
	editorSectionConstraints: __( 'Constraints', 'woocommerce-product-addon' ),
	editorSectionValidation: __( 'Validation', 'woocommerce-product-addon' ),
	editorSectionDisplay: __( 'Display & layout', 'woocommerce-product-addon' ),
	editorSectionBehavior: __( 'Behavior', 'woocommerce-product-addon' ),
	editorSectionDateCalendar: __(
		'Date & calendar',
		'woocommerce-product-addon'
	),
	editorSectionImageDimensions: __(
		'Image dimensions',
		'woocommerce-product-addon'
	),
	editorSectionMedia: __( 'Media & layout', 'woocommerce-product-addon' ),
	editorSectionVariationLayout: __(
		'Layout & quantity limits',
		'woocommerce-product-addon'
	),
	editorSectionMore: __( 'More options', 'woocommerce-product-addon' ),
	editorSectionImageSettings: __(
		'Image Settings',
		'woocommerce-product-addon'
	),
};

export const conditionStrings: I18nDict = {
	cfrSectionTitle: __( 'Conditional Repeater', 'woocommerce-product-addon' ),
	cfrLockedBody: __(
		'Repeat fields based on another field’s value (e.g. quantity). Upgrade to PPOM Pro Plus or higher to enable Conditional Repeater.',
		'woocommerce-product-addon'
	),
	cfrEnableLabel: __(
		'Enable Conditional Repeat',
		'woocommerce-product-addon'
	),
	cfrOriginLabel: __( 'Origin', 'woocommerce-product-addon' ),
	cfrOriginPlaceholder: __(
		'Select origin field…',
		'woocommerce-product-addon'
	),
	cfrOriginNone: __( 'None', 'woocommerce-product-addon' ),
	cfrOriginHelp: __(
		'Only Number, Variation Quantity, and Quantity Pack fields can be the origin.',
		'woocommerce-product-addon'
	),
	cfrMagicTagsHeading: __( 'Magic tags', 'woocommerce-product-addon' ),
	cfrMagicTagsDescription: __(
		'Magic tags can be used in the Field Title (under the Fields tab).',
		'woocommerce-product-addon'
	),
	cfrLearnMore: __( 'Learn more', 'woocommerce-product-addon' ),
	cfrCopied: __( 'Copied', 'woocommerce-product-addon' ),
	cfrCopyFallback: __( 'Copy this value:', 'woocommerce-product-addon' ),
	cfrUpgradeCta: __( 'Upgrade to Pro', 'woocommerce-product-addon' ),
	cfrViewDemoLabel: __( 'View demo', 'woocommerce-product-addon' ),
	condShow: __( 'Show', 'woocommerce-product-addon' ),
	condHide: __( 'Hide', 'woocommerce-product-addon' ),
	condAll: __( 'All', 'woocommerce-product-addon' ),
	condAny: __( 'Any', 'woocommerce-product-addon' ),
	condOnlyIf: __( 'only if', 'woocommerce-product-addon' ),
	condFollowingMatches: __(
		'of the following matches',
		'woocommerce-product-addon'
	),
	condRule: __( 'Rule', 'woocommerce-product-addon' ),
	condGroupValue: __( 'Value Comparison', 'woocommerce-product-addon' ),
	condGroupText: __( 'Text Matching', 'woocommerce-product-addon' ),
	condGroupNumeric: __( 'Numeric Comparison', 'woocommerce-product-addon' ),
	condOpIs: __( 'is', 'woocommerce-product-addon' ),
	condOpNot: __( 'is not', 'woocommerce-product-addon' ),
	condOpEmpty: __( 'is empty', 'woocommerce-product-addon' ),
	condOpAny: __( 'has any value', 'woocommerce-product-addon' ),
	condOpContains: __( 'contains', 'woocommerce-product-addon' ),
	condOpNotContains: __( 'does not contain', 'woocommerce-product-addon' ),
	condOpRegex: __( 'matches RegEx', 'woocommerce-product-addon' ),
	condOpGreater: __( 'greater than', 'woocommerce-product-addon' ),
	condOpLess: __( 'less than', 'woocommerce-product-addon' ),
	condOpBetween: __( 'is between', 'woocommerce-product-addon' ),
	condOpMultiple: __( 'is multiple of', 'woocommerce-product-addon' ),
	condOpEven: __( 'is even', 'woocommerce-product-addon' ),
	condOpOdd: __( 'is odd', 'woocommerce-product-addon' ),
	condAnd: __( 'and', 'woocommerce-product-addon' ),
	condAddRule: __( 'Add rule', 'woocommerce-product-addon' ),
	condRemoveRule: __( 'Remove rule', 'woocommerce-product-addon' ),
	condTargetField: __( 'Field', 'woocommerce-product-addon' ),
	condOperator: __( 'Operator', 'woocommerce-product-addon' ),
	condValue: __( 'Value', 'woocommerce-product-addon' ),
	condSelectField: __( 'Select a field…', 'woocommerce-product-addon' ),
	condSelectValue: __( 'Select value…', 'woocommerce-product-addon' ),
	condShowHide: __( 'Visibility', 'woocommerce-product-addon' ),
	condAllAny: __( 'Match mode', 'woocommerce-product-addon' ),
	condEnableLogicHint: __(
		'Turn on "Use conditional logic" above to apply these rules on the product page.',
		'woocommerce-product-addon'
	),
	conditionUpgradeCta: __( 'Upgrade to Unlock', 'woocommerce-product-addon' ),
};

export const optionEditorStrings: I18nDict = {
	selectOptionsTitle: __( 'Options', 'woocommerce-product-addon' ),
	pairedOptionsHelper: __(
		'Add the choices customers can select.',
		'woocommerce-product-addon'
	),
	pairedOptionsAddRow: __( 'Add option', 'woocommerce-product-addon' ),
	pairedOptionsAddFirst: __(
		'Add your first option',
		'woocommerce-product-addon'
	),
	pairedOptionsRemove: __( 'Remove', 'woocommerce-product-addon' ),
	pairedOptionsMoveUp: __( 'Move up', 'woocommerce-product-addon' ),
	pairedOptionsMoveDown: __( 'Move down', 'woocommerce-product-addon' ),
	pairedOptionsDragHandle: __(
		'Drag to reorder',
		'woocommerce-product-addon'
	),
	pairedOptionsEmptyTitle: __(
		'No options yet',
		'woocommerce-product-addon'
	),
	pairedOptionsEmptyDescription: __(
		'Add at least one option for customers to choose from.',
		'woocommerce-product-addon'
	),
	pairedOptionLabel: __( 'Option', 'woocommerce-product-addon' ),
	pairedOptionPrice: __( 'Price', 'woocommerce-product-addon' ),
	pairedOptionWeight: __( 'Weight', 'woocommerce-product-addon' ),
	pairedOptionStock: __( 'Stock', 'woocommerce-product-addon' ),
	pairedOptionId: __( 'Option ID', 'woocommerce-product-addon' ),
	pairedOptionImageId: __( 'Image ID', 'woocommerce-product-addon' ),
	pairedOptionDiscount: __( 'Discount', 'woocommerce-product-addon' ),
	pairedOptionTooltip: __( 'Tooltip', 'woocommerce-product-addon' ),
	pairedMatrixOption: __( 'Option', 'woocommerce-product-addon' ),
	pairedMatrixPrice: __( 'Price', 'woocommerce-product-addon' ),
	pairedMatrixLabel: __( 'Label', 'woocommerce-product-addon' ),
	pairedMatrixOptionId: __( 'Unique option ID', 'woocommerce-product-addon' ),
	pairedMatrixFixed: __( 'Fixed', 'woocommerce-product-addon' ),
	palettesOptionsTitle: __( 'Add colors', 'woocommerce-product-addon' ),
	priceMatrixOptionsTitle: __( 'Price matrix', 'woocommerce-product-addon' ),
	bulkQuantityOptionsTitle: __(
		'Bulk quantity',
		'woocommerce-product-addon'
	),
	bulkQtyColumnQuantityRange: __(
		'Quantity Range',
		'woocommerce-product-addon'
	),
	bulkQtyColumnBasePrice: __( 'Base Price', 'woocommerce-product-addon' ),
	bulkQtyAddRow: __( 'Add quantity range', 'woocommerce-product-addon' ),
	bulkQtyAddVariation: __(
		'Add variation column',
		'woocommerce-product-addon'
	),
	bulkQtyNewVariationPrompt: __(
		'Variation column name',
		'woocommerce-product-addon'
	),
	bulkQtyRangePlaceholder: __( '1-10', 'woocommerce-product-addon' ),
	bulkQtyRangeHint: __(
		'Quantity range format: start-end (e.g. 1-10).',
		'woocommerce-product-addon'
	),
	bulkQtyActions: __( 'Actions', 'woocommerce-product-addon' ),
	fixedPriceOptionsTitle: __( 'Quantity', 'woocommerce-product-addon' ),
	fixedPriceQtyPlaceholder: __(
		'Quantity e.g 1000',
		'woocommerce-product-addon'
	),
	fixedPricePricePlaceholder: __(
		'Fixed Price',
		'woocommerce-product-addon'
	),
	selectQtyOptionsTitle: __( 'Add options', 'woocommerce-product-addon' ),
	emojisOptionsTitle: __( 'Add colors', 'woocommerce-product-addon' ),
	cropperViewportTitle: __( 'Viewport Size', 'woocommerce-product-addon' ),
	cropperViewportAddRow: __( 'Add viewport', 'woocommerce-product-addon' ),
	cropperViewportLabel: __( 'Label', 'woocommerce-product-addon' ),
	cropperViewportWidth: __( 'Width', 'woocommerce-product-addon' ),
	cropperViewportHeight: __( 'Height', 'woocommerce-product-addon' ),
	cropperViewportPrice: __( 'Price (optional)', 'woocommerce-product-addon' ),
	quantityOptionsTitle: __( 'Options', 'woocommerce-product-addon' ),
	quantityPairedAddRow: __( 'Add option', 'woocommerce-product-addon' ),
	quantityPairedOptionPlaceholder: __(
		'Option',
		'woocommerce-product-addon'
	),
	quantityPairedPricePlaceholder: __(
		'Price (if any)',
		'woocommerce-product-addon'
	),
	quantityPairedWeightPlaceholder: __(
		'Weight (if any)',
		'woocommerce-product-addon'
	),
	quantityPairedDefaultPlaceholder: __(
		'Default qty',
		'woocommerce-product-addon'
	),
	quantityPairedMinPlaceholder: __( 'Min qty', 'woocommerce-product-addon' ),
	quantityPairedMaxPlaceholder: __( 'Max qty', 'woocommerce-product-addon' ),
	quantityPairedStockPlaceholder: __( 'Stock', 'woocommerce-product-addon' ),
	vqmatrixColumnsTitle: __( 'Priced Options', 'woocommerce-product-addon' ),
	vqmatrixRowsTitle: __( 'Matrix Rows', 'woocommerce-product-addon' ),
	vqmatrixAddRow: __( 'Add row option', 'woocommerce-product-addon' ),
	fontsOptionsTitle: __( 'Font Families', 'woocommerce-product-addon' ),
	fontsAddRow: __( 'Add font family', 'woocommerce-product-addon' ),
	fontsRemove: __( 'Remove', 'woocommerce-product-addon' ),
	fontsCustomFont: __( 'Custom Font', 'woocommerce-product-addon' ),
};

export const mediaStrings: I18nDict = {
	imagesSelectUpload: __(
		'Select/Upload Image',
		'woocommerce-product-addon'
	),
	imagesMediaTitle: __( 'Choose Images', 'woocommerce-product-addon' ),
	imagesMediaButton: __( 'Select', 'woocommerce-product-addon' ),
	imagesReplaceTitle: __( 'Replace image', 'woocommerce-product-addon' ),
	imagesReplaceButton: __( 'Replace', 'woocommerce-product-addon' ),
	imagesTitle: __( 'Title', 'woocommerce-product-addon' ),
	imagesPrice: __( 'Price', 'woocommerce-product-addon' ),
	imagesPricePlaceholder: __(
		'Price (fix or %)',
		'woocommerce-product-addon'
	),
	imagesStock: __( 'Stock', 'woocommerce-product-addon' ),
	imagesUrl: __( 'URL', 'woocommerce-product-addon' ),
	imagesRemove: __( 'Remove', 'woocommerce-product-addon' ),
	imagesMoveUp: __( 'Move up', 'woocommerce-product-addon' ),
	imagesMoveDown: __( 'Move down', 'woocommerce-product-addon' ),
	imagesDragHandle: __( 'Drag to reorder', 'woocommerce-product-addon' ),
	imagesReplace: __( 'Replace image', 'woocommerce-product-addon' ),
	imagesMetaId: __( 'Meta IDs', 'woocommerce-product-addon' ),
	imagesPriceType: __( 'Type', 'woocommerce-product-addon' ),
	imagesPriceTypeFixed: __( 'Fixed', 'woocommerce-product-addon' ),
	imagesPriceTypePercent: __( '%', 'woocommerce-product-addon' ),
	imagesDescription: __( 'Description', 'woocommerce-product-addon' ),
	imagesEmptyState: __(
		'No images selected. Click the button above to add images.',
		'woocommerce-product-addon'
	),
	imagesSectionHelper: __(
		'Add images customers can select.',
		'woocommerce-product-addon'
	),
	imagesAddMore: __( 'Add image', 'woocommerce-product-addon' ),
	imagesFooterSingular: __( '1 image', 'woocommerce-product-addon' ),
	imagesFooterPlural: __( '{count} images', 'woocommerce-product-addon' ),
	imagesEmptyTitle: __(
		'Add images to this field',
		'woocommerce-product-addon'
	),
	imagesEmptyDescription: __(
		'Upload new images or choose existing ones from the media library.',
		'woocommerce-product-addon'
	),
	imagesUploadButton: __( 'Upload images', 'woocommerce-product-addon' ),
	imagesLibraryButton: __(
		'Choose from library',
		'woocommerce-product-addon'
	),
	imagesEmptySupport: __(
		'Supported images can include prices, stock, and descriptions.',
		'woocommerce-product-addon'
	),
	addImagesSectionTitle: __( 'Images', 'woocommerce-product-addon' ),
	addAudioVideoSectionTitle: __(
		'Audio / Video',
		'woocommerce-product-addon'
	),
	audioMediaTitle: __( 'Choose audio or video', 'woocommerce-product-addon' ),
	audioMediaButton: __( 'Select', 'woocommerce-product-addon' ),
	audioSelectUpload: __( 'Select Audio/Video', 'woocommerce-product-addon' ),
	audioEmptyState: __(
		'No audio or video selected. Use the button above to add files from the media library.',
		'woocommerce-product-addon'
	),
	audioPricePlaceholder: __(
		'Price (fix or %)',
		'woocommerce-product-addon'
	),
	audioTitlePlaceholder: __( 'Title', 'woocommerce-product-addon' ),
	audioMoveUp: __( 'Move up', 'woocommerce-product-addon' ),
	audioMoveDown: __( 'Move down', 'woocommerce-product-addon' ),
	audioRemove: __( 'Remove', 'woocommerce-product-addon' ),
	audioDragHandle: __( 'Drag to reorder', 'woocommerce-product-addon' ),
};

export const fieldModalI18n: I18nDict = {
	...modalStrings,
	...guideStrings,
	...editorSectionStrings,
	...conditionStrings,
	...optionEditorStrings,
	...mediaStrings,
};
