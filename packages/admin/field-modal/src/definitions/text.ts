/**
 * Text field type — Phase 1 vertical slice (`FieldTabs` + section blocks).
 *
 * Registration happens in {@link ./builtinFieldUiDefinitions}; this module is the stable import path.
 */
import type { FieldUiDefinition } from './types';
import { textLikeDefinition } from './builtinFieldUiDefinitions';

export const textFieldUiDefinition: FieldUiDefinition =
	textLikeDefinition( 'text' );
