/**
 * Color Palettes: basic + paired-palettes matrix + display + behavior + conditions.
 */
import { VStack } from '@chakra-ui/react';
import { editorSectionIsConditions } from '../schemaTabs';
import {
	SettingsConditionsTabs,
	shouldShowConditionalRepeaterTab,
} from '../panels/SettingsConditionsTabs';
import { ConditionalRepeaterSection } from '../components/ConditionalRepeaterSection';
import { PairedMatrixOptionsEditor } from '../components/PairedMatrixOptionsEditor';
import { GroupedFieldSections } from './GroupedFieldSections';
import { LegacyAdvancedFieldStack } from './legacyAdvancedFieldStack';
import type { FieldEditorBaseProps } from '../types/fieldModal';
import type { LegacySectionConfig } from './legacyAdvancedFieldStack';

export function PalettesFieldEditor( {
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
	const needsMatrix =
		optionsMeta &&
		optionsMeta.type &&
		String( optionsMeta.type ) === 'paired-palettes';

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
		{
			label: i18n.editorSectionDisplay || 'Display & layout',
			keys: [ 'selected_palette_bcolor' ],
			advanced: true,
		},
	];

	const sectionsAfter: LegacySectionConfig[] = [
		{
			label: i18n.editorSectionDefaultPrice || 'Defaults',
			keys: [ 'selected' ],
			advanced: true,
		},
		{
			label: i18n.editorSectionDisplay || 'Display & layout',
			keys: [
				'class',
				'width',
				'max_selected',
				'color_width',
				'color_height',
				'visibility',
				'visibility_role',
			],
			advanced: true,
		},
		{
			label: i18n.editorSectionBehavior || 'Behavior',
			keys: [
				'multiple_allowed',
				'onetime',
				'circle',
				'desc_tooltip',
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
		: i18n.palettesOptionsTitle || 'Add colors';

	const matrixBlock = needsMatrix ? (
		<PairedMatrixOptionsEditor
			values={ values }
			onChange={ onChange }
			i18n={ i18n }
			title={ optionsTitle }
		/>
	) : null;

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
					{ matrixBlock }
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
