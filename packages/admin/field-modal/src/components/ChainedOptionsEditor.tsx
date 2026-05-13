/**
 * Inline editor for chained option rows.
 */
import { Box, Button, HStack, Icon, Text, VStack } from '@chakra-ui/react';
import type { Dispatch, SetStateAction } from 'react';
import { LuBookOpen, LuListChecks, LuPlus } from 'react-icons/lu';
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
	link?: string;
}

export function ChainedOptionsEditor( {
	values,
	onChange,
	i18n,
	title,
	link,
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
		setRows( rows.filter( ( _, rowIndex ) => rowIndex !== index ) );
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
			<HStack justify="space-between" align="center" gap={ 2 } mb={ 3 }>
				<Text fontWeight="semibold" fontSize="sm">
					{ title }
				</Text>
				{ link ? (
					<HStack gap={ 1 } align="center" fontSize="xs">
						<Icon
							as={ LuBookOpen }
							boxSize={ 3.5 }
							color="blue.600"
							aria-hidden
						/>
						<Box
							color="gray.600"
							css={ {
								'& a': {
									color: 'var(--chakra-colors-blue-600)',
									textDecoration: 'underline',
								},
								'& a:hover': {
									color: 'var(--chakra-colors-blue-700)',
								},
							} }
							dangerouslySetInnerHTML={ { __html: link } }
						/>
					</HStack>
				) : null }
			</HStack>
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
			) }
		</Box>
	);
}
