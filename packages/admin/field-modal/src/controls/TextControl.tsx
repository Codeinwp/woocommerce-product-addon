import { Field, Input } from '@chakra-ui/react';
import { controlSurface } from './chakraFieldStyles';
import {
	labelProps,
	type PrimitiveSettingControlProps,
	readControlDescription,
	readControlLabelRequired,
	readControlTitle,
	readControlValue,
	renderHelperText,
	renderMetaLink,
	updateFallbackSettingValue,
} from './shared';

export interface TextControlProps extends PrimitiveSettingControlProps {
	inputType?: string;
}

export function TextControl( {
	settingKey,
	meta,
	ctx,
	inputType = 'text',
}: TextControlProps ) {
	const title = readControlTitle( settingKey, meta );
	const description = readControlDescription( meta );
	const labelRequired = readControlLabelRequired( meta, settingKey );
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
							<Input
								size="sm"
								type={ inputType }
								required={ labelRequired }
								value={
									field.state.value == null
										? ''
										: String( field.state.value )
								}
								onChange={ ( e ) =>
									field.handleChange( e.target.value )
								}
								onBlur={ field.handleBlur }
								{ ...controlSurface }
							/>
							{ renderHelperText( description ) }
							{ renderMetaLink( meta.link ) }
							{ error ? (
								<Field.ErrorText>
									{ String( error ) }
								</Field.ErrorText>
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
			<Input
				size="sm"
				type={ inputType }
				required={ labelRequired }
				value={ String( readControlValue( settingKey, ctx ) ?? '' ) }
				onChange={ ( e ) =>
					updateFallbackSettingValue(
						ctx,
						settingKey,
						e.target.value
					)
				}
				{ ...controlSurface }
			/>
			{ renderHelperText( description ) }
			{ renderMetaLink( meta.link ) }
		</Field.Root>
	);
}
