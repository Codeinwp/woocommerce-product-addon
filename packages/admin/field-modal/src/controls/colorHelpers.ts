/**
 * Color value normalization for Chakra ColorPicker (PPOM stored values vary).
 */
import { parseColor } from '@chakra-ui/react';

/** `rgb(…)` / `rgba(…)` → `#rrggbb` without relying on Ark `toString('hex')` (can throw rgb→hex). */
export function rgbCssStringToHex6( rgb: string ): string {
	const m = rgb.match(
		/rgba?\(\s*([\d.]+)\s*[,\s]\s*([\d.]+)\s*[,\s]\s*([\d.]+)/i
	);
	if ( ! m ) {
		return '#000000';
	}
	const r = Math.max( 0, Math.min( 255, Math.round( Number( m[ 1 ] ) ) ) );
	const g = Math.max( 0, Math.min( 255, Math.round( Number( m[ 2 ] ) ) ) );
	const b = Math.max( 0, Math.min( 255, Math.round( Number( m[ 3 ] ) ) ) );
	return (
		'#' +
		[ r, g, b ]
			.map( ( c ) => c.toString( 16 ).padStart( 2, '0' ) )
			.join( '' )
	);
}

/** Normalize stored PPOM values (`#fff`, `rgb()`, named colors) to `#rrggbb` for `parseColor`. */
export function normalizeStoredColorToHex6( raw: string ): string {
	const s = raw.trim();
	if ( ! s ) {
		return '#000000';
	}
	if ( s[ 0 ] === '#' ) {
		const body = s.slice( 1 );
		if ( /^[0-9a-f]{3}$/i.test( body ) ) {
			const [ a, b, c ] = body.toLowerCase().split( '' );
			return `#${ a }${ a }${ b }${ b }${ c }${ c }`;
		}
		if ( /^[0-9a-f]{6}$/i.test( body ) ) {
			return `#${ body.toLowerCase() }`;
		}
	}
	if ( /^rgba?\(/i.test( s ) ) {
		return rgbCssStringToHex6( s );
	}
	try {
		const c = parseColor( s );
		try {
			return c.toString( 'hex' );
		} catch {
			try {
				return rgbCssStringToHex6( c.toString( 'rgb' ) );
			} catch {
				return '#000000';
			}
		}
	} catch {
		return '#000000';
	}
}

export function colorFromStoredValue( raw: unknown ) {
	const hex = normalizeStoredColorToHex6(
		raw == null ? '' : String( raw )
	);
	return parseColor( hex );
}

export function persistColorValueAsHex( value: unknown ) {
	const stringable = value as { toString( fmt: string ): string };
	try {
		return stringable.toString( 'hex' );
	} catch {
		try {
			return rgbCssStringToHex6( stringable.toString( 'rgb' ) );
		} catch {
			return '#000000';
		}
	}
}
