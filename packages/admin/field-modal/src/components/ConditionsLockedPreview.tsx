/**
 * Locked Conditions section: upsell card stacked over a non-interactive
 * ConditionsEditor seeded with sample data so users can see the feature.
 */
import type { Dispatch, SetStateAction } from 'react';
import { Box, VStack } from '@chakra-ui/react';
import { ConditionsEditor } from '../ConditionsEditor';
import { ProFeatureUpsellCard } from './ProFeatureUpsellCard';
import type { FieldModalLinks, FieldRow, I18nDict } from '../types/fieldModal';

const PREVIEW_VALUES: FieldRow = {
	clientId: 'preview-target',
	data_name: '',
	logic: 'on',
};

const NOOP_ON_CHANGE: Dispatch< SetStateAction< FieldRow | null > > = () =>
	undefined;

export interface ConditionsLockedPreviewProps {
	meta: Record< string, unknown >;
	i18n: I18nDict;
	links: FieldModalLinks;
}

export function ConditionsLockedPreview( {
	meta,
	i18n,
	links,
}: ConditionsLockedPreviewProps ) {
	return (
		<VStack align="stretch" gap={ 3 }>
			<ProFeatureUpsellCard
				title={ i18n.condEditorTitle || 'Conditional logic' }
				description={ i18n.condLockedBody || '' }
				primaryUrl={ links.conditionUpgradeUrl }
				primaryLabel={ i18n.cfrUpgradeCta || 'Upgrade to Pro' }
				badgeLabel={ i18n.proBadge || 'PRO' }
			/>
			<Box
				aria-disabled="true"
				opacity={ 0.55 }
				filter="grayscale(0.25)"
				css={ {
					pointerEvents: 'none',
					userSelect: 'none',
					'& *': { cursor: 'default' },
				} }
			>
				<ConditionsEditor
					meta={ meta }
					values={ PREVIEW_VALUES }
					onChange={ NOOP_ON_CHANGE }
					i18n={ i18n }
					builderFields={ [] }
					conditionsProEnabled={ true }
					links={ links }
					upsellMode={ true }
				/>
			</Box>
		</VStack>
	);
}
