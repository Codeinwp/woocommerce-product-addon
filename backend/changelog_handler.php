<?php
/**
 * Parses Themeisle-style changelog markdown into structured release data.
 *
 * @package PPOM
 * @subpackage Backend
 */

/**
 * Markdown changelog parser for in-admin release notes.
 */
class PPOM_Changelog_Handler {
	/**
	 * Reads and parses a changelog file into release buckets (fixes, features, tweaks).
	 *
	 * @param string $changelog_path Absolute filesystem path to the changelog file.
	 *
	 * @return list<array<string, mixed>>
	 */
	public function get_changelog( $changelog_path ) {

		if ( ! is_file( $changelog_path ) ) {
			return array();
		}

		if ( ! WP_Filesystem() ) {
			return array();
		}

		return $this->parse_changelog( $changelog_path );
	}

	/**
	 * Parses changelog lines into ordered release entries.
	 *
	 * @param string $changelog_path Absolute path to the changelog file.
	 *
	 * @return list<array<string, mixed>>
	 */
	private function parse_changelog( $changelog_path ) {
		WP_Filesystem();
		global $wp_filesystem;
		$changelog = $wp_filesystem->get_contents( $changelog_path );
		if ( is_wp_error( $changelog ) ) {
			$changelog = '';
		}
		$changelog     = explode( PHP_EOL, $changelog );
		$releases      = array();
		$release_count = 0;

		foreach ( $changelog as $changelog_line ) {
			if ( strpos( $changelog_line, '**Changes:**' ) !== false || empty( $changelog_line ) ) {
				continue;
			}
			if ( substr( ltrim( $changelog_line ), 0, 4 ) === '####' ) {
				++$release_count;

				preg_match( '/[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}/', $changelog_line, $found_v );
				preg_match( '/[0-9]{4}-[0-9]{1,2}-[0-9]{1,2}/', $changelog_line, $found_d );
				$releases[ $release_count ] = array(
					'version' => ! empty( $found_v ) ? $found_v[0] : '',
					'date'    => ! empty( $found_d ) ? $found_d[0] : '',
				);
				continue;
			}
			if ( substr( ltrim( $changelog_line ), 0, 3 ) === '###' ) {
				continue;
			}
			if ( preg_match( '/[*|-]?\s?(\[fix]|\[Fix]|fix|Fix|Fixed)[:]?\s?(\b|(?=\[))/', $changelog_line ) ) {
				$changelog_line                        = preg_replace( '/[*|-]?\s?(\[fix]|\[Fix]|fix|Fix)[:]?\s?(\b|(?=\[))/', '', $changelog_line );
				$releases[ $release_count ]['fixes'][] = $this->parse_md_and_clean( $changelog_line );
				continue;
			}

			if ( preg_match( '/[*|-]?\s?(\[feat]|\[Feat]|feat|Feat)[:]?\s?(\b|(?=\[))/', $changelog_line ) ) {
				$changelog_line                           = preg_replace( '/[*|-]?\s?(\[feat]|\[Feat]|feat|Feat)[:]?\s?(\b|(?=\[))/', '', $changelog_line );
				$releases[ $release_count ]['features'][] = $this->parse_md_and_clean( $changelog_line );
				continue;
			}

			$changelog_line = $this->parse_md_and_clean( $changelog_line );

			if ( empty( $changelog_line ) ) {
				continue;
			}

			$releases[ $release_count ]['tweaks'][] = $changelog_line;
		}

		return array_values( $releases );
	}

	/**
	 * Normalizes a changelog line and converts markdown links to HTML anchors.
	 *
	 * @param string $string Raw line content.
	 *
	 * @return string HTML-safe text with inline markdown links converted to anchor tags.
	 */
	private function parse_md_and_clean( $string ) {
		// Drop spaces, starting lines | asterisks.
		$string = trim( $string );
		$string = ltrim( $string, '*' );
		$string = ltrim( $string, '-' );

		// Replace markdown links with <a> tags.
		$string = preg_replace_callback(
			'/\[(.*?)]\((.*?)\)/',
			function ( $matches ) {
				return '<a href="' . $matches[2] . '" target="_blank" rel="noopener"><i class="dashicons dashicons-external"></i>' . $matches[1] . '</a>';
			},
			htmlspecialchars( $string )
		);

		return $string;
	}
}
