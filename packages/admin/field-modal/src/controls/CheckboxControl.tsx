import { Box, Checkbox, Field, Stack } from '@chakra-ui/react';
import {
	labelProps,
	type PrimitiveSettingControlProps,
	normalizeToggleValue,
	readControlDescription,
	readControlTitle,
	readControlValue,
	updateFallbackSettingValue,
} from './shared';

function checkboxRootProps() {
	return {
		gap: '3' as const,
		alignItems: 'flex-start' as const,
		colorPalette: 'blue' as const,
	};
}

export function CheckboxControl( {
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
					const checked = normalizeToggleValue( field.state.value );
					const error = field.state.meta.errors?.[ 0 ];
					return (
						<Field.Root
							invalid={ Boolean( error ) }
							width="100%"
							mb={ 0 }
						>
							<Checkbox.Root
								{ ...checkboxRootProps() }
								checked={ checked }
								invalid={ Boolean( error ) }
								onCheckedChange={ ( d ) =>
									field.handleChange(
										d.checked === true ? 'on' : 'off'
									)
								}
								onBlur={ field.handleBlur }
							>
								<Checkbox.HiddenInput />
								<Checkbox.Control />
								<Stack gap="1">
									<Checkbox.Label
										{ ...labelProps }
										mb={ 0 }
									>
										{ title }
									</Checkbox.Label>
									{ description ? (
										<Box
											fontSize="xs"
											color="gray.600"
											lineHeight="1.45"
										>
											{ description }
										</Box>
									) : null }
								</Stack>
							</Checkbox.Root>
							{ error ? (
								<Field.ErrorText mt={ 1 }>
									{ String( error ) }
								</Field.ErrorText>
							) : null }
						</Field.Root>
					);
				} }
			</AppField>
		);
	}

	const checked = normalizeToggleValue( readControlValue( settingKey, ctx ) );

	return (
		<Field.Root width="100%" mb={ 0 }>
			<Checkbox.Root
				{ ...checkboxRootProps() }
				checked={ checked }
				onCheckedChange={ ( d ) =>
					updateFallbackSettingValue(
						ctx,
						settingKey,
						d.checked === true ? 'on' : 'off'
					)
				}
			>
				<Checkbox.HiddenInput />
				<Checkbox.Control />
				<Stack gap="1">
					<Checkbox.Label { ...labelProps } mb={ 0 }>
						{ title }
					</Checkbox.Label>
					{ description ? (
						<Box
							fontSize="xs"
							color="gray.600"
							lineHeight="1.45"
						>
							{ description }
						</Box>
					) : null }
				</Stack>
			</Checkbox.Root>
		</Field.Root>
	);
}
