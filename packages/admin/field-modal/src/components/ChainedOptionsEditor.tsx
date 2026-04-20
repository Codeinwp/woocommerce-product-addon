/**
 * Inline editor for chained option rows.
 */
import {
	Box,
	Button,
	HStack,
	IconButton,
	Input,
	Text,
	VStack,
} from '@chakra-ui/react';
import { LuTrash2 } from 'react-icons/lu';
import type { Dispatch, SetStateAction } from 'react';
import type { FieldRow, I18nDict } from '../types/fieldModal';

type ChainedOptionRow = {
	option: string;
	chained: string;
	features: string;
	id: string;
};

function emptyRow(): ChainedOptionRow {
	return {
		option: '',
		chained: '',
		features: '',
		id: '',
	};
}

function normalizeChainedRows( raw: unknown ): ChainedOptionRow[] {
	if ( Array.isArray( raw ) ) {
		const rows = raw.map( ( item ) => {
			if (
				! item ||
				typeof item !== 'object' ||
				Array.isArray( item )
			) {
				return emptyRow();
			}
			const row = item as Record< string, unknown >;
			return {
				option: String( row.option ?? '' ),
				chained: String( row.chained ?? '' ),
				features: String( row.features ?? '' ),
				id: String( row.id ?? '' ),
			};
		} );
		return rows.length > 0 ? rows : [ emptyRow() ];
	}
	if ( raw && typeof raw === 'object' && ! Array.isArray( raw ) ) {
		const rows = Object.keys( raw as object ).map( ( key ) => {
			const item = ( raw as Record< string, unknown > )[ key ];
			if (
				! item ||
				typeof item !== 'object' ||
				Array.isArray( item )
			) {
				return emptyRow();
			}
			const row = item as Record< string, unknown >;
			return {
				option: String( row.option ?? '' ),
				chained: String( row.chained ?? '' ),
				features: String( row.features ?? '' ),
				id: String( row.id ?? '' ),
			};
		} );
		return rows.length > 0 ? rows : [ emptyRow() ];
	}
	return [ emptyRow() ];
}

function serializeChainedRows( rows: ChainedOptionRow[] ): ChainedOptionRow[] {
	const filtered = rows.filter(
		( row ) =>
			row.option.trim() !== '' ||
			row.chained.trim() !== '' ||
			row.features.trim() !== '' ||
			row.id.trim() !== ''
	);
	return filtered.length > 0 ? filtered : [];
}

export interface ChainedOptionsEditorProps {
	values: FieldRow;
	onChange: Dispatch< SetStateAction< FieldRow | null > >;
	i18n: I18nDict;
	title: string;
}

export function ChainedOptionsEditor( {
	values,
	onChange,
	i18n,
	title,
}: ChainedOptionsEditorProps ) {
	const rows = normalizeChainedRows( values.options );

	const setRows = ( next: ChainedOptionRow[] ) => {
		onChange( ( prev ) => {
			if ( ! prev ) {
				return prev;
			}
			return {
				...prev,
				options: serializeChainedRows( next ),
			};
		} );
	};

	const updateRow = ( index: number, patch: Partial< ChainedOptionRow > ) => {
		const next = rows.map( ( row, rowIndex ) =>
			rowIndex === index ? { ...row, ...patch } : row
		);
		setRows( next );
	};

	const addRow = () => {
		setRows( [ ...rows, emptyRow() ] );
	};

	const removeRow = ( index: number ) => {
		const next = rows.filter( ( _, rowIndex ) => rowIndex !== index );
		setRows( next.length > 0 ? next : [ emptyRow() ] );
	};

	const move = ( index: number, dir: -1 | 1 ) => {
		const swapIndex = index + dir;
		if ( swapIndex < 0 || swapIndex >= rows.length ) {
			return;
		}
		const next = [ ...rows ];
		const tmp = next[ index ];
		next[ index ] = next[ swapIndex ];
		next[ swapIndex ] = tmp;
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
							align="center"
							gap={ 2 }
							w="full"
							overflowX="auto"
							flexWrap="wrap"
						>
							<Input
								size="sm"
								flex="1 1 150px"
								minW={ 0 }
								placeholder={ i18n.pairedOptionLabel || 'Option' }
								value={ row.option }
								onValueChange={ ( e ) =>
									updateRow( index, {
										option: e.target.value,
									} )
								}
							/>
							<Input
								size="sm"
								flex="1 1 150px"
								minW={ 0 }
								placeholder="Chain"
								value={ row.chained }
								onValueChange={ ( e ) =>
									updateRow( index, {
										chained: e.target.value,
									} )
								}
							/>
							<Input
								size="sm"
								flex="1 1 150px"
								minW={ 0 }
								placeholder="Sub-Chain"
								value={ row.features }
								onValueChange={ ( e ) =>
									updateRow( index, {
										features: e.target.value,
									} )
								}
							/>
							<Input
								size="sm"
								flex="1 1 150px"
								minW={ 0 }
								placeholder="Option ID"
								value={ row.id }
								onValueChange={ ( e ) =>
									updateRow( index, {
										id: e.target.value,
									} )
								}
							/>
							<HStack gap={ 1 } flexShrink={ 0 }>
								<Button
									size="xs"
									variant="ghost"
									onClick={ () => move( index, -1 ) }
									disabled={ index === 0 }
								>
									↑
								</Button>
								<Button
									size="xs"
									variant="ghost"
									onClick={ () => move( index, 1 ) }
									disabled={ index === rows.length - 1 }
								>
									↓
								</Button>
								<IconButton
									size="xs"
									variant="ghost"
									colorPalette="red"
									onClick={ () => removeRow( index ) }
									aria-label={ i18n.pairedOptionsRemove || 'Remove' }
									title={ i18n.pairedOptionsRemove || 'Remove' }
								>
									<LuTrash2 />
								</IconButton>
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
