<?php
/**
 * Themeisle SDK compatibility metadata and PPOM marketing filter registrations.
 *
 * @package PPOM
 */

namespace PPOM\Core\Bootstrap;

use PPOM\Core\RegisterHooks;

/**
 * @since 33.0.19
 */
final class SdkCompatAndMetadataRegistration implements RegisterHooks {

	/**
	 * @return void
	 */
	public function register(): void {
		add_filter(
			'themeisle_sdk_compatibilities/' . PPOM_BASENAME,
			function ( $compatibilities ) {
				$compatibilities['ppompro'] = array(
					'basefile'  => defined( 'PPOM_PRO_PATH' ) ? PPOM_PRO_PATH . '/ppom.php' : '',
					'required'  => '25.0',
					'tested_up' => '26.0',
				);

				return $compatibilities;
			}
		);

		add_filter(
			'woocommerce_product_addon_about_us_metadata',
			function () {
				return array(
					'location' => 'ppom',
					'logo'     => PPOM_URL . '/images/logo.jpg',
				);
			}
		);

		add_filter(
			'woocommerce_product_addon_float_widget_metadata',
			function () {
				return array(
					'logo'                 => PPOM_URL . '/images/help.svg',
					'nice_name'            => 'PPOM',
					'primary_color'        => '#313350',
					'pages'                => array( 'woocommerce_page_ppom' ),
					'has_upgrade_menu'     => ! defined( 'PPOM_PRO_PATH' ),
					'upgrade_link'         => tsdk_utmify( tsdk_translate_link( PPOM_UPGRADE_URL ), 'float_widget' ),
					'documentation_link'   => 'https://rviv.ly/C1cmSQ',
					'premium_support_link' => defined( 'PPOM_PRO_PATH' ) ? tsdk_translate_link( (string) tsdk_support_link( PPOM_PRO_PATH . '/ppom.php' ) ) : '',
					'feature_request_link' => tsdk_translate_link( 'https://store.themeisle.com/suggest-a-feature/' ),
				);
			}
		);

		add_filter(
			'woocommerce_product_addon_welcome_metadata',
			function () {
				return array(
					'is_enabled' => ! defined( 'PPOM_PRO_PATH' ),
					'pro_name'   => 'PPOM PRO',
					'logo'       => PPOM_URL . '/images/logo.jpg',
					'cta_link'   => 'https://rviv.ly/fJhjZN',
				);
			}
		);

		add_filter(
			'woocommerce_product_addon_welcome_upsell_message',
			function () {
				return '<p>You\'ve been using <b>{product}</b> for 7 days now and we appreciate your loyalty! We also want to make sure you\'re getting the most out of our product. That\'s why we\'re offering you a special deal - upgrade to <b>{pro_product}</b> in the next 5 days and receive a discount of <b>up to 55%</b>. <a href="{cta_link}" target="_blank">Upgrade now</a> and unlock all the amazing features of <b>{pro_product}</b>!</p>';
			}
		);
	}
}
