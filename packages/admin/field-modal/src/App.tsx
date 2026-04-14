/**
 * Field modal: grouped picker, typed or schema fallback editor, save via admin REST.
 */
import { useFieldModalController } from './hooks/useFieldModalController';
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
		modalEntry,
		i18n,
		ppomFieldIndex,
		catalogGroups,
		activeSchema,
		modalContext,
		TypedEditor,
		setPickerOpen,
		setPickerQuery,
		setEditDraft,
		addFieldOfType,
		removeField,
		handleSave,
		closeModal,
		openLegacyEditor,
	} = useFieldModalController( productmetaId );

	const onPickerBackOrCancel = () => {
		setPickerQuery( '' );
		if ( modalEntry === 'picker' ) {
			closeModal();
		} else {
			setPickerOpen( false );
		}
	};

	const onOpenPickerFromManage = () => {
		setPickerQuery( '' );
		setPickerOpen( true );
	};

	const onBackToFieldTypes = () => {
		setPickerQuery( '' );
		setPickerOpen( true );
	};

	return (
		<FieldModalFrame
			isOpen={ open }
			onClose={ closeModal }
			saving={ saving }
			title={ i18n.newFieldModal || 'PPOM fields' }
		>
			<FieldModalBody
				status={ { loading, error } }
				ctx={ ctx }
				pickerOpen={ pickerOpen }
				picker={ {
					modalEntry,
					i18n,
					catalogGroups,
					pickerQuery,
					onPickerQueryChange: setPickerQuery,
					onBackOrCancel: onPickerBackOrCancel,
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
				selectedId={ selectedId }
				editDraft={ editDraft }
				modalEntry={ modalEntry }
				onBackToFieldTypes={ onBackToFieldTypes }
				onOpenLegacyEditor={ openLegacyEditor }
				onRemoveSelected={ () => {
					if ( selectedId ) {
						removeField( selectedId );
					}
				} }
				onClose={ closeModal }
				onSave={ handleSave }
			/>
		</FieldModalFrame>
	);
}
