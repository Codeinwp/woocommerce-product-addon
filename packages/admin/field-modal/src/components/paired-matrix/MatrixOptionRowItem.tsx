import { Checkbox, Input } from '@chakra-ui/react';
import { DraggableOptionRow } from '../draggable-options/DraggableOptionRow';
import type { I18nDict } from '../../types/fieldModal';
import {
	type MatrixOptionRow,
	isMatrixFixedChecked,
} from '../../utils/pairedMatrixData';

export interface MatrixOptionRowItemProps {
	row: MatrixOptionRow;
	index: number;
	i18n: I18nDict;
	onPatch: ( index: number, patch: Partial< MatrixOptionRow > ) => void;
	onMoveUp: ( index: number ) => void;
	onMoveDown: ( index: number ) => void;
	onRemove: ( index: number ) => void;
	onToggleFixed: ( index: number, checked: boolean ) => void;
	dragIndex: number | null;
	onDragStart: ( index: number ) => void;
	onDragEnd: () => void;
	onDrop: ( slot: number ) => void;
}

export function MatrixOptionRowItem( {
	row,
	index,
	i18n,
	onPatch,
	onMoveUp,
	onMoveDown,
	onRemove,
	onToggleFixed,
	dragIndex,
	onDragStart,
	onDragEnd,
	onDrop,
}: MatrixOptionRowItemProps ) {
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
				flex="1 1 120px"
				minW={ 0 }
				placeholder={ i18n.pairedMatrixOption || 'Option' }
				value={ String( row.option ?? '' ) }
				onChange={ ( e ) =>
					onPatch( index, { option: e.target.value } )
				}
			/>
			<Input
				size="sm"
				flex="1 1 100px"
				minW={ 0 }
				placeholder={ i18n.pairedMatrixPrice || 'Price' }
				value={ String( row.price ?? '' ) }
				onChange={ ( e ) =>
					onPatch( index, { price: e.target.value } )
				}
			/>
			<Input
				size="sm"
				flex="1 1 100px"
				minW={ 0 }
				placeholder={ i18n.pairedMatrixLabel || 'Label' }
				value={ String( row.label ?? '' ) }
				onChange={ ( e ) =>
					onPatch( index, { label: e.target.value } )
				}
			/>
			<Input
				size="sm"
				flex="1 1 100px"
				minW={ 0 }
				placeholder={ i18n.pairedMatrixOptionId || 'Option ID' }
				value={ String( row.id ?? '' ) }
				onChange={ ( e ) => onPatch( index, { id: e.target.value } ) }
			/>
			<Checkbox.Root
				size="sm"
				onCheckedChange={ ( e ) =>
					onToggleFixed( index, e.target.checked )
				}
				checked={ isMatrixFixedChecked( row ) }
				flexShrink={ 0 }
			>
				<Checkbox.HiddenInput />
				<Checkbox.Control>
					<Checkbox.Indicator />
				</Checkbox.Control>
				<Checkbox.Label>
					{ i18n.pairedMatrixFixed || 'Fixed' }
				</Checkbox.Label>
			</Checkbox.Root>
		</DraggableOptionRow>
	);
}
