/**
 * Maps PPOM field type slug → editor component. Pro/custom may set window.ppomFieldModalEditors.
 */
import { TextFieldEditor } from './TextFieldEditor';
import { TextareaFieldEditor } from './TextareaFieldEditor';
import { SelectFieldEditor } from './SelectFieldEditor';
import { ImageFieldEditor } from './ImageFieldEditor';
import { ImageselectFieldEditor } from './ImageselectFieldEditor';
import { AudioFieldEditor } from './AudioFieldEditor';
import { CropperFieldEditor } from './CropperFieldEditor';
import { QuantitiesFieldEditor } from './QuantitiesFieldEditor';
import { CheckboxFieldEditor } from './CheckboxFieldEditor';
import { RadioFieldEditor } from './RadioFieldEditor';
import { PalettesFieldEditor } from './PalettesFieldEditor';
import { PriceMatrixFieldEditor } from './PriceMatrixFieldEditor';
import { BulkQuantityFieldEditor } from './BulkQuantityFieldEditor';
import { FixedPriceFieldEditor } from './FixedPriceFieldEditor';
import { SelectQtyFieldEditor } from './SelectQtyFieldEditor';
import { EmojisFieldEditor } from './EmojisFieldEditor';
import type { FieldEditorComponent } from '../types/fieldModal';

const coreEditors: Record< string, FieldEditorComponent > = {
	text: TextFieldEditor,
	textarea: TextareaFieldEditor,
	select: SelectFieldEditor,
	checkbox: CheckboxFieldEditor,
	radio: RadioFieldEditor,
	palettes: PalettesFieldEditor,
	pricematrix: PriceMatrixFieldEditor,
	bulkquantity: BulkQuantityFieldEditor,
	fixedprice: FixedPriceFieldEditor,
	selectqty: SelectQtyFieldEditor,
	emojis: EmojisFieldEditor,
	image: ImageFieldEditor,
	imageselect: ImageselectFieldEditor,
	audio: AudioFieldEditor,
	cropper: CropperFieldEditor,
	quantities: QuantitiesFieldEditor,
};

export function getFieldEditor( slug: string ): FieldEditorComponent | null {
	const t = String( slug || '' ).toLowerCase();
	const extra =
		typeof window !== 'undefined' &&
		window.ppomFieldModalEditors &&
		typeof window.ppomFieldModalEditors === 'object'
			? window.ppomFieldModalEditors
			: {};
	const merged: Record< string, FieldEditorComponent > = {
		...coreEditors,
		...extra,
	};
	return merged[ t ] || null;
}
