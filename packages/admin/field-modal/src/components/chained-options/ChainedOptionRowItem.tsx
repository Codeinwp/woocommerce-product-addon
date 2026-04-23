import { Box, Button, HStack, IconButton, Input } from '@chakra-ui/react';
import { LuTrash2 } from 'react-icons/lu';
import type { I18nDict } from '../../types/fieldModal';
import type { ChainedOptionRow } from '../../utils/chainedOptionsData';

export interface ChainedOptionRowItemProps {
	row: ChainedOptionRow;
	index: number;
	isFirst: boolean;
	isLast: boolean;
	i18n: I18nDict;
	onPatch: ( index: number, patch: Partial< ChainedOptionRow > ) => void;
	onMoveUp: ( index: number ) => void;
	onMoveDown: ( index: number ) => void;
	onRemove: ( index: number ) => void;
}

export function ChainedOptionRowItem( {
	row,
	index,
	isFirst,
	isLast,
	i18n,
	onPatch,
	onMoveUp,
	onMoveDown,
	onRemove,
}: ChainedOptionRowItemProps ) {
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
					flex="1 1 150px"
					minW={ 0 }
					placeholder={ i18n.pairedOptionLabel || 'Option' }
					value={ row.option }
					onValueChange={ ( e ) =>
						onPatch( index, { option: e.target.value } )
					}
				/>
				<Input
					size="sm"
					flex="1 1 150px"
					minW={ 0 }
					placeholder="Chain"
					value={ row.chained }
					onValueChange={ ( e ) =>
						onPatch( index, { chained: e.target.value } )
					}
				/>
				<Input
					size="sm"
					flex="1 1 150px"
					minW={ 0 }
					placeholder="Sub-Chain"
					value={ row.features }
					onValueChange={ ( e ) =>
						onPatch( index, { features: e.target.value } )
					}
				/>
				<Input
					size="sm"
					flex="1 1 150px"
					minW={ 0 }
					placeholder="Option ID"
					value={ row.id }
					onValueChange={ ( e ) =>
						onPatch( index, { id: e.target.value } )
					}
				/>
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
