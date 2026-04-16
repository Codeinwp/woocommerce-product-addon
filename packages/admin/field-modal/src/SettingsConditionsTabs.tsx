/**
 * Top-level Settings / Conditions / optional Conditional Repeater tabs for field modal editors.
 */
import type { ReactNode } from 'react';
import { Box, Tabs } from '@chakra-ui/react';
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

	const settingsTabValue = 'settings';
	const conditionsTabValue = 'conditions';
	const repeaterTabValue = 'repeater';

	return (
		<Tabs.Root
			variant="line"
			colorPalette="blue"
			lazyMount
			defaultValue={ settingsTabValue }
		>
			<Tabs.List borderBottomColor="gray.200" mb={ 3 } gap={ 1 }>
				<Tabs.Trigger
					value={ settingsTabValue }
					fontWeight="semibold"
					px={ 1 }
					py={ 1.5 }
				>
					{ settingsLabel }
				</Tabs.Trigger>
				{ hasConditions ? (
					<Tabs.Trigger
						value={ conditionsTabValue }
						fontWeight="semibold"
						px={ 1 }
						py={ 1.5 }
					>
						{ conditionsLabel }
					</Tabs.Trigger>
				) : null }
				{ showRepeater ? (
					<Tabs.Trigger
						value={ repeaterTabValue }
						fontWeight="semibold"
						px={ 1 }
						py={ 1.5 }
					>
						{ repeaterLabel }
					</Tabs.Trigger>
				) : null }
			</Tabs.List>
			<Tabs.ContentGroup>
				<Tabs.Content value={ settingsTabValue } px={ 0 } pt={ 0 }>
					<Box w="100%">{ settings }</Box>
				</Tabs.Content>
				{ hasConditions ? (
					<Tabs.Content value={ conditionsTabValue } px={ 0 } pt={ 0 }>
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
