import { Input } from '@chakra-ui/react';
import { DraggableOptionRow } from '../draggable-options/DraggableOptionRow';
import type { I18nDict } from '../../types/fieldModal';
import type { ChainedOptionRow } from '../../utils/chainedOptionsData';

export interface ChainedOptionRowItemProps {
	row: ChainedOptionRow;
	index: number;
	i18n: I18nDict;
	onPatch: ( index: number, patch: Partial< ChainedOptionRow > ) => void;
	onMoveUp: ( index: number ) => void;
	onMoveDown: ( index: number ) => void;
	onRemove: ( index: number ) => void;
	dragIndex: number | null;
	onDragStart: ( index: number ) => void;
	onDragEnd: () => void;
	onDrop: ( slot: number ) => void;
}

export function ChainedOptionRowItem( {
	row,
	index,
	i18n,
	onPatch,
	onMoveUp,
	onMoveDown,
	onRemove,
	dragIndex,
	onDragStart,
	onDragEnd,
	onDrop,
}: ChainedOptionRowItemProps ) {
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
			flexWrap="wrap"
		>
			<Input
				size="sm"
				flex="1 1 150px"
				minW={ 0 }
				placeholder={ i18n.pairedOptionLabel || 'Option' }
				value={ row.option }
				onChange={ ( e ) =>
					onPatch( index, { option: e.target.value } )
				}
			/>
			<Input
				size="sm"
				flex="1 1 150px"
				minW={ 0 }
				placeholder="Chain"
				value={ row.chained }
				onChange={ ( e ) =>
					onPatch( index, { chained: e.target.value } )
				}
			/>
			<Input
				size="sm"
				flex="1 1 150px"
				minW={ 0 }
				placeholder="Sub-Chain"
				value={ row.features }
				onChange={ ( e ) =>
					onPatch( index, { features: e.target.value } )
				}
			/>
			<Input
				size="sm"
				flex="1 1 150px"
				minW={ 0 }
				placeholder="Option ID"
				value={ row.id }
				onChange={ ( e ) => onPatch( index, { id: e.target.value } ) }
			/>
		</DraggableOptionRow>
	);
}
