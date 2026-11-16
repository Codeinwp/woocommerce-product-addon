<?php
/**
 * Tests for the metadata PPOM hands to the Themeisle SDK Support Hub.
 *
 * @package ppom-pro
 */

namespace PPOM_Pro {
	if ( ! function_exists( __NAMESPACE__ . '\get_license_status' ) ) {
		/**
		 * Stands in for PPOM Pro's stored license lookup, which the unit suite does not load.
		 *
		 * @param bool $check_expiration Unused.
		 *
		 * @return string
		 */
		function get_license_status( $check_expiration = false ) {
			return (string) get_option( 'ppom_test_pro_license_status', 'not_active' );
		}
	}
}

namespace {
	/**
	 * Support Hub opt-in tests.
	 */
	class Test_Support_Hub extends WP_UnitTestCase {

		/**
		 * The filter returns the keys PPOM opts into, and nothing else.
		 */
		public function test_metadata_has_the_agreed_keys() {
			$data = apply_filters( 'woocommerce_product_addon_support_hub_metadata', array() );

			$this->assertIsArray( $data );
			$this->assertSame( array( 'is_pro', 'links' ), array_keys( $data ) );
		}

		/**
		 * The footer links point to PPOM's docs and wordpress.org forum.
		 */
		public function test_links_point_to_ppom() {
			$links = apply_filters( 'woocommerce_product_addon_support_hub_metadata', array() )['links'];

			$this->assertSame(
				array(
					'https://docs.themeisle.com/collection/1695-product-option-manager',
					'https://wordpress.org/support/plugin/woocommerce-product-addon/',
				),
				wp_list_pluck( $links, 'url' )
			);
			foreach ( $links as $link ) {
				$this->assertNotEmpty( $link['icon'] );
				$this->assertNotEmpty( $link['text'] );
			}
		}

		/**
		 * Messages are tagged as Pro only while the stored PPOM Pro license is valid,
		 * even when the license status filter is not registered yet.
		 */
		public function test_is_pro_reads_the_stored_pro_license() {
			remove_all_filters( 'product_ppom_license_status' );

			update_option( 'ppom_test_pro_license_status', 'expired' );
			$this->assertFalse( apply_filters( 'woocommerce_product_addon_support_hub_metadata', array() )['is_pro'] );

			update_option( 'ppom_test_pro_license_status', 'valid' );
			$this->assertTrue( apply_filters( 'woocommerce_product_addon_support_hub_metadata', array() )['is_pro'] );
		}

		/**
		 * The float widget is no longer registered: the Help button replaces it.
		 */
		public function test_float_widget_is_not_registered() {
			$this->assertSame( array(), apply_filters( 'woocommerce_product_addon_float_widget_metadata', array() ) );
		}
	}
}
