/**
 * MVP editor for schema `options.type === 'paired'` (select, radio, etc.).
 */
import { Box, Button, Icon, Text, VStack } from '@chakra-ui/react';
import type { Dispatch, SetStateAction } from 'react';
import { LuListChecks, LuPlus } from 'react-icons/lu';
import type { FieldRow } from '../types/fieldModal';
import type { I18nDict } from '../types/fieldModal';
import { arrayMove } from '../utils/arrayMove';
import {
	type PairedOptionRow,
	type PairedOptionsVariant,
	emptyPairedOptionRow,
	normalizePairedOptionsArray,
} from '../utils/pairedOptionsData';
import { PairedOptionRowItem } from './paired-options/PairedOptionRowItem';

export type {
	PairedOptionRow,
	PairedOptionsVariant,
} from '../utils/pairedOptionsData';

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
	const rows = normalizePairedOptionsArray( values.options );
	const isEmpty = rows.length === 0;

	const setRows = ( next: PairedOptionRow[] ) => {
		onChange( ( prev ) => {
			if ( ! prev ) {
				return prev;
			}
			return { ...prev, options: next };
		} );
	};

	const updateRow = ( index: number, patch: Partial< PairedOptionRow > ) => {
		setRows(
			rows.map( ( r, i ) => ( i === index ? { ...r, ...patch } : r ) )
		);
	};

	const addRow = () => {
		setRows( [ ...rows, emptyPairedOptionRow( variant ) ] );
	};

	const removeRow = ( index: number ) => {
		setRows( rows.filter( ( _, i ) => i !== index ) );
	};

	const moveUp = ( index: number ) => setRows( arrayMove( rows, index, -1 ) );
	const moveDown = ( index: number ) =>
		setRows( arrayMove( rows, index, 1 ) );

	const helperText =
		i18n.pairedOptionsHelper ||
		'Add the choices customers can select.';
	const addLabel = i18n.pairedOptionsAddRow || 'Add option';
	const addFirstLabel =
		i18n.pairedOptionsAddFirst || 'Add your first option';
	const emptyTitle = i18n.pairedOptionsEmptyTitle || 'No options yet';
	const emptyDescription =
		i18n.pairedOptionsEmptyDescription ||
		'Options are the choices your customer picks from — like sizes, colors, or add-ons.';

	return (
		<Box
			borderWidth="1px"
			borderColor="gray.200"
			borderRadius="md"
			p={ 3 }
			bg="white"
		>
			<VStack align="flex-start" gap={ 0 } mb={ 3 }>
				<Text
					fontWeight="semibold"
					fontSize="sm"
					lineHeight="1.2"
					m={ 0 }
				>
					{ title }
				</Text>
				<Text fontSize="xs" color="gray.500" lineHeight="1.3" m={ 0 }>
					{ helperText }
				</Text>
			</VStack>

			{ isEmpty ? (
				<VStack
					align="center"
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
						{ emptyTitle }
					</Text>
					<Text
						fontSize="xs"
						color="gray.500"
						textAlign="center"
						maxW="xs"
						m={ 0 }
					>
						{ emptyDescription }
					</Text>
					<Button
						size="xs"
						colorPalette="blue"
						onClick={ addRow }
						mt={ 0.5 }
					>
						<Icon as={ LuPlus } boxSize={ 3 } mr={ 1 } />
						{ addFirstLabel }
					</Button>
				</VStack>
			) : (
				<VStack align="stretch" gap={ 2 }>
					{ rows.map( ( row, index ) => (
						<PairedOptionRowItem
							key={ index }
							row={ row }
							index={ index }
							isFirst={ index === 0 }
							isLast={ index === rows.length - 1 }
							variant={ variant }
							i18n={ i18n }
							onPatch={ updateRow }
							onMoveUp={ moveUp }
							onMoveDown={ moveDown }
							onRemove={ removeRow }
						/>
					) ) }
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
						{ addLabel }
					</Button>
				</VStack>
			) }
		</Box>
	);
}
