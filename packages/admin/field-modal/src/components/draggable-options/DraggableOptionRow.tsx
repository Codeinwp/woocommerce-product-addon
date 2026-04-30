import { Box, HStack, IconButton, type StackProps } from '@chakra-ui/react';
import {
	useRef,
	useState,
	type CSSProperties,
	type DragEvent,
	type KeyboardEvent,
	type ReactNode,
} from 'react';
import { LuGripVertical, LuTrash2 } from 'react-icons/lu';
import { arrayMove } from '../../utils/arrayMove';
import { arrayReorder } from '../../utils/arrayReorder';

export interface DraggableRowsHandlers {
	dragIndex: number | null;
	onDragStart: ( index: number ) => void;
	onDragEnd: () => void;
	onDrop: ( slot: number ) => void;
	onMoveUp: ( index: number ) => void;
	onMoveDown: ( index: number ) => void;
}

export function useDraggableRows< T >(
	rows: T[],
	setRows: ( next: T[] ) => void
): DraggableRowsHandlers {
	const [ dragIndex, setDragIndex ] = useState< number | null >( null );
	const dragIndexRef = useRef< number | null >( null );

	const onDragStart = ( index: number ) => {
		dragIndexRef.current = index;
		setDragIndex( index );
	};

	const onDragEnd = () => {
		dragIndexRef.current = null;
		setDragIndex( null );
	};

	const onDrop = ( slot: number ) => {
		const from = dragIndexRef.current;
		if ( from !== null ) {
			setRows( arrayReorder( rows, from, slot ) );
		}
		dragIndexRef.current = null;
		setDragIndex( null );
	};

	return {
		dragIndex,
		onDragStart,
		onDragEnd,
		onDrop,
		onMoveUp: ( index: number ) => setRows( arrayMove( rows, index, -1 ) ),
		onMoveDown: ( index: number ) => setRows( arrayMove( rows, index, 1 ) ),
	};
}

export interface DraggableOptionRowProps {
	children: ReactNode;
	index: number;
	dragIndex: number | null;
	onDragStart: ( index: number ) => void;
	onDragEnd: () => void;
	onDrop: ( slot: number ) => void;
	onMoveUp: ( index: number ) => void;
	onMoveDown: ( index: number ) => void;
	onRemove: ( index: number ) => void;
	dragLabel: string;
	removeLabel: string;
	hideRemove?: boolean;
	align?: StackProps[ 'align' ];
	flexWrap?: StackProps[ 'flexWrap' ];
	overflowX?: StackProps[ 'overflowX' ];
}

export function DraggableOptionRow( {
	children,
	index,
	dragIndex,
	onDragStart,
	onDragEnd,
	onDrop,
	onMoveUp,
	onMoveDown,
	onRemove,
	dragLabel,
	removeLabel,
	hideRemove = false,
	align = 'center',
	flexWrap,
	overflowX = 'auto',
}: DraggableOptionRowProps ) {
	const [ draggable, setDraggable ] = useState( false );
	const [ dropEdge, setDropEdge ] = useState< 'above' | 'below' | null >(
		null
	);
	const rowRef = useRef< HTMLDivElement | null >( null );

	const isBeingDragged = dragIndex === index;
	const isDragActive = dragIndex !== null;

	const handleDragStart = ( e: DragEvent< HTMLDivElement > ) => {
		if ( rowRef.current ) {
			e.dataTransfer.effectAllowed = 'move';
			e.dataTransfer.setData( 'text/plain', String( index ) );
			e.dataTransfer.setDragImage( rowRef.current, 12, 12 );
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

	return (
		<Box
			ref={ rowRef }
			borderWidth="1px"
			borderColor="gray.100"
			borderRadius="md"
			p={ 2 }
			draggable={ draggable }
			onDragStart={ handleDragStart }
			onDragEnter={ handleDragEnter }
			onDragOver={ handleDragOver }
			onDragLeave={ () => setDropEdge( null ) }
			onDrop={ handleDrop }
			onDragEnd={ handleDragEnd }
			style={ rowStyle }
		>
			<HStack
				align={ align }
				gap={ 2 }
				w="full"
				overflowX={ overflowX }
				flexWrap={ flexWrap }
			>
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
				{ children }
				<IconButton
					size="xs"
					variant="ghost"
					colorPalette="red"
					onClick={ () => onRemove( index ) }
					aria-label={ removeLabel }
					title={ removeLabel }
					flexShrink={ 0 }
					visibility={ hideRemove ? 'hidden' : 'visible' }
					pointerEvents={ hideRemove ? 'none' : undefined }
					tabIndex={ hideRemove ? -1 : undefined }
				>
					<LuTrash2 />
				</IconButton>
			</HStack>
		</Box>
	);
}
