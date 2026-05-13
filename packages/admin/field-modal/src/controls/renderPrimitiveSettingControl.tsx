import { CheckboxControl } from './CheckboxControl';
import { ColorControl } from './ColorControl';
import { DateControl } from './DateControl';
import { NumberControl } from './NumberControl';
import { OptionsDropdownControl } from './OptionsDropdownControl';
import { SelectControl } from './SelectControl';
import { SwitchControl } from './SwitchControl';
import { TextControl } from './TextControl';
import { TextareaControl } from './TextareaControl';
import type { PrimitiveSettingControlProps } from './shared';

export function renderPrimitiveSettingControl(
	props: PrimitiveSettingControlProps
): JSX.Element {
	const type = props.meta.type ? String( props.meta.type ) : 'text';

	switch ( type ) {
		case 'textarea':
			return <TextareaControl { ...props } />;
		case 'select':
			return <SelectControl { ...props } />;
		case 'options-dropdown':
			return <OptionsDropdownControl { ...props } />;
		case 'checkbox':
			return <CheckboxControl { ...props } />;
		case 'switch':
			return <SwitchControl { ...props } />;
		case 'color':
			return <ColorControl { ...props } />;
		case 'number':
			return <NumberControl { ...props } />;
		case 'date':
			return <DateControl { ...props } />;
		default:
			return <TextControl { ...props } />;
	}
}
