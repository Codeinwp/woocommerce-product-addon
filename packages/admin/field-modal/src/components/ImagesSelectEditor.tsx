/**
 * Inline editor for pre-uploaded images (image + imageselect field types).
 * Mini media-manager: empty-state dropzone, 2-row image cards, drag-to-reorder.
 */
import { Box, Button, HStack, Text, VStack } from '@chakra-ui/react';
import {
	useEffect,
	useRef,
	useState,
	type Dispatch,
	type SetStateAction,
} from 'react';
import { LuImagePlus, LuLibrary, LuPlus } from 'react-icons/lu';
import type { FieldRow, I18nDict } from '../types/fieldModal';
import { arrayReorder } from '../utils/arrayReorder';
import {
	type ImageOptionRow,
	type ImagesSelectVariant,
	emptyImageRow,
	normalizeImagesArray,
} from '../utils/imagesSelectData';
import { lockMediaFrame, unlockMediaFrame } from '../utils/mediaLock';
import { ImageRowItem } from './images-select/ImageRowItem';

export type {
	ImageOptionRow,
	ImagesSelectVariant,
} from '../utils/imagesSelectData';

declare global {
	interface Window {
		wp?: {
			media?: ( settings: Record< string, unknown > ) => {
				on: (
					event: string,
					cb: ( arg: unknown ) => void
				) => {
					open: () => void;
				};
				state: () => {
					get: ( key: string ) => {
						toJSON: () => Array< Record< string, unknown > >;
					};
				};
				open: () => void;
			};
		};
	}
}

export interface ImagesSelectEditorProps {
	values: FieldRow;
	onChange: Dispatch< SetStateAction< FieldRow | null > >;
	i18n: I18nDict;
	title: string;
	variant?: ImagesSelectVariant;
}

type MediaFrame = ReturnType<
	NonNullable< NonNullable< Window[ 'wp' ] >[ 'media' ] >
>;

type AttachmentLike = {
	url?: unknown;
	id?: unknown;
	title?: unknown;
	filename?: unknown;
};

function toImageRowFromAttachment(
	attachment: AttachmentLike
): ImageOptionRow {
	const url = String( attachment.url ?? '' );
	const imageTitle = String( attachment.title ?? attachment.filename ?? '' );
	return {
		...emptyImageRow(),
		link: url,
		id: String( attachment.id ?? '' ),
		title: imageTitle,
	};
}

export function ImagesSelectEditor( {
	values,
	onChange,
	i18n,
	title,
	variant = 'image',
}: ImagesSelectEditorProps ) {
	const rows = normalizeImagesArray( values.images );

	const setRows = ( next: ImageOptionRow[] ) => {
		onChange( ( prev ) => {
			if ( ! prev ) {
				return prev;
			}
			return { ...prev, images: next };
		} );
	};

	const updateRow = ( index: number, patch: Partial< ImageOptionRow > ) => {
		setRows(
			rows.map( ( r, i ) => ( i === index ? { ...r, ...patch } : r ) )
		);
	};

	const removeRow = ( index: number ) => {
		setRows( rows.filter( ( _, i ) => i !== index ) );
	};

	const moveUp = ( index: number ) => {
		if ( index <= 0 ) return;
		const next = [ ...rows ];
		[ next[ index - 1 ], next[ index ] ] = [
			next[ index ],
			next[ index - 1 ],
		];
		setRows( next );
	};

	const moveDown = ( index: number ) => {
		if ( index >= rows.length - 1 ) return;
		const next = [ ...rows ];
		[ next[ index + 1 ], next[ index ] ] = [
			next[ index ],
			next[ index + 1 ],
		];
		setRows( next );
	};

	const reorder = ( from: number, slot: number ) =>
		setRows( arrayReorder( rows, from, slot ) );

	// Release the lock if this editor unmounts while a media frame is open.
	const holdingLockRef = useRef( false );
	useEffect(
		() => () => {
			if ( holdingLockRef.current ) {
				holdingLockRef.current = false;
				unlockMediaFrame();
			}
		},
		[]
	);

	const openMediaFrame = ( options: {
		multiple: boolean;
		frameTitle?: string;
		buttonText?: string;
		onSelect: ( attachments: AttachmentLike[] ) => void;
	} ) => {
		if ( ! window.wp?.media ) {
			return;
		}

		const acquireLock = () => {
			if ( ! holdingLockRef.current ) {
				holdingLockRef.current = true;
				lockMediaFrame();
			}
		};

		const releaseLock = () => {
			if ( holdingLockRef.current ) {
				holdingLockRef.current = false;
				unlockMediaFrame();
			}
		};

		const frame: MediaFrame = window.wp.media( {
			title:
				options.frameTitle || i18n.imagesMediaTitle || 'Choose Images',
			library: { type: 'image' },
			button: {
				text: options.buttonText || i18n.imagesMediaButton || 'Select',
			},
			multiple: options.multiple,
		} );

		frame.on( 'open', acquireLock );
		frame.on( 'close', releaseLock );

		frame.on( 'select', () => {
			const attachments = frame
				.state()
				.get( 'selection' )
				.toJSON() as AttachmentLike[];
			options.onSelect( attachments );
		} );

		try {
			acquireLock();
			frame.open();
		} catch ( error ) {
			releaseLock();
			throw error;
		}
	};

	const addImagesFromMedia = () => {
		openMediaFrame( {
			multiple: true,
			onSelect: ( attachments ) => {
				setRows( [
					...rows,
					...attachments.map( toImageRowFromAttachment ),
				] );
			},
		} );
	};

	const replaceImageAt = ( index: number ) => {
		openMediaFrame( {
			multiple: false,
			frameTitle: i18n.imagesReplaceTitle || 'Replace image',
			buttonText: i18n.imagesReplaceButton || 'Replace',
			onSelect: ( attachments ) => {
				const next = attachments[ 0 ];
				if ( ! next ) return;
				updateRow( index, {
					link: String( next.url ?? '' ),
					id: String( next.id ?? '' ),
				} );
			},
		} );
	};

	// Drag-and-drop reorder state. `dragIndexRef` mirrors state so handleDrop
	// can read the source index synchronously even if React hasn't committed
	// the re-render from `setDragIndex` before the drop event fires.
	const [ dragIndex, setDragIndex ] = useState< number | null >( null );
	const dragIndexRef = useRef< number | null >( null );

	const handleDragStart = ( idx: number ) => {
		dragIndexRef.current = idx;
		setDragIndex( idx );
	};
	const handleDragEnd = () => {
		dragIndexRef.current = null;
		setDragIndex( null );
	};
	const handleDrop = ( slot: number ) => {
		const from = dragIndexRef.current;
		if ( from !== null ) {
			reorder( from, slot );
		}
		dragIndexRef.current = null;
		setDragIndex( null );
	};

	const isEmpty = rows.length === 0;
	const sectionHelper =
		i18n.imagesSectionHelper ||
		'Upload or choose the images that can be shown for this field.';

	return (
		<Box
			bg="white"
			borderRadius="md"
			px={ { base: 2.5, md: 3 } }
			py={ 2.5 }
			minW={ 0 }
		>
			<HStack
				justify="space-between"
				align="center"
				gap={ 3 }
				pb={ 1 }
				mb={ 2 }
				borderBottomWidth="1px"
				borderBottomColor="gray.100"
			>
				<VStack align="start" gap={ 0 } flex="1" minW={ 0 }>
					<Text
						as="h3"
						fontSize="11px"
						fontWeight="700"
						color="gray.500"
						textTransform="uppercase"
						letterSpacing="0.08em"
					>
						{ title }
					</Text>
					<Text fontSize="xs" color="gray.500" mt={ 0.5 }>
						{ sectionHelper }
					</Text>
				</VStack>
				{ ! isEmpty ? (
					<Button
						size="sm"
						variant="ghost"
						color="blue.600"
						_hover={ { bg: 'blue.50' } }
						onClick={ addImagesFromMedia }
					>
						<LuPlus />
						{ i18n.imagesAddMore || 'Add image' }
					</Button>
				) : null }
			</HStack>

			{ isEmpty ? (
				<EmptyState
					i18n={ i18n }
					onUpload={ addImagesFromMedia }
					onBrowse={ addImagesFromMedia }
				/>
			) : (
				<Box mt={ 2 }>
					{ rows.map( ( row, index ) => (
						<Box
							key={ index }
							borderBottomWidth={
								index === rows.length - 1 ? 0 : '1px'
							}
							borderBottomColor="gray.100"
						>
							<ImageRowItem
								row={ row }
								index={ index }
								isFirst={ index === 0 }
								isLast={ index === rows.length - 1 }
								variant={ variant }
								i18n={ i18n }
								onPatch={ updateRow }
								onMoveUp={ moveUp }
								onMoveDown={ moveDown }
								onRemove={ removeRow }
								onReplace={ replaceImageAt }
								dragIndex={ dragIndex }
								onDragStart={ handleDragStart }
								onDragEnd={ handleDragEnd }
								onDrop={ handleDrop }
							/>
						</Box>
					) ) }
					<Text fontSize="11px" color="gray.400" mt={ 2 }>
						{ rows.length === 1
							? i18n.imagesFooterSingular || '1 image'
							: (
									i18n.imagesFooterPlural || '{count} images'
							  ).replace( '{count}', String( rows.length ) ) }
					</Text>
				</Box>
			) }
		</Box>
	);
}

interface EmptyStateProps {
	i18n: I18nDict;
	onUpload: () => void;
	onBrowse: () => void;
}

function EmptyState( { i18n, onUpload, onBrowse }: EmptyStateProps ) {
	return (
		<Box
			mt={ 3 }
			borderWidth="1.5px"
			borderStyle="dashed"
			borderColor="gray.300"
			borderRadius="lg"
			bg="gray.50"
			px={ 6 }
			py={ 8 }
		>
			<VStack gap={ 3 } textAlign="center">
				<Box color="gray.400">
					<LuImagePlus size={ 32 } />
				</Box>
				<VStack gap={ 1 }>
					<Text fontWeight="semibold" fontSize="sm" color="gray.700">
						{ i18n.imagesEmptyTitle || 'Add images to this field' }
					</Text>
					<Text fontSize="xs" color="gray.500" maxW="380px">
						{ i18n.imagesEmptyDescription ||
							'Upload from your device or pick from the media library.' }
					</Text>
				</VStack>
				<HStack gap={ 2 }>
					<Button size="sm" colorPalette="blue" onClick={ onUpload }>
						<LuImagePlus />
						{ i18n.imagesUploadButton || 'Upload images' }
					</Button>
					<Button size="sm" variant="outline" onClick={ onBrowse }>
						<LuLibrary />
						{ i18n.imagesLibraryButton || 'Choose from library' }
					</Button>
				</HStack>
				<Text fontSize="xs" color="gray.400">
					{ i18n.imagesEmptySupport ||
						'JPG, PNG, WebP or SVG · 800×800 recommended' }
				</Text>
			</VStack>
		</Box>
	);
}
