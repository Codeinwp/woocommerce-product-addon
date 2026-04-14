/**
 * PPOM textarea bound to TanStack Form field context + Chakra v2.
 */
import {
	FormControl,
	FormLabel,
	Textarea,
} from '@chakra-ui/react';
import { useId } from '@wordpress/element';
import { useFieldContext } from '../ppomFormContext';
import { ppomControlSurface, ppomFieldLabelProps } from '../fieldStyles';

export interface PpomTextareaInputProps {
	label?: string;
	rows?: number;
}

export function PpomTextareaInput( {
	label,
	rows = 2,
}: PpomTextareaInputProps ) {
	const field = useFieldContext<string>();
	const v = field.state.value;
	const textareaId = useId();
	return (
		<FormControl>
			{ label ? (
				<FormLabel htmlFor={ textareaId } { ...ppomFieldLabelProps }>
					{ label }
				</FormLabel>
			) : null }
			<Textarea
				id={ textareaId }
				size="sm"
				rows={ rows }
				resize="vertical"
				value={ v ?? '' }
				onChange={ ( e ) => field.handleChange( e.target.value ) }
				onBlur={ field.handleBlur }
				{ ...ppomControlSurface }
			/>
		</FormControl>
	);
}
