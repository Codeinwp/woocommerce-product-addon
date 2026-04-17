/**
 * Single setting row renderer shared by legacy widgets and primitive controls.
 */
import { renderPrimitiveSettingControl } from './controls';
import { ConditionsEditor } from './ConditionsEditor';
import { PairedCropperEditor } from './components/PairedCropperEditor';
import { PairedQuantityEditor } from './components/PairedQuantityEditor';
import type { SettingRowContext } from './types/fieldModal';

export function openLegacyFieldModal( ppomFieldIndex: number ) {
	if ( ! ppomFieldIndex || ppomFieldIndex < 1 ) {
		return;
	}
	const btn = document.querySelector(
		`button.ppom-edit-field[id="${ String( ppomFieldIndex ) }"]`
	);
	if ( ! btn ) {
		return;
	}
	( btn as HTMLElement ).click();
}

export function renderSettingRow(
	key: string,
	meta: Record< string, unknown >,
	ctx: SettingRowContext
) {
	const { values, i18n } = ctx;
	const type = meta.type ? String( meta.type ) : 'text';
	const title = meta.title ? String( meta.title ) : key;
	const desc = meta.desc ? String( meta.desc ) : '';

	if ( meta.hidden ) {
		return null;
	}

	if ( type === 'html-conditions' ) {
		const builderFields = ctx.builderFields || [];
		const conditionsProEnabled = !! ctx.conditionsProEnabled;
		return (
			<ConditionsEditor
				key={ key }
				meta={ meta }
				values={ values }
				onChange={ ( nextRow ) => ctx.onChange( nextRow ) }
				i18n={ i18n }
				builderFields={ builderFields }
				conditionsProEnabled={ conditionsProEnabled }
			/>
		);
	}

	if ( type === 'paired-cropper' ) {
		return (
			<PairedCropperEditor
				key={ key }
				fieldKey={ key }
				title={ title }
				description={ desc }
				values={ values }
				onChange={ ctx.onChange }
				i18n={ i18n }
			/>
		);
	}

	if ( type === 'paired-quantity' ) {
		return (
			<PairedQuantityEditor
				key={ key }
				fieldKey={ key }
				title={ title }
				description={ desc }
				values={ values }
				onChange={ ctx.onChange }
				i18n={ i18n }
			/>
		);
	}

	return renderPrimitiveSettingControl( {
		settingKey: key,
		meta,
		ctx,
	} );
}
