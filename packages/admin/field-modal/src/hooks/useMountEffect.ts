/**
 * Explicit escape hatch for one-time external system synchronization.
 */
import { useEffect } from '@wordpress/element';

export function useMountEffect( effect: () => void | ( () => void ) ) {
	/* eslint-disable-next-line react-hooks/exhaustive-deps */
	useEffect( effect, [] );
}
