/**
 * Single setting row renderer shared by schema fallback and typed field editors.
 */
import {
	Box,
	ColorPicker,
	Field,
	HStack,
	Input,
	NativeSelect,
	Switch,
	Text,
	Textarea,
	parseColor,
} from '@chakra-ui/react';
import { normalizeSelectOptions } from './schemaTabs';
import { ConditionsEditor } from './ConditionsEditor';
import { PairedCropperEditor } from './components/PairedCropperEditor';
import { PairedQuantityEditor } from './components/PairedQuantityEditor';
import type { SettingRowContext } from './types/fieldModal';

const controlSurface = {
	bg: 'white',
	borderColor: 'gray.200',
	borderRadius: 'md',
	_hover: { borderColor: 'gray.300' },
	_focus: {
		borderColor: 'blue.500',
		boxShadow: '0 0 0 1px #2271b1',
	},
};

const helperTextProps = {
	mt: 1,
	fontSize: 'xs',
	color: 'gray.600',
	lineHeight: '1.45',
	sx: {
		a: { color: 'blue.600', textDecoration: 'underline' },
	},
};

const labelProps = {
	fontSize: '13px',
	fontWeight: '600',
	color: 'gray.800',
	mb: 0.5,
};

/** `rgb(…)` / `rgba(…)` → `#rrggbb` without relying on Ark `toString('hex')` (can throw rgb→hex). */
function rgbCssStringToHex6( rgb: string ): string {
	const m = rgb.match(
		/rgba?\(\s*([\d.]+)\s*[,\s]\s*([\d.]+)\s*[,\s]\s*([\d.]+)/i
	);
	if ( ! m ) {
		return '#000000';
	}
	const r = Math.max( 0, Math.min( 255, Math.round( Number( m[ 1 ] ) ) ) );
	const g = Math.max( 0, Math.min( 255, Math.round( Number( m[ 2 ] ) ) ) );
	const b = Math.max( 0, Math.min( 255, Math.round( Number( m[ 3 ] ) ) ) );
	return (
		'#' +
		[ r, g, b ]
			.map( ( c ) => c.toString( 16 ).padStart( 2, '0' ) )
			.join( '' )
	);
}

/** Normalize stored PPOM values (`#fff`, `rgb()`, named colors) to `#rrggbb` for `parseColor`. */
function normalizeStoredColorToHex6( raw: string ): string {
	const s = raw.trim();
	if ( ! s ) {
		return '#000000';
	}
	if ( s[ 0 ] === '#' ) {
		const body = s.slice( 1 );
		if ( /^[0-9a-f]{3}$/i.test( body ) ) {
			const [ a, b, c ] = body.toLowerCase().split( '' );
			return `#${ a }${ a }${ b }${ b }${ c }${ c }`;
		}
		if ( /^[0-9a-f]{6}$/i.test( body ) ) {
			return `#${ body.toLowerCase() }`;
		}
	}
	if ( /^rgba?\(/i.test( s ) ) {
		return rgbCssStringToHex6( s );
	}
	try {
		const c = parseColor( s );
		try {
			return c.toString( 'hex' );
		} catch {
			try {
				return rgbCssStringToHex6( c.toString( 'rgb' ) );
			} catch {
				return '#000000';
			}
		}
	} catch {
		return '#000000';
	}
}

function colorFromStoredValue( raw: unknown ) {
	const hex = normalizeStoredColorToHex6(
		raw == null ? '' : String( raw )
	);
	return parseColor( hex );
}

function persistColorValueAsHex( value: { toString( fmt: string ): string } ) {
	try {
		return value.toString( 'hex' );
	} catch {
		try {
			return rgbCssStringToHex6( value.toString( 'rgb' ) );
		} catch {
			return '#000000';
		}
	}
}

export function openLegacyFieldModal( ppomFieldIndex: number ) {
	if ( ! ppomFieldIndex || ppomFieldIndex < 1 ) {
		return;
	}
	const btn = document.querySelector(
		`button.ppom-edit-field[id="${ String( ppomFieldIndex ) }"]`
	);
	if ( ! btn ) {
		return;
	}
	( btn as HTMLElement ).click();
}

export function renderSettingRow(
	key: string,
	meta: Record< string, unknown >,
	ctx: SettingRowContext
) {
	const { values, onChange, i18n, ppomFieldIndex } = ctx;
	const type = meta.type ? String( meta.type ) : 'text';
	const title = meta.title ? String( meta.title ) : key;
	const desc = meta.desc ? String( meta.desc ) : '';
	const raw = values[ key ];
	const value = raw == null ? '' : raw;

	const setKey = ( k: string, val: unknown ) => {
		onChange( { ...values, [ k ]: val } );
	};

	if ( meta.hidden ) {
		return null;
	}

	if ( type === 'textarea' ) {
		return (
            <Field.Root key={ key }>
                <Field.Label { ...labelProps }>{ title }</Field.Label>
                <Textarea
					size="sm"
					rows={ 2 }
					resize="vertical"
					value={ String( value ) }
					onValueChange={ ( e ) => setKey( key, e.target.value ) }
					{ ...controlSurface }
				/>
                { desc ? (
					<Field.HelperText
						{ ...helperTextProps }
						dangerouslySetInnerHTML={ { __html: desc } }
					/>
				) : null }
            </Field.Root>
        );
	}

	if ( type === 'select' ) {
		const opts = normalizeSelectOptions( meta.options );
		return (
            <Field.Root key={ key }>
                <Field.Label { ...labelProps }>{ title }</Field.Label>
                <NativeSelect.Root>
                    <NativeSelect.Field
                        size="sm"
                        value={ String( value ) }
                        onValueChange={ ( e ) => setKey( key, e.target.value ) }
                        { ...controlSurface }>
                        { opts.map( ( o ) => (
                            <option key={ o.value } value={ o.value }>
                                { o.label }
                            </option>
                        ) ) }
                    </NativeSelect.Field>
                    <NativeSelect.Indicator />
                </NativeSelect.Root>
                { desc ? (
					<Field.HelperText { ...helperTextProps }>{ desc }</Field.HelperText>
				) : null }
            </Field.Root>
        );
	}

	if ( type === 'checkbox' ) {
		const checked = value === 'on' || value === true || value === '1';
		return (
            <Field.Root
				key={ key }
				display="grid"
				gridTemplateColumns="auto minmax(0, 1fr)"
				columnGap={ 2 }
				rowGap={ title && desc ? 0.5 : 0 }
				alignItems="start"
				py={ 1.5 }
				px={ 2 }
				mb={ 0 }
			>
                <Switch.Root
					gridRow={ 1 }
					gridColumn={ 1 }
					mt={ title ? 0.5 : 0 }
					alignSelf="start"
					colorPalette="blue"
					checked={ checked }
					onCheckedChange={ ( { checked: next } ) =>
						setKey( key, next ? 'on' : 'off' )
					}
				>
					<Switch.HiddenInput />
					<Switch.Control />
				</Switch.Root>
				<Field.Label
					{ ...labelProps }
					gridRow={ 1 }
					gridColumn={ 2 }
					mb={ 0 }
				>
					{ title }
				</Field.Label>
				{ desc ? (
					<Text
						gridRow={ title ? 2 : 1 }
						gridColumn={ 2 }
						fontSize="xs"
						color="gray.600"
						lineHeight="1.5"
					>
						{ desc }
					</Text>
				) : null }
            </Field.Root>
        );
	}

	if ( type === 'html-conditions' ) {
		const builderFields = ctx.builderFields || [];
		const conditionsProEnabled = !! ctx.conditionsProEnabled;
		return (
			<ConditionsEditor
				key={ key }
				meta={ meta }
				values={ values }
				onChange={ ( nextRow ) => ctx.onChange( nextRow ) }
				i18n={ i18n }
				builderFields={ builderFields }
				conditionsProEnabled={ conditionsProEnabled }
			/>
		);
	}

	if ( type === 'paired-cropper' ) {
		return (
			<PairedCropperEditor
				key={ key }
				fieldKey={ key }
				title={ title }
				description={ desc }
				values={ values }
				onChange={ ctx.onChange }
				i18n={ i18n }
			/>
		);
	}

	if ( type === 'paired-quantity' ) {
		return (
			<PairedQuantityEditor
				key={ key }
				fieldKey={ key }
				title={ title }
				description={ desc }
				values={ values }
				onChange={ ctx.onChange }
				i18n={ i18n }
			/>
		);
	}

	if ( type === 'color' ) {
		return (
			<Field.Root key={ key }>
				<Field.Label { ...labelProps }>{ title }</Field.Label>
				<ColorPicker.Root
					value={ colorFromStoredValue( value ) }
					onValueChange={ ( details ) =>
						setKey( key, persistColorValueAsHex( details.value ) )
					}
					size="sm"
					maxW="200px"
				>
					<ColorPicker.HiddenInput />
					<ColorPicker.Control>
						<ColorPicker.Input />
						<ColorPicker.Trigger />
					</ColorPicker.Control>
					<ColorPicker.Positioner>
						<ColorPicker.Content>
							<ColorPicker.Area />
							<HStack>
								<ColorPicker.EyeDropper size="xs" variant="outline" />
								<ColorPicker.Sliders />
							</HStack>
						</ColorPicker.Content>
					</ColorPicker.Positioner>
				</ColorPicker.Root>
				{ desc ? (
					<Field.HelperText { ...helperTextProps }>{ desc }</Field.HelperText>
				) : null }
				{ meta.link ? (
					<Box
						fontSize="xs"
						mt={ 1 }
						color="gray.600"
						sx={ helperTextProps.sx }
						dangerouslySetInnerHTML={ { __html: String( meta.link ) } }
					/>
				) : null }
			</Field.Root>
		);
	}

	return (
        <Field.Root key={ key }>
            <Field.Label { ...labelProps }>{ title }</Field.Label>
            <Input
				size="sm"
				value={ String( value ) }
				onValueChange={ ( e ) => setKey( key, e.target.value ) }
				{ ...controlSurface }
			/>
            { desc ? (
				<Field.HelperText { ...helperTextProps }>{ desc }</Field.HelperText>
			) : null }
            { meta.link ? (
				<Box
					fontSize="xs"
					mt={ 1 }
					color="gray.600"
					sx={ helperTextProps.sx }
					dangerouslySetInnerHTML={ { __html: String( meta.link ) } }
				/>
			) : null }
        </Field.Root>
    );
}
