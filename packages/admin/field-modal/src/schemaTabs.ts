/** Setting keys grouped into the Conditions tab in typed field editors. */
export const CONDITION_EDITOR_KEYS = new Set( [ 'logic', 'conditions' ] );

/** True when a typed editor section belongs on the Conditions tab. */
export function editorSectionIsConditions( section: { keys: string[] } ): boolean {
	return section.keys.some( ( k ) => CONDITION_EDITOR_KEYS.has( k ) );
}

/** Map PPOM setting meta to editor tabs (mirrors admin CSS tab classes). */
export function classifySettingTab(
	key: string,
	meta: Record<string, unknown> | null | undefined,
	fieldType: string
): 'fields' | 'conditions' | 'unsupported' {
	const type = meta && meta.type ? String( meta.type ) : '';
	const tabsClass =
		meta && Array.isArray( meta.tabs_class )
			? ( meta.tabs_class as string[] )
			: [];
	const classStr = tabsClass.join( ' ' );
	if ( classStr.includes( 'ppom_handle_condition_tab' ) ) {
		return 'conditions';
	}
	if ( type === 'html-conditions' ) {
		return 'conditions';
	}
	if ( key === 'conditions' ) {
		return 'conditions';
	}
	if ( key === 'logic' ) {
		return 'conditions';
	}

	const unsupportedTypes = new Set( [
		'paired',
		'paired-cropper',
		'paired-quantity',
		'paired-pricematrix',
		'bulk-quantity',
		'paired-palettes',
		'pre-images',
		'pre-audios',
		'imageselect',
	] );
	if ( unsupportedTypes.has( type ) ) {
		return 'unsupported';
	}

	if ( meta && Boolean( meta.hidden ) ) {
		return 'fields';
	}

	return 'fields';
}

/** Normalize select options from PPOM (object or array) to `{ value, label }[]`. */
export function normalizeSelectOptions(
	raw: unknown
): Array< { value: string; label: string } > {
	if ( ! raw ) {
		return [];
	}
	if ( Array.isArray( raw ) ) {
		return raw.map( ( item, i ) => {
			if ( item && typeof item === 'object' && 'value' in item ) {
				return {
					value: String( item.value ),
					label: String( item.label != null ? item.label : item.value ),
				};
			}
			return { value: String( item ), label: String( item ) };
		} );
	}
	if ( typeof raw === 'object' && raw !== null ) {
		return Object.keys( raw ).map( ( k ) => ( {
			value: String( k ),
			label: String( ( raw as Record<string, unknown> )[ k ] ),
		} ) );
	}
	return [];
}
