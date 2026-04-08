/**
 * Top-level Settings / Conditions tabs for field modal editors.
 */
import {
	Tabs,
	TabList,
	TabPanels,
	Tab,
	TabPanel,
} from '@chakra-ui/react';

/**
 * @param {Object}            props
 * @param {Object}            props.i18n
 * @param {boolean}           props.hasConditions When false, renders `settings` only (no tab chrome).
 * @param {import('react').ReactNode} props.settings
 * @param {import('react').ReactNode} props.conditions
 */
export function SettingsConditionsTabs( {
	i18n,
	hasConditions,
	settings,
	conditions,
} ) {
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
