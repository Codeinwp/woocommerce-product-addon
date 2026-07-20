<?php
/**
 * Data access for the PPOM field-group table (`nm_personalized`).
 *
 * @package PPOM
 */

namespace PPOM\Data;

// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery -- Repository centralizes intentional PPOM table queries.
// phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is prefix + constant; placeholders used for values.
// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared -- Dynamic IN() lists are built with validated integer placeholders.
// phpcs:disable WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare

/**
 * Centralizes SQL access to the PPOM field-group table.
 *
 * @since 33.0.19
 */
final class FieldGroupRepository {

	/**
	 * @var self|null
	 */
	private static $instance;

	/**
	 * @return self
	 */
	public static function instance(): self {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * @return string Prefixed table name.
	 */
	public function table_name(): string {
		global $wpdb;

		return $wpdb->prefix . PPOM_TABLE_META;
	}

	/**
	 * Rows that have category or tag columns populated.
	 *
	 * @return array<int, object>
	 */
	public function find_rows_with_categories_or_tags(): array {
		global $wpdb;
		$ppom_table = $this->table_name();
		$qry        = "SELECT productmeta_id, productmeta_categories, productmeta_tags FROM {$ppom_table} WHERE productmeta_categories != '' OR productmeta_tags != ''";
		$results    = $wpdb->get_results( $qry );

		return is_array( $results ) ? $results : array();
	}

	/**
	 * Full rows for one or more field-group IDs.
	 *
	 * @param array<int> $productmeta_ids Numeric IDs.
	 * @return array<int, object>
	 */
	public function get_rows_by_productmeta_ids( array $productmeta_ids ): array {
		$productmeta_ids = array_values(
			array_filter(
				array_map( 'absint', $productmeta_ids )
			)
		);

		if ( array() === $productmeta_ids ) {
			return array();
		}

		global $wpdb;
		$table        = $this->table_name();
		$placeholders = implode( ', ', array_fill( 0, count( $productmeta_ids ), '%d' ) );
		$sql          = "SELECT * FROM {$table} WHERE productmeta_id IN ({$placeholders})";
		$prepared     = $wpdb->prepare( $sql, $productmeta_ids );
		$results      = $wpdb->get_results( $prepared );

		return is_array( $results ) ? $results : array();
	}

	/**
	 * @param int $productmeta_id Field-group ID.
	 * @return string|null JSON `the_meta` column.
	 */
	public function get_the_meta_json_by_productmeta_id( int $productmeta_id ): ?string {
		global $wpdb;
		$table  = $this->table_name();
		$fields = $wpdb->get_var( $wpdb->prepare( "SELECT the_meta FROM {$table} WHERE productmeta_id = %d", $productmeta_id ) );

		return is_string( $fields ) ? $fields : null;
	}

	/**
	 * @param int $productmeta_id Field-group ID.
	 * @return object|null Full row.
	 */
	public function get_row_by_productmeta_id( int $productmeta_id ): ?object {
		global $wpdb;
		$table         = $this->table_name();
		$meta_settings = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE productmeta_id = %d", $productmeta_id ) );

		return empty( $meta_settings ) ? null : $meta_settings;
	}

	/**
	 * @param array<string, mixed> $data         Column => value.
	 * @param array<int, string>   $format       wpdb formats.
	 * @return int Inserted row ID (0 on failure).
	 */
	public function insert_row( array $data, array $format ): int {
		global $wpdb;
		$wpdb->insert( $this->table_name(), $data, $format );

		return (int) $wpdb->insert_id;
	}

	/**
	 * @param array<string, mixed> $data         Column => value.
	 * @param array<string, mixed> $where        WHERE column => value.
	 * @param array<int, string>   $format       Data formats.
	 * @param array<int, string>   $where_format WHERE formats.
	 * @return int|false Rows affected.
	 */
	public function update_row( array $data, array $where, array $format, array $where_format ) {
		global $wpdb;

		return $wpdb->update( $this->table_name(), $data, $where, $format, $where_format );
	}

	/**
	 * Category and tag columns for attach UI.
	 *
	 * @param int $ppom_field_id Field-group ID.
	 * @return array<string, string>
	 */
	public function get_categories_tags_columns_by_id( int $ppom_field_id ): array {
		global $wpdb;
		$table = $this->table_name();
		$rows  = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT productmeta_categories, productmeta_tags FROM {$table} WHERE productmeta_id = %d",
				$ppom_field_id
			),
			ARRAY_A
		);

		return 0 < count( $rows ) && ! empty( $rows[0] ) ? $rows[0] : array();
	}

	/**
	 * Rows that have legacy inline JS or CSS (used to seed ppom_legacy_user option).
	 *
	 * @return array<int, object>
	 */
	public function find_rows_with_inline_js_or_style(): array {
		global $wpdb;
		$table = $this->table_name();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is prefixed and constant-derived; no user input.
		$res = $wpdb->get_results( "SELECT * FROM `{$table}` WHERE `productmeta_js` != '' OR `productmeta_style` != ''" );

		return is_array( $res ) ? $res : array();
	}

	/**
	 * @param int|string $productmeta_id ID (may be string from request).
	 * @return int|false Rows deleted.
	 */
	public function delete_by_productmeta_id( $productmeta_id ) {
		global $wpdb;
		$tbl_name = $this->table_name();

		return $wpdb->query( $wpdb->prepare( "DELETE FROM {$tbl_name} WHERE productmeta_id = %d", $productmeta_id ) );
	}

	/**
	 * @param array<int> $productmeta_ids Numeric IDs.
	 * @return int|false Rows deleted.
	 */
	public function delete_by_productmeta_ids( array $productmeta_ids ) {
		$del_ids = array_values(
			array_filter(
				array_map( 'absint', $productmeta_ids )
			)
		);

		if ( array() === $del_ids ) {
			return false;
		}

		global $wpdb;
		$del_ids_ph = implode( ', ', array_fill( 0, count( $del_ids ), '%d' ) );
		$tbl_name   = $this->table_name();

		return $wpdb->query( $wpdb->prepare( "DELETE FROM {$tbl_name} WHERE productmeta_id IN ({$del_ids_ph})", $del_ids ) );
	}
}
