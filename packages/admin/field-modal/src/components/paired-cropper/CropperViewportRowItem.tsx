import { Box, Button, HStack, IconButton, Input } from '@chakra-ui/react';
import { LuTrash2 } from 'react-icons/lu';
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
	isFirst: boolean;
	isLast: boolean;
	i18n: I18nDict;
	labelPlaceholder: string;
	widthPlaceholder: string;
	heightPlaceholder: string;
	pricePlaceholder: string;
	onPatch: ( index: number, patch: Partial< CropperViewportRow > ) => void;
	onMoveUp: ( index: number ) => void;
	onMoveDown: ( index: number ) => void;
	onRemove: ( index: number ) => void;
}

export function CropperViewportRowItem( {
	row,
	index,
	isFirst,
	isLast,
	i18n,
	labelPlaceholder,
	widthPlaceholder,
	heightPlaceholder,
	pricePlaceholder,
	onPatch,
	onMoveUp,
	onMoveDown,
	onRemove,
}: CropperViewportRowItemProps ) {
	return (
		<Box borderWidth="1px" borderColor="gray.100" borderRadius="md" p={ 2 }>
			<HStack align="flex-start" gap={ 2 } w="full" flexWrap="wrap">
				<Input
					size="sm"
					flex="1 1 120px"
					minW={ 0 }
					placeholder={ labelPlaceholder }
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
					placeholder={ widthPlaceholder }
					value={ row.width }
					onValueChange={ ( e ) =>
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
					onValueChange={ ( e ) =>
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
					onValueChange={ ( e ) =>
						onPatch( index, { price: e.target.value } )
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
