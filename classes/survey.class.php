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
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
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
		public function get_survey_metadata() {
			$license_data = get_option( 'ppom_pro_license_data', array() );
			$attributes   = array();
			$user_id      = 'ppom_' . ( ! empty( $license_data->key ) ? $license_data->key : preg_replace( '/[^\w\d]*/', '', get_site_url() ) ); // Use a normalized version of the site URL as a user ID for free users.

			$days_since_install = round( ( time() - get_option( 'woocommerce_product_addon_install', 0 ) ) / DAY_IN_SECONDS );
			$install_category   = 0; // Normalized value.
			if ( 0 === $days_since_install || 1 === $days_since_install ) {
				$install_category = 0;
			} elseif ( 1 < $days_since_install && 8 > $days_since_install ) {
				$install_category = 7;
			} elseif ( 8 <= $days_since_install && 31 > $days_since_install ) {
				$install_category = 30;
			} elseif ( 30 < $days_since_install && 90 > $days_since_install ) {
				$install_category = 90;
			} elseif ( 90 <= $days_since_install ) {
				$install_category = 91;
			}

			$attributes['days_since_install'] = strval( $install_category );
			$attributes['license_status']     = ! empty( $license_data->license ) ? $license_data->license : 'invalid';
			$attributes['free_version']       = PPOM_VERSION;

			if ( ! empty( $license_data->plan ) ) {
				$attributes['plan'] = $this->plan_category( $license_data );
			}

			if ( defined( 'PPOM_PRO_VERSION' ) ) {
				$attributes['pro_version'] = PPOM_PRO_VERSION;
			}

			return array(
				'userId'     => $user_id,
				'attributes' => $attributes,
			);
		}

		/**
		 * Enqueue scripts.
		 */
		public function enqueue_scripts() {

			if ( defined( 'CYPRESS_TESTING' ) ) {
				return;
			}

			$survey_handler = apply_filters( 'themeisle_sdk_dependency_script_handler', 'survey' );
			if ( empty( $survey_handler ) ) {
				return;
			}

			do_action( 'themeisle_sdk_dependency_enqueue_script', 'survey' );
			wp_enqueue_script( 'ppom_survey', PPOM_URL . '/js/survey.js', array( $survey_handler ), PPOM_VERSION, true );
			wp_localize_script( 'ppom_survey', 'PPOMSurveyData', $this->get_survey_metadata() );
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
