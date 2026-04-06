<?php
/**
 * PPOM AI Service
 * Shared AI backend for formula builder, onboarding wizard, and template suggestions.
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not Allowed' );
}

class PPOM_AI_Service {

	private static $ins;

	const OPTION_API_KEY  = 'ppom_ai_api_key';
	const OPTION_PROVIDER = 'ppom_ai_provider';

	public static function get_instance() {
		is_null( self::$ins ) && self::$ins = new self();
		return self::$ins;
	}

	function __construct() {
		add_action( 'wp_ajax_ppom_ai_generate', array( $this, 'ajax_generate' ) );
		add_action( 'wp_ajax_ppom_ai_formula', array( $this, 'ajax_formula' ) );
		add_action( 'wp_ajax_ppom_ai_generate_css', array( $this, 'ajax_generate_css' ) );
		add_action( 'wp_ajax_ppom_ai_generate_js', array( $this, 'ajax_generate_js' ) );
		add_action( 'wp_ajax_ppom_get_preview_url', array( $this, 'ajax_get_preview_url' ) );
		add_action( 'wp_ajax_ppom_search_products', array( $this, 'ajax_search_products' ) );
		add_action( 'wp_ajax_ppom_debug_product', array( $this, 'ajax_debug_product' ) );
		add_action( 'wp_ajax_ppom_debug_fix_product', array( $this, 'ajax_debug_fix_product' ) );
		add_action( 'wp_ajax_ppom_reset_product_assignments', array( $this, 'ajax_reset_product_assignments' ) );
		add_action( 'wp_ajax_ppom_add_fields_to_group', array( $this, 'ajax_add_fields_to_group' ) );
	}

	/**
	 * Get an AI setting from the PPOM settings panel.
	 */
	private static function get_ai_setting( $key, $default = '' ) {
		if ( class_exists( 'PPOM_SettingsFramework' ) ) {
			$value = PPOM_SettingsFramework::get_saved_settings( $key, $default );
			if ( ! empty( $value ) ) {
				return $value;
			}
		}
		// Fallback to direct option (in case settings framework isn't loaded yet)
		return get_option( $key, $default );
	}

	/**
	 * Check if AI features are available (API key configured).
	 */
	public static function is_available() {
		return ! empty( self::get_ai_setting( self::OPTION_API_KEY ) );
	}

	/**
	 * Get configured provider.
	 */
	public static function get_provider() {
		return self::get_ai_setting( self::OPTION_PROVIDER, 'openai' );
	}

	/**
	 * Call the AI API.
	 *
	 * @param string $system_prompt System instructions.
	 * @param string $user_prompt   User message.
	 * @return array|WP_Error Response array with 'content' key, or WP_Error.
	 */
	public function call_ai( $system_prompt, $user_prompt ) {
		$api_key  = self::get_ai_setting( self::OPTION_API_KEY );
		$provider = self::get_provider();

		if ( empty( $api_key ) ) {
			return new WP_Error( 'no_api_key', __( 'AI API key is not configured. Go to WooCommerce > PPOM Settings to add it.', 'woocommerce-product-addon' ) );
		}

		if ( 'anthropic' === $provider ) {
			return $this->call_anthropic( $api_key, $system_prompt, $user_prompt );
		}

		return $this->call_openai( $api_key, $system_prompt, $user_prompt );
	}

	/**
	 * Call OpenAI Chat Completions API.
	 */
	private function call_openai( $api_key, $system_prompt, $user_prompt ) {
		$response = wp_remote_post( 'https://api.openai.com/v1/chat/completions', array(
			'timeout' => 60,
			'headers' => array(
				'Authorization' => 'Bearer ' . $api_key,
				'Content-Type'  => 'application/json',
			),
			'body'    => wp_json_encode( array(
				'model'    => 'gpt-4o-mini',
				'messages' => array(
					array( 'role' => 'system', 'content' => $system_prompt ),
					array( 'role' => 'user', 'content' => $user_prompt ),
				),
				'temperature' => 0.3,
				'max_tokens'  => 4000,
			) ),
		) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( isset( $body['error'] ) ) {
			return new WP_Error( 'ai_error', $body['error']['message'] ?? __( 'AI API error', 'woocommerce-product-addon' ) );
		}

		return array(
			'content' => $body['choices'][0]['message']['content'] ?? '',
		);
	}

	/**
	 * Call Anthropic Messages API.
	 */
	private function call_anthropic( $api_key, $system_prompt, $user_prompt ) {
		$response = wp_remote_post( 'https://api.anthropic.com/v1/messages', array(
			'timeout' => 60,
			'headers' => array(
				'x-api-key'         => $api_key,
				'anthropic-version' => '2023-06-01',
				'Content-Type'      => 'application/json',
			),
			'body'    => wp_json_encode( array(
				'model'      => 'claude-sonnet-4-20250514',
				'max_tokens' => 4000,
				'system'     => $system_prompt,
				'messages'   => array(
					array( 'role' => 'user', 'content' => $user_prompt ),
				),
			) ),
		) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( isset( $body['error'] ) ) {
			return new WP_Error( 'ai_error', $body['error']['message'] ?? __( 'AI API error', 'woocommerce-product-addon' ) );
		}

		return array(
			'content' => $body['content'][0]['text'] ?? '',
		);
	}

	/**
	 * System prompt for generating PPOM field group configurations.
	 */
	public function get_fieldgroup_system_prompt() {
		return 'You are a WooCommerce product customization expert. You help store owners configure PPOM (Personalized Product Option Manager) field groups for their products.

You generate field group configurations as JSON arrays. Each field is an object with these properties:

ESSENTIAL properties (always include):
- "type": Field type. Available types: "text", "textarea", "email", "number", "hidden", "checkbox", "radio", "select", "date", "measure", "pricematrix", "bulkquantity"
- "title": Display label shown to customers
- "data_name": Unique lowercase identifier using underscores (e.g., "custom_text", "shirt_size")
- "description": Help text shown near the field. Always add helpful descriptions.
- "placeholder": Placeholder text (for text/textarea/email/number). Always add meaningful examples.
- "required": "on" if required, "" if optional
- "price": Add-on price as a plain number string (no currency symbols). The store currency is applied automatically. Use "10" for fixed, "10%" for percentage, or "" for no price.
- "options": For select/radio/checkbox — array of objects with "option" (label), "price" (plain number or ""), and "id" (unique lowercase slug)
- "status": Always "on"

ADVANCED properties (always configure for best results):
- "width": Column width 1-12 (Bootstrap grid). Use smart layout: "6" for fields that pair nicely side-by-side (like size + color), "4" for compact fields (numbers, small selects), "12" for textareas, checkboxes with many options, or fields needing full width. Think about visual grouping.
- "error_message": Custom validation message when field is required but empty. Always set for required fields.
- "maxlength": Character limit for text/textarea fields. Set sensible limits (e.g., "30" for names, "200" for messages).
- "desc_tooltip": Set to "on" to show description as a hover tooltip instead of inline text. Use for fields where the description is helpful but would clutter the layout.
- "onetime": Set to "on" for add-on prices that should NOT multiply with cart quantity (e.g., engraving fee, setup fee). Leave "" for per-unit prices.
- "default_value": Pre-filled value for text/number fields where a sensible default exists.
- "visibility": Usually "everyone". Set to "loggedin" for member-only fields.
- "class": Additional CSS classes. Usually leave as "".
- "logic": "" (no conditions) or "on" (has conditions)
- "conditions": Leave as "" unless conditional logic is needed

PRICING RULES:
- All prices are plain numbers WITHOUT currency symbols (e.g., "5.99" not "$5.99"). WooCommerce formats the currency automatically.
- Use "" (empty string) when an option has no extra cost.
- Use percentage format "10%" for percentage-based pricing.

For measure fields (area/length pricing):
- "price": Base price per unit (plain number)
- "price-multiplier": Conversion multiplier (e.g., "0.01" to convert cm to m)
- "min": Minimum value, "max": Maximum value, "step": Step increment

For pricematrix fields (quantity-based pricing):
- "options": Array of objects with "option" (range like "1-25"), "price" (price for that range), "id" (unique slug)

STYLING: If the user describes how they want the fields to look (modern, minimal, dark, elegant, etc.), include a "css" key with CSS code. Use "selector" as the wrapper element (PPOM replaces it with the actual class at render time). Target elements: selector (the wrapper), selector label, selector input, selector select, selector textarea, selector .ppom-field-wrapper. Include hover/focus states. Keep the CSS clean and avoid !important unless needed. If no styling is mentioned, omit the "css" key entirely.

IMPORTANT: Respond ONLY with valid JSON. The response must be a JSON object with:
{
  "name": "Field group name",
  "fields": [ ...array of field objects... ],
  "explanation": "Brief explanation of the configuration for the store owner",
  "css": "optional CSS string using selector as wrapper — only if user described styling"
}';
	}

	/**
	 * System prompt for the formula/pricing AI builder.
	 */
	public function get_formula_system_prompt() {
		return 'You are a pricing formula expert for WooCommerce PPOM plugin. Users describe what fields to add or modify and you generate PPOM field configurations.

CONTEXT: The user may have EXISTING FIELDS listed at the start of their message. You must:
- Only output NEW fields to ADD. Never recreate existing fields.
- If the user asks to REMOVE a field, include it in the "remove_fields" array by its data_name.
- If the user asks to MODIFY a field, include it in "remove_fields" (old) and add the replacement in "fields" (new).

IMPORTANT RULES:
- All prices are plain numbers WITHOUT currency symbols (e.g., "5.00" not "$5.00").
- For select/radio/checkbox options, each option MUST use the key "option" (not "label" or "name") for the display text. Format: {"option": "Display Text", "price": "5", "id": "unique_slug"}
- "id" in options must be a unique lowercase slug.

PPOM pricing mechanisms:
1. Fixed add-on price: "price": "5.00"
2. Percentage: "price": "10%"
3. Measure field: "price" + "price-multiplier" for dimension-based pricing
4. Price Matrix: options with ranges like "1-25"
5. Options with prices: select/radio/checkbox where each option object has "option", "price", "id"
6. One-time fee: "onetime": "on"

Always include these properties in each field:
- "width": Smart column widths (4, 6, or 12)
- "error_message": For required fields
- "description": Helpful context for customers
- "placeholder": For text/number inputs
- "status": Always "on"

Respond with a JSON object:
{
  "fields": [ ...array of NEW field configs to ADD... ],
  "remove_fields": [ ...array of data_name strings to REMOVE (optional)... ],
  "explanation": "What will change — fields added and/or removed",
  "example": { "inputs": {}, "calculation": "", "total_addon": "" }
}';
	}

	/**
	 * AJAX: Generate field group from description (for onboarding wizard).
	 */
	public function ajax_generate() {
		check_ajax_referer( 'ppom_ai_nonce', 'nonce' );

		if ( ! ppom_security_role() ) {
			wp_send_json_error( __( 'Permission denied.', 'woocommerce-product-addon' ) );
		}

		$description = isset( $_POST['description'] ) ? sanitize_textarea_field( $_POST['description'] ) : '';
		$category    = isset( $_POST['category'] ) ? sanitize_text_field( $_POST['category'] ) : '';

		if ( empty( $description ) ) {
			wp_send_json_error( __( 'Please describe your product customization needs.', 'woocommerce-product-addon' ) );
		}

		$user_prompt = '';
		if ( ! empty( $category ) ) {
			$user_prompt .= "Product category: {$category}\n\n";
		}
		$user_prompt .= "Store owner's description:\n{$description}";

		$result = $this->call_ai( $this->get_fieldgroup_system_prompt(), $user_prompt );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		}

		// Parse the JSON from the AI response
		$content = $result['content'];

		// Extract JSON from markdown code blocks if present
		if ( preg_match( '/```(?:json)?\s*([\s\S]*?)```/', $content, $matches ) ) {
			$content = trim( $matches[1] );
		}

		$parsed = json_decode( $content, true );
		if ( ! $parsed || ! isset( $parsed['fields'] ) ) {
			wp_send_json_error( __( 'Failed to parse AI response. Please try again.', 'woocommerce-product-addon' ) );
		}

		wp_send_json_success( $parsed );
	}

	/**
	 * AJAX: Generate formula/pricing config from description.
	 */
	public function ajax_formula() {
		check_ajax_referer( 'ppom_ai_nonce', 'nonce' );

		if ( ! ppom_security_role() ) {
			wp_send_json_error( __( 'Permission denied.', 'woocommerce-product-addon' ) );
		}

		$description = isset( $_POST['description'] ) ? sanitize_textarea_field( $_POST['description'] ) : '';

		if ( empty( $description ) ) {
			wp_send_json_error( __( 'Please describe your pricing logic.', 'woocommerce-product-addon' ) );
		}

		$result = $this->call_ai( $this->get_formula_system_prompt(), $description );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		}

		$content = $result['content'];
		if ( preg_match( '/```(?:json)?\s*([\s\S]*?)```/', $content, $matches ) ) {
			$content = trim( $matches[1] );
		}

		$parsed = json_decode( $content, true );
		if ( ! $parsed || ! isset( $parsed['fields'] ) ) {
			wp_send_json_error( __( 'Failed to parse AI response. Please try again.', 'woocommerce-product-addon' ) );
		}

		wp_send_json_success( $parsed );
	}
	/**
	 * AJAX: Add AI-generated fields to an existing field group.
	 */
	/**
	 * AJAX: Generate CSS for the Style tab using AI.
	 */
	public function ajax_generate_css() {
		check_ajax_referer( 'ppom_ai_nonce', 'nonce' );

		if ( ! ppom_security_role() ) {
			wp_send_json_error( __( 'Permission denied.', 'woocommerce-product-addon' ) );
		}

		$prompt = isset( $_POST['prompt'] ) ? sanitize_textarea_field( $_POST['prompt'] ) : '';
		if ( empty( $prompt ) ) {
			wp_send_json_error( __( 'Please describe the style you want.', 'woocommerce-product-addon' ) );
		}

		$system = 'You are a CSS expert for WooCommerce product pages. Generate CSS for PPOM (product option) fields.

RULES:
- Use "selector" as the wrapper element (PPOM replaces it with the actual wrapper class).
- Target common elements: selector label, selector input, selector select, selector textarea, selector .form-group, selector .ppom-input-wrapper, selector .ppom-field-wrapper.
- Generate clean, production-ready CSS only.
- Include hover/focus states where appropriate.
- Use modern CSS (flexbox, border-radius, transitions, box-shadow).
- Do NOT use @import or external fonts.
- Respond with ONLY the CSS code, no explanations, no markdown code blocks, no comments.';

		$result = $this->call_ai( $system, $prompt );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		}

		$css = $result['content'];
		// Strip markdown code blocks if AI added them despite instructions
		$css = preg_replace( '/^```(?:css)?\s*/m', '', $css );
		$css = preg_replace( '/```\s*$/m', '', $css );
		$css = trim( $css );

		if ( empty( $css ) ) {
			wp_send_json_error( __( 'AI returned empty CSS. Try rephrasing.', 'woocommerce-product-addon' ) );
		}

		wp_send_json_success( array( 'css' => $css ) );
	}

	/**
	 * AJAX: Generate JS for the Style tab using AI.
	 */
	public function ajax_generate_js() {
		check_ajax_referer( 'ppom_ai_nonce', 'nonce' );

		if ( ! ppom_security_role() ) {
			wp_send_json_error( __( 'Permission denied.', 'woocommerce-product-addon' ) );
		}

		$prompt = isset( $_POST['prompt'] ) ? sanitize_textarea_field( $_POST['prompt'] ) : '';
		if ( empty( $prompt ) ) {
			wp_send_json_error( __( 'Please describe the behavior you want.', 'woocommerce-product-addon' ) );
		}

		$system = 'You are a JavaScript expert for WooCommerce product pages with PPOM (product option) fields.

CONTEXT: The JS runs on the product page after PPOM fields are loaded. jQuery is available.

PPOM DOM STRUCTURE:
- Wrapper: .ppom-wrapper
- Each field: .ppom-field-wrapper.ppom-col with data-data_name and data-type attributes
- Labels: label.form-control-label
- Text inputs: input.form-control.ppom-input (with data-data_name, data-type, maxlength attributes)
- Selects: select.ppom-input.form-select
- Checkboxes: input.checkbox.ppom-check-input
- Radio: input.ppom-input[type=radio]
- Measure inputs: input.ppom-measure-input inside .ppom-measure
- Price container: #ppom-price-container or .ppom-price-container
- Error messages: shown via .ppom-has-error class on field wrapper
- Form: form.cart

RULES:
- Use jQuery() not $() since it runs outside a closure.
- Use /* block comments */ not // line comments.
- Keep code concise and production-ready.
- Respond with ONLY the JavaScript code, no explanations, no markdown code blocks.';

		$result = $this->call_ai( $system, $prompt );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		}

		$js = $result['content'];
		$js = preg_replace( '/^```(?:javascript|js)?\s*/m', '', $js );
		$js = preg_replace( '/```\s*$/m', '', $js );
		$js = trim( $js );

		if ( empty( $js ) ) {
			wp_send_json_error( __( 'AI returned empty JS. Try rephrasing.', 'woocommerce-product-addon' ) );
		}

		wp_send_json_success( array( 'js' => $js ) );
	}

	public function ajax_add_fields_to_group() {
		check_ajax_referer( 'ppom_ai_nonce', 'nonce' );

		if ( ! ppom_security_role() ) {
			wp_send_json_error( __( 'Permission denied.', 'woocommerce-product-addon' ) );
		}

		$group_id      = isset( $_POST['group_id'] ) ? intval( $_POST['group_id'] ) : 0;
		$fields_json   = isset( $_POST['fields'] ) ? wp_unslash( $_POST['fields'] ) : '';
		$remove_json   = isset( $_POST['remove_fields'] ) ? wp_unslash( $_POST['remove_fields'] ) : '';

		if ( ! $group_id ) {
			wp_send_json_error( __( 'Missing group ID.', 'woocommerce-product-addon' ) );
		}

		$new_fields    = ! empty( $fields_json ) ? json_decode( $fields_json, true ) : array();
		$remove_fields = ! empty( $remove_json ) ? json_decode( $remove_json, true ) : array();

		if ( ! is_array( $new_fields ) ) {
			$new_fields = array();
		}
		if ( ! is_array( $remove_fields ) ) {
			$remove_fields = array();
		}

		if ( empty( $new_fields ) && empty( $remove_fields ) ) {
			wp_send_json_error( __( 'No fields to add or remove.', 'woocommerce-product-addon' ) );
		}

		// Get existing fields
		global $wpdb;
		$ppom_table = $wpdb->prefix . PPOM_TABLE_META;
		$existing_json = $wpdb->get_var( $wpdb->prepare(
			"SELECT the_meta FROM $ppom_table WHERE productmeta_id = %d",
			$group_id
		) );

		$existing_fields = $existing_json ? json_decode( $existing_json, true ) : array();
		if ( ! is_array( $existing_fields ) ) {
			$existing_fields = array();
		}

		// Remove fields by data_name
		$removed_count = 0;
		if ( ! empty( $remove_fields ) ) {
			foreach ( $existing_fields as $key => $field ) {
				if ( isset( $field['data_name'] ) && in_array( $field['data_name'], $remove_fields, true ) ) {
					unset( $existing_fields[ $key ] );
					$removed_count++;
				}
			}
			// Re-index
			$existing_fields = array_values( $existing_fields );
			$reindexed = array();
			$i = 1;
			foreach ( $existing_fields as $field ) {
				$reindexed[ $i ] = $field;
				$i++;
			}
			$existing_fields = $reindexed;
		}

		// Normalize new fields (fix common AI output issues)
		foreach ( $new_fields as &$field ) {
			$field = self::normalize_field( $field );
		}
		unset( $field );

		// Find the next index
		$next_index = empty( $existing_fields ) ? 1 : max( array_keys( $existing_fields ) ) + 1;

		// Append new fields
		foreach ( $new_fields as $field ) {
			$existing_fields[ $next_index ] = $field;
			$next_index++;
		}

		// Run through the standard save filter to add ppom_id etc.
		$existing_fields = apply_filters( 'ppom_meta_data_saving', $existing_fields, $group_id );
		$existing_fields = ppom_sanitize_array_data( $existing_fields );

		// Save
		ppom_admin_update_ppom_meta_only( $group_id, $existing_fields );

		$msg_parts = array();
		if ( count( $new_fields ) > 0 ) {
			$msg_parts[] = sprintf( __( '%d field(s) added', 'woocommerce-product-addon' ), count( $new_fields ) );
		}
		if ( $removed_count > 0 ) {
			$msg_parts[] = sprintf( __( '%d field(s) removed', 'woocommerce-product-addon' ), $removed_count );
		}

		wp_send_json_success( array(
			'message'      => implode( ', ', $msg_parts ) . '.',
			'fields_count' => count( $existing_fields ),
		) );
	}

	/**
	 * Normalize a field array from AI output to match PPOM expected format.
	 */
	public static function normalize_field( $field ) {
		// Normalize options: AI sometimes uses "label"/"name"/"text" instead of "option"
		if ( isset( $field['options'] ) && is_array( $field['options'] ) ) {
			foreach ( $field['options'] as &$opt ) {
				if ( ! isset( $opt['option'] ) ) {
					if ( isset( $opt['label'] ) ) {
						$opt['option'] = $opt['label'];
						unset( $opt['label'] );
					} elseif ( isset( $opt['name'] ) ) {
						$opt['option'] = $opt['name'];
						unset( $opt['name'] );
					} elseif ( isset( $opt['text'] ) ) {
						$opt['option'] = $opt['text'];
						unset( $opt['text'] );
					} elseif ( isset( $opt['value'] ) ) {
						$opt['option'] = $opt['value'];
					}
				}
				// Ensure price exists
				if ( ! isset( $opt['price'] ) ) {
					$opt['price'] = '';
				}
				// Ensure id exists
				if ( ! isset( $opt['id'] ) ) {
					$opt['id'] = sanitize_key( $opt['option'] ?? 'opt' );
				}
			}
			unset( $opt );
		}

		// Ensure status
		if ( ! isset( $field['status'] ) ) {
			$field['status'] = 'on';
		}

		return $field;
	}

	/**
	 * AJAX: Reset all PPOM group assignments for a product.
	 */
	public function ajax_reset_product_assignments() {
		check_ajax_referer( 'ppom_reset_assignments', '_wpnonce' );

		if ( ! ppom_security_role() ) {
			wp_send_json_error( __( 'Permission denied.', 'woocommerce-product-addon' ) );
		}

		$product_id = isset( $_POST['product_id'] ) ? intval( $_POST['product_id'] ) : 0;
		if ( ! $product_id ) {
			wp_send_json_error( __( 'No product ID.', 'woocommerce-product-addon' ) );
		}

		delete_post_meta( $product_id, PPOM_PRODUCT_META_KEY );

		wp_send_json_success( array(
			'message' => __( 'All PPOM field group assignments have been removed from this product. You can now re-assign groups.', 'woocommerce-product-addon' ),
		) );
	}

	/**
	 * AJAX: Debug product PPOM assignment (temporary diagnostic tool).
	 */
	public function ajax_debug_product() {
		if ( ! ppom_security_role() ) {
			wp_send_json_error( 'Permission denied' );
		}

		$product_id = isset( $_GET['product_id'] ) ? intval( $_GET['product_id'] ) : 0;
		if ( ! $product_id ) {
			wp_send_json_error( 'No product_id' );
		}

		$raw_meta = get_post_meta( $product_id, PPOM_PRODUCT_META_KEY, true );

		$ppom = new PPOM_Meta( $product_id );

		// Check CSS for ALL attached groups
		global $wpdb;
		$ppom_table = $wpdb->prefix . PPOM_TABLE_META;
		$group_ids = is_array( $raw_meta ) ? $raw_meta : ( is_numeric( $raw_meta ) ? array( $raw_meta ) : array() );
		$groups_css = array();
		foreach ( $group_ids as $gid ) {
			$row = $wpdb->get_row( $wpdb->prepare(
				"SELECT productmeta_name, productmeta_style FROM $ppom_table WHERE productmeta_id = %d",
				$gid
			) );
			$groups_css[ $gid ] = array(
				'name'  => $row ? $row->productmeta_name : 'NOT FOUND',
				'style' => $row ? $row->productmeta_style : 'NOT FOUND',
			);
		}

		wp_send_json_success( array(
			'product_id'      => $product_id,
			'group_ids'       => $group_ids,
			'groups_css'      => $groups_css,
			'ppom_inline_css' => $ppom->inline_css ?? '',
			'is_legacy_user'  => ppom_is_legacy_user(),
		) );
	}

	/**
	 * AJAX: Fix a product's stale PPOM group references.
	 * Removes group IDs that no longer exist in the database.
	 */
	public function ajax_debug_fix_product() {
		if ( ! ppom_security_role() ) {
			wp_send_json_error( 'Permission denied' );
		}

		$product_id = isset( $_POST['product_id'] ) ? intval( $_POST['product_id'] ) : 0;
		if ( ! $product_id ) {
			wp_send_json_error( 'No product_id' );
		}

		global $wpdb;
		$ppom_table = $wpdb->prefix . PPOM_TABLE_META;

		$raw_meta = get_post_meta( $product_id, PPOM_PRODUCT_META_KEY, true );
		$group_ids = is_array( $raw_meta ) ? $raw_meta : ( is_numeric( $raw_meta ) ? array( $raw_meta ) : array() );

		$valid_ids = array();
		$removed_ids = array();

		foreach ( $group_ids as $gid ) {
			$exists = $wpdb->get_var( $wpdb->prepare(
				"SELECT productmeta_id FROM $ppom_table WHERE productmeta_id = %d",
				$gid
			) );
			if ( $exists ) {
				$valid_ids[] = (int) $gid;
			} else {
				$removed_ids[] = (int) $gid;
			}
		}

		if ( ! empty( $valid_ids ) ) {
			update_post_meta( $product_id, PPOM_PRODUCT_META_KEY, $valid_ids );
		} else {
			delete_post_meta( $product_id, PPOM_PRODUCT_META_KEY );
		}

		wp_send_json_success( array(
			'message'     => sprintf( 'Cleaned product %d. Removed %d stale group(s), kept %d valid group(s).', $product_id, count( $removed_ids ), count( $valid_ids ) ),
			'removed_ids' => $removed_ids,
			'valid_ids'   => $valid_ids,
		) );
	}

	/**
	 * AJAX: Search products for Select2 AJAX mode.
	 * Returns paginated results with search support.
	 */
	public function ajax_search_products() {
		if ( ! ppom_security_role() ) {
			wp_send_json( array( 'results' => array(), 'pagination' => array( 'more' => false ) ) );
		}

		$search   = isset( $_GET['q'] ) ? sanitize_text_field( $_GET['q'] ) : '';
		$page     = isset( $_GET['page'] ) ? max( 1, intval( $_GET['page'] ) ) : 1;
		$per_page = 20;

		$args = array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => $per_page,
			'paged'          => $page,
			'orderby'        => 'title',
			'order'          => 'ASC',
		);

		if ( ! empty( $search ) ) {
			$args['s'] = $search;
		}

		$query   = new WP_Query( $args );
		$results = array();

		while ( $query->have_posts() ) {
			$query->the_post();
			$results[] = array(
				'id'   => get_the_ID(),
				'text' => get_the_title() . ' (#' . get_the_ID() . ')',
			);
		}
		wp_reset_postdata();

		wp_send_json( array(
			'results'    => $results,
			'pagination' => array( 'more' => $query->max_num_pages > $page ),
		) );
	}

	/**
	 * AJAX: Get the frontend URL for a product (for live preview).
	 */
	public function ajax_get_preview_url() {
		if ( ! ppom_security_role() ) {
			wp_send_json_error( '' );
		}

		$product_id = isset( $_GET['product_id'] ) ? intval( $_GET['product_id'] ) : 0;
		if ( ! $product_id ) {
			wp_send_json_error( '' );
		}

		$url = get_permalink( $product_id );
		wp_send_json_success( $url ? $url : '' );
	}
}

// Initialize
PPOM_AI_Service::get_instance();
