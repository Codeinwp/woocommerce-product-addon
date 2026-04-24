import type { PrimitiveSettingControlProps } from './shared';
import { TextControl } from './TextControl';

export function NumberControl( props: PrimitiveSettingControlProps ) {
	return <TextControl { ...props } inputType="number" />;
}
