/**
 * Viewport size rows for Image Cropper (paired-cropper schema type).
 */
import { Box, Button, VStack, Field } from '@chakra-ui/react';
import type { Dispatch, SetStateAction } from 'react';
import type { FieldRow, I18nDict } from '../types/fieldModal';
import { arrayMove } from '../utils/arrayMove';
import {
	type CropperViewportRow,
	emptyCropperRow,
	normalizePairedCropperOptions,
	serializePairedCropperOptions,
} from '../utils/pairedCropperData';
import { CropperViewportRowItem } from './paired-cropper/CropperViewportRowItem';

export type { CropperViewportRow } from '../utils/pairedCropperData';
export {
	normalizePairedCropperOptions,
	serializePairedCropperOptions,
} from '../utils/pairedCropperData';

const labelProps = {
	fontSize: '13px',
	fontWeight: '600',
	color: 'gray.800',
	mb: 0.5,
};

const helperTextProps = {
	mt: 1,
	fontSize: 'xs',
	color: 'gray.600',
	lineHeight: '1.45',
};

export interface PairedCropperEditorProps {
	fieldKey: string;
	title: string;
	description: string;
	values: FieldRow;
	onChange: Dispatch< SetStateAction< FieldRow | null > >;
	i18n: I18nDict;
}

export function PairedCropperEditor( {
	fieldKey,
	title,
	description,
	values,
	onChange,
	i18n,
}: PairedCropperEditorProps ) {
	const raw = values[ fieldKey ];
	const rows = normalizePairedCropperOptions( raw );

	const setRows = ( next: CropperViewportRow[] ) => {
		onChange( ( prev ) => {
			if ( ! prev ) {
				return prev;
			}
			return {
				...prev,
				[ fieldKey ]: serializePairedCropperOptions( next ),
			};
		} );
	};

	const updateRow = (
		index: number,
		patch: Partial< CropperViewportRow >
	) => {
		setRows(
			rows.map( ( r, i ) => ( i === index ? { ...r, ...patch } : r ) )
		);
	};

	const addRow = () => {
		setRows( [ ...rows, emptyCropperRow() ] );
	};

	const removeRow = ( index: number ) => {
		if ( rows.length <= 1 ) {
			setRows( [ emptyCropperRow() ] );
			return;
		}
		setRows( rows.filter( ( _, i ) => i !== index ) );
	};

	const moveUp = ( index: number ) => setRows( arrayMove( rows, index, -1 ) );
	const moveDown = ( index: number ) => setRows( arrayMove( rows, index, 1 ) );

	const labelPh =
		i18n.cropperViewportLabel || i18n.pairedOptionLabel || 'Label';
	const widthPh = i18n.cropperViewportWidth || 'Width';
	const heightPh = i18n.cropperViewportHeight || 'Height';
	const pricePh =
		i18n.cropperViewportPrice ||
		i18n.pairedOptionPrice ||
		'Price (optional)';

	return (
		<Field.Root w="full">
			<Field.Label { ...labelProps }>{ title }</Field.Label>
			<Box
				w="full"
				borderWidth="1px"
				borderColor="gray.200"
				borderRadius="md"
				p={ 3 }
				bg="white"
			>
				<Button size="sm" colorPalette="blue" mb={ 3 } onClick={ addRow }>
					{ i18n.cropperViewportAddRow ||
						i18n.pairedOptionsAddRow ||
						'Add viewport' }
				</Button>

				<VStack align="stretch" gap={ 3 }>
					{ rows.map( ( row, index ) => (
						<CropperViewportRowItem
							key={ index }
							row={ row }
							index={ index }
							isFirst={ index === 0 }
							isLast={ index === rows.length - 1 }
							i18n={ i18n }
							labelPlaceholder={ labelPh }
							widthPlaceholder={ widthPh }
							heightPlaceholder={ heightPh }
							pricePlaceholder={ pricePh }
							onPatch={ updateRow }
							onMoveUp={ moveUp }
							onMoveDown={ moveDown }
							onRemove={ removeRow }
						/>
					) ) }
				</VStack>
			</Box>
			{ description ? (
				<Field.HelperText { ...helperTextProps }>
					{ description }
				</Field.HelperText>
			) : null }
		</Field.Root>
	);
}
