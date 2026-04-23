/**
 * PPOM textarea bound to TanStack Form field context + Chakra v2.
 */
import { Steps, Textarea, Field } from '@chakra-ui/react';
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
	const field = useFieldContext< string >();
	const v = field.state.value;
	// Use React.useId instead (available in React 18+)
	const textareaId = useId();
	return (
		<Field.Root>
			{ label ? (
				<Field.Label htmlFor={ textareaId } { ...ppomFieldLabelProps }>
					{ label }
				</Field.Label>
			) : null }
			<Textarea
				id={ textareaId }
				size="sm"
				rows={ rows }
				resize="vertical"
				value={ v ?? '' }
				onValueChange={ ( e ) => field.handleChange( e.target.value ) }
				onBlur={ field.handleBlur }
				{ ...ppomControlSurface }
			/>
		</Field.Root>
	);
}
