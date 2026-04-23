/**
 * TanStack Form contexts for PPOM field modal — single source of truth for useFieldContext / useFormContext.
 *
 * @see https://tanstack.com/form/latest/docs/framework/react/guides/form-composition
 */
import { createFormHookContexts } from '@tanstack/react-form';

export const { fieldContext, formContext, useFieldContext, useFormContext } =
	createFormHookContexts();
