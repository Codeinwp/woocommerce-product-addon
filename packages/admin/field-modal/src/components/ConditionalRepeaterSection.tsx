/**
 * Conditional Field Repeater (Plus) — boot-gated; not driven by PHP schema tabs.
 */
import { Steps, Alert, Link, Text, VStack } from '@chakra-ui/react';
import type { Dispatch, SetStateAction } from 'react';
import { ConditionalRepeaterMappingEditor } from './ConditionalRepeaterMappingEditor';
import type { FieldRow } from '../types/fieldModal';
import type { I18nDict, ModalContextValue } from '../types/fieldModal';

export interface ConditionalRepeaterSectionProps {
	i18n: I18nDict;
	modalContext: ModalContextValue | null | undefined;
	values?: FieldRow;
	onChange?: Dispatch< SetStateAction< FieldRow | null > >;
}

export function ConditionalRepeaterSection( {
	i18n,
	modalContext,
	values,
	onChange,
}: ConditionalRepeaterSectionProps ) {
	const unlocked = modalContext?.conditionalRepeaterUnlocked === true;
	const showUpsell = modalContext?.conditionalRepeaterShowUpsell === true;
	const links = modalContext?.links || {};

	const title = i18n.cfrSectionTitle || 'Conditional Repeater';
	const upgradeUrl = links.cfrUpgradeUrl || '';
	const demoUrl = links.cfrViewDemoUrl || '';

	if ( unlocked && values && onChange ) {
		return (
			<ConditionalRepeaterMappingEditor
				values={ values }
				onChange={ onChange }
				i18n={ i18n }
				modalContext={ modalContext }
			/>
		);
	}

	if ( ! showUpsell ) {
		return null;
	}

	return (
		<Alert.Root status="warning" variant="subtle" borderRadius="md">
			<Alert.Indicator />
			<VStack align="stretch" gap={ 2 }>
				<Text fontWeight="semibold" fontSize="sm">
					{ title } (PRO)
				</Text>
				<Text fontSize="sm" lineHeight="1.5">
					{ i18n.cfrLockedBody ||
						'Upgrade to PPOM Pro Plus or higher to enable Conditional Repeater.' }
				</Text>
				{ upgradeUrl ? (
					<Link
						href={ upgradeUrl }
						color="blue.600"
						fontWeight="semibold"
						target="_blank"
						rel="noopener noreferrer"
					>
						{ i18n.cfrUpgradeCta || 'Upgrade to Pro' }
					</Link>
				) : null }
				{ demoUrl ? (
					<Link
						href={ demoUrl }
						fontSize="sm"
						target="_blank"
						rel="noopener noreferrer"
					>
						{ i18n.cfrViewDemoLabel || 'View demo' }
					</Link>
				) : null }
			</VStack>
		</Alert.Root>
	);
}
