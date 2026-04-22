/**
 * Modal footer: cancel + save actions.
 */
import { Button, HStack, Dialog } from '@chakra-ui/react';
import { LuCheck, LuX } from 'react-icons/lu';
import type { I18nDict } from '../types/fieldModal';

export interface FieldModalFooterProps {
	i18n: I18nDict;
	pickerOpen: boolean;
	loading: boolean;
	saving: boolean;
	hasCtx: boolean;
	onClose: () => void;
	onSave: () => void;
}

export function FieldModalFooter( {
	i18n,
	pickerOpen,
	loading,
	saving,
	hasCtx,
	onClose,
	onSave,
}: FieldModalFooterProps ) {
	return (
        <Dialog.Footer
			flexShrink={ 0 }
			display="flex"
			gap={ 2 }
			justifyContent="flex-end"
			alignItems="center"
		>
            <HStack gap={ 2 }>
				<Button variant="ghost" onClick={ onClose } disabled={ saving }>
					<LuX />
					{ i18n.cancel || 'Cancel' }
				</Button>
				{ ! pickerOpen && (
					<Button
						colorPalette="blue"
						onClick={ onSave }
						loading={ saving }
						disabled={ loading || ! hasCtx }
					>
						<LuCheck />
						{ i18n.save || 'Save' }
					</Button>
				) }
			</HStack>
        </Dialog.Footer>
    );
}
