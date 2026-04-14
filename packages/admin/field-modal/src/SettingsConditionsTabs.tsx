/**
 * Top-level Settings / Conditions tabs for field modal editors.
 */
import type { ReactNode } from 'react';
import {
	Tabs,
	TabList,
	TabPanels,
	Tab,
	TabPanel,
} from '@chakra-ui/react';
import type { I18nDict } from './types/fieldModal';

export interface SettingsConditionsTabsProps {
	i18n: I18nDict;
	hasConditions: boolean;
	settings: ReactNode;
	conditions: ReactNode;
}

export function SettingsConditionsTabs( {
	i18n,
	hasConditions,
	settings,
	conditions,
}: SettingsConditionsTabsProps ) {
	const settingsLabel = i18n.settingsTab || 'Settings';
	const conditionsLabel = i18n.conditionsTab || 'Conditions';

	if ( ! hasConditions ) {
		return settings;
	}

	return (
		<Tabs variant="line" colorScheme="blue" isLazy>
			<TabList borderBottomColor="gray.200" mb={ 3 } gap={ 1 }>
				<Tab fontWeight="semibold" px={ 1 } py={ 1.5 }>
					{ settingsLabel }
				</Tab>
				<Tab fontWeight="semibold" px={ 1 } py={ 1.5 }>
					{ conditionsLabel }
				</Tab>
			</TabList>
			<TabPanels>
				<TabPanel px={ 0 } pt={ 0 }>
					{ settings }
				</TabPanel>
				<TabPanel px={ 0 } pt={ 0 }>
					{ conditions }
				</TabPanel>
			</TabPanels>
		</Tabs>
	);
}
