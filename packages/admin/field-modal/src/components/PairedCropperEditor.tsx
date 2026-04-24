/**
 * Viewport size rows for Image Cropper (paired-cropper schema type).
 */
import { Box, Button, VStack, Field, Icon, Text } from '@chakra-ui/react';
import type { Dispatch, SetStateAction } from 'react';
import { LuListChecks, LuPlus } from 'react-icons/lu';
import type { FieldRow, I18nDict } from '../types/fieldModal';
import {
	type CropperViewportRow,
	emptyCropperRow,
	normalizePairedCropperOptions,
	serializePairedCropperOptions,
} from '../utils/pairedCropperData';
import { useDraggableRows } from './draggable-options/DraggableOptionRow';
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
		setRows( rows.filter( ( _, i ) => i !== index ) );
	};

	const labelPh =
		i18n.cropperViewportLabel || i18n.pairedOptionLabel || 'Label';
	const widthPh = i18n.cropperViewportWidth || 'Width';
	const heightPh = i18n.cropperViewportHeight || 'Height';
	const pricePh =
		i18n.cropperViewportPrice ||
		i18n.pairedOptionPrice ||
		'Price (optional)';
	const dragHandlers = useDraggableRows( rows, setRows );
	const isEmpty = rows.length === 0;

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
				<VStack align="stretch" gap={ 2 } w="full">
					{ isEmpty ? (
						<VStack
							align="center"
							w="full"
							gap={ 1.5 }
							py={ 4 }
							px={ 4 }
							borderWidth="1px"
							borderStyle="dashed"
							borderColor="gray.300"
							borderRadius="md"
							bg="gray.50"
						>
							<Icon
								as={ LuListChecks }
								boxSize={ 5 }
								color="gray.400"
								aria-hidden
							/>
							<Text
								fontWeight="semibold"
								fontSize="sm"
								color="gray.700"
								m={ 0 }
							>
								{ i18n.pairedOptionsEmptyTitle ||
									'No options yet' }
							</Text>
							<Text
								fontSize="xs"
								color="gray.500"
								textAlign="center"
								maxW="xs"
								m={ 0 }
							>
								{ i18n.pairedOptionsEmptyDescription ||
									'Options are the choices your customer picks from.' }
							</Text>
						</VStack>
					) : (
						rows.map( ( row, index ) => (
							<CropperViewportRowItem
								key={ index }
								row={ row }
								index={ index }
								i18n={ i18n }
								labelPlaceholder={ labelPh }
								widthPlaceholder={ widthPh }
								heightPlaceholder={ heightPh }
								pricePlaceholder={ pricePh }
								onPatch={ updateRow }
								onMoveUp={ dragHandlers.onMoveUp }
								onMoveDown={ dragHandlers.onMoveDown }
								onRemove={ removeRow }
								dragIndex={ dragHandlers.dragIndex }
								onDragStart={ dragHandlers.onDragStart }
								onDragEnd={ dragHandlers.onDragEnd }
								onDrop={ dragHandlers.onDrop }
							/>
						) )
					) }
					<Button
						size="sm"
						variant="outline"
						borderStyle="dashed"
						color="gray.600"
						width="full"
						mt={ 1 }
						onClick={ addRow }
					>
						<Icon as={ LuPlus } boxSize={ 3.5 } mr={ 1 } />
						{ i18n.cropperViewportAddRow ||
							i18n.pairedOptionsAddRow ||
							'Add viewport' }
					</Button>
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
