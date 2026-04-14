/**
 * Single setting row renderer shared by schema fallback and typed field editors.
 */
import {
	Box,
	FormControl,
	FormLabel,
	Input,
	Textarea,
	Select,
	Switch,
	FormHelperText,
	Text,
} from '@chakra-ui/react';
import { normalizeSelectOptions } from './schemaTabs';
import { ConditionsEditor } from './ConditionsEditor';
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
			<FormControl key={ key }>
				<FormLabel { ...labelProps }>{ title }</FormLabel>
				<Textarea
					size="sm"
					rows={ 2 }
					resize="vertical"
					value={ String( value ) }
					onChange={ ( e ) => setKey( key, e.target.value ) }
					{ ...controlSurface }
				/>
				{ desc ? (
					<FormHelperText
						{ ...helperTextProps }
						dangerouslySetInnerHTML={ { __html: desc } }
					/>
				) : null }
			</FormControl>
		);
	}

	if ( type === 'select' ) {
		const opts = normalizeSelectOptions( meta.options );
		return (
			<FormControl key={ key }>
				<FormLabel { ...labelProps }>{ title }</FormLabel>
				<Select
					size="sm"
					value={ String( value ) }
					onChange={ ( e ) => setKey( key, e.target.value ) }
					{ ...controlSurface }
				>
					{ opts.map( ( o ) => (
						<option key={ o.value } value={ o.value }>
							{ o.label }
						</option>
					) ) }
				</Select>
				{ desc ? (
					<FormHelperText { ...helperTextProps }>{ desc }</FormHelperText>
				) : null }
			</FormControl>
		);
	}

	if ( type === 'checkbox' ) {
		const checked = value === 'on' || value === true || value === '1';
		return (
			<FormControl
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
				<Switch
					mt={ 0.5 }
					colorScheme="blue"
					isChecked={ checked }
					onChange={ ( e ) => setKey( key, e.target.checked ? 'on' : 'off' ) }
				/>
				<Box flex="1" minW={ 0 }>
					<FormLabel { ...labelProps } mb={ 0.5 }>
						{ title }
					</FormLabel>
					{ desc ? (
						<Text fontSize="xs" color="gray.600" lineHeight="1.5">
							{ desc }
						</Text>
					) : null }
				</Box>
			</FormControl>
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

	return (
		<FormControl key={ key }>
			<FormLabel { ...labelProps }>{ title }</FormLabel>
			<Input
				size="sm"
				value={ String( value ) }
				onChange={ ( e ) => setKey( key, e.target.value ) }
				{ ...controlSurface }
			/>
			{ desc ? (
				<FormHelperText { ...helperTextProps }>{ desc }</FormHelperText>
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
		</FormControl>
	);
}
