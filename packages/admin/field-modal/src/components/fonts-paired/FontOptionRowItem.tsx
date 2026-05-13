import { Checkbox, Input } from '@chakra-ui/react';
import { DraggableOptionRow } from '../draggable-options/DraggableOptionRow';
import type { I18nDict } from '../../types/fieldModal';
import type { FontOptionRow } from '../../utils/fontsPairedData';

export interface FontOptionRowItemProps {
	row: FontOptionRow;
	index: number;
	i18n: I18nDict;
	onPatch: ( index: number, patch: Partial< FontOptionRow > ) => void;
	onMoveUp: ( index: number ) => void;
	onMoveDown: ( index: number ) => void;
	onRemove: ( index: number ) => void;
	dragIndex: number | null;
	onDragStart: ( index: number ) => void;
	onDragEnd: () => void;
	onDrop: ( slot: number ) => void;
}

export function FontOptionRowItem( {
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
}: FontOptionRowItemProps ) {
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
			removeLabel={ i18n.fontsRemove || 'Remove' }
			flexWrap="wrap"
		>
			<Input
				size="sm"
				flex="1 1 180px"
				minW={ 0 }
				placeholder="Font Family"
				value={ row.fontfamily }
				onChange={ ( e ) =>
					onPatch( index, { fontfamily: e.target.value } )
				}
			/>
			<Input
				size="sm"
				flex="1 1 180px"
				minW={ 0 }
				placeholder="Display As Label"
				value={ row.fontdisplay }
				onChange={ ( e ) =>
					onPatch( index, { fontdisplay: e.target.value } )
				}
			/>
			<Checkbox.Root
				size="sm"
				checked={ row.is_customfont === 'on' }
				onCheckedChange={ ( details ) =>
					onPatch( index, {
						is_customfont: details.checked === true ? 'on' : '',
					} )
				}
				flexShrink={ 0 }
			>
				<Checkbox.HiddenInput />
				<Checkbox.Control>
					<Checkbox.Indicator />
				</Checkbox.Control>
				<Checkbox.Label>
					{ i18n.fontsCustomFont || 'Custom Font' }
				</Checkbox.Label>
			</Checkbox.Root>
		</DraggableOptionRow>
	);
}
