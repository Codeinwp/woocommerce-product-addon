import {
	Box,
	Button,
	HStack,
	IconButton,
	Input,
	VStack,
} from '@chakra-ui/react';
import { LuTrash2 } from 'react-icons/lu';
import type { I18nDict } from '../../types/fieldModal';
import type { AudioOptionRow } from '../../utils/audiosSelectData';
import { AudioIconBox } from './AudioIconBox';

export interface AudioRowItemProps {
	row: AudioOptionRow;
	index: number;
	isFirst: boolean;
	isLast: boolean;
	i18n: I18nDict;
	pricePlaceholder: string;
	onPatch: ( index: number, patch: Partial< AudioOptionRow > ) => void;
	onMoveUp: ( index: number ) => void;
	onMoveDown: ( index: number ) => void;
	onRemove: ( index: number ) => void;
}

export function AudioRowItem( {
	row,
	index,
	isFirst,
	isLast,
	i18n,
	pricePlaceholder,
	onPatch,
	onMoveUp,
	onMoveDown,
	onRemove,
}: AudioRowItemProps ) {
	return (
		<Box borderWidth="1px" borderColor="gray.100" borderRadius="md" p={ 2 }>
			<HStack
				align="flex-start"
				gap={ 2 }
				w="full"
				overflowX="auto"
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
							onValueChange={ ( e ) =>
								onPatch( index, { title: e.target.value } )
							}
						/>
						<Input
							size="sm"
							flex="1 1 0"
							minW={ 0 }
							placeholder={ pricePlaceholder }
							value={ row.price }
							onValueChange={ ( e ) =>
								onPatch( index, { price: e.target.value } )
							}
						/>
					</HStack>
				</VStack>

				<HStack gap={ 1 } flexShrink={ 0 } alignSelf="center">
					<Button
						size="xs"
						variant="ghost"
						aria-label={ i18n.audioMoveUp || 'Move up' }
						onClick={ () => onMoveUp( index ) }
						disabled={ isFirst }
					>
						&#8593;
					</Button>
					<Button
						size="xs"
						variant="ghost"
						aria-label={ i18n.audioMoveDown || 'Move down' }
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
						aria-label={ i18n.audioRemove || 'Remove' }
						title={ i18n.audioRemove || 'Remove' }
					>
						<LuTrash2 />
					</IconButton>
				</HStack>
			</HStack>
		</Box>
	);
}
