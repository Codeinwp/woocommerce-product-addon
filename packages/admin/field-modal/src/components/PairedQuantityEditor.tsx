/**
 * Variation quantity matrix rows (paired-quantity schema type).
 */
import { Box, Button, VStack, Field, Icon, Text } from '@chakra-ui/react';
import type { Dispatch, SetStateAction } from 'react';
import { LuListChecks, LuPlus } from 'react-icons/lu';
import type { FieldRow, I18nDict } from '../types/fieldModal';
import {
	type QuantityOptionRow,
	emptyQuantityRow,
	normalizePairedQuantityOptions,
	serializePairedQuantityOptions,
} from '../utils/pairedQuantityData';
import { useDraggableRows } from './draggable-options/DraggableOptionRow';
import { QuantityRowItem } from './paired-quantity/QuantityRowItem';

export type { QuantityOptionRow } from '../utils/pairedQuantityData';
export {
	normalizePairedQuantityOptions,
	serializePairedQuantityOptions,
} from '../utils/pairedQuantityData';

const labelProps = {
	fontSize: '13px',
	fontWeight: '600',
	color: 'gray.800',
	mb: 0.5,
};

const helperTextProps = {
	mt: 1,
	fontSize: 'xs',
	color: 'gray.600',
	lineHeight: '1.45',
};

export interface PairedQuantityEditorProps {
	fieldKey: string;
	title: string;
	description: string;
	values: FieldRow;
	onChange: Dispatch< SetStateAction< FieldRow | null > >;
	i18n: I18nDict;
}

export function PairedQuantityEditor( {
	fieldKey,
	title,
	description,
	values,
	onChange,
	i18n,
}: PairedQuantityEditorProps ) {
	const raw = values[ fieldKey ];
	const rows = normalizePairedQuantityOptions( raw );

	const setRows = ( next: QuantityOptionRow[] ) => {
		onChange( ( prev ) => {
			if ( ! prev ) {
				return prev;
			}
			return {
				...prev,
				[ fieldKey ]: serializePairedQuantityOptions( next ),
			};
		} );
	};

	const updateRow = (
		index: number,
		patch: Partial< QuantityOptionRow >
	) => {
		setRows(
			rows.map( ( r, i ) => ( i === index ? { ...r, ...patch } : r ) )
		);
	};

	const addRow = () => {
		setRows( [ ...rows, emptyQuantityRow() ] );
	};

	const removeRow = ( index: number ) => {
		setRows( rows.filter( ( _, i ) => i !== index ) );
	};

	const placeholders = {
		option:
			i18n.quantityPairedOptionPlaceholder ||
			i18n.pairedOptionLabel ||
			'Option',
		price:
			i18n.quantityPairedPricePlaceholder ||
			i18n.pairedOptionPrice ||
			'Price',
		weight: i18n.quantityPairedWeightPlaceholder || 'Weight',
		defaultQty: i18n.quantityPairedDefaultPlaceholder || 'Default qty',
		min: i18n.quantityPairedMinPlaceholder || 'Min qty',
		max: i18n.quantityPairedMaxPlaceholder || 'Max qty',
		stock: i18n.quantityPairedStockPlaceholder || 'Stock',
	};
	const dragHandlers = useDraggableRows( rows, setRows );
	const isEmpty = rows.length === 0;

	return (
		<Field.Root w="full">
			<Field.Label { ...labelProps }>{ title }</Field.Label>
			<Box
				w="full"
				borderWidth="1px"
				borderColor="gray.200"
				borderRadius="md"
				p={ 3 }
				bg="white"
			>
				<VStack align="stretch" gap={ 2 } w="full">
					{ isEmpty ? (
						<VStack
							align="center"
							w="full"
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
								{ i18n.pairedOptionsEmptyTitle ||
									'No options yet' }
							</Text>
							<Text
								fontSize="xs"
								color="gray.500"
								textAlign="center"
								maxW="xs"
								m={ 0 }
							>
								{ i18n.pairedOptionsEmptyDescription ||
									'Options are the choices your customer picks from.' }
							</Text>
						</VStack>
					) : (
						rows.map( ( row, index ) => (
							<QuantityRowItem
								key={ index }
								row={ row }
								index={ index }
								i18n={ i18n }
								placeholders={ placeholders }
								onPatch={ updateRow }
								onMoveUp={ dragHandlers.onMoveUp }
								onMoveDown={ dragHandlers.onMoveDown }
								onRemove={ removeRow }
								dragIndex={ dragHandlers.dragIndex }
								onDragStart={ dragHandlers.onDragStart }
								onDragEnd={ dragHandlers.onDragEnd }
								onDrop={ dragHandlers.onDrop }
							/>
						) )
					) }
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
						{ i18n.quantityPairedAddRow ||
							i18n.pairedOptionsAddRow ||
							'Add option' }
					</Button>
				</VStack>
			</Box>
			{ description ? (
				<Field.HelperText { ...helperTextProps }>
					{ description }
				</Field.HelperText>
			) : null }
		</Field.Root>
	);
}
