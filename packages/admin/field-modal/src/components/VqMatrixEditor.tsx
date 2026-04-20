/**
 * Inline editor for Variation Matrix columns and rows.
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

type MatrixColumnRow = {
	option: string;
	img_id: string;
	price: string;
	min: string;
	max: string;
	option_id: string;
};

type MatrixRowRow = {
	option: string;
	img_id: string;
	option_id: string;
};

function emptyColumnRow(): MatrixColumnRow {
	return {
		option: '',
		img_id: '',
		price: '',
		min: '',
		max: '',
		option_id: '',
	};
}

function emptyRowRow(): MatrixRowRow {
	return {
		option: '',
		img_id: '',
		option_id: '',
	};
}

function normalizeColumnRows( raw: unknown ): MatrixColumnRow[] {
	if ( Array.isArray( raw ) ) {
		const rows = raw.map( ( item ) => {
			if (
				! item ||
				typeof item !== 'object' ||
				Array.isArray( item )
			) {
				return emptyColumnRow();
			}
			const row = item as Record< string, unknown >;
			return {
				option: String( row.option ?? '' ),
				img_id: String( row.img_id ?? '' ),
				price: String( row.price ?? '' ),
				min: String( row.min ?? '' ),
				max: String( row.max ?? '' ),
				option_id: String( row.option_id ?? '' ),
			};
		} );
		return rows.length > 0 ? rows : [ emptyColumnRow() ];
	}
	if ( raw && typeof raw === 'object' && ! Array.isArray( raw ) ) {
		const rows = Object.keys( raw as object ).map( ( key ) => {
			const item = ( raw as Record< string, unknown > )[ key ];
			if (
				! item ||
				typeof item !== 'object' ||
				Array.isArray( item )
			) {
				return emptyColumnRow();
			}
			const row = item as Record< string, unknown >;
			return {
				option: String( row.option ?? '' ),
				img_id: String( row.img_id ?? '' ),
				price: String( row.price ?? '' ),
				min: String( row.min ?? '' ),
				max: String( row.max ?? '' ),
				option_id: String( row.option_id ?? '' ),
			};
		} );
		return rows.length > 0 ? rows : [ emptyColumnRow() ];
	}
	return [ emptyColumnRow() ];
}

function normalizeRowRows( raw: unknown ): MatrixRowRow[] {
	if ( Array.isArray( raw ) ) {
		const rows = raw.map( ( item ) => {
			if (
				! item ||
				typeof item !== 'object' ||
				Array.isArray( item )
			) {
				return emptyRowRow();
			}
			const row = item as Record< string, unknown >;
			return {
				option: String( row.option ?? '' ),
				img_id: String( row.img_id ?? '' ),
				option_id: String( row.option_id ?? '' ),
			};
		} );
		return rows.length > 0 ? rows : [ emptyRowRow() ];
	}
	if ( raw && typeof raw === 'object' && ! Array.isArray( raw ) ) {
		const rows = Object.keys( raw as object ).map( ( key ) => {
			const item = ( raw as Record< string, unknown > )[ key ];
			if (
				! item ||
				typeof item !== 'object' ||
				Array.isArray( item )
			) {
				return emptyRowRow();
			}
			const row = item as Record< string, unknown >;
			return {
				option: String( row.option ?? '' ),
				img_id: String( row.img_id ?? '' ),
				option_id: String( row.option_id ?? '' ),
			};
		} );
		return rows.length > 0 ? rows : [ emptyRowRow() ];
	}
	return [ emptyRowRow() ];
}

function serializeColumnRows( rows: MatrixColumnRow[] ): MatrixColumnRow[] {
	const filtered = rows.filter(
		( row ) =>
			row.option.trim() !== '' ||
			row.img_id.trim() !== '' ||
			row.price.trim() !== '' ||
			row.min.trim() !== '' ||
			row.max.trim() !== '' ||
			row.option_id.trim() !== ''
	);
	return filtered.length > 0 ? filtered : [];
}

function serializeRowRows( rows: MatrixRowRow[] ): MatrixRowRow[] {
	const filtered = rows.filter(
		( row ) =>
			row.option.trim() !== '' ||
			row.img_id.trim() !== '' ||
			row.option_id.trim() !== ''
	);
	return filtered.length > 0 ? filtered : [];
}

export interface VqMatrixEditorProps {
	values: FieldRow;
	onChange: Dispatch< SetStateAction< FieldRow | null > >;
	i18n: I18nDict;
	title: string;
}

export function VqMatrixEditor( {
	values,
	onChange,
	i18n,
	title,
}: VqMatrixEditorProps ) {
	const columnRows = normalizeColumnRows( values.options );
	const rowRows = normalizeRowRows( values.row_options );

	const setColumnRows = ( next: MatrixColumnRow[] ) => {
		onChange( ( prev ) => {
			if ( ! prev ) {
				return prev;
			}
			return {
				...prev,
				options: serializeColumnRows( next ),
			};
		} );
	};

	const setRowRows = ( next: MatrixRowRow[] ) => {
		onChange( ( prev ) => {
			if ( ! prev ) {
				return prev;
			}
			return {
				...prev,
				row_options: serializeRowRows( next ),
			};
		} );
	};

	const updateColumnRow = (
		index: number,
		patch: Partial< MatrixColumnRow >
	) => {
		const next = columnRows.map( ( row, rowIndex ) =>
			rowIndex === index ? { ...row, ...patch } : row
		);
		setColumnRows( next );
	};

	const updateRowRow = (
		index: number,
		patch: Partial< MatrixRowRow >
	) => {
		const next = rowRows.map( ( row, rowIndex ) =>
			rowIndex === index ? { ...row, ...patch } : row
		);
		setRowRows( next );
	};

	const move = <T,>( rows: T[], index: number, dir: -1 | 1 ): T[] => {
		const swapIndex = index + dir;
		if ( swapIndex < 0 || swapIndex >= rows.length ) {
			return rows;
		}
		const next = [ ...rows ];
		const tmp = next[ index ];
		next[ index ] = next[ swapIndex ];
		next[ swapIndex ] = tmp;
		return next;
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
			<VStack align="stretch" gap={ 4 }>
				<Box>
					<Text fontSize="xs" fontWeight="700" color="gray.600" mb={ 2 }>
						{ i18n.vqmatrixColumnsTitle || 'Priced Options' }
					</Text>
					<VStack align="stretch" gap={ 3 }>
						{ columnRows.map( ( row, index ) => (
							<Box
								key={ `column-${ index }` }
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
										flex="1 1 140px"
										minW={ 0 }
										placeholder="Option"
										value={ row.option }
										onValueChange={ ( e ) =>
											updateColumnRow( index, {
												option: e.target.value,
											} )
										}
									/>
									<Input
										size="sm"
										flex="1 1 140px"
										minW={ 0 }
										placeholder="Image ID (URL)"
										value={ row.img_id }
										onValueChange={ ( e ) =>
											updateColumnRow( index, {
												img_id: e.target.value,
											} )
										}
									/>
									<Input
										size="sm"
										flex="1 1 110px"
										minW={ 0 }
										placeholder="Price"
										value={ row.price }
										onValueChange={ ( e ) =>
											updateColumnRow( index, {
												price: e.target.value,
											} )
										}
									/>
									<Input
										size="sm"
										flex="1 1 110px"
										minW={ 0 }
										placeholder="Min. Qty"
										value={ row.min }
										onValueChange={ ( e ) =>
											updateColumnRow( index, {
												min: e.target.value,
											} )
										}
									/>
									<Input
										size="sm"
										flex="1 1 110px"
										minW={ 0 }
										placeholder="Max. Qty"
										value={ row.max }
										onValueChange={ ( e ) =>
											updateColumnRow( index, {
												max: e.target.value,
											} )
										}
									/>
									<Input
										size="sm"
										flex="1 1 140px"
										minW={ 0 }
										placeholder="Option ID"
										value={ row.option_id }
										onValueChange={ ( e ) =>
											updateColumnRow( index, {
												option_id: e.target.value,
											} )
										}
									/>
									<HStack gap={ 1 } flexShrink={ 0 }>
										<Button
											size="xs"
											variant="ghost"
											onClick={ () =>
												setColumnRows(
													move(
														columnRows,
														index,
														-1
													)
												)
											}
											disabled={ index === 0 }
										>
											↑
										</Button>
										<Button
											size="xs"
											variant="ghost"
											onClick={ () =>
												setColumnRows(
													move(
														columnRows,
														index,
														1
													)
												)
											}
											disabled={
												index === columnRows.length - 1
											}
										>
											↓
										</Button>
										<IconButton
											size="xs"
											variant="ghost"
											colorPalette="red"
											onClick={ () =>
												setColumnRows(
													columnRows.filter(
														( _, rowIndex ) =>
															rowIndex !== index
													)
												)
											}
											aria-label={
												i18n.pairedOptionsRemove ||
												'Remove'
											}
											title={
												i18n.pairedOptionsRemove ||
												'Remove'
											}
										>
											<LuTrash2 />
										</IconButton>
									</HStack>
								</HStack>
							</Box>
						) ) }
						<Button
							size="sm"
							alignSelf="flex-start"
							onClick={ () =>
								setColumnRows( [
									...columnRows,
									emptyColumnRow(),
								] )
							}
						>
							{ i18n.pairedOptionsAddRow || 'Add option' }
						</Button>
					</VStack>
				</Box>
				<Box>
					<Text fontSize="xs" fontWeight="700" color="gray.600" mb={ 2 }>
						{ i18n.vqmatrixRowsTitle || 'Matrix Rows' }
					</Text>
					<VStack align="stretch" gap={ 3 }>
						{ rowRows.map( ( row, index ) => (
							<Box
								key={ `row-${ index }` }
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
										flex="1 1 180px"
										minW={ 0 }
										placeholder="Option"
										value={ row.option }
										onValueChange={ ( e ) =>
											updateRowRow( index, {
												option: e.target.value,
											} )
										}
									/>
									<Input
										size="sm"
										flex="1 1 180px"
										minW={ 0 }
										placeholder="Image ID (URL)"
										value={ row.img_id }
										onValueChange={ ( e ) =>
											updateRowRow( index, {
												img_id: e.target.value,
											} )
										}
									/>
									<Input
										size="sm"
										flex="1 1 180px"
										minW={ 0 }
										placeholder="Option ID"
										value={ row.option_id }
										onValueChange={ ( e ) =>
											updateRowRow( index, {
												option_id: e.target.value,
											} )
										}
									/>
									<HStack gap={ 1 } flexShrink={ 0 }>
										<Button
											size="xs"
											variant="ghost"
											onClick={ () =>
												setRowRows(
													move( rowRows, index, -1 )
												)
											}
											disabled={ index === 0 }
										>
											↑
										</Button>
										<Button
											size="xs"
											variant="ghost"
											onClick={ () =>
												setRowRows(
													move( rowRows, index, 1 )
												)
											}
											disabled={ index === rowRows.length - 1 }
										>
											↓
										</Button>
										<IconButton
											size="xs"
											variant="ghost"
											colorPalette="red"
											onClick={ () =>
												setRowRows(
													rowRows.filter(
														( _, rowIndex ) =>
															rowIndex !== index
													)
												)
											}
											aria-label={
												i18n.pairedOptionsRemove ||
												'Remove'
											}
											title={
												i18n.pairedOptionsRemove ||
												'Remove'
											}
										>
											<LuTrash2 />
										</IconButton>
									</HStack>
								</HStack>
							</Box>
						) ) }
						<Button
							size="sm"
							alignSelf="flex-start"
							onClick={ () =>
								setRowRows( [ ...rowRows, emptyRowRow() ] )
							}
						>
							{ i18n.vqmatrixAddRow || 'Add row option' }
						</Button>
					</VStack>
				</Box>
			</VStack>
		</Box>
	);
}
