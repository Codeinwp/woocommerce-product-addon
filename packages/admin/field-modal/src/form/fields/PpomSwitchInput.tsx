/**
 * PPOM switch — stores PPOM-style 'on' | 'off' strings (see schemaSettingControl checkbox).
 */
import { Field, Switch, Text } from '@chakra-ui/react';
import { useId } from '@wordpress/element';
import { useFieldContext } from '../ppomFormContext';
import { ppomFieldLabelProps } from '../fieldStyles';

export interface PpomSwitchInputProps {
	label?: string;
	description?: string;
}

export function PpomSwitchInput( {
	label,
	description,
}: PpomSwitchInputProps ) {
	const field = useFieldContext<unknown>();
	// Use React.useId instead (available in React 18+)
    const switchId = useId();
	const raw = field.state.value;
	const checked =
		raw === 'on' ||
		raw === true ||
		raw === '1' ||
		raw === 1 ||
		raw === 'true';
	return (
        <Field.Root
			display="grid"
			gridTemplateColumns="auto minmax(0, 1fr)"
			columnGap={ 2 }
			rowGap={ label && description ? 0.5 : 0 }
			alignItems="start"
			py={ 1.5 }
			px={ 2 }
			mb={ 0 }
		>
            <Switch.Root
				gridRow={ 1 }
				gridColumn={ 1 }
				id={ switchId }
				mt={ label ? 0.5 : 0 }
				alignSelf="start"
				colorPalette="blue"
				checked={ Boolean( checked ) }
				aria-label={
					label
						? undefined
						: description
						? description
						: 'Toggle'
				}
				onCheckedChange={ ( { checked: next } ) =>
					field.handleChange( next ? 'on' : 'off' )
				}
				onBlur={ field.handleBlur }
			>
				<Switch.HiddenInput />
				<Switch.Control />
			</Switch.Root>
			{ label ? (
				<Field.Label
					gridRow={ 1 }
					gridColumn={ 2 }
					htmlFor={ switchId }
					{ ...ppomFieldLabelProps }
					mb={ 0 }
				>
					{ label }
				</Field.Label>
			) : null }
			{ description ? (
				<Text
					gridRow={ label ? 2 : 1 }
					gridColumn={ 2 }
					fontSize="xs"
					color="gray.600"
					lineHeight="1.5"
				>
					{ description }
				</Text>
			) : null }
        </Field.Root>
    );
}
