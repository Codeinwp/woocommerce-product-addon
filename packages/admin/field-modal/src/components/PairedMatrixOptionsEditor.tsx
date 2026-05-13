/**
 * Classic paired-palettes / paired-pricematrix rows: option, price, label, id, isfixed.
 */
import { Box, Button, Icon, Text, VStack } from '@chakra-ui/react';
import type { Dispatch, SetStateAction } from 'react';
import { LuListChecks, LuPlus } from 'react-icons/lu';
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
	/**
	 * `'color'` adds a color-picker swatch next to the option input. The text input
	 * stays free-form so `black` / `rgba(...)` / gradients still pass through.
	 */
	optionVariant?: 'text' | 'color';
}

export function PairedMatrixOptionsEditor( {
	values,
	onChange,
	i18n,
	title,
	optionVariant = 'text',
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

	const mutateRows = (
		mutator: ( current: MatrixOptionRow[] ) => MatrixOptionRow[]
	) => {
		onChange( ( prev ) => {
			if ( ! prev ) {
				return prev;
			}
			const current = normalizeMatrixOptions( prev.options );
			return { ...prev, options: mutator( current ) };
		} );
	};

	const updateRow = ( index: number, patch: Partial< MatrixOptionRow > ) => {
		mutateRows( ( current ) =>
			current.map( ( r, i ) => ( i === index ? { ...r, ...patch } : r ) )
		);
	};

	const addRow = () => {
		mutateRows( ( current ) => [ ...current, emptyMatrixRow() ] );
	};

	const removeRow = ( index: number ) => {
		mutateRows( ( current ) => current.filter( ( _, i ) => i !== index ) );
	};

	const toggleFixed = ( index: number, checked: boolean ) => {
		updateRow( index, { isfixed: checked ? 'on' : '' } );
	};

	const dragHandlers = useDraggableRows( rows, setRows );
	const isEmpty = rows.length === 0;

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
			{ isEmpty ? (
				<VStack
					align="center"
					gap={ 1.5 }
					py={ 4 }
					px={ 4 }
					borderWidth="1px"
					borderStyle="dashed"
					borderColor="gray.300"
					borderRadius="md"
					bg="gray.50"
				>
					<Icon
						as={ LuListChecks }
						boxSize={ 5 }
						color="gray.400"
						aria-hidden
					/>
					<Text
						fontWeight="semibold"
						fontSize="sm"
						color="gray.700"
						m={ 0 }
					>
						{ i18n.pairedOptionsEmptyTitle || 'No options yet' }
					</Text>
					<Text
						fontSize="xs"
						color="gray.500"
						textAlign="center"
						maxW="xs"
						m={ 0 }
					>
						{ i18n.pairedOptionsEmptyDescription ||
							'Add at least one option for customers to choose from.' }
					</Text>
					<Button
						size="xs"
						colorPalette="blue"
						onClick={ addRow }
						mt={ 0.5 }
					>
						<Icon as={ LuPlus } boxSize={ 3 } mr={ 1 } />
						{ i18n.pairedOptionsAddFirst ||
							'Add your first option' }
					</Button>
				</VStack>
			) : (
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
							optionVariant={ optionVariant }
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
			) }
		</Box>
	);
}
