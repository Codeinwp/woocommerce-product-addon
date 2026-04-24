import { Box, HStack, IconButton, Input } from '@chakra-ui/react';
import {
	useRef,
	useState,
	type CSSProperties,
	type DragEvent,
	type KeyboardEvent,
} from 'react';
import { LuGripVertical, LuTrash2 } from 'react-icons/lu';
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
	variant: PairedOptionsVariant;
	i18n: I18nDict;
	onPatch: ( index: number, patch: Partial< PairedOptionRow > ) => void;
	onMoveUp: ( index: number ) => void;
	onMoveDown: ( index: number ) => void;
	onRemove: ( index: number ) => void;
	/** Index currently being dragged (null = none). Used to dim the source. */
	dragIndex: number | null;
	onDragStart: ( index: number ) => void;
	onDragEnd: () => void;
	/** Slot = insertion index (0..rows.length). Upper half → index, lower half → index+1. */
	onDrop: ( slot: number ) => void;
}

export function PairedOptionRowItem( {
	row,
	index,
	variant,
	i18n,
	onPatch,
	onMoveUp,
	onMoveDown,
	onRemove,
	dragIndex,
	onDragStart,
	onDragEnd,
	onDrop,
}: PairedOptionRowItemProps ) {
	const showCheckboxExtras = variant === 'checkbox';
	const showSwitcherExtras = variant === 'switcher';

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

	const showDropEdge = isDragActive && ! isBeingDragged && dropEdge !== null;
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

	const dragLabel = i18n.pairedOptionsDragHandle || 'Drag to reorder';

	return (
		<Box
			ref={ cardRef }
			borderWidth="1px"
			borderColor="gray.100"
			borderRadius="md"
			p={ 2 }
			draggable={ draggable }
			onDragStart={ handleDragStart }
			onDragEnter={ handleDragEnter }
			onDragOver={ handleDragOver }
			onDragLeave={ handleDragLeave }
			onDrop={ handleDrop }
			onDragEnd={ handleDragEnd }
			style={ rowStyle }
		>
			<HStack align="center" gap={ 2 } w="full" overflowX="auto">
				<IconButton
					aria-label={ dragLabel }
					size="xs"
					variant="ghost"
					color="gray.500"
					_hover={ { color: 'gray.700', bg: 'gray.100' } }
					cursor="grab"
					onPointerDown={ () => setDraggable( true ) }
					onPointerUp={ () => setDraggable( false ) }
					onKeyDown={ handleKeyDown }
					title={ dragLabel }
					flexShrink={ 0 }
				>
					<LuGripVertical />
				</IconButton>
				<Input
					size="sm"
					flex="1 1 0"
					minW={ 0 }
					w="auto"
					placeholder={ i18n.pairedOptionLabel || 'Option' }
					value={ String( row.option ?? row.title ?? '' ) }
					onChange={ ( e ) =>
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
					onChange={ ( e ) =>
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
					onChange={ ( e ) =>
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
					onChange={ ( e ) =>
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
					onChange={ ( e ) =>
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
				<IconButton
					size="xs"
					variant="ghost"
					colorPalette="red"
					onClick={ () => onRemove( index ) }
					aria-label={ i18n.pairedOptionsRemove || 'Remove' }
					title={ i18n.pairedOptionsRemove || 'Remove' }
					flexShrink={ 0 }
				>
					<LuTrash2 />
				</IconButton>
			</HStack>
		</Box>
	);
}
