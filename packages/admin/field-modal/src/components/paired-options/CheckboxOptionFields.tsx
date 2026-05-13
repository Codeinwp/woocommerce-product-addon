import { Input } from '@chakra-ui/react';
import type { I18nDict } from '../../types/fieldModal';
import type { PairedOptionRow } from '../../utils/pairedOptionsData';

export interface CheckboxOptionFieldsProps {
	row: PairedOptionRow;
	index: number;
	i18n: I18nDict;
	onPatch: ( index: number, patch: Partial< PairedOptionRow > ) => void;
}

export function CheckboxOptionFields( {
	row,
	index,
	i18n,
	onPatch,
}: CheckboxOptionFieldsProps ) {
	return (
		<>
			<Input
				size="sm"
				flex="1 1 0"
				minW={ 0 }
				w="auto"
				placeholder={ i18n.pairedOptionDiscount || 'Discount' }
				value={ String( row.discount ?? '' ) }
				onChange={ ( e ) =>
					onPatch( index, { discount: e.target.value } )
				}
			/>
			<Input
				size="sm"
				flex="1 1 0"
				minW={ 0 }
				w="auto"
				placeholder={ i18n.pairedOptionTooltip || 'Tooltip' }
				value={ String( row.tooltip ?? '' ) }
				onChange={ ( e ) =>
					onPatch( index, { tooltip: e.target.value } )
				}
			/>
		</>
	);
}
