import { Input } from '@chakra-ui/react';
import { DraggableOptionRow } from '../draggable-options/DraggableOptionRow';
import type { I18nDict } from '../../types/fieldModal';
import type { QuantityOptionRow } from '../../utils/pairedQuantityData';

const controlSurface = {
	bg: 'white',
	borderColor: 'gray.200',
	borderRadius: 'md',
	_hover: { borderColor: 'gray.300' },
	_focus: {
		borderColor: 'blue.500',
		boxShadow: '0 0 0 1px #2271b1',
	},
};

export interface QuantityRowPlaceholders {
	option: string;
	price: string;
	weight: string;
	defaultQty: string;
	min: string;
	max: string;
	stock: string;
}

export interface QuantityRowItemProps {
	row: QuantityOptionRow;
	index: number;
	i18n: I18nDict;
	placeholders: QuantityRowPlaceholders;
	onPatch: ( index: number, patch: Partial< QuantityOptionRow > ) => void;
	onMoveUp: ( index: number ) => void;
	onMoveDown: ( index: number ) => void;
	onRemove: ( index: number ) => void;
	dragIndex: number | null;
	onDragStart: ( index: number ) => void;
	onDragEnd: () => void;
	onDrop: ( slot: number ) => void;
}

export function QuantityRowItem( {
	row,
	index,
	i18n,
	placeholders,
	onPatch,
	onMoveUp,
	onMoveDown,
	onRemove,
	dragIndex,
	onDragStart,
	onDragEnd,
	onDrop,
}: QuantityRowItemProps ) {
	return (
		<DraggableOptionRow
			index={ index }
			dragIndex={ dragIndex }
			onDragStart={ onDragStart }
			onDragEnd={ onDragEnd }
			onDrop={ onDrop }
			onMoveUp={ onMoveUp }
			onMoveDown={ onMoveDown }
			onRemove={ onRemove }
			dragLabel={ i18n.pairedOptionsDragHandle || 'Drag to reorder' }
			removeLabel={ i18n.pairedOptionsRemove || 'Remove' }
			align="flex-start"
			flexWrap="wrap"
		>
			<Input
				size="sm"
				flex="1 1 72px"
				minW={ 0 }
				placeholder={ placeholders.option }
				value={ row.option }
				onChange={ ( e ) =>
					onPatch( index, { option: e.target.value } )
				}
				{ ...controlSurface }
			/>
			<Input
				size="sm"
				flex="1 1 72px"
				minW={ 0 }
				placeholder={ placeholders.price }
				value={ row.price }
				onChange={ ( e ) =>
					onPatch( index, { price: e.target.value } )
				}
				{ ...controlSurface }
			/>
			<Input
				size="sm"
				flex="1 1 72px"
				minW={ 0 }
				placeholder={ placeholders.weight }
				value={ row.weight }
				onChange={ ( e ) =>
					onPatch( index, { weight: e.target.value } )
				}
				{ ...controlSurface }
			/>
			<Input
				size="sm"
				flex="1 1 72px"
				minW={ 0 }
				placeholder={ placeholders.defaultQty }
				value={ row.defaultQty }
				onChange={ ( e ) =>
					onPatch( index, { defaultQty: e.target.value } )
				}
				{ ...controlSurface }
			/>
			<Input
				size="sm"
				flex="1 1 64px"
				minW={ 0 }
				placeholder={ placeholders.min }
				value={ row.min }
				onChange={ ( e ) => onPatch( index, { min: e.target.value } ) }
				{ ...controlSurface }
			/>
			<Input
				size="sm"
				flex="1 1 64px"
				minW={ 0 }
				placeholder={ placeholders.max }
				value={ row.max }
				onChange={ ( e ) => onPatch( index, { max: e.target.value } ) }
				{ ...controlSurface }
			/>
			<Input
				size="sm"
				flex="1 1 64px"
				minW={ 0 }
				placeholder={ placeholders.stock }
				value={ row.stock }
				onChange={ ( e ) =>
					onPatch( index, { stock: e.target.value } )
				}
				{ ...controlSurface }
			/>
		</DraggableOptionRow>
	);
}
