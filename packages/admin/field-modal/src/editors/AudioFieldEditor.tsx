/**
 * Audio / Video field type: grouped settings + AudiosSelectEditor for the "audio" key.
 */
import { VStack } from '@chakra-ui/react';
import { editorSectionIsConditions } from '../schemaTabs';
import {
	SettingsConditionsTabs,
	shouldShowConditionalRepeaterTab,
} from '../panels/SettingsConditionsTabs';
import { AudiosSelectEditor } from '../components/AudiosSelectEditor';
import { ConditionalRepeaterSection } from '../components/ConditionalRepeaterSection';
import { GroupedFieldSections } from './GroupedFieldSections';
import { LegacyAdvancedFieldStack } from './legacyAdvancedFieldStack';
import type { FieldEditorBaseProps } from '../types/fieldModal';
import type { LegacySectionConfig } from './legacyAdvancedFieldStack';

export function AudioFieldEditor( {
	schema,
	values,
	onChange,
	i18n,
	ppomFieldIndex,
	modalContext,
}: FieldEditorBaseProps ) {
	const audioSectionTitle =
		i18n.addAudioVideoSectionTitle || 'Audio / Video';

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
			label: i18n.editorSectionDisplay || 'Display & layout',
			keys: [ 'class', 'width', 'visibility', 'visibility_role' ],
			advanced: true,
		},
		{
			label: i18n.editorSectionBehavior || 'Behavior',
			keys: [ 'desc_tooltip', 'required', 'multiple_allowed' ],
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

	return (
		<SettingsConditionsTabs
			i18n={ i18n }
			hasConditions={ hasConditions }
			settings={
				<VStack align="stretch" gap={ 3 }>
					<LegacyAdvancedFieldStack
						{ ...shared }
						sections={ sectionsBefore }
					/>
					<AudiosSelectEditor
						values={ values }
						onChange={ onChange }
						i18n={ i18n }
						title={ audioSectionTitle }
					/>
					<LegacyAdvancedFieldStack
						{ ...shared }
						sections={ sectionsAfterSettings }
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
