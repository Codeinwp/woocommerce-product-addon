import {
	Checkbox,
	ColorPicker,
	HStack,
	Input,
	parseColor,
} from '@chakra-ui/react';
import { DraggableOptionRow } from '../draggable-options/DraggableOptionRow';
import type { I18nDict } from '../../types/fieldModal';
import {
	colorFromStoredValue,
	persistColorValueAsHex,
} from '../../controls/colorHelpers';
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
	optionVariant?: 'text' | 'color';
}

/**
 * Try to parse the user's free-form option string as a color for the swatch.
 * Falls back to opaque black silently — palette options accept any CSS
 * background-color value (named colors, rgba, gradients), so we shouldn't
 * surface a parse error just because the swatch can't render it.
 */
function safeColorFromOption( raw: unknown ) {
	const trimmed = String( raw ?? '' ).trim();
	if ( ! trimmed ) {
		return parseColor( '#000000' );
	}
	try {
		return colorFromStoredValue( trimmed );
	} catch {
		return parseColor( '#000000' );
	}
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
	optionVariant = 'text',
}: MatrixOptionRowItemProps ) {
	const optionInput = (
		<Input
			size="sm"
			flex="1 1 120px"
			minW={ 0 }
			placeholder={ i18n.pairedMatrixOption || 'Option' }
			value={ String( row.option ?? '' ) }
			onChange={ ( e ) => onPatch( index, { option: e.target.value } ) }
		/>
	);

	const optionField =
		optionVariant === 'color' ? (
			<HStack flex="1 1 120px" minW={ 0 } gap={ 1.5 }>
				<ColorPicker.Root
					value={ safeColorFromOption( row.option ) }
					onValueChange={ ( details ) =>
						onPatch( index, {
							option: persistColorValueAsHex( details.value ),
						} )
					}
					size="sm"
				>
					<ColorPicker.HiddenInput />
					<ColorPicker.Trigger
						aria-label={
							i18n.pairedMatrixPickColor || 'Pick color'
						}
						flexShrink={ 0 }
					>
						<ColorPicker.ValueSwatch
							boxSize={ 6 }
							borderWidth="1px"
							borderColor="gray.300"
							borderRadius="sm"
						/>
					</ColorPicker.Trigger>
					<ColorPicker.Positioner>
						<ColorPicker.Content>
							<ColorPicker.Area />
							<HStack mt={ 2 }>
								<ColorPicker.EyeDropper
									size="xs"
									variant="outline"
								/>
								<ColorPicker.Sliders />
							</HStack>
						</ColorPicker.Content>
					</ColorPicker.Positioner>
				</ColorPicker.Root>
				{ optionInput }
			</HStack>
		) : (
			optionInput
		);

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
			{ optionField }
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
