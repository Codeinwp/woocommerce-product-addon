import { Box, Flex, IconButton, Input, Table } from '@chakra-ui/react';
import { LuTrash2, LuX } from 'react-icons/lu';
import type { I18nDict } from '../../types/fieldModal';
import type { BulkQuantityRow } from '../../utils/bulkQuantityData';

export interface BulkQuantityTableProps {
	columns: string[];
	rows: BulkQuantityRow[];
	rangeKey: string;
	i18n: I18nDict;
	onUpdateCell: ( rowIndex: number, col: string, value: string ) => void;
	onRemoveRow: ( rowIndex: number ) => void;
	onRemoveColumn: ( col: string, columnIndex: number ) => void;
}

export function BulkQuantityTable( {
	columns,
	rows,
	rangeKey,
	i18n,
	onUpdateCell,
	onRemoveRow,
	onRemoveColumn,
}: BulkQuantityTableProps ) {
	return (
		<Box
			overflowX="auto"
			borderWidth="1px"
			borderColor="gray.200"
			borderRadius="md"
		>
			<Table.Root
				size="sm"
				variant="simple"
				borderCollapse="separate"
				borderSpacing="0"
			>
				<Table.Header>
					<Table.Row bg="#f6f7f7">
						{ columns.map( ( col, ci ) => (
							<Table.ColumnHeader
								key={ col }
								whiteSpace="nowrap"
								color="#1d2327"
								fontSize="xs"
								fontWeight="700"
								lineHeight="1.2"
								py={ 3 }
								px={ 3 }
								borderBottomWidth="1px"
								borderBottomColor="gray.200"
							>
								<Flex align="center" gap={ 2 }>
									<Box as="span">{ col }</Box>
									{ ci >= 2 ? (
										<IconButton
											size="xs"
											variant="ghost"
											colorPalette="red"
											minW="6"
											h="6"
											onClick={ () =>
												onRemoveColumn( col, ci )
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
											<LuX />
										</IconButton>
									) : null }
								</Flex>
							</Table.ColumnHeader>
						) ) }
						<Table.ColumnHeader
							w="56px"
							py={ 3 }
							px={ 3 }
							borderBottomWidth="1px"
							borderBottomColor="gray.200"
							color="#1d2327"
							fontSize="xs"
							fontWeight="700"
						>
							{ i18n.bulkQtyActions || '' }
						</Table.ColumnHeader>
					</Table.Row>
				</Table.Header>
				<Table.Body>
					{ rows.map( ( row, ri ) => (
						<Table.Row key={ ri }>
							{ columns.map( ( col ) => (
								<Table.Cell key={ col } p={ 3 }>
									<Input
										size="sm"
										value={ row[ col ] ?? '' }
										onValueChange={ ( e ) =>
											onUpdateCell(
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
								</Table.Cell>
							) ) }
							<Table.Cell p={ 3 } textAlign="center">
								<IconButton
									size="xs"
									variant="ghost"
									colorPalette="red"
									disabled={ rows.length < 2 }
									onClick={ () => onRemoveRow( ri ) }
									aria-label={
										i18n.pairedOptionsRemove || 'Remove'
									}
									title={
										i18n.pairedOptionsRemove || 'Remove'
									}
								>
									<LuTrash2 />
								</IconButton>
							</Table.Cell>
						</Table.Row>
					) ) }
				</Table.Body>
			</Table.Root>
		</Box>
	);
}
