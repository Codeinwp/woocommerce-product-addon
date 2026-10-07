<?php
/**
 * Sanitizer for uploaded SVG files.
 *
 * @package PPOM
 */

namespace PPOM\Files;

use enshrined\svgSanitize\Sanitizer;

/**
 * Cleans uploads with enshrined/svg-sanitize, remote references included.
 *
 * @internal
 */
final class SvgSanitizer {

	/**
	 * Cleans an SVG document.
	 *
	 * @param string $svg Raw file contents.
	 *
	 * @return string|null Cleaned markup, or null when the input is not a usable SVG.
	 */
	public static function clean( string $svg ): ?string {

		// ext-dom is optional; a DTD is refused rather than silently stripped.
		if ( '' === trim( $svg ) || ! class_exists( 'DOMDocument' ) || preg_match( '/<!\s*(DOCTYPE|ENTITY)/i', $svg ) ) {
			return null;
		}

		$sanitizer = new Sanitizer();
		$sanitizer->removeXMLTag( true );
		$sanitizer->removeRemoteReferences( true );

		try {
			$clean = $sanitizer->sanitize( $svg );
		} catch ( \Throwable $e ) {
			// Well-formed XML without an <svg> root throws.
			return null;
		}

		return is_string( $clean ) && '' !== $clean ? $clean : null;
	}
}
