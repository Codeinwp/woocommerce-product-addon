/**
 * Variation quantity matrix rows (paired-quantity schema type).
 */
import { Box, Button, VStack, Field } from '@chakra-ui/react';
import type { Dispatch, SetStateAction } from 'react';
import type { FieldRow, I18nDict } from '../types/fieldModal';
import { arrayMove } from '../utils/arrayMove';
import {
	type QuantityOptionRow,
	emptyQuantityRow,
	normalizePairedQuantityOptions,
	serializePairedQuantityOptions,
} from '../utils/pairedQuantityData';
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
		if ( rows.length <= 1 ) {
			setRows( [ emptyQuantityRow() ] );
			return;
		}
		setRows( rows.filter( ( _, i ) => i !== index ) );
	};

	const moveUp = ( index: number ) => setRows( arrayMove( rows, index, -1 ) );
	const moveDown = ( index: number ) => setRows( arrayMove( rows, index, 1 ) );

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

	return (
		<Field.Root>
			<Field.Label { ...labelProps }>{ title }</Field.Label>
			<Box
				borderWidth="1px"
				borderColor="gray.200"
				borderRadius="md"
				p={ 3 }
				bg="white"
			>
				<Button size="sm" colorPalette="blue" mb={ 3 } onClick={ addRow }>
					{ i18n.quantityPairedAddRow ||
						i18n.pairedOptionsAddRow ||
						'Add option' }
				</Button>

				<VStack align="stretch" gap={ 3 }>
					{ rows.map( ( row, index ) => (
						<QuantityRowItem
							key={ index }
							row={ row }
							index={ index }
							isFirst={ index === 0 }
							isLast={ index === rows.length - 1 }
							i18n={ i18n }
							placeholders={ placeholders }
							onPatch={ updateRow }
							onMoveUp={ moveUp }
							onMoveDown={ moveDown }
							onRemove={ removeRow }
						/>
					) ) }
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
