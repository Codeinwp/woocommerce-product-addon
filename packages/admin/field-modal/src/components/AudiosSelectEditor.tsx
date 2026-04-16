/**
 * Inline editor for pre-uploaded audio/video (audio field type).
 */
import { Steps, Box, Button, HStack, Input, Text, VStack } from '@chakra-ui/react';
import type { Dispatch, SetStateAction } from 'react';
import type { FieldRow, I18nDict } from '../types/fieldModal';

export type AudioOptionRow = {
	link: string;
	id: string;
	title: string;
	price: string;
};

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

function normalizeAudioArray( raw: unknown ): AudioOptionRow[] {
	if ( Array.isArray( raw ) ) {
		return raw.map( ( o ) =>
			o && typeof o === 'object' && ! Array.isArray( o )
				? {
						link: String(
							( o as Record< string, unknown > ).link ?? ''
						),
						id: String(
							( o as Record< string, unknown > ).id ?? ''
						),
						title: String(
							( o as Record< string, unknown > ).title ?? ''
						),
						price: String(
							( o as Record< string, unknown > ).price ?? ''
						),
				  }
				: {
						link: '',
						id: '',
						title: '',
						price: '',
				  }
		);
	}
	if ( raw && typeof raw === 'object' && ! Array.isArray( raw ) ) {
		return Object.keys( raw as object ).map( ( k ) => {
			const v = ( raw as Record< string, unknown > )[ k ];
			if ( v && typeof v === 'object' && ! Array.isArray( v ) ) {
				const obj = v as Record< string, unknown >;
				return {
					link: String( obj.link ?? '' ),
					id: String( obj.id ?? '' ),
					title: String( obj.title ?? '' ),
					price: String( obj.price ?? '' ),
				};
			}
			return {
				link: k,
				id: '',
				title: '',
				price: '',
			};
		} );
	}
	return [];
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
		const next = rows.map( ( r, i ) =>
			i === index ? { ...r, ...patch } : r
		);
		setRows( next );
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

	const move = ( index: number, dir: -1 | 1 ) => {
		const j = index + dir;
		if ( j < 0 || j >= rows.length ) {
			return;
		}
		const next = [ ...rows ];
		const t = next[ index ];
		next[ index ] = next[ j ];
		next[ j ] = t;
		setRows( next );
	};

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
					<Box
						key={ index }
						borderWidth="1px"
						borderColor="gray.100"
						borderRadius="md"
						p={ 2 }
					>
						<HStack
							align="flex-start"
							gap={ 2 }
							w="full"
							overflowX="auto"
						>
							<Box
								flexShrink={ 0 }
								w="40px"
								h="40px"
								borderRadius="sm"
								overflow="hidden"
								bg="gray.50"
								borderWidth="1px"
								borderColor="gray.200"
								display="flex"
								alignItems="center"
								justifyContent="center"
							>
								<Text fontSize="lg" aria-hidden="true">
									&#9835;
								</Text>
							</Box>

							<VStack gap={ 1.5 } flex="1" minW={ 0 }>
								<HStack gap={ 2 } w="full">
									<Input
										size="sm"
										flex="1 1 0"
										minW={ 0 }
										placeholder={
											i18n.audioTitlePlaceholder ||
											'Title'
										}
										value={ row.title }
										onValueChange={ ( e ) =>
											updateRow( index, {
												title: e.target.value,
											} )
										}
									/>
									<Input
										size="sm"
										flex="1 1 0"
										minW={ 0 }
										placeholder={ pricePlaceholder }
										value={ row.price }
										onValueChange={ ( e ) =>
											updateRow( index, {
												price: e.target.value,
											} )
										}
									/>
								</HStack>
							</VStack>

							<HStack
								gap={ 1 }
								flexShrink={ 0 }
								alignSelf="center"
							>
								<Button
									size="xs"
									variant="ghost"
									aria-label={
										i18n.audioMoveUp || 'Move up'
									}
									onClick={ () => move( index, -1 ) }
									disabled={ index === 0 }
								>
									&#8593;
								</Button>
								<Button
									size="xs"
									variant="ghost"
									aria-label={
										i18n.audioMoveDown || 'Move down'
									}
									onClick={ () => move( index, 1 ) }
									disabled={ index === rows.length - 1 }
								>
									&#8595;
								</Button>
								<Button
									size="xs"
									variant="ghost"
									colorPalette="red"
									onClick={ () => removeRow( index ) }
								>
									{ i18n.audioRemove || 'Remove' }
								</Button>
							</HStack>
						</HStack>
					</Box>
				) ) }
			</VStack>
        </Box>
    );
}
