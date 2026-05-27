<?php
/**
 * Unit tests for selected PPOM\Support\Helpers methods.
 *
 * @package ppom-pro
 */

use PPOM\Support\Helpers;

/**
 * Stub for the external Wholesale Prices plugin class (WWP_Wholesale_Roles).
 * Defined here so tests can exercise the PPOM\Support namespace boundary without
 * requiring the actual Wholesale Prices plugin to be installed.
 */
if ( ! class_exists( 'WWP_Wholesale_Roles' ) ) {
	class WWP_Wholesale_Roles {
		/** @var self|null */
		private static $instance = null;

		/** @var array */
		public $roles = array();

		public static function getInstance() {
			if ( null === self::$instance ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		public function getUserRoles() {
			return $this->roles;
		}
	}
}

/**
 * Stub for the external Wholesale Prices plugin class (WWP_Wholesale_Prices).
 */
if ( ! class_exists( 'WWP_Wholesale_Prices' ) ) {
	class WWP_Wholesale_Prices {
		/** @var array */
		public static $stub_price_arr = array();

		public static function get_product_wholesale_price_on_shop_v3( $product_id, $user_wholesale_role ) {
			return self::$stub_price_arr;
		}
	}
}

/**
 * Stub for the external Wholesale Prices plugin class (WWP_Helper_Functions).
 */
if ( ! class_exists( 'WWP_Helper_Functions' ) ) {
	class WWP_Helper_Functions {
		public static function wwp_get_product_id( $product ) {
			return $product->get_id();
		}
	}
}

/**
 * @covers \PPOM\Support\Helpers
 */
class Test_Helpers_Src extends PPOM_Test_Case {

	/**
	 * @var callable|null
	 */
	private $field_col_filter;

	/**
	 * @return void
	 */
	public function tearDown(): void {
		if ( null !== $this->field_col_filter ) {
			remove_filter( 'ppom_field_col', $this->field_col_filter );
			$this->field_col_filter = null;
		}
		parent::tearDown();
	}

	/**
	 * @return void
	 */
	public function test_get_field_colum_defaults_to_twelve() {
		$this->assertSame( 12, Helpers::get_field_colum( array() ) );
	}

	/**
	 * @return void
	 */
	public function test_get_field_colum_respects_numeric_width() {
		$this->assertSame( 6, Helpers::get_field_colum( array( 'width' => 6 ) ) );
	}

	/**
	 * @return void
	 */
	public function test_get_field_colum_percent_width_falls_back_to_twelve() {
		$this->assertSame( 12, Helpers::get_field_colum( array( 'width' => '50%' ) ) );
	}

	/**
	 * @return void
	 */
	public function test_get_field_colum_clamps_above_twelve() {
		$this->assertSame( 12, Helpers::get_field_colum( array( 'width' => 24 ) ) );
	}

	/**
	 * @return void
	 */
	public function test_get_field_colum_apply_filters_ppom_field_col() {
		$this->field_col_filter = static function ( $col ) {
			return 4;
		};
		add_filter( 'ppom_field_col', $this->field_col_filter, 10, 1 );

		$this->assertSame( 4, Helpers::get_field_colum( array( 'width' => 8 ) ) );
	}

	/**
	 * @return void
	 */
	public function test_get_product_id_simple_product() {
		$product = $this->create_simple_product();

		$this->assertSame( $product->get_id(), Helpers::get_product_id( $product ) );
	}

	/**
	 * @return void
	 */
	public function test_get_product_id_variation_returns_parent() {
		$pair = $this->create_variable_product_with_variation();

		$this->assertSame( $pair['product']->get_id(), Helpers::get_product_id( $pair['variation'] ) );
	}

	/**
	 * @return void
	 */
	public function test_wpml_translate_passes_through_arrays() {
		$arr = array( 'a' => 1 );
		$this->assertSame( $arr, Helpers::wpml_translate( $arr, 'PPOM' ) );
	}

	/**
	 * Regression: get_product_price() must not fatal when WWP_Wholesale_Prices exposes
	 * get_product_wholesale_price_on_shop_v3() and the code runs under the PPOM\Support
	 * namespace. In PPOM 34.0.1 the external class references lacked the global `\` prefix,
	 * causing "Class PPOM\Support\WWP_Wholesale_Roles not found" fatals.
	 *
	 * @return void
	 */
	public function test_get_product_price_returns_wholesale_price_when_wwp_v3_available() {
		$product = $this->create_simple_product( array( 'regular_price' => '20' ) );

		WWP_Wholesale_Prices::$stub_price_arr = array( 'wholesale_price' => '15' );

		$price = Helpers::get_product_price( $product );

		WWP_Wholesale_Prices::$stub_price_arr = array();

		$this->assertSame( '15', (string) $price );
	}

	/**
	 * When the WWP wholesale price array is empty, get_product_price() falls back
	 * to the regular WooCommerce product price.
	 *
	 * @return void
	 */
	public function test_get_product_price_falls_back_when_wwp_price_is_empty() {
		$product = $this->create_simple_product( array( 'regular_price' => '20' ) );

		WWP_Wholesale_Prices::$stub_price_arr = array();

		$price = Helpers::get_product_price( $product );

		$this->assertSame( '20', (string) $price );
	}
}
