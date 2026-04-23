/**
 * Variation Quantity: basic + paired options + layout/limits + display + behavior + conditions + CFR.
 */
import { VStack } from '@chakra-ui/react';
import { editorSectionIsConditions } from '../schemaTabs';
import {
	SettingsConditionsTabs,
	shouldShowConditionalRepeaterTab,
} from '../panels/SettingsConditionsTabs';
import { ConditionalRepeaterSection } from '../components/ConditionalRepeaterSection';
import { PairedQuantityEditor } from '../components/PairedQuantityEditor';
import { GroupedFieldSections } from './GroupedFieldSections';
import { LegacyAdvancedFieldStack } from './legacyAdvancedFieldStack';
import type { FieldEditorBaseProps } from '../types/fieldModal';
import type { LegacySectionConfig } from './legacyAdvancedFieldStack';

export function QuantitiesFieldEditor( {
	schema,
	values,
	onChange,
	i18n,
	ppomFieldIndex,
	modalContext,
}: FieldEditorBaseProps ) {
	const settings =
		schema && schema.settings && typeof schema.settings === 'object'
			? ( schema.settings as Record< string, Record< string, unknown > > )
			: {};
	const optMeta = settings.options;
	const optionsTitle =
		optMeta && typeof optMeta.title === 'string'
			? optMeta.title
			: 'Add options';
	const optionsDesc =
		optMeta && typeof optMeta.desc === 'string' ? optMeta.desc : '';

	const sectionsBefore: LegacySectionConfig[] = [
		{
			label: i18n.editorSectionBasic || 'Basic',
			keys: [ 'title', 'data_name', 'description' ],
			advanced: false,
		},
		{
			label: i18n.editorSectionValidation || 'Validation',
			keys: [ 'error_message' ],
			advanced: true,
		},
	];

	const sectionsAfter: LegacySectionConfig[] = [
		{
			label:
				i18n.editorSectionVariationLayout || 'Layout & quantity limits',
			keys: [ 'view_control', 'default_price', 'min_qty', 'max_qty' ],
			advanced: true,
		},
		{
			label: i18n.editorSectionDisplay || 'Display & layout',
			keys: [ 'class', 'width', 'visibility', 'visibility_role' ],
			advanced: true,
		},
		{
			label: i18n.editorSectionBehavior || 'Behavior',
			keys: [
				'desc_tooltip',
				'enable_plusminus',
				'manage_stock',
				'unlink_order_qty',
				'required',
			],
			advanced: true,
		},
		{
			label: i18n.conditionsTab || 'Conditions',
			keys: [ 'logic', 'conditions' ],
		},
	];

	const sectionsAfterSettings = sectionsAfter.filter(
		( s ) => ! editorSectionIsConditions( s )
	);
	const sectionsAfterConditions = sectionsAfter.filter( ( s ) =>
		editorSectionIsConditions( s )
	);
	const hasConditions = sectionsAfterConditions.length > 0;
	const showRepeaterTab = shouldShowConditionalRepeaterTab( modalContext );

	const settingsSections: LegacySectionConfig[] = [
		...sectionsBefore,
		...sectionsAfterSettings,
	];

	const shared = {
		schema,
		values,
		onChange,
		i18n,
		ppomFieldIndex,
		modalContext,
	};

	return (
		<SettingsConditionsTabs
			i18n={ i18n }
			hasConditions={ hasConditions }
			settings={
				<VStack align="stretch" gap={ 3 }>
					<LegacyAdvancedFieldStack
						{ ...shared }
						sections={ settingsSections }
						betweenPrimaryAndAdvanced={
							<PairedQuantityEditor
								fieldKey="options"
								title={ optionsTitle }
								description={ optionsDesc }
								values={ values }
								onChange={ onChange }
								i18n={ i18n }
							/>
						}
					/>
				</VStack>
			}
			conditions={
				<GroupedFieldSections
					{ ...shared }
					sections={ sectionsAfterConditions }
				/>
			}
			hasRepeater={ showRepeaterTab }
			repeater={
				<ConditionalRepeaterSection
					i18n={ i18n }
					modalContext={ modalContext }
					values={ values }
					onChange={ onChange }
				/>
			}
		/>
	);
}
