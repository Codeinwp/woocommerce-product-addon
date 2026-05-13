import type { PrimitiveSettingControlProps } from './shared';
import { TextControl } from './TextControl';

export function DateControl( props: PrimitiveSettingControlProps ) {
	return <TextControl { ...props } inputType="date" />;
}
