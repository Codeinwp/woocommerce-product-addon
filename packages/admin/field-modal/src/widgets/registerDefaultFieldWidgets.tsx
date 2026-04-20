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
import { ChainedOptionsEditor } from '../components/ChainedOptionsEditor';
import { FontsPairedEditor } from '../components/FontsPairedEditor';
import { ImagesSelectEditor } from '../components/ImagesSelectEditor';
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
			ctx.i18n.selectOptionsTitle || 'Options'
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
	id: 'paired-quantity',
	ownedKeys: [ 'options' ],
	metaKey: ( ctx ) =>
		typeof ctx.widgetProps?.fieldKey === 'string'
			? String( ctx.widgetProps.fieldKey )
			: 'options',
	metaType: 'paired-quantity',
	titleFallback: ( i18n ) => i18n.quantityOptionsTitle || 'Options',
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
	id: 'paired-switch',
	ownedKeys: [ 'options' ],
	metaKey: 'options',
	metaType: 'paired-switch',
	titleFallback: ( i18n ) => i18n.selectOptionsTitle || 'Options',
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
	id: 'fonts-paired',
	ownedKeys: [ 'options' ],
	metaKey: 'options',
	metaType: 'fonts_paired',
	titleFallback: ( i18n ) => i18n.fontsOptionsTitle || 'Font Families',
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
	titleFallback: ( i18n ) => i18n.selectOptionsTitle || 'Options',
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
	titleFallback: ( i18n ) => i18n.imagesMediaTitle || 'Select images',
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
	titleFallback: ( i18n ) => i18n.priceMatrixOptionsTitle || 'Matrix options',
	render: ( { ctx, title } ) => (
		<VqMatrixEditor
			values={ ctx.field }
			onChange={ ctx.updateField }
			i18n={ ctx.i18n }
			title={ title }
		/>
	),
} );
