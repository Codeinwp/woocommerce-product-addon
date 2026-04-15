/**
 * Top-level Settings / Conditions / optional Conditional Repeater tabs for field modal editors.
 */
import type { ReactNode } from 'react';
import {
	Tabs,
	TabList,
	TabPanels,
	Tab,
	TabPanel,
} from '@chakra-ui/react';
import type { I18nDict, ModalContextValue } from './types/fieldModal';

/** Whether the Conditional Repeater tab should appear (Lite upsell or Plus mapping). */
export function shouldShowConditionalRepeaterTab(
	modalContext: ModalContextValue | null | undefined
): boolean {
	if ( ! modalContext ) {
		return false;
	}
	return (
		modalContext.conditionalRepeaterUnlocked === true ||
		modalContext.conditionalRepeaterShowUpsell === true
	);
}

export interface SettingsConditionsTabsProps {
	i18n: I18nDict;
	hasConditions: boolean;
	settings: ReactNode;
	conditions: ReactNode;
	/**
	 * When true with a non-null `repeater` node, adds a third tab (CFR upsell or mapping).
	 */
	hasRepeater?: boolean;
	repeater?: ReactNode;
}

export function SettingsConditionsTabs( {
	i18n,
	hasConditions,
	settings,
	conditions,
	hasRepeater = false,
	repeater = null,
}: SettingsConditionsTabsProps ) {
	const settingsLabel = i18n.settingsTab || 'Settings';
	const conditionsLabel = i18n.conditionsTab || 'Conditions';
	const repeaterLabel =
		i18n.repeaterTab || i18n.cfrSectionTitle || 'Conditional Repeater';

	const showRepeater = Boolean( hasRepeater && repeater );

	if ( ! hasConditions && ! showRepeater ) {
		return settings;
	}

	return (
		<Tabs variant="line" colorScheme="blue" isLazy>
			<TabList borderBottomColor="gray.200" mb={ 3 } gap={ 1 }>
				<Tab fontWeight="semibold" px={ 1 } py={ 1.5 }>
					{ settingsLabel }
				</Tab>
				{ hasConditions ? (
					<Tab fontWeight="semibold" px={ 1 } py={ 1.5 }>
						{ conditionsLabel }
					</Tab>
				) : null }
				{ showRepeater ? (
					<Tab fontWeight="semibold" px={ 1 } py={ 1.5 }>
						{ repeaterLabel }
					</Tab>
				) : null }
			</TabList>
			<TabPanels>
				<TabPanel px={ 0 } pt={ 0 }>
					{ settings }
				</TabPanel>
				{ hasConditions ? (
					<TabPanel px={ 0 } pt={ 0 }>
						{ conditions }
					</TabPanel>
				) : null }
				{ showRepeater ? (
					<TabPanel px={ 0 } pt={ 0 }>
						{ repeater }
					</TabPanel>
				) : null }
			</TabPanels>
		</Tabs>
	);
}
