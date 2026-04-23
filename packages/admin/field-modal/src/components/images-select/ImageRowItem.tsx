import {
	Box,
	HStack,
	IconButton,
	Input,
	NativeSelect,
	Text,
	VStack,
} from '@chakra-ui/react';
import {
	useRef,
	useState,
	type CSSProperties,
	type DragEvent,
	type KeyboardEvent,
	type ReactNode,
} from 'react';
import { LuGripVertical, LuRefreshCw, LuTrash2 } from 'react-icons/lu';
import type { I18nDict } from '../../types/fieldModal';
import {
	filenameFromUrl,
	formatPrice,
	parsePriceAmount,
	parsePriceType,
	type ImageOptionRow,
	type ImagesSelectVariant,
	type PriceType,
} from '../../utils/imagesSelectData';
import { ImagePreview } from './ImagePreview';

export interface ImageRowItemProps {
	row: ImageOptionRow;
	index: number;
	isFirst: boolean;
	isLast: boolean;
	variant: ImagesSelectVariant;
	i18n: I18nDict;
	onPatch: ( index: number, patch: Partial< ImageOptionRow > ) => void;
	onMoveUp: ( index: number ) => void;
	onMoveDown: ( index: number ) => void;
	onRemove: ( index: number ) => void;
	onReplace: ( index: number ) => void;
	/** Index currently being dragged (null = none). Used to dim the source. */
	dragIndex: number | null;
	onDragStart: ( index: number ) => void;
	onDragEnd: () => void;
	/** Slot = insertion index (0..rows.length). Upper half → index, lower half → index+1. */
	onDrop: ( slot: number ) => void;
}

/**
 * Minimalist row — borderless identity line + hover-reveal controls + inline
 * metadata. No per-card border; sibling rows are separated by a thin divider
 * rendered by the list container.
 */
export function ImageRowItem( {
	row,
	index,
	isFirst: _isFirst,
	isLast: _isLast,
	variant,
	i18n,
	onPatch,
	onMoveUp,
	onMoveDown,
	onRemove,
	onReplace,
	dragIndex,
	onDragStart,
	onDragEnd,
	onDrop,
}: ImageRowItemProps ) {
	const isImageselect = variant === 'imageselect';
	const isConditionalMeta = variant === 'conditional-meta';

	const [ draggable, setDraggable ] = useState( false );
	const [ dropEdge, setDropEdge ] = useState< 'above' | 'below' | null >(
		null
	);
	const cardRef = useRef< HTMLDivElement | null >( null );

	const isBeingDragged = dragIndex === index;
	const isDragActive = dragIndex !== null;

	const handleDragStart = ( e: DragEvent< HTMLDivElement > ) => {
		if ( cardRef.current ) {
			e.dataTransfer.effectAllowed = 'move';
			e.dataTransfer.setData( 'text/plain', String( index ) );
			e.dataTransfer.setDragImage( cardRef.current, 12, 12 );
		}
		onDragStart( index );
	};

	const edgeFor = ( e: DragEvent< HTMLDivElement > ): 'above' | 'below' => {
		const rect = e.currentTarget.getBoundingClientRect();
		return e.clientY < rect.top + rect.height / 2 ? 'above' : 'below';
	};

	const handleDragEnter = ( e: DragEvent< HTMLDivElement > ) => {
		e.preventDefault();
		setDropEdge( edgeFor( e ) );
	};

	const handleDragOver = ( e: DragEvent< HTMLDivElement > ) => {
		e.preventDefault();
		e.dataTransfer.dropEffect = 'move';
		setDropEdge( edgeFor( e ) );
	};

	const handleDragLeave = () => setDropEdge( null );

	const handleDrop = ( e: DragEvent< HTMLDivElement > ) => {
		e.preventDefault();
		const slot = edgeFor( e ) === 'below' ? index + 1 : index;
		onDrop( slot );
		setDraggable( false );
		setDropEdge( null );
	};

	const handleDragEnd = () => {
		onDragEnd();
		setDraggable( false );
		setDropEdge( null );
	};

	const handleKeyDown = ( e: KeyboardEvent< HTMLButtonElement > ) => {
		if ( e.key === 'ArrowUp' ) {
			e.preventDefault();
			onMoveUp( index );
		} else if ( e.key === 'ArrowDown' ) {
			e.preventDefault();
			onMoveDown( index );
		}
	};

	const priceAmount = parsePriceAmount( row.price );
	const priceType = parsePriceType( row.price );

	const patchPriceAmount = ( amount: string ) =>
		onPatch( index, { price: formatPrice( amount, priceType ) } );

	const patchPriceType = ( nextType: PriceType ) =>
		onPatch( index, { price: formatPrice( priceAmount, nextType ) } );

	const showDropEdge =
		isDragActive && ! isBeingDragged && dropEdge !== null;
	const indicatorShadow =
		showDropEdge && dropEdge === 'above'
			? 'inset 0 2px 0 0 var(--chakra-colors-blue-500)'
			: showDropEdge && dropEdge === 'below'
				? 'inset 0 -2px 0 0 var(--chakra-colors-blue-500)'
				: undefined;

	const rowStyle: CSSProperties = {
		opacity: isBeingDragged ? 0.4 : 1,
		transition: 'opacity 120ms ease, box-shadow 120ms ease',
		boxShadow: indicatorShadow,
	};

	const filename = filenameFromUrl( row.link );
	const urlPlaceholder = i18n.imagesUrl || 'URL or slug';

	return (
		<Box
			ref={ cardRef }
			py={ 2.5 }
			px={ 1 }
			draggable={ draggable }
			onDragStart={ handleDragStart }
			onDragEnter={ handleDragEnter }
			onDragOver={ handleDragOver }
			onDragLeave={ handleDragLeave }
			onDrop={ handleDrop }
			onDragEnd={ handleDragEnd }
			style={ rowStyle }
			css={ {
				'&:hover [data-row-reveal], &:focus-within [data-row-reveal]':
					{
						opacity: 1,
					},
			} }
		>
			<HStack align="center" gap={ 3 }>
				<Box
					data-row-reveal
					opacity={ 0.35 }
					transition="opacity 120ms ease"
				>
					<IconButton
						aria-label={
							i18n.imagesDragHandle || 'Drag to reorder'
						}
						size="xs"
						variant="ghost"
						color="gray.500"
						_hover={ { color: 'gray.700', bg: 'gray.100' } }
						cursor="grab"
						onPointerDown={ () => setDraggable( true ) }
						onPointerUp={ () => setDraggable( false ) }
						onKeyDown={ handleKeyDown }
						title={
							i18n.imagesDragHandle || 'Drag to reorder'
						}
					>
						<LuGripVertical />
					</IconButton>
				</Box>

				<ImagePreview src={ row.link } alt={ row.title } />

				<VStack align="stretch" gap={ 0 } flex="1" minW={ 0 }>
					<Input
						size="sm"
						variant="flushed"
						fontWeight="medium"
						placeholder={ i18n.imagesTitle || 'Image title' }
						value={ row.title }
						onChange={ ( e ) =>
							onPatch( index, { title: e.target.value } )
						}
						aria-label={ i18n.imagesTitle || 'Image title' }
					/>
					{ filename ? (
						<Text
							fontSize="11px"
							color="gray.400"
							lineHeight="1.3"
							mt={ 0.5 }
							truncate
						>
							{ filename }
						</Text>
					) : null }
				</VStack>

				<HStack
					data-row-reveal
					opacity={ 0.7 }
					transition="opacity 120ms ease"
					gap={ 2 }
					flexShrink={ 0 }
				>
					<IconButton
						size="xs"
						variant="ghost"
						color="gray.500"
						_hover={ { color: 'gray.800', bg: 'gray.100' } }
						onClick={ () => onReplace( index ) }
						aria-label={ i18n.imagesReplace || 'Replace image' }
						title={ i18n.imagesReplace || 'Replace image' }
					>
						<LuRefreshCw />
					</IconButton>
					<IconButton
						size="xs"
						variant="ghost"
						color="red.500"
						_hover={ { color: 'red.600', bg: 'red.50' } }
						onClick={ () => onRemove( index ) }
						aria-label={ i18n.imagesRemove || 'Remove image' }
						title={ i18n.imagesRemove || 'Remove image' }
					>
						<LuTrash2 />
					</IconButton>
				</HStack>
			</HStack>

			<HStack
				gap={ 3 }
				mt={ 2 }
				pl="72px"
				align="flex-end"
			>
				{ isConditionalMeta ? (
					<>
						<LabeledField
							label={ i18n.imagesMetaId || 'Meta IDs' }
							w="160px"
						>
							<Input
								variant="outline"
								size="sm"
								placeholder={ i18n.imagesMetaId || 'Meta IDs' }
								value={ row.meta_id || '' }
								onChange={ ( e ) =>
									onPatch( index, {
										meta_id: e.target.value,
									} )
								}
							/>
						</LabeledField>
						<LabeledField
							label={ urlPlaceholder }
							flex="1"
							minW={ 0 }
						>
							<Input
								variant="outline"
								size="sm"
								placeholder={ urlPlaceholder }
								value={ row.url || '' }
								onChange={ ( e ) =>
									onPatch( index, {
										url: e.target.value,
									} )
								}
							/>
						</LabeledField>
					</>
				) : (
					<>
						<LabeledField
							label={ i18n.imagesStock || 'Stock' }
							w="80px"
						>
							<Input
								variant="outline"
								size="sm"
								inputMode="numeric"
								placeholder="0"
								value={ row.stock }
								onChange={ ( e ) =>
									onPatch( index, {
										stock: e.target.value,
									} )
								}
								textAlign="right"
							/>
						</LabeledField>
						<LabeledField
							label={ i18n.imagesPrice || 'Price' }
							w="112px"
						>
							<Input
								variant="outline"
								size="sm"
								inputMode="decimal"
								placeholder="0.00"
								value={ priceAmount }
								onChange={ ( e ) =>
									patchPriceAmount( e.target.value )
								}
								textAlign="right"
							/>
						</LabeledField>
						<LabeledField
							label={ i18n.imagesPriceType || 'Type' }
							w="112px"
						>
							<NativeSelect.Root size="sm" variant="outline">
								<NativeSelect.Field
									aria-label={
										i18n.imagesPriceType || 'Type'
									}
									value={ priceType }
									onChange={ ( e ) =>
										patchPriceType(
											e.target.value as PriceType
										)
									}
								>
									<option value="fixed">
										{ i18n.imagesPriceTypeFixed ||
											'Fixed' }
									</option>
									<option value="percent">
										{ i18n.imagesPriceTypePercent ||
											'%' }
									</option>
								</NativeSelect.Field>
								<NativeSelect.Indicator />
							</NativeSelect.Root>
						</LabeledField>
						{ isImageselect ? (
							<LabeledField
								label={
									i18n.imagesDescription ||
									'Description'
								}
								flex="1"
								minW={ 0 }
							>
								<Input
									variant="outline"
									size="sm"
									placeholder={
										i18n.imagesDescription ||
										'Description'
									}
									value={ row.description || '' }
									onChange={ ( e ) =>
										onPatch( index, {
											description: e.target.value,
										} )
									}
								/>
							</LabeledField>
						) : (
							<LabeledField
								label={ urlPlaceholder }
								flex="1"
								minW={ 0 }
							>
								<Input
									variant="outline"
									size="sm"
									placeholder={ urlPlaceholder }
									value={ row.url || '' }
									onChange={ ( e ) =>
										onPatch( index, {
											url: e.target.value,
										} )
									}
								/>
							</LabeledField>
						) }
					</>
				) }
			</HStack>
		</Box>
	);
}

interface LabeledFieldProps {
	label: string;
	children: ReactNode;
	w?: string;
	flex?: string;
	minW?: number | string;
}

function LabeledField( {
	label,
	children,
	w,
	flex,
	minW,
}: LabeledFieldProps ) {
	return (
		<VStack align="stretch" gap={ 1 } w={ w } flex={ flex } minW={ minW }>
			<Text
				fontSize="xs"
				fontWeight="medium"
				color="gray.600"
				lineHeight="1"
			>
				{ label }
			</Text>
			{ children }
		</VStack>
	);
}

