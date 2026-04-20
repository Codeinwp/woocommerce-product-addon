/**
 * Select Option Quantity (Pro): paired options + option/qty labels + select-like layout.
 */
import { VStack } from '@chakra-ui/react';
import { editorSectionIsConditions } from '../schemaTabs';
import {
	SettingsConditionsTabs,
	shouldShowConditionalRepeaterTab,
} from '../panels/SettingsConditionsTabs';
import { ConditionalRepeaterSection } from '../components/ConditionalRepeaterSection';
import { PairedOptionsEditor } from '../components/PairedOptionsEditor';
import { GroupedFieldSections } from './GroupedFieldSections';
import { LegacyAdvancedFieldStack } from './legacyAdvancedFieldStack';
import type { FieldEditorBaseProps } from '../types/fieldModal';
import type { LegacySectionConfig } from './legacyAdvancedFieldStack';

export function SelectQtyFieldEditor( {
	schema,
	values,
	onChange,
	i18n,
	ppomFieldIndex,
	modalContext,
}: FieldEditorBaseProps ) {
	const settings: Record< string, unknown > =
		schema && schema.settings && typeof schema.settings === 'object'
			? ( schema.settings as Record< string, unknown > )
			: {};
	const optionsMeta = settings.options as Record< string, unknown > | undefined;
	const needsPaired =
		optionsMeta &&
		optionsMeta.type &&
		String( optionsMeta.type ) === 'paired';

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
			label: i18n.editorSectionDefaultPrice || 'Defaults',
			keys: [ 'selected', 'first_option', 'option_label', 'qty_label' ],
			advanced: true,
		},
		{
			label: i18n.editorSectionDisplay || 'Display & layout',
			keys: [ 'class', 'width', 'visibility', 'visibility_role' ],
			advanced: true,
		},
		{
			label: i18n.editorSectionBehavior || 'Behavior',
			keys: [ 'desc_tooltip', 'required', 'unlink_order_qty' ],
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

	const shared = {
		schema,
		values,
		onChange,
		i18n,
		ppomFieldIndex,
		modalContext,
	};

	const optionsTitle = optionsMeta?.title
		? String( optionsMeta.title )
		: i18n.selectQtyOptionsTitle || 'Add options';

	const pairedBlock = needsPaired ? (
		<PairedOptionsEditor
			variant="select"
			values={ values }
			onChange={ onChange }
			i18n={ i18n }
			title={ optionsTitle }
		/>
	) : null;

	const settingsSections: LegacySectionConfig[] = [
		...sectionsBefore,
		...sectionsAfterSettings,
	];

	return (
		<SettingsConditionsTabs
			i18n={ i18n }
			hasConditions={ hasConditions }
			settings={
				<VStack align="stretch" gap={ 3 }>
					<LegacyAdvancedFieldStack
						{ ...shared }
						sections={ settingsSections }
						{ ...( pairedBlock
							? { betweenPrimaryAndAdvanced: pairedBlock }
							: {} ) }
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
