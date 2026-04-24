import { Box, Button, Icon, Input, Text, VStack } from '@chakra-ui/react';
import { LuPlus } from 'react-icons/lu';
import type { I18nDict } from '../../types/fieldModal';
import { type MatrixColumnRow, emptyColumnRow } from '../../utils/vqMatrixData';
import {
	DraggableOptionRow,
	useDraggableRows,
} from '../draggable-options/DraggableOptionRow';

export interface ColumnRowsSectionProps {
	rows: MatrixColumnRow[];
	i18n: I18nDict;
	onChange: ( rows: MatrixColumnRow[] ) => void;
}

function isColumnRowEmpty( row: MatrixColumnRow ): boolean {
	return (
		row.option.trim() === '' &&
		row.img_id.trim() === '' &&
		row.price.trim() === '' &&
		row.min.trim() === '' &&
		row.max.trim() === '' &&
		row.option_id.trim() === ''
	);
}

export function ColumnRowsSection( {
	rows,
	i18n,
	onChange,
}: ColumnRowsSectionProps ) {
	const updateRow = ( index: number, patch: Partial< MatrixColumnRow > ) => {
		onChange(
			rows.map( ( row, rowIndex ) =>
				rowIndex === index ? { ...row, ...patch } : row
			)
		);
	};
	const dragHandlers = useDraggableRows( rows, onChange );

	return (
		<Box>
			<Text fontSize="xs" fontWeight="700" color="gray.600" mb={ 2 }>
				{ i18n.vqmatrixColumnsTitle || 'Priced Options' }
			</Text>
			<VStack align="stretch" gap={ 2 }>
				{ rows.map( ( row, index ) => (
					<DraggableOptionRow
						key={ `column-${ index }` }
						index={ index }
						dragIndex={ dragHandlers.dragIndex }
						onDragStart={ dragHandlers.onDragStart }
						onDragEnd={ dragHandlers.onDragEnd }
						onDrop={ dragHandlers.onDrop }
						onMoveUp={ dragHandlers.onMoveUp }
						onMoveDown={ dragHandlers.onMoveDown }
						onRemove={ ( rowIndex ) =>
							onChange(
								rows.filter(
									( _, currentIndex ) =>
										currentIndex !== rowIndex
								)
							)
						}
						dragLabel={
							i18n.pairedOptionsDragHandle || 'Drag to reorder'
						}
						removeLabel={ i18n.pairedOptionsRemove || 'Remove' }
						hideRemove={ isColumnRowEmpty( row ) }
						flexWrap="wrap"
					>
						<Input
							size="sm"
							flex="1 1 140px"
							minW={ 0 }
							placeholder="Option"
							value={ row.option }
							onChange={ ( e ) =>
								updateRow( index, {
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
							onChange={ ( e ) =>
								updateRow( index, {
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
							onChange={ ( e ) =>
								updateRow( index, {
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
							onChange={ ( e ) =>
								updateRow( index, { min: e.target.value } )
							}
						/>
						<Input
							size="sm"
							flex="1 1 110px"
							minW={ 0 }
							placeholder="Max. Qty"
							value={ row.max }
							onChange={ ( e ) =>
								updateRow( index, { max: e.target.value } )
							}
						/>
						<Input
							size="sm"
							flex="1 1 140px"
							minW={ 0 }
							placeholder="Option ID"
							value={ row.option_id }
							onChange={ ( e ) =>
								updateRow( index, {
									option_id: e.target.value,
								} )
							}
						/>
					</DraggableOptionRow>
				) ) }
				<Button
					size="sm"
					variant="outline"
					borderStyle="dashed"
					color="gray.600"
					width="full"
					mt={ 1 }
					onClick={ () => onChange( [ ...rows, emptyColumnRow() ] ) }
				>
					<Icon as={ LuPlus } boxSize={ 3.5 } mr={ 1 } />
					{ i18n.pairedOptionsAddRow || 'Add option' }
				</Button>
			</VStack>
		</Box>
	);
}
