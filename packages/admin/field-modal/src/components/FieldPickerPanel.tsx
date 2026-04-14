/**
 * Field type catalog step: back/cancel + picker or empty warning.
 */
import { memo } from '@wordpress/element';
import { Button, VStack, HStack, Alert, AlertIcon } from '@chakra-ui/react';
import { FieldTypePicker } from '../FieldTypePicker';
import type { FieldModalPickerStepProps } from '../types/fieldModal';

export type FieldPickerPanelProps = FieldModalPickerStepProps;

function FieldPickerPanelComponent( {
	modalEntry,
	i18n,
	catalogGroups,
	pickerQuery,
	onPickerQueryChange,
	onBackOrCancel,
	onPickType,
	upsell,
	license,
}: FieldPickerPanelProps ) {
	return (
		<VStack align="stretch" spacing={ 2 }>
			<HStack justify="flex-end">
				<Button size="sm" variant="outline" onClick={ onBackOrCancel }>
					{ modalEntry === 'picker'
						? i18n.cancelFieldPicker || 'Cancel'
						: i18n.back || 'Back' }
				</Button>
			</HStack>
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
