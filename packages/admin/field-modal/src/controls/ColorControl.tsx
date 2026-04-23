import { ColorPicker, Field, HStack } from '@chakra-ui/react';
import { controlSurface } from './chakraFieldStyles';
import { colorFromStoredValue, persistColorValueAsHex } from './colorHelpers';
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

export function ColorControl( {
	settingKey,
	meta,
	ctx,
}: PrimitiveSettingControlProps ) {
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
							<ColorPicker.Root
								value={ colorFromStoredValue(
									field.state.value
								) }
								onValueChange={ ( details ) =>
									field.handleChange(
										persistColorValueAsHex( details.value )
									)
								}
								size="sm"
								maxW="200px"
							>
								<ColorPicker.HiddenInput />
								<ColorPicker.Control { ...controlSurface }>
									<ColorPicker.Input />
									<ColorPicker.Trigger />
								</ColorPicker.Control>
								<ColorPicker.Positioner>
									<ColorPicker.Content>
										<ColorPicker.Area />
										<HStack>
											<ColorPicker.EyeDropper
												size="xs"
												variant="outline"
											/>
											<ColorPicker.Sliders />
										</HStack>
									</ColorPicker.Content>
								</ColorPicker.Positioner>
							</ColorPicker.Root>
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
			<ColorPicker.Root
				value={ colorFromStoredValue(
					readControlValue( settingKey, ctx )
				) }
				onValueChange={ ( details ) =>
					updateFallbackSettingValue(
						ctx,
						settingKey,
						persistColorValueAsHex( details.value )
					)
				}
				size="sm"
				maxW="200px"
			>
				<ColorPicker.HiddenInput />
				<ColorPicker.Control { ...controlSurface }>
					<ColorPicker.Input />
					<ColorPicker.Trigger />
				</ColorPicker.Control>
				<ColorPicker.Positioner>
					<ColorPicker.Content>
						<ColorPicker.Area />
						<HStack>
							<ColorPicker.EyeDropper
								size="xs"
								variant="outline"
							/>
							<ColorPicker.Sliders />
						</HStack>
					</ColorPicker.Content>
				</ColorPicker.Positioner>
			</ColorPicker.Root>
			{ renderHelperText( description ) }
			{ renderMetaLink( meta.link ) }
		</Field.Root>
	);
}
