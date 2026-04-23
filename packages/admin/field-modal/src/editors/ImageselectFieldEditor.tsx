/**
 * Imageselect (Image Dropdown) field type: grouped settings + ImagesSelectEditor.
 *
 * The imageselect type is registered by PPOM Pro; its setting keys come from the
 * Pro input class via the REST schema. This editor owns the `images` key via
 * ImagesSelectEditor and delegates all other settings to GroupedFieldSections
 * driven by the schema, using the fallback section blueprint.
 */
import { useMemo } from '@wordpress/element';
import { VStack } from '@chakra-ui/react';
import { editorSectionIsConditions } from '../schemaTabs';
import {
	SettingsConditionsTabs,
	shouldShowConditionalRepeaterTab,
} from '../panels/SettingsConditionsTabs';
import { ConditionalRepeaterSection } from '../components/ConditionalRepeaterSection';
import { ImagesSelectEditor } from '../components/ImagesSelectEditor';
import { GroupedFieldSections } from './GroupedFieldSections';
import { LegacyAdvancedFieldStack } from './legacyAdvancedFieldStack';
import { buildFallbackGroupedSections } from '../fieldSettingSectionBlueprint';
import type { FieldEditorBaseProps } from '../types/fieldModal';
import type { LegacySectionConfig } from './legacyAdvancedFieldStack';

export function ImageselectFieldEditor( {
	schema,
	values,
	onChange,
	i18n,
	ppomFieldIndex,
	modalContext,
	fieldType,
}: FieldEditorBaseProps ) {
	const imagesTitle = i18n.addImagesSectionTitle || 'Images';

	const allSections = useMemo(
		() =>
			buildFallbackGroupedSections(
				schema,
				fieldType || 'imageselect',
				i18n
			),
		[ schema, fieldType, i18n ]
	);

	const settingsSections = useMemo(
		() => allSections.filter( ( s ) => ! editorSectionIsConditions( s ) ),
		[ allSections ]
	);
	const conditionsSections = useMemo(
		() => allSections.filter( ( s ) => editorSectionIsConditions( s ) ),
		[ allSections ]
	);

	const primarySettings = useMemo(
		() =>
			settingsSections
				.filter( ( s ) => s.advanced !== true )
				.map( ( { label, keys } ) => ( { label, keys } ) ),
		[ settingsSections ]
	);

	const advancedSectionsOnly: LegacySectionConfig[] = useMemo(
		() => settingsSections.filter( ( s ) => s.advanced === true ),
		[ settingsSections ]
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
					{ primarySettings.length > 0 && (
						<GroupedFieldSections
							{ ...shared }
							sections={ primarySettings }
						/>
					) }
					<ImagesSelectEditor
						values={ values }
						onChange={ onChange }
						i18n={ i18n }
						title={ imagesTitle }
						variant="imageselect"
					/>
					{ advancedSectionsOnly.length > 0 && (
						<LegacyAdvancedFieldStack
							{ ...shared }
							sections={ advancedSectionsOnly }
						/>
					) }
				</VStack>
			}
			conditions={
				<GroupedFieldSections
					{ ...shared }
					sections={ conditionsSections.map(
						( { label, keys } ) => ( { label, keys } )
					) }
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
