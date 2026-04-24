/**
 * Schema setting keys not rendered by the generic React field modal (Pro CFR, freemium placeholders).
 * The dedicated Conditional Repeater section + boot flags own this UX instead.
 */
export function isReactModalExcludedSchemaKey( key: string ): boolean {
	if ( key === 'locked_cfr' ) {
		return true;
	}
	if ( key.startsWith( 'cond_field_repeater' ) ) {
		return true;
	}
	return false;
}
