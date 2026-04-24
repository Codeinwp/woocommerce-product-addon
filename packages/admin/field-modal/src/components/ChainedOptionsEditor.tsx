/**
 * Inline editor for chained option rows.
 */
import { Box, Button, Icon, Text, VStack } from '@chakra-ui/react';
import type { Dispatch, SetStateAction } from 'react';
import { LuPlus } from 'react-icons/lu';
import type { FieldRow, I18nDict } from '../types/fieldModal';
import {
	type ChainedOptionRow,
	emptyChainedRow,
	normalizeChainedRows,
	serializeChainedRows,
} from '../utils/chainedOptionsData';
import { useDraggableRows } from './draggable-options/DraggableOptionRow';
import { ChainedOptionRowItem } from './chained-options/ChainedOptionRowItem';

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
		setRows(
			rows.map( ( row, rowIndex ) =>
				rowIndex === index ? { ...row, ...patch } : row
			)
		);
	};

	const addRow = () => {
		setRows( [ ...rows, emptyChainedRow() ] );
	};

	const removeRow = ( index: number ) => {
		const next = rows.filter( ( _, rowIndex ) => rowIndex !== index );
		setRows( next.length > 0 ? next : [ emptyChainedRow() ] );
	};

	const dragHandlers = useDraggableRows( rows, setRows );

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
			<VStack align="stretch" gap={ 2 }>
				{ rows.map( ( row, index ) => (
					<ChainedOptionRowItem
						key={ index }
						row={ row }
						index={ index }
						i18n={ i18n }
						onPatch={ updateRow }
						onMoveUp={ dragHandlers.onMoveUp }
						onMoveDown={ dragHandlers.onMoveDown }
						onRemove={ removeRow }
						dragIndex={ dragHandlers.dragIndex }
						onDragStart={ dragHandlers.onDragStart }
						onDragEnd={ dragHandlers.onDragEnd }
						onDrop={ dragHandlers.onDrop }
					/>
				) ) }
				<Button
					size="sm"
					variant="outline"
					borderStyle="dashed"
					color="gray.600"
					width="full"
					mt={ 1 }
					onClick={ addRow }
				>
					<Icon as={ LuPlus } boxSize={ 3.5 } mr={ 1 } />
					{ i18n.pairedOptionsAddRow || 'Add option' }
				</Button>
			</VStack>
		</Box>
	);
}
