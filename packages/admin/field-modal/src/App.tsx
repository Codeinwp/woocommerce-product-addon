/**
 * Field modal: grouped picker, typed or schema fallback editor, save via admin REST.
 */
import { useMemo } from '@wordpress/element';
import { useFieldModalController } from './hooks/useFieldModalController';
import { useConfirmClose } from './hooks/useConfirmClose';
import { FieldModalFrame } from './components/FieldModalFrame';
import { FieldModalBody } from './components/FieldModalBody';
import { FieldModalFooter } from './components/FieldModalFooter';

export interface AppProps {
	productmetaId?: number;
}

export function App( { productmetaId }: AppProps ) {
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
		i18n,
		ppomFieldIndex,
		catalogGroups,
		fieldTypeLabel,
		activeSchema,
		modalContext,
		TypedEditor,
		setPickerOpen,
		setPickerQuery,
		setEditDraft,
		addFieldOfType,
		handleSave,
		closeModal,
		clearError,
	} = useFieldModalController( productmetaId );

	const onOpenPickerFromManage = () => {
		setPickerQuery( '' );
		setPickerOpen( true );
	};

	const onBackToFieldTypes = () => {
		clearError();
		setPickerQuery( '' );
		setPickerOpen( true );
	};

	const modalTitle = useMemo( () => {
		const base = i18n.newFieldModal || 'PPOM fields';
		if ( pickerOpen || ! fieldTypeLabel ) {
			return base;
		}
		const tpl = i18n.fieldModalTitleWithType;
		if ( tpl && tpl.includes( '%1$s' ) ) {
			return tpl
				.replace( '%1$s', base )
				.replace( '%2$s', fieldTypeLabel );
		}
		return `${ base } · ${ fieldTypeLabel }`;
	}, [
		pickerOpen,
		fieldTypeLabel,
		i18n.newFieldModal,
		i18n.fieldModalTitleWithType,
	] );

	const canGoBack =
		! pickerOpen &&
		Boolean( selectedId ) &&
		Boolean( editDraft ) &&
		modalEntry === 'picker';

	const isDirty = ! pickerOpen && Boolean( editDraft );
	const { confirming: confirmingCancel, requestClose } = useConfirmClose( {
		requireConfirm: isDirty,
		onClose: closeModal,
	} );

	return (
		<FieldModalFrame
			isOpen={ open }
			onClose={ requestClose }
			saving={ saving }
			title={ modalTitle }
			onBack={ canGoBack ? onBackToFieldTypes : undefined }
			backLabel={ i18n.back || 'Back' }
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
					TypedEditor,
					onEditDraftChange: setEditDraft,
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
				onClose={ requestClose }
				onSave={ handleSave }
			/>
		</FieldModalFrame>
	);
}
