<?php
/**
 * Admin list table for fields inside a PPOM field group.
 *
 * @package PPOM
 * @subpackage Admin
 */

namespace PPOM\Admin;

use WP_List_Table;

if ( ! class_exists( '\WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Field group fields list table.
 *
 * Uses the WordPress admin table structure while preserving PPOM builder
 * selectors used by the field modal, bulk remove, and drag ordering scripts.
 *
 * @internal
 */
final class FieldGroupFieldsListTable extends WP_List_Table {

	/**
	 * Field metadata rows.
	 *
	 * @var array<int,array<string,mixed>>
	 */
	private $field_meta;

	/**
	 * Constructor.
	 *
	 * Accepts the raw `json_decode` output for a meta group's field list,
	 * which may be `null`, a scalar, or a malformed array — hence the
	 * `mixed` type and the per-row normalization below.
	 *
	 * @param mixed $field_meta Field metadata (typically an array of arrays).
	 */
	public function __construct( $field_meta ) {
		parent::__construct(
			array(
				'singular' => 'ppom_field',
				'plural'   => 'ppom_fields',
				'ajax'     => false,
			)
		);

		$normalized = array();
		if ( is_array( $field_meta ) ) {
			foreach ( $field_meta as $row ) {
				if ( is_array( $row ) ) {
					$normalized[] = $row;
				}
			}
		}
		$this->field_meta = $normalized;
	}

	/**
	 * Table CSS classes.
	 *
	 * @return array<int,string>
	 */
	protected function get_table_classes() {
		return array( 'widefat', 'fixed', 'striped', 'ppom_fields', 'ppom_field_table' );
	}

	/**
	 * Visible columns.
	 *
	 * @return array<string,string>
	 */
	public function get_columns() {
		return array(
			'order'       => '',
			'cb'          => '<input type="checkbox" />',
			'status'      => __( 'Status', 'woocommerce-product-addon' ),
			'data_name'   => __( 'Data Name', 'woocommerce-product-addon' ),
			'type'        => __( 'Type', 'woocommerce-product-addon' ),
			'title'       => __( 'Title', 'woocommerce-product-addon' ),
			'placeholder' => __( 'Placeholder', 'woocommerce-product-addon' ),
			'required'    => __( 'Required', 'woocommerce-product-addon' ),
			'actions'     => __( 'Actions', 'woocommerce-product-addon' ),
		);
	}

	/**
	 * Prepares rows and headers.
	 *
	 * @return void
	 */
	public function prepare_items() {
		$this->items = array();

		foreach ( $this->field_meta as $index => $field_meta ) {
			$this->items[] = array(
				'index' => $index + 1,
				'meta'  => $field_meta,
			);
		}

		$this->_column_headers = array(
			$this->get_columns(),
			array(),
			array(),
		);
	}

	/**
	 * Extra controls above and below the table.
	 *
	 * @param string $which Position.
	 * @return void
	 */
	protected function display_tablenav( $which ) {
		if ( 'top' === $which ) {
			echo '<div class="tablenav top ppom-fields-tablenav">';
			echo '<div class="alignleft actions">';
			printf(
				'<button type="button" class="button button-primary" data-modal-id="ppom_fields_model_id">%s</button>',
				esc_html__( 'Add field', 'woocommerce-product-addon' )
			);
			printf(
				'<button type="button" class="button ppom_remove_field">%s</button>',
				esc_html__( 'Remove', 'woocommerce-product-addon' )
			);
			echo '</div>';
			echo '<br class="clear" />';
			echo '</div>';
			return;
		}

		echo '<div class="tablenav bottom ppom-fields-tablenav">';
		echo '<div class="alignright actions ppom-submit-btn">';
		echo '<span class="ppom-meta-save-notice"></span>';
		printf(
			'<input type="submit" class="button button-primary" value="%s" />',
			esc_attr__( 'Save Fields', 'woocommerce-product-addon' )
		);
		echo '</div>';
		echo '<br class="clear" />';
		echo '</div>';
	}

	/**
	 * Empty state.
	 *
	 * Renders a designed empty-state card with a CTA that opens the existing
	 * field-picker modal (`#ppom_fields_model_id`) via the global
	 * `[data-modal-id]` handler in `js/admin/ppom-admin.js`.
	 *
	 * @return void
	 */
	public function no_items() {
		?>
		<div class="ppom-empty-state">
			<span class="dashicons dashicons-forms ppom-empty-icon" aria-hidden="true"></span>
			<h3 class="ppom-empty-title"><?php esc_html_e( 'No fields in this group yet', 'woocommerce-product-addon' ); ?></h3>
			<p class="ppom-empty-desc">
				<?php esc_html_e( 'Add fields like text boxes, dropdowns, checkboxes, file uploads, and more to collect input from your customers.', 'woocommerce-product-addon' ); ?>
			</p>
			<button type="button" class="button button-primary button-hero ppom-empty-cta" data-modal-id="ppom_fields_model_id">
				<?php esc_html_e( 'Add your first field', 'woocommerce-product-addon' ); ?>
			</button>
		</div>
		<?php
	}

	/**
	 * Renders one table row with PPOM row identifiers.
	 *
	 * @param array{index:int,meta:array<string,mixed>} $item Field row.
	 * @return void
	 */
	public function single_row( $item ) {
		$index = (int) $item['index'];
		printf(
			'<tr class="row_no_%1$d" id="ppom_sort_id_%1$d">',
			esc_attr( (string) $index )
		);
		$this->single_row_columns( $item );
		echo '</tr>';
	}

	/**
	 * Drag handle column.
	 *
	 * @param array{index:int,meta:array<string,mixed>} $item Field row.
	 * @return string
	 */
	public function column_order( $item ) {
		return '<span class="ppom-sortable-handle dashicons dashicons-move" aria-hidden="true"></span>';
	}

	/**
	 * Checkbox column.
	 *
	 * @param array{index:int,meta:array<string,mixed>} $item Field row.
	 * @return string
	 */
	public function column_cb( $item ) {
		$index = (int) $item['index'];
		return sprintf(
			'<label class="screen-reader-text" for="ppom-field-cb-%1$d">%2$s</label><input type="checkbox" id="ppom-field-cb-%1$d" value="%1$d" />',
			$index,
			esc_html__( 'Select field', 'woocommerce-product-addon' )
		);
	}

	/**
	 * Status column.
	 *
	 * @param array{index:int,meta:array<string,mixed>} $item Field row.
	 * @return string
	 */
	public function column_status( $item ) {
		$index        = (int) $item['index'];
		$field_status = isset( $item['meta']['status'] ) ? (string) $item['meta']['status'] : 'on';
		$input_id     = 'ppom-onoffswitch-' . $index;

		return sprintf(
			'<div class="onoffswitch"><input %1$s type="checkbox" class="onoffswitch-checkbox" id="%2$s" tabindex="0" />'
				. '<label class="onoffswitch-label" for="%2$s"><span class="onoffswitch-inner"></span><span class="onoffswitch-switch"></span></label>'
				. '<input type="hidden" value="%3$s" name="ppom[%4$d][status]" /></div>',
			checked( $field_status, 'on', false ),
			esc_attr( $input_id ),
			esc_attr( $field_status ),
			$index
		);
	}

	/**
	 * Data name column.
	 *
	 * @param array{index:int,meta:array<string,mixed>} $item Field row.
	 * @return string
	 */
	public function column_data_name( $item ) {
		return sprintf(
			'<span class="ppom_meta_field_id">%s</span>',
			esc_html( $this->get_meta_value( $item, 'data_name' ) )
		);
	}

	/**
	 * Type column.
	 *
	 * @param array{index:int,meta:array<string,mixed>} $item Field row.
	 * @return string
	 */
	public function column_type( $item ) {
		return sprintf(
			'<span class="ppom_meta_field_type">%s</span>',
			esc_html( $this->get_meta_value( $item, 'type' ) )
		);
	}

	/**
	 * Title column.
	 *
	 * @param array{index:int,meta:array<string,mixed>} $item Field row.
	 * @return string
	 */
	public function column_title( $item ) {
		return sprintf(
			'<span class="ppom_meta_field_title">%s</span>',
			esc_html( $this->get_meta_value( $item, 'title' ) )
		);
	}

	/**
	 * Placeholder column.
	 *
	 * @param array{index:int,meta:array<string,mixed>} $item Field row.
	 * @return string
	 */
	public function column_placeholder( $item ) {
		return sprintf(
			'<span class="ppom_meta_field_plchlder">%s</span>',
			esc_html( $this->get_meta_value( $item, 'placeholder' ) )
		);
	}

	/**
	 * Required column.
	 *
	 * @param array{index:int,meta:array<string,mixed>} $item Field row.
	 * @return string
	 */
	public function column_required( $item ) {
		$required = 'on' === $this->get_meta_value( $item, 'required' )
			? __( 'Yes', 'woocommerce-product-addon' )
			: __( 'No', 'woocommerce-product-addon' );

		return sprintf(
			'<span class="ppom_meta_field_req">%s</span>',
			esc_html( $required )
		);
	}

	/**
	 * Actions column.
	 *
	 * @param array{index:int,meta:array<string,mixed>} $item Field row.
	 * @return string
	 */
	public function column_actions( $item ) {
		$index      = (int) $item['index'];
		$field_type = $this->get_meta_value( $item, 'type' );
		$pro_class  = ! ppom_pro_is_valid_license() && ! isset( PPOM()->inputs[ $field_type ] ) ? ' ppom-is-pro-field' : '';

		return sprintf(
			'<button type="button" class="button ppom_copy_field%1$s" data-field-type="%2$s" title="%3$s" id="%4$d"><span class="dashicons dashicons-admin-page" aria-hidden="true"></span><span class="screen-reader-text">%3$s</span></button> '
				. '<button type="button" class="button ppom-edit-field%1$s" data-modal-id="ppom_field_model_%4$d" id="%4$d" title="%5$s"><span class="dashicons dashicons-edit" aria-hidden="true"></span><span class="screen-reader-text">%5$s</span></button>',
			esc_attr( $pro_class ),
			esc_attr( $field_type ),
			esc_attr__( 'Copy Field', 'woocommerce-product-addon' ),
			$index,
			esc_attr__( 'Edit Field', 'woocommerce-product-addon' )
		);
	}

	/**
	 * Default column output.
	 *
	 * @param array{index:int,meta:array<string,mixed>} $item Field row.
	 * @param string                                    $column_name Column name.
	 * @return string
	 */
	public function column_default( $item, $column_name ) {
		return '';
	}

	/**
	 * Gets a field meta value.
	 *
	 * @param array{index:int,meta:array<string,mixed>} $item Field row.
	 * @param string                                    $key Meta key.
	 * @return string
	 */
	private function get_meta_value( $item, $key ) {
		return isset( $item['meta'][ $key ] ) ? (string) $item['meta'][ $key ] : '';
	}
}
