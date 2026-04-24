import { HStack, Input, VStack } from '@chakra-ui/react';
import { DraggableOptionRow } from '../draggable-options/DraggableOptionRow';
import type { I18nDict } from '../../types/fieldModal';
import type { AudioOptionRow } from '../../utils/audiosSelectData';
import { AudioIconBox } from './AudioIconBox';

export interface AudioRowItemProps {
	row: AudioOptionRow;
	index: number;
	i18n: I18nDict;
	pricePlaceholder: string;
	onPatch: ( index: number, patch: Partial< AudioOptionRow > ) => void;
	onMoveUp: ( index: number ) => void;
	onMoveDown: ( index: number ) => void;
	onRemove: ( index: number ) => void;
	dragIndex: number | null;
	onDragStart: ( index: number ) => void;
	onDragEnd: () => void;
	onDrop: ( slot: number ) => void;
}

export function AudioRowItem( {
	row,
	index,
	i18n,
	pricePlaceholder,
	onPatch,
	onMoveUp,
	onMoveDown,
	onRemove,
	dragIndex,
	onDragStart,
	onDragEnd,
	onDrop,
}: AudioRowItemProps ) {
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
			dragLabel={ i18n.audioDragHandle || 'Drag to reorder' }
			removeLabel={ i18n.audioRemove || 'Remove' }
			align="flex-start"
		>
			<AudioIconBox />

			<VStack gap={ 1.5 } flex="1" minW={ 0 }>
				<HStack gap={ 2 } w="full">
					<Input
						size="sm"
						flex="1 1 0"
						minW={ 0 }
						placeholder={ i18n.audioTitlePlaceholder || 'Title' }
						value={ row.title }
						onChange={ ( e ) =>
							onPatch( index, { title: e.target.value } )
						}
					/>
					<Input
						size="sm"
						flex="1 1 0"
						minW={ 0 }
						placeholder={ pricePlaceholder }
						value={ row.price }
						onChange={ ( e ) =>
							onPatch( index, { price: e.target.value } )
						}
					/>
				</HStack>
			</VStack>
		</DraggableOptionRow>
	);
}
