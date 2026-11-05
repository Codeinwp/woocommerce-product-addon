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

use Automattic\WooCommerce\StoreApi\Exceptions\RouteException;
use PPOM_Form;
use PPOM_Meta;
use WC_Product;
use WP_Post;
use WP_REST_Request;
use PPOM\Files\Handler;
use PPOM\Hooks\Callbacks;
use PPOM\Support\Helpers;
use PPOM\WooCommerce\Cart\CartHandler;

/**
 * @internal
 */
final class ProductHandler {

	/**
	 * Products that passed WooCommerce's add-to-cart validation and await their add.
	 *
	 * @var array<int, true>
	 */
	private static $validated_adds = array();

	/**
	 * Whether a Store API route is being dispatched.
	 *
	 * @var bool
	 */
	private static $dispatching_store_api = false;

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

			echo '<div class="error">' . esc_html__( 'Some of your fields have duplicate data names. Please fix them.', 'woocommerce-product-addon' ) . '</div>';

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

		// Server-side even with browser validation: dropped uploads leave a field empty.
		if ( ! self::validate_required_uploads( (int) $product_id, (int) $variation_id ) ) {
			$passed = false;
		}

		$posted_fields = isset( $_POST['ppom']['fields'] ) ? wp_unslash( $_POST['ppom']['fields'] ) : null; // phpcs:ignore WordPress.Security.NonceVerification,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$fields        = is_array( $posted_fields ) ? ppom_sanitize_array_data( $posted_fields ) : null;
		$product_id    = isset( $_POST['ppom_product_id'] ) ? intval( wp_unslash( $_POST['ppom_product_id'] ) ) : $product_id; // phpcs:ignore WordPress.Security.NonceVerification

		if ( Helpers::get_price_mode() == 'legacy' && $fields ) {

			if ( Helpers::is_price_attached_with_fields( $fields ) && empty( $_POST['ppom']['ppom_option_price'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
				// Price-matrix pricing is stored in ppom_pricematrix and handled independently.
				if ( isset( $_POST['ppom']['ppom_pricematrix'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
					return $passed;
				}

				$computed = Helpers::compute_option_price_from_fields( $fields, $product_id, wc_get_product( $variation_id ? $variation_id : $product_id ) );
				if ( ! empty( $computed ) ) {
					$_POST['ppom']['ppom_option_price'] = wp_json_encode( $computed );
					return $passed;
				}

				$error_message = __( 'An error occurred while processing your order. Please refresh the page and try again, or contact the site owner.', 'woocommerce-product-addon' );
				Helpers::wc_add_notice( $error_message );
				$passed = false;

				return $passed;
			}
		}

		return $passed;
	}

	/**
	 * Filter callback: product-page express checkout support (WooPayments).
	 *
	 * Express buttons add the product without the PPOM form, so its fields
	 * would be neither validated nor stored.
	 *
	 * @param bool                  $is_supported Gateway's verdict.
	 * @param WC_Product|false|null $product      Product on the page.
	 *
	 * @return bool
	 */
	public static function supports_express_checkout( $is_supported, $product ): bool {

		if ( ! $is_supported || ! $product instanceof WC_Product ) {
			return (bool) $is_supported;
		}

		return ! self::has_ppom_fields( $product );
	}

	/**
	 * Filter callback: hides Stripe's product-page express checkout for PPOM products.
	 *
	 * @param bool         $hide Gateway's verdict.
	 * @param WP_Post|null $post Current post.
	 *
	 * @return bool
	 */
	public static function hide_express_checkout_on_product_page( $hide, $post ): bool {

		// Stripe also runs this filter in a one-off settings migration; only answer on product pages.
		if ( $hide || ! is_product() || ! $post instanceof WP_Post ) {
			return (bool) $hide;
		}

		$product = wc_get_product( $post->ID );

		return $product instanceof WC_Product && self::has_ppom_fields( $product );
	}

	/**
	 * Rejects Store API adds that leave a required PPOM field empty.
	 *
	 * The add-to-cart validation treats a request without posted fields as
	 * having nothing to check. Here missing fields count as empty, and a
	 * conditional field counts as shown unless the posted form says it is
	 * hidden, since its visibility cannot be worked out without the form.
	 *
	 * @param WC_Product $product Product or variation being added.
	 *
	 * @return void
	 *
	 * @throws RouteException When a required field is empty.
	 */
	public static function validate_store_api_add_to_cart( WC_Product $product ): void {

		$product_id   = (int) Helpers::get_product_id( $product );
		$variation_id = $product->is_type( 'variation' ) ? $product->get_id() : 0;
		$ppom         = new PPOM_Meta( $product_id );
		if ( ! $ppom->fields ) {
			return;
		}

		$missing = self::missing_required_field( $ppom->fields, $product_id, $variation_id );
		if ( null !== $missing ) {
			throw new RouteException( 'ppom_required_field', self::required_field_message( $missing ), 400 );
		}
	}

	/**
	 * Validates PPOM fields when the add skipped WooCommerce's add-to-cart validation.
	 *
	 * PayPal Payments' buttons copy the product form into $_POST and call
	 * WC_Cart::add_to_cart() directly. An add that follows a passed validation
	 * of the same product (form submit) uses up that result; adds made from
	 * inside the filter keep the outer result. Store API adds are neither
	 * recorded nor checked here; validate_store_api_add_to_cart() checks them.
	 * PayPal's price simulations run on a half-filled form and are skipped; the
	 * add made on button click is checked.
	 *
	 * @param int $product_id   Product being added.
	 * @param int $quantity     Requested quantity.
	 * @param int $variation_id Selected variation, or 0.
	 *
	 * @return bool
	 */
	public static function validate_unvalidated_add( int $product_id, int $quantity, int $variation_id ): bool {

		// The Store API filters item data before validating, and checks the add itself.
		if ( self::$dispatching_store_api || doing_filter( 'woocommerce_add_to_cart_validation' ) || apply_filters( 'woocommerce_paypal_payments_is_simulating_cart', false ) ) {
			return true;
		}

		if ( isset( self::$validated_adds[ $product_id ] ) ) {
			unset( self::$validated_adds[ $product_id ] );
			return true;
		}

		$ppom = new PPOM_Meta( $product_id );
		if ( ! $ppom->fields ) {
			return true;
		}

		// check_validation() passes a payload with no fields, so required values are checked first.
		$missing = self::missing_required_field( $ppom->fields, $product_id, $variation_id );
		if ( null !== $missing ) {
			Helpers::wc_add_notice( self::required_field_message( $missing ) );
			return false;
		}

		$passed = self::validate_product( true, $product_id, $quantity, $variation_id );

		// validate_product() leaves field checks to the browser when AJAX validation is on.
		if ( $ppom->ajax_validation_enabled ) {
			$passed = self::check_validation( $product_id, $_POST, $passed, $variation_id );
		}

		return (bool) $passed;
	}

	/**
	 * Filter callback for rest_request_before_callbacks: notes a Store API dispatch.
	 *
	 * @param mixed                $response Response so far.
	 * @param array<string, mixed> $handler  Route handler.
	 * @param WP_REST_Request      $request  Request being dispatched.
	 *
	 * @return mixed
	 */
	public static function start_rest_dispatch( $response, $handler, WP_REST_Request $request ) {

		self::$dispatching_store_api = 0 === strpos( $request->get_route(), '/wc/store/' );

		return $response;
	}

	/**
	 * Filter callback for rest_request_after_callbacks: ends the noted dispatch.
	 *
	 * @param mixed $response Route response.
	 *
	 * @return mixed
	 */
	public static function end_rest_dispatch( $response ) {

		self::$dispatching_store_api = false;

		return $response;
	}

	/**
	 * Filter callback: records a product whose add-to-cart validation passed.
	 *
	 * Runs last on woocommerce_add_to_cart_validation so it sees the final verdict.
	 *
	 * @param bool       $passed     Validation verdict.
	 * @param int|string $product_id Product being validated.
	 *
	 * @return bool
	 */
	public static function record_validated_add( $passed, $product_id ): bool {

		// Store API adds never reach WC_Cart::add_to_cart(), which would use up the record.
		if ( $passed && ! self::$dispatching_store_api ) {
			self::$validated_adds[ (int) $product_id ] = true;
		}

		return (bool) $passed;
	}

	/**
	 * Whether the product has active PPOM fields.
	 *
	 * @param WC_Product $product Product or variation.
	 *
	 * @return bool
	 */
	private static function has_ppom_fields( WC_Product $product ): bool {

		$ppom = new PPOM_Meta( (int) Helpers::get_product_id( $product ) );

		return ! empty( $ppom->fields );
	}

	/**
	 * The first required field that applies to the selection and has no posted value.
	 *
	 * Absent `ppom[fields]` counts as empty. The posted hidden list is honoured
	 * only for fields with conditions; conditions are evaluated in the browser.
	 *
	 * @param array<array-key, mixed> $fields       Saved field definitions.
	 * @param int                     $product_id   Product being added.
	 * @param int                     $variation_id Selected variation, or 0.
	 *
	 * @return array<string, mixed>|null
	 */
	private static function missing_required_field( array $fields, int $product_id, int $variation_id ): ?array {

		$posted = isset( $_POST['ppom']['fields'] ) && is_array( $_POST['ppom']['fields'] ) ? wp_unslash( $_POST['ppom']['fields'] ) : array();

		foreach ( $fields as $field ) {
			if ( self::is_required_field_missing( $field, $posted, $product_id, $variation_id ) ) {
				return $field;
			}
		}

		return null;
	}

	/**
	 * Whether a required field that applies to the selection has no posted value.
	 *
	 * @param mixed                $field        Saved field definition.
	 * @param array<string, mixed> $posted       Posted `ppom[fields]`, or empty.
	 * @param int                  $product_id   Product being added.
	 * @param int                  $variation_id Selected variation, or 0.
	 *
	 * @return bool
	 *
	 * @phpstan-assert-if-true array<string, mixed> $field
	 */
	private static function is_required_field_missing( $field, array $posted, int $product_id, int $variation_id ): bool {

		$is_conditional = isset( $field['logic'] ) && 'on' === $field['logic'];

		return is_array( $field )
			&& ! empty( $field['data_name'] )
			&& isset( $field['required'] ) && 'on' === $field['required']
			&& self::field_applies_to_selection( $field, $product_id, $variation_id )
			&& ! ( $is_conditional && Helpers::is_field_hidden_by_condition( sanitize_key( $field['data_name'] ) ) )
			&& ! Helpers::has_posted_field_value( $posted, $field );
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

			if ( ! self::field_applies_to_selection( $field, (int) $product_id, $variation_id ) ) {
				continue;
			}

			if ( ! self::field_requires_add_to_cart_schema_checks( $field ) ) {
				continue;
			}

			$passed = apply_filters( 'ppom_before_fields_validation', $passed, $field, $post_data, $product_id );

			$data_name = sanitize_key( $field['data_name'] );

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
				Helpers::wc_add_notice( self::required_field_message( $field ) );
				$passed = false;
			}
		}

		// ppom_pa($post_data); exit;

		return apply_filters( 'ppom_add_to_cart_validation', $passed, $ppom, $product_id, $variation_id );
	}

	/**
	 * Rejects add-to-cart when a required upload field loses every file row to
	 * the ownership/schema check that runs before the item is stored.
	 *
	 * Required-field validation reads the raw request, where a file array counts
	 * as present when any nested value is filled, and it is skipped when
	 * validation runs in the browser, so a dropped upload would otherwise still
	 * let the product in.
	 *
	 * @param int $product_id   Product being added.
	 * @param int $variation_id Selected variation, or 0.
	 *
	 * @return bool
	 *
	 * @see Handler::retain_owned_uploads()
	 */
	private static function validate_required_uploads( int $product_id, int $variation_id ): bool {

		$posted = isset( $_POST['ppom'] ) ? $_POST['ppom'] : null; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- WooCommerce add-to-cart sends no nonce; read-only validation.
		if ( ! is_array( $posted ) || ! isset( $posted['fields'] ) || ! is_array( $posted['fields'] ) ) {
			return true;
		}

		$ppom = new PPOM_Meta( $product_id );
		if ( ! $ppom->fields ) {
			return true;
		}

		$owned  = Handler::retain_owned_uploads( $posted, $product_id, CartHandler::replaced_item_verified_files() );
		$passed = true;

		foreach ( $ppom->fields as $field ) {
			if ( ! self::is_required_upload_in_play( $field, $product_id, $variation_id ) ) {
				continue;
			}

			if ( Helpers::has_posted_field_value( $posted['fields'], $field ) && 0 === self::count_file_rows( $owned['fields'], (string) $field['data_name'] ) ) {
				Helpers::wc_add_notice( self::required_field_message( $field ) );
				$passed = false;
			}
		}

		return $passed;
	}

	/**
	 * Whether a field is a required, visible, active upload field.
	 *
	 * @param mixed $field        Saved field definition.
	 * @param int   $product_id   Product being added.
	 * @param int   $variation_id Selected variation, or 0.
	 *
	 * @return bool
	 */
	private static function is_required_upload_in_play( $field, int $product_id, int $variation_id ): bool {

		if ( ! is_array( $field ) || empty( $field['data_name'] ) || ! isset( $field['required'] ) || 'on' !== $field['required'] ) {
			return false;
		}

		return Handler::field_accepts_uploads( $field )
			&& self::field_applies_to_selection( $field, $product_id, $variation_id )
			&& ! Helpers::is_field_hidden_by_condition( sanitize_key( $field['data_name'] ) );
	}

	/**
	 * Whether a field is visible and its group is active for the variation.
	 *
	 * @param array<string, mixed> $field        Saved field definition.
	 * @param int                  $product_id   Product being added.
	 * @param int                  $variation_id Selected variation, or 0.
	 *
	 * @return bool
	 */
	private static function field_applies_to_selection( array $field, int $product_id, int $variation_id ): bool {

		if ( ! Helpers::is_field_visible( $field ) ) {
			return false;
		}

		$ppom_id = isset( $field['ppom_id'] ) ? absint( $field['ppom_id'] ) : 0;

		return 0 === $ppom_id || Helpers::is_meta_group_active_for_variation( $product_id, $ppom_id, $variation_id );
	}

	/**
	 * Counts file rows posted for a field, including its repeater clones.
	 *
	 * @param mixed  $fields    Posted PPOM fields.
	 * @param string $data_name Field data name.
	 *
	 * @return int
	 */
	private static function count_file_rows( $fields, string $data_name ): int {

		if ( ! is_array( $fields ) ) {
			return 0;
		}

		$data_name = sanitize_key( $data_name );
		$count     = 0;

		foreach ( $fields as $key => $rows ) {
			if ( ! is_array( $rows ) || ! in_array( $data_name, explode( '__clone_', (string) $key ), true ) ) {
				continue;
			}

			foreach ( $rows as $row ) {
				if ( is_array( $row ) && isset( $row['org'] ) ) {
					++$count;
				}
			}
		}

		return $count;
	}

	/**
	 * Builds the notice for a missing required field.
	 *
	 * @param array<string, mixed> $field Saved field definition.
	 *
	 * @return string
	 */
	private static function required_field_message( array $field ): string {

		$title = isset( $field['title'] ) ? $field['title'] : '';

		$error_message = ( isset( $field['error_message'] ) && $field['error_message'] != '' )
			? sprintf( '%1$s: %2$s', $title, $field['error_message'] )
			: sprintf(
				/* translators: %s: the name of the field. */
				__( '%s is a required field', 'woocommerce-product-addon' ),
				$title
			);

		return stripslashes( $error_message );
	}
}
