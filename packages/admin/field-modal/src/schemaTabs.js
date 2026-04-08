/** Setting keys grouped into the Conditions tab in typed field editors. */
export const CONDITION_EDITOR_KEYS = new Set( [ 'logic', 'conditions' ] );

/**
 * @param {{ keys: string[] }} section Section from a typed editor config.
 * @return {boolean} True if this section should appear under Conditions.
 */
export function editorSectionIsConditions( section ) {
	return section.keys.some( ( k ) => CONDITION_EDITOR_KEYS.has( k ) );
}

/**
 * Map PPOM setting meta to editor tabs (mirrors admin CSS tab classes).
 *
 * @param {string} key    Setting key.
 * @param {Object} meta   Setting definition from schema.
 * @param {string} fieldType Field type slug.
 * @return {'fields'|'conditions'|'unsupported'}
 */
export function classifySettingTab( key, meta, fieldType ) {
	const type = meta && meta.type ? String( meta.type ) : '';
	const tabsClass = meta && Array.isArray( meta.tabs_class ) ? meta.tabs_class : [];
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

	if ( meta && meta.hidden ) {
		return 'fields';
	}

	return 'fields';
}

/**
 * Normalize select options from PPOM (object or array) to { value, label }[].
 *
 * @param {*} raw Options from schema.
 * @return {Array<{value: string, label: string}>}
 */
export function normalizeSelectOptions( raw ) {
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
	if ( typeof raw === 'object' ) {
		return Object.keys( raw ).map( ( k ) => ( {
			value: String( k ),
			label: String( raw[ k ] ),
		} ) );
	}
	return [];
}
