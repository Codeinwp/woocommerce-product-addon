<?php
/**
 * PPOM Elementor Integration Class
 *
 * It will register the new elementor widgets/controls
 *
 * @since 1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


class PPOM_ELEMENTOR {

	/**
	 * Class Instance var
	 *
	 * @var self|null
	 */
	private static $_instance = null;


	/**
	 * Hooks Elementor integration after plugins load.
	 *
	 * @return void
	 */
	public function __construct() {
		add_action( 'plugins_loaded', array( $this, 'load' ) );
	}


	/**
	 * Singleton accessor.
	 *
	 * @return self
	 */
	public static function instance() {

		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}


	/**
	 * Checks if Elementor has installed & loaded
	 *
	 * @return void
	 */
	public function load() {

		if ( $this->is_compatible() ) {
			add_action( 'elementor/init', array( $this, 'init' ) );
		}
	}


	/**
	 * Compatibility Checks
	 *
	 * @return bool
	 */
	public function is_compatible() {

		// Check if Elementor installed and activated
		if ( ! did_action( 'elementor/loaded' ) ) {
			return false;
		}

		return true;
	}


	/**
	 * Initialize the Class
	 *
	 * @return void
	 */
	public function init() {

		// $frontend = \Elementor\Plugin::$instance->frontend->has_elementor_in_page();

		// Register New Weidget
		add_action( 'elementor/widgets/widgets_registered', array( $this, 'init_widgets' ) );

		// Register New Controls
		// add_action( 'elementor/controls/controls_registered', [ $this, 'init_controls' ] );

		// Register Widget Styles
		add_action( 'elementor/frontend/after_enqueue_styles', array( $this, 'widget_styles' ) );
	}


	/**
	 * Init Widgets
	 *
	 * Include widgets files and register them
	 *
	 * @return void
	 */
	public function init_widgets() {

		// Include Widget files
		require_once PPOM_PATH . '/classes/integrations/elementor/shortcode-widget.php';

		// Register Widget
		\Elementor\Plugin::instance()->widgets_manager->register_widget_type( new \PPOM_Elementor_Shortcode_Widget() );
	}


	/**
	 * Load Widgets Styles
	 *
	 * @return void
	 */
	public function widget_styles() {
		wp_enqueue_style( 'ppom-main' );
	}
}

PPOM_ELEMENTOR::instance();
