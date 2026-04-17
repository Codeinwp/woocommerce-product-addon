/**
 * Bulk Quantity (Pro): matrix + label / display extras from input schema.
 */
import { VStack } from '@chakra-ui/react';
import { editorSectionIsConditions } from '../schemaTabs';
import {
	SettingsConditionsTabs,
	shouldShowConditionalRepeaterTab,
} from '../SettingsConditionsTabs';
import { ConditionalRepeaterSection } from '../components/ConditionalRepeaterSection';
import { BulkQuantityMatrixEditor } from '../components/BulkQuantityMatrixEditor';
import { GroupedFieldSections } from './GroupedFieldSections';
import type { FieldEditorBaseProps } from '../types/fieldModal';

export function BulkQuantityFieldEditor( {
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
		String( optionsMeta.type ) === 'bulk-quantity';

	const sectionsBefore = [
		{
			label: i18n.editorSectionBasic || 'Basic',
			keys: [ 'title', 'data_name', 'description' ],
		},
	];

	const sectionsAfter = [
		{
			label: i18n.editorSectionDisplay || 'Labels & display',
			keys: [
				'fixed_prices',
				'label_quantity',
				'label_baseprice',
				'label_total',
				'label_fixed',
				'hide_baseprice',
				'show_pricerange',
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
		: i18n.bulkQuantityOptionsTitle || 'Bulk quantity';

	const matrixBlock = needsMatrix ? (
		<BulkQuantityMatrixEditor
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
