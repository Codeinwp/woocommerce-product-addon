/**
 * Swap an element with its neighbour in a given direction (-1 up, +1 down).
 * Returns the original array unchanged when the swap would go out of bounds.
 */
export function arrayMove< T >(
	rows: T[],
	index: number,
	dir: -1 | 1
): T[] {
	const swapIndex = index + dir;
	if ( swapIndex < 0 || swapIndex >= rows.length ) {
		return rows;
	}
	const next = [ ...rows ];
	const tmp = next[ index ];
	next[ index ] = next[ swapIndex ];
	next[ swapIndex ] = tmp;
	return next;
}
