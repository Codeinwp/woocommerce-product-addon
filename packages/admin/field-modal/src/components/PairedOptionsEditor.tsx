/**
 * MVP editor for schema `options.type === 'paired'` (select, radio, etc.).
 */
import { Box, Button, Text, VStack } from '@chakra-ui/react';
import type { Dispatch, SetStateAction } from 'react';
import type { FieldRow } from '../types/fieldModal';
import type { I18nDict } from '../types/fieldModal';
import { arrayMove } from '../utils/arrayMove';
import {
	type PairedOptionRow,
	type PairedOptionsVariant,
	emptyPairedOptionRow,
	normalizePairedOptionsArray,
} from '../utils/pairedOptionsData';
import { PairedOptionRowItem } from './paired-options/PairedOptionRowItem';

export type {
	PairedOptionRow,
	PairedOptionsVariant,
} from '../utils/pairedOptionsData';

export interface PairedOptionsEditorProps {
	values: FieldRow;
	onChange: Dispatch< SetStateAction< FieldRow | null > >;
	i18n: I18nDict;
	title: string;
	/** `checkbox` adds discount + tooltip columns (classic paired UI). */
	variant?: PairedOptionsVariant;
}

export function PairedOptionsEditor( {
	values,
	onChange,
	i18n,
	title,
	variant = 'select',
}: PairedOptionsEditorProps ) {
	const rows = normalizePairedOptionsArray( values.options );

	const setRows = ( next: PairedOptionRow[] ) => {
		onChange( ( prev ) => {
			if ( ! prev ) {
				return prev;
			}
			return { ...prev, options: next };
		} );
	};

	const updateRow = (
		index: number,
		patch: Partial< PairedOptionRow >
	) => {
		setRows(
			rows.map( ( r, i ) => ( i === index ? { ...r, ...patch } : r ) )
		);
	};

	const addRow = () => {
		setRows( [ ...rows, emptyPairedOptionRow( variant ) ] );
	};

	const removeRow = ( index: number ) => {
		setRows( rows.filter( ( _, i ) => i !== index ) );
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
					<PairedOptionRowItem
						key={ index }
						row={ row }
						index={ index }
						isFirst={ index === 0 }
						isLast={ index === rows.length - 1 }
						variant={ variant }
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
