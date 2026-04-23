import { Field, NativeSelect } from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import { controlSurface } from './chakraFieldStyles';
import { normalizePairedOptionsArray } from '../utils/pairedOptionsData';
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

interface DropdownOption {
	value: string;
	label: string;
}

function buildOptions(
	rawOptions: unknown,
	currentValue: string
): { options: DropdownOption[]; isEmpty: boolean } {
	const rows = normalizePairedOptionsArray( rawOptions );
	const seen = new Set< string >();
	const fromRows: DropdownOption[] = [];

	for ( const row of rows ) {
		const label =
			typeof row.option === 'string' ? row.option.trim() : '';
		if ( ! label || seen.has( label ) ) {
			continue;
		}
		seen.add( label );
		fromRows.push( { value: label, label } );
	}

	const noneLabel = __( '— None —', 'woocommerce-product-addon' );
	const options: DropdownOption[] = [
		{ value: '', label: noneLabel },
		...fromRows,
	];

	if ( currentValue && ! seen.has( currentValue ) ) {
		const missingSuffix = __( '(missing)', 'woocommerce-product-addon' );
		options.push( {
			value: currentValue,
			label: `${ currentValue } ${ missingSuffix }`,
		} );
	}

	return { options, isEmpty: fromRows.length === 0 };
}

export function OptionsDropdownControl( {
	settingKey,
	meta,
	ctx,
}: PrimitiveSettingControlProps ) {
	const title = readControlTitle( settingKey, meta );
	const description = readControlDescription( meta );
	const labelRequired = readControlLabelRequired( meta, settingKey );
	const rawOptions = readControlValue( 'options', ctx );
	const AppField = ctx.form?.AppField;

	const emptyHelper = __(
		'Add options in the Add Options tab first.',
		'woocommerce-product-addon'
	);

	if ( AppField ) {
		return (
			<AppField name={ settingKey }>
				{ ( field: any ) => {
					const currentValue =
						field.state.value == null
							? ''
							: String( field.state.value );
					const { options, isEmpty } = buildOptions(
						rawOptions,
						currentValue
					);
					const error = field.state.meta.errors?.[ 0 ];
					const helperContent = isEmpty ? emptyHelper : description;
					return (
						<Field.Root
							invalid={ Boolean( error ) }
							required={ labelRequired }
							disabled={ isEmpty }
						>
							<Field.Label { ...labelProps }>
								{ title }
								<Field.RequiredIndicator />
							</Field.Label>
							<NativeSelect.Root size="sm">
								<NativeSelect.Field
									required={ labelRequired }
									disabled={ isEmpty }
									value={ currentValue }
									onChange={ ( e ) =>
										field.handleChange( e.target.value )
									}
									onBlur={ field.handleBlur }
									{ ...controlSurface }
								>
									{ options.map( ( option ) => (
										<option
											key={ option.value }
											value={ option.value }
										>
											{ option.label }
										</option>
									) ) }
								</NativeSelect.Field>
								<NativeSelect.Indicator />
							</NativeSelect.Root>
							{ renderHelperText( helperContent ) }
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

	const currentValue = String( readControlValue( settingKey, ctx ) ?? '' );
	const { options, isEmpty } = buildOptions( rawOptions, currentValue );
	const helperContent = isEmpty ? emptyHelper : description;

	return (
		<Field.Root required={ labelRequired } disabled={ isEmpty }>
			<Field.Label { ...labelProps }>
				{ title }
				<Field.RequiredIndicator />
			</Field.Label>
			<NativeSelect.Root size="sm">
				<NativeSelect.Field
					required={ labelRequired }
					disabled={ isEmpty }
					value={ currentValue }
					onChange={ ( e ) =>
						updateFallbackSettingValue(
							ctx,
							settingKey,
							e.target.value
						)
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
			{ renderHelperText( helperContent ) }
		</Field.Root>
	);
}
