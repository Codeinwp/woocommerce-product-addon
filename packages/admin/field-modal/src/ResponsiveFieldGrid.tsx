/**
 * Two-column grid with full-width rows for most textarea / block controls.
 * The `description` setting stays one column so it aligns with Title / Data name.
 */
import { Grid, GridItem } from '@chakra-ui/react';
import { renderSettingRow } from './schemaSettingControl';
import type { SettingRowContext } from './types/fieldModal';

export interface ResponsiveFieldGridProps {
	entries: Array< { key: string; meta: Record< string, unknown > } >;
	ctx: SettingRowContext;
}

export function ResponsiveFieldGrid( {
	entries,
	ctx,
}: ResponsiveFieldGridProps ) {
	if ( ! entries.length ) {
		return null;
	}
	return (
		<Grid
			templateColumns={ { base: '1fr', md: 'repeat(2, minmax(0, 1fr))' } }
			columnGap={ { base: 3, md: 4 } }
			rowGap={ 2 }
		>
			{ entries.map(
				( {
					key,
					meta,
				}: {
					key: string;
					meta: Record< string, unknown >;
				} ) => {
					const type = meta.type ? String( meta.type ) : 'text';
					const fullRow =
						( type === 'textarea' && key !== 'description' ) ||
						type === 'html-conditions' ||
						type === 'paired' ||
						type === 'paired-cropper' ||
						type === 'paired-quantity';
					return (
						<GridItem
							key={ key }
							colSpan={ { base: 1, md: fullRow ? 2 : 1 } }
						>
							{ renderSettingRow( key, meta, ctx ) }
						</GridItem>
					);
				}
			) }
		</Grid>
	);
}
