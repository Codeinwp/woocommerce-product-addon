<?php
/**
 * Admin list table for the PPOM Field Groups screen.
 *
 * Replaces the legacy DataTables-driven markup in
 * `templates/admin/existing-meta.php` with a `WP_List_Table` so the screen
 * follows WordPress admin conventions (search, sortable headers, pagination,
 * native bulk-action form, screen options).
 *
 * @package PPOM
 * @subpackage Admin
 */

namespace PPOM\Admin;

use PPOM\Meta\MetaRepositoryAccessor;
use PPOM\Support\Helpers;
use WP_List_Table;

if ( ! class_exists( '\WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Field-groups admin list table.
 *
 * @phpstan-import-type PPOM_Meta_Group_Row from \PPOM_Meta_Repository
 *
 * @internal
 */
final class MetaGroupsListTable extends WP_List_Table {

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => 'ppom_meta_group',
				'plural'   => 'ppom_meta_groups',
				'ajax'     => false,
			)
		);
	}

	/**
	 * Visible columns.
	 *
	 * @return array<string,string>
	 */
	public function get_columns() {
		return array(
			'cb'     => '<input type="checkbox" />',
			'id'     => __( 'Meta ID', 'woocommerce-product-addon' ),
			'name'   => __( 'Name', 'woocommerce-product-addon' ),
			'meta'   => __( 'Meta', 'woocommerce-product-addon' ),
			'status' => __( 'Status', 'woocommerce-product-addon' ),
			'attach' => __( 'Select Products', 'woocommerce-product-addon' ),
		);
	}

	/**
	 * Sortable columns. Whitelisted in the repository.
	 *
	 * @return array<string,array{0:string,1:bool}>
	 */
	public function get_sortable_columns() {
		return array(
			'id'   => array( 'productmeta_id', false ),
			'name' => array( 'productmeta_name', false ),
		);
	}

	/**
	 * Bulk-select checkbox. Uses `name="ppom_meta[]"` so the existing
	 * `admin_post_ppom_export_meta` handler (and any Pro override) keeps
	 * receiving the same payload shape it does today.
	 *
	 * @param PPOM_Meta_Group_Row $item Field-group row.
	 * @return string
	 */
	public function column_cb( $item ) {
		$id = (int) $item->productmeta_id;
		return sprintf(
			'<label class="screen-reader-text" for="cb-select-%1$d">%2$s</label>'
				. '<input type="checkbox" id="cb-select-%1$d" class="ppom_product_checkbox" name="ppom_meta[]" value="%1$d" />',
			$id,
			esc_html__( 'Select field group', 'woocommerce-product-addon' )
		);
	}

	/**
	 * Meta ID column.
	 *
	 * @param PPOM_Meta_Group_Row $item Field-group row.
	 * @return string
	 */
	public function column_id( $item ) {
		return (string) (int) $item->productmeta_id;
	}

	/**
	 * Name column with row actions (Edit / Clone / Delete).
	 *
	 * @param PPOM_Meta_Group_Row $item Field-group row.
	 * @return string
	 */
	public function column_name( $item ) {
		$id        = (int) $item->productmeta_id;
		$edit_url  = add_query_arg(
			array(
				'productmeta_id' => $id,
				'do_meta'        => 'edit',
			),
			admin_url( 'admin.php?page=ppom' )
		);
		$clone_url = add_query_arg(
			array(
				'productmeta_id' => $id,
				'do_meta'        => 'clone',
			),
			admin_url( 'admin.php?page=ppom' )
		);
		$clone_url = wp_nonce_url( $clone_url, 'ppom_clone_nonce_action', 'ppom_clone_nonce' );

		$name_html = sprintf(
			'<a href="%1$s"><strong>%2$s</strong></a>',
			esc_url( $edit_url ),
			esc_html( stripcslashes( (string) $item->productmeta_name ) )
		);

		$actions = array(
			'edit'   => sprintf(
				'<a href="%s">%s</a>',
				esc_url( $edit_url ),
				esc_html__( 'Edit', 'woocommerce-product-addon' )
			),
			'clone'  => sprintf(
				'<a href="%s">%s</a>',
				esc_url( $clone_url ),
				esc_html__( 'Clone', 'woocommerce-product-addon' )
			),
			'delete' => sprintf(
				'<a href="#" id="del-file-%1$d" class="ppom-delete-single-product submitdelete" data-product-id="%1$d">%2$s</a>',
				$id,
				esc_html__( 'Delete', 'woocommerce-product-addon' )
			),
		);

		return $name_html . $this->row_actions( $actions );
	}

	/**
	 * Meta summary column.
	 *
	 * @param PPOM_Meta_Group_Row $item Field-group row.
	 * @return string
	 */
	public function column_meta( $item ) {
		// `ppom_admin_simplify_meta()` already returns a sanitized HTML summary.
		return ppom_admin_simplify_meta( $item->the_meta );
	}

	/**
	 * Status column. Renders an accessible toggle switch that flips the
	 * group's disabled flag via AJAX. Disabled groups are skipped during
	 * frontend resolution but keep their schema and product attachments,
	 * so re-enabling restores the form instantly.
	 *
	 * @param PPOM_Meta_Group_Row $item Field-group row.
	 * @return string
	 */
	public function column_status( $item ) {
		$id          = (int) $item->productmeta_id;
		$is_disabled = isset( $item->productmeta_disabled ) && 'on' === $item->productmeta_disabled;
		$state_label = $is_disabled
			? __( 'Disabled', 'woocommerce-product-addon' )
			: __( 'Enabled', 'woocommerce-product-addon' );
		/* translators: %s: field-group name. */
		$aria_label = sprintf(
			// translators: %s: field-group name.
			__( 'Toggle %s', 'woocommerce-product-addon' ),
			stripcslashes( (string) $item->productmeta_name )
		);

		return sprintf(
			'<label class="ppom-toggle" data-ppom-id="%1$d">'
				. '<input type="checkbox" class="ppom-toggle-input" %2$s aria-label="%3$s" />'
				. '<span class="ppom-toggle-track" aria-hidden="true"><span class="ppom-toggle-thumb"></span></span>'
				. '<span class="ppom-toggle-label">%4$s</span>'
				. '</label>',
			$id,
			$is_disabled ? '' : 'checked',
			esc_attr( $aria_label ),
			esc_html( $state_label )
		);
	}

	/**
	 * Attach-to-Products column. Renders the modal trigger button — JS in
	 * `js/admin/ppom-meta-table.js` opens the modal on click.
	 *
	 * @param PPOM_Meta_Group_Row $item Field-group row.
	 * @return string
	 */
	public function column_attach( $item ) {
		return sprintf(
			'<a href="#" class="button button-small ppom-products-modal" data-ppom_id="%1$d" data-formmodal-id="ppom-product-modal" data-reload="true">%2$s</a>',
			(int) $item->productmeta_id,
			esc_html__( 'Attach to Products', 'woocommerce-product-addon' )
		);
	}

	/**
	 * Bulk action options.
	 *
	 * @return array<string,string>
	 */
	public function get_bulk_actions() {
		return array(
			'enable'  => __( 'Enable', 'woocommerce-product-addon' ),
			'disable' => __( 'Disable', 'woocommerce-product-addon' ),
			'delete'  => __( 'Delete', 'woocommerce-product-addon' ),
			'export'  => ppom_pro_is_installed()
				? __( 'Export', 'woocommerce-product-addon' )
				: __( 'Export (Pro)', 'woocommerce-product-addon' ),
		);
	}

	/**
	 * Process bulk actions before any output. Called from `prepare_items()`.
	 *
	 * @return void
	 */
	public function process_bulk_action() {
		$action  = $this->current_action();
		$handled = array( 'delete', 'export', 'enable', 'disable' );
		if ( ! in_array( $action, $handled, true ) ) {
			return;
		}

		// WP_List_Table emits a bulk action nonce keyed bulk-PLURAL automatically.
		check_admin_referer( 'bulk-' . $this->_args['plural'] );

		if ( ! Helpers::security_role() ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to perform this action.', 'woocommerce-product-addon' ) );
		}

		$raw_ids = isset( $_REQUEST['ppom_meta'] ) && is_array( $_REQUEST['ppom_meta'] )
			? wp_unslash( $_REQUEST['ppom_meta'] ) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- normalized below.
			: array();
		$ids     = array();
		foreach ( $raw_ids as $raw_id ) {
			if ( is_int( $raw_id ) && $raw_id > 0 ) {
				$ids[] = $raw_id;
				continue;
			}

			if ( is_string( $raw_id ) && ctype_digit( $raw_id ) ) {
				$id = absint( $raw_id );
				if ( $id > 0 ) {
					$ids[] = $id;
				}
			}
		}
		$ids = array_values( array_unique( $ids ) );

		if ( empty( $ids ) ) {
			return;
		}

		if ( 'delete' === $action ) {
			MetaRepositoryAccessor::instance()->delete_by_ids( $ids );
			$redirect = remove_query_arg( array( '_wpnonce', 'action', 'action2', 'ppom_meta', '_wp_http_referer' ) );
			$redirect = add_query_arg( array( 'ppom_deleted' => count( $ids ) ), $redirect );
			wp_safe_redirect( $redirect );
			exit;
		}

		if ( 'enable' === $action || 'disable' === $action ) {
			$disabled = 'disable' === $action;
			MetaRepositoryAccessor::instance()->set_disabled_for_ids( $ids, $disabled );
			$redirect = remove_query_arg( array( '_wpnonce', 'action', 'action2', 'ppom_meta', '_wp_http_referer' ) );
			$redirect = add_query_arg(
				array( $disabled ? 'ppom_disabled' : 'ppom_enabled' => count( $ids ) ),
				$redirect
			);
			wp_safe_redirect( $redirect );
			exit;
		}

		// Export — re-fire the existing admin_post handler so the free upgrade
		// prompt and any Pro export override keep working with no changes.
		$_POST['ppom_meta'] = $ids;
		do_action( 'admin_post_ppom_export_meta' );
		exit;
	}

	/**
	 * Fetches items, runs bulk actions, sets pagination + column headers.
	 *
	 * @return void
	 */
	public function prepare_items() {
		$this->process_bulk_action();

		$per_page     = (int) $this->get_items_per_page( 'ppom_groups_per_page', 50 );
		$current_page = $this->get_pagenum();
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only routing/query params (search, sort, paging); no state change.
		$search      = isset( $_REQUEST['s'] ) ? sanitize_text_field( wp_unslash( (string) $_REQUEST['s'] ) ) : '';
		$orderby_raw = isset( $_REQUEST['orderby'] ) ? sanitize_key( (string) wp_unslash( $_REQUEST['orderby'] ) ) : 'productmeta_id';
		$order_raw   = isset( $_REQUEST['order'] ) ? sanitize_key( (string) wp_unslash( $_REQUEST['order'] ) ) : 'asc';
		$order       = 'desc' === strtolower( $order_raw ) ? 'DESC' : 'ASC';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		$repo = MetaRepositoryAccessor::instance();

		$this->items = $repo->get_paged_rows(
			array(
				'per_page' => $per_page,
				'paged'    => $current_page,
				'search'   => $search,
				'orderby'  => $orderby_raw,
				'order'    => $order,
			)
		);

		$total = $repo->count_filtered_rows( $search );

		$this->set_pagination_args(
			array(
				'total_items' => $total,
				'per_page'    => $per_page,
				'total_pages' => (int) ceil( $total / max( 1, $per_page ) ),
			)
		);

		$this->_column_headers = array(
			$this->get_columns(),
			array(),
			$this->get_sortable_columns(),
		);
	}

	/**
	 * Toolbar above the table — Import button (Pro upsell on free) + Add Group.
	 *
	 * @param string $which Either 'top' or 'bottom'.
	 * @return void
	 */
	public function extra_tablenav( $which ) {
		if ( 'top' !== $which ) {
			return;
		}

		$is_pro      = ppom_pro_is_installed();
		$add_url     = add_query_arg( array( 'action' => 'new' ), admin_url( 'admin.php?page=ppom' ) );
		$import_url  = $is_pro
			? '#'
			: tsdk_utmify( tsdk_translate_link( PPOM_UPGRADE_URL ), 'lockedimport' );
		$import_icon = $is_pro ? 'download' : 'lock';

		echo '<div class="alignleft actions ppom-listtable-actions">';

		printf(
			'<a class="button ppom-import-export-btn" href="%1$s"><span class="dashicons dashicons-%2$s" aria-hidden="true"></span><span>%3$s</span></a>',
			esc_url( $import_url ),
			esc_attr( $import_icon ),
			esc_html__( 'Import', 'woocommerce-product-addon' )
		);

		printf(
			'<a class="button button-primary" href="%1$s"><span class="dashicons dashicons-plus" aria-hidden="true"></span><span>%2$s</span></a>',
			esc_url( $add_url ),
			esc_html__( 'Add Group', 'woocommerce-product-addon' )
		);

		echo '</div>';
	}

	/**
	 * Empty-state message.
	 *
	 * @return void
	 */
	public function no_items() {
		$add_url = add_query_arg( array( 'action' => 'new' ), admin_url( 'admin.php?page=ppom' ) );
		printf(
			/* translators: %s: link to add a new field group. */
			esc_html__( 'No PPOM field groups yet. %s', 'woocommerce-product-addon' ),
			sprintf(
				'<a href="%1$s">%2$s</a>',
				esc_url( $add_url ),
				esc_html__( 'Add the first one', 'woocommerce-product-addon' )
			)
		);
	}
}
