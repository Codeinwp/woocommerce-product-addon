/**
 * Bulk quantity matrix: rows keyed by column headers (Quantity Range, Base Price, + variations).
 * Matches classic ppom-bulkquantity.js tableToJSON() shape.
 */
import {
	Box,
	Button,
	HStack,
	Input,
	Table,
	Tbody,
	Td,
	Th,
	Thead,
	Tr,
	Text,
	VStack,
} from '@chakra-ui/react';
import type { Dispatch, SetStateAction } from 'react';
import type { FieldRow } from '../types/fieldModal';
import type { I18nDict } from '../types/fieldModal';

function parseOptionsRaw( raw: unknown ): Record< string, string >[] {
	if ( raw == null || raw === '' ) {
		return [];
	}
	if ( typeof raw === 'string' ) {
		try {
			const p = JSON.parse( raw ) as unknown;
			return parseOptionsRaw( p );
		} catch {
			return [];
		}
	}
	if ( Array.isArray( raw ) ) {
		return raw.map( ( row ) => {
			if ( ! row || typeof row !== 'object' || Array.isArray( row ) ) {
				return {};
			}
			const o: Record< string, string > = {};
			for ( const [ k, v ] of Object.entries( row ) ) {
				o[ k ] = v == null ? '' : String( v );
			}
			return o;
		} );
	}
	return [];
}

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
	const rangeKey =
		i18n.bulkQtyColumnQuantityRange || 'Quantity Range';
	const baseKey = i18n.bulkQtyColumnBasePrice || 'Base Price';

	const rows = parseOptionsRaw( values.options );

	const columns =
		rows.length > 0
			? Object.keys( rows[ 0 ] )
			: [ rangeKey, baseKey ];

	const setRowsAndPersist = ( next: Record< string, string >[] ) => {
		onChange( ( prev ) => {
			if ( ! prev ) {
				return prev;
			}
			return { ...prev, options: next };
		} );
	};

	const ensureRowKeys = (
		row: Record< string, string >
	): Record< string, string > => {
		const out: Record< string, string > = {};
		for ( const c of columns ) {
			out[ c ] = row[ c ] ?? '';
		}
		return out;
	};

	const normalizedRows =
		rows.length > 0
			? rows.map( ensureRowKeys )
			: [ { [ rangeKey ]: '', [ baseKey ]: '' } ];

	const updateCell = (
		rowIndex: number,
		col: string,
		value: string
	) => {
		const next = normalizedRows.map( ( r, i ) =>
			i === rowIndex ? { ...r, [ col ]: value } : r
		);
		setRowsAndPersist( next );
	};

	const addRow = () => {
		const empty: Record< string, string > = {};
		for ( const c of columns ) {
			empty[ c ] = '';
		}
		setRowsAndPersist( [ ...normalizedRows, empty ] );
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
		const nextCols = [ ...columns, key ];
		const nextRows = normalizedRows.map( ( r ) => {
			const o = { ...r };
			o[ key ] = '';
			return o;
		} );
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
			<VStack align="stretch" spacing={ 3 }>
				<HStack spacing={ 2 } flexWrap="wrap">
					<Button size="xs" onClick={ addRow }>
						{ i18n.bulkQtyAddRow || 'Add quantity range' }
					</Button>
					<Button size="xs" variant="outline" onClick={ addVariationColumn }>
						{ i18n.bulkQtyAddVariation || 'Add variation column' }
					</Button>
				</HStack>
				<Box overflowX="auto">
					<Table size="sm" variant="simple">
						<Thead>
							<Tr>
								{ columns.map( ( col, ci ) => (
									<Th key={ col } whiteSpace="nowrap">
										{ col }
										{ ci >= 2 ? (
											<Button
												ml={ 1 }
												size="xs"
												variant="ghost"
												colorScheme="red"
												onClick={ () =>
													removeColumn( col, ci )
												}
											>
												×
											</Button>
										) : null }
									</Th>
								) ) }
								<Th>{ i18n.bulkQtyActions || '' }</Th>
							</Tr>
						</Thead>
						<Tbody>
							{ normalizedRows.map( ( row, ri ) => (
								<Tr key={ ri }>
									{ columns.map( ( col ) => (
										<Td key={ col }>
											<Input
												size="sm"
												value={ row[ col ] ?? '' }
												onChange={ ( e ) =>
													updateCell(
														ri,
														col,
														e.target.value
													)
												}
												placeholder={
													col === rangeKey
														? i18n.bulkQtyRangePlaceholder ||
														  '1-10'
														: ''
												}
											/>
										</Td>
									) ) }
									<Td>
										<Button
											size="xs"
											variant="ghost"
											colorScheme="red"
											isDisabled={
												normalizedRows.length < 2
											}
											onClick={ () => removeRow( ri ) }
										>
											{ i18n.pairedOptionsRemove ||
												'Remove' }
										</Button>
									</Td>
								</Tr>
							) ) }
						</Tbody>
					</Table>
				</Box>
				<Text fontSize="xs" color="gray.600">
					{ i18n.bulkQtyRangeHint ||
						'Quantity range format: start-end (e.g. 1-10).' }
				</Text>
			</VStack>
		</Box>
	);
}
