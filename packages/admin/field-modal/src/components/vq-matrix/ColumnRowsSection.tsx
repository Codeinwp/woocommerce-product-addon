import {
	Box,
	Button,
	HStack,
	IconButton,
	Input,
	Text,
	VStack,
} from '@chakra-ui/react';
import { LuTrash2 } from 'react-icons/lu';
import type { I18nDict } from '../../types/fieldModal';
import { arrayMove } from '../../utils/arrayMove';
import { type MatrixColumnRow, emptyColumnRow } from '../../utils/vqMatrixData';

export interface ColumnRowsSectionProps {
	rows: MatrixColumnRow[];
	i18n: I18nDict;
	onChange: ( rows: MatrixColumnRow[] ) => void;
}

export function ColumnRowsSection( {
	rows,
	i18n,
	onChange,
}: ColumnRowsSectionProps ) {
	const updateRow = ( index: number, patch: Partial< MatrixColumnRow > ) => {
		onChange(
			rows.map( ( row, rowIndex ) =>
				rowIndex === index ? { ...row, ...patch } : row
			)
		);
	};

	return (
		<Box>
			<Text fontSize="xs" fontWeight="700" color="gray.600" mb={ 2 }>
				{ i18n.vqmatrixColumnsTitle || 'Priced Options' }
			</Text>
			<VStack align="stretch" gap={ 3 }>
				{ rows.map( ( row, index ) => (
					<Box
						key={ `column-${ index }` }
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
								flex="1 1 140px"
								minW={ 0 }
								placeholder="Option"
								value={ row.option }
								onValueChange={ ( e ) =>
									updateRow( index, {
										option: e.target.value,
									} )
								}
							/>
							<Input
								size="sm"
								flex="1 1 140px"
								minW={ 0 }
								placeholder="Image ID (URL)"
								value={ row.img_id }
								onValueChange={ ( e ) =>
									updateRow( index, {
										img_id: e.target.value,
									} )
								}
							/>
							<Input
								size="sm"
								flex="1 1 110px"
								minW={ 0 }
								placeholder="Price"
								value={ row.price }
								onValueChange={ ( e ) =>
									updateRow( index, {
										price: e.target.value,
									} )
								}
							/>
							<Input
								size="sm"
								flex="1 1 110px"
								minW={ 0 }
								placeholder="Min. Qty"
								value={ row.min }
								onValueChange={ ( e ) =>
									updateRow( index, { min: e.target.value } )
								}
							/>
							<Input
								size="sm"
								flex="1 1 110px"
								minW={ 0 }
								placeholder="Max. Qty"
								value={ row.max }
								onValueChange={ ( e ) =>
									updateRow( index, { max: e.target.value } )
								}
							/>
							<Input
								size="sm"
								flex="1 1 140px"
								minW={ 0 }
								placeholder="Option ID"
								value={ row.option_id }
								onValueChange={ ( e ) =>
									updateRow( index, {
										option_id: e.target.value,
									} )
								}
							/>
							<HStack gap={ 1 } flexShrink={ 0 }>
								<Button
									size="xs"
									variant="ghost"
									onClick={ () =>
										onChange( arrayMove( rows, index, -1 ) )
									}
									disabled={ index === 0 }
								>
									↑
								</Button>
								<Button
									size="xs"
									variant="ghost"
									onClick={ () =>
										onChange( arrayMove( rows, index, 1 ) )
									}
									disabled={ index === rows.length - 1 }
								>
									↓
								</Button>
								<IconButton
									size="xs"
									variant="ghost"
									colorPalette="red"
									onClick={ () =>
										onChange(
											rows.filter(
												( _, rowIndex ) =>
													rowIndex !== index
											)
										)
									}
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
				<Button
					size="sm"
					alignSelf="flex-start"
					onClick={ () => onChange( [ ...rows, emptyColumnRow() ] ) }
				>
					{ i18n.pairedOptionsAddRow || 'Add option' }
				</Button>
			</VStack>
		</Box>
	);
}
