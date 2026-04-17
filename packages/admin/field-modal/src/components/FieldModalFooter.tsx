/**
 * Modal footer: back to field types (when applicable) + close/save.
 */
import { Steps, Button, HStack, Dialog } from '@chakra-ui/react';
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
	onClose,
	onSave,
}: FieldModalFooterProps ) {
	return (
        <Dialog.Footer
			flexShrink={ 0 }
			display="flex"
			flexWrap="wrap"
			gap={ 3 }
			justifyContent="space-between"
			alignItems="center"
		>
            <HStack flexWrap="wrap" gap={ 3 } alignItems="center">
				{ ! pickerOpen && selectedId && editDraft && (
					<>
						{ modalEntry === 'picker' && (
							<Button
								variant='plain'
								size="sm"
								colorPalette="blue"
								onClick={ onBackToFieldTypes }
							>
								{ i18n.backToFieldTypes || 'Back to field types' }
							</Button>
						) }
					</>
				) }
			</HStack>
            <HStack gap={ 2 }>
				{ ! pickerOpen && (
					<Button variant="ghost" onClick={ onClose } disabled={ saving }>
						{ i18n.close || 'Close' }
					</Button>
				) }
				<Button
					colorPalette="blue"
					onClick={ onSave }
					loading={ saving }
					disabled={ loading || ! hasCtx || pickerOpen }
				>
					{ i18n.save || 'Save' }
				</Button>
			</HStack>
        </Dialog.Footer>
    );
}
