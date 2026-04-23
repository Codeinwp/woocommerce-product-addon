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
import type { FontOptionRow } from '../../utils/fontsPairedData';

export interface FontOptionRowItemProps {
	row: FontOptionRow;
	index: number;
	isFirst: boolean;
	isLast: boolean;
	i18n: I18nDict;
	onPatch: ( index: number, patch: Partial< FontOptionRow > ) => void;
	onMoveUp: ( index: number ) => void;
	onMoveDown: ( index: number ) => void;
	onRemove: ( index: number ) => void;
}

export function FontOptionRowItem( {
	row,
	index,
	isFirst,
	isLast,
	i18n,
	onPatch,
	onMoveUp,
	onMoveDown,
	onRemove,
}: FontOptionRowItemProps ) {
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
					flex="1 1 180px"
					minW={ 0 }
					placeholder="Font Family"
					value={ row.fontfamily }
					onValueChange={ ( e ) =>
						onPatch( index, { fontfamily: e.target.value } )
					}
				/>
				<Input
					size="sm"
					flex="1 1 180px"
					minW={ 0 }
					placeholder="Display As Label"
					value={ row.fontdisplay }
					onValueChange={ ( e ) =>
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
				>
					<Checkbox.HiddenInput />
					<Checkbox.Control>
						<Checkbox.Indicator />
					</Checkbox.Control>
					<Checkbox.Label>
						{ i18n.fontsCustomFont || 'Custom Font' }
					</Checkbox.Label>
				</Checkbox.Root>
				<HStack gap={ 1 } flexShrink={ 0 }>
					<Button
						size="xs"
						variant="ghost"
						onClick={ () => onMoveUp( index ) }
						disabled={ isFirst }
					>
						↑
					</Button>
					<Button
						size="xs"
						variant="ghost"
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
						aria-label={ i18n.fontsRemove || 'Remove' }
						title={ i18n.fontsRemove || 'Remove' }
					>
						<LuTrash2 />
					</IconButton>
				</HStack>
			</HStack>
		</Box>
	);
}
