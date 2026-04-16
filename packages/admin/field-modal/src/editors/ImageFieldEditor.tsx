/**
 * Image field type: grouped settings + ImagesSelectEditor for the "images" key.
 */
import { Steps, Box, VStack, Text } from '@chakra-ui/react';
import { editorSectionIsConditions } from '../schemaTabs';
import {
	SettingsConditionsTabs,
	shouldShowConditionalRepeaterTab,
} from '../SettingsConditionsTabs';
import { ConditionalRepeaterSection } from '../components/ConditionalRepeaterSection';
import { ImagesSelectEditor } from '../components/ImagesSelectEditor';
import { GroupedFieldSections } from './GroupedFieldSections';
import type { FieldEditorBaseProps } from '../types/fieldModal';

export function ImageFieldEditor( {
	schema,
	values,
	onChange,
	i18n,
	ppomFieldIndex,
	modalContext,
}: FieldEditorBaseProps ) {
	const imagesTitle =
		i18n.addImagesSectionTitle || 'Images';

	const sectionsBefore = [
		{
			label: i18n.editorSectionBasic || 'Basic',
			keys: [
				'title',
				'data_name',
				'description',
				'error_message',
			],
		},
	];

	const sectionsAfter = [
		{
			label: i18n.editorSectionDefaultPrice || 'Defaults',
			keys: [ 'selected' ],
		},
		{
			label:
				i18n.editorSectionImageSettings || 'Image Settings',
			keys: [
				'selected_img_bordercolor',
				'image_width',
				'image_height',
			],
		},
		{
			label: i18n.editorSectionDisplay || 'Display & layout',
			keys: [
				'class',
				'width',
				'visibility',
				'visibility_role',
				'legacy_view',
				'show_popup',
			],
		},
		{
			label: i18n.editorSectionBehavior || 'Behavior',
			keys: [
				'desc_tooltip',
				'required',
				'multiple_allowed',
				'min_checked',
				'max_checked',
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
					<Box>
						<ImagesSelectEditor
							values={ values }
							onChange={ onChange }
							i18n={ i18n }
							title={ imagesTitle }
							variant="image"
						/>
						<Text
							fontSize="xs"
							color="gray.500"
							mt={ 2 }
							lineHeight="1.5"
						>
							{ i18n.legacyEditorHint }
						</Text>
					</Box>
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
					ppomFieldIndex={ ppomFieldIndex }
				/>
			}
		/>
    );
}
