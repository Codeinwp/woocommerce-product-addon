/**
 * Inline editor for pre-uploaded audio/video (audio field type).
 */
import { Box, Button, Text, VStack } from '@chakra-ui/react';
import type { Dispatch, SetStateAction } from 'react';
import type { FieldRow, I18nDict } from '../types/fieldModal';
import { arrayMove } from '../utils/arrayMove';
import {
	type AudioOptionRow,
	normalizeAudioArray,
} from '../utils/audiosSelectData';
import { AudioRowItem } from './audios-select/AudioRowItem';

export type { AudioOptionRow } from '../utils/audiosSelectData';

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

export interface AudiosSelectEditorProps {
	values: FieldRow;
	onChange: Dispatch< SetStateAction< FieldRow | null > >;
	i18n: I18nDict;
	title: string;
}

export function AudiosSelectEditor( {
	values,
	onChange,
	i18n,
	title,
}: AudiosSelectEditorProps ) {
	const rows = normalizeAudioArray( values.audio );

	const setRows = ( next: AudioOptionRow[] ) => {
		onChange( ( prev ) => {
			if ( ! prev ) {
				return prev;
			}
			return { ...prev, audio: next };
		} );
	};

	const updateRow = (
		index: number,
		patch: Partial< AudioOptionRow >
	) => {
		setRows(
			rows.map( ( r, i ) => ( i === index ? { ...r, ...patch } : r ) )
		);
	};

	const addFromMedia = () => {
		if ( ! window.wp?.media ) {
			return;
		}

		const frame = window.wp.media( {
			title: i18n.audioMediaTitle || 'Choose audio or video',
			library: { type: 'audio,video' },
			button: { text: i18n.audioMediaButton || 'Select' },
			multiple: true,
		} );

		frame.on( 'select', () => {
			const attachments = frame
				.state()
				.get( 'selection' )
				.toJSON() as Array< Record< string, unknown > >;

			const newRows: AudioOptionRow[] = attachments.map(
				( attachment ) => {
					const url = String( attachment.url ?? '' );
					const mediaTitle = String(
						attachment.title ?? attachment.filename ?? ''
					);
					return {
						link: url,
						id: String( attachment.id ?? '' ),
						title: mediaTitle,
						price: '',
					};
				}
			);

			setRows( [ ...rows, ...newRows ] );
		} );

		frame.open();
	};

	const removeRow = ( index: number ) => {
		setRows( rows.filter( ( _, i ) => i !== index ) );
	};

	const moveUp = ( index: number ) => setRows( arrayMove( rows, index, -1 ) );
	const moveDown = ( index: number ) => setRows( arrayMove( rows, index, 1 ) );

	const pricePlaceholder =
		i18n.audioPricePlaceholder || 'Price (fix or %)';

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
				onClick={ addFromMedia }
			>
				{ i18n.audioSelectUpload || 'Select Audio/Video' }
			</Button>
			{ rows.length === 0 && (
				<Text fontSize="xs" color="gray.500">
					{ i18n.audioEmptyState ||
						'No audio or video selected. Use the button above to add files from the media library.' }
				</Text>
			) }
			<VStack align="stretch" gap={ 3 }>
				{ rows.map( ( row, index ) => (
					<AudioRowItem
						key={ index }
						row={ row }
						index={ index }
						isFirst={ index === 0 }
						isLast={ index === rows.length - 1 }
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
