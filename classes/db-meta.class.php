<?php
/**
 * PPOM Meta DB Class
 * Repository for accessing PPOM meta data with individual row caching.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class PPOM_Meta_DB {

	/**
	 * Cache group for PPOM Meta
	 */
	const CACHE_GROUP = 'ppom_meta';

	/**
	 * Get table name.
	 *
	 * @return string
	 */
	public static function get_table_name() {
		global $wpdb;
		return $wpdb->prefix . PPOM_TABLE_META;
	}

	/**
	 * Get a single PPOM meta block by ID.
	 *
	 * @param int|string $id
	 * @return object{
	 *   productmeta_id: int,
	 *   productmeta_name: string,
	 *   productmeta_validation: string|null,
	 *   dynamic_price_display: string|null,
	 *   send_file_attachment: string,
	 *   show_cart_thumb: string|null,
	 *   aviary_api_key: string|null,
	 *   productmeta_style: string|null,
	 *   productmeta_js: string|null,
	 *   productmeta_categories: string|null,
	 *   productmeta_tags: string|null,
	 *   the_meta: string,
	 *   productmeta_created: string
	 * }|null
	 */
	public static function get( $id ) {
		$id = absint( trim( $id ) );
		if ( ! $id ) {
			return null;
		}

		$cache_key = "ppom_meta_{$id}";
		$meta      = wp_cache_get( $cache_key, self::CACHE_GROUP );

		if ( false === $meta ) {
			global $wpdb;
			$table = self::get_table_name();
			$meta  = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE productmeta_id = %d", $id ) );
			
			// Cache even if null to avoid repetitive queries for non-existent IDs
			wp_cache_set( $cache_key, $meta, self::CACHE_GROUP, DAY_IN_SECONDS );
		}

		return $meta;
	}

	/**
	 * Get multiple PPOM meta blocks by an array of IDs.
	 *
	 * @param array<int|string> $ids
	 * @return array<object{
	 *   productmeta_id: int,
	 *   productmeta_name: string,
	 *   productmeta_validation: string|null,
	 *   dynamic_price_display: string|null,
	 *   send_file_attachment: string,
	 *   show_cart_thumb: string|null,
	 *   aviary_api_key: string|null,
	 *   productmeta_style: string|null,
	 *   productmeta_js: string|null,
	 *   productmeta_categories: string|null,
	 *   productmeta_tags: string|null,
	 *   the_meta: string,
	 *   productmeta_created: string
	 * }>
	 */
	public static function get_multiple( $ids ) {
		$ids = array_map( 'absint', $ids );
		$ids = array_filter( $ids );
		$ids = array_unique( $ids );

		if ( empty( $ids ) ) {
			return array();
		}

		$results = array();
		$keys    = array();
		foreach ( $ids as $id ) {
			$keys[] = "ppom_meta_{$id}";
		}

		// Use multiple cache get if available, otherwise loop
		if ( function_exists( 'wp_cache_get_multiple' ) ) {
			$cached = wp_cache_get_multiple( $keys, self::CACHE_GROUP );
		} else {
			$cached = array();
			foreach ( $keys as $key ) {
				$cached[ $key ] = wp_cache_get( $key, self::CACHE_GROUP );
			}
		}

		$uncached_ids = array();

		foreach ( $ids as $id ) {
			$key = "ppom_meta_{$id}";
			if ( isset( $cached[ $key ] ) && false !== $cached[ $key ] ) {
				$results[ $id ] = $cached[ $key ];
			} else {
				$uncached_ids[] = $id;
			}
		}

		// Fetch missing IDs
		if ( ! empty( $uncached_ids ) ) {
			global $wpdb;
			$table       = self::get_table_name();
			$id_list     = implode( ',', $uncached_ids ); // Already absint
			$db_results  = $wpdb->get_results( "SELECT * FROM {$table} WHERE productmeta_id IN ({$id_list})" );

			$fetched_map = array();
			foreach ( $db_results as $row ) {
				$fetched_map[ $row->productmeta_id ] = $row;
			}

			// Cache fetched results and map to results array
			foreach ( $uncached_ids as $uncached_id ) {
				$meta_value = isset( $fetched_map[ $uncached_id ] ) ? $fetched_map[ $uncached_id ] : null;
				wp_cache_set( "ppom_meta_{$uncached_id}", $meta_value, self::CACHE_GROUP, DAY_IN_SECONDS );
				if ( $meta_value ) {
					$results[ $uncached_id ] = $meta_value;
				}
			}
		}

		return array_values( $results );
	}

	/**
	 * Get all categories and tags lookup.
	 *
	 * @return list<object{
	 *   productmeta_id: int,
	 *   productmeta_categories: string|null,
	 *   productmeta_tags: string|null
	 * }>
	 */
	public static function get_categories_lookup() {
		$cache_key = 'ppom_meta_lookup_categories';
		$results   = wp_cache_get( $cache_key, self::CACHE_GROUP );

		if ( false === $results ) {
			global $wpdb;
			$table = self::get_table_name();
			$qry   = "SELECT productmeta_id, productmeta_categories, productmeta_tags FROM {$table} WHERE productmeta_categories != '' OR productmeta_tags != ''";
			$results = $wpdb->get_results( $qry );
			wp_cache_set( $cache_key, $results, self::CACHE_GROUP, DAY_IN_SECONDS );
		}

		return $results;
	}

	/**
	 * Invalidate cache for a specific PPOM Meta ID.
	 * Call this when a row is modified or deleted.
	 *
	 * @param int|string $id
	 * @return void
	 */
	public static function invalidate_cache( $id ) {
		$id = absint( trim( $id ) );
		if ( $id ) {
			wp_cache_delete( "ppom_meta_{$id}", self::CACHE_GROUP );
		}
		
		// Invalidate category lookup cache in case category assignment changed.
		wp_cache_delete( 'ppom_meta_lookup_categories', self::CACHE_GROUP );
	}

	/**
	 * Insert a new PPOM meta block.
	 *
	 * @param array<string,mixed> $data
	 * @param array<int,string>|string|null $format
	 * @return int|false
	 */
	public static function insert( $data, $format = null ) {
		global $wpdb;
		$table = self::get_table_name();
		
		$result = $wpdb->insert( $table, $data, $format );
		if ( false !== $result ) {
			$insert_id = $wpdb->insert_id;
			self::invalidate_cache( $insert_id );
			return $insert_id;
		}
		return false;
	}

	/**
	 * Update an existing PPOM meta block.
	 *
	 * @param int|string $id
	 * @param array<string,mixed> $data
	 * @param array<int,string>|string|null $format
	 * @param array<int,string>|string|null $where_format
	 * @return int|false
	 */
	public static function update( $id, $data, $format = null, $where_format = null ) {
		global $wpdb;
		$table = self::get_table_name();
		
		if ( null === $where_format ) {
			$where_format = array( '%d' );
		}
		
		$result = $wpdb->update(
			$table,
			$data,
			array( 'productmeta_id' => $id ),
			$format,
			$where_format
		);
		
		if ( false !== $result ) {
			self::invalidate_cache( $id );
		}
		
		return $result;
	}

	/**
	 * Delete a PPOM meta block.
	 *
	 * @param int|string|array<int|string> $id
	 * @param array<int,string>|string|null $where_format
	 * @return int|false
	 */
	public static function delete( $id, $where_format = null ) {
		global $wpdb;
		$table = self::get_table_name();
		
		if ( is_array( $id ) ) {
			$ids = array_map( 'absint', $id );
			$ids = array_filter( $ids );
			if ( empty( $ids ) ) {
				return 0;
			}

			$result = $wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE productmeta_id IN ({$where_format})", $ids ) );
			
			if ( false !== $result ) {
				foreach ( $ids as $deleted_id ) {
					self::invalidate_cache( $deleted_id );
				}
			}
			return $result;
		}
		
		if ( null === $where_format ) {
			$where_format = array( '%d' );
		}
		
		$result = $wpdb->delete(
			$table,
			array( 'productmeta_id' => $id ),
			$where_format
		);
		
		if ( false !== $result ) {
			self::invalidate_cache( $id );
		}
		
		return $result;
	}
}
