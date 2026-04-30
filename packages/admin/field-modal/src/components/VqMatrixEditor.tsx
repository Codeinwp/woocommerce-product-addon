/**
 * Inline editor for Variation Matrix columns and rows.
 */
import { Box, Text, VStack } from '@chakra-ui/react';
import type { Dispatch, SetStateAction } from 'react';
import type { FieldRow, I18nDict } from '../types/fieldModal';
import {
	type MatrixColumnRow,
	type MatrixRowRow,
	normalizeColumnRows,
	normalizeRowRows,
	serializeColumnRows,
	serializeRowRows,
} from '../utils/vqMatrixData';
import { ColumnRowsSection } from './vq-matrix/ColumnRowsSection';
import { MatrixRowsSection } from './vq-matrix/MatrixRowsSection';

export interface VqMatrixEditorProps {
	values: FieldRow;
	onChange: Dispatch< SetStateAction< FieldRow | null > >;
	i18n: I18nDict;
	title: string;
}

export function VqMatrixEditor( {
	values,
	onChange,
	i18n,
	title,
}: VqMatrixEditorProps ) {
	const columnRows = normalizeColumnRows( values.options );
	const rowRows = normalizeRowRows( values.row_options );

	const setColumnRows = ( next: MatrixColumnRow[] ) => {
		onChange( ( prev ) => {
			if ( ! prev ) {
				return prev;
			}
			return {
				...prev,
				options: serializeColumnRows( next ),
			};
		} );
	};

	const setRowRows = ( next: MatrixRowRow[] ) => {
		onChange( ( prev ) => {
			if ( ! prev ) {
				return prev;
			}
			return {
				...prev,
				row_options: serializeRowRows( next ),
			};
		} );
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
			<VStack align="stretch" gap={ 4 }>
				<ColumnRowsSection
					rows={ columnRows }
					i18n={ i18n }
					onChange={ setColumnRows }
				/>
				<MatrixRowsSection
					rows={ rowRows }
					i18n={ i18n }
					onChange={ setRowRows }
				/>
			</VStack>
		</Box>
	);
}
