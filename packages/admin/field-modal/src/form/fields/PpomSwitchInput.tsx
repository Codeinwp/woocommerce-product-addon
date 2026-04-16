/**
 * PPOM switch — stores PPOM-style 'on' | 'off' strings (see schemaSettingControl checkbox).
 */
import { Box, Field, Switch, Text } from '@chakra-ui/react';
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
			display="flex"
			alignItems="flex-start"
			gap={ 2 }
			p={ 2 }
			bg="gray.50"
			borderRadius="md"
			borderWidth="1px"
			borderColor="gray.100"
		>
            <Switch.Root
				id={ switchId }
				mt={ 0.5 }
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
            <Box flex="1" minW={ 0 }>
				{ label ? (
					<Field.Label
						htmlFor={ switchId }
						{ ...ppomFieldLabelProps }
						mb={ 0.5 }
					>
						{ label }
					</Field.Label>
				) : null }
				{ description ? (
					<Text fontSize="xs" color="gray.600" lineHeight="1.5">
						{ description }
					</Text>
				) : null }
			</Box>
        </Field.Root>
    );
}
