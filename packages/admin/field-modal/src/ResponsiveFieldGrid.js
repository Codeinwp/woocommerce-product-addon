/**
 * Two-column grid with full-width rows for textarea / block controls.
 */
import { Grid, GridItem } from '@chakra-ui/react';
import { renderSettingRow } from './schemaSettingControl';

/**
 * @param {Array<{ key: string, meta: Object }>} entries
 * @param {Object}                              ctx  Passed to renderSettingRow.
 */
export function ResponsiveFieldGrid( { entries, ctx } ) {
	if ( ! entries.length ) {
		return null;
	}
	return (
		<Grid
			templateColumns={ { base: '1fr', md: 'repeat(2, minmax(0, 1fr))' } }
			columnGap={ { base: 3, md: 4 } }
			rowGap={ 3 }
		>
			{ entries.map( ( { key, meta } ) => {
				const type = meta.type ? String( meta.type ) : 'text';
				const fullRow =
					type === 'textarea' ||
					type === 'html-conditions' ||
					type === 'paired';
				return (
					<GridItem
						key={ key }
						colSpan={ { base: 1, md: fullRow ? 2 : 1 } }
					>
						{ renderSettingRow( key, meta, ctx ) }
					</GridItem>
				);
			} ) }
		</Grid>
	);
}
