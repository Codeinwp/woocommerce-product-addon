<?php
/**
 * Registers the PPOM admin settings framework and settings-page assets.
 *
 * @package PPOM
 * @subpackage Settings
 *
 * @see ppom_load_free_options()
 * @see ppom_load_pro_options()
 */

/* 
**========== Block direct access =========== 
*/
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Stores settings-panel structure and persists PPOM admin settings.
 *
 * Registers the PPOM settings page, tracks tabs, panels, and fields, and
 * generates the saved CSS output consumed by the frontend runtime.
 */
class PPOM_SettingsFramework {

	private static $ins = null;

	/**
	 * Option name used to persist settings-panel values (`{id}-settings_panel`).
	 *
	 * @var string
	 */
	private static $save_key = '';

	/**
	 * Cached copy of saved settings (reserved / legacy; not actively populated).
	 *
	 * @var string
	 */
	private static $saved_settings = '';

	/**
	 * Registered settings tabs and nested panel definitions.
	 *
	 * @var array<string, mixed>
	 */
	public $tabs = array();

	/**
	 * Flat list of all registered panel definitions.
	 *
	 * @var array<string, mixed>
	 */
	public $panels = array();

	/**
	 * All registered setting field definitions keyed by setting ID.
	 *
	 * @var array<string, mixed>
	 */
	public $settings_array = array();

	/**
	 * Merged framework configuration (IDs, menu placement, labels, etc.).
	 *
	 * @var array<string, mixed>
	 */
	public $config = array();

	/**
	 * Base URL for settings-panel CSS/JS assets under `backend/assets`.
	 *
	 * @var string
	 */
	private static $assets_url = '';

	/**
	 * Optional reference to a scripts helper class (legacy; reserved for extensions).
	 *
	 * @var mixed|null
	 */
	public $scripts_class;

	/**
	 * Registers the settings framework hooks and save endpoints.
	 *
	 * @return void
	 */
	public function __construct() {
		$this->config = array(
			'id'         => 'ppom',
			'plugin_url' => PPOM_URL,
		);

		add_action( 'init', array( $this, 'register_config' ) );

		self::$assets_url = $this->get_config( 'plugin_url' ) . '/backend/assets';

		// Save settings key
		self::$save_key = "{$this->get_config('id')}-settings_panel";

		// Save Settings action
		add_action(
			'wp_ajax_' . $this->get_config( 'id' ) . '_settings_panel_action',
			array(
				$this,
				'save_settings',
			) 
		);

		// PPOM settings migration action, it only used for ppom plugin
		add_action( 'admin_post_ppom_migrate_settings_panel', array( $this, 'ppom_migrate_settings_panel' ) );

		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );

		add_action( 'admin_enqueue_scripts', array( $this, 'load_scripts' ), 999 );

		// add_action( 'in_admin_header', array( $this, 'remove_admin_notices' ), 99 );
		// delete_option('ppom_settings_migration_done');
	}

	// Settings structure registration.


	/**
	 * Singleton accessor for the settings framework.
	 *
	 * @return self
	 */
	public static function get_instance() {

		// create a new object if it doesn't exist.
		is_null( self::$ins ) && self::$ins = new self();

		return self::$ins;
	}


	/**
	 * Merges default menu and branding options into `$this->config`.
	 *
	 * @return void
	 */
	public function register_config() {

		$config = array(
			'name'            => __( 'PPOM Admin Settings', 'woocommerce-product-addon' ),
			'version'         => '1.0',
			'form_tag'        => false,
			'menu_type'       => 'wc',
			'menu_title'      => __( 'PPOM Settings', 'woocommerce-product-addon' ),
			'menu_page_title' => __( 'PPOM Settings', 'woocommerce-product-addon' ),
			'menu_slug'       => 'ppom_settings',
			'wc_slug'         => 'wc-settings',
			'menu_capability' => 'manage_options',
			'menu_parent'     => 'woocommerce',
			'menu_position'   => 50,
			'plugin_name'     => __( 'PPOM', 'woocommerce-product-addon' ),
			'plugin_version'  => PPOM_VERSION,
			'plugin_domain'   => 'woocommerce-product-addon',
		);

		$this->config = array_merge( $this->config, $config );
	}


	/**
	 * Reads a value from the merged framework configuration.
	 *
	 * @param string $key Config key (e.g. `menu_slug`, `plugin_url`).
	 *
	 * @return mixed Empty string when the key is missing.
	 */
	public function get_config( $key ) {

		$value = '';
		if ( isset( $this->config[ $key ] ) ) {
			$value = $this->config[ $key ];
		}

		return $value;
	}


	/**
	 * Adds the PPOM tab to the WooCommerce settings tabs list.
	 *
	 * @param array<string, string> $settings_tabs Tab slug => label pairs from WooCommerce.
	 *
	 * @return array<string, string>
	 */
	public function add_settings_tab( $settings_tabs ) {

		$settings_tabs[ $this->get_config( 'menu_slug' ) ] = $this->get_config( 'menu_title' );

		return $settings_tabs;
	}


	/**
	 * Renders the configured PPOM settings tabs and panels.
	 *
	 * @return void
	 */
	public function render_settings_panel() {

		wp_dequeue_script( 'woocommerce_settings' );

		self::load_template(
			'/templates/admin-settings.php',
			array(
				'tabs'      => $this->tabs,
				'class_ins' => $this,
			) 
		);
	}


	/**
	 * Registers one or more top-level settings tabs (skips keys that already exist).
	 *
	 * @param array<string, mixed> $tabs Tab definitions keyed by tab ID.
	 *
	 * @return $this
	 */
	public function register_tabs( $tabs ) {

		if ( array_intersect_key( $tabs, $this->tabs ) ) {
			return $this;
		}

		$this->tabs = array_merge( $this->tabs, $tabs );

		return $this;
	}


	/**
	 * Attaches panel definitions to a tab and merges them into the flat `$this->panels` list.
	 *
	 * @param string               $tab_id Target tab ID.
	 * @param array<string, mixed> $panels Panel definitions keyed by panel ID.
	 *
	 * @return $this
	 */
	public function register_panel( $tab_id, $panels ) {


		$panels = apply_filters( 'ppom_register_panel', $panels );
		// only used for store all Panels on single array
		$this->panels = array_merge( $this->panels, $panels );

		$existing_panels                 = ! isset( $this->tabs[ $tab_id ]['panels'] ) ? array() : $this->tabs[ $tab_id ]['panels'];
		$this->tabs[ $tab_id ]['panels'] = array_merge( $existing_panels, $panels );

		return $this;
	}


	/**
	 * Registers settings definitions under a panel.
	 *
	 * @param string               $panel_id Panel ID that receives the settings.
	 * @param array<string, mixed> $settings Settings definitions keyed by setting ID.
	 *
	 * @return $this
	 *
	 * @see ppom_load_free_options()
	 * @see ppom_load_pro_options()
	 */
	public function register_setting( $panel_id, $settings ) {

		$settings = apply_filters( 'ppom_register_settings', $settings );
		// only used for store all settings on single array
		$this->settings_array = array_merge( $this->settings_array, $settings );

		$this->tabs = array_map(
			function ( $tab ) use ( $panel_id, $settings ) {
				if ( isset( $tab['panels'] ) && isset( $tab['panels'][ $panel_id ] ) ) {
						$existing_settings                      = ! isset( $tab['panels'][ $panel_id ]['settings'] ) ? array() : $tab['panels'][ $panel_id ]['settings'];
						$tab['panels'][ $panel_id ]['settings'] = array_merge( $existing_settings, $settings );
				}

				return $tab;
			},
			$this->tabs 
		);

		return $this;
	}

	// Settings persistence and CSS generation.


	/**
	 * Includes the admin input partial for a settings field based on its `type`.
	 *
	 * @param array<string, mixed> $input_meta Field definition including `type` and `input_id`.
	 *
	 * @return void
	 */
	public function load_inputs( $input_meta ) {

		$type = isset( $input_meta['type'] ) ? $input_meta['type'] : '';

		self::load_template(
			"/templates/inputs/{$type}.php",
			array(
				'input_meta' => $input_meta,
				'class_ins'  => $this,
			) 
		);
	}


	/**
	 * Reorders settings entries that declare a `move_to` target key.
	 *
	 * @param array<string|int, mixed> $settings Associative settings array.
	 *
	 * @return array<string|int, mixed>
	 */
	public static function settings_position_controller( $settings ) {

		$new_settings = array();
		foreach ( $settings as $index => $value ) {

			$move_to = isset( $value['move_to'] ) ? $value['move_to'] : '';

			if ( $move_to != '' ) {

				$settings = self::reposition_array_element( $settings, $move_to, $index );
			}
		}

		return $settings;
	}


	/**
	 * Moves the element at key `$move` to immediately before key `$find`.
	 *
	 * @param array<string|int, mixed> $arr  Source array.
	 * @param string|int               $find Key to insert before.
	 * @param string|int               $move Key of the element to relocate.
	 *
	 * @return array<string|int, mixed> Unchanged `$arr` if keys are missing.
	 */
	public static function reposition_array_element( $arr, $find, $move ) {

		if ( ! isset( $arr[ $find ], $arr[ $move ] ) ) {
			return $arr;
		}

		$elem  = array( $move => $arr[ $move ] );
		$start = array_splice( $arr, 0, array_search( $find, array_keys( $arr ) ) );
		unset( $start[ $move ] );

		return $start + $elem + $arr;
	}


	/**
	 * Persists the submitted PPOM settings-panel payload.
	 *
	 * Sanitizes the submitted settings array, regenerates the aggregated
	 * frontend CSS output, and writes the normalized settings into the panel
	 * option key.
	 *
	 * @return void
	 */
	public function save_settings() {

		if ( ! isset( $_POST['ppom_settings_nonce'] )
			|| ! wp_verify_nonce( $_POST['ppom_settings_nonce'], 'ppom_settings_nonce_action' )
			|| ! ppom_security_role()
		) {
			$response = array(
				'status'  => 'error',
				'message' => __( 'Sorry, you are not allowed to perform this action please try again', 'woocommerce-product-addon' ),
			);

			wp_send_json( $response );
		}


		$response = array();
		if ( isset( $_REQUEST[ self::$save_key ] ) ) {
			$_REQUEST[ self::$save_key ] = array_filter(
				$_REQUEST[ self::$save_key ],
				function ( $key ) {
					return strpos( $key, '_locked' ) === false;
				},
				ARRAY_FILTER_USE_KEY 
			);
			// $settings_meta = $_REQUEST[self::$save_key];
			$settings_meta = array_map(
				function ( $setting ) {

					$setting = is_array( $setting ) ? array_map( 'sanitize_text_field', $setting ) : sanitize_text_field( $setting );

					return $setting;
				},
				$_REQUEST[ self::$save_key ] 
			);

			// Generate and saved css
			$this->generate_css( $settings_meta );

			update_option( self::$save_key, $settings_meta );

			$response = array(
				'status'  => 'success',
				'message' => __( 'Settings saved successfully.', 'woocommerce-product-addon' ),
			);

		} else {
			$response = array(
				'status'  => 'error',
				'message' => __( 'Settings saved error.', 'woocommerce-product-addon' ),
			);
		}

		wp_send_json( $response );
	}


	/**
	 * Regenerates the CSS output derived from settings-panel fields.
	 *
	 * @param array $settings_meta Sanitized settings-panel values.
	 *
	 * @return void
	 *
	 * @see PPOM_FRONTEND_SCRIPTS::add_inline_css()
	 */
	public function generate_css( $settings_meta ) {

		$setting_fields = array_intersect_key( $this->settings_array, $settings_meta );

		$ppom_css_output = '';
		foreach ( $settings_meta as $key => $val ) {

			if ( $val != '' ) {
				$settings = $this->settings_array[ $key ];
				$output   = isset( $settings['output'] ) ? $settings['output'] : array();
				$type     = isset( $settings['type'] ) ? $settings['type'] : '';
				$mode     = isset( $settings['mode'] ) ? $settings['mode'] : '';
				$enqueue  = isset( $settings['enqueue'] ) ? $settings['enqueue'] : true;

				if ( ! $enqueue ) {
					continue;
				}

				if ( $type == 'css_editor' ) {
					$css_val          = isset( $val[ $mode ] ) ? $val[ $mode ] : '';
					$ppom_css_output .= $this->generate_css_editor_output_css( $settings, $css_val );
				} elseif ( $type == 'typography' ) {
					$ppom_css_output .= $this->generate_typograpy_output_css( $settings, $val );
				} elseif ( is_array( $output ) && ! empty( $output ) ) {
					foreach ( $output as $css_key => $css_selector ) {
						$ppom_css_output .= $css_selector . '{' . $css_key . ':' . $val . ' !important;}';
					}
				}
			}
		}

		// update ppom css output
		update_option( 'ppom_css_output', $ppom_css_output );
	}


	/**
	 * Resolves a tab, panel, or setting definition by key.
	 *
	 * @param string $type One of `tabs`, `panels`, or `settings`.
	 * @param string $key  Identifier within that bucket.
	 *
	 * @return mixed|null
	 */
	public function _get( $type, $key ) {

		$return = null;
		switch ( $type ) {

			case 'tabs':
				$return = isset( $this->tabs[ $key ] ) ? $this->tabs[ $key ] : null;
				break;

			case 'panels':
				$return = isset( $this->panels[ $key ] ) ? $this->panels[ $key ] : null;
				break;

			case 'settings':
				$return = isset( $this->settings_array[ $key ] ) ? $this->settings_array[ $key ] : null;
				break;
		}

		return $return;
	}


	/**
	 * Builds the HTML `name` attribute for a saved settings field.
	 *
	 * @param string $id Setting / input ID.
	 *
	 * @return string Dot/bracket notation key under the panel option.
	 */
	public static function get_form_name( $id ) {

		if ( empty( $id ) ) {
			return '';
		}

		return self::$save_key . '[' . esc_attr( $id ) . ']';
	}


	/**
	 * Returns a single value from the persisted settings-panel option.
	 *
	 * @param string     $key     Setting ID.
	 * @param mixed|null $default Value when the key is absent or empty string.
	 *
	 * @return mixed|null
	 */
	public static function get_saved_settings( $key, $default = null ) {

		$saved_settings = get_option( self::$save_key );

		$value = $default !== null ? $default : null;

		if ( isset( $saved_settings[ $key ] ) && $saved_settings[ $key ] != '' ) {
			$value = $saved_settings[ $key ];
		}

		return $value;
	}


	/**
	 * Migrates legacy PPOM options into the settings-panel option format.
	 *
	 * @return void
	 *
	 * @see ppom_settings_migrated()
	 */
	public function ppom_migrate_settings_panel() {

		if ( ! isset( $_GET['ppom_migrate_nonce'] )
			|| ! wp_verify_nonce( $_GET['ppom_migrate_nonce'], 'ppom_migrate_nonce_action' )
		) {
			wp_die( 'Sorry, you are not allowed to clone', 'ppom' );
		}

		$ppom_settings_url = admin_url( 'admin.php' );
		$ppom_settings_url = add_query_arg(
			array(
				'page' => 'wc-settings',
				'tab'  => 'ppom_settings',
			),
			$ppom_settings_url
		);

		// if( ppom_settings_migrated() && $old_settings == 'yes' ) return '';

		// Migrate to back old settings
		$old_settings = isset( $_GET['old_settings'] ) ? sanitize_text_field( $_GET['old_settings'] ) : '';
		if ( $old_settings == 'yes' ) {
			delete_option( 'ppom_settings_migration_done' );

			wp_redirect( esc_url_raw( $ppom_settings_url ) );
			exit;
		} else {

			$legacy_values = array();
			$panel_fields  = $this->settings_array;

			// Getting the keys
			foreach ( $panel_fields as $key => $meta ) {
				$legacy_values[ $key ] = get_option( $key );
			}

			update_option( self::$save_key, $legacy_values );

			update_option( 'ppom_settings_migration_done', 1 );

			wp_redirect( esc_url_raw( $ppom_settings_url ) );
			exit;
		}
	}

	// Settings-page assets and helpers.


	/**
	 * CSS border-style choices for css_editor border mode.
	 *
	 * @return array<string, string> Value => translated label.
	 */
	public function border_style() {

		$style = array(
			'none'   => __( 'None', 'woocommerce-product-addon' ),
			'solid'  => __( 'Solid', 'woocommerce-product-addon' ),
			'dotted' => __( 'Dotted', 'woocommerce-product-addon' ),
			'dashed' => __( 'Dashed', 'woocommerce-product-addon' ),
			'double' => __( 'Double', 'woocommerce-product-addon' ),
			'groove' => __( 'Groove', 'woocommerce-product-addon' ),
			'rige'   => __( 'Ridge', 'woocommerce-product-addon' ),
			'inset'  => __( 'Inset', 'woocommerce-product-addon' ),
			'outset' => __( 'Outset', 'woocommerce-product-addon' ),
		);

		return $style;
	}


	/**
	 * Side keys and labels/icons for css_editor margin, padding, and border fields.
	 *
	 * @return array<string, array{title: string, icon?: string}>
	 */
	public function css_editor_options() {

		$option = array(
			'top'    => array(
				'title' => __( 'Top', 'woocommerce-product-addon' ),
				'icon'  => 'dashicons dashicons-arrow-up-alt',
			),
			'right'  => array(
				'title' => __( 'Right', 'woocommerce-product-addon' ),
				'icon'  => 'dashicons dashicons-arrow-right-alt',
			),
			'bottom' => array(
				'title' => __( 'Bottom', 'woocommerce-product-addon' ),
				'icon'  => 'dashicons dashicons-arrow-down-alt',
			),
			'left'   => array(
				'title' => __( 'Left', 'woocommerce-product-addon' ),
				'icon'  => 'dashicons dashicons-arrow-left-alt',
			),
		);

		return $option;
	}


	/**
	 * Typography sub-field keys and labels for typography settings rows.
	 *
	 * @return array<string, array{title: string}>
	 */
	public function typography_options() {

		$option = array(
			'font-size'   => array(
				'title' => __( 'Font Size', 'woocommerce-product-addon' ),
			),
			'line-height' => array(
				'title' => __( 'Line Height', 'woocommerce-product-addon' ),
			),
			'color'       => array(
				'title' => __( 'Font Color', 'woocommerce-product-addon' ),
			),
		);

		return $option;
	}


	/**
	 * Builds CSS rules for a `css_editor` field (border, margin, or padding).
	 *
	 * @param array<string, mixed>         $settings Field definition including `output` and `mode`.
	 * @param array<string, string>|string $css_val  Per-side values; border mode uses nested width/style/color.
	 *
	 * @return string Concatenated CSS rule or empty string.
	 */
	public function generate_css_editor_output_css( $settings, $css_val ) {

		$css      = '';
		$css_prop = '';
		$output   = isset( $settings['output'] ) ? $settings['output'] : array();
		$type     = isset( $settings['type'] ) ? $settings['type'] : '';
		$mode     = isset( $settings['mode'] ) ? $settings['mode'] : '';

		$border_types = $this->css_editor_options();

		foreach ( $border_types as $key => $meta ) {

			if ( $mode == 'border' ) {
				if ( isset( $css_val[ $key ], $css_val['style'], $css_val['color'] ) && $css_val[ $key ] != '' && $css_val['color'] != '' && $css_val['style'] != 'none' ) {
					$css_prop .= $mode . '-' . $key . '-width:' . $css_val[ $key ] . '!important;';
				}
				if ( isset( $css_val[ $key ], $css_val['style'], $css_val['color'] ) && $css_val[ $key ] != '' && $css_val['color'] != '' && $css_val['style'] != 'none' ) {
					$css_prop .= $mode . '-' . $key . '-color:' . $css_val['color'] . '!important;';
				}
				if ( isset( $css_val[ $key ], $css_val['style'], $css_val['color'] ) && $css_val[ $key ] != '' && $css_val['color'] != '' && $css_val['style'] != 'none' ) {
					$css_prop .= $mode . '-' . $key . '-style:' . $css_val['style'] . '!important;';
				}
			} elseif ( isset( $css_val[ $key ] ) && $css_val[ $key ] != '' ) {
					$css_prop .= $mode . '-' . $key . ':' . $css_val[ $key ] . '!important;';
			}
		}

		if ( $css_prop != '' ) {
			$css .= $output . '{' . $css_prop . '}';
		}

		return $css;
	}


	/**
	 * Builds CSS rules for a `typography` settings field.
	 *
	 * @param array<string, mixed> $settings Field definition including string `output` selector.
	 * @param array<string, mixed> $css_val  Values keyed by typography option (e.g. font-size).
	 *
	 * @return string Concatenated CSS rule or empty string.
	 */
	public function generate_typograpy_output_css( $settings, $css_val ) {

		$css      = '';
		$css_prop = '';
		$output   = isset( $settings['output'] ) ? $settings['output'] : array();
		$type     = isset( $settings['type'] ) ? $settings['type'] : '';
		$mode     = isset( $settings['mode'] ) ? $settings['mode'] : '';

		$options = $this->typography_options();

		foreach ( $options as $key => $meta ) {
			if ( isset( $css_val[ $key ] ) && $css_val[ $key ] != '' ) {
				$css_prop .= $key . ':' . $css_val[ $key ] . '!important;';
			}
		}

		if ( $css_prop != '' ) {
			$css .= $output . '{' . $css_prop . '}';
		}

		return $css;
	}


	/**
	 * Loads a PHP template from `backend/` with optional extracted variables.
	 *
	 * @param string               $file_name Path relative to `backend/` (e.g. `/templates/admin-settings.php`).
	 * @param array<string, mixed> $variables Variables to `extract()` into the template scope.
	 *
	 * @return void Terminates with `die()` when the file is missing.
	 */
	public static function load_template( $file_name, $variables = array() ) {

		extract( $variables );

		$file_path = PPOM_PATH . '/backend/' . $file_name;

		if ( file_exists( $file_path ) ) {
			include $file_path;
		} else {
			die( 'File not found' . $file_path );
		}
	}


	/**
	 * Registers the admin menu entry or WooCommerce settings tab for this panel.
	 *
	 * @return void
	 */
	public function add_admin_menu() {

		if ( $this->get_config( 'menu_type' ) === 'menu' ) {

			$menu = add_menu_page(
				$this->get_config( 'menu_page_title' ),
				$this->get_config( 'menu_title' ),
				$this->get_config( 'menu_capability' ),
				$this->get_config( 'menu_slug' ),
				array( $this, 'render_settings_panel' ),
				$this->get_config( 'menu_icon' ),
				$this->get_config( 'menu_position' )
			);

		} elseif ( $this->get_config( 'menu_type' ) === 'submenu' ) {

			$menu = add_submenu_page(
				$this->get_config( 'menu_parent' ),
				$this->get_config( 'menu_page_title' ),
				$this->get_config( 'menu_title' ),
				$this->get_config( 'menu_capability' ),
				$this->get_config( 'menu_slug' ),
				array( $this, 'render_settings_panel' ),
				$this->get_config( 'menu_position' )
			);
		} else {

			// It only used for ppom settings ppom_settings_migrated()
			if ( ppom_settings_migrated() ) {
				add_filter( 'woocommerce_settings_tabs_array', array( $this, 'add_settings_tab' ), 50 );
				add_action( 'woocommerce_settings_tabs_ppom_settings', array( $this, 'render_settings_panel' ) );
			}
		}
	}


	/**
	 * Script handles and metadata for the settings UI (filtered).
	 *
	 * @return array<string, array{src: string, deps: array<int, string>, version: string}>
	 */
	public function get_scripts() {

		$register_scripts = array(
			'nmsf-notifications-lib'   => array(
				'src'     => self::$assets_url . '/notifications/notifications.js',
				'deps'    => array( 'jquery' ),
				'version' => '1.0',
			),
			'nmsf-tooltip-lib'         => array(
				'src'     => self::$assets_url . '/tooltip/tooltip.js',
				'deps'    => array( 'jquery' ),
				'version' => '1.0',
			),
			'nmsf-videopopup-lib'      => array(
				'src'     => self::$assets_url . '/videopopup/videopopup.js',
				'deps'    => array( 'jquery' ),
				'version' => $this->get_config( 'plugin_version' ),
			),
			'nmsf-deps-lib'            => array(
				'src'     => self::$assets_url . '/deps.js',
				'deps'    => array( 'jquery' ),
				'version' => '1.0',
			),
			'nmsf-settings-panel'      => array(
				'src'     => self::$assets_url . '/settings.js',
				'deps'    => array( 'jquery' ),
				'version' => '1.0',
			),
			'nmsf-wpcolorpicker-alpha' => array(
				'src'     => self::$assets_url . '/wp-color-picker-alpha.min.js',
				'deps'    => array( 'jquery' ),
				'version' => '1.0',
			),
		);

		return apply_filters( 'ppom_admin_global_settings_scripts_before_register', $register_scripts );
	}


	/**
	 * Stylesheet handles and metadata for the settings UI (filtered).
	 *
	 * @return array<string, array{src: string, deps: array<int, string>, version: string}>
	 */
	public function get_styles() {

		$register_styles = array(
			'nmsf-notifications-lib' => array(
				'src'     => self::$assets_url . '/notifications/notifications.css',
				'deps'    => array(),
				'version' => $this->get_config( 'plugin_version' ),
			),
			'nmsf-tooltip-lib'       => array(
				'src'     => self::$assets_url . '/tooltip/tooltip.css',
				'deps'    => array(),
				'version' => $this->get_config( 'plugin_version' ),
			),
			'nmsf-videopopup-lib'    => array(
				'src'     => self::$assets_url . '/videopopup/videopopup.css',
				'deps'    => array(),
				'version' => '4.0.0',
			),
			'nmsf-grid-lib'          => array(
				'src'     => self::$assets_url . '/grid-layout.css',
				'deps'    => array(),
				'version' => '4.0.0',
			),
			'nmsf-settings-panel'    => array(
				'src'     => self::$assets_url . '/settings.css',
				'deps'    => array(),
				'version' => '4.0.0',
			),
		);

		return apply_filters( 'ppom_admin_global_settings_styles_before_register', $register_styles );
	}


	/**
	 * Loads the PPOM settings-page scripts, styles, and localized data.
	 *
	 * @return string|void Empty string when the current screen is not the PPOM settings page; otherwise void.
	 */
	public function load_scripts() {

		$is_settings_page = $this->is_settings_page();

		if ( ! $is_settings_page ) {
			return '';
		}

		$all_scripts = $this->get_scripts();
		$all_styles  = $this->get_styles();

		// Register all styles & scripts
		PPOM_SCRIPTS::register_scripts( $all_scripts );
		PPOM_SCRIPTS::register_styles( $all_styles );

		// WP color picker
		PPOM_SCRIPTS::enqueue_style( 'wp-color-picker' );
		PPOM_SCRIPTS::enqueue_script( 'wp-color-picker' );
		PPOM_SCRIPTS::enqueue_script( 'nmsf-wpcolorpicker-alpha' );

		// Alert notification library
		PPOM_SCRIPTS::enqueue_style( 'nmsf-notifications-lib' );
		PPOM_SCRIPTS::enqueue_script( 'nmsf-notifications-lib' );

		// Powertip tooltip library
		PPOM_SCRIPTS::enqueue_style( 'nmsf-tooltip-lib' );
		PPOM_SCRIPTS::enqueue_script( 'nmsf-tooltip-lib' );

		// Videopopup library   
		PPOM_SCRIPTS::enqueue_style( 'nmsf-videopopup-lib' );
		PPOM_SCRIPTS::enqueue_script( 'nmsf-videopopup-lib' );

		// nmsf grid library    
		PPOM_SCRIPTS::enqueue_style( 'nmsf-grid-lib' );

		// Condition base settings library  
		PPOM_SCRIPTS::enqueue_script( 'nmsf-deps-lib' );

		// Settings panel scripts
		PPOM_SCRIPTS::enqueue_style( 'nmsf-settings-panel' );
		PPOM_SCRIPTS::enqueue_script( 'nmsf-settings-panel' );

		$this->set_localize_data( 'nmsf-settings-panel', 'nmsf_vars' );

		do_action( 'themeisle_internal_page', PPOM_PRODUCT_SLUG, 'settings' );
	}


	/**
	 * Localizes runtime data for the settings-panel scripts.
	 *
	 * @param string $handle         Script handle receiving the data.
	 * @param string $var_name       JS variable name.
	 * @param array  $js_vars        Handle-specific JS vars.
	 * @param array  $global_js_vars Shared JS vars.
	 *
	 * @return void
	 */
	public function set_localize_data( $handle, $var_name, $js_vars = array(), $global_js_vars = array() ) {

		switch ( $handle ) {

			case 'nmsf-settings-panel':
				$localize_data = array(
					'migrate_back_msg'                     => __( 'Are you sure?', 'woocommerce-product-addon' ),
					'administrator_role_cannot_be_removed' => esc_html__( 'The administrator role cannot be removed.', 'woocommerce-product-addon' ),
					'active'                               => esc_html__( 'Active', 'woocommerce-product-addon' ),
					'inactive'                             => esc_html__( 'Inactive', 'woocommerce-product-addon' ),
					'copied'                               => esc_html__( 'Copied', 'woocommerce-product-addon' ),
				);

				break;
		}

		$localize_data = array_merge( $js_vars, $localize_data, $global_js_vars );

		$localize_data = apply_filters( 'ppom_get_admin_global_settings_localize_script_data', $localize_data, $handle );

		PPOM_SCRIPTS::localize_script( $handle, $var_name, $localize_data );
	}


	/**
	 * Strips default admin notices on the settings panel screen (hook currently unused).
	 *
	 * @return void
	 */
	public function remove_admin_notices() {

		$is_settings_page = $this->is_settings_page();

		if ( $is_settings_page ) {
			remove_all_actions( 'user_admin_notices' );
			remove_all_actions( 'admin_notices' );
		}
	}


	/**
	 * Determines whether the current admin request targets the PPOM settings page.
	 *
	 * @return bool
	 */
	public function is_settings_page() {

		$current_screen = get_current_screen();

		$id               = substr( $current_screen->id, - strlen( $this->get_config( 'menu_slug' ) ) );
		$is_settings_page = false;

		switch ( $this->get_config( 'menu_type' ) ) {
			case 'wc':
				if ( isset( $_GET['page'], $_GET['tab'] ) && $_GET['page'] == 'wc-settings' && $_GET['tab'] == $this->get_config( 'menu_slug' ) && ppom_settings_migrated() ) {
					$is_settings_page = true;
				}
				break;

			default:
				if ( $id == $this->get_config( 'menu_slug' ) ) {
					$is_settings_page = true;
				}
				break;
		}

		return $is_settings_page;
	}


	/**
	 * Inserts an item into an array at a fixed position.
	 *
	 * @param array $items    Source array.
	 * @param array $item     Item to insert.
	 * @param int   $position Numeric insertion offset.
	 *
	 * @return array
	 */
	public function insert_at( $items = array(), $item = array(), $position = 0 ) {
		$previous_items = array_slice( $items, 0, $position, true );
		$next_items     = array_slice( $items, $position, null, true );

		return $previous_items + $item + $next_items;
	}
}

PPOMSETTINGS();

/**
 * Returns the shared PPOM settings framework instance.
 *
 * @return PPOM_SettingsFramework
 */
function PPOMSETTINGS() {
	return PPOM_SettingsFramework::get_instance();
}
