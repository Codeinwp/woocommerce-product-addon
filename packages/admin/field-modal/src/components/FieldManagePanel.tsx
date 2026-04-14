/**
 * Manage step: empty state or active field editor (typed, fallback, or unsupported).
 */
import { Box, Button, VStack, Text, Alert, AlertIcon, Skeleton } from '@chakra-ui/react';
import { FieldSettingsForm } from '../FieldSettingsForm';
import type { FieldModalManageStepProps } from '../types/fieldModal';

export type FieldManagePanelProps = FieldModalManageStepProps;

export function FieldManagePanel( {
	i18n,
	fields,
	selectedId,
	editDraft,
	schemaLoading,
	activeSchema,
	TypedEditor,
	onEditDraftChange,
	ppomFieldIndex,
	modalContext,
	onOpenPicker,
}: FieldManagePanelProps ) {
	return (
		<Box minH="220px">
			{ fields.length === 0 && (
				<VStack
					align="stretch"
					spacing={ 4 }
					p={ { base: 2, md: 3 } }
					pt={ 0 }
				>
					<Text fontSize="sm" color="gray.600" lineHeight="1.6">
						{ i18n.manageFieldsEmpty ||
							'No fields yet. Use Add field above (classic) or choose a type below.' }
					</Text>
					<Button
						size="sm"
						colorScheme="blue"
						alignSelf="flex-start"
						onClick={ onOpenPicker }
					>
						{ i18n.openAddFieldType || 'Choose field type' }
					</Button>
				</VStack>
			) }
			{ fields.length > 0 && selectedId && editDraft && (
				<VStack align="stretch" spacing={ 3 }>
					{ schemaLoading && ! activeSchema && (
						<VStack spacing={ 2 } align="stretch">
							<Skeleton height="36px" />
							<Skeleton height="36px" />
							<Skeleton height="72px" />
						</VStack>
					) }
					{ activeSchema && TypedEditor && (
						<TypedEditor
							schema={ activeSchema }
							values={ editDraft }
							onChange={ onEditDraftChange }
							i18n={ i18n }
							ppomFieldIndex={ ppomFieldIndex }
							modalContext={ modalContext }
						/>
					) }
					{ activeSchema && ! TypedEditor && (
						<FieldSettingsForm
							schema={ activeSchema }
							values={ editDraft }
							onChange={ onEditDraftChange }
							fieldType={ editDraft.type || '' }
							i18n={ i18n }
							ppomFieldIndex={ ppomFieldIndex }
							modalContext={ modalContext }
							isFallback
						/>
					) }
					{ ! schemaLoading && ! activeSchema && editDraft.type && (
						<Alert status="info">
							<AlertIcon />
							{ i18n.unsupportedControl }
						</Alert>
					) }
				</VStack>
			) }
		</Box>
	);
}
