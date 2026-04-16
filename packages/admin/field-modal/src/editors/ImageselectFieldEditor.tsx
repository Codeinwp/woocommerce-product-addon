/**
 * Imageselect (Image Dropdown) field type: grouped settings + ImagesSelectEditor.
 *
 * The imageselect type is registered by PPOM Pro; its setting keys come from the
 * Pro input class via the REST schema. This editor owns the `images` key via
 * ImagesSelectEditor and delegates all other settings to GroupedFieldSections
 * driven by the schema, using the fallback section blueprint.
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
import { buildFallbackGroupedSections } from '../fieldSettingSectionBlueprint';
import type { FieldEditorBaseProps } from '../types/fieldModal';

export function ImageselectFieldEditor( {
	schema,
	values,
	onChange,
	i18n,
	ppomFieldIndex,
	modalContext,
	fieldType,
}: FieldEditorBaseProps ) {
	const imagesTitle =
		i18n.addImagesSectionTitle || 'Images';

	const allSections = buildFallbackGroupedSections(
		schema,
		fieldType || 'imageselect',
		i18n
	);

	const settingsSections = allSections.filter(
		( s ) => ! editorSectionIsConditions( s )
	);
	const conditionsSections = allSections.filter( ( s ) =>
		editorSectionIsConditions( s )
	);
	const hasConditions = conditionsSections.length > 0;
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
						sections={ settingsSections }
					/>
					<Box>
						<ImagesSelectEditor
							values={ values }
							onChange={ onChange }
							i18n={ i18n }
							title={ imagesTitle }
							variant="imageselect"
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
				</VStack>
			}
			conditions={
				<GroupedFieldSections
					{ ...shared }
					sections={ conditionsSections }
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
