/**
 * PPOM native select bound to TanStack Form field context + Chakra v2.
 */
import { Steps, NativeSelect, Field } from '@chakra-ui/react';
import { useId } from '@wordpress/element';
import { useFieldContext } from '../ppomFormContext';
import { ppomControlSurface, ppomFieldLabelProps } from '../fieldStyles';

export interface PpomSelectOption {
	value: string;
	label: string;
}

export interface PpomSelectInputProps {
	label?: string;
	options: PpomSelectOption[];
}

export function PpomSelectInput( { label, options }: PpomSelectInputProps ) {
	const field = useFieldContext<string>();
	const v = field.state.value;
	// Use React.useId instead (available in React 18+)
    const selectId = useId();
	return (
        <Field.Root>
            { label ? (
				<Field.Label htmlFor={ selectId } { ...ppomFieldLabelProps }>
					{ label }
				</Field.Label>
			) : null }
            <NativeSelect.Root>
                <NativeSelect.Field
                    id={ selectId }
                    size="sm"
                    value={ v ?? '' }
                    onValueChange={ ( e ) => field.handleChange( e.target.value ) }
                    onBlur={ field.handleBlur }
                    { ...ppomControlSurface }>
                    { options.map( ( o, i ) => (
                        <option key={ `${ i }:${ o.value }` } value={ o.value }>
                            { o.label }
                        </option>
                    ) ) }
                </NativeSelect.Field>
                <NativeSelect.Indicator />
            </NativeSelect.Root>
        </Field.Root>
    );
}
