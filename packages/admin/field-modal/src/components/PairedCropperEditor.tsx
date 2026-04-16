/**
 * Viewport size rows for Image Cropper (paired-cropper schema type).
 */
import { Steps, Box, Button, HStack, Input, VStack, Field } from '@chakra-ui/react';
import type { Dispatch, SetStateAction } from 'react';
import type { FieldRow, I18nDict } from '../types/fieldModal';

export type CropperViewportRow = {
	option: string;
	width: string;
	height: string;
	price: string;
};

function emptyRow(): CropperViewportRow {
	return { option: '', width: '', height: '', price: '' };
}

function normalizeOneRow( item: unknown ): CropperViewportRow {
	if ( ! item || typeof item !== 'object' || Array.isArray( item ) ) {
		return emptyRow();
	}
	const o = item as Record< string, unknown >;
	return {
		option: String( o.option ?? '' ),
		width: String( o.width ?? '' ),
		height: String( o.height ?? '' ),
		price: String( o.price ?? '' ),
	};
}

/** Normalize stored options (associative object or array) into editable rows. */
export function normalizePairedCropperOptions(
	raw: unknown
): CropperViewportRow[] {
	if ( raw == null || raw === '' ) {
		return [ emptyRow() ];
	}
	if ( Array.isArray( raw ) ) {
		if ( raw.length === 0 ) {
			return [ emptyRow() ];
		}
		return raw.map( ( item ) => normalizeOneRow( item ) );
	}
	if ( typeof raw === 'object' ) {
		const rec = raw as Record< string, unknown >;
		const keys = Object.keys( rec ).sort(
			( a, b ) => Number( a ) - Number( b )
		);
		if ( keys.length === 0 ) {
			return [ emptyRow() ];
		}
		return keys.map( ( k ) => normalizeOneRow( rec[ k ] ) );
	}
	return [ emptyRow() ];
}

/** Persist as a JSON array of row objects (matches PHP `array` options). */
export function serializePairedCropperOptions(
	rows: CropperViewportRow[]
): unknown {
	const filtered = rows.filter(
		( r ) =>
			r.option.trim() !== '' ||
			r.width.trim() !== '' ||
			r.height.trim() !== '' ||
			r.price.trim() !== ''
	);
	if ( filtered.length === 0 ) {
		return [];
	}
	return filtered.map( ( r ) => ( {
		option: r.option,
		width: r.width,
		height: r.height,
		price: r.price,
	} ) );
}

const labelProps = {
	fontSize: '13px',
	fontWeight: '600',
	color: 'gray.800',
	mb: 0.5,
};

const helperTextProps = {
	mt: 1,
	fontSize: 'xs',
	color: 'gray.600',
	lineHeight: '1.45',
};

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

export interface PairedCropperEditorProps {
	fieldKey: string;
	title: string;
	description: string;
	values: FieldRow;
	onChange: Dispatch< SetStateAction< FieldRow | null > >;
	i18n: I18nDict;
}

export function PairedCropperEditor( {
	fieldKey,
	title,
	description,
	values,
	onChange,
	i18n,
}: PairedCropperEditorProps ) {
	const raw = values[ fieldKey ];
	const rows = normalizePairedCropperOptions( raw );

	const setRows = ( next: CropperViewportRow[] ) => {
		onChange( ( prev ) => {
			if ( ! prev ) {
				return prev;
			}
			return {
				...prev,
				[ fieldKey ]: serializePairedCropperOptions( next ),
			};
		} );
	};

	const updateRow = (
		index: number,
		patch: Partial< CropperViewportRow >
	) => {
		const next = rows.map( ( r, i ) =>
			i === index ? { ...r, ...patch } : r
		);
		setRows( next );
	};

	const addRow = () => {
		setRows( [ ...rows, emptyRow() ] );
	};

	const removeRow = ( index: number ) => {
		if ( rows.length <= 1 ) {
			setRows( [ emptyRow() ] );
			return;
		}
		setRows( rows.filter( ( _, i ) => i !== index ) );
	};

	const move = ( index: number, dir: -1 | 1 ) => {
		const j = index + dir;
		if ( j < 0 || j >= rows.length ) {
			return;
		}
		const next = [ ...rows ];
		const t = next[ index ];
		next[ index ] = next[ j ];
		next[ j ] = t;
		setRows( next );
	};

	const lbl =
		i18n.cropperViewportLabel || i18n.pairedOptionLabel || 'Label';
	const wPh = i18n.cropperViewportWidth || 'Width';
	const hPh = i18n.cropperViewportHeight || 'Height';
	const pricePh =
		i18n.cropperViewportPrice ||
		i18n.pairedOptionPrice ||
		'Price (optional)';

	return (
        <Field.Root>
            <Field.Label { ...labelProps }>{ title }</Field.Label>
            <Box
				borderWidth="1px"
				borderColor="gray.200"
				borderRadius="md"
				p={ 3 }
				bg="white"
			>
				<Button size="sm" colorPalette="blue" mb={ 3 } onClick={ addRow }>
					{ i18n.cropperViewportAddRow ||
						i18n.pairedOptionsAddRow ||
						'Add viewport' }
				</Button>

				<VStack align="stretch" gap={ 3 }>
					{ rows.map( ( row, index ) => (
						<Box
							key={ index }
							borderWidth="1px"
							borderColor="gray.100"
							borderRadius="md"
							p={ 2 }
						>
							<HStack
								align="flex-start"
								gap={ 2 }
								w="full"
								flexWrap="wrap"
							>
								<Input
									size="sm"
									flex="1 1 120px"
									minW={ 0 }
									placeholder={ lbl }
									value={ row.option }
									onValueChange={ ( e ) =>
										updateRow( index, {
											option: e.target.value,
										} )
									}
									{ ...controlSurface }
								/>
								<Input
									size="sm"
									flex="1 1 72px"
									minW={ 0 }
									placeholder={ wPh }
									value={ row.width }
									onValueChange={ ( e ) =>
										updateRow( index, {
											width: e.target.value,
										} )
									}
									{ ...controlSurface }
								/>
								<Input
									size="sm"
									flex="1 1 72px"
									minW={ 0 }
									placeholder={ hPh }
									value={ row.height }
									onValueChange={ ( e ) =>
										updateRow( index, {
											height: e.target.value,
										} )
									}
									{ ...controlSurface }
								/>
								<Input
									size="sm"
									flex="1 1 96px"
									minW={ 0 }
									placeholder={ pricePh }
									value={ row.price }
									onValueChange={ ( e ) =>
										updateRow( index, {
											price: e.target.value,
										} )
									}
									{ ...controlSurface }
								/>
								<HStack gap={ 1 } flexShrink={ 0 }>
									<Button
										size="xs"
										variant="ghost"
										aria-label={
											i18n.pairedOptionsMoveUp ||
											'Move up'
										}
										onClick={ () => move( index, -1 ) }
										disabled={ index === 0 }
									>
										&#8593;
									</Button>
									<Button
										size="xs"
										variant="ghost"
										aria-label={
											i18n.pairedOptionsMoveDown ||
											'Move down'
										}
										onClick={ () => move( index, 1 ) }
										disabled={
											index === rows.length - 1
										}
									>
										&#8595;
									</Button>
									<Button
										size="xs"
										variant="ghost"
										colorPalette="red"
										onClick={ () => removeRow( index ) }
									>
										{ i18n.pairedOptionsRemove ||
											'Remove' }
									</Button>
								</HStack>
							</HStack>
						</Box>
					) ) }
				</VStack>
			</Box>
            { description ? (
				<Field.HelperText { ...helperTextProps }>
					{ description }
				</Field.HelperText>
			) : null }
        </Field.Root>
    );
}
