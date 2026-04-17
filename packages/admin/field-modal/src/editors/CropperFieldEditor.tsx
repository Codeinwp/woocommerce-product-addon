/**
 * Image Cropper: basic + viewport rows + grouped settings + conditions + CFR.
 */
import { VStack } from '@chakra-ui/react';
import { editorSectionIsConditions } from '../schemaTabs';
import {
	SettingsConditionsTabs,
	shouldShowConditionalRepeaterTab,
} from '../panels/SettingsConditionsTabs';
import { ConditionalRepeaterSection } from '../components/ConditionalRepeaterSection';
import { PairedCropperEditor } from '../components/PairedCropperEditor';
import { GroupedFieldSections } from './GroupedFieldSections';
import type { FieldEditorBaseProps } from '../types/fieldModal';

export function CropperFieldEditor( {
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
	const viewportTitle =
		optMeta && typeof optMeta.title === 'string'
			? optMeta.title
			: 'Viewport Size';
	const viewportDesc =
		optMeta && typeof optMeta.desc === 'string' ? optMeta.desc : '';

	const sectionsBefore = [
		{
			label: i18n.editorSectionBasic || 'Basic',
			keys: [ 'title', 'data_name', 'description', 'error_message' ],
		},
	];

	const sectionsAfter = [
		{
			label: i18n.editorSectionDefaultPrice || 'Defaults',
			keys: [ 'file_cost', 'selected', 'first_option' ],
		},
		{
			label: i18n.editorSectionDisplay || 'Display & layout',
			keys: [
				'class',
				'width',
				'button_label_select',
				'button_class',
				'files_allowed',
				'file_types',
				'file_size',
				'visibility',
				'visibility_role',
			],
		},
		{
			label: i18n.editorSectionMedia || 'Media & layout',
			keys: [
				'viewport_type',
				'boundary',
				'enforce_boundary',
				'resize',
				'enable_zoom',
				'show_zoomer',
				'enable_exif',
				'onetime_taxable',
			],
		},
		{
			label: i18n.editorSectionBehavior || 'Behavior',
			keys: [ 'desc_tooltip', 'required' ],
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
					<GroupedFieldSections { ...shared } sections={ sectionsBefore } />
					<PairedCropperEditor
						fieldKey="options"
						title={ viewportTitle }
						description={ viewportDesc }
						values={ values }
						onChange={ onChange }
						i18n={ i18n }
					/>
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
