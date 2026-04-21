/**
 * Bulk quantity matrix: rows keyed by column headers (Quantity Range, Base Price, + variations).
 * Matches classic ppom-bulkquantity.js tableToJSON() shape.
 */
import { Box, Button, HStack, Text, VStack } from '@chakra-ui/react';
import type { Dispatch, SetStateAction } from 'react';
import type { FieldRow, I18nDict } from '../types/fieldModal';
import {
	type BulkQuantityRow,
	emptyBulkRow,
	ensureRowKeys,
	parseOptionsRaw,
} from '../utils/bulkQuantityData';
import { BulkQuantityTable } from './bulk-quantity/BulkQuantityTable';

export interface BulkQuantityMatrixEditorProps {
	values: FieldRow;
	onChange: Dispatch< SetStateAction< FieldRow | null > >;
	i18n: I18nDict;
	title: string;
}

export function BulkQuantityMatrixEditor( {
	values,
	onChange,
	i18n,
	title,
}: BulkQuantityMatrixEditorProps ) {
	const rangeKey = i18n.bulkQtyColumnQuantityRange || 'Quantity Range';
	const baseKey = i18n.bulkQtyColumnBasePrice || 'Base Price';

	const rows = parseOptionsRaw( values.options );
	const columns =
		rows.length > 0 ? Object.keys( rows[ 0 ] ) : [ rangeKey, baseKey ];

	const setRowsAndPersist = ( next: BulkQuantityRow[] ) => {
		onChange( ( prev ) => {
			if ( ! prev ) {
				return prev;
			}
			return { ...prev, options: next };
		} );
	};

	const normalizedRows =
		rows.length > 0
			? rows.map( ( r ) => ensureRowKeys( r, columns ) )
			: [ { [ rangeKey ]: '', [ baseKey ]: '' } ];

	const updateCell = ( rowIndex: number, col: string, value: string ) => {
		setRowsAndPersist(
			normalizedRows.map( ( r, i ) =>
				i === rowIndex ? { ...r, [ col ]: value } : r
			)
		);
	};

	const addRow = () => {
		setRowsAndPersist( [ ...normalizedRows, emptyBulkRow( columns ) ] );
	};

	const removeRow = ( index: number ) => {
		if ( normalizedRows.length < 2 ) {
			return;
		}
		setRowsAndPersist( normalizedRows.filter( ( _, i ) => i !== index ) );
	};

	const addVariationColumn = () => {
		const name = window.prompt(
			i18n.bulkQtyNewVariationPrompt || 'Variation column name',
			''
		);
		if ( ! name || ! name.trim() ) {
			return;
		}
		const key = name.trim();
		if ( columns.includes( key ) ) {
			return;
		}
		const nextRows = normalizedRows.map( ( r ) => ( {
			...r,
			[ key ]: '',
		} ) );
		onChange( ( prev ) => {
			if ( ! prev ) {
				return prev;
			}
			return { ...prev, options: nextRows };
		} );
	};

	const removeColumn = ( col: string, index: number ) => {
		if ( index < 2 ) {
			return;
		}
		const nextRows = normalizedRows.map( ( r ) => {
			const o = { ...r };
			delete o[ col ];
			return o;
		} );
		setRowsAndPersist( nextRows );
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
			<VStack align="stretch" gap={ 3 }>
				<HStack gap={ 2 } flexWrap="wrap">
					<Button size="xs" onClick={ addRow }>
						{ i18n.bulkQtyAddRow || 'Add quantity range' }
					</Button>
					<Button
						size="xs"
						variant="outline"
						onClick={ addVariationColumn }
					>
						{ i18n.bulkQtyAddVariation || 'Add variation column' }
					</Button>
				</HStack>
				<BulkQuantityTable
					columns={ columns }
					rows={ normalizedRows }
					rangeKey={ rangeKey }
					i18n={ i18n }
					onUpdateCell={ updateCell }
					onRemoveRow={ removeRow }
					onRemoveColumn={ removeColumn }
				/>
				<Text fontSize="xs" color="gray.600">
					{ i18n.bulkQtyRangeHint ||
						'Quantity range format: start-end (e.g. 1-10).' }
				</Text>
			</VStack>
		</Box>
	);
}
