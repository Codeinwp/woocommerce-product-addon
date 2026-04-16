/**
 * Color Palettes: basic + paired-palettes matrix + display + behavior + conditions.
 */
import { Steps, Box, VStack } from '@chakra-ui/react';
import { editorSectionIsConditions } from '../schemaTabs';
import {
	SettingsConditionsTabs,
	shouldShowConditionalRepeaterTab,
} from '../SettingsConditionsTabs';
import { ConditionalRepeaterSection } from '../components/ConditionalRepeaterSection';
import { PairedMatrixOptionsEditor } from '../components/PairedMatrixOptionsEditor';
import { GroupedFieldSections } from './GroupedFieldSections';
import type { FieldEditorBaseProps } from '../types/fieldModal';

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

	const sectionsBefore = [
		{
			label: i18n.editorSectionBasic || 'Basic',
			keys: [
				'title',
				'data_name',
				'description',
				'error_message',
				'selected_palette_bcolor',
			],
		},
	];

	const sectionsAfter = [
		{
			label: i18n.editorSectionDefaultPrice || 'Defaults',
			keys: [ 'selected' ],
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
		<Box>
			<PairedMatrixOptionsEditor
				values={ values }
				onChange={ onChange }
				i18n={ i18n }
				title={ optionsTitle }
			/>
		</Box>
	) : null;

	return (
        <SettingsConditionsTabs
			i18n={ i18n }
			hasConditions={ hasConditions }
			settings={
				<VStack align="stretch" gap={ 3 }>
					<GroupedFieldSections
						{ ...shared }
						sections={ sectionsBefore }
					/>
					{ matrixBlock }
					<GroupedFieldSections
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
