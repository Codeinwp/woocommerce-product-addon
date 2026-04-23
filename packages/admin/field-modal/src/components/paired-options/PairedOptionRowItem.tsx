import { Box, Button, HStack, IconButton, Input } from '@chakra-ui/react';
import { LuTrash2 } from 'react-icons/lu';
import type { I18nDict } from '../../types/fieldModal';
import type {
	PairedOptionRow,
	PairedOptionsVariant,
} from '../../utils/pairedOptionsData';
import { CheckboxOptionFields } from './CheckboxOptionFields';
import { SwitcherOptionFields } from './SwitcherOptionFields';

export interface PairedOptionRowItemProps {
	row: PairedOptionRow;
	index: number;
	isFirst: boolean;
	isLast: boolean;
	variant: PairedOptionsVariant;
	i18n: I18nDict;
	onPatch: ( index: number, patch: Partial< PairedOptionRow > ) => void;
	onMoveUp: ( index: number ) => void;
	onMoveDown: ( index: number ) => void;
	onRemove: ( index: number ) => void;
}

export function PairedOptionRowItem( {
	row,
	index,
	isFirst,
	isLast,
	variant,
	i18n,
	onPatch,
	onMoveUp,
	onMoveDown,
	onRemove,
}: PairedOptionRowItemProps ) {
	const showCheckboxExtras = variant === 'checkbox';
	const showSwitcherExtras = variant === 'switcher';

	return (
		<Box borderWidth="1px" borderColor="gray.100" borderRadius="md" p={ 2 }>
			<HStack align="center" gap={ 2 } w="full" overflowX="auto">
				<Input
					size="sm"
					flex="1 1 0"
					minW={ 0 }
					w="auto"
					placeholder={ i18n.pairedOptionLabel || 'Option' }
					value={ String( row.option ?? row.title ?? '' ) }
					onValueChange={ ( e ) =>
						onPatch( index, { option: e.target.value } )
					}
				/>
				<Input
					size="sm"
					flex="1 1 0"
					minW={ 0 }
					w="auto"
					placeholder={ i18n.pairedOptionPrice || 'Price' }
					value={ String( row.price ?? '' ) }
					onValueChange={ ( e ) =>
						onPatch( index, { price: e.target.value } )
					}
				/>
				{ showCheckboxExtras ? (
					<CheckboxOptionFields
						row={ row }
						index={ index }
						i18n={ i18n }
						onPatch={ onPatch }
					/>
				) : null }
				<Input
					size="sm"
					flex="1 1 0"
					minW={ 0 }
					w="auto"
					placeholder={ i18n.pairedOptionWeight || 'Weight' }
					value={ String( row.weight ?? '' ) }
					onValueChange={ ( e ) =>
						onPatch( index, { weight: e.target.value } )
					}
				/>
				<Input
					size="sm"
					flex="1 1 0"
					minW={ 0 }
					w="auto"
					placeholder={ i18n.pairedOptionStock || 'Stock' }
					value={ String( row.stock ?? '' ) }
					onValueChange={ ( e ) =>
						onPatch( index, { stock: e.target.value } )
					}
				/>
				<Input
					size="sm"
					flex="1 1 0"
					minW={ 0 }
					w="auto"
					placeholder={
						showSwitcherExtras
							? i18n.pairedOptionId || 'Option ID'
							: i18n.pairedOptionImageId || 'Image ID'
					}
					value={ String( row.id ?? row.images ?? '' ) }
					onValueChange={ ( e ) =>
						onPatch( index, { id: e.target.value } )
					}
				/>
				{ showSwitcherExtras ? (
					<SwitcherOptionFields
						row={ row }
						index={ index }
						i18n={ i18n }
						onPatch={ onPatch }
					/>
				) : null }
				<HStack gap={ 1 } flexShrink={ 0 }>
					<Button
						size="xs"
						variant="ghost"
						aria-label={ i18n.pairedOptionsMoveUp || 'Up' }
						onClick={ () => onMoveUp( index ) }
						disabled={ isFirst }
					>
						↑
					</Button>
					<Button
						size="xs"
						variant="ghost"
						aria-label={ i18n.pairedOptionsMoveDown || 'Down' }
						onClick={ () => onMoveDown( index ) }
						disabled={ isLast }
					>
						↓
					</Button>
					<IconButton
						size="xs"
						variant="ghost"
						colorPalette="red"
						onClick={ () => onRemove( index ) }
						aria-label={ i18n.pairedOptionsRemove || 'Remove' }
						title={ i18n.pairedOptionsRemove || 'Remove' }
					>
						<LuTrash2 />
					</IconButton>
				</HStack>
			</HStack>
		</Box>
	);
}
