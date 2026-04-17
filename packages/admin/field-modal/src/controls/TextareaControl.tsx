import { Field, Textarea } from '@chakra-ui/react';
import { controlSurface } from './chakraFieldStyles';
import {
	labelProps,
	type PrimitiveSettingControlProps,
	readControlDescription,
	readControlTitle,
	readControlValue,
	renderHelperText,
	updateFallbackSettingValue,
} from './shared';

export function TextareaControl( {
	settingKey,
	meta,
	ctx,
}: PrimitiveSettingControlProps ) {
	const title = readControlTitle( settingKey, meta );
	const description = readControlDescription( meta );
	const AppField = ctx.form?.AppField;

	if ( AppField ) {
		return (
			<AppField name={ settingKey }>
				{ ( field: any ) => {
					const error = field.state.meta.errors?.[ 0 ];
					return (
						<Field.Root invalid={ Boolean( error ) }>
							<Field.Label { ...labelProps }>{ title }</Field.Label>
							<Textarea
								size="sm"
								rows={ 2 }
								resize="vertical"
								value={ field.state.value == null ? '' : String( field.state.value ) }
								onChange={ ( e ) =>
									field.handleChange( e.target.value )
								}
								onBlur={ field.handleBlur }
								{ ...controlSurface }
							/>
							{ renderHelperText( description, { allowHtml: true } ) }
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
		<Field.Root>
			<Field.Label { ...labelProps }>{ title }</Field.Label>
			<Textarea
				size="sm"
				rows={ 2 }
				resize="vertical"
				value={ String( readControlValue( settingKey, ctx ) ?? '' ) }
				onChange={ ( e ) =>
					updateFallbackSettingValue( ctx, settingKey, e.target.value )
				}
				{ ...controlSurface }
			/>
			{ renderHelperText( description, { allowHtml: true } ) }
		</Field.Root>
	);
}
