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
			'ppom_logger_data',
			function ( $data ) {
				if ( ! is_array( $data ) ) {
					$data = array();
				}

				$usage = get_option( 'ppom_template_usage_counts', array() );
				if ( is_array( $usage ) && ! empty( $usage ) ) {
					$sanitized_usage = array();
					foreach ( $usage as $template_key => $template_count ) {
						$sanitized_usage[ sanitize_text_field( (string) $template_key ) ] = (int) $template_count;
					}
					$data['template_usage'] = $sanitized_usage;
				}

				$groups_count = get_transient( PPOM_GROUPS_COUNT_CACHE_KEY );
				if ( false === $groups_count && class_exists( 'NM_PersonalizedProduct' ) ) {
					$groups_count = \NM_PersonalizedProduct::get_product_meta_count( 100 );
					set_transient( PPOM_GROUPS_COUNT_CACHE_KEY, $groups_count, 12 * HOUR_IN_SECONDS );
				}
				if ( false !== $groups_count ) {
					$data['field_groups_count'] = max( 0, (int) $groups_count );
				}

				$tracked_general_settings = array(
					'ppom_disable_bootstrap',
					'ppom_enable_legacy_inputs_rendering',
					'ppom_new_conditions',
					'ppom_legacy_price',
					'ppom_permission_mfields',
				);
				$saved_settings           = get_option( 'ppom-settings_panel', array() );
				if ( is_array( $saved_settings ) ) {
					$general_settings = array();
					foreach ( $tracked_general_settings as $setting_key ) {
						if ( ! array_key_exists( $setting_key, $saved_settings ) ) {
							continue;
						}
						$value = $saved_settings[ $setting_key ];
						if ( is_array( $value ) ) {
							$value = array_values(
								array_map(
									static function ( $item ) {
										return sanitize_text_field( (string) $item );
									},
									$value
								)
							);
						} elseif ( is_scalar( $value ) ) {
							$value = sanitize_text_field( (string) $value );
						} else {
							continue;
						}
						$general_settings[ $setting_key ] = $value;
					}
					if ( ! empty( $general_settings ) ) {
						$data['general_settings'] = $general_settings;
					}
				}

				return $data;
			}
		);

		add_filter(
			'themeisle_sdk_compatibilities/' . PPOM_BASENAME,
			function ( $compatibilities ) {
				$compatibilities['ppompro'] = array(
					'basefile'  => defined( 'PPOM_PRO_PATH' ) ? PPOM_PRO_PATH . '/ppom.php' : '',
					'required'  => '25.0',
					'tested_up' => '27.0',
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
