import { Alert } from '@chakra-ui/react';
import type { I18nDict } from '../../types/fieldModal';

export interface SchemaUnavailableAlertProps {
	i18n: I18nDict;
}

export function SchemaUnavailableAlert( {
	i18n,
}: SchemaUnavailableAlertProps ) {
	return (
		<Alert.Root status="info">
			<Alert.Indicator />
			{ i18n.fieldModalEditorUnavailable ||
				i18n.unsupportedControl ||
				'Settings for this field could not be loaded in this editor.' }
		</Alert.Root>
	);
}
