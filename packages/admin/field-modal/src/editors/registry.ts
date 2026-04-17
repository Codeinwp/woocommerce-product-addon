/**
 * Maps PPOM field type slug → legacy React editor (used only for embedded-legacy definitions).
 */
import { AudioFieldEditor } from './AudioFieldEditor';
import { BulkQuantityFieldEditor } from './BulkQuantityFieldEditor';
import { CropperFieldEditor } from './CropperFieldEditor';
import { EmojisFieldEditor } from './EmojisFieldEditor';
import { FixedPriceFieldEditor } from './FixedPriceFieldEditor';
import { ImageFieldEditor } from './ImageFieldEditor';
import { ImageselectFieldEditor } from './ImageselectFieldEditor';
import { PalettesFieldEditor } from './PalettesFieldEditor';
import { PriceMatrixFieldEditor } from './PriceMatrixFieldEditor';
import { QuantitiesFieldEditor } from './QuantitiesFieldEditor';
import { SelectQtyFieldEditor } from './SelectQtyFieldEditor';
import type { FieldEditorComponent } from '../types/fieldModal';

const coreEditors: Record< string, FieldEditorComponent > = {
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
	return coreEditors[ t ] || null;
}
