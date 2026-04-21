import {
	Box,
	Button,
	HStack,
	IconButton,
	Input,
} from '@chakra-ui/react';
import { LuTrash2 } from 'react-icons/lu';
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
	isFirst: boolean;
	isLast: boolean;
	i18n: I18nDict;
	placeholders: QuantityRowPlaceholders;
	onPatch: ( index: number, patch: Partial< QuantityOptionRow > ) => void;
	onMoveUp: ( index: number ) => void;
	onMoveDown: ( index: number ) => void;
	onRemove: ( index: number ) => void;
}

export function QuantityRowItem( {
	row,
	index,
	isFirst,
	isLast,
	i18n,
	placeholders,
	onPatch,
	onMoveUp,
	onMoveDown,
	onRemove,
}: QuantityRowItemProps ) {
	return (
		<Box borderWidth="1px" borderColor="gray.100" borderRadius="md" p={ 2 }>
			<HStack
				align="flex-start"
				gap={ 2 }
				w="full"
				flexWrap="wrap"
			>
				<Input
					size="sm"
					flex="1 1 72px"
					minW={ 0 }
					placeholder={ placeholders.option }
					value={ row.option }
					onValueChange={ ( e ) =>
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
					onValueChange={ ( e ) =>
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
					onValueChange={ ( e ) =>
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
					onValueChange={ ( e ) =>
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
					onValueChange={ ( e ) =>
						onPatch( index, { min: e.target.value } )
					}
					{ ...controlSurface }
				/>
				<Input
					size="sm"
					flex="1 1 64px"
					minW={ 0 }
					placeholder={ placeholders.max }
					value={ row.max }
					onValueChange={ ( e ) =>
						onPatch( index, { max: e.target.value } )
					}
					{ ...controlSurface }
				/>
				<Input
					size="sm"
					flex="1 1 64px"
					minW={ 0 }
					placeholder={ placeholders.stock }
					value={ row.stock }
					onValueChange={ ( e ) =>
						onPatch( index, { stock: e.target.value } )
					}
					{ ...controlSurface }
				/>
				<HStack gap={ 1 } flexShrink={ 0 }>
					<Button
						size="xs"
						variant="ghost"
						aria-label={ i18n.pairedOptionsMoveUp || 'Move up' }
						onClick={ () => onMoveUp( index ) }
						disabled={ isFirst }
					>
						&#8593;
					</Button>
					<Button
						size="xs"
						variant="ghost"
						aria-label={ i18n.pairedOptionsMoveDown || 'Move down' }
						onClick={ () => onMoveDown( index ) }
						disabled={ isLast }
					>
						&#8595;
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
