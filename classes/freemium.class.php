<?php
/**
 * Class PPOM_Freemium
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PPOM_Freemium
 */
class PPOM_Freemium {
	const TAB_KEY_FREEMIUM_CFR = 'locked_conditional_field_repeater';

	public static $instance = null;

	/**
	 * Constructor
	 *
	 * @return void
	 */
	private function __construct()
	{
		add_filter( 'ppom_fields_tabs_show', array( $this, 'add_locked_cfr_tab' ), 10, 1 );
		add_filter( 'ppom_all_inputs', array( $this, 'locked_cfr_register_form_elements' ), PHP_INT_MAX );
	}

	/**
	 * Get instance
	 *
	 * @return void
	 */
	public static function get_instance() {
		if( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Add a tab to all PPOM field types as Conditional Repeater (PRO)
	 *
	 * @param  array $tabs Current tabs.
	 * @return array
	 */
	public function add_locked_cfr_tab( $tabs ) {
		if ( ppom_pro_is_installed() && PPOM()->is_license_of_type( 'plus' ) ) {
			return $tabs;
		}
		$tabs[self::TAB_KEY_FREEMIUM_CFR] = array(
			'label' => __( 'Conditional Repeater', 'woocommerce-product-addon' ). ' (' . __( 'PRO', 'woocommerce-product-addon' ) . ')',
			'class' => array( 'ppom-tabs-label' ),
			'field_depend' => array( 'all' )
		);
		return $tabs;
	}

	/**
	 * HTML content of the freemium Conditional Field Repeater
	 *
	 * @return string
	 */
	public function get_freemium_cfr_content() {
		if ( ppom_pro_is_installed() && PPOM()->is_license_of_type( 'plus' ) ) {
			return '';
		}
		ob_start();
		$upgrade_url = tsdk_utmify( tsdk_translate_link( PPOM_UPGRADE_URL ), 'lockedconditionalfield', 'ppompage' );
		?>
		<div class="freemium-cfr-content">
			<p><?php esc_html_e( 'The Conditional Repeater allows fields to be automatically repeated based on the value entered in another field, such as a Number, Variation Quantity, or Quantity Pack field. For example, if a user enters "2," two corresponding fields will appear. This feature is part of the PPOM Pro plugin.', 'woocommerce-product-addon' ); ?></p>
			<div class="notice notice-info mb-3">
				<p><strong><?php esc_html_e( 'Use Case:', 'woocommerce-product-addon' ); ?></strong> <?php esc_html_e( 'Selling personalized caps? With the Conditional Repeater, customers can select the number of caps (e.g., 5), and the feature will automatically generate 5 fields to enter unique names for each cap. This makes it simple to personalize multiple caps in one go!', 'woocommerce-product-addon' ); ?> <a href="https://demo-ppom-lite.vertisite.cloud/product/personalized-caps-using-conditional-repeater/" class="ppom-repeater-learn-more" target="_blank"><?php esc_html_e( 'View Demo', 'woocommerce-product-addon' ); ?> <span class="dashicons dashicons-external"></span></a></p>
			</div>

			<a target="_blank" class="btn btn-sm btn-secondary mr-2" href="https://docs.themeisle.com/article/1700-personalized-product-meta-manager#conditional-repeater"><?php echo __( 'Check Documentation', 'woocommerce-product-addon' ); ?></a>
			<a target="_blank" class="btn btn-sm btn-primary" href="<?php echo esc_url( $upgrade_url ); ?>"><?php echo __( 'Upgrade to Pro', 'woocommerce-product-addon' ); ?></a>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
     * Adds admin setting fields to all input types.
     *
     * @param  array $inputs current input classes
     * @return array
     */
    public function locked_cfr_register_form_elements( $inputs ) {
        return array_map( function($input_class) {
            if( ! is_object( $input_class ) || ! property_exists( $input_class, 'settings' ) || !is_array($input_class->settings) ) {
                return $input_class;
            }

            $input_class->settings['locked_cfr'] = array(
                'type' => 'checkbox',
                'title' => __( 'Enable Conditional Repeat', 'woocommerce-product-addon' ),
				'disabled' => true,
                'desc' => '',
				'tabs_class' => array( 'ppom_handle_' . self::TAB_KEY_FREEMIUM_CFR )
            );

            return $input_class;
        }, $inputs );
    }

	public function get_pro_fields() {
		return [
			[
				'title' => __( 'Collapse', 'woocommerce-product-addon' ),
				'icon'  => '<i class="fa fa-money" aria-hidden="true"></i>',
			],
			[
				'title' => __( 'Emojis', 'woocommerce-product-addon' ),
				'icon'  => '<i class="fa fa-user-plus" aria-hidden="true"></i>',
			],
			[
				'title' => __( 'Phone Input', 'woocommerce-product-addon' ),
				'icon'  => '<i class="fa fa-check" aria-hidden="true"></i>',
			],
			[
				'title' => __( 'Chained Input', 'woocommerce-product-addon' ),
				'icon'  => '<i class="fa fa-check" aria-hidden="true"></i>',
			],
			[
				'title' => __( 'Conditional Images', 'woocommerce-product-addon' ),
				'icon'  => '<i class="fa fa-picture-o" aria-hidden="true"></i>',
			],
			[
				'title' => __( 'Domain', 'woocommerce-product-addon' ),
				'icon'  => '<i class="fa fa-server" aria-hidden="true"></i>',
			],
			[
				'title' => __( 'Fonts Picker', 'woocommerce-product-addon' ),
				'icon'  => '<i class="fa fa-font" aria-hidden="true"></i>',
			],
			[
				'title' => __( 'Personalization Preview', 'woocommerce-product-addon' ),
				'icon'  => '<i class="fa fa-keyboard-o" aria-hidden="true"></i>',
			],
			[
				'title' => __( 'Text Counter', 'woocommerce-product-addon' ),
				'icon'  => '<i class="fa fa-comments-o" aria-hidden="true"></i>',
			],
			[
				'title' => __( 'Fixed Price', 'woocommerce-product-addon' ),
				'icon'  => '<i class="fa fa-money" aria-hidden="true"></i>',
			],
			[
				'title' => __( 'Select Option Quantity', 'woocommerce-product-addon' ),
				'icon'  => '<i class="fa fa-list-ol" aria-hidden="true"></i>',
			],
			[
				'title' => __( 'Image DropDown', 'woocommerce-product-addon' ),
				'icon'  => '<i class="fa fa-file-image-o" aria-hidden="true"></i>',
			],
			[
				'title' => __( 'Super List', 'woocommerce-product-addon' ),
				'icon'  => '<i class="fa fa-check" aria-hidden="true"></i>',
			],
			[
				'title' => __( 'Quantity Option', 'woocommerce-product-addon' ),
				'icon'  => '<i class="fa fa-money" aria-hidden="true"></i>',
			],
			[
				'title' => __( 'Quantities Pack', 'woocommerce-product-addon' ),
				'icon'  => '<i class="fa fa-list-alt" aria-hidden="true"></i>',
			],
			[
				'title' => __( 'Radio Switcher', 'woocommerce-product-addon' ),
				'icon'  => '<i class="fa fa-dot-circle-o" aria-hidden="true"></i>',
			],
			[
				'title' => __( 'Variation Matrix', 'woocommerce-product-addon' ),
				'icon'  => '<i class="fa fa-list-alt" aria-hidden="true"></i>',
			],
			[
				'title' => __( 'DateRange Input', 'woocommerce-product-addon' ),
				'icon'  => '<i class="fa fa-table" aria-hidden="true"></i>',
			],
			[
				'title' => __( 'Color picker', 'woocommerce-product-addon' ),
				'icon'  => '<i class="fa fa-modx" aria-hidden="true"></i>',
			],
			[
				'title' => __( 'File Input', 'woocommerce-product-addon' ),
				'icon'  => '<i class="fa fa-file" aria-hidden="true"></i>',
			],
			[
				'title' => __( 'Image Cropper', 'woocommerce-product-addon' ),
				'icon'  => '<i class="fa fa-crop" aria-hidden="true"></i>',
			],
			[
				'title' => __( 'Timezone Input', 'woocommerce-product-addon' ),
				'icon'  => '<i class="fa fa-clock-o" aria-hidden="true"></i>',
			],
			[
				'title' => __( 'Variation Quantity', 'woocommerce-product-addon' ),
				'icon'  => '<i class="fa fa-list-ol" aria-hidden="true"></i>',
			],
			[
				'title' => __( 'Images', 'woocommerce-product-addon' ),
				'icon'  => '<i class="fa fa-picture-o" aria-hidden="true"></i>',
			],
			[
				'title' => __( 'Price Matrix', 'woocommerce-product-addon' ),
				'icon'  => '<i class="fa fa-usd" aria-hidden="true"></i>',
			],
			[
				'title' => __( 'HTML', 'woocommerce-product-addon' ),
				'icon'  => '<i class="fa fa-code" aria-hidden="true"></i>',
			],
			[
				'title' => __( 'Color Palettes', 'woocommerce-product-addon' ),
				'icon'  => '<i class="fa fa-user-plus" aria-hidden="true"></i>',
			],
			[
				'title' => __( 'Audio / Video', 'woocommerce-product-addon' ),
				'icon'  => '<i class="fa fa-file-video-o" aria-hidden="true"></i>',
			],
			[
				'title' => __( 'Measure Input', 'woocommerce-product-addon' ),
				'icon'  => '<i class="fa fa-building-o" aria-hidden="true"></i>',
			],
			[
				'title' => __( 'Divider', 'woocommerce-product-addon' ),
				'icon'  => '<i class="fa fa-pencil-square-o" aria-hidden="true"></i>',
			],
		];
	}
}
