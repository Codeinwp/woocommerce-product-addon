/**
 * TanStack Form adapter for editing one PPOM field row.
 *
 * ## Do not “fix” by always passing `defaultValues: editDraft`
 *
 * `useForm` calls `formApi.update(opts)` on every render. If `opts` includes
 * `defaultValues` taken from the parent `editDraft` prop, that object can lag
 * the TanStack store by one React frame: after adapter-level form sync,
 * `useStore` re-renders the caller before `onEditDraftChange` has committed.
 * `FormApi.update` may then treat parent `defaultValues` as the source of truth
 * and overwrite `values` (see `@tanstack/form-core` `FormApi.update`:
 * `shouldUpdateValues` when `!isTouched`).
 *
 * We pass `defaultValues` only on the first options object, then drop it after
 * `useLayoutEffect`. Remount per field via `key={clientId}` still gets a fresh
 * initial `defaultValues` when switching rows.
 */
import type { Dispatch, SetStateAction } from 'react';
import {
	useCallback,
	useEffect,
	useLayoutEffect,
	useMemo,
	useState,
} from '@wordpress/element';
import { useStore } from '@tanstack/react-form';
import { usePpomAppForm } from '../form/ppomForm';
import {
	applyFieldRowToForm,
	stableStringifyFieldRow,
} from '../utils/fieldFormSync';
import type { FieldRow } from '../types/fieldModal';

export function useFieldRowFormAdapter( args: {
	editDraft: FieldRow;
	onEditDraftChange: Dispatch< SetStateAction< FieldRow | null > >;
} ) {
	const { editDraft, onEditDraftChange } = args;
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

	const updateField: Dispatch< SetStateAction< FieldRow | null > > =
		useCallback(
			( action ) => {
				const prev = form.state.values as FieldRow;
				const next =
					typeof action === 'function'
						? (
								action as (
									p: FieldRow | null
								) => FieldRow | null
						   )( prev )
						: action;
				if ( next === null || next === undefined ) {
					return;
				}
				applyFieldRowToForm( form, next );
				// TanStack form's form-level `listeners.onChange` only fires for
				// keys owned by a registered `<form.Field>`. Widget-driven keys
				// such as `options` may never register a Field, so push them up.
				onEditDraftChange( form.state.values as FieldRow );
			},
			[ form, onEditDraftChange ]
		);

	return { values, form, updateField };
}
