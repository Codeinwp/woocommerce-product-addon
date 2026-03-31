<?php
/**
 * Shared helpers for PPOM PHPUnit coverage.
 *
 * @package ppom-pro
 */

abstract class PPOM_Test_Case extends WP_UnitTestCase {

	/**
	 * Reset globals and settings used by the helper-heavy tests.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$_POST    = array();
		$_GET     = array();
		$_REQUEST = array();

		$this->unset_ppom_option( 'ppom_rest_secret_key' );
		$this->unset_ppom_option( 'ppom_api_enable' );
		$this->unset_ppom_option( 'ppom_enable_client_validation' );
		$this->unset_ppom_option( 'ppom_taxable_option_price' );
	}

	/**
	 * Clean globals after each test.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		$_POST    = array();
		$_GET     = array();
		$_REQUEST = array();

		parent::tearDown();
	}

	/**
	 * Create and persist a simple WooCommerce product.
	 *
	 * @param array $args Product overrides.
	 *
	 * @return WC_Product_Simple
	 */
	protected function create_simple_product( $args = array() ) {
		$product = new WC_Product_Simple();

		$name          = isset( $args['name'] ) ? $args['name'] : 'PPOM Test Product ' . wp_generate_password( 6, false );
		$regular_price = isset( $args['regular_price'] ) ? (string) $args['regular_price'] : '10';

		$product->set_name( $name );
		$product->set_status( 'publish' );
		$product->set_catalog_visibility( 'visible' );
		$product->set_regular_price( $regular_price );
		$product->set_price( $regular_price );

		if ( ! empty( $args['manage_stock'] ) ) {
			$product->set_manage_stock( true );
		}

		if ( isset( $args['stock_quantity'] ) ) {
			$product->set_stock_quantity( (int) $args['stock_quantity'] );
		}

		if ( isset( $args['backorders'] ) ) {
			$product->set_backorders( $args['backorders'] ? 'yes' : 'no' );
		}

		$product_id = $product->save();

		return wc_get_product( $product_id );
	}

	/**
	 * Insert a PPOM field-group row and optionally attach it to a product.
	 *
	 * @param array $fields     PPOM field definitions.
	 * @param int   $product_id Optional product ID.
	 * @param array $overrides  Row overrides.
	 *
	 * @return int
	 */
	protected function insert_ppom_meta( $fields, $product_id = 0, $overrides = array() ) {
		global $wpdb;

		$ppom_table = $wpdb->prefix . PPOM_TABLE_META;
		$row        = array_merge(
			array(
				'productmeta_name'       => $product_id ? get_the_title( $product_id ) : 'PPOM Test Meta ' . wp_generate_password( 6, false ),
				'productmeta_validation' => 'no',
				'dynamic_price_display'  => 'no',
				'send_file_attachment'   => '',
				'show_cart_thumb'        => 'no',
				'aviary_api_key'         => '',
				'productmeta_style'      => '',
				'productmeta_categories' => '',
				'the_meta'               => wp_json_encode( $fields ),
				'productmeta_created'    => current_time( 'mysql' ),
			),
			$overrides
		);

		$inserted = $wpdb->insert( $ppom_table, $row );
		$this->assertNotFalse( $inserted );

		$meta_id = (int) $wpdb->insert_id;

		$fields = apply_filters( 'ppom_meta_data_saving', $fields, $meta_id );
		ppom_admin_update_ppom_meta_only( $meta_id, $fields );

		if ( $product_id ) {
			update_post_meta( $product_id, PPOM_PRODUCT_META_KEY, $meta_id );
		}

		return $meta_id;
	}

	/**
	 * Count PPOM field-group rows.
	 *
	 * @return int
	 */
	protected function ppom_meta_row_count() {
		global $wpdb;

		$ppom_table = $wpdb->prefix . PPOM_TABLE_META;

		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$ppom_table}" );
	}

	/**
	 * Read a single PPOM row.
	 *
	 * @param int $meta_id PPOM row ID.
	 *
	 * @return array|null
	 */
	protected function get_ppom_meta_row( $meta_id ) {
		global $wpdb;

		$ppom_table = $wpdb->prefix . PPOM_TABLE_META;

		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$ppom_table} WHERE productmeta_id = %d",
				$meta_id
			),
			ARRAY_A
		);
	}

	/**
	 * Persist a PPOM option for both legacy and migrated storage.
	 *
	 * @param string $key   Option key.
	 * @param mixed  $value Option value.
	 *
	 * @return void
	 */
	protected function set_ppom_option( $key, $value ) {
		update_option( $key, $value );

		$saved_settings = get_option( 'ppom-settings_panel', array() );
		if ( ! is_array( $saved_settings ) ) {
			$saved_settings = array();
		}

		$saved_settings[ $key ] = $value;
		update_option( 'ppom-settings_panel', $saved_settings );
	}

	/**
	 * Remove a PPOM option from both legacy and migrated storage.
	 *
	 * @param string $key Option key.
	 *
	 * @return void
	 */
	protected function unset_ppom_option( $key ) {
		delete_option( $key );

		$saved_settings = get_option( 'ppom-settings_panel', array() );
		if ( ! is_array( $saved_settings ) ) {
			return;
		}

		if ( array_key_exists( $key, $saved_settings ) ) {
			unset( $saved_settings[ $key ] );
			update_option( 'ppom-settings_panel', $saved_settings );
		}
	}
}
