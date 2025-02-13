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

			$license_data = get_option( 'ppom_pro_license_data', array() );

			$install_days_number = round( ( time() - get_option( 'woocommerce_product_addon_install', time() ) ) / DAY_IN_SECONDS );
			$install_category    = 0; 

			if ( 1 < $install_days_number && 8 > $install_days_number ) {
				$install_category = 7;
			} elseif ( 8 <= $install_days_number && 31 > $install_days_number ) {
				$install_category = 30;
			} elseif ( 30 < $install_days_number && 90 > $install_days_number ) {
				$install_category = 90;
			} elseif ( 90 <= $install_days_number ) {
				$install_category = 91;
			}

			$data = array(
				'environmentId'     => 'clza3s4zm000h10km1699nlli',
				'attributes'        => array(
					'days_since_install'  => strval( $install_category ),
					'install_days_number' => $install_days_number,
					'free_version'        => PPOM_VERSION,
					'license_status'      => ! empty( $license_data->license ) ? $license_data->license : 'invalid',
				)
			);

			if ( ! empty( $license_data->plan ) ) {
				$data['attributes']['plan'] = $this->plan_category( $license_data );
			}

			if ( isset( $license_data->key ) ) {
				$data['attributes']['license_key'] = apply_filters( 'themeisle_sdk_secret_masking', $license_data->key );
			}

			if ( defined( 'PPOM_PRO_VERSION' ) ) {
				$data['attributes']['pro_version'] = PPOM_PRO_VERSION;
			}

			return $data;
		}

		/**
		 * Get the plan category for the product plan ID.
		 *
		 * @param object $license_data The license data.
		 * @return int
		 */
		private static function plan_category( $license_data ) {

			if ( ! isset( $license_data->plan ) || ! is_numeric( $license_data->plan ) ) {
				return 0; // Free.
			}

			$plan             = (int) $license_data->plan;
			$current_category = -1;

			$categories = array(
				'1' => array( 1, 4, 9 ), // Personal.
				'2' => array( 2, 5, 8 ), // Business/Developer.
				'3' => array( 3, 6, 7, 10 ), // Agency.
			);

			foreach ( $categories as $category => $plans ) {
				if ( in_array( $plan, $plans, true ) ) {
					$current_category = (int) $category;
					break;
				}
			}

			return $current_category;
		}
	}
}
