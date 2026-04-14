/**
 * PPOM native select bound to TanStack Form field context + Chakra v2.
 */
import {
	FormControl,
	FormLabel,
	Select,
} from '@chakra-ui/react';
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
	const selectId = useId();
	return (
		<FormControl>
			{ label ? (
				<FormLabel htmlFor={ selectId } { ...ppomFieldLabelProps }>
					{ label }
				</FormLabel>
			) : null }
			<Select
				id={ selectId }
				size="sm"
				value={ v ?? '' }
				onChange={ ( e ) => field.handleChange( e.target.value ) }
				onBlur={ field.handleBlur }
				{ ...ppomControlSurface }
			>
				{ options.map( ( o, i ) => (
					<option key={ `${ i }:${ o.value }` } value={ o.value }>
						{ o.label }
					</option>
				) ) }
			</Select>
		</FormControl>
	);
}
