/**
 * Field type catalog step: picker or empty warning.
 */
import { memo } from '@wordpress/element';
import { VStack, Alert, AlertIcon } from '@chakra-ui/react';
import { FieldTypePicker } from '../FieldTypePicker';
import type { FieldModalPickerStepProps } from '../types/fieldModal';

export type FieldPickerPanelProps = FieldModalPickerStepProps;

function FieldPickerPanelComponent( {
	i18n,
	catalogGroups,
	pickerQuery,
	onPickerQueryChange,
	onPickType,
	upsell,
	license,
}: FieldPickerPanelProps ) {
	return (
		<VStack align="stretch" spacing={ 2 }>
			{ catalogGroups.length > 0 ? (
				<FieldTypePicker
					catalogGroups={ catalogGroups }
					query={ pickerQuery }
					onQueryChange={ onPickerQueryChange }
					onPick={ onPickType }
					upsell={ upsell }
					license={ license }
					i18n={ i18n }
				/>
			) : (
				<Alert status="warning">
					<AlertIcon />
					{ i18n.noFieldTypes || 'No field types are available.' }
				</Alert>
			) }
		</VStack>
	);
}

export const FieldPickerPanel = memo( FieldPickerPanelComponent );
