<?php
/**
 * Product page: field rendering, scripts, and add-to-cart validation.
 *
 * @package PPOM
 * @subpackage WooCommerce
 *
 * @see woocommerce.php Parent loader under inc/.
 */

namespace PPOM\WooCommerce\Product;

use PPOM_Form;
use PPOM_Meta;
use PPOM\Hooks\Callbacks;
use PPOM\Support\Helpers;

/**
 * @internal
 */
final class ProductHandler {

	/**
	 * Whether the field participates in add-to-cart validation (required / min-max option checks).
	 *
	 * @param array $field PPOM field definition.
	 *
	 * @return bool
	 */
	public static function field_requires_add_to_cart_schema_checks( array $field ) {
		if ( empty( $field['data_name'] ) ) {
			return false;
		}

		if (
			( ! isset( $field['required'] ) || 'on' !== $field['required'] ) &&
			empty( $field['min_checked'] ) &&
			empty( $field['max_checked'] )
		) {
			return false;
		}

		return true;
	}

	// Rendering.
	/**
	 * Renders field if legacy input rendering mode is on
	 *
	 * @return void
	 */
	public static function show_fields() {

		global $product;

		$product_id = Helpers::get_product_id( $product );

		self::show_fields_on_product( $product_id );
	}


	/**
	 * Renders legacy PPOM fields for a product.
	 *
	 * @param int        $product_id Product ID.
	 * @param array|null $args       Rendering arguments.
	 *
	 * @return string|void
	 *
	 * @see PPOM_Meta::__construct()
	 * @see Helpers::load_template()
	 * @see self::template_base_inputs_rendering()
	 */
	public static function show_fields_on_product( $product_id, $args = null ) {

		$product = wc_get_product( $product_id );

		$product_id = Helpers::get_product_id( $product );
		$ppom       = new PPOM_Meta( $product_id );

		if ( ! $ppom->fields ) {
			return '';
		}

		if ( ! $ppom->has_unique_datanames() ) {

			echo '<div class="error">' . esc_html__( 'Some of your fields has duplicated datanames, please fix it', 'woocommerce-product-addon' ) . '</div>';

			return;
		}


		$ppom_box_id = is_array( $ppom->meta_id ) ? implode( '-', $ppom->meta_id ) : $ppom->meta_id;
		$ppom_html   = '<div id="ppom-box-' . esc_attr( $ppom_box_id ) . '" class="ppom-wrapper">';

		if ( Helpers::get_price_table_location() === 'before' ) {
			$ppom_html .= '<div id="ppom-price-container"></div>';
		}

		$template_vars = array(
			'ppom_settings'    => $ppom->ppom_settings,
			'product'          => $product,
			'ppom_fields_meta' => $ppom->fields,
			'ppom_id'          => $ppom->meta_id,
			'args'             => $args,
		);
		ob_start();
		Helpers::load_template( 'render-fields.php', $template_vars );
		$ppom_html .= ob_get_clean();

		if ( Helpers::get_price_table_location() === 'after' ) {
			$ppom_html .= '<div id="ppom-price-container"></div>';
		}

		// Clear fix
		$ppom_html .= '<div style="clear:both"></div>';   // Clear fix
		$ppom_html .= '</div>';   // Ends ppom-wrappper

		echo apply_filters( 'ppom_fields_html', $ppom_html, $product );
	}

	// Template Base Callback function
	public static function inputs_template_base() {

		global $product;

		$product_id = Helpers::get_product_id( $product );

		$args = apply_filters( 'ppom_rendering_template_args', array( 'enable_add_to_cart_id' => false ), $product );

		self::template_base_inputs_rendering( $product_id, $args );
	}

	/**
	 * Renders template-based PPOM fields for a product.
	 *
	 * @param int        $product_id Product ID.
	 * @param array|null $args       Rendering arguments.
	 *
	 * @return string|void
	 *
	 * @see PPOM_Form::ppom_fields_render()
	 * @see Helpers::load_input_templates()
	 * @see self::show_fields_on_product()
	 */
	public static function template_base_inputs_rendering( $product_id, $args = null ) {

		$product = wc_get_product( $product_id );

		// @TODO: have to re-check abou args param for the Form class
		$form_obj = new PPOM_Form( $product, $args );

		// Check if PPOM fields is empty
		if ( ! $form_obj->has_ppom_fields() ) {
			return '';
		}

		$ppom_html     = '';
		$template_vars = array( 'form_obj' => $form_obj );

		ob_start();
		Helpers::load_input_templates( 'frontend/ppom-fields.php', $template_vars );
		$ppom_html .= ob_get_clean();

		echo apply_filters( 'ppom_fields_html', $ppom_html, $product );
	}

	public static function load_scripts() {

		if ( ! is_product() ) {
			return '';
		}

		global $post;
		$product = wc_get_product( $post->ID );
		if ( ! $product ) {
			return '';
		}

		$ppom = new PPOM_Meta( $product->get_id() );


		if ( ! $ppom->fields ) {
			return '';
		}

		// Loading all required scripts/css for inputs like datepicker, fileupload etc
		Callbacks::load_input_scripts( $product );

		do_action( 'ppom_after_scripts_loaded', $ppom, $product );
	}


	// Validation.

	/**
	 * Validates PPOM fields during add to cart.
	 *
	 * Treats posted PPOM values as untrusted input and validates them against the
	 * resolved field schema for the product.
	 *
	 * @param bool                 $passed       Validation state from earlier callbacks.
	 * @param int                  $product_id   Product ID.
	 * @param int|float            $qty          Requested quantity.
	 * @param int                  $variation_id Selected variation ID, or 0 for simple products.
	 * @param array<string, mixed> $variations   Selected variation attributes (unused; kept for filter compatibility).
	 *
	 * @return bool
	 *
	 * @see self::check_validation()
	 * @see PPOM_Meta::__construct()
	 */
	public static function validate_product( $passed, $product_id, $qty, $variation_id = 0, $variations = array() ) {

		$ppom = new PPOM_Meta( $product_id );
		if ( ! $ppom->ajax_validation_enabled ) {
			$passed = self::check_validation( $product_id, $_POST, true, $variation_id );
		}

		if ( Helpers::get_price_mode() == 'legacy' && isset( $_POST['ppom']['fields'] ) ) {

			if ( Helpers::is_price_attached_with_fields( $_POST['ppom']['fields'] ) &&
			empty( $_POST['ppom']['ppom_option_price'] )
			) {
				$error_message = __( 'Sorry, an error has occurred. Please enable JavaScript or contact site owner.', 'woocommerce-product-addon' );
				Helpers::wc_add_notice( $error_message );
				$passed = false;

				return $passed;
			}
		}

		return $passed;
	}

	/**
	 * Validates PPOM fields through the AJAX validation endpoint.
	 *
	 * @return void
	 *
	 * @see self::check_validation()
	 * @see self::validate_product()
	 */
	public static function ajax_validate() {

		// ppom_pa($_POST); exit;
		$ppom_nonce            = $_REQUEST['ppom_nonce'];
		$validate_nonce_action = 'ppom_validating_action';
		if ( ! wp_verify_nonce( $ppom_nonce, $validate_nonce_action ) ) {

			$message  = sprintf( '<div class="woocommerce-error" role="alert">%s</div>', __( 'Error while validating, try again', 'woocommerce-product-addon' ) );
			$response = array(
				'status'  => 'error',
				'message' => $message,
			);
			wp_send_json( $response );
		}

		$errors_found = array();

		$product_id   = intval( $_POST['ppom_product_id'] );
		$variation_id = isset( $_POST['variation_id'] ) ? absint( $_POST['variation_id'] ) : 0;
		$passed       = self::check_validation( $product_id, $_POST, true, $variation_id );

		$all_notices = wc_get_notices();
		wc_clear_notices();

		$response = array();
		if ( ! $passed ) {
			ob_start();
			foreach ( $all_notices as $type => $message ) {

				if ( $type != 'error' ) {
					continue;
				}
				wc_get_template(
					"notices/{$type}.php",
					array(
						'messages' => $message,
					)
				);
			}

			$all_notices = wc_kses_notice( ob_get_clean() );
			$response    = array(
				'status'  => 'error',
				'message' => $all_notices,
			);
		} else {
			$response = array( 'status' => 'success' );
		}
		// $all_notices = '<div class="">'.$all_notices.'</div>';
		// ppom_pa($all_notices);

		wp_send_json( $response );
	}

	/**
	 * Validates posted PPOM fields against the product field schema.
	 *
	 * Reads posted values from `ppom[fields]`, skips hidden fields, and adds
	 * WooCommerce notices for failed requirements.
	 *
	 * @param int   $product_id   Product ID.
	 * @param array $post_data    Posted request payload.
	 * @param bool  $passed       Validation state from earlier checks.
	 * @param int   $variation_id Selected variation ID, or 0 for simple products.
	 *
	 * @return bool
	 *
	 * @see PPOM_Meta::get_fields()
	 * @see Helpers::has_posted_field_value()
	 * @see ppom_woocommerce_add_cart_item_data()
	 */
	public static function check_validation( $product_id, $post_data, $passed = true, $variation_id = 0 ) {

		$ppom         = new PPOM_Meta( $product_id );
		$variation_id = absint( $variation_id );

		if ( ! $ppom->fields ) {
			return $passed;
		}

		$ppom_posted_fields = isset( $post_data['ppom']['fields'] ) ? $post_data['ppom']['fields'] : null;
		if ( ! $ppom_posted_fields ) {
			return $passed;
		}

		foreach ( $ppom->fields as $field ) {

			// ppom_pa($field);

			// Check field Visibility settings
			if ( ! Helpers::is_field_visible( $field ) ) {
				continue;
			}

			$ppom_id = isset( $field['ppom_id'] ) ? absint( $field['ppom_id'] ) : 0;
			if ( $ppom_id > 0 && ! Helpers::is_meta_group_active_for_variation( $product_id, $ppom_id, $variation_id ) ) {
				continue;
			}

			if ( ! self::field_requires_add_to_cart_schema_checks( $field ) ) {
				continue;
			}

			$passed = apply_filters( 'ppom_before_fields_validation', $passed, $field, $post_data, $product_id );

			$data_name = sanitize_key( $field['data_name'] );

			$title = isset( $field['title'] ) ? $field['title'] : '';

			// var_dump($data_name, Helpers::is_field_hidden_by_condition($data_name));
			// Check if field is required by hidden by condition
			if ( Helpers::is_field_hidden_by_condition( $data_name ) ) {
				continue;
			}

			$max_min_validation = Helpers::posted_field_max_min_value_validation( $ppom_posted_fields, $field );
			if ( ! empty( $max_min_validation ) ) {
				Helpers::wc_add_notice( $max_min_validation );
				$passed = false;
			} elseif ( isset( $field['required'] ) && 'on' === $field['required'] && ! Helpers::has_posted_field_value( $ppom_posted_fields, $field ) ) {

				// Note: Checkbox is being validate by hook: ppom_has_posted_field_value
				// $error_message = isset($field['error_message']) ? $field['error_message'] : '';
				// $error_message = (isset($field['error_message']) && $field['error_message'] != '') ? $title.": ".$field['error_message'] : "{$title} is a required field";
			
				$error_message = ( isset( $field['error_message'] ) && $field['error_message'] != '' )
				? sprintf( '%1$s: %2$s', $title, $field['error_message'] )
				: sprintf(
					/* translators: %s: the name of the field. */
					__( '%s is a required field', 'woocommerce-product-addon' ),
					$title
				);
				$error_message = $error_message;
				$error_message = stripslashes( $error_message );
				Helpers::wc_add_notice( $error_message );
				$passed = false;
			}
		}

		// ppom_pa($post_data); exit;

		return apply_filters( 'ppom_add_to_cart_validation', $passed, $ppom, $product_id, $variation_id );
	}
}
