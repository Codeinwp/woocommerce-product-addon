<?php
/**
 * PPOM meta table repository.
 *
 * @package PPOM
 */

/**
 *
 * @phpstan-type PPOM_Meta_Group_Row object{
 *     productmeta_id: numeric-string,
 *     productmeta_name: string,
 *     productmeta_validation: string|null,
 *     productmeta_disabled: string|null,
 *     dynamic_price_display: string|null,
 *     send_file_attachment: string,
 *     show_cart_thumb: string|null,
 *     aviary_api_key: string|null,
 *     productmeta_style: string|null,
 *     productmeta_js: string|null,
 *     productmeta_categories: string|null,
 *     productmeta_tags: string|null,
 *     the_meta: string,
 *     productmeta_created: string
 * }
 * @phpstan-type PPOM_Meta_Group_Summary_Row object{
 *     productmeta_id: numeric-string,
 *     productmeta_categories: string|null,
 *     productmeta_tags: string|null
 * }
 * @phpstan-type PPOM_Meta_Group_Categories_Tags_Columns array{
 *     productmeta_categories?: string,
 *     productmeta_tags?: string
 * }
 * @phpstan-type PPOM_Meta_Group_ColumnKey 'productmeta_id'|'productmeta_name'|'productmeta_validation'|'productmeta_disabled'|'dynamic_price_display'|'send_file_attachment'|'show_cart_thumb'|'aviary_api_key'|'productmeta_style'|'productmeta_js'|'productmeta_categories'|'productmeta_tags'|'the_meta'|'productmeta_created'
 * @phpstan-type PPOM_Meta_Group_ColumnData array<PPOM_Meta_Group_ColumnKey, mixed>
 * @phpstan-type PPOM_Meta_Demo_ColumnKey 'productmeta_name'|'productmeta_validation'|'productmeta_disabled'|'dynamic_price_display'|'send_file_attachment'|'show_cart_thumb'|'aviary_api_key'|'productmeta_style'|'productmeta_js'|'productmeta_categories'|'productmeta_tags'|'the_meta'|'productmeta_created'
 * @phpstan-type PPOM_Meta_Demo_Export_Input array{
 *     productmeta_name?: string,
 *     productmeta_validation?: string,
 *     productmeta_disabled?: string,
 *     dynamic_price_display?: string,
 *     send_file_attachment?: string,
 *     show_cart_thumb?: string,
 *     aviary_api_key?: string,
 *     productmeta_style?: string,
 *     productmeta_js?: string,
 *     productmeta_categories?: string,
 *     productmeta_tags?: string,
 *     the_meta?: string,
 *     productmeta_created?: string
 * }
 *
 * @package PPOM
 * @subpackage Database
 *
 * @see PPOM_TABLE_META
 * @see NM_PersonalizedProduct::upgrade_database()
 */
class PPOM_Meta_Repository {

	/**
	 * Default object cache group for PPOM meta rows.
	 */
	private const CACHE_GROUP_DEFAULT = 'ppom_meta';

	/**
	 * Object-cache key: full `SELECT *` list (e.g. export / existing-meta screen).
	 */
	private const CACHE_KEY_ALL_ROWS_FULL = 'ppom_meta_all_rows_full';

	/**
	 * Object-cache key: slim id+name list (admin metabox, bulk actions, admin bar).
	 */
	private const CACHE_KEY_ALL_ROWS_LIST_UI = 'ppom_meta_all_rows_list_ui';

	/**
	 * Object-cache key: category/tag assignment summary scan.
	 */
	private const CACHE_KEY_CATEGORY_TAG_SUMMARY = 'ppom_category_tag_summary_rows';

	/**
	 * Shared instance.
	 *
	 * @var PPOM_Meta_Repository|null
	 */
	private static $instance = null;

	/**
	 * WordPress database object.
	 *
	 * @var wpdb
	 */
	private $wpdb;

	/**
	 * Constructor.
	 *
	 * @param wpdb|null $wpdb Optional database handle (for tests).
	 */
	public function __construct( ?wpdb $wpdb = null ) {
		$this->wpdb = $wpdb ? $wpdb : $GLOBALS['wpdb'];
	}

	/**
	 * Singleton accessor.
	 *
	 * @return self
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Filterable cache group name.
	 *
	 * @return string
	 */
	public function cache_group() {
		return (string) apply_filters( 'ppom_meta_cache_group', self::CACHE_GROUP_DEFAULT );
	}

	/**
	 * Prefixed table name.
	 *
	 * @return string
	 */
	public function table_name() {
		return $this->wpdb->prefix . PPOM_TABLE_META;
	}

	/**
	 * Creates or upgrades the PPOM meta table via dbDelta.
	 *
	 * @return void
	 */
	public static function ensure_schema() {
		global $wpdb;

		$table           = $wpdb->prefix . PPOM_TABLE_META;
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table} (
		productmeta_id INT(5) NOT NULL AUTO_INCREMENT,
		productmeta_name VARCHAR(50) NOT NULL,
		productmeta_validation VARCHAR(3),
		productmeta_disabled VARCHAR(3) NOT NULL DEFAULT '',
        dynamic_price_display VARCHAR(10),
        send_file_attachment VARCHAR(3) NOT NULL,
        show_cart_thumb VARCHAR(3),
		aviary_api_key VARCHAR(40),
		productmeta_style MEDIUMTEXT,
		productmeta_js MEDIUMTEXT,
		productmeta_categories MEDIUMTEXT,
		productmeta_tags LONGTEXT,
		the_meta MEDIUMTEXT NOT NULL,
		productmeta_created DATETIME NOT NULL,
		PRIMARY KEY  (productmeta_id)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		update_option( 'personalizedproduct_db_version', PPOM_DB_VERSION );
	}

	/**
	 * Object-cache key for one PPOM row.
	 *
	 * @param int $id Row id.
	 * @return string
	 */
	private function cache_key_row( $id ) {
		return 'ppom_group_' . (int) $id;
	}

	/**
	 * Drops one row from the object cache.
	 *
	 * @param int $id Row id.
	 * @return void
	 */
	private function invalidate_row_cache( $id ) {
		wp_cache_delete( $this->cache_key_row( (int) $id ), $this->cache_group() );
	}

	/**
	 * Clears cached aggregate queries (full list, list UI, category/tag summary).
	 *
	 * @return void
	 */
	private function invalidate_aggregate_list_caches() {
		$group = $this->cache_group();
		wp_cache_delete( self::CACHE_KEY_ALL_ROWS_FULL, $group );
		wp_cache_delete( self::CACHE_KEY_ALL_ROWS_LIST_UI, $group );
		wp_cache_delete( self::CACHE_KEY_CATEGORY_TAG_SUMMARY, $group );
	}

	/**
	 * Stores one row in the object cache.
	 *
	 * @param int    $id  Row id.
	 * @param object $row Full table row (`wpdb` OBJECT).
	 * @return void
	 */
	private function cache_set_row( $id, $row ) {
		wp_cache_set( $this->cache_key_row( (int) $id ), $row, $this->cache_group(), 0 );
	}

	/**
	 * All rows (admin export, existing-meta table — needs `the_meta` etc.).
	 *
	 * @return list<PPOM_Meta_Group_Row> Each OBJECT row with all table columns.
	 */
	public function get_all_rows() {
		$group  = $this->cache_group();
		$cached = wp_cache_get( self::CACHE_KEY_ALL_ROWS_FULL, $group );
		if ( false !== $cached && is_array( $cached ) ) {
			/**
			 * Full table rows from object cache.
			 *
			 * @var list<PPOM_Meta_Group_Row> $cached
			 */
			return $cached;
		}

		$table = $this->table_name();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is internal.
		$rows = $this->wpdb->get_results( "SELECT * FROM `{$table}` ORDER BY productmeta_id ASC" );

		/**
		 * Full table rows from wpdb.
		 *
		 * @var list<PPOM_Meta_Group_Row> $rows
		 */
		wp_cache_set( self::CACHE_KEY_ALL_ROWS_FULL, $rows, $group, 0 );

		return $rows;
	}

	/**
	 * Slim rows for admin UI lists (id + name only — smaller query + payload).
	 *
	 * @return list<stdClass> OBJECT rows with productmeta_id, productmeta_name.
	 * @phpstan-return list<stdClass>
	 */
	public function get_all_rows_list_ui() {
		$group  = $this->cache_group();
		$cached = wp_cache_get( self::CACHE_KEY_ALL_ROWS_LIST_UI, $group );
		if ( false !== $cached && is_array( $cached ) ) {
			/**
			 * Cached slim rows (0..n-1 keys).
			 *
			 * @var list<stdClass> $cached
			 */
			return $cached;
		}

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared -- %i identifier; literal projection.
		$raw = $this->wpdb->get_results(
			$this->wpdb->prepare(
				'SELECT productmeta_id, productmeta_name FROM %i ORDER BY productmeta_id ASC',
				$this->table_name()
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared

		$rows = is_array( $raw ) ? $raw : array();

		/**
		 * Slim rows from wpdb.
		 *
		 * @var list<stdClass> $rows
		 */
		wp_cache_set( self::CACHE_KEY_ALL_ROWS_LIST_UI, $rows, $group, 0 );

		return $rows;
	}

	/**
	 * Paged + searchable + sortable rows for the admin list table.
	 *
	 * Not cached: cache-key cardinality (per page × per search × per sort) isn't
	 * worth it at this volume, and writes already invalidate the aggregate
	 * caches that other call sites rely on.
	 *
	 * @param array $args {
	 *     Query args. Unknown `orderby` falls back to `productmeta_id`.
	 *
	 *     @type int    $per_page Page size.
	 *     @type int    $paged    1-based page number.
	 *     @type string $search   Match against `productmeta_name`; empty disables.
	 *     @type string $orderby  Either `productmeta_id` or `productmeta_name`.
	 *     @type string $order    `ASC` or `DESC`.
	 * }
	 * @phpstan-param array{
	 *     per_page?: int,
	 *     paged?: int,
	 *     search?: string,
	 *     orderby?: string,
	 *     order?: string,
	 * } $args
	 * @return list<PPOM_Meta_Group_Row>
	 */
	public function get_paged_rows( array $args = array() ) {
		$defaults = array(
			'per_page' => 50,
			'paged'    => 1,
			'search'   => '',
			'orderby'  => 'productmeta_id',
			'order'    => 'ASC',
		);
		$args     = array_merge( $defaults, $args );

		$allowed_orderby = array( 'productmeta_id', 'productmeta_name' );
		$orderby         = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'productmeta_id';
		$order           = strtoupper( (string) $args['order'] ) === 'DESC' ? 'DESC' : 'ASC';
		$per_page        = max( 1, (int) $args['per_page'] );
		$paged           = max( 1, (int) $args['paged'] );
		$offset          = ( $paged - 1 ) * $per_page;
		$search          = trim( (string) $args['search'] );

		$table = $this->table_name();

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared -- list table queries; results not separately cached; %i identifier placeholders are validated by wpdb.
		if ( '' === $search ) {
			// %i is whitelisted for identifiers (WP 6.2+); ORDER direction is
			// branched on so each template stays a literal string for prepare().
			$sql = 'ASC' === $order
				? 'SELECT * FROM %i ORDER BY %i ASC LIMIT %d OFFSET %d'
				: 'SELECT * FROM %i ORDER BY %i DESC LIMIT %d OFFSET %d';

			$rows = $this->wpdb->get_results(
				$this->wpdb->prepare( $sql, $table, $orderby, $per_page, $offset )
			);
		} else {
			$sql = 'ASC' === $order
				? 'SELECT * FROM %i WHERE productmeta_name LIKE %s ORDER BY %i ASC LIMIT %d OFFSET %d'
				: 'SELECT * FROM %i WHERE productmeta_name LIKE %s ORDER BY %i DESC LIMIT %d OFFSET %d';

			$rows = $this->wpdb->get_results(
				$this->wpdb->prepare(
					$sql,
					$table,
					'%' . $this->wpdb->esc_like( $search ) . '%',
					$orderby,
					$per_page,
					$offset
				)
			);
		}
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared

		if ( ! is_array( $rows ) ) {
			return array();
		}

		/**
		 * Result rows.
		 *
		 * @var list<PPOM_Meta_Group_Row> $rows
		 */
		return $rows;
	}

	/**
	 * Count rows matching the optional search term (admin list table pagination).
	 *
	 * @param string $search Optional search term matched against `productmeta_name`.
	 * @return int
	 */
	public function count_filtered_rows( $search = '' ) {
		$table  = $this->table_name();
		$search = trim( (string) $search );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared -- list table count; cheap; %i identifier placeholder validated by wpdb.
		if ( '' === $search ) {
			$count = (int) $this->wpdb->get_var(
				$this->wpdb->prepare( 'SELECT COUNT(*) FROM %i', $table )
			);
		} else {
			$count = (int) $this->wpdb->get_var(
				$this->wpdb->prepare(
					'SELECT COUNT(*) FROM %i WHERE productmeta_name LIKE %s',
					$table,
					'%' . $this->wpdb->esc_like( $search ) . '%'
				)
			);
		}
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared

		return $count;
	}

	/**
	 * Count of rows. With `$limit`, returns how many rows exist up to that cap (survey / bounded metrics).
	 *
	 * @param int|null $limit When set, count is `min( actual_row_count, $limit )`; negative values are clamped to 0.
	 * @return int
	 */
	public function count_rows( $limit = null ) {
		$table = $this->table_name();
		$lim   = null === $limit ? null : max( 0, (int) $limit );

		if ( null === $lim ) {
			$sql = "SELECT COUNT(*) FROM `{$table}`";
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared -- table name is internal trusted constant.
			return (int) $this->wpdb->get_var( $sql );
		}

		$sql = "SELECT COUNT(*) FROM ( SELECT 1 FROM `{$table}` ORDER BY productmeta_id ASC LIMIT {$lim} ) AS ppom_count_capped";

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared -- table name internal; LIMIT is cast int.
		return (int) $this->wpdb->get_var( $sql );
	}

	/**
	 * Single row by id with object cache.
	 *
	 * @param int $id Product meta row id.
	 * @return PPOM_Meta_Group_Row|null OBJECT row; null if missing.
	 */
	public function get_row_by_id( $id ) {
		$id = (int) $id;
		if ( $id <= 0 ) {
			return null;
		}

		$cached = wp_cache_get( $this->cache_key_row( $id ), $this->cache_group() );
		if ( false !== $cached ) {
			/**
			 * Object-cache hit for a full row.
			 *
			 * @var PPOM_Meta_Group_Row $cached
			 */
			return $cached;
		}

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared -- %i is the supported identifier placeholder (WP 6.2+).
		$row = $this->wpdb->get_row(
			$this->wpdb->prepare(
				'SELECT * FROM %i WHERE productmeta_id = %d',
				$this->table_name(),
				$id
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared

		if ( ! $row ) {
			return null;
		}

		/**
		 * Full row loaded from the database.
		 *
		 * @var PPOM_Meta_Group_Row $row
		 */
		$this->cache_set_row( $id, $row );

		return $row;
	}

	/**
	 * Multiple rows by id: object-cache first, then one SQL `IN` for misses.
	 * Normalizes input (unique positive ints), returns rows in that order (skips missing).
	 *
	 * @param array<int|string> $ids Row ids.
	 * @return list<PPOM_Meta_Group_Row>
	 */
	public function get_rows_by_ids( array $ids ) {
		$ids = array_values(
			array_unique(
				array_filter(
					array_map( 'absint', $ids ),
					static function ( $v ) {
						return $v > 0;
					}
				)
			)
		);

		if ( empty( $ids ) ) {
			return array();
		}

		$by_id   = array();
		$missing = array();

		foreach ( $ids as $id ) {
			$cached = wp_cache_get( $this->cache_key_row( $id ), $this->cache_group() );
			if ( false !== $cached ) {
				$by_id[ $id ] = $cached;
			} else {
				$missing[] = $id;
			}
		}

		if ( ! empty( $missing ) ) {
			$table   = $this->table_name();
			$ids_sql = implode( ',', $missing );
			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table from prefix + constant; IN list is absint'd ids only.
			$rows = $this->wpdb->get_results( "SELECT * FROM `{$table}` WHERE productmeta_id IN ({$ids_sql})" );
			// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

			foreach ( (array) $rows as $row ) {
				if ( isset( $row->productmeta_id ) ) {
					$rid = (int) $row->productmeta_id;
					$this->cache_set_row( $rid, $row );
					$by_id[ $rid ] = $row;
				}
			}
		}

		$ordered = array();
		foreach ( $ids as $id ) {
			if ( isset( $by_id[ $id ] ) ) {
				$ordered[] = $by_id[ $id ];
			}
		}

		return $ordered;
	}

	/**
	 * Rows that have category or tag assignment data.
	 *
	 * @return list<PPOM_Meta_Group_Summary_Row> Each row: productmeta_id, productmeta_categories, productmeta_tags.
	 */
	public function get_category_tag_summary_rows() {
		$group  = $this->cache_group();
		$cached = wp_cache_get( self::CACHE_KEY_CATEGORY_TAG_SUMMARY, $group );
		if ( false !== $cached && is_array( $cached ) ) {
			/**
			 * Summary rows from object cache.
			 *
			 * @var list<PPOM_Meta_Group_Summary_Row> $cached
			 */
			return $cached;
		}

		$table = $this->table_name();

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- static predicate; table from trusted prefix.
		$results = $this->wpdb->get_results(
			"SELECT productmeta_id, productmeta_categories, productmeta_tags FROM `{$table}` WHERE productmeta_categories != '' OR productmeta_tags != ''"
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		/**
		 * Summary projection (id, categories, tags).
		 *
		 * @var list<PPOM_Meta_Group_Summary_Row> $results
		 */
		wp_cache_set( self::CACHE_KEY_CATEGORY_TAG_SUMMARY, $results, $group, 0 );

		return $results;
	}

	/**
	 * Categories and tags columns for attach UI.
	 *
	 * @param int $ppom_field_id Row id.
	 * @return PPOM_Meta_Group_Categories_Tags_Columns Empty when no row.
	 */
	public function get_categories_and_tags_columns( $ppom_field_id ) {
		$id = (int) $ppom_field_id;
		if ( $id <= 0 ) {
			return array();
		}

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared -- %i identifier placeholder (WP 6.2+).
		$rows = $this->wpdb->get_results(
			$this->wpdb->prepare(
				'SELECT productmeta_categories, productmeta_tags FROM %i WHERE productmeta_id = %d',
				$this->table_name(),
				$id
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared

		if ( is_array( $rows ) && isset( $rows[0] ) ) {
			/**
			 * First (and only) associative row.
			 *
			 * @var PPOM_Meta_Group_Categories_Tags_Columns $out
			 */
			$out = $rows[0];

			return $out;
		}

		return array();
	}

	/**
	 * Legacy detection: rows with inline style or JS.
	 *
	 * @return list<PPOM_Meta_Group_Row> Full OBJECT rows from SELECT *.
	 */
	public function get_rows_with_non_empty_style_or_js() {
		$table = $this->table_name();

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- static predicate; table from trusted prefix.
		$results = $this->wpdb->get_results(
			"SELECT * FROM `{$table}` WHERE `productmeta_js` != '' OR `productmeta_style` != ''"
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		/**
		 * Full rows that have non-empty style or JS.
		 *
		 * @var list<PPOM_Meta_Group_Row> $results
		 */
		return $results;
	}

	/**
	 * Insert a new field-group row.
	 *
	 * @param array<string, mixed> $data Column => value.
	 * @param array<int, string>   $format wpdb formats.
	 * @phpstan-param PPOM_Meta_Group_ColumnData $data
	 * @return int Insert id or 0 on failure.
	 */
	public function insert_group( array $data, array $format ) {
		$this->wpdb->insert( $this->table_name(), $data, $format );

		$new_id = (int) $this->wpdb->insert_id;
		if ( $new_id > 0 ) {
			$this->invalidate_aggregate_list_caches();
		}

		return $new_id;
	}

	/**
	 * Update an existing row.
	 *
	 * @param int                  $id Row id.
	 * @param array<string, mixed> $data Column => value.
	 * @param array<int, string>   $format wpdb formats.
	 * @param array<string, mixed> $where Where clause (e.g. productmeta_id => id).
	 * @param array<int, string>   $where_format Formats for where.
	 * @phpstan-param PPOM_Meta_Group_ColumnData $data
	 * @phpstan-param PPOM_Meta_Group_ColumnData $where
	 * @return int|false Rows matched or false.
	 */
	public function update_group( $id, array $data, array $format, array $where, array $where_format ) {
		$result = $this->wpdb->update( $this->table_name(), $data, $where, $format, $where_format );
		$this->invalidate_row_cache( (int) $id );
		$this->invalidate_aggregate_list_caches();

		return $result;
	}

	/**
	 * Update only the_meta JSON blob.
	 *
	 * @param int    $id   Row id.
	 * @param string $json Encoded field schema.
	 * @return int|false
	 */
	public function update_the_meta_only( $id, $json ) {
		$id = (int) $id;
		if ( $id <= 0 ) {
			return false;
		}

		$result = $this->wpdb->update(
			$this->table_name(),
			array( 'the_meta' => $json ),
			array( 'productmeta_id' => $id ),
			array( '%s' ),
			array( '%d' )
		);
		$this->invalidate_row_cache( $id );
		$this->invalidate_aggregate_list_caches();

		return $result;
	}

	/**
	 * Set the disabled flag for one row.
	 *
	 * `'on'` disables the group (skipped during frontend resolution); empty
	 * string re-enables it. Product attachments and `the_meta` JSON are left
	 * untouched so toggling is fully reversible.
	 *
	 * @param int  $id       Row id.
	 * @param bool $disabled Whether the group should be disabled.
	 * @return int|false Rows updated, or false on failure.
	 */
	public function set_disabled( $id, $disabled ) {
		$id = (int) $id;
		if ( $id <= 0 ) {
			return false;
		}

		$result = $this->wpdb->update(
			$this->table_name(),
			array( 'productmeta_disabled' => $disabled ? 'on' : '' ),
			array( 'productmeta_id' => $id ),
			array( '%s' ),
			array( '%d' )
		);
		$this->invalidate_row_cache( $id );
		$this->invalidate_aggregate_list_caches();

		return $result;
	}

	/**
	 * Bulk-set the disabled flag for many rows in a single prepared statement.
	 *
	 * @param array<int|string> $ids Row ids.
	 * @param bool              $disabled Whether the rows should be disabled.
	 * @return int|false Rows affected or false.
	 */
	public function set_disabled_for_ids( array $ids, $disabled ) {
		$normalized_ids = array();
		foreach ( $ids as $id ) {
			if ( is_int( $id ) && $id > 0 ) {
				$normalized_ids[] = $id;
				continue;
			}

			if ( is_string( $id ) && ctype_digit( $id ) ) {
				$id = absint( $id );
				if ( $id > 0 ) {
					$normalized_ids[] = $id;
				}
			}
		}
		$ids = array_values( array_unique( $normalized_ids ) );

		if ( empty( $ids ) ) {
			return false;
		}

		$table   = $this->table_name();
		$ids_sql = implode( ',', $ids );
		// 'on' / '' is server-controlled (see $disabled cast above), and the
		// IN list is absint'd. No request data reaches the SQL.
		$value = $disabled ? 'on' : '';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared -- table name internal; value is trusted enum.
		$result = $this->wpdb->query( "UPDATE `{$table}` SET productmeta_disabled = '{$value}' WHERE productmeta_id IN ({$ids_sql})" );

		foreach ( $ids as $id ) {
			$this->invalidate_row_cache( (int) $id );
		}
		$this->invalidate_aggregate_list_caches();

		if ( false === $result ) {
			return false;
		}

		return (int) $result;
	}

	/**
	 * Delete one row.
	 *
	 * @param int $id Row id.
	 * @return int|false Rows deleted or false.
	 */
	public function delete_by_id( $id ) {
		$id = (int) $id;
		if ( $id <= 0 ) {
			return false;
		}

		$result = $this->wpdb->delete(
			$this->table_name(),
			array( 'productmeta_id' => $id ),
			array( '%d' )
		);
		$this->invalidate_row_cache( $id );
		$this->invalidate_aggregate_list_caches();

		return $result;
	}

	/**
	 * Delete many rows in one prepared statement.
	 *
	 * @param array<int> $ids Row ids.
	 * @return int|false
	 */
	public function delete_by_ids( array $ids ) {
		$ids = array_values(
			array_filter(
				array_map( 'absint', $ids ),
				static function ( $v ) {
					return $v > 0;
				}
			)
		);

		if ( empty( $ids ) ) {
			return false;
		}

		/**
		 * Normalized unique row ids (> 0).
		 *
		 * @phpstan-var non-empty-list<positive-int> $ids
		 */
		$table   = $this->table_name();
		$ids_sql = implode( ',', $ids );
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table from prefix + constant; IN list is absint'd ids only.
		$result = $this->wpdb->query( "DELETE FROM `{$table}` WHERE productmeta_id IN ({$ids_sql})" );
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		foreach ( $ids as $id ) {
			$this->invalidate_row_cache( (int) $id );
		}
		$this->invalidate_aggregate_list_caches();

		if ( false === $result ) {
			return false;
		}

		return (int) $result;
	}

	/**
	 * Update category/tag attachment columns; omits productmeta_tags when $tags is false.
	 *
	 * @param int                      $ppom_id    Row id.
	 * @param array<int, string>       $categories Category slugs.
	 * @param array<int, string>|false $tags       Tag slugs, empty array to clear, false to skip column.
	 * @return int|false
	 * @phpstan-param list<string> $categories
	 * @phpstan-param list<string>|false $tags
	 */
	public function save_categories_and_tags( $ppom_id, array $categories, $tags ) {
		$ppom_id = (int) $ppom_id;
		if ( $ppom_id <= 0 ) {
			return false;
		}

		$data = array(
			'productmeta_categories' => implode( "\r\n", $categories ),
		);

		if ( is_array( $tags ) ) {
			// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize -- legacy DB format for productmeta_tags.
			$data['productmeta_tags'] = empty( $tags ) ? '' : serialize( $tags );
		}

		$format = array( '%s' );
		if ( is_array( $tags ) ) {
			$format[] = '%s';
		}

		$result = $this->wpdb->update(
			$this->table_name(),
			$data,
			array( 'productmeta_id' => $ppom_id ),
			$format,
			array( '%d' )
		);
		$this->invalidate_row_cache( $ppom_id );
		$this->invalidate_aggregate_list_caches();

		return $result;
	}

	/**
	 * Clone a field group row (admin).
	 *
	 * @param int $source_id Source row id.
	 * @return int New insert id or 0.
	 */
	public function clone_group_from( $source_id ) {
		$source_id = (int) $source_id;
		if ( $source_id <= 0 ) {
			return 0;
		}

		$table = $this->table_name();
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared -- %i identifier placeholders (WP 6.2+).
		$prepared = $this->wpdb->prepare(
			'INSERT INTO %i
		(productmeta_name, aviary_api_key, productmeta_style,productmeta_categories, the_meta, productmeta_created)
		SELECT CONCAT(productmeta_name, \' (clone)\'), aviary_api_key, productmeta_style,productmeta_categories, the_meta, productmeta_created
		FROM %i
		WHERE productmeta_id = %d',
			$table,
			$table,
			$source_id
		);
		if ( is_string( $prepared ) ) {
			$this->wpdb->query( $prepared );
		}
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared

		$new_id = (int) $this->wpdb->insert_id;
		if ( $new_id > 0 ) {
			$this->invalidate_row_cache( $new_id );
			$this->invalidate_aggregate_list_caches();
		}

		return $new_id;
	}

	/**
	 * Insert one demo row from whitelisted export columns (activation).
	 *
	 * @param array<string, string> $columns Column => value from JSON export.
	 * @phpstan-param PPOM_Meta_Demo_Export_Input&array<string, string> $columns
	 * @return bool Whether insert was attempted and succeeded.
	 */
	public function insert_demo_row( array $columns ) {
		$allowed = array(
			'productmeta_name',
			'productmeta_validation',
			'productmeta_disabled',
			'dynamic_price_display',
			'send_file_attachment',
			'show_cart_thumb',
			'aviary_api_key',
			'productmeta_style',
			'productmeta_js',
			'productmeta_categories',
			'productmeta_tags',
			'the_meta',
			'productmeta_created',
		);

		$row = array();
		foreach ( $allowed as $key ) {
			if ( array_key_exists( $key, $columns ) ) {
				$row[ $key ] = $columns[ $key ];
			}
		}

		$row['productmeta_name'] = 'PPOM Demo Field';

		if ( ! isset( $row['the_meta'] ) || '' === $row['the_meta'] ) {
			return false;
		}
		if ( ! isset( $row['productmeta_created'] ) ) {
			$row['productmeta_created'] = current_time( 'mysql' );
		}
		if ( ! isset( $row['send_file_attachment'] ) || '' === $row['send_file_attachment'] ) {
			$row['send_file_attachment'] = '';
		}

		$format = array_fill( 0, count( $row ), '%s' );

		/**
		 * Whitelisted string columns ready for wpdb insert.
		 *
		 * @phpstan-var array<PPOM_Meta_Demo_ColumnKey, string> $row
		 */
		$this->wpdb->insert( $this->table_name(), $row, $format );
		$new_id = (int) $this->wpdb->insert_id;
		if ( $new_id > 0 ) {
			$this->invalidate_row_cache( $new_id );
			$this->invalidate_aggregate_list_caches();
		}

		return $new_id > 0;
	}
}
