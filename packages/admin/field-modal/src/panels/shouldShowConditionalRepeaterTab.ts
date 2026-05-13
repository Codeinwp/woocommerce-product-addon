/**
 * Whether the Conditional Repeater tab should appear (Lite upsell or Plus mapping).
 */
import type { ModalContextValue } from '../types/fieldModal';

export function shouldShowConditionalRepeaterTab(
	modalContext: ModalContextValue | null | undefined
): boolean {
	if ( ! modalContext ) {
		return false;
	}
	return (
		modalContext.conditionalRepeaterUnlocked === true ||
		modalContext.conditionalRepeaterShowUpsell === true
	);
}
