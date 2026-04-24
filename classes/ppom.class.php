<?php
/**
 * Resolves PPOM field groups and settings for products.
 *
 * @package PPOM
 * @subpackage Metadata
 */

/**
 * Resolves product-level PPOM metadata for frontend and cart/order flows.
 *
 * @since version 15.0
 *
 * @phpstan-import-type PPOM_Meta_Group_Row from PPOM_Meta_Repository
 */
class PPOM_Meta {

	protected static $wc_product;
	private static $ins = null;
	public static $product_id;

	/**
	 * Resolved category-based PPOM group IDs for the current product.
	 *
	 * @var array $category_meta
	 */
	public $category_meta = array();

	/**
	 * Candidate group rows that include category or tag assignments.
	 *
	 * Tags are fetched here as stored data, but core resolution matches
	 * categories directly and leaves tag-based behavior to extension filters.
	 *
	 * @var array $ppom_categories_and_tags_row
	 */
	public $ppom_categories_and_tags_row = array();

	/**
	 * Runtime settings row selected from the resolved PPOM groups.
	 *
	 * Empty `array()` before resolution; otherwise a DB row object (see {@see settings()}).
	 *
	 * @var object|array|null $ppom_settings
	 *
	 * @phpstan-var object|array|null $ppom_settings
	 */
	public $ppom_settings = array();

	/**
	 * Fields.
	 *
	 * @var bool $is_exists
	 */
	public $is_exists = false;

	/**
	 * Meta ID.
	 *
	 * @var int $single_meta_id
	 */
	public $single_meta_id = 0;

	/**
	 * Check has multiple meta.
	 *
	 * @var bool $has_multiple_meta
	 */
	public $has_multiple_meta = false;

	/**
	 * Check ajax validation enabled or not.
	 *
	 * @var bool $ajax_validation_enabled
	 */
	public $ajax_validation_enabled = false;

	/**
	 * Inline CSS.
	 *
	 * @var string $inline_css
	 */
	public $inline_css = '';

	/**
	 * Inline JS.
	 *
	 * @var string $inline_js
	 */
	public $inline_js = '';

	/**
	 * Price display.
	 *
	 * @var string $price_display
	 */
	public $price_display = '';

	/**
	 * Meta title.
	 *
	 * @var string $meta_title
	 */
	public $meta_title = '';

	/**
	 * Fields.
	 *
	 * @var array $fields
	 */
	public $fields = array();

	// QM-5
	var $meta_id;

	/**
	 * Resolves field groups, settings, and derived flags for a product.
	 *
	 * @param int|null $product_id Product ID used to resolve attached field groups.
	 *
	 * @return void
	 */
	// $product_id can be null if get instance to get data by meta_id
	function __construct( $product_id = null ) {

		self::$wc_product                   = wc_get_product( $product_id );
		$this->category_meta                = array();
		$this->ppom_categories_and_tags_row = \PPOM\Data\FieldGroupRepository::instance()->find_rows_with_categories_or_tags();
		$this->meta_id                      = $this->get_meta_id( $product_id );
		self::$product_id                   = $product_id;


		$this->ppom_settings = $this->settings();
		$this->fields        = $this->get_fields();

		// Now we are creating properties agains each methods in our Alpha class.
		$methods          = get_class_methods( $this );
		$excluded_methods = array(
			'__construct',
			'get_settings_by_id',
			'get_settings_by_ids',
			'get_fields_by_id',
			'settings',
			'all_ppom_with_categories',
			'ppom_has_category_meta',
			'get_meta_id',
			'get_fields',
			'has_unique_datanames',
			'get_instance',
		);

		foreach ( $methods as $method ) {
			if ( ! in_array( $method, $excluded_methods ) ) {
				$this->$method = $this->$method();
			}
		}

		// Retrieve fields with the repeater enabled.
		$is_cloned = is_array( $this->fields ) ? array_filter( array_column( $this->fields, 'is_cloned' ) ) : array();
		if ( isset( $this->ppom_settings->productmeta_validation ) && ! empty( $is_cloned ) ) {
			$this->ppom_settings->productmeta_validation = 'on';
		}
	}

	public static function get_instance( $product_id ) {

		// ALERT: This cause issues .. April 16, 2021: Najeeb
		// ( $product_id != self::$product_id || is_null(self::$ins) ) &&  self::$ins = new PPOM_Meta($product_id);

		is_null( self::$ins ) && self::$ins = new PPOM_Meta( $product_id );

		return self::$ins;
	}

	/**
	 * Resolves PPOM meta IDs for a product.
	 *
	 * Reads direct product assignments and category assignments before applying
	 * merge and override filters.
	 *
	 * @param int|null $product_id Product ID being resolved.
	 *
	 * @return array|int|null
	 *
	 * @see PPOM_PRODUCT_META_KEY
	 */
	public function get_meta_id( $product_id ) {

		$ppom_in_category = $this->ppom_has_category_meta( $product_id );

		$resolver = new \PPOM\Data\ProductConfigurationResolver();

		return $resolver->merge_meta_ids_from_product_and_categories( $product_id, $ppom_in_category );
	}

	// Properties functions
	function is_exists() {

		if ( $this->meta_id == 0 || $this->meta_id == 'None' ) {
			$this->meta_id = null;
		}


		return $this->meta_id == null ? false : true;
	}


	// since 15.0 multiple meta can be set against single product
	// so we have to set one active one meta for compatiblility isues
	// QM-5
	function single_meta_id() {

		$single_meta = ( $this->meta_id == 0 || $this->meta_id == 'None' || empty( $this->meta_id ) ) ? null : $this->meta_id;

		if ( is_array( $single_meta ) && 0 < count( $single_meta ) ) {
			$single_meta = $single_meta[0];
		}

		return $single_meta;
	}

	// QM-5
	function has_multiple_meta() {

		$multiple_meta = false;
		if ( is_array( $this->meta_id ) ) {
			$multiple_meta = true;
		}

		return $multiple_meta;
	}

	/**
	 * Loads the primary settings row for the resolved PPOM group.
	 *
	 * Returns a full `SELECT *` row from the PPOM meta table (same shape as
	 * {@see PPOM_Meta_Repository::get_row_by_id()} and the repository’s
	 * `PPOM_Meta_Group_Row` PHPStan alias),
	 * or null when no group is resolved. Filtered with {@see 'ppom_meta_settings'}.
	 *
	 * Typed as generic `object` for static analysis so callers may mutate properties
	 * (e.g. `productmeta_validation`) like a `stdClass` row.
	 *
	 * @return object|null Row object with PPOM meta columns, or null.
	 *
	 * @phpstan-return object|null
	 */
	public function settings() {

		$meta_id = $this->single_meta_id();

		if ( ! $meta_id || $meta_id == __( 'None', 'woocommerce-product-addon' ) ) {
			return null;
		}

		$repo = \PPOM\Data\FieldGroupRepository::instance();

		if ( is_array( $meta_id ) ) {
			$meta_ids = $meta_id;
		} else {
			$meta_ids = array( $meta_id );
		}

		$meta_settings = $repo->get_rows_by_productmeta_ids( $meta_ids );
		$filter_meta   = array();
		foreach ( $meta_settings as $meta ) {
			if ( ! is_object( $meta ) ) {
				continue;
			}
			$vars = get_object_vars( $meta );
			if ( isset( $vars['productmeta_validation'] ) && 'on' === $vars['productmeta_validation'] ) {
				$filter_meta[] = $meta;
			}
		}
		$meta_settings = ! empty( $filter_meta ) ? reset( $filter_meta ) : reset( $meta_settings );

		$meta_settings = empty( $meta_settings ) ? null : $meta_settings;

		return apply_filters( 'ppom_meta_settings', $meta_settings, $this );
	}

	/**
	 * Loads active field definitions for the resolved PPOM group or groups.
	 *
	 * @return array|null
	 *
	 * @see ppom_get_field_meta_by_dataname()
	 */
	public function get_fields() {

		if ( ! $this->is_exists() ) {
			return null;
		}

		// Meta created without any fields
		if ( ! $this->ppom_settings ) {
			return null;
		}

		$meta_fields = array();
		$repo        = \PPOM\Data\FieldGroupRepository::instance();
		if ( $this->has_multiple_meta() ) {

			foreach ( $this->meta_id as $meta_id ) {
				$fields = $repo->get_the_meta_json_by_productmeta_id( absint( $meta_id ) );

				if ( ! is_string( $fields ) || empty( $fields ) ) {
					continue;
				}

				$fields = json_decode( $fields, true );

				if ( is_array( $fields ) ) {
					$meta_fields = array_merge( $meta_fields, $fields );
				}
			}
		} else {
			$meta_id     = $this->meta_id;
			$fields      = $repo->get_the_meta_json_by_productmeta_id( absint( $meta_id ) );
			$meta_fields = ( is_string( $fields ) && '' !== $fields ) ? json_decode( $fields, true ) : null;
		}

		// Filter fields which are active only
		$meta_fields = array_filter(
			(array) $meta_fields,
			function ( $field ) {
				return ! isset( $field['status'] ) || $field['status'] == 'on';
			}
		);

		// ppom_pa($meta_fields);

		return apply_filters( 'ppom_meta_fields', $meta_fields, $this );
	}

	// Getting fields by meta id
	function get_fields_by_id( $ppom_id ) {

		$meta_fields = array();
		$repo        = \PPOM\Data\FieldGroupRepository::instance();

		$ppom_ids = array_filter( array_map( 'absint', explode( ',', (string) $ppom_id ) ) );
		foreach ( $ppom_ids as $meta_id ) {
			$fields = $repo->get_the_meta_json_by_productmeta_id( absint( $meta_id ) );
			$fields = is_string( $fields ) ? json_decode( $fields, true ) : null;
			if ( is_array( $fields ) ) {
				$meta_fields = array_merge( $meta_fields, $fields );
			}
		}

		// Filter fields which are active only
		$meta_fields = array_filter(
			$meta_fields,
			function ( $field ) {
				return ! isset( $field['status'] ) || $field['status'] == 'on';
			}
		);

		$meta_fields = array_filter(
			$meta_fields,
			function ( $field ) {
				return ! isset( $field['status'] ) || $field['status'] == 'on';
			}
		);

		// if( empty($meta_fields) ) return null;

		return apply_filters( 'ppom_meta_fields_by_id', $meta_fields, $ppom_ids, $this );
	}

	function ppom_has_category_meta( $product_id ) {

		$resolver   = new \PPOM\Data\ProductConfigurationResolver();
		$meta_found = $resolver->match_categories_for_product( $product_id, $this->ppom_categories_and_tags_row );

		$this->category_meta = $meta_found;

		return $meta_found;
	}

	function all_ppom_with_categories() {

		return \PPOM\Data\FieldGroupRepository::instance()->find_rows_with_categories_or_tags();
	}

	// check meta settings: ajax validation
	function ajax_validation_enabled() {

		$validation_enabled = false;

		if ( ! $this->is_exists() ) {
			return null;
		}

		// Meta created without any fields
		if ( ! $this->ppom_settings ) {
			return null;
		}

		return apply_filters( 'ppom_ajax_validation_enabled', $validation_enabled, $this );
	}

	// check meta settings: styels
	function inline_css() {

		$inline_css = '';

		if ( ! $this->is_exists() ) {
			return null;
		}

		// Meta created without any fields
		if ( ! $this->ppom_settings ) {
			return null;
		}

		if (
			isset( $this->ppom_settings->productmeta_style ) &&
			is_string( $this->ppom_settings->productmeta_style ) &&
			$this->ppom_settings->productmeta_style !== ''
		) {
			$selector = '';
			$template = stripslashes( strip_tags( $this->ppom_settings->productmeta_style ) );

			if ( is_array( $this->meta_id ) ) {
				$field_selector = array();
				foreach ( $this->meta_id as $field_id ) {
					$field_selector[] = '.ppom-id-' . $field_id;
				}
				$selector = ':where(' . implode( ', ', $field_selector ) . ')';
			} elseif ( is_numeric( $this->meta_id ) ) {
				$selector = '.ppom-id-' . $this->meta_id;
			}
			$inline_css = str_replace( 'selector', $selector, $template );
		}

		return apply_filters( 'ppom_inline_css', $inline_css, $this );
	}

	// check meta settings: styels
	function inline_js() {

		$inline_js = '';

		if ( ! $this->is_exists() ) {
			return null;
		}

		// Meta created without any fields
		if ( ! $this->ppom_settings ) {
			return null;
		}

		if ( isset( $this->ppom_settings->productmeta_js ) && $this->ppom_settings->productmeta_js != '' ) {
			$inline_js = stripslashes( strip_tags( $this->ppom_settings->productmeta_js ) );
		}

		return apply_filters( 'ppom_inline_js', $inline_js, $this );
	}

	// check meta settings: styels
	function price_display() {

		$price_display = '';

		if ( ! $this->is_exists() ) {
			return null;
		}

		// Meta created without any fields
		if ( ! $this->ppom_settings ) {
			return null;
		}

		$price_display = $this->ppom_settings->dynamic_price_display;

		return apply_filters( 'ppom_price_display', $price_display, $this );
	}

	// check meta settings: styels
	function meta_title() {

		$meta_title = '';

		if ( ! $this->is_exists() ) {
			return null;
		}

		// Meta created without any fields
		if ( ! $this->ppom_settings ) {
			return null;
		}

		$meta_title = stripslashes( $this->ppom_settings->productmeta_name );

		return apply_filters( 'ppom_meta_title', $meta_title, $this );
	}

	// Since 15.1: checking if all meta has unique datanames
	function has_unique_datanames() {

		if ( ! $this->fields ) {
			return false;
		}

		$has_unique      = true;
		$datanames_array = array();

		// ppom_pa($this->fields);

		foreach ( $this->fields as $field ) {

			$type = isset( $field['type'] ) ? $field['type'] : '';

			// pricematrix does not have dataname
			if ( $type == 'pricematrix' ) {
				continue;
			}
			// ignore collapased fields
			if ( $type == 'collapse' ) {
				continue;
			}

			if ( ! isset( $field['data_name'] ) ) {
				$has_unique = false;
				break;
			}

			if ( in_array( $field['data_name'], $datanames_array ) ) {

				$has_unique = false;
				break;
			}

			$datanames_array[] = $field['data_name'];

		}

		// ppom_pa($datanames_array);
		return apply_filters( 'ppom_has_unique_fields', $has_unique, $this );
	}

	/* ============== Get settings by metaid  ================= */
	function get_settings_by_id( $meta_id ) {

		$meta_settings = \PPOM\Data\FieldGroupRepository::instance()->get_row_by_productmeta_id( absint( $meta_id ) );
		$meta_settings = empty( $meta_settings ) ? null : $meta_settings;

		return apply_filters( 'ppom_get_settings_by_id', $meta_settings, $meta_id, $this );
	}

	/**
	 * Loads settings rows for many meta ids in one repository round-trip (cache-friendly).
	 *
	 * @param array<int|string> $meta_ids PPOM group ids.
	 * @return array<int, PPOM_Meta_Group_Row|null> Keyed by numeric id; values run through `ppom_get_settings_by_id`.
	 *
	 * @phpstan-return array<int, PPOM_Meta_Group_Row|null>
	 */
	public function get_settings_by_ids( array $meta_ids ) {
		$ids = array_values(
			array_unique(
				array_filter(
					array_map( 'absint', $meta_ids ),
					static function ( $v ) {
						return $v > 0;
					}
				)
			)
		);

		if ( empty( $ids ) ) {
			return array();
		}

		$rows  = \PPOM\Data\FieldGroupRepository::instance()->get_rows_by_productmeta_ids( $ids );
		$by_id = array();
		foreach ( $rows as $row ) {
			if ( isset( $row->productmeta_id ) ) {
				$by_id[ (int) $row->productmeta_id ] = $row;
			}
		}

		$out = array();
		foreach ( $ids as $id ) {
			$settings   = isset( $by_id[ $id ] ) ? $by_id[ $id ] : null;
			$out[ $id ] = apply_filters( 'ppom_get_settings_by_id', $settings, $id, $this );
		}

		return $out;
	}
}
