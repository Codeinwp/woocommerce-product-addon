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
import { type MatrixRowRow, emptyRowRow } from '../../utils/vqMatrixData';

export interface MatrixRowsSectionProps {
	rows: MatrixRowRow[];
	i18n: I18nDict;
	onChange: ( rows: MatrixRowRow[] ) => void;
}

export function MatrixRowsSection( {
	rows,
	i18n,
	onChange,
}: MatrixRowsSectionProps ) {
	const updateRow = ( index: number, patch: Partial< MatrixRowRow > ) => {
		onChange(
			rows.map( ( row, rowIndex ) =>
				rowIndex === index ? { ...row, ...patch } : row
			)
		);
	};

	return (
		<Box>
			<Text fontSize="xs" fontWeight="700" color="gray.600" mb={ 2 }>
				{ i18n.vqmatrixRowsTitle || 'Matrix Rows' }
			</Text>
			<VStack align="stretch" gap={ 3 }>
				{ rows.map( ( row, index ) => (
					<Box
						key={ `row-${ index }` }
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
								flex="1 1 180px"
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
								flex="1 1 180px"
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
					onClick={ () => onChange( [ ...rows, emptyRowRow() ] ) }
				>
					{ i18n.vqmatrixAddRow || 'Add row option' }
				</Button>
			</VStack>
		</Box>
	);
}
