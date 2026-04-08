/**
 * Maps PPOM field type slug → editor component. Pro/custom may set window.ppomFieldModalEditors.
 */
import { TextFieldEditor } from './TextFieldEditor';
import { TextareaFieldEditor } from './TextareaFieldEditor';
import { SelectFieldEditor } from './SelectFieldEditor';

const coreEditors = {
	text: TextFieldEditor,
	textarea: TextareaFieldEditor,
	select: SelectFieldEditor,
};

/**
 * @param {string} slug Field type from PPOM (e.g. text, email).
 * @return {import('react').ComponentType<any>|null}
 */
export function getFieldEditor( slug ) {
	const t = String( slug || '' ).toLowerCase();
	const extra =
		typeof window !== 'undefined' &&
		window.ppomFieldModalEditors &&
		typeof window.ppomFieldModalEditors === 'object'
			? window.ppomFieldModalEditors
			: {};
	const merged = { ...coreEditors, ...extra };
	return merged[ t ] || null;
}
