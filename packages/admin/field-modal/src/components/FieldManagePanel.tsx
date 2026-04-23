/**
 * Manage step: empty state or active field editor (typed, fallback, or unsupported).
 */
import { Steps, Box, Button, VStack, Text } from '@chakra-ui/react';
import { FieldManageEditorBridge } from './FieldManageEditorBridge';
import type { FieldModalManageStepProps } from '../types/fieldModal';

export type FieldManagePanelProps = FieldModalManageStepProps;

export function FieldManagePanel( {
	i18n,
	fields,
	selectedId,
	editDraft,
	schemaLoading,
	schemaFetchError,
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
					gap={ 4 }
					p={ { base: 2, md: 3 } }
					pt={ 0 }
				>
					<Text fontSize="sm" color="gray.600" lineHeight="1.6">
						{ i18n.manageFieldsEmpty ||
							'No fields yet. Use Add field above or choose a field type below.' }
					</Text>
					<Button
						size="sm"
						colorPalette="blue"
						alignSelf="flex-start"
						onClick={ onOpenPicker }
					>
						{ i18n.openAddFieldType || 'Choose field type' }
					</Button>
				</VStack>
			) }
			{ fields.length > 0 && selectedId && editDraft && (
				<FieldManageEditorBridge
					i18n={ i18n }
					fields={ fields }
					selectedId={ selectedId }
					editDraft={ editDraft }
					schemaLoading={ schemaLoading }
					schemaFetchError={ schemaFetchError }
					activeSchema={ activeSchema }
					TypedEditor={ TypedEditor }
					onEditDraftChange={ onEditDraftChange }
					ppomFieldIndex={ ppomFieldIndex }
					modalContext={ modalContext }
					onOpenPicker={ onOpenPicker }
				/>
			) }
		</Box>
	);
}
