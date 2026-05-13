/**
 * PPOM TanStack Form factory — pre-bound Chakra field components for AppField usage.
 */
import { createFormHook } from '@tanstack/react-form';
import {
	PpomNumberInput,
	PpomSelectInput,
	PpomSwitchInput,
	PpomTextareaInput,
	PpomTextInput,
} from './fields';
import { fieldContext, formContext } from './ppomFormContext';

const ppomFormHook = createFormHook( {
	fieldContext,
	formContext,
	fieldComponents: {
		PpomTextInput,
		PpomTextareaInput,
		PpomSelectInput,
		PpomSwitchInput,
		PpomNumberInput,
	},
	formComponents: {},
} );

export const {
	useAppForm: usePpomAppForm,
	withForm,
	extendForm,
	withFieldGroup,
} = ppomFormHook;

export {
	fieldContext,
	formContext,
	useFieldContext,
	useFormContext,
} from './ppomFormContext';
