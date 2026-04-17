/**
 * Loud failure surface for unsupported third-party slugs (replaces silent schema fallback).
 */
import { Alert, Button, Text, VStack } from '@chakra-ui/react';
import type { I18nDict } from '../types/fieldModal';
import { openLegacyFieldModal } from '../schemaSettingControl';

export interface UnknownSlugPanelProps {
	slug: string;
	i18n: I18nDict;
	ppomFieldIndex: number;
}

export function UnknownSlugPanel( {
	slug,
	i18n,
	ppomFieldIndex,
}: UnknownSlugPanelProps ) {
	const title =
		i18n.unknownFieldTypeTitle || 'This field type is not supported here yet.';
	const hint =
		i18n.unknownFieldTypeHint ||
		'Use the classic PPOM editor for this field, or contact the extension author.';
	return (
		<VStack align="stretch" gap={ 3 }>
			<Alert.Root status="warning" borderRadius="md">
				<Alert.Indicator />
				<VStack align="stretch" gap={ 1 }>
					<Text fontWeight="semibold">{ title }</Text>
					<Text fontSize="sm" color="gray.700">
						{ hint }
					</Text>
					<Text fontSize="xs" color="gray.500">
						Type: { slug || '—' }
					</Text>
				</VStack>
			</Alert.Root>
			{ ppomFieldIndex > 0 ? (
				<Button
					size="sm"
					variant="outline"
					colorPalette="blue"
					alignSelf="flex-start"
					onClick={ () => openLegacyFieldModal( ppomFieldIndex ) }
				>
					{ i18n.openLegacyModal || 'Open classic field editor' }
				</Button>
			) : null }
		</VStack>
	);
}
