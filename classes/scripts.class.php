<?php
/**
 * Registers and enqueues PPOM scripts and styles (admin and shared helpers).
 *
 * @package PPOM
 * @subpackage Assets
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Thin wrapper around WordPress script/style registration for PPOM handles.
 */
class PPOM_SCRIPTS {

	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static $ins = null;

	/**
	 * Base URL for plugin assets (same as PPOM_URL).
	 *
	 * @var string
	 */
	public static $scripts_url = PPOM_URL;

	/**
	 * Current plugin version string.
	 *
	 * @var string
	 */
	public static $version = PPOM_VERSION;

	/**
	 * Reserved hook; PPOM registers assets via explicit enqueue methods.
	 *
	 * @return void
	 */
	public static function init() {
	}


	/**
	 * Registers a script handle with WordPress.
	 *
	 * @param string             $handle    Unique script handle.
	 * @param string             $path      Full URL to the script file.
	 * @param array<int, string> $deps      Script dependencies.
	 * @param string|false       $version   Version string or false.
	 * @param bool               $in_footer Whether to load in the footer.
	 *
	 * @return void
	 */
	private static function register_script( $handle, $path, $deps = array( 'jquery' ), $version = '', $in_footer = true ) {
		wp_register_script( $handle, $path, $deps, $version, $in_footer );
	}


	/**
	 * Registers (if needed) and enqueues a script.
	 *
	 * @param string             $handle    Script handle.
	 * @param string             $path      Full URL; required when the handle is not yet registered.
	 * @param array<int, string> $deps      Dependencies when registering.
	 * @param string|false       $version   Version when registering.
	 * @param bool               $in_footer Load in footer when registering.
	 *
	 * @return void
	 */
	public static function enqueue_script( $handle, $path = '', $deps = array( 'jquery' ), $version = '', $in_footer = true ) {
		if ( ! wp_script_is( $handle, 'registered' ) && $path ) {
			self::register_script( $handle, $path, $deps, $version, $in_footer );
		}
		wp_enqueue_script( $handle );
	}


	/**
	 * Registers a stylesheet handle with WordPress.
	 *
	 * @param string             $handle  Style handle.
	 * @param string             $path    Full URL to the CSS file.
	 * @param array<int, string> $deps    Style dependencies.
	 * @param string|false       $version Version query arg.
	 * @param string             $media   Media descriptor.
	 *
	 * @return void
	 */
	private static function register_style( $handle, $path, $deps = array(), $version = false, $media = 'all' ) {
		wp_register_style( $handle, $path, $deps, $version, $media );
	}


	/**
	 * Registers (if needed) and enqueues a stylesheet.
	 *
	 * @param string             $handle  Style handle.
	 * @param string             $path    Full URL; required when the handle is not yet registered.
	 * @param array<int, string> $deps    Dependencies when registering.
	 * @param string|false       $version Version when registering.
	 * @param string             $media   Media when registering.
	 *
	 * @return void
	 */
	public static function enqueue_style( $handle, $path = '', $deps = array(), $version = '', $media = 'all' ) {
		if ( ! wp_style_is( $handle, 'registered' ) && $path ) {
			self::register_style( $handle, $path, $deps, $version, $media );
		}
		wp_enqueue_style( $handle );
	}


	/**
	 * Registers many scripts from a handle => props map.
	 *
	 * @param array<string, array{src: string, deps: array<int, string>, version: string, footer?: bool}> $register_scripts Registry entries.
	 *
	 * @return void
	 */
	public static function register_scripts( $register_scripts ) {

		foreach ( $register_scripts as $handle => $props ) {
			$is_footer = isset( $props['footer'] ) ? $props['footer'] : true;
			self::register_script( $handle, $props['src'], $props['deps'], $props['version'], $is_footer );
		}
	}


	/**
	 * Registers many styles from a handle => props map.
	 *
	 * @param array<string, array{src: string, deps: array<int, string>, version: string|false}> $register_styles Registry entries.
	 *
	 * @return void
	 */
	public static function register_styles( $register_styles ) {

		foreach ( $register_styles as $handle => $props ) {
			self::register_style( $handle, $props['src'], $props['deps'], $props['version'], 'all' );
		}
	}


	/**
	 * Attaches inline data to an enqueued script.
	 *
	 * @param string               $handle       Registered script handle.
	 * @param string               $js_var_name  JavaScript object name.
	 * @param array<string, mixed> $js_var_data  Data exported to the browser.
	 *
	 * @return void
	 */
	public static function localize_script( $handle, $js_var_name, $js_var_data = array() ) {

		if ( wp_script_is( $handle ) ) {

			if ( empty( $js_var_data ) ) {
				return;
			}

			wp_localize_script( $handle, $js_var_name, $js_var_data );
		}
	}


	/**
	 * Appends inline CSS to a registered stylesheet.
	 *
	 * @param string $handle Registered style handle.
	 * @param string $css    Raw CSS (no wrapping tags).
	 *
	 * @return void
	 */
	public static function inline_style( $handle, $css ) {

		if ( $css != '' ) {
			wp_add_inline_style( $handle, $css );
		}
	}


	/**
	 * Appends inline JavaScript after a registered script.
	 *
	 * @param string $handle Registered script handle.
	 * @param string $js     JavaScript source.
	 *
	 * @return void
	 */
	public static function inline_script( $handle, $js ) {

		if ( $js != '' ) {
			wp_add_inline_script( $handle, $js );
		}
	}


	/**
	 * Returns the plugin asset base URL.
	 *
	 * @return string
	 */
	public static function get_url() {

		return self::$scripts_url;
	}

	/**
	 * Returns the plugin version string.
	 *
	 * @return string
	 */
	public static function get_version() {

		return self::$version;
	}

	/**
	 * Singleton accessor (legacy; most call sites use static methods only).
	 *
	 * @return self
	 */
	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}
}
