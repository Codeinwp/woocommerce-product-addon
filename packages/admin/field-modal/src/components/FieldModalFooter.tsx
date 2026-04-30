/**
 * Modal footer: cancel + save actions.
 */
import { Button, HStack, Dialog } from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import { LuCheck, LuTriangleAlert, LuX } from 'react-icons/lu';
import type { I18nDict } from '../types/fieldModal';

export interface FieldModalFooterProps {
	i18n: I18nDict;
	pickerOpen: boolean;
	loading: boolean;
	saving: boolean;
	hasCtx: boolean;
	confirmingCancel?: boolean;
	onClose: () => void;
	onSave: () => void;
}

export function FieldModalFooter( {
	i18n,
	pickerOpen,
	loading,
	saving,
	hasCtx,
	confirmingCancel = false,
	onClose,
	onSave,
}: FieldModalFooterProps ) {
	const cancelLabel = confirmingCancel
		? __( 'Confirm', 'woocommerce-product-addon' )
		: __( 'Cancel', 'woocommerce-product-addon' );

	return (
		<Dialog.Footer
			flexShrink={ 0 }
			display="flex"
			gap={ 2 }
			justifyContent="flex-end"
			alignItems="center"
		>
			<HStack gap={ 2 }>
				<Button
					variant={ confirmingCancel ? 'solid' : 'ghost' }
					colorPalette={ confirmingCancel ? 'red' : undefined }
					onClick={ onClose }
					disabled={ saving }
					aria-label={ cancelLabel }
				>
					{ confirmingCancel ? <LuTriangleAlert /> : <LuX /> }
					{ cancelLabel }
				</Button>
				{ ! pickerOpen && (
					<Button
						colorPalette="blue"
						onClick={ onSave }
						loading={ saving }
						disabled={ loading || ! hasCtx }
					>
						<LuCheck />
						{ __( 'Save', 'woocommerce-product-addon' ) }
					</Button>
				) }
			</HStack>
		</Dialog.Footer>
	);
}
