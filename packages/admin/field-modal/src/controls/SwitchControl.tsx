import { Field, Switch } from '@chakra-ui/react';
import { useId } from '@wordpress/element';
import { HelperIcon } from './HelperIcon';
import {
	labelProps,
	type PrimitiveSettingControlProps,
	normalizeToggleValue,
	readControlDescription,
	readControlTitle,
	readControlValue,
	updateFallbackSettingValue,
} from './shared';

export function SwitchControl( {
	settingKey,
	meta,
	ctx,
}: PrimitiveSettingControlProps ) {
	const title = readControlTitle( settingKey, meta );
	const description = readControlDescription( meta );
	const switchId = useId();
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
							display="grid"
							gridTemplateColumns="auto minmax(0, 1fr)"
							columnGap={ 2 }
							alignItems="center"
							width="100%"
							mb={ 0 }
						>
							<Switch.Root
								gridRow={ 1 }
								gridColumn={ 1 }
								id={ switchId }
								colorPalette="blue"
								checked={ checked }
								invalid={ Boolean( error ) }
								onCheckedChange={ ( { checked: next } ) =>
									field.handleChange( next ? 'on' : 'off' )
								}
								onBlur={ field.handleBlur }
							>
								<Switch.HiddenInput />
								<Switch.Control />
							</Switch.Root>
							{ title ? (
								<Field.Label
									gridRow={ 1 }
									gridColumn={ 2 }
									htmlFor={ switchId }
									{ ...labelProps }
									mb={ 0 }
								>
									{ title }
									<HelperIcon
										description={ description }
									/>
								</Field.Label>
							) : null }
							{ error ? (
								<Field.ErrorText gridColumn="1 / -1" mt={ 1 }>
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
		<Field.Root
			display="grid"
			gridTemplateColumns="auto minmax(0, 1fr)"
			columnGap={ 2 }
			alignItems="center"
			width="100%"
			mb={ 0 }
		>
			<Switch.Root
				gridRow={ 1 }
				gridColumn={ 1 }
				id={ switchId }
				colorPalette="blue"
				checked={ checked }
				onCheckedChange={ ( { checked: next } ) =>
					updateFallbackSettingValue(
						ctx,
						settingKey,
						next ? 'on' : 'off'
					)
				}
			>
				<Switch.HiddenInput />
				<Switch.Control />
			</Switch.Root>
			{ title ? (
				<Field.Label
					gridRow={ 1 }
					gridColumn={ 2 }
					htmlFor={ switchId }
					{ ...labelProps }
					mb={ 0 }
				>
					{ title }
					<HelperIcon description={ description } />
				</Field.Label>
			) : null }
		</Field.Root>
	);
}
