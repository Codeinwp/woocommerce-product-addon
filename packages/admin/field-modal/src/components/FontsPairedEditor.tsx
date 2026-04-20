/**
 * Inline editor for Fonts Picker option rows.
 */
import {
	Box,
	Button,
	Checkbox,
	HStack,
	IconButton,
	Input,
	Text,
	VStack,
} from '@chakra-ui/react';
import { LuTrash2 } from 'react-icons/lu';
import type { Dispatch, SetStateAction } from 'react';
import type { FieldRow, I18nDict } from '../types/fieldModal';

type FontOptionRow = {
	fontfamily: string;
	fontdisplay: string;
	is_customfont: string;
};

function emptyRow(): FontOptionRow {
	return {
		fontfamily: '',
		fontdisplay: '',
		is_customfont: '',
	};
}

function normalizeFontRows( raw: unknown ): FontOptionRow[] {
	if ( Array.isArray( raw ) ) {
		const rows = raw.map( ( item ) => {
			if (
				! item ||
				typeof item !== 'object' ||
				Array.isArray( item )
			) {
				return emptyRow();
			}
			const row = item as Record< string, unknown >;
			return {
				fontfamily: String( row.fontfamily ?? '' ),
				fontdisplay: String( row.fontdisplay ?? '' ),
				is_customfont: String( row.is_customfont ?? '' ),
			};
		} );
		return rows.length > 0 ? rows : [ emptyRow() ];
	}
	if ( raw && typeof raw === 'object' && ! Array.isArray( raw ) ) {
		const entries = Object.keys( raw as object ).map( ( key ) => {
			const item = ( raw as Record< string, unknown > )[ key ];
			if (
				! item ||
				typeof item !== 'object' ||
				Array.isArray( item )
			) {
				return emptyRow();
			}
			const row = item as Record< string, unknown >;
			return {
				fontfamily: String( row.fontfamily ?? '' ),
				fontdisplay: String( row.fontdisplay ?? '' ),
				is_customfont: String( row.is_customfont ?? '' ),
			};
		} );
		return entries.length > 0 ? entries : [ emptyRow() ];
	}
	return [ emptyRow() ];
}

function serializeFontRows( rows: FontOptionRow[] ): FontOptionRow[] {
	const filtered = rows.filter(
		( row ) =>
			row.fontfamily.trim() !== '' ||
			row.fontdisplay.trim() !== '' ||
			row.is_customfont === 'on'
	);
	return filtered.length > 0 ? filtered : [];
}

export interface FontsPairedEditorProps {
	values: FieldRow;
	onChange: Dispatch< SetStateAction< FieldRow | null > >;
	i18n: I18nDict;
	title: string;
}

export function FontsPairedEditor( {
	values,
	onChange,
	i18n,
	title,
}: FontsPairedEditorProps ) {
	const rows = normalizeFontRows( values.options );

	const setRows = ( next: FontOptionRow[] ) => {
		onChange( ( prev ) => {
			if ( ! prev ) {
				return prev;
			}
			return {
				...prev,
				options: serializeFontRows( next ),
			};
		} );
	};

	const updateRow = ( index: number, patch: Partial< FontOptionRow > ) => {
		const next = rows.map( ( row, rowIndex ) =>
			rowIndex === index ? { ...row, ...patch } : row
		);
		setRows( next );
	};

	const addRow = () => {
		setRows( [ ...rows, emptyRow() ] );
	};

	const removeRow = ( index: number ) => {
		const next = rows.filter( ( _, rowIndex ) => rowIndex !== index );
		setRows( next.length > 0 ? next : [ emptyRow() ] );
	};

	const move = ( index: number, dir: -1 | 1 ) => {
		const swapIndex = index + dir;
		if ( swapIndex < 0 || swapIndex >= rows.length ) {
			return;
		}
		const next = [ ...rows ];
		const tmp = next[ index ];
		next[ index ] = next[ swapIndex ];
		next[ swapIndex ] = tmp;
		setRows( next );
	};

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
							align="center"
							gap={ 2 }
							w="full"
							overflowX="auto"
							flexWrap="wrap"
						>
							<Input
								size="sm"
								flex="1 1 180px"
								minW={ 0 }
								placeholder="Font Family"
								value={ row.fontfamily }
								onValueChange={ ( e ) =>
									updateRow( index, {
										fontfamily: e.target.value,
									} )
								}
							/>
							<Input
								size="sm"
								flex="1 1 180px"
								minW={ 0 }
								placeholder="Display As Label"
								value={ row.fontdisplay }
								onValueChange={ ( e ) =>
									updateRow( index, {
										fontdisplay: e.target.value,
									} )
								}
							/>
							<Checkbox.Root
								size="sm"
								checked={ row.is_customfont === 'on' }
								onCheckedChange={ ( details ) =>
									updateRow( index, {
										is_customfont:
											details.checked === true
											? 'on'
											: '',
									} )
								}
							>
								<Checkbox.HiddenInput />
								<Checkbox.Control>
									<Checkbox.Indicator />
								</Checkbox.Control>
								<Checkbox.Label>
									{ i18n.fontsCustomFont || 'Custom Font' }
								</Checkbox.Label>
							</Checkbox.Root>
							<HStack gap={ 1 } flexShrink={ 0 }>
								<Button
									size="xs"
									variant="ghost"
									onClick={ () => move( index, -1 ) }
									disabled={ index === 0 }
								>
									↑
								</Button>
								<Button
									size="xs"
									variant="ghost"
									onClick={ () => move( index, 1 ) }
									disabled={ index === rows.length - 1 }
								>
									↓
								</Button>
								<IconButton
									size="xs"
									variant="ghost"
									colorPalette="red"
									onClick={ () => removeRow( index ) }
									aria-label={ i18n.fontsRemove || 'Remove' }
									title={ i18n.fontsRemove || 'Remove' }
								>
									<LuTrash2 />
								</IconButton>
							</HStack>
						</HStack>
					</Box>
				) ) }
				<Button size="sm" alignSelf="flex-start" onClick={ addRow }>
					{ i18n.fontsAddRow || 'Add font family' }
				</Button>
			</VStack>
		</Box>
	);
}
