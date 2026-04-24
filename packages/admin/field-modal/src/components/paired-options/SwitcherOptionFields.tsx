import { Input } from '@chakra-ui/react';
import type { I18nDict } from '../../types/fieldModal';
import type { PairedOptionRow } from '../../utils/pairedOptionsData';

export interface SwitcherOptionFieldsProps {
	row: PairedOptionRow;
	index: number;
	i18n: I18nDict;
	onPatch: ( index: number, patch: Partial< PairedOptionRow > ) => void;
}

export function SwitcherOptionFields( {
	row,
	index,
	i18n,
	onPatch,
}: SwitcherOptionFieldsProps ) {
	return (
		<Input
			size="sm"
			flex="1 1 0"
			minW={ 0 }
			w="auto"
			placeholder={ i18n.pairedOptionImageId || 'Image ID' }
			value={ String( row.image ?? '' ) }
			onChange={ ( e ) => onPatch( index, { image: e.target.value } ) }
		/>
	);
}
