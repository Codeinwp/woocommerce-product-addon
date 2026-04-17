/**
 * Field modal: grouped picker, typed or schema fallback editor, save via admin REST.
 */
import { useMemo } from '@wordpress/element';
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
	} = useFieldModalController( productmetaId );

	const onOpenPickerFromManage = () => {
		setPickerQuery( '' );
		setPickerOpen( true );
	};

	const onBackToFieldTypes = () => {
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
			return tpl.replace( '%1$s', base ).replace( '%2$s', fieldTypeLabel );
		}
		return `${ base } · ${ fieldTypeLabel }`;
	}, [
		pickerOpen,
		fieldTypeLabel,
		i18n.newFieldModal,
		i18n.fieldModalTitleWithType,
	] );

	return (
		<FieldModalFrame
			isOpen={ open }
			onClose={ closeModal }
			saving={ saving }
			title={ modalTitle }
		>
			<FieldModalBody
				status={ { loading, error } }
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
				onClose={ closeModal }
				onSave={ handleSave }
			/>
		</FieldModalFrame>
	);
}
