/**
 * PPOM text input bound to TanStack Form field context + Chakra v2.
 */
import { Steps, Input, Field } from '@chakra-ui/react';
import { useId } from '@wordpress/element';
import { useFieldContext } from '../ppomFormContext';
import { ppomControlSurface, ppomFieldLabelProps } from '../fieldStyles';

export interface PpomTextInputProps {
	label?: string;
}

export function PpomTextInput( { label }: PpomTextInputProps ) {
	const field = useFieldContext<string>();
	const v = field.state.value;
	// Use React.useId instead (available in React 18+)
    const inputId = useId();
	return (
        <Field.Root>
            { label ? (
				<Field.Label htmlFor={ inputId } { ...ppomFieldLabelProps }>
					{ label }
				</Field.Label>
			) : null }
            <Input
				id={ inputId }
				size="sm"
				value={ v ?? '' }
				onValueChange={ ( e ) => field.handleChange( e.target.value ) }
				onBlur={ field.handleBlur }
				{ ...ppomControlSurface }
			/>
        </Field.Root>
    );
}
