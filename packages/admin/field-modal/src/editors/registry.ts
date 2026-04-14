/**
 * Maps PPOM field type slug → editor component. Pro/custom may set window.ppomFieldModalEditors.
 */
import { TextFieldEditor } from './TextFieldEditor';
import { TextareaFieldEditor } from './TextareaFieldEditor';
import { SelectFieldEditor } from './SelectFieldEditor';
import type { FieldEditorComponent } from '../types/fieldModal';

const coreEditors: Record< string, FieldEditorComponent > = {
	text: TextFieldEditor,
	textarea: TextareaFieldEditor,
	select: SelectFieldEditor,
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
