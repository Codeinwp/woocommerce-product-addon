/**
 * Bridges the active field row into the form adapter and editor runtime.
 */
import { EditorRuntime } from './EditorRuntime';
import { useFieldRowFormAdapter } from '../hooks/useFieldRowFormAdapter';
import type { FieldModalManageStepProps, FieldRow } from '../types/fieldModal';

export type FieldManageEditorBridgeProps = FieldModalManageStepProps;

type FieldManageEditorBridgeInnerProps = Omit<
	FieldModalManageStepProps,
	'editDraft' | 'onOpenPicker' | 'selectedId'
> & {
	editDraft: FieldRow;
};

export function FieldManageEditorBridge( props: FieldManageEditorBridgeProps ) {
	if ( ! props.editDraft ) {
		return null;
	}
	return (
		<FieldManageEditorBridgeInner
			key={ props.editDraft.clientId }
			i18n={ props.i18n }
			fields={ props.fields }
			editDraft={ props.editDraft }
			schemaLoading={ props.schemaLoading }
			schemaFetchError={ props.schemaFetchError }
			activeSchema={ props.activeSchema }
			onEditDraftChange={ props.onEditDraftChange }
			ppomFieldIndex={ props.ppomFieldIndex }
			modalContext={ props.modalContext }
		/>
	);
}

function FieldManageEditorBridgeInner( {
	i18n,
	fields,
	editDraft,
	schemaLoading,
	schemaFetchError,
	activeSchema,
	onEditDraftChange,
	ppomFieldIndex,
	modalContext,
}: FieldManageEditorBridgeInnerProps ) {
	const { values, form, updateField } = useFieldRowFormAdapter( {
		editDraft,
		onEditDraftChange,
	} );

	return (
		<EditorRuntime
			fields={ fields }
			values={ values }
			activeSchema={ activeSchema }
			schemaLoading={ schemaLoading }
			schemaFetchError={ schemaFetchError }
			onChange={ updateField }
			i18n={ i18n }
			ppomFieldIndex={ ppomFieldIndex }
			form={ form }
			modalContext={ modalContext }
		/>
	);
}
