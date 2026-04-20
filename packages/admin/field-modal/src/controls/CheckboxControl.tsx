import { CheckboxCard, Field } from '@chakra-ui/react';
import {
	labelProps,
	type PrimitiveSettingControlProps,
	normalizeToggleValue,
	readControlDescription,
	readControlTitle,
	readControlValue,
	updateFallbackSettingValue,
} from './shared';

function checkboxCardProps() {
	return {
		width: '100%',
		variant: 'outline' as const,
		size: 'sm' as const,
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
							<CheckboxCard.Root
								{ ...checkboxCardProps() }
								checked={ checked }
								invalid={ Boolean( error ) }
								onCheckedChange={ ( d ) =>
									field.handleChange(
										d.checked === true ? 'on' : 'off'
									)
								}
								onBlur={ field.handleBlur }
							>
								<CheckboxCard.HiddenInput />
								<CheckboxCard.Control>
									<CheckboxCard.Content>
										<CheckboxCard.Label
											{ ...labelProps }
											mb={ 0 }
										>
											{ title }
										</CheckboxCard.Label>
										{ description ? (
											<CheckboxCard.Description>
												{ description }
											</CheckboxCard.Description>
										) : null }
									</CheckboxCard.Content>
									<CheckboxCard.Indicator />
								</CheckboxCard.Control>
							</CheckboxCard.Root>
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
			<CheckboxCard.Root
				{ ...checkboxCardProps() }
				checked={ checked }
				onCheckedChange={ ( d ) =>
					updateFallbackSettingValue(
						ctx,
						settingKey,
						d.checked === true ? 'on' : 'off'
					)
				}
			>
				<CheckboxCard.HiddenInput />
				<CheckboxCard.Control>
					<CheckboxCard.Content>
						<CheckboxCard.Label { ...labelProps } mb={ 0 }>
							{ title }
						</CheckboxCard.Label>
						{ description ? (
							<CheckboxCard.Description>
								{ description }
							</CheckboxCard.Description>
						) : null }
					</CheckboxCard.Content>
					<CheckboxCard.Indicator />
				</CheckboxCard.Control>
			</CheckboxCard.Root>
		</Field.Root>
	);
}
