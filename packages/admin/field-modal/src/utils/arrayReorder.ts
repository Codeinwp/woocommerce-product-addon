/**
 * Move `rows[from]` to insertion `slot` in `[0, rows.length]`.
 * `slot=0` → before first, `slot=rows.length` → after last.
 * After removing `from`, slots to its right collapse by one — this is handled
 * internally so callers can pass the raw drop-edge slot.
 */
export function arrayReorder< T >(
	rows: T[],
	from: number,
	slot: number
): T[] {
	if ( from < 0 || from >= rows.length ) {
		return rows;
	}
	if ( slot < 0 || slot > rows.length ) {
		return rows;
	}
	const adjusted = slot > from ? slot - 1 : slot;
	if ( adjusted === from ) {
		return rows;
	}
	const next = [ ...rows ];
	const [ moved ] = next.splice( from, 1 );
	next.splice( adjusted, 0, moved );
	return next;
}
