/**
 * Textarea field type: grouped settings editor.
 */
import { editorSectionIsConditions } from '../schemaTabs';
import { SettingsConditionsTabs } from '../SettingsConditionsTabs';
import { GroupedFieldSections } from './GroupedFieldSections';
import type { FieldEditorBaseProps } from '../types/fieldModal';

export function TextareaFieldEditor( {
	schema,
	values,
	onChange,
	i18n,
	ppomFieldIndex,
	modalContext,
}: FieldEditorBaseProps ) {
	const sections = [
		{
			label: i18n.editorSectionBasic || 'Basic',
			keys: [
				'title',
				'data_name',
				'description',
				'placeholder',
				'error_message',
			],
		},
		{
			label: i18n.editorSectionDefaultPrice || 'Default & pricing',
			keys: [ 'default_value', 'max_length', 'price' ],
		},
		{
			label: i18n.editorSectionDisplay || 'Display & layout',
			keys: [ 'class', 'width', 'visibility' ],
		},
		{
			label: i18n.editorSectionBehavior || 'Behavior',
			keys: [ 'rich_editor', 'desc_tooltip', 'required' ],
		},
		{
			label: i18n.conditionsTab || 'Conditions',
			keys: [ 'logic', 'conditions' ],
		},
	];

	const settingsSections = sections.filter(
		( s ) => ! editorSectionIsConditions( s )
	);
	const conditionsSections = sections.filter( ( s ) =>
		editorSectionIsConditions( s )
	);
	const hasConditions = conditionsSections.length > 0;

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
				<GroupedFieldSections { ...shared } sections={ settingsSections } />
			}
			conditions={
				<GroupedFieldSections
					{ ...shared }
					sections={ conditionsSections }
				/>
			}
		/>
	);
}
