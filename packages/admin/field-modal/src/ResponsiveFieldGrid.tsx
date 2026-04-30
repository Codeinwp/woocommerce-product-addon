/**
 * Responsive grid with full-width rows reserved for block controls.
 * Short textareas stay in the regular grid so settings remain compact.
 */
import { Grid, GridItem } from '@chakra-ui/react';
import { renderSettingRow } from './schemaSettingControl';
import type { SettingRowContext } from './types/fieldModal';

export interface ResponsiveFieldGridProps {
	entries: Array< { key: string; meta: Record< string, unknown > } >;
	ctx: SettingRowContext;
}

const FULL_ROW_TEXTAREA_KEYS = new Set( [ 'html' ] );

function settingType( meta: Record< string, unknown > ) {
	return meta.type ? String( meta.type ) : 'text';
}

function visibleEntries(
	entries: Array< { key: string; meta: Record< string, unknown > } >
) {
	return entries.filter( ( entry ) => ! entry.meta.hidden );
}

export function ResponsiveFieldGrid( {
	entries,
	ctx,
}: ResponsiveFieldGridProps ) {
	const layoutEntries = visibleEntries( entries );

	if ( ! layoutEntries.length ) {
		return null;
	}
	return (
		<Grid
			templateColumns={ {
				base: '1fr',
				md: 'repeat(2, minmax(0, 1fr))',
				xl: 'repeat(3, minmax(0, 1fr))',
			} }
			columnGap={ { base: 3, md: 4 } }
			rowGap={ 2 }
		>
			{ layoutEntries.map(
				( {
					key,
					meta,
				}: {
					key: string;
					meta: Record< string, unknown >;
				} ) => {
					const type = settingType( meta );
					const fullRow =
						( type === 'textarea' &&
							FULL_ROW_TEXTAREA_KEYS.has( key ) ) ||
						type === 'html-conditions' ||
						type === 'paired' ||
						type === 'paired-cropper' ||
						type === 'paired-quantity';
					return (
						<GridItem
							key={ key }
							colSpan={ {
								base: 1,
								md: fullRow ? 2 : 1,
								xl: fullRow ? 3 : 1,
							} }
						>
							{ renderSettingRow( key, meta, ctx ) }
						</GridItem>
					);
				}
			) }
		</Grid>
	);
}
