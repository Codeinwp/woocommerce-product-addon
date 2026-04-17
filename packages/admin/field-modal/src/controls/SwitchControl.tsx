import type { PrimitiveSettingControlProps } from './shared';
import { CheckboxControl } from './CheckboxControl';

export function SwitchControl( props: PrimitiveSettingControlProps ) {
	return <CheckboxControl { ...props } />;
}
