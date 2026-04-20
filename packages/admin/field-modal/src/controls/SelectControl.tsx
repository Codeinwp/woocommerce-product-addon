import { Field, NativeSelect } from '@chakra-ui/react';
import { controlSurface } from './chakraFieldStyles';
import { normalizeSelectOptions } from '../schemaTabs';
import {
	labelProps,
	type PrimitiveSettingControlProps,
	readControlDescription,
	readControlLabelRequired,
	readControlTitle,
	readControlValue,
	renderHelperText,
	updateFallbackSettingValue,
} from './shared';

export function SelectControl( {
	settingKey,
	meta,
	ctx,
}: PrimitiveSettingControlProps ) {
	const title = readControlTitle( settingKey, meta );
	const description = readControlDescription( meta );
	const labelRequired = readControlLabelRequired( meta, settingKey );
	const options = normalizeSelectOptions( meta.options );
	const AppField = ctx.form?.AppField;

	if ( AppField ) {
		return (
			<AppField name={ settingKey }>
				{ ( field: any ) => {
					const error = field.state.meta.errors?.[ 0 ];
					return (
						<Field.Root
							invalid={ Boolean( error ) }
							required={ labelRequired }
						>
							<Field.Label { ...labelProps }>
								{ title }
								<Field.RequiredIndicator />
							</Field.Label>
							<NativeSelect.Root size="sm">
								<NativeSelect.Field
									required={ labelRequired }
									value={ field.state.value == null ? '' : String( field.state.value ) }
									onChange={ ( e ) =>
										field.handleChange( e.target.value )
									}
									onBlur={ field.handleBlur }
									{ ...controlSurface }
								>
									{ options.map( ( option ) => (
										<option key={ option.value } value={ option.value }>
											{ option.label }
										</option>
									) ) }
								</NativeSelect.Field>
								<NativeSelect.Indicator />
							</NativeSelect.Root>
							{ renderHelperText( description ) }
							{ error ? (
								<Field.ErrorText>{ String( error ) }</Field.ErrorText>
							) : null }
						</Field.Root>
					);
				} }
			</AppField>
		);
	}

	return (
		<Field.Root required={ labelRequired }>
			<Field.Label { ...labelProps }>
				{ title }
				<Field.RequiredIndicator />
			</Field.Label>
			<NativeSelect.Root size="sm">
				<NativeSelect.Field
					required={ labelRequired }
					value={ String( readControlValue( settingKey, ctx ) ?? '' ) }
					onChange={ ( e ) =>
						updateFallbackSettingValue( ctx, settingKey, e.target.value )
					}
					{ ...controlSurface }
				>
					{ options.map( ( option ) => (
						<option key={ option.value } value={ option.value }>
							{ option.label }
						</option>
					) ) }
				</NativeSelect.Field>
				<NativeSelect.Indicator />
			</NativeSelect.Root>
			{ renderHelperText( description ) }
		</Field.Root>
	);
}
