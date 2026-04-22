/**
 * Inline editor for pre-uploaded images (image + imageselect field types).
 */
import { Box, Button, Text, VStack } from '@chakra-ui/react';
import { useEffect, useRef, type Dispatch, type SetStateAction } from 'react';
import type { FieldRow, I18nDict } from '../types/fieldModal';
import { arrayMove } from '../utils/arrayMove';
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
				on: ( event: string, cb: ( arg: unknown ) => void ) => {
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

	const updateRow = (
		index: number,
		patch: Partial< ImageOptionRow >
	) => {
		setRows(
			rows.map( ( r, i ) => ( i === index ? { ...r, ...patch } : r ) )
		);
	};

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

	const addImagesFromMedia = () => {
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

		const frame = window.wp.media( {
			title: i18n.imagesMediaTitle || 'Choose Images',
			library: { type: 'image' },
			button: { text: i18n.imagesMediaButton || 'Select' },
			multiple: true,
		} );

		frame.on( 'open', acquireLock );
		frame.on( 'close', releaseLock );

		frame.on( 'select', () => {
			const attachments = frame
				.state()
				.get( 'selection' )
				.toJSON() as Array< Record< string, unknown > >;

			const newRows: ImageOptionRow[] = attachments.map(
				( attachment ) => {
					const url = String( attachment.url ?? '' );
					const imageTitle = String(
						attachment.title ?? attachment.filename ?? ''
					);
					return {
						...emptyImageRow(),
						link: url,
						id: String( attachment.id ?? '' ),
						title: imageTitle,
					};
				}
			);

			setRows( [ ...rows, ...newRows ] );
		} );

		try {
			acquireLock();
			frame.open();
		} catch ( error ) {
			releaseLock();
			throw error;
		}
	};

	const removeRow = ( index: number ) => {
		setRows( rows.filter( ( _, i ) => i !== index ) );
	};

	const moveUp = ( index: number ) => setRows( arrayMove( rows, index, -1 ) );
	const moveDown = ( index: number ) => setRows( arrayMove( rows, index, 1 ) );

	const isImageselect = variant === 'imageselect';
	const pricePlaceholder = isImageselect
		? i18n.imagesPrice || 'Price'
		: i18n.imagesPricePlaceholder || 'Price (fix or %)';

	return (
		<Box
			borderWidth="1px"
			borderColor="gray.200"
			borderRadius="md"
			p={ 3 }
			bg="white"
		>
			<Text fontWeight="semibold" fontSize="sm" mb={ 3 }>
				{ title }
			</Text>
			<Button
				size="sm"
				colorPalette="blue"
				mb={ 3 }
				onClick={ addImagesFromMedia }
			>
				{ i18n.imagesSelectUpload || 'Select/Upload Image' }
			</Button>
			{ rows.length === 0 && (
				<Text fontSize="xs" color="gray.500">
					{ i18n.imagesEmptyState ||
						'No images selected. Click the button above to add images.' }
				</Text>
			) }
			<VStack align="stretch" gap={ 3 }>
				{ rows.map( ( row, index ) => (
					<ImageRowItem
						key={ index }
						row={ row }
						index={ index }
						isFirst={ index === 0 }
						isLast={ index === rows.length - 1 }
						variant={ variant }
						i18n={ i18n }
						pricePlaceholder={ pricePlaceholder }
						onPatch={ updateRow }
						onMoveUp={ moveUp }
						onMoveDown={ moveDown }
						onRemove={ removeRow }
					/>
				) ) }
			</VStack>
		</Box>
	);
}
