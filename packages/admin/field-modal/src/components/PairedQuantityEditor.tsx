/**
 * Variation quantity matrix rows (paired-quantity schema type).
 */
import {
	Box,
	Button,
	FormControl,
	FormHelperText,
	FormLabel,
	HStack,
	Input,
	VStack,
} from '@chakra-ui/react';
import type { Dispatch, SetStateAction } from 'react';
import type { FieldRow, I18nDict } from '../types/fieldModal';

export type QuantityOptionRow = {
	option: string;
	price: string;
	weight: string;
	defaultQty: string;
	min: string;
	max: string;
	stock: string;
};

function emptyRow(): QuantityOptionRow {
	return {
		option: '',
		price: '',
		weight: '',
		defaultQty: '',
		min: '',
		max: '',
		stock: '',
	};
}

function normalizeOneRow( item: unknown ): QuantityOptionRow {
	if ( ! item || typeof item !== 'object' || Array.isArray( item ) ) {
		return emptyRow();
	}
	const o = item as Record< string, unknown >;
	return {
		option: String( o.option ?? '' ),
		price: String( o.price ?? '' ),
		weight: String( o.weight ?? '' ),
		defaultQty: String( o.default ?? '' ),
		min: String( o.min ?? '' ),
		max: String( o.max ?? '' ),
		stock: String( o.stock ?? '' ),
	};
}

/** Normalize stored options (associative object or array) into editable rows. */
export function normalizePairedQuantityOptions(
	raw: unknown
): QuantityOptionRow[] {
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

/** Persist with PHP key `default` for default quantity. */
export function serializePairedQuantityOptions(
	rows: QuantityOptionRow[]
): unknown {
	const filtered = rows.filter(
		( r ) =>
			r.option.trim() !== '' ||
			r.price.trim() !== '' ||
			r.weight.trim() !== '' ||
			r.defaultQty.trim() !== '' ||
			r.min.trim() !== '' ||
			r.max.trim() !== '' ||
			r.stock.trim() !== ''
	);
	if ( filtered.length === 0 ) {
		return [];
	}
	return filtered.map( ( r ) => ( {
		option: r.option,
		price: r.price,
		weight: r.weight,
		default: r.defaultQty,
		min: r.min,
		max: r.max,
		stock: r.stock,
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

export interface PairedQuantityEditorProps {
	fieldKey: string;
	title: string;
	description: string;
	values: FieldRow;
	onChange: Dispatch< SetStateAction< FieldRow | null > >;
	i18n: I18nDict;
}

export function PairedQuantityEditor( {
	fieldKey,
	title,
	description,
	values,
	onChange,
	i18n,
}: PairedQuantityEditorProps ) {
	const raw = values[ fieldKey ];
	const rows = normalizePairedQuantityOptions( raw );

	const setRows = ( next: QuantityOptionRow[] ) => {
		onChange( ( prev ) => {
			if ( ! prev ) {
				return prev;
			}
			return {
				...prev,
				[ fieldKey ]: serializePairedQuantityOptions( next ),
			};
		} );
	};

	const updateRow = (
		index: number,
		patch: Partial< QuantityOptionRow >
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

	const ph = {
		option:
			i18n.quantityPairedOptionPlaceholder ||
			i18n.pairedOptionLabel ||
			'Option',
		price:
			i18n.quantityPairedPricePlaceholder ||
			i18n.pairedOptionPrice ||
			'Price',
		weight: i18n.quantityPairedWeightPlaceholder || 'Weight',
		defaultQty:
			i18n.quantityPairedDefaultPlaceholder || 'Default qty',
		min: i18n.quantityPairedMinPlaceholder || 'Min qty',
		max: i18n.quantityPairedMaxPlaceholder || 'Max qty',
		stock: i18n.quantityPairedStockPlaceholder || 'Stock',
	};

	return (
		<FormControl>
			<FormLabel { ...labelProps }>{ title }</FormLabel>
			<Box
				borderWidth="1px"
				borderColor="gray.200"
				borderRadius="md"
				p={ 3 }
				bg="white"
			>
				<Button size="sm" colorScheme="blue" mb={ 3 } onClick={ addRow }>
					{ i18n.quantityPairedAddRow ||
						i18n.pairedOptionsAddRow ||
						'Add option' }
				</Button>

				<VStack align="stretch" spacing={ 3 }>
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
								spacing={ 2 }
								w="full"
								flexWrap="wrap"
							>
								<Input
									size="sm"
									flex="1 1 72px"
									minW={ 0 }
									placeholder={ ph.option }
									value={ row.option }
									onChange={ ( e ) =>
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
									placeholder={ ph.price }
									value={ row.price }
									onChange={ ( e ) =>
										updateRow( index, {
											price: e.target.value,
										} )
									}
									{ ...controlSurface }
								/>
								<Input
									size="sm"
									flex="1 1 72px"
									minW={ 0 }
									placeholder={ ph.weight }
									value={ row.weight }
									onChange={ ( e ) =>
										updateRow( index, {
											weight: e.target.value,
										} )
									}
									{ ...controlSurface }
								/>
								<Input
									size="sm"
									flex="1 1 72px"
									minW={ 0 }
									placeholder={ ph.defaultQty }
									value={ row.defaultQty }
									onChange={ ( e ) =>
										updateRow( index, {
											defaultQty: e.target.value,
										} )
									}
									{ ...controlSurface }
								/>
								<Input
									size="sm"
									flex="1 1 64px"
									minW={ 0 }
									placeholder={ ph.min }
									value={ row.min }
									onChange={ ( e ) =>
										updateRow( index, {
											min: e.target.value,
										} )
									}
									{ ...controlSurface }
								/>
								<Input
									size="sm"
									flex="1 1 64px"
									minW={ 0 }
									placeholder={ ph.max }
									value={ row.max }
									onChange={ ( e ) =>
										updateRow( index, {
											max: e.target.value,
										} )
									}
									{ ...controlSurface }
								/>
								<Input
									size="sm"
									flex="1 1 64px"
									minW={ 0 }
									placeholder={ ph.stock }
									value={ row.stock }
									onChange={ ( e ) =>
										updateRow( index, {
											stock: e.target.value,
										} )
									}
									{ ...controlSurface }
								/>
								<HStack spacing={ 1 } flexShrink={ 0 }>
									<Button
										size="xs"
										variant="ghost"
										aria-label={
											i18n.pairedOptionsMoveUp ||
											'Move up'
										}
										onClick={ () => move( index, -1 ) }
										isDisabled={ index === 0 }
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
										isDisabled={
											index === rows.length - 1
										}
									>
										&#8595;
									</Button>
									<Button
										size="xs"
										variant="ghost"
										colorScheme="red"
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
				<FormHelperText { ...helperTextProps }>
					{ description }
				</FormHelperText>
			) : null }
		</FormControl>
	);
}
