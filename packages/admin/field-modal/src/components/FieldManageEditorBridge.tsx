/**
 * Bridges TanStack Form state to legacy editor props (values / onChange) while syncing editDraft upward.
 *
 * ## Do not “fix” by always passing `defaultValues: editDraft`
 *
 * `useForm` calls `formApi.update(opts)` on every render. If `opts` includes `defaultValues` taken from
 * the parent `editDraft` prop, that object can lag the TanStack store by one React frame: after
 * a bridge-level form sync, `useStore` re-renders this component before
 * `setEditDraft` from `listeners.onChange` has committed. `FormApi.update` may then treat parent
 * `defaultValues` as the source of truth and overwrite `values` (see `@tanstack/form-core` `FormApi.update`:
 * `shouldUpdateValues` when `!isTouched` — legacy inputs are not TanStack `Field`s, so the form often
 * stays “untouched”). Result: inputs look broken (e.g. empty “Post ID” / `default_value`).
 *
 * We pass `defaultValues` only on the **first** options object, then drop it via
 * `includeDefaultValuesInOpts` after `useLayoutEffect`, so subsequent `update({ listeners })` calls
 * never push a stale `editDraft` snapshot into the store. Remount per field via `key={clientId}` still
 * gets a fresh initial `defaultValues` when switching rows.
 *
 * ## Parent `editDraft` replaced in place (same `clientId`)
 *
 * After mount we no longer pass `defaultValues` from props, so if the parent replaces `editDraft`
 * (undo, server refresh) without remounting, we `form.reset(editDraft)` when a stable serialization of
 * the incoming prop differs from the form store. User edits stay in sync because the listener updates
 * the parent before the serialized snapshot diverges.
 */
import {
	useCallback,
	useEffect,
	useLayoutEffect,
	useMemo,
	useState,
} from '@wordpress/element';
import { useStore } from '@tanstack/react-form';
import { VStack } from '@chakra-ui/react';
import { usePpomAppForm } from '../form/ppomForm';
import { getFieldUiDefinition } from '../definitions/registry';
import { resolveFieldModalRoute } from '../definitions/routing';
import { mergeBuilderFieldsWithActive } from '../utils/mergedBuilderFields';
import { UnknownSlugPanel } from '../panels/UnknownSlugPanel';
import { DefinitionDrivenFieldEditor } from './DefinitionDrivenFieldEditor';
import { FieldEditorFetchError } from './manage/FieldEditorFetchError';
import { SchemaLoadingSkeleton } from './manage/SchemaLoadingSkeleton';
import { SchemaUnavailableAlert } from './manage/SchemaUnavailableAlert';
import {
	applyFieldRowToForm,
	stableStringifyFieldRow,
} from '../utils/fieldFormSync';
import type { FieldRow } from '../types/fieldModal';
import type { FieldModalManageStepProps } from '../types/fieldModal';

export type FieldManageEditorBridgeProps = FieldModalManageStepProps;

type FieldManageEditorBridgeInnerProps = Omit<
	FieldModalManageStepProps,
	'editDraft' | 'onOpenPicker'
> & {
	editDraft: FieldRow;
};

export function FieldManageEditorBridge( props: FieldManageEditorBridgeProps ) {
	if ( ! props.editDraft ) {
		return null;
	}
	const { onOpenPicker: _onOpenPicker, ...rest } = props;
	return (
		<FieldManageEditorBridgeInner
			key={ props.editDraft.clientId }
			{ ...rest }
			editDraft={ props.editDraft }
		/>
	);
}

function FieldManageEditorBridgeInner( {
	i18n,
	fields,
	selectedId: _selectedId,
	editDraft,
	schemaLoading,
	schemaFetchError,
	activeSchema,
	onEditDraftChange,
	ppomFieldIndex,
	modalContext,
}: FieldManageEditorBridgeInnerProps ) {
	const [ includeDefaultValuesInOpts, setIncludeDefaultValuesInOpts ] =
		useState( true );

	const listeners = useMemo(
		() => ( {
			onChange: ( {
				formApi,
			}: {
				formApi: { state: { values: unknown } };
			} ) => {
				onEditDraftChange( formApi.state.values as FieldRow );
			},
		} ),
		[ onEditDraftChange ]
	);

	useLayoutEffect( () => {
		setIncludeDefaultValuesInOpts( false );
	}, [] );

	const form = usePpomAppForm(
		includeDefaultValuesInOpts
			? {
					defaultValues: editDraft,
					listeners,
			  }
			: { listeners }
	);

	useEffect( () => {
		const incoming = stableStringifyFieldRow( editDraft );
		const current = stableStringifyFieldRow( form.state.values );
		if ( incoming !== current ) {
			form.reset( editDraft );
		}
	}, [ editDraft, form ] );

	const values = useStore( form.store, ( s ) => s.values ) as FieldRow;

	const bridgeOnChange = useCallback(
		( action: Parameters< typeof onEditDraftChange >[ 0 ] ) => {
			const prev = form.state.values as FieldRow;
			const next =
				typeof action === 'function'
					? ( action as ( p: FieldRow | null ) => FieldRow | null )(
							prev
					  )
					: action;
			if ( next === null || next === undefined ) {
				return;
			}
			applyFieldRowToForm( form, next );
		},
		[ form ]
	);

	const slug = editDraft.type ? String( editDraft.type ) : '';
	const route = resolveFieldModalRoute( slug );
	const uiDefinition = getFieldUiDefinition( slug );

	const mergedBuilderFields = useMemo(
		() =>
			mergeBuilderFieldsWithActive( fields, editDraft.clientId, values ),
		[ fields, editDraft.clientId, values ]
	);

	return (
		<VStack align="stretch" gap={ 3 }>
			{ schemaFetchError && (
				<FieldEditorFetchError message={ schemaFetchError } />
			) }
			{ schemaLoading && ! activeSchema && ! schemaFetchError && (
				<SchemaLoadingSkeleton />
			) }
			{ activeSchema &&
				! schemaFetchError &&
				route.kind === 'definition' &&
				uiDefinition && (
					<DefinitionDrivenFieldEditor
						definition={ uiDefinition }
						mergedBuilderFields={ mergedBuilderFields }
						schema={ activeSchema }
						values={ values }
						onChange={ bridgeOnChange }
						i18n={ i18n }
						ppomFieldIndex={ ppomFieldIndex }
						form={ form }
						modalContext={ modalContext }
					/>
				) }
			{ ! schemaLoading &&
				editDraft.type &&
				( route.kind === 'unknown' || route.kind === 'legacyPhp' ) && (
					<UnknownSlugPanel
						slug={ route.slug }
						i18n={ i18n }
						ppomFieldIndex={ ppomFieldIndex }
					/>
				) }
			{ ! schemaLoading &&
				! schemaFetchError &&
				! activeSchema &&
				editDraft.type &&
				route.kind === 'definition' && (
					<SchemaUnavailableAlert i18n={ i18n } />
				) }
		</VStack>
	);
}
