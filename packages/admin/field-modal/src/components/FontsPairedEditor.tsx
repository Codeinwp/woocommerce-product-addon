/**
 * Inline editor for Fonts Picker option rows.
 */
import { Box, Button, Icon, Text, VStack } from '@chakra-ui/react';
import type { Dispatch, SetStateAction } from 'react';
import { LuPlus } from 'react-icons/lu';
import type { FieldRow, I18nDict } from '../types/fieldModal';
import {
	type FontOptionRow,
	emptyFontRow,
	normalizeFontRows,
	serializeFontRows,
} from '../utils/fontsPairedData';
import { useDraggableRows } from './draggable-options/DraggableOptionRow';
import { FontOptionRowItem } from './fonts-paired/FontOptionRowItem';

export interface FontsPairedEditorProps {
	values: FieldRow;
	onChange: Dispatch< SetStateAction< FieldRow | null > >;
	i18n: I18nDict;
	title: string;
}

export function FontsPairedEditor( {
	values,
	onChange,
	i18n,
	title,
}: FontsPairedEditorProps ) {
	const rows = normalizeFontRows( values.options );

	const setRows = ( next: FontOptionRow[] ) => {
		onChange( ( prev ) => {
			if ( ! prev ) {
				return prev;
			}
			return {
				...prev,
				options: serializeFontRows( next ),
			};
		} );
	};

	const updateRow = ( index: number, patch: Partial< FontOptionRow > ) => {
		setRows(
			rows.map( ( row, rowIndex ) =>
				rowIndex === index ? { ...row, ...patch } : row
			)
		);
	};

	const addRow = () => {
		setRows( [ ...rows, emptyFontRow() ] );
	};

	const removeRow = ( index: number ) => {
		const next = rows.filter( ( _, rowIndex ) => rowIndex !== index );
		setRows( next.length > 0 ? next : [ emptyFontRow() ] );
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
					<FontOptionRowItem
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
					{ i18n.fontsAddRow || 'Add font family' }
				</Button>
			</VStack>
		</Box>
	);
}
