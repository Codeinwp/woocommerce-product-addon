import { Input } from '@chakra-ui/react';
import { DraggableOptionRow } from '../draggable-options/DraggableOptionRow';
import type { I18nDict } from '../../types/fieldModal';
import type { CropperViewportRow } from '../../utils/pairedCropperData';

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

export interface CropperViewportRowItemProps {
	row: CropperViewportRow;
	index: number;
	i18n: I18nDict;
	labelPlaceholder: string;
	widthPlaceholder: string;
	heightPlaceholder: string;
	pricePlaceholder: string;
	onPatch: ( index: number, patch: Partial< CropperViewportRow > ) => void;
	onMoveUp: ( index: number ) => void;
	onMoveDown: ( index: number ) => void;
	onRemove: ( index: number ) => void;
	dragIndex: number | null;
	onDragStart: ( index: number ) => void;
	onDragEnd: () => void;
	onDrop: ( slot: number ) => void;
}

export function CropperViewportRowItem( {
	row,
	index,
	i18n,
	labelPlaceholder,
	widthPlaceholder,
	heightPlaceholder,
	pricePlaceholder,
	onPatch,
	onMoveUp,
	onMoveDown,
	onRemove,
	dragIndex,
	onDragStart,
	onDragEnd,
	onDrop,
}: CropperViewportRowItemProps ) {
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
				flex="1 1 120px"
				minW={ 0 }
				placeholder={ labelPlaceholder }
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
				placeholder={ widthPlaceholder }
				value={ row.width }
				onChange={ ( e ) =>
					onPatch( index, { width: e.target.value } )
				}
				{ ...controlSurface }
			/>
			<Input
				size="sm"
				flex="1 1 72px"
				minW={ 0 }
				placeholder={ heightPlaceholder }
				value={ row.height }
				onChange={ ( e ) =>
					onPatch( index, { height: e.target.value } )
				}
				{ ...controlSurface }
			/>
			<Input
				size="sm"
				flex="1 1 96px"
				minW={ 0 }
				placeholder={ pricePlaceholder }
				value={ row.price }
				onChange={ ( e ) =>
					onPatch( index, { price: e.target.value } )
				}
				{ ...controlSurface }
			/>
		</DraggableOptionRow>
	);
}
