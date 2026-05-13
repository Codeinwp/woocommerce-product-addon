/**
 * Conditional Field Repeater (Plus) — boot-gated; not driven by PHP schema tabs.
 */
import type { Dispatch, SetStateAction } from 'react';
import { ConditionalRepeaterMappingEditor } from './ConditionalRepeaterMappingEditor';
import { ProFeatureUpsellCard } from './ProFeatureUpsellCard';
import type {
	FieldRow,
	I18nDict,
	ModalContextValue,
} from '../types/fieldModal';

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
		<ProFeatureUpsellCard
			title={ title }
			description={ i18n.cfrLockedBody }
			primaryUrl={ upgradeUrl }
			primaryLabel={ i18n.cfrUpgradeCta || 'Upgrade to Pro' }
			secondaryUrl={ demoUrl }
			secondaryLabel={ i18n.cfrViewDemoLabel || 'View Demo' }
			badgeLabel={ i18n.proBadge || 'PRO' }
		/>
	);
}
