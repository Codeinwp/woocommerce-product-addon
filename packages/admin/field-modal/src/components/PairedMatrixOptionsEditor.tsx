/**
 * Classic paired-palettes / paired-pricematrix rows: option, price, label, id, isfixed.
 */
import { Box, Button, Text, VStack } from '@chakra-ui/react';
import type { Dispatch, SetStateAction } from 'react';
import type { FieldRow } from '../types/fieldModal';
import type { I18nDict } from '../types/fieldModal';
import { arrayMove } from '../utils/arrayMove';
import {
	type MatrixOptionRow,
	emptyMatrixRow,
	normalizeMatrixOptions,
} from '../utils/pairedMatrixData';
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

	const moveUp = ( index: number ) => setRows( arrayMove( rows, index, -1 ) );
	const moveDown = ( index: number ) =>
		setRows( arrayMove( rows, index, 1 ) );

	const toggleFixed = ( index: number, checked: boolean ) => {
		updateRow( index, { isfixed: checked ? 'on' : '' } );
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
					<MatrixOptionRowItem
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
						onToggleFixed={ toggleFixed }
					/>
				) ) }
				<Button size="sm" alignSelf="flex-start" onClick={ addRow }>
					{ i18n.pairedOptionsAddRow || 'Add option' }
				</Button>
			</VStack>
		</Box>
	);
}
