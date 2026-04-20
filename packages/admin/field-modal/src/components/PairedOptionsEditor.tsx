/**
 * MVP editor for schema `options.type === 'paired'` (select, radio, etc.).
 */
import { Steps, Box, Button, HStack, Input, Text, VStack, IconButton } from '@chakra-ui/react';
import { LuTrash2 } from 'react-icons/lu';
import type { Dispatch, SetStateAction } from 'react';
import type { FieldRow } from '../types/fieldModal';
import type { I18nDict } from '../types/fieldModal';

export type PairedOptionRow = Record< string, unknown >;

function normalizeOptionsArray( raw: unknown ): PairedOptionRow[] {
	if ( Array.isArray( raw ) ) {
		return raw.map( ( o ) =>
			o && typeof o === 'object' && ! Array.isArray( o )
				? { ...( o as Record< string, unknown > ) }
				: {}
		);
	}
	if ( raw && typeof raw === 'object' && ! Array.isArray( raw ) ) {
		return Object.keys( raw as object ).map( ( k ) => {
			const v = ( raw as Record< string, unknown > )[ k ];
			if ( v && typeof v === 'object' && ! Array.isArray( v ) ) {
				return { ...( v as Record< string, unknown > ) };
			}
			return { option: k };
		} );
	}
	return [];
}

export type PairedOptionsVariant =
	| 'select'
	| 'radio'
	| 'checkbox'
	| 'switcher';

export interface PairedOptionsEditorProps {
	values: FieldRow;
	onChange: Dispatch< SetStateAction< FieldRow | null > >;
	i18n: I18nDict;
	title: string;
	/** `checkbox` adds discount + tooltip columns (classic paired UI). */
	variant?: PairedOptionsVariant;
}

export function PairedOptionsEditor( {
	values,
	onChange,
	i18n,
	title,
	variant = 'select',
}: PairedOptionsEditorProps ) {
	const rows = normalizeOptionsArray( values.options );
	const showCheckboxExtras = variant === 'checkbox';
	const showSwitcherExtras = variant === 'switcher';

	const setRows = ( next: PairedOptionRow[] ) => {
		onChange( ( prev ) => {
			if ( ! prev ) {
				return prev;
			}
			return { ...prev, options: next };
		} );
	};

	const updateRow = ( index: number, patch: Partial< PairedOptionRow > ) => {
		const next = rows.map( ( r, i ) =>
			i === index ? { ...r, ...patch } : r
		);
		setRows( next );
	};

	const addRow = () => {
		const base: PairedOptionRow = {
			option: '',
			price: '',
			weight: '',
			stock: '',
			id: '',
		};
		if ( showCheckboxExtras ) {
			base.discount = '';
			base.tooltip = '';
		}
		if ( showSwitcherExtras ) {
			base.image = '';
		}
		setRows( [ ...rows, base ] );
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
						>
							<Input
								size="sm"
								flex="1 1 0"
								minW={ 0 }
								w="auto"
								placeholder={
									i18n.pairedOptionLabel || 'Option'
								}
								value={ String( row.option ?? row.title ?? '' ) }
								onValueChange={ ( e ) =>
									updateRow( index, {
										option: e.target.value,
									} )
								}
							/>
							<Input
								size="sm"
								flex="1 1 0"
								minW={ 0 }
								w="auto"
								placeholder={
									i18n.pairedOptionPrice || 'Price'
								}
								value={ String( row.price ?? '' ) }
								onValueChange={ ( e ) =>
									updateRow( index, { price: e.target.value } )
								}
							/>
							{ showCheckboxExtras ? (
								<>
									<Input
										size="sm"
										flex="1 1 0"
										minW={ 0 }
										w="auto"
										placeholder={
											i18n.pairedOptionDiscount ||
											'Discount'
										}
										value={ String( row.discount ?? '' ) }
										onValueChange={ ( e ) =>
											updateRow( index, {
												discount: e.target.value,
											} )
										}
									/>
									<Input
										size="sm"
										flex="1 1 0"
										minW={ 0 }
										w="auto"
										placeholder={
											i18n.pairedOptionTooltip ||
											'Tooltip'
										}
										value={ String( row.tooltip ?? '' ) }
										onValueChange={ ( e ) =>
											updateRow( index, {
												tooltip: e.target.value,
											} )
										}
									/>
								</>
							) : null }
							<Input
								size="sm"
								flex="1 1 0"
								minW={ 0 }
								w="auto"
								placeholder={
									i18n.pairedOptionWeight || 'Weight'
								}
								value={ String( row.weight ?? '' ) }
								onValueChange={ ( e ) =>
									updateRow( index, {
										weight: e.target.value,
									} )
								}
							/>
							<Input
								size="sm"
								flex="1 1 0"
								minW={ 0 }
								w="auto"
								placeholder={
									i18n.pairedOptionStock || 'Stock'
								}
								value={ String( row.stock ?? '' ) }
								onValueChange={ ( e ) =>
									updateRow( index, { stock: e.target.value } )
								}
							/>
							<Input
								size="sm"
								flex="1 1 0"
								minW={ 0 }
								w="auto"
								placeholder={
									showSwitcherExtras
										? i18n.pairedOptionId ||
										  'Option ID'
										: i18n.pairedOptionImageId ||
										  'Image ID'
								}
								value={ String( row.id ?? row.images ?? '' ) }
								onValueChange={ ( e ) =>
									updateRow( index, {
										id: e.target.value,
									} )
								}
							/>
							{ showSwitcherExtras ? (
								<Input
									size="sm"
									flex="1 1 0"
									minW={ 0 }
									w="auto"
									placeholder={
										i18n.pairedOptionImageId ||
										'Image ID'
									}
									value={ String( row.image ?? '' ) }
									onValueChange={ ( e ) =>
										updateRow( index, {
											image: e.target.value,
										} )
									}
								/>
							) : null }
							<HStack gap={ 1 } flexShrink={ 0 }>
								<Button
									size="xs"
									variant="ghost"
									aria-label={
										i18n.pairedOptionsMoveUp || 'Up'
									}
									onClick={ () => move( index, -1 ) }
									disabled={ index === 0 }
								>
									↑
								</Button>
								<Button
									size="xs"
									variant="ghost"
									aria-label={
										i18n.pairedOptionsMoveDown || 'Down'
									}
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
									aria-label={
										i18n.pairedOptionsRemove || 'Remove'
									}
									title={
										i18n.pairedOptionsRemove || 'Remove'
									}
								>
									<LuTrash2 />
								</IconButton>
							</HStack>
						</HStack>
					</Box>
				) ) }
				<Button size="sm" alignSelf="flex-start" onClick={ addRow }>
					{ i18n.pairedOptionsAddRow || 'Add option' }
				</Button>
			</VStack>
        </Box>
    );
}
