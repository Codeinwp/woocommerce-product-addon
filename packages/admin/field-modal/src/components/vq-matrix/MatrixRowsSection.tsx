import { Box, Button, Icon, Input, Text, VStack } from '@chakra-ui/react';
import { LuPlus } from 'react-icons/lu';
import type { I18nDict } from '../../types/fieldModal';
import { type MatrixRowRow, emptyRowRow } from '../../utils/vqMatrixData';
import {
	DraggableOptionRow,
	useDraggableRows,
} from '../draggable-options/DraggableOptionRow';

export interface MatrixRowsSectionProps {
	rows: MatrixRowRow[];
	i18n: I18nDict;
	onChange: ( rows: MatrixRowRow[] ) => void;
}

function isMatrixRowEmpty( row: MatrixRowRow ): boolean {
	return (
		row.option.trim() === '' &&
		row.img_id.trim() === '' &&
		row.option_id.trim() === ''
	);
}

export function MatrixRowsSection( {
	rows,
	i18n,
	onChange,
}: MatrixRowsSectionProps ) {
	const updateRow = ( index: number, patch: Partial< MatrixRowRow > ) => {
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
				{ i18n.vqmatrixRowsTitle || 'Matrix Rows' }
			</Text>
			<VStack align="stretch" gap={ 2 }>
				{ rows.map( ( row, index ) => (
					<DraggableOptionRow
						key={ `row-${ index }` }
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
						hideRemove={ isMatrixRowEmpty( row ) }
						flexWrap="wrap"
					>
						<Input
							size="sm"
							flex="1 1 180px"
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
							flex="1 1 180px"
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
							flex="1 1 180px"
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
					onClick={ () => onChange( [ ...rows, emptyRowRow() ] ) }
				>
					<Icon as={ LuPlus } boxSize={ 3.5 } mr={ 1 } />
					{ i18n.vqmatrixAddRow || 'Add row option' }
				</Button>
			</VStack>
		</Box>
	);
}
