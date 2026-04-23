/**
 * Numeric input for PPOM fields (stores number or undefined when empty / invalid).
 */
import { Steps, Input, Field } from '@chakra-ui/react';
import { useEffect, useId, useState } from '@wordpress/element';
import { useFieldContext } from '../ppomFormContext';
import { ppomControlSurface, ppomFieldLabelProps } from '../fieldStyles';

export interface PpomNumberInputProps {
	label?: string;
	min?: number;
	max?: number;
	step?: number | string;
}

function numToDisplay( v: number | undefined | null ): string {
	if ( v === undefined || v === null || Number.isNaN( v ) ) {
		return '';
	}
	return String( v );
}

export function PpomNumberInput( {
	label,
	min,
	max,
	step,
}: PpomNumberInputProps ) {
	const field = useFieldContext< number | undefined >();
	const v = field.state.value;
	// Use React.useId instead (available in React 18+)
	const inputId = useId();
	const [ focused, setFocused ] = useState( false );
	const [ draft, setDraft ] = useState( () => numToDisplay( v ) );

	useEffect( () => {
		if ( ! focused ) {
			setDraft( numToDisplay( v ) );
		}
	}, [ v, focused ] );

	const display = focused ? draft : numToDisplay( v );

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
				type="number"
				value={ display }
				min={ min }
				max={ max }
				step={ step }
				onValueChange={ ( e ) => {
					const next = e.target.value;
					setDraft( next );
					if ( next === '' ) {
						field.handleChange( undefined );
						return;
					}
					if ( next === '-' || next === '.' ) {
						field.handleChange( undefined );
						return;
					}
					const n = Number( next );
					if ( ! Number.isNaN( n ) ) {
						field.handleChange( n );
					}
				} }
				onFocus={ () => {
					setFocused( true );
					setDraft( numToDisplay( v ) );
				} }
				onBlur={ () => {
					setFocused( false );
					const t = draft.trim();
					if ( t === '' || t === '-' || t === '.' ) {
						field.handleChange( undefined );
						setDraft( '' );
					} else {
						const n = Number( t );
						if ( Number.isNaN( n ) ) {
							field.handleChange( undefined );
							setDraft( '' );
						} else {
							field.handleChange( n );
							setDraft( String( n ) );
						}
					}
					field.handleBlur();
				} }
				{ ...ppomControlSurface }
			/>
		</Field.Root>
	);
}
