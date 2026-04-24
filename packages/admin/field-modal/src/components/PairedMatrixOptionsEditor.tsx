/**
 * Classic paired-palettes / paired-pricematrix rows: option, price, label, id, isfixed.
 */
import { Box, Button, Icon, Text, VStack } from '@chakra-ui/react';
import type { Dispatch, SetStateAction } from 'react';
import { LuPlus } from 'react-icons/lu';
import type { FieldRow } from '../types/fieldModal';
import type { I18nDict } from '../types/fieldModal';
import {
	type MatrixOptionRow,
	emptyMatrixRow,
	normalizeMatrixOptions,
} from '../utils/pairedMatrixData';
import { useDraggableRows } from './draggable-options/DraggableOptionRow';
import { MatrixOptionRowItem } from './paired-matrix/MatrixOptionRowItem';

export type { MatrixOptionRow } from '../utils/pairedMatrixData';

export interface PairedMatrixOptionsEditorProps {
	values: FieldRow;
	onChange: Dispatch< SetStateAction< FieldRow | null > >;
	i18n: I18nDict;
	title: string;
}

export function PairedMatrixOptionsEditor( {
	values,
	onChange,
	i18n,
	title,
}: PairedMatrixOptionsEditorProps ) {
	const rows = normalizeMatrixOptions( values.options );

	const setRows = ( next: MatrixOptionRow[] ) => {
		onChange( ( prev ) => {
			if ( ! prev ) {
				return prev;
			}
			return { ...prev, options: next };
		} );
	};

	const updateRow = ( index: number, patch: Partial< MatrixOptionRow > ) => {
		setRows(
			rows.map( ( r, i ) => ( i === index ? { ...r, ...patch } : r ) )
		);
	};

	const addRow = () => {
		setRows( [ ...rows, emptyMatrixRow() ] );
	};

	const removeRow = ( index: number ) => {
		setRows( rows.filter( ( _, i ) => i !== index ) );
	};

	const toggleFixed = ( index: number, checked: boolean ) => {
		updateRow( index, { isfixed: checked ? 'on' : '' } );
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
					<MatrixOptionRowItem
						key={ index }
						row={ row }
						index={ index }
						i18n={ i18n }
						onPatch={ updateRow }
						onMoveUp={ dragHandlers.onMoveUp }
						onMoveDown={ dragHandlers.onMoveDown }
						onRemove={ removeRow }
						onToggleFixed={ toggleFixed }
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
