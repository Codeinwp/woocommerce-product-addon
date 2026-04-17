/**
 * Classic paired-palettes / paired-pricematrix rows: option, price, label, id, isfixed.
 */
import {
	Steps,
	Box,
	Button,
	Checkbox,
	HStack,
	Input,
	Text,
	VStack,
	IconButton,
} from '@chakra-ui/react';
import { LuTrash2 } from 'react-icons/lu';
import type { Dispatch, SetStateAction } from 'react';
import type { FieldRow } from '../types/fieldModal';
import type { I18nDict } from '../types/fieldModal';

export type MatrixOptionRow = Record< string, unknown >;

function normalizeOptionsArray( raw: unknown ): MatrixOptionRow[] {
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

function isFixedChecked( row: MatrixOptionRow ): boolean {
	const v = row.isfixed;
	return v === 'on' || v === true || v === '1' || v === 1;
}

export interface PairedMatrixOptionsEditorProps {
	values: FieldRow;
	onChange: Dispatch< SetStateAction< FieldRow | null > >;
	i18n: I18nDict;
	title: string;
}

export function PairedMatrixOptionsEditor( {
	values,
	onChange,
	i18n,
	title,
}: PairedMatrixOptionsEditorProps ) {
	const rows = normalizeOptionsArray( values.options );

	const setRows = ( next: MatrixOptionRow[] ) => {
		onChange( ( prev ) => {
			if ( ! prev ) {
				return prev;
			}
			return { ...prev, options: next };
		} );
	};

	const updateRow = ( index: number, patch: Partial< MatrixOptionRow > ) => {
		const next = rows.map( ( r, i ) =>
			i === index ? { ...r, ...patch } : r
		);
		setRows( next );
	};

	const addRow = () => {
		setRows( [
			...rows,
			{
				option: '',
				price: '',
				label: '',
				id: '',
				isfixed: '',
			},
		] );
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

	const toggleFixed = ( index: number, checked: boolean ) => {
		updateRow( index, { isfixed: checked ? 'on' : '' } );
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
								flex="1 1 120px"
								minW={ 0 }
								placeholder={
									i18n.pairedMatrixOption || 'Option'
								}
								value={ String( row.option ?? '' ) }
								onValueChange={ ( e ) =>
									updateRow( index, {
										option: e.target.value,
									} )
								}
							/>
							<Input
								size="sm"
								flex="1 1 100px"
								minW={ 0 }
								placeholder={
									i18n.pairedMatrixPrice || 'Price'
								}
								value={ String( row.price ?? '' ) }
								onValueChange={ ( e ) =>
									updateRow( index, { price: e.target.value } )
								}
							/>
							<Input
								size="sm"
								flex="1 1 100px"
								minW={ 0 }
								placeholder={
									i18n.pairedMatrixLabel || 'Label'
								}
								value={ String( row.label ?? '' ) }
								onValueChange={ ( e ) =>
									updateRow( index, { label: e.target.value } )
								}
							/>
							<Input
								size="sm"
								flex="1 1 100px"
								minW={ 0 }
								placeholder={
									i18n.pairedMatrixOptionId || 'Option ID'
								}
								value={ String( row.id ?? '' ) }
								onValueChange={ ( e ) =>
									updateRow( index, { id: e.target.value } )
								}
							/>
							<Checkbox.Root
								size="sm"
								onCheckedChange={ ( e ) =>
									toggleFixed( index, e.target.checked )
								}
								checked={ isFixedChecked( row ) }
							><Checkbox.HiddenInput /><Checkbox.Control><Checkbox.Indicator /></Checkbox.Control><Checkbox.Label>
                                { i18n.pairedMatrixFixed || 'Fixed' }
                            </Checkbox.Label></Checkbox.Root>
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
