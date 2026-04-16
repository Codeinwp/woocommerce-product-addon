/**
 * Fixed Price paired rows: quantity + fixed price (number inputs), classic input.fixedprice.php shape.
 */
import { Box, Button, HStack, Input, Text, VStack } from '@chakra-ui/react';
import type { Dispatch, SetStateAction } from 'react';
import type { FieldRow } from '../types/fieldModal';
import type { I18nDict } from '../types/fieldModal';
import type { PairedOptionRow } from './PairedOptionsEditor';

function normalizeOptionsArray( raw: unknown ): PairedOptionRow[] {
	if ( Array.isArray( raw ) ) {
		return raw.map( ( o ) =>
			o && typeof o === 'object' && ! Array.isArray( o )
				? { ...( o as Record< string, unknown > ) }
				: {}
		);
	}
	if ( raw && typeof raw === 'object' && ! Array.isArray( raw ) ) {
		return Object.keys( raw as object ).map( ( k ) => {
			const v = ( raw as Record< string, unknown > )[ k ];
			if ( v && typeof v === 'object' && ! Array.isArray( v ) ) {
				return { ...( v as Record< string, unknown > ) };
			}
			return { option: k };
		} );
	}
	return [];
}

export interface PairedFixedPriceEditorProps {
	values: FieldRow;
	onChange: Dispatch< SetStateAction< FieldRow | null > >;
	i18n: I18nDict;
	title: string;
	/** From schema `options.placeholders` (e.g. quantity label, price label). */
	placeholders?: string[];
	/** From schema `options.types` (e.g. `number`, `number`). */
	types?: string[];
}

export function PairedFixedPriceEditor( {
	values,
	onChange,
	i18n,
	title,
	placeholders = [],
	types = [ 'number', 'number' ],
}: PairedFixedPriceEditorProps ) {
	const rows = normalizeOptionsArray( values.options );
	const p0 = placeholders[ 0 ] || i18n.fixedPriceQtyPlaceholder || 'Quantity';
	const p1 = placeholders[ 1 ] || i18n.fixedPricePricePlaceholder || 'Fixed Price';
	const t0 = types[ 0 ] === 'number' ? 'number' : 'text';
	const t1 = types[ 1 ] === 'number' ? 'number' : 'text';

	const setRows = ( next: PairedOptionRow[] ) => {
		onChange( ( prev ) => {
			if ( ! prev ) {
				return prev;
			}
			return { ...prev, options: next };
		} );
	};

	const updateRow = ( index: number, patch: Partial< PairedOptionRow > ) => {
		const next = rows.map( ( r, i ) =>
			i === index ? { ...r, ...patch } : r
		);
		setRows( next );
	};

	const addRow = () => {
		setRows( [ ...rows, { option: '', price: '', id: '' } ] );
	};

	const removeRow = ( index: number ) => {
		setRows( rows.filter( ( _, i ) => i !== index ) );
	};

	const move = ( index: number, dir: -1 | 1 ) => {
		const j = index + dir;
		if ( j < 0 || j >= rows.length ) {
			return;
		}
		const next = [ ...rows ];
		const tmp = next[ index ];
		next[ index ] = next[ j ];
		next[ j ] = tmp;
		setRows( next );
	};

	return (
		<Box
			borderWidth="1px"
			borderColor="gray.200"
			borderRadius="md"
			p={ 3 }
			bg="white"
		>
			<Text fontWeight="semibold" fontSize="sm" mb={ 3 }>
				{ title }
			</Text>
			<VStack align="stretch" spacing={ 3 }>
				{ rows.map( ( row, index ) => (
					<Box
						key={ index }
						borderWidth="1px"
						borderColor="gray.100"
						borderRadius="md"
						p={ 2 }
					>
						<HStack spacing={ 2 } flexWrap="wrap" align="center">
							<Input
								size="sm"
								type={ t0 }
								flex="1 1 120px"
								minW={ 0 }
								placeholder={ p0 }
								value={ String( row.option ?? '' ) }
								onChange={ ( e ) =>
									updateRow( index, {
										option: e.target.value,
									} )
								}
							/>
							<Input
								size="sm"
								type={ t1 }
								flex="1 1 120px"
								minW={ 0 }
								placeholder={ p1 }
								value={ String( row.price ?? '' ) }
								onChange={ ( e ) =>
									updateRow( index, { price: e.target.value } )
								}
							/>
							<Input
								size="sm"
								flex="1 1 100px"
								minW={ 0 }
								placeholder={
									i18n.pairedMatrixOptionId || 'Unique ID'
								}
								value={ String( row.id ?? '' ) }
								onChange={ ( e ) =>
									updateRow( index, { id: e.target.value } )
								}
							/>
							<HStack spacing={ 1 }>
								<Button
									size="xs"
									variant="ghost"
									onClick={ () => move( index, -1 ) }
									isDisabled={ index === 0 }
								>
									↑
								</Button>
								<Button
									size="xs"
									variant="ghost"
									onClick={ () => move( index, 1 ) }
									isDisabled={ index === rows.length - 1 }
								>
									↓
								</Button>
								<Button
									size="xs"
									variant="ghost"
									colorScheme="red"
									onClick={ () => removeRow( index ) }
								>
									{ i18n.pairedOptionsRemove || 'Remove' }
								</Button>
							</HStack>
						</HStack>
					</Box>
				) ) }
				<Button size="sm" alignSelf="flex-start" onClick={ addRow }>
					{ i18n.pairedOptionsAddRow || 'Add option' }
				</Button>
			</VStack>
		</Box>
	);
}
