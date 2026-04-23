<?php
/**
 * Loads PPOM input classes and optional add-on input classes.
 *
 * @package PPOM
 * @subpackage Inputs
 */

/**
 * Loads PPOM input classes and related add-on integrations.
 */
class PPOM_Inputs {

	/**
	 * Plugin meta array from `ppom_get_plugin_meta()` (paths, URLs, shortname).
	 *
	 * @var array<string, mixed>
	 */
	public $plugin_meta;

	/**
	 * Queued script/style handles for `load_input_scripts()` (`shipped`, `custom` keys).
	 *
	 * @var array<string, mixed>
	 */
	public $input_scripts = array();

	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static $ins = null;

	/**
	 * Admin field-type icon HTML for the builder.
	 *
	 * @var string
	 */
	public $icon = '';


	/**
	 * Loads plugin meta used when resolving input class paths.
	 *
	 * @return void
	 */
	public function __construct() {

		$this->plugin_meta = ppom_get_plugin_meta();
	}

	/**
	 * Accessor for the shared inputs loader instance.
	 *
	 * @return self
	 */
	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	/**
	 * Loads the input class for a field type.
	 *
	 * @param string $type Field type.
	 *
	 * @return object|null
	 *
	 * @see PPOM_Form::render_input_template()
	 */
	function get_input( $type ) {

		$class_name = 'NM_' . ucfirst( $type ) . '_wooproduct';
		$file_name  = 'input.' . $type . '.php';

		if ( ! class_exists( $class_name ) ) {

			/**
			 * adding filter: nm_input_class-filename
			 *
			 * @since 6.7
			 * @since 7.6: changing path for eventcalendar addon
			 */

			$_inputs = '';
			switch ( $type ) {

				case 'eventcalendar':
					$_inputs = $this->plugin_meta['ppom_eventcalendar'];
					break;

				default:
					$_inputs = apply_filters( 'nm_input_class-' . $type, PPOM_PATH . "/classes/inputs/{$file_name}", $type );
					break;
			}


			if ( file_exists( $_inputs ) ) {

				include_once $_inputs;
				if ( class_exists( $class_name ) ) {
					return new $class_name();
				} else {
					return null;
				}           
			}
		} else {
			return new $class_name();
		}
	}

	/**
	 * Loads an add-on input class from the Facebook import plugin when present.
	 *
	 * @param string $type Input type slug matching `NM_{Type}_wooproduct`.
	 *
	 * @return object|null
	 */
	function get_addon( $type ) {

		$addon_directory = 'nm-facebook-import-addon';

		$facebook_class_file = WP_CONTENT_DIR . '/plugins/' . $addon_directory . '/index.php';
		if ( file_exists( $facebook_class_file ) ) {

			include_once $facebook_class_file;
			$class_name = 'NM_' . ucfirst( $type ) . '_wooproduct';
			if ( class_exists( $class_name ) ) {
				return new $class_name();
			} else {
				return null;
			}       
		}

		return null;
	}

	/**
	 * Enqueues scripts and styles previously stored in `$this->input_scripts`.
	 *
	 * @return void
	 */
	function load_input_scripts() {

		if ( ! empty( $this->input_scripts['shipped'] ) && is_array( $this->input_scripts['shipped'] ) ) {
			foreach ( $this->input_scripts['shipped'] as $handler ) {
				wp_enqueue_script( $handler );
			}
		}

		// Front-end scripts.
		if ( ! empty( $this->input_scripts['custom'] ) && is_array( $this->input_scripts['custom'] ) ) {
			foreach ( $this->input_scripts['custom'] as $scripts => $script ) {

				// checking if it is style
				// nm_personalizedproduct_pa($script);   
				if ( $script['type'] == 'js' ) {
					wp_enqueue_script( $this->plugin_meta['shortname'] . '-' . $script['script_name'], $this->plugin_meta['url'] . $script['script_source'], $script['depends'], '3.0', $script['in_footer'] );
				} else {
					wp_enqueue_style( $this->plugin_meta['shortname'] . '-' . $script['script_name'], $this->plugin_meta['url'] . $script['script_source'] );
				}
			}
		}
	}


	/**
	 * Whether the current request user agent looks like Internet Explorer / Trident.
	 *
	 * @return bool
	 */
	function if_browser_is_ie() {
		// print_r($_SERVER['HTTP_USER_AGENT']);

		if ( ! ( isset( $_SERVER['HTTP_USER_AGENT'] ) && ( strpos( $_SERVER['HTTP_USER_AGENT'], 'Trident' ) !== false || strpos( $_SERVER['HTTP_USER_AGENT'], 'MSIE' ) !== false ) ) ) {
			return false;
		} else {
			return true;
		}
	}

	/**
	 * Builds the current request URL including scheme and query string.
	 *
	 * @return string
	 */
	function current_page_url() {
		$page_url = 'http';
		if ( isset( $_SERVER['HTTPS'] ) ) {
			if ( $_SERVER['HTTPS'] == 'on' ) {
				$page_url .= 's';
			}
		}
		$page_url .= '://';
		if ( $_SERVER['SERVER_PORT'] != '80' ) {
			$page_url .= $_SERVER['SERVER_NAME'] . ':' . $_SERVER['SERVER_PORT'] . $_SERVER['REQUEST_URI'];
		} else {
			$page_url .= $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
		}

		return $page_url;
	}
}

/**
 * Returns the shared PPOM inputs loader instance.
 *
 * @return PPOM_Inputs
 */
function PPOM_Inputs() {
	return PPOM_Inputs::get_instance();
}
