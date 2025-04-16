<?php
/**
 * Class PPOM_Survey file.
 *
 * @package PPOM_Survey
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'PPOM_Survey' ) ) {

	/**
	 * PPOM Survey class.
	 */
	class PPOM_Survey {

		/**
		 * The maximum limit of created groups to count. Used in the duration of the cached value.
		 *
		 * @var int
		 */
		public const GROUPS_COUNT_LIMIT = 100;

		/**
		 * Reference to singleton insance.
		 *
		 * @var [PPOM_Survey]
		 */
		public static $instance = null;

		/**
		 * Init hooks.
		 */
		public function init() {
			add_filter( 'themeisle-sdk/survey/' . PPOM_PRODUCT_SLUG, array( $this, 'get_survey_metadata' ), 10, 2 );
		}

		/**
		 * Get instance
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		/**
		 * Get the data used for the survey.
		 *
		 * @return array
		 * @see survey.js
		 */
		public function get_survey_metadata( $data, $page_slug ) {
			if ( defined( 'CYPRESS_TESTING' ) ) {
				return $data;
			}

			$license_status     = apply_filters( 'product_ppom_license_status', 'invalid' );
			$license_plan       = intval( apply_filters( 'product_ppom_license_plan', -1 ) );
			$license_key        = apply_filters( 'product_ppom_license_key', '' );
			$group_fields_count = get_transient( PPOM_GROUPS_COUNT_CACHE_KEY );

			if ( false === $group_fields_count ) {
				$group_fields_count = NM_PersonalizedProduct::get_product_meta_count( self::GROUPS_COUNT_LIMIT );
				set_transient( PPOM_GROUPS_COUNT_CACHE_KEY, $group_fields_count, self::GROUPS_COUNT_LIMIT === $group_fields_count ? WEEK_IN_SECONDS : 12 * HOUR_IN_SECONDS );
			}

			$install_days_number = intval( ( time() - get_option( 'woocommerce_product_addon_install', time() ) ) / DAY_IN_SECONDS );

			$data = array(
				'environmentId'     => 'clza3s4zm000h10km1699nlli',
				'attributes'        => array(
					'install_days_number' => $install_days_number,
					'free_version'        => PPOM_VERSION,
					'license_status'      => $license_status,
					'field_groups_count'  => intval( $group_fields_count )
				)
			);

			if ( 1 <= $license_plan ) {
				$data['attributes']['plan'] = NM_PersonalizedProduct::get_license_category( $license_plan );
			}

			if ( ! empty( $license_key ) ) {
				$data['attributes']['license_key'] = apply_filters( 'themeisle_sdk_secret_masking', $license_key );
			}

			if ( defined( 'PPOM_PRO_VERSION' ) ) {
				$data['attributes']['pro_version'] = PPOM_PRO_VERSION;
			}

			return $data;
		}
	}
}
