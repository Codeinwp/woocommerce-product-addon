/**
 * Registers default field-modal widgets.
 *
 * Each widget is a typed Lego brick: `{ id, render, ownedKeys? }`. Definitions in
 * `definitions/` reference them by id. Add new widgets here (or from third-party code
 * via `ppomFieldModal.registerWidget`) — do not fork the shell.
 */
import { PairedOptionsEditor } from '../components/PairedOptionsEditor';
import type { PairedOptionsVariant } from '../components/PairedOptionsEditor';
import { registerFieldWidget } from './registry';

function optionsTitleFromSchema(
	schema: Record< string, unknown > | null | undefined,
	fallback: string
): string {
	const settings =
		schema && typeof schema.settings === 'object'
			? ( schema.settings as Record< string, unknown > )
			: {};
	const optionsMeta = settings.options as Record< string, unknown > | undefined;
	if ( optionsMeta?.title != null ) {
		return String( optionsMeta.title );
	}
	return fallback;
}

registerFieldWidget( {
	id: 'paired-options',
	ownedKeys: [ 'options' ],
	render: ( ctx ) => {
		const settings =
			ctx.schema && typeof ctx.schema.settings === 'object'
				? ( ctx.schema.settings as Record< string, unknown > )
				: {};
		const optionsMeta = settings.options as
			| Record< string, unknown >
			| undefined;
		const needsPaired =
			optionsMeta &&
			optionsMeta.type &&
			String( optionsMeta.type ) === 'paired';
		if ( ! needsPaired ) {
			return null;
		}
		const variant =
			( ctx.widgetProps?.variant as PairedOptionsVariant | undefined ) ||
			'select';
		const title = optionsTitleFromSchema(
			ctx.schema as Record< string, unknown > | null,
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
