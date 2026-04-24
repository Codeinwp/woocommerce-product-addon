/**
 * Registers default field-modal widgets.
 *
 * Each widget is a typed Lego brick: `{ id, render, ownedKeys? }`. Definitions in
 * `definitions/` reference them by id. Add new widgets here (or from third-party code
 * via `ppomFieldModal.registerWidget`) — do not fork the shell.
 *
 * Simple "render editor X when meta.type === Y" dispatchers go through
 * `registerMetaTypeWidget`; widgets that also vary on `widgetProps` (e.g. the
 * shared `paired-options` widget picking between select/radio/checkbox) stay
 * custom below.
 */
import { PairedOptionsEditor } from '../components/PairedOptionsEditor';
import type { PairedOptionsVariant } from '../components/PairedOptionsEditor';
import { AudiosSelectEditor } from '../components/AudiosSelectEditor';
import { BulkQuantityMatrixEditor } from '../components/BulkQuantityMatrixEditor';
import { ChainedOptionsEditor } from '../components/ChainedOptionsEditor';
import { FontsPairedEditor } from '../components/FontsPairedEditor';
import { ImagesSelectEditor } from '../components/ImagesSelectEditor';
import { PairedCropperEditor } from '../components/PairedCropperEditor';
import { PairedFixedPriceEditor } from '../components/PairedFixedPriceEditor';
import { PairedMatrixOptionsEditor } from '../components/PairedMatrixOptionsEditor';
import { PairedQuantityEditor } from '../components/PairedQuantityEditor';
import { VqMatrixEditor } from '../components/VqMatrixEditor';
import { readSettingMeta, readSettingTitle } from '../definitions/settingMeta';
import { registerFieldWidget } from './registry';
import { registerMetaTypeWidget } from './registerMetaTypeWidget';

registerFieldWidget( {
	id: 'paired-options',
	ownedKeys: [ 'options' ],
	render: ( ctx ) => {
		const optionsMeta = readSettingMeta( ctx.schema, 'options' );
		if ( ! optionsMeta || optionsMeta.type !== 'paired' ) {
			return null;
		}
		const variant =
			( ctx.widgetProps?.variant as PairedOptionsVariant | undefined ) ||
			'select';
		const title = readSettingTitle(
			ctx.schema,
			'options',
			ctx.i18n.selectOptionsTitle
		);
		return (
			<PairedOptionsEditor
				variant={ variant }
				values={ ctx.field }
				onChange={ ctx.updateField }
				i18n={ ctx.i18n }
				title={ title }
			/>
		);
	},
} );

registerMetaTypeWidget( {
	id: 'audio-media',
	ownedKeys: [ 'audio' ],
	metaKey: 'audio',
	metaType: 'pre-audios',
	titleFallback: ( i18n ) => i18n.addAudioVideoSectionTitle,
	render: ( { ctx, title } ) => (
		<AudiosSelectEditor
			values={ ctx.field }
			onChange={ ctx.updateField }
			i18n={ ctx.i18n }
			title={ title }
		/>
	),
} );

registerMetaTypeWidget( {
	id: 'image-media',
	ownedKeys: [ 'images' ],
	metaKey: 'images',
	metaType: 'pre-images',
	titleFallback: ( i18n ) => i18n.addImagesSectionTitle,
	render: ( { ctx, title } ) => (
		<ImagesSelectEditor
			variant="image"
			values={ ctx.field }
			onChange={ ctx.updateField }
			i18n={ ctx.i18n }
			title={ title }
		/>
	),
} );

registerMetaTypeWidget( {
	id: 'imageselect-media',
	ownedKeys: [ 'images' ],
	metaKey: 'images',
	metaType: 'imageselect',
	titleFallback: ( i18n ) => i18n.addImagesSectionTitle,
	render: ( { ctx, title } ) => (
		<ImagesSelectEditor
			variant="imageselect"
			values={ ctx.field }
			onChange={ ctx.updateField }
			i18n={ ctx.i18n }
			title={ title }
		/>
	),
} );

registerMetaTypeWidget( {
	id: 'paired-cropper',
	ownedKeys: [ 'options' ],
	metaKey: ( ctx ) =>
		typeof ctx.widgetProps?.fieldKey === 'string'
			? String( ctx.widgetProps.fieldKey )
			: 'options',
	metaType: 'paired-cropper',
	titleFallback: ( i18n ) => i18n.cropperViewportTitle,
	render: ( { ctx, title, description, metaKey } ) => (
		<PairedCropperEditor
			fieldKey={ metaKey }
			title={ title }
			description={ description }
			values={ ctx.field }
			onChange={ ctx.updateField }
			i18n={ ctx.i18n }
		/>
	),
} );

registerMetaTypeWidget( {
	id: 'paired-quantity',
	ownedKeys: [ 'options' ],
	metaKey: ( ctx ) =>
		typeof ctx.widgetProps?.fieldKey === 'string'
			? String( ctx.widgetProps.fieldKey )
			: 'options',
	metaType: 'paired-quantity',
	titleFallback: ( i18n ) => i18n.quantityOptionsTitle,
	render: ( { ctx, title, description, metaKey } ) => (
		<PairedQuantityEditor
			fieldKey={ metaKey }
			title={ title }
			description={ description }
			values={ ctx.field }
			onChange={ ctx.updateField }
			i18n={ ctx.i18n }
		/>
	),
} );

registerMetaTypeWidget( {
	id: 'fixed-price-paired',
	ownedKeys: [ 'options' ],
	metaKey: 'options',
	metaType: 'paired',
	titleFallback: ( i18n ) => i18n.fixedPriceOptionsTitle,
	render: ( { ctx, title, meta } ) => (
		<PairedFixedPriceEditor
			values={ ctx.field }
			onChange={ ctx.updateField }
			i18n={ ctx.i18n }
			title={ title }
			placeholders={
				Array.isArray( meta.placeholders )
					? ( meta.placeholders as string[] )
					: undefined
			}
			types={
				Array.isArray( meta.types )
					? ( meta.types as string[] )
					: undefined
			}
		/>
	),
} );

registerMetaTypeWidget( {
	id: 'paired-switch',
	ownedKeys: [ 'options' ],
	metaKey: 'options',
	metaType: 'paired-switch',
	titleFallback: ( i18n ) => i18n.selectOptionsTitle,
	render: ( { ctx, title } ) => (
		<PairedOptionsEditor
			variant="switcher"
			values={ ctx.field }
			onChange={ ctx.updateField }
			i18n={ ctx.i18n }
			title={ title }
		/>
	),
} );

registerMetaTypeWidget( {
	id: 'paired-palettes',
	ownedKeys: [ 'options' ],
	metaKey: 'options',
	metaType: 'paired-palettes',
	titleFallback: ( i18n ) => i18n.palettesOptionsTitle,
	render: ( { ctx, title } ) => (
		<PairedMatrixOptionsEditor
			values={ ctx.field }
			onChange={ ctx.updateField }
			i18n={ ctx.i18n }
			title={ title }
		/>
	),
} );

registerMetaTypeWidget( {
	id: 'paired-pricematrix',
	ownedKeys: [ 'options' ],
	metaKey: 'options',
	metaType: 'paired-pricematrix',
	titleFallback: ( i18n ) => i18n.priceMatrixOptionsTitle,
	render: ( { ctx, title } ) => (
		<PairedMatrixOptionsEditor
			values={ ctx.field }
			onChange={ ctx.updateField }
			i18n={ ctx.i18n }
			title={ title }
		/>
	),
} );

registerMetaTypeWidget( {
	id: 'bulk-quantity',
	ownedKeys: [ 'options' ],
	metaKey: 'options',
	metaType: 'bulk-quantity',
	titleFallback: ( i18n ) => i18n.bulkQuantityOptionsTitle,
	render: ( { ctx, title } ) => (
		<BulkQuantityMatrixEditor
			values={ ctx.field }
			onChange={ ctx.updateField }
			i18n={ ctx.i18n }
			title={ title }
		/>
	),
} );

registerMetaTypeWidget( {
	id: 'fonts-paired',
	ownedKeys: [ 'options' ],
	metaKey: 'options',
	metaType: 'fonts_paired',
	titleFallback: ( i18n ) => i18n.fontsOptionsTitle,
	render: ( { ctx, title } ) => (
		<FontsPairedEditor
			values={ ctx.field }
			onChange={ ctx.updateField }
			i18n={ ctx.i18n }
			title={ title }
		/>
	),
} );

registerMetaTypeWidget( {
	id: 'chained-options',
	ownedKeys: [ 'options' ],
	metaKey: 'options',
	metaType: 'chained_options',
	titleFallback: ( i18n ) => i18n.selectOptionsTitle,
	render: ( { ctx, title } ) => (
		<ChainedOptionsEditor
			values={ ctx.field }
			onChange={ ctx.updateField }
			i18n={ ctx.i18n }
			title={ title }
		/>
	),
} );

registerMetaTypeWidget( {
	id: 'conditional-images',
	ownedKeys: [ 'images' ],
	metaKey: 'images',
	metaType: 'conditional-images',
	titleFallback: ( i18n ) => i18n.imagesMediaTitle,
	render: ( { ctx, title } ) => (
		<ImagesSelectEditor
			variant="conditional-meta"
			values={ ctx.field }
			onChange={ ctx.updateField }
			i18n={ ctx.i18n }
			title={ title }
		/>
	),
} );

registerMetaTypeWidget( {
	id: 'vqmatrix',
	ownedKeys: [ 'options', 'row_options' ],
	metaKey: 'options',
	metaType: 'vqmatrix-colunm',
	extraGuard: ( ctx ) =>
		readSettingMeta( ctx.schema, 'row_options' )?.type === 'vqmatrix-row',
	titleFallback: ( i18n ) => i18n.priceMatrixOptionsTitle,
	render: ( { ctx, title } ) => (
		<VqMatrixEditor
			values={ ctx.field }
			onChange={ ctx.updateField }
			i18n={ ctx.i18n }
			title={ title }
		/>
	),
} );
