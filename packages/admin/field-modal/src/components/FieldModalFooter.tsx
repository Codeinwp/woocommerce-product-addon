/**
 * Modal footer: secondary actions (legacy, remove, back to types) + close/save.
 */
import { ModalFooter, Button, HStack } from '@chakra-ui/react';
import type { FieldRow, I18nDict } from '../types/fieldModal';

export interface FieldModalFooterProps {
	i18n: I18nDict;
	pickerOpen: boolean;
	loading: boolean;
	saving: boolean;
	hasCtx: boolean;
	selectedId: string | null;
	editDraft: FieldRow | null;
	modalEntry: 'picker' | 'manage';
	onBackToFieldTypes: () => void;
	onOpenLegacyEditor: () => void;
	onRemoveSelected: () => void;
	onClose: () => void;
	onSave: () => void;
}

export function FieldModalFooter( {
	i18n,
	pickerOpen,
	loading,
	saving,
	hasCtx,
	selectedId,
	editDraft,
	modalEntry,
	onBackToFieldTypes,
	onOpenLegacyEditor,
	onRemoveSelected,
	onClose,
	onSave,
}: FieldModalFooterProps ) {
	return (
		<ModalFooter
			flexShrink={ 0 }
			display="flex"
			flexWrap="wrap"
			gap={ 3 }
			justifyContent="space-between"
			alignItems="center"
		>
			<HStack flexWrap="wrap" spacing={ 3 } alignItems="center">
				{ ! pickerOpen && selectedId && editDraft && (
					<>
						{ modalEntry === 'picker' && (
							<Button
								variant="link"
								size="sm"
								colorScheme="blue"
								onClick={ onBackToFieldTypes }
							>
								{ i18n.backToFieldTypes || 'Back to field types' }
							</Button>
						) }
						<Button variant="link" size="sm" onClick={ onOpenLegacyEditor }>
							{ i18n.openLegacyModal }
						</Button>
						<Button
							variant="link"
							size="sm"
							colorScheme="red"
							onClick={ onRemoveSelected }
						>
							{ i18n.remove || 'Remove' }
						</Button>
					</>
				) }
			</HStack>
			<HStack spacing={ 2 }>
				{ ! pickerOpen && (
					<Button variant="ghost" onClick={ onClose } isDisabled={ saving }>
						{ i18n.close || 'Close' }
					</Button>
				) }
				<Button
					colorScheme="blue"
					onClick={ onSave }
					isLoading={ saving }
					isDisabled={ loading || ! hasCtx || pickerOpen }
				>
					{ i18n.save || 'Save' }
				</Button>
			</HStack>
		</ModalFooter>
	);
}
