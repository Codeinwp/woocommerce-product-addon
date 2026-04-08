/**
 * Text field type: grouped settings editor.
 */
import { editorSectionIsConditions } from '../schemaTabs';
import { SettingsConditionsTabs } from '../SettingsConditionsTabs';
import { GroupedFieldSections } from './GroupedFieldSections';

/**
 * @param {Object}   props
 * @param {Object}   props.schema
 * @param {Object}   props.values
 * @param {Function} props.onChange
 * @param {Object}   props.i18n
 * @param {number}   props.ppomFieldIndex
 * @param {Object}   [props.modalContext]
 */
export function TextFieldEditor( {
	schema,
	values,
	onChange,
	i18n,
	ppomFieldIndex,
	modalContext,
} ) {
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
			label: i18n.editorSectionValidation || 'Validation',
			keys: [ 'maxlength', 'minlength' ],
		},
		{
			label: i18n.editorSectionDefaultPrice || 'Default & pricing',
			keys: [ 'default_value', 'price' ],
		},
		{
			label: i18n.editorSectionDisplay || 'Display & layout',
			keys: [ 'class', 'input_mask', 'width', 'visibility' ],
		},
		{
			label: i18n.editorSectionBehavior || 'Behavior',
			keys: [ 'onetime', 'use_regex', 'desc_tooltip', 'required' ],
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
