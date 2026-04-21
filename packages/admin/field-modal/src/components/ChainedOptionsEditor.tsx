/**
 * Inline editor for chained option rows.
 */
import { Box, Button, Text, VStack } from '@chakra-ui/react';
import type { Dispatch, SetStateAction } from 'react';
import type { FieldRow, I18nDict } from '../types/fieldModal';
import { arrayMove } from '../utils/arrayMove';
import {
	type ChainedOptionRow,
	emptyChainedRow,
	normalizeChainedRows,
	serializeChainedRows,
} from '../utils/chainedOptionsData';
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

	const updateRow = (
		index: number,
		patch: Partial< ChainedOptionRow >
	) => {
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

	const moveUp = ( index: number ) => setRows( arrayMove( rows, index, -1 ) );
	const moveDown = ( index: number ) => setRows( arrayMove( rows, index, 1 ) );

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
					<ChainedOptionRowItem
						key={ index }
						row={ row }
						index={ index }
						isFirst={ index === 0 }
						isLast={ index === rows.length - 1 }
						i18n={ i18n }
						onPatch={ updateRow }
						onMoveUp={ moveUp }
						onMoveDown={ moveDown }
						onRemove={ removeRow }
					/>
				) ) }
				<Button size="sm" alignSelf="flex-start" onClick={ addRow }>
					{ i18n.pairedOptionsAddRow || 'Add option' }
				</Button>
			</VStack>
		</Box>
	);
}
