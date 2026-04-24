/**
 * Fixed Price paired rows: quantity + fixed price (number inputs), classic input.fixedprice.php shape.
 */
import { Box, Button, Icon, Input, Text, VStack } from '@chakra-ui/react';
import { LuPlus } from 'react-icons/lu';
import type { Dispatch, SetStateAction } from 'react';
import type { FieldRow } from '../types/fieldModal';
import type { I18nDict } from '../types/fieldModal';
import type { PairedOptionRow } from './PairedOptionsEditor';
import {
	DraggableOptionRow,
	useDraggableRows,
} from './draggable-options/DraggableOptionRow';

function normalizeOptionsArray( raw: unknown ): PairedOptionRow[] {
	if ( Array.isArray( raw ) ) {
		return raw.map( ( o ) =>
			o && typeof o === 'object' && ! Array.isArray( o )
				? { ...( o as Record< string, unknown > ) }
				: {}
		);
	}
	if ( raw && typeof raw === 'object' && ! Array.isArray( raw ) ) {
		return Object.keys( raw as object ).map( ( k ) => {
			const v = ( raw as Record< string, unknown > )[ k ];
			if ( v && typeof v === 'object' && ! Array.isArray( v ) ) {
				return { ...( v as Record< string, unknown > ) };
			}
			return { option: k };
		} );
	}
	return [];
}

export interface PairedFixedPriceEditorProps {
	values: FieldRow;
	onChange: Dispatch< SetStateAction< FieldRow | null > >;
	i18n: I18nDict;
	title: string;
	/** From schema `options.placeholders` (e.g. quantity label, price label). */
	placeholders?: string[];
	/** From schema `options.types` (e.g. `number`, `number`). */
	types?: string[];
}

export function PairedFixedPriceEditor( {
	values,
	onChange,
	i18n,
	title,
	placeholders = [],
	types = [ 'number', 'number' ],
}: PairedFixedPriceEditorProps ) {
	const rows = normalizeOptionsArray( values.options );
	const p0 = placeholders[ 0 ] || i18n.fixedPriceQtyPlaceholder || 'Quantity';
	const p1 =
		placeholders[ 1 ] || i18n.fixedPricePricePlaceholder || 'Fixed Price';
	const t0 = types[ 0 ] === 'number' ? 'number' : 'text';
	const t1 = types[ 1 ] === 'number' ? 'number' : 'text';

	const setRows = ( next: PairedOptionRow[] ) => {
		onChange( ( prev ) => {
			if ( ! prev ) {
				return prev;
			}
			return { ...prev, options: next };
		} );
	};

	const updateRow = ( index: number, patch: Partial< PairedOptionRow > ) => {
		const next = rows.map( ( r, i ) =>
			i === index ? { ...r, ...patch } : r
		);
		setRows( next );
	};

	const addRow = () => {
		setRows( [ ...rows, { option: '', price: '', id: '' } ] );
	};

	const removeRow = ( index: number ) => {
		setRows( rows.filter( ( _, i ) => i !== index ) );
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
					<DraggableOptionRow
						key={ index }
						index={ index }
						dragIndex={ dragHandlers.dragIndex }
						onDragStart={ dragHandlers.onDragStart }
						onDragEnd={ dragHandlers.onDragEnd }
						onDrop={ dragHandlers.onDrop }
						onMoveUp={ dragHandlers.onMoveUp }
						onMoveDown={ dragHandlers.onMoveDown }
						onRemove={ removeRow }
						dragLabel={
							i18n.pairedOptionsDragHandle || 'Drag to reorder'
						}
						removeLabel={ i18n.pairedOptionsRemove || 'Remove' }
						flexWrap="wrap"
					>
						<Input
							size="sm"
							type={ t0 }
							flex="1 1 120px"
							minW={ 0 }
							placeholder={ p0 }
							value={ String( row.option ?? '' ) }
							onChange={ ( e ) =>
								updateRow( index, {
									option: e.target.value,
								} )
							}
						/>
						<Input
							size="sm"
							type={ t1 }
							flex="1 1 120px"
							minW={ 0 }
							placeholder={ p1 }
							value={ String( row.price ?? '' ) }
							onChange={ ( e ) =>
								updateRow( index, {
									price: e.target.value,
								} )
							}
						/>
						<Input
							size="sm"
							flex="1 1 100px"
							minW={ 0 }
							placeholder={
								i18n.pairedMatrixOptionId || 'Unique ID'
							}
							value={ String( row.id ?? '' ) }
							onChange={ ( e ) =>
								updateRow( index, { id: e.target.value } )
							}
						/>
					</DraggableOptionRow>
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
