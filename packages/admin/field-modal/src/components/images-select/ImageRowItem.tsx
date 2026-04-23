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
import type {
	ImageOptionRow,
	ImagesSelectVariant,
} from '../../utils/imagesSelectData';
import { ImagePreview } from './ImagePreview';

export interface ImageRowItemProps {
	row: ImageOptionRow;
	index: number;
	isFirst: boolean;
	isLast: boolean;
	variant: ImagesSelectVariant;
	i18n: I18nDict;
	pricePlaceholder: string;
	onPatch: ( index: number, patch: Partial< ImageOptionRow > ) => void;
	onMoveUp: ( index: number ) => void;
	onMoveDown: ( index: number ) => void;
	onRemove: ( index: number ) => void;
}

export function ImageRowItem( {
	row,
	index,
	isFirst,
	isLast,
	variant,
	i18n,
	pricePlaceholder,
	onPatch,
	onMoveUp,
	onMoveDown,
	onRemove,
}: ImageRowItemProps ) {
	const isImageselect = variant === 'imageselect';
	const isConditionalMeta = variant === 'conditional-meta';

	return (
		<Box borderWidth="1px" borderColor="gray.100" borderRadius="md" p={ 2 }>
			<HStack align="flex-start" gap={ 2 } w="full" overflowX="auto">
				<ImagePreview src={ row.link } alt={ row.title } />

				<VStack gap={ 1.5 } flex="1" minW={ 0 }>
					<HStack gap={ 2 } w="full">
						<Input
							size="sm"
							flex="1 1 0"
							minW={ 0 }
							placeholder={ i18n.imagesTitle || 'Title' }
							value={ row.title }
							onValueChange={ ( e ) =>
								onPatch( index, { title: e.target.value } )
							}
						/>
						{ isConditionalMeta ? (
							<Input
								size="sm"
								flex="1 1 0"
								minW={ 0 }
								placeholder="Meta IDs"
								value={ row.meta_id || '' }
								onValueChange={ ( e ) =>
									onPatch( index, {
										meta_id: e.target.value,
									} )
								}
							/>
						) : (
							<Input
								size="sm"
								flex="1 1 0"
								minW={ 0 }
								placeholder={ pricePlaceholder }
								value={ row.price }
								onValueChange={ ( e ) =>
									onPatch( index, {
										price: e.target.value,
									} )
								}
							/>
						) }
					</HStack>
					<HStack gap={ 2 } w="full">
						{ isConditionalMeta ? (
							<Input
								size="sm"
								flex="1 1 0"
								minW={ 0 }
								placeholder={ i18n.imagesUrl || 'URL' }
								value={ row.url || '' }
								onValueChange={ ( e ) =>
									onPatch( index, { url: e.target.value } )
								}
							/>
						) : (
							<Input
								size="sm"
								flex="1 1 0"
								minW={ 0 }
								placeholder={ i18n.imagesStock || 'Stock' }
								value={ row.stock }
								onValueChange={ ( e ) =>
									onPatch( index, {
										stock: e.target.value,
									} )
								}
							/>
						) }
						{ ! isConditionalMeta && isImageselect ? (
							<Input
								size="sm"
								flex="1 1 0"
								minW={ 0 }
								placeholder="Description"
								value={ row.description || '' }
								onValueChange={ ( e ) =>
									onPatch( index, {
										description: e.target.value,
									} )
								}
							/>
						) : ! isConditionalMeta ? (
							<Input
								size="sm"
								flex="1 1 0"
								minW={ 0 }
								placeholder={ i18n.imagesUrl || 'URL' }
								value={ row.url || '' }
								onValueChange={ ( e ) =>
									onPatch( index, { url: e.target.value } )
								}
							/>
						) : null }
					</HStack>
				</VStack>

				<HStack gap={ 1 } flexShrink={ 0 } alignSelf="center">
					<Button
						size="xs"
						variant="ghost"
						aria-label={ i18n.imagesMoveUp || 'Move up' }
						onClick={ () => onMoveUp( index ) }
						disabled={ isFirst }
					>
						&#8593;
					</Button>
					<Button
						size="xs"
						variant="ghost"
						aria-label={ i18n.imagesMoveDown || 'Move down' }
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
						aria-label={ i18n.imagesRemove || 'Remove' }
						title={ i18n.imagesRemove || 'Remove' }
					>
						<LuTrash2 />
					</IconButton>
				</HStack>
			</HStack>
		</Box>
	);
}
