import { Box, Button, IconButton, Input, Table } from '@chakra-ui/react';
import { LuTrash2 } from 'react-icons/lu';
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
		<Box overflowX="auto">
			<Table.Root size="sm" variant="simple">
				<Table.Header>
					<Table.Row>
						{ columns.map( ( col, ci ) => (
							<Table.ColumnHeader key={ col } whiteSpace="nowrap">
								{ col }
								{ ci >= 2 ? (
									<Button
										ml={ 1 }
										size="xs"
										variant="ghost"
										colorPalette="red"
										onClick={ () =>
											onRemoveColumn( col, ci )
										}
									>
										×
									</Button>
								) : null }
							</Table.ColumnHeader>
						) ) }
						<Table.ColumnHeader>
							{ i18n.bulkQtyActions || '' }
						</Table.ColumnHeader>
					</Table.Row>
				</Table.Header>
				<Table.Body>
					{ rows.map( ( row, ri ) => (
						<Table.Row key={ ri }>
							{ columns.map( ( col ) => (
								<Table.Cell key={ col }>
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
							<Table.Cell>
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
