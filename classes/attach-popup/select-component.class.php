<?php

namespace PPOM\Attach;

class SelectComponent extends ContainerView {
	/**
	 * The title of the component.
	 *
	 * @var string
	 */
	protected $title = '';

	/**
	 * The description of the component.
	 *
	 * @var string
	 */
	protected $description = '';

	/**
	 * The select component.
	 *
	 * @var array{ name: string, multiple: bool, isUsed: bool, options: array{value: string, label: string, selected: bool}} The select component.
	 */
	protected $select = array();

	/**
	 * Component status.
	 *
	 * @var string
	 */
	protected $status = 'valid';

	public function __construct(  ) {}

	/**
	 * Render the select component.
	 *
	 * @inheritDoc
	 */
	public function render() {
		$select         = $this->get_select();
		$select_name    = isset( $select['name'] ) && is_string( $select['name'] ) ? $select['name'] : '';
		$select_options = ! empty( $select['options'] ) && is_array( $select['options'] ) ? $select['options'] : array();
		$is_multiple    = isset( $select['multiple'] ) && $select['multiple'];
        $input_label    = isset( $select['label'] ) && is_string( $select['label'] ) ? $select['label'] : '';
        $is_used        = isset( $select['is_used'] ) && $select['is_used'];

		$initial_values = array();
		foreach ( $select_options as $option ) {
			if ( ! $option['selected'] ) {
				continue;
			}
			$initial_values[]= $option['value'];
		}

		ob_start();
		?>
        <div
			class="ppom-attach-container-item"
			id="<?php echo esc_attr( $this->get_id() ) ?>"
        >
            <div class="postbox <?php echo $is_used ? '' : 'closed'  ?>">

                <div class="postbox-header ">
                    <h6 > <span class="dashicons dashicon-ppom-status"></span><?php echo esc_html( $this->get_title() ) ?></h6>
                </div>
                <div class="inside">
                    <div class="ppom-attach-container-input">
						<?php if ( $input_label ) { ?>
                            <label for="<?php echo esc_attr( $this->get_id() ) ?>"><?php echo esc_html( $input_label ) ?></label>

						<?php } ?>
                        <select
                                class="ppom-attach"
                                name="<?php echo esc_attr( $select_name ) ?>"
							<?php echo $is_multiple ? 'multiple="multiple"' : '' ?>
							<?php echo 'valid' !== $this->get_status() ? 'disabled' : '' ?>
                        >
							<?php
							if ( ! empty( $select_options ) ) {
								foreach ( $select_options as $option ) {
									$value = ! empty( $option['value'] ) ? $option['value'] : '';
									$label = ! empty( $option['label'] ) ? $option['label'] : '';

									echo '<option value="' . esc_attr( $value ) . '" ' . selected( true, $option['selected'], false) . ' ' . disabled( true, isset( $option['disabled'] ) && $option['disabled'], false ) . '>' . esc_html( $label ) . '</option>';
								}
							} else {
								echo '<option value="" disabled>' . esc_html__( 'No options available!', 'woocommerce-product-addon' ) . '</option>';
							}
							?>
                        </select>
                    </div>
                    <span class="ppom-attach-description">
                        <?php echo esc_html( $this->get_description() ) ?>
                    </span>
					<input
						name="<?php echo esc_attr( str_replace( '[]', '', $select_name ) ) . '-initial' ?>"
						type="hidden"
						value="<?php echo esc_attr( implode( ',', $initial_values ) ) ?>"
					/>
                </div>
            </div>
        </div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Gets the title of the select component.
	 *
	 * @return string The title of the select component.
	 */
	public function get_title(): string {
		return $this->title;
	}

	/**
	 * Sets the title of the select component.
	 *
	 * @param string $title The new title of the select component.
	 * @return SelectComponent Returns the instance of the select component.
	 */
	public function set_title( string $title ): SelectComponent {
		$this->title = $title;

		return $this;
	}

	/**
	 * Gets the description of the select component.
	 *
	 * @return string The description of the select component.
	 */
	public function get_description(): string {
		return $this->description;
	}

	/**
	 * Sets the description of the select component.
	 *
	 * @param string $description The new description of the select component.
	 * @return SelectComponent Returns the instance of the select component.
	 */
	public function set_description( string $description ): SelectComponent {
		$this->description = $description;

		return $this;
	}

	/**
	 * Gets the select options of the select component.
	 *
	 * @return array The select options of the select component.
	 */
	public function get_select(): array {
		return $this->select;
	}

	/**
	 * Sets the select options of the select component.
	 *
	 * @param array $select The new select options of the select component.
	 * @return SelectComponent Returns the instance of the select component.
	 */
	public function set_select( array $select ): SelectComponent {
		$this->select = $select;

		return $this;
	}

	/**
	 * Gets the status of the select component.
	 *
	 * @return string The status of the select component.
	 */
	public function get_status(): string {
		return $this->status;
	}

	/**
	 * Sets the status of the select component.
	 *
	 * @param string $status The new status of the select component.
	 * @return SelectComponent Returns the instance of the select component.
	 */
	public function set_status( string $status ): SelectComponent {
		$this->status = $status;

		return $this;
	}
}