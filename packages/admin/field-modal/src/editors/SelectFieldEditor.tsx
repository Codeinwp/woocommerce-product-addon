/**
 * Select field type: grouped settings; paired options use classic editor CTA.
 */
import { Box, Alert, AlertIcon, Text, Button, VStack } from '@chakra-ui/react';
import { editorSectionIsConditions } from '../schemaTabs';
import { SettingsConditionsTabs } from '../SettingsConditionsTabs';
import { GroupedFieldSections } from './GroupedFieldSections';
import { openLegacyFieldModal } from '../schemaSettingControl';
import type { FieldEditorBaseProps } from '../types/fieldModal';

export function SelectFieldEditor( {
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
	const needsLegacyOptions =
		optionsMeta &&
		optionsMeta.type &&
		String( optionsMeta.type ) === 'paired';

	const sectionsBefore = [
		{
			label: i18n.editorSectionBasic || 'Basic',
			keys: [ 'title', 'data_name', 'description', 'error_message' ],
		},
	];

	const sectionsAfter = [
		{
			label: i18n.editorSectionDefaultPrice || 'Defaults',
			keys: [ 'selected', 'first_option' ],
		},
		{
			label: i18n.editorSectionDisplay || 'Display & layout',
			keys: [ 'class', 'width', 'visibility' ],
		},
		{
			label: i18n.editorSectionBehavior || 'Behavior',
			keys: [ 'desc_tooltip', 'onetime', 'required' ],
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

	const shared = {
		schema,
		values,
		onChange,
		i18n,
		ppomFieldIndex,
		modalContext,
	};

	const legacyOptionsAlert = needsLegacyOptions ? (
		<Alert
			status="warning"
			variant="subtle"
			borderRadius="lg"
			borderWidth="1px"
			borderColor="orange.200"
		>
			<AlertIcon />
			<Box>
				<Text fontWeight="semibold" fontSize="sm" color="gray.800">
					{ optionsMeta.title
						? String( optionsMeta.title )
						: i18n.selectOptionsTitle || 'Options' }
				</Text>
				<Text fontSize="sm" mt={ 2 } color="gray.600" lineHeight="1.5">
					{ i18n.legacyEditorHint }
				</Text>
						<Button
							size="sm"
							mt={ 2 }
							variant="outline"
					colorScheme="blue"
					borderColor="blue.300"
					onClick={ () => openLegacyFieldModal( ppomFieldIndex ) }
				>
					{ i18n.openLegacyModal }
				</Button>
			</Box>
		</Alert>
	) : null;

	return (
		<SettingsConditionsTabs
			i18n={ i18n }
			hasConditions={ hasConditions }
			settings={
				<VStack align="stretch" spacing={ 3 }>
					<GroupedFieldSections
						{ ...shared }
						sections={ sectionsBefore }
					/>
					{ legacyOptionsAlert }
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
		/>
	);
}
