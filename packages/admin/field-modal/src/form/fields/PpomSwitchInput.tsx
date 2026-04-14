/**
 * PPOM switch — stores PPOM-style 'on' | 'off' strings (see schemaSettingControl checkbox).
 */
import { Box, FormControl, FormLabel, Switch, Text } from '@chakra-ui/react';
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
	const switchId = useId();
	const raw = field.state.value;
	const checked =
		raw === 'on' ||
		raw === true ||
		raw === '1' ||
		raw === 1 ||
		raw === 'true';
	return (
		<FormControl
			display="flex"
			alignItems="flex-start"
			gap={ 2 }
			p={ 2 }
			bg="gray.50"
			borderRadius="md"
			borderWidth="1px"
			borderColor="gray.100"
		>
			<Switch
				id={ switchId }
				mt={ 0.5 }
				colorScheme="blue"
				isChecked={ Boolean( checked ) }
				aria-label={
					label
						? undefined
						: description
						? description
						: 'Toggle'
				}
				onChange={ ( e ) =>
					field.handleChange( e.target.checked ? 'on' : 'off' )
				}
				onBlur={ field.handleBlur }
			/>
			<Box flex="1" minW={ 0 }>
				{ label ? (
					<FormLabel
						htmlFor={ switchId }
						{ ...ppomFieldLabelProps }
						mb={ 0.5 }
					>
						{ label }
					</FormLabel>
				) : null }
				{ description ? (
					<Text fontSize="xs" color="gray.600" lineHeight="1.5">
						{ description }
					</Text>
				) : null }
			</Box>
		</FormControl>
	);
}
