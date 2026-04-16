/**
 * Single setting row renderer shared by schema fallback and typed field editors.
 */
import { Box, Field, Input, NativeSelect, Switch, Text, Textarea } from '@chakra-ui/react';
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
				display="flex"
				alignItems="flex-start"
				gap={ 2 }
				p={ 2 }
				bg="gray.50"
				borderRadius="md"
				borderWidth="1px"
				borderColor="gray.100"
			>
                <Switch.Root
					mt={ 0.5 }
					colorPalette="blue"
					checked={ checked }
					onCheckedChange={ ( { checked: next } ) =>
						setKey( key, next ? 'on' : 'off' )
					}
				>
					<Switch.HiddenInput />
					<Switch.Control />
				</Switch.Root>
                <Box flex="1" minW={ 0 }>
					<Field.Label { ...labelProps } mb={ 0.5 }>
						{ title }
					</Field.Label>
					{ desc ? (
						<Text fontSize="xs" color="gray.600" lineHeight="1.5">
							{ desc }
						</Text>
					) : null }
				</Box>
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
