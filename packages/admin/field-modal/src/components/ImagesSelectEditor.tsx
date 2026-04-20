/**
 * Inline editor for pre-uploaded images (image + imageselect field types).
 */
import {
	Steps,
	Box,
	Button,
	HStack,
	Input,
	Text,
	VStack,
	Image as ChakraImage,
	IconButton,
} from '@chakra-ui/react';
import { LuTrash2 } from 'react-icons/lu';
import type { Dispatch, SetStateAction } from 'react';
import type { FieldRow, I18nDict } from '../types/fieldModal';

export type ImageOptionRow = {
	link: string;
	id: string | number;
	title: string;
	price: string;
	stock: string;
	meta_id?: string;
	url?: string;
	description?: string;
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

function normalizeImagesArray( raw: unknown ): ImageOptionRow[] {
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
						stock: String(
							( o as Record< string, unknown > ).stock ?? ''
						),
						meta_id: String(
							( o as Record< string, unknown > ).meta_id ?? ''
						),
						url: String(
							( o as Record< string, unknown > ).url ?? ''
						),
						description: String(
							( o as Record< string, unknown > ).description ?? ''
						),
				  }
				: {
						link: '',
						id: '',
						title: '',
						price: '',
						stock: '',
						meta_id: '',
						url: '',
						description: '',
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
					stock: String( obj.stock ?? '' ),
					meta_id: String( obj.meta_id ?? '' ),
					url: String( obj.url ?? '' ),
					description: String( obj.description ?? '' ),
				};
			}
			return {
				link: k,
				id: '',
				title: '',
				price: '',
				stock: '',
				meta_id: '',
				url: '',
				description: '',
			};
		} );
	}
	return [];
}

export interface ImagesSelectEditorProps {
	values: FieldRow;
	onChange: Dispatch< SetStateAction< FieldRow | null > >;
	i18n: I18nDict;
	title: string;
	variant?: 'image' | 'imageselect' | 'conditional-meta';
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
		const next = rows.map( ( r, i ) =>
			i === index ? { ...r, ...patch } : r
		);
		setRows( next );
	};

	const addImagesFromMedia = () => {
		if ( ! window.wp?.media ) {
			return;
		}

		const frame = window.wp.media( {
			title: i18n.imagesMediaTitle || 'Choose Images',
			library: { type: 'image' },
			button: { text: i18n.imagesMediaButton || 'Select' },
			multiple: true,
		} );

		frame.on( 'select', () => {
			const attachments = frame
				.state()
				.get( 'selection' )
				.toJSON() as Array< Record< string, unknown > >;

			const newRows: ImageOptionRow[] = attachments.map(
				( attachment ) => {
					const url = String(
						attachment.url ?? attachment.url ?? ''
					);
					const imageTitle = String(
						attachment.title ?? attachment.filename ?? ''
					);
					return {
						link: url,
						id: String( attachment.id ?? '' ),
						title: imageTitle,
						price: '',
						stock: '',
						meta_id: '',
						url: '',
						description: '',
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

	const isImageselect = variant === 'imageselect';
	const isConditionalMeta = variant === 'conditional-meta';
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
								{ row.link ? (
									<ChakraImage
										src={ row.link }
										alt={ row.title }
										fallbackSrc="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24'%3E%3Cpath fill='%23ccc' d='M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z'/%3E%3C/svg%3E"
										boxSize="36px"
										objectFit="cover"
										borderRadius="sm"
									/>
								) : (
									<Text
										fontSize="xs"
										color="gray.400"
										textAlign="center"
									>
										No img
									</Text>
								) }
							</Box>

							<VStack gap={ 1.5 } flex="1" minW={ 0 }>
								<HStack gap={ 2 } w="full">
									<Input
										size="sm"
										flex="1 1 0"
										minW={ 0 }
										placeholder={
											i18n.imagesTitle || 'Title'
										}
										value={ row.title }
										onValueChange={ ( e ) =>
											updateRow( index, {
												title: e.target.value,
											} )
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
												updateRow( index, {
													meta_id:
														e.target.value,
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
												updateRow( index, {
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
											placeholder={
												i18n.imagesUrl || 'URL'
											}
											value={ row.url || '' }
											onValueChange={ ( e ) =>
												updateRow( index, {
													url: e.target.value,
												} )
											}
										/>
									) : (
										<Input
											size="sm"
											flex="1 1 0"
											minW={ 0 }
											placeholder={
												i18n.imagesStock || 'Stock'
											}
											value={ row.stock }
											onValueChange={ ( e ) =>
												updateRow( index, {
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
												updateRow( index, {
													description:
														e.target.value,
												} )
											}
										/>
									) : ! isConditionalMeta ? (
										<Input
											size="sm"
											flex="1 1 0"
											minW={ 0 }
											placeholder={
												i18n.imagesUrl || 'URL'
											}
											value={ row.url || '' }
											onValueChange={ ( e ) =>
												updateRow( index, {
													url: e.target.value,
												} )
											}
										/>
									) : null }
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
										i18n.imagesMoveUp || 'Move up'
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
										i18n.imagesMoveDown || 'Move down'
									}
									onClick={ () => move( index, 1 ) }
									disabled={ index === rows.length - 1 }
								>
									&#8595;
								</Button>
								<IconButton
									size="xs"
									variant="ghost"
									colorPalette="red"
									onClick={ () => removeRow( index ) }
									aria-label={
										i18n.imagesRemove || 'Remove'
									}
									title={ i18n.imagesRemove || 'Remove' }
								>
									<LuTrash2 />
								</IconButton>
							</HStack>
						</HStack>
					</Box>
				) ) }
			</VStack>
        </Box>
    );
}
