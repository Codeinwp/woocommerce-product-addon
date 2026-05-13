/**
 * Field modal: grouped picker, typed or schema fallback editor, save via admin REST.
 */
import { useMemo } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { useFieldModalSession } from './hooks/useFieldModalSession';
import { useConfirmClose } from './hooks/useConfirmClose';
import { FieldModalFrame } from './components/FieldModalFrame';
import { FieldModalBody } from './components/FieldModalBody';
import { FieldModalFooter } from './components/FieldModalFooter';
import { FieldPickerHeader } from './components/FieldPickerHeader';

export interface AppProps {
	productmetaId?: number;
}

export function App( { productmetaId }: AppProps ) {
	const { state, derived, commands } = useFieldModalSession( productmetaId );
	const {
		open,
		pickerOpen,
		pickerQuery,
		loading,
		saving,
		error,
		ctx,
		fields,
		selectedId,
		editDraft,
		schemaLoading,
		schemaFetchError,
		modalEntry,
	} = state;
	const {
		isDirty,
		isNewField,
		i18n,
		ppomFieldIndex,
		catalogGroups,
		fieldTypeLabel,
		activeSchema,
		modalContext,
	} = derived;
	const {
		showPicker,
		setPickerQuery,
		updateDraft,
		addFieldOfType,
		save,
		close,
		clearError,
	} = commands;

	const onOpenPickerFromManage = () => {
		setPickerQuery( '' );
		showPicker();
	};

	const onBackToFieldTypes = () => {
		clearError();
		setPickerQuery( '' );
		showPicker();
	};

	const modalTitle = useMemo( () => {
		const base = __( 'PPOM fields', 'woocommerce-product-addon' );
		if ( pickerOpen || ! fieldTypeLabel ) {
			return base;
		}
		const tpl =
			/* translators: 1: modal title, 2: selected field type label. */
			__( '%1$s · %2$s', 'woocommerce-product-addon' );
		return sprintf( tpl, base, fieldTypeLabel );
	}, [ pickerOpen, fieldTypeLabel ] );

	const canGoBack =
		! pickerOpen &&
		Boolean( selectedId ) &&
		Boolean( editDraft ) &&
		modalEntry === 'picker';

	const { confirming: confirmingCancel, requestClose } = useConfirmClose( {
		requireConfirm: isDirty,
		onClose: close,
	} );

	return (
		<FieldModalFrame
			isOpen={ open }
			onClose={ requestClose }
			saving={ saving }
			title={ modalTitle }
			headerContent={
				pickerOpen ? (
					<FieldPickerHeader
						i18n={ i18n }
						query={ pickerQuery }
						onQueryChange={ setPickerQuery }
					/>
				) : undefined
			}
			onBack={ canGoBack ? onBackToFieldTypes : undefined }
			backLabel={ __( 'Back', 'woocommerce-product-addon' ) }
		>
			<FieldModalBody
				status={ { loading, error } }
				onDismissError={ clearError }
				ctx={ ctx }
				pickerOpen={ pickerOpen }
				picker={ {
					i18n,
					catalogGroups,
					pickerQuery,
					onPickerQueryChange: setPickerQuery,
					onPickType: addFieldOfType,
					upsell: ctx?.upsell,
					license: ctx?.license,
				} }
				manage={ {
					i18n,
					fields,
					selectedId,
					editDraft,
					schemaLoading,
					schemaFetchError,
					activeSchema,
					onEditDraftChange: updateDraft,
					ppomFieldIndex,
					modalContext,
					onOpenPicker: onOpenPickerFromManage,
				} }
			/>
			<FieldModalFooter
				i18n={ i18n }
				pickerOpen={ pickerOpen }
				loading={ loading }
				saving={ saving }
				hasCtx={ Boolean( ctx ) }
				confirmingCancel={ confirmingCancel }
				isNewField={ isNewField }
				onClose={ requestClose }
				onSave={ save }
			/>
		</FieldModalFrame>
	);
}
