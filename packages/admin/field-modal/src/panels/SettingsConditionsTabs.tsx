/**
 * Top-level Settings / Conditions / optional Conditional Repeater tabs for legacy field editors.
 */
import { Fragment, type ReactNode } from 'react';
import { Box, Tabs } from '@chakra-ui/react';
import type { I18nDict } from '../types/fieldModal';

export { shouldShowConditionalRepeaterTab } from './shouldShowConditionalRepeaterTab';

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

	const settingsTabValue = 'settings';
	const conditionsTabValue = 'conditions';
	const repeaterTabValue = 'repeater';

	const triggers = [
		{ value: settingsTabValue, label: settingsLabel, show: true },
		{
			value: conditionsTabValue,
			label: conditionsLabel,
			show: hasConditions,
		},
		{ value: repeaterTabValue, label: repeaterLabel, show: showRepeater },
	].filter( ( t ) => t.show );

	return (
		<Tabs.Root
			variant="line"
			colorPalette="blue"
			lazyMount
			defaultValue={ settingsTabValue }
		>
			<Tabs.List borderBottomColor="gray.200" mb={ 3 } gap={ 2 }>
				{ triggers.map( ( t, idx ) => (
					<Fragment key={ t.value }>
						{ idx > 0 ? (
							<Box
								aria-hidden
								alignSelf="center"
								w="1px"
								h="16px"
								bg="gray.300"
							/>
						) : null }
						<Tabs.Trigger
							value={ t.value }
							fontWeight="semibold"
							px={ 2 }
							py={ 1.5 }
						>
							{ t.label }
						</Tabs.Trigger>
					</Fragment>
				) ) }
			</Tabs.List>
			<Tabs.ContentGroup>
				<Tabs.Content value={ settingsTabValue } px={ 0 } pt={ 0 }>
					<Box w="100%">{ settings }</Box>
				</Tabs.Content>
				{ hasConditions ? (
					<Tabs.Content
						value={ conditionsTabValue }
						px={ 0 }
						pt={ 0 }
					>
						<Box w="100%">{ conditions }</Box>
					</Tabs.Content>
				) : null }
				{ showRepeater ? (
					<Tabs.Content value={ repeaterTabValue } px={ 0 } pt={ 0 }>
						<Box w="100%">{ repeater }</Box>
					</Tabs.Content>
				) : null }
			</Tabs.ContentGroup>
		</Tabs.Root>
	);
}
