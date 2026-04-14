/**
 * PPOM text input bound to TanStack Form field context + Chakra v2.
 */
import {
	FormControl,
	FormLabel,
	Input,
} from '@chakra-ui/react';
import { useId } from '@wordpress/element';
import { useFieldContext } from '../ppomFormContext';
import { ppomControlSurface, ppomFieldLabelProps } from '../fieldStyles';

export interface PpomTextInputProps {
	label?: string;
}

export function PpomTextInput( { label }: PpomTextInputProps ) {
	const field = useFieldContext<string>();
	const v = field.state.value;
	const inputId = useId();
	return (
		<FormControl>
			{ label ? (
				<FormLabel htmlFor={ inputId } { ...ppomFieldLabelProps }>
					{ label }
				</FormLabel>
			) : null }
			<Input
				id={ inputId }
				size="sm"
				value={ v ?? '' }
				onChange={ ( e ) => field.handleChange( e.target.value ) }
				onBlur={ field.handleBlur }
				{ ...ppomControlSurface }
			/>
		</FormControl>
	);
}
