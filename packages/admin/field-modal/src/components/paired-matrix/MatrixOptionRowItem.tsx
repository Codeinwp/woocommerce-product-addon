import {
	Box,
	Button,
	Checkbox,
	HStack,
	IconButton,
	Input,
} from '@chakra-ui/react';
import { LuTrash2 } from 'react-icons/lu';
import type { I18nDict } from '../../types/fieldModal';
import {
	type MatrixOptionRow,
	isMatrixFixedChecked,
} from '../../utils/pairedMatrixData';

export interface MatrixOptionRowItemProps {
	row: MatrixOptionRow;
	index: number;
	isFirst: boolean;
	isLast: boolean;
	i18n: I18nDict;
	onPatch: ( index: number, patch: Partial< MatrixOptionRow > ) => void;
	onMoveUp: ( index: number ) => void;
	onMoveDown: ( index: number ) => void;
	onRemove: ( index: number ) => void;
	onToggleFixed: ( index: number, checked: boolean ) => void;
}

export function MatrixOptionRowItem( {
	row,
	index,
	isFirst,
	isLast,
	i18n,
	onPatch,
	onMoveUp,
	onMoveDown,
	onRemove,
	onToggleFixed,
}: MatrixOptionRowItemProps ) {
	return (
		<Box borderWidth="1px" borderColor="gray.100" borderRadius="md" p={ 2 }>
			<HStack
				align="center"
				gap={ 2 }
				w="full"
				overflowX="auto"
				flexWrap="wrap"
			>
				<Input
					size="sm"
					flex="1 1 120px"
					minW={ 0 }
					placeholder={ i18n.pairedMatrixOption || 'Option' }
					value={ String( row.option ?? '' ) }
					onValueChange={ ( e ) =>
						onPatch( index, { option: e.target.value } )
					}
				/>
				<Input
					size="sm"
					flex="1 1 100px"
					minW={ 0 }
					placeholder={ i18n.pairedMatrixPrice || 'Price' }
					value={ String( row.price ?? '' ) }
					onValueChange={ ( e ) =>
						onPatch( index, { price: e.target.value } )
					}
				/>
				<Input
					size="sm"
					flex="1 1 100px"
					minW={ 0 }
					placeholder={ i18n.pairedMatrixLabel || 'Label' }
					value={ String( row.label ?? '' ) }
					onValueChange={ ( e ) =>
						onPatch( index, { label: e.target.value } )
					}
				/>
				<Input
					size="sm"
					flex="1 1 100px"
					minW={ 0 }
					placeholder={ i18n.pairedMatrixOptionId || 'Option ID' }
					value={ String( row.id ?? '' ) }
					onValueChange={ ( e ) =>
						onPatch( index, { id: e.target.value } )
					}
				/>
				<Checkbox.Root
					size="sm"
					onCheckedChange={ ( e ) =>
						onToggleFixed( index, e.target.checked )
					}
					checked={ isMatrixFixedChecked( row ) }
				>
					<Checkbox.HiddenInput />
					<Checkbox.Control>
						<Checkbox.Indicator />
					</Checkbox.Control>
					<Checkbox.Label>
						{ i18n.pairedMatrixFixed || 'Fixed' }
					</Checkbox.Label>
				</Checkbox.Root>
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
