/**
 * Registers authored `FieldUiDefinition` entries only.
 */
import { registerBuiltinFieldUiDefinitions } from './builtinFieldUiDefinitions';

export function registerAllFieldUiDefinitions(): void {
	registerBuiltinFieldUiDefinitions();
}

registerAllFieldUiDefinitions();
