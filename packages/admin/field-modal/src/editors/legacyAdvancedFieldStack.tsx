/**
 * Splits typed-editor section configs into primary vs advanced (collapsible).
 */
import type { ReactNode } from 'react';
import { useMemo } from '@wordpress/element';
import { VStack } from '@chakra-ui/react';
import { AdvancedSettingsPanel } from '../components/AdvancedSettingsPanel';
import { GroupedFieldSections } from './GroupedFieldSections';
import type { GroupedFieldSectionsProps } from '../types/fieldModal';

export type LegacySectionConfig = {
	label: string;
	keys: string[];
	advanced?: boolean;
};

export type LegacyAdvancedFieldStackProps = Omit<
	GroupedFieldSectionsProps,
	'sections'
> & {
	sections: LegacySectionConfig[];
	/**
	 * Optional content rendered inside the advanced panel after the advanced section
	 * at `advancedInsertionAfterIndex` (0 = after the first advanced section card).
	 * Use to embed paired editors (e.g. matrices) between grouped schema sections
	 * inside the same collapsible region — avoids a second toggle from a second stack.
	 */
	advancedInsertionAfterIndex?: number;
	advancedInsertion?: ReactNode;
	/**
	 * Rendered after primary (non-advanced) sections and before the advanced toggle.
	 * Use when a custom editor must stay visible even while advanced settings are collapsed
	 * (e.g. cropper viewport, image/audio lists) — avoids hiding it inside the advanced panel.
	 */
	betweenPrimaryAndAdvanced?: ReactNode;
};

function splitSections( sections: LegacySectionConfig[] ): {
	primary: Array< { label: string; keys: string[] } >;
	advanced: Array< { label: string; keys: string[] } >;
} {
	const primary = sections
		.filter( ( s ) => s.advanced !== true )
		.map( ( { label, keys } ) => ( { label, keys } ) );
	const advanced = sections
		.filter( ( s ) => s.advanced === true )
		.map( ( { label, keys } ) => ( { label, keys } ) );
	return { primary, advanced };
}

/** Renders grouped settings: always-visible sections first, then “Show advanced settings” for the rest. */
export function LegacyAdvancedFieldStack( {
	sections,
	advancedInsertionAfterIndex,
	advancedInsertion,
	betweenPrimaryAndAdvanced,
	i18n,
	...shared
}: LegacyAdvancedFieldStackProps ) {
	const { primary, advanced } = useMemo(
		() => splitSections( sections ),
		[ sections ]
	);
	const showLabel = i18n.showAdvancedSettings || 'Show advanced settings';
	const hideLabel = i18n.hideAdvancedSettings || 'Hide advanced settings';

	const insertionIdx =
		advancedInsertion !== null &&
		advancedInsertion !== undefined &&
		typeof advancedInsertionAfterIndex === 'number' &&
		advancedInsertionAfterIndex >= 0
			? advancedInsertionAfterIndex
			: null;

	let advancedPanelBody: ReactNode = null;
	if ( advanced.length > 0 ) {
		if ( insertionIdx === null ) {
			advancedPanelBody = (
				<GroupedFieldSections
					{ ...shared }
					i18n={ i18n }
					sections={ advanced }
					variant="flat"
				/>
			);
		} else {
			const before = advanced.slice( 0, insertionIdx + 1 );
			const after = advanced.slice( insertionIdx + 1 );
			advancedPanelBody = (
				<>
					{ before.length > 0 && (
						<GroupedFieldSections
							{ ...shared }
							i18n={ i18n }
							sections={ before }
							variant="flat"
						/>
					) }
					{ advancedInsertion }
					{ after.length > 0 && (
						<GroupedFieldSections
							{ ...shared }
							i18n={ i18n }
							sections={ after }
							variant="flat"
						/>
					) }
				</>
			);
		}
	}

	return (
		<VStack align="stretch" gap={ 3 }>
			{ primary.length > 0 && (
				<GroupedFieldSections
					{ ...shared }
					i18n={ i18n }
					sections={ primary }
				/>
			) }
			{ betweenPrimaryAndAdvanced }
			{ advanced.length > 0 && (
				<AdvancedSettingsPanel
					showLabel={ showLabel }
					hideLabel={ hideLabel }
				>
					{ advancedPanelBody }
				</AdvancedSettingsPanel>
			) }
		</VStack>
	);
}
