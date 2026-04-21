<?php
/**
 * Admin-only REST API for the React field modal.
 *
 * @package PPOM
 * @subpackage Admin\FieldModal
 */

namespace PPOM\Admin\FieldModal;

use PPOM\Meta\MetaRepositoryAccessor;
use PPOM\Support\Helpers;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * REST endpoints for loading and saving field groups from the React modal.
 *
 * @phpstan-type BootPayload array<string, mixed>
 * @phpstan-type SaveSuccess array{success: bool, message: string, productmeta_id: int, redirect_to: string, fields: array<int, array<string, mixed>>}
 *
 * @internal
 */
final class FieldModalRestController {

	/**
	 * REST route namespace (same base as public PPOM API).
	 *
	 * Public class constant for PHP 7.0+ compatibility (no visibility modifier required).
	 */
	const REST_NAMESPACE = 'ppom/v1';

	/**
	 * Schema builder instance.
	 *
	 * @var FieldModalSchemaBuilder
	 */
	private $schema_builder;

	/**
	 * Persistence handler instance.
	 *
	 * @var FieldModalPersistence
	 */
	private $persistence;

	/**
	 * Creates the controller with optional collaborators (for tests).
	 *
	 * Parameters are untyped so this stays valid on PHP 7.0 (no nullable object type hints).
	 *
	 * @param FieldModalSchemaBuilder|null $schema_builder Optional schema builder.
	 * @param FieldModalPersistence|null   $persistence    Optional persistence handler.
	 *
	 * @phpstan-param FieldModalSchemaBuilder|null $schema_builder
	 * @phpstan-param FieldModalPersistence|null   $persistence
	 */
	public function __construct( $schema_builder = null, $persistence = null ) {
		if ( $schema_builder instanceof FieldModalSchemaBuilder ) {
			$this->schema_builder = $schema_builder;
		} else {
			$this->schema_builder = new FieldModalSchemaBuilder();
		}
		if ( $persistence instanceof FieldModalPersistence ) {
			$this->persistence = $persistence;
		} else {
			$this->persistence = new FieldModalPersistence();
		}
	}

	/**
	 * Registers `ppom/v1/admin/field-groups` routes.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			self::REST_NAMESPACE,
			'/admin/field-groups/context',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_context' ),
				'permission_callback' => array( $this, 'permission_check' ),
				'args'                => array(
					'productmeta_id' => array(
						'type'              => 'integer',
						'default'           => 0,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/admin/field-groups/schema/(?P<type>[a-z0-9_-]+)',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_type_schema' ),
				'permission_callback' => array( $this, 'permission_check' ),
				'args'                => array(
					'type' => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_key',
					),
				),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/admin/field-groups',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'create_group' ),
				'permission_callback' => array( $this, 'permission_check' ),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/admin/field-groups/(?P<id>\d+)',
			array(
				'methods'             => array( 'PUT', 'PATCH' ),
				'callback'            => array( $this, 'update_group' ),
				'permission_callback' => array( $this, 'permission_check' ),
				'args'                => array(
					'id' => array(
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
				),
			)
		);
	}

	/**
	 * Ensures the current user matches PPOM admin permission roles.
	 *
	 * @return bool|WP_Error
	 */
	public function permission_check() {
		if ( ! Helpers::security_role() ) {
			return new WP_Error(
				'ppom_forbidden',
				__( 'Sorry, you are not allowed to perform this action.', 'woocommerce-product-addon' ),
				array( 'status' => 403 )
			);
		}
		return true;
	}

	/**
	 * GET JSON schema for one field type (lazy load for React modal).
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function get_type_schema( WP_REST_Request $request ) {
		$type   = (string) $request->get_param( 'type' );
		$schema = $this->schema_builder->get_schema_for_type( $type );

		return rest_ensure_response(
			array(
				'type'   => $type,
				'schema' => $schema,
			)
		);
	}

	/**
	 * GET context for bootstrapping the React modal.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error Response body includes catalog, fields, group, type_schemas, i18n.
	 *
	 * @phpstan-return WP_REST_Response|WP_Error
	 */
	public function get_context( WP_REST_Request $request ) {
		$productmeta_id = (int) $request->get_param( 'productmeta_id' );

		$catalog        = $this->schema_builder->get_catalog();
		$catalog_groups = $this->schema_builder->get_catalog_groups_for_rest();
		$license_ctx    = function_exists( 'ppom_get_admin_field_modal_license_context' )
			? ppom_get_admin_field_modal_license_context()
			: array(
				'plan_category'  => -1,
				'license_status' => 'invalid',
				'show_upsell'    => true,
			);
		$upsell         = function_exists( 'ppom_get_admin_field_modal_upsell_for_rest' )
			? ppom_get_admin_field_modal_upsell_for_rest()
			: null;

		$group = array(
			'productmeta_name'      => '',
			'dynamic_price_display' => 'hide',
			'send_file_attachment'  => 'YES',
			'show_cart_thumb'       => 'YES',
			'aviary_api_key'        => '',
			'productmeta_style'     => '',
			'productmeta_js'        => '',
		);

		$fields = array();

		$conditions_pro_enabled = function_exists( 'ppom_pro_is_installed' )
			&& ppom_pro_is_installed()
			&& 'valid' === apply_filters( 'product_ppom_license_status', '' );

		$conditional_repeater_unlocked = false;
		if ( function_exists( 'ppom_pro_is_installed' ) && ppom_pro_is_installed() && function_exists( 'PPOM' ) ) {
			$ppom = PPOM();
			if ( is_object( $ppom ) && method_exists( $ppom, 'is_license_of_type' ) ) {
				$conditional_repeater_unlocked = $ppom->is_license_of_type( 'plus' );
			}
		}
		/**
		 * Whether Conditional Field Repeater (Plus tier) is available in the admin UI.
		 * Tests may override without loading PPOM Pro.
		 *
		 * @param bool $unlocked       Default from license check.
		 * @param int  $productmeta_id Field group id or 0.
		 */
		$conditional_repeater_unlocked    = (bool) apply_filters(
			'ppom_field_modal_conditional_repeater_unlocked',
			$conditional_repeater_unlocked,
			$productmeta_id
		);
		$conditional_repeater_show_upsell = ! $conditional_repeater_unlocked;

		if ( $productmeta_id > 0 ) {
			$row = MetaRepositoryAccessor::instance()->get_row_by_id( $productmeta_id );
			if ( null === $row ) {
				return new WP_Error(
					'ppom_not_found',
					__( 'Field group not found.', 'woocommerce-product-addon' ),
					array( 'status' => 404 )
				);
			}
			$group['productmeta_name']      = isset( $row->productmeta_name ) ? (string) $row->productmeta_name : '';
			$group['dynamic_price_display'] = isset( $row->dynamic_price_display ) ? (string) $row->dynamic_price_display : 'hide';
			$group['send_file_attachment']  = isset( $row->send_file_attachment ) ? (string) $row->send_file_attachment : 'YES';
			$group['show_cart_thumb']       = isset( $row->show_cart_thumb ) ? (string) $row->show_cart_thumb : 'YES';
			$group['aviary_api_key']        = isset( $row->aviary_api_key ) ? (string) $row->aviary_api_key : '';
			$group['productmeta_style']     = isset( $row->productmeta_style ) ? (string) $row->productmeta_style : '';
			$group['productmeta_js']        = isset( $row->productmeta_js ) ? (string) $row->productmeta_js : '';

			$decoded = json_decode( isset( $row->the_meta ) ? (string) $row->the_meta : '[]', true );
			$fields  = is_array( $decoded ) ? array_values( $decoded ) : array();
		}

		$payload = array(
			'productmeta_id'                   => $productmeta_id,
			'conditions_pro_enabled'           => $conditions_pro_enabled,
			'conditional_repeater_unlocked'    => $conditional_repeater_unlocked,
			'conditional_repeater_show_upsell' => $conditional_repeater_show_upsell,
			'group'                            => $group,
			'fields'                           => $fields,
			'catalog'                          => $catalog,
			'catalog_groups'                   => $catalog_groups,
			'type_schemas'                     => array(),
			'license'                          => array(
				'plan_category'  => (int) $license_ctx['plan_category'],
				'license_status' => (string) $license_ctx['license_status'],
				'show_upsell'    => (bool) $license_ctx['show_upsell'],
			),
			'upsell'                           => $upsell,
			'rest_url'                         => rest_url( self::REST_NAMESPACE . '/' ),
			'nonce'                            => wp_create_nonce( 'wp_rest' ),
			'i18n'                             => array(
				'newFieldModal'                    => __( 'PPOM fields', 'woocommerce-product-addon' ),
				'fieldModalTitleWithType'          => __( '%1$s · %2$s', 'woocommerce-product-addon' ),
				'selectFieldType'                  => __( 'Select field type', 'woocommerce-product-addon' ),
				'save'                             => __( 'Save', 'woocommerce-product-addon' ),
				'close'                            => __( 'Close', 'woocommerce-product-addon' ),
				'addField'                         => __( 'Add field', 'woocommerce-product-addon' ),
				'editField'                        => __( 'Edit field', 'woocommerce-product-addon' ),
				'dataName'                         => __( 'Data name', 'woocommerce-product-addon' ),
				'title'                            => __( 'Title', 'woocommerce-product-addon' ),
				'fieldType'                        => __( 'Field type', 'woocommerce-product-addon' ),
				'loading'                          => __( 'Loading…', 'woocommerce-product-addon' ),
				'back'                             => __( 'Back', 'woocommerce-product-addon' ),
				'remove'                           => __( 'Remove', 'woocommerce-product-addon' ),
				'fieldsList'                       => __( 'Fields', 'woocommerce-product-addon' ),
				'searchFieldTypes'                 => __( 'Search field types…', 'woocommerce-product-addon' ),
				'noTypesMatch'                     => __( 'No types match your search.', 'woocommerce-product-addon' ),
				'noFieldTypes'                     => __( 'No field types are available.', 'woocommerce-product-addon' ),
				'selectOrAddHint'                  => __( 'Select a field from the list or add a new one.', 'woocommerce-product-addon' ),
				'allTab'                           => __( 'All', 'woocommerce-product-addon' ),
				'proBadge'                         => __( 'PRO', 'woocommerce-product-addon' ),
				'fieldGuideEmptyTitle'             => __( 'Field Guide', 'woocommerce-product-addon' ),
				'fieldGuideEmptyBody'              => __( 'Hover over any field to see what it does and when to use it.', 'woocommerce-product-addon' ),
				'fieldGuideRegion'                 => __( 'Field guide', 'woocommerce-product-addon' ),
				'examplesLabel'                    => __( 'Examples:', 'woocommerce-product-addon' ),
				'featuresLabel'                    => __( 'Features:', 'woocommerce-product-addon' ),
				'notRightForLabel'                 => __( 'Not the right field if:', 'woocommerce-product-addon' ),
				'openLegacyModal'                  => __( 'Open classic editor', 'woocommerce-product-addon' ),
				'fieldsTab'                        => __( 'Fields', 'woocommerce-product-addon' ),
				'settingsTab'                      => __( 'Settings', 'woocommerce-product-addon' ),
				'conditionsTab'                    => __( 'Conditions', 'woocommerce-product-addon' ),
				'repeaterTab'                      => __( 'Conditional Repeater', 'woocommerce-product-addon' ),
				'unsupportedControl'               => __( 'This control is not available in the React field editor yet.', 'woocommerce-product-addon' ),
				'fieldModalEditorUnavailable'      => __( 'Settings for this field could not be loaded in this editor. Reload the page or try again.', 'woocommerce-product-addon' ),
				'schemaEmptyResponse'              => __( 'No field settings were returned for this type. Check that the field type is registered.', 'woocommerce-product-addon' ),
				'manageFieldsEmpty'                => __( 'No fields yet. Use Add field above or choose a field type below.', 'woocommerce-product-addon' ),
				'manageFieldsEmptyRight'           => __( 'Choose a field type from the list on the left.', 'woocommerce-product-addon' ),
				'openAddFieldType'                 => __( 'Choose field type', 'woocommerce-product-addon' ),
				'showAdvancedSettings'             => __( 'Show advanced settings', 'woocommerce-product-addon' ),
				'hideAdvancedSettings'             => __( 'Hide advanced settings', 'woocommerce-product-addon' ),
				'editorSectionBasic'               => __( 'Basic', 'woocommerce-product-addon' ),
				'editorSectionFieldSettings'       => __( 'Field Settings', 'woocommerce-product-addon' ),
				'editorSectionDefaultPrice'        => __( 'Pricing', 'woocommerce-product-addon' ),
				'editorSectionConstraints'         => __( 'Constraints', 'woocommerce-product-addon' ),
				'editorSectionValidation'          => __( 'Validation', 'woocommerce-product-addon' ),
				'editorSectionDisplay'             => __( 'Display & layout', 'woocommerce-product-addon' ),
				'editorSectionBehavior'            => __( 'Behavior', 'woocommerce-product-addon' ),
				'editorSectionDateCalendar'        => __( 'Date & calendar', 'woocommerce-product-addon' ),
				'editorSectionImageDimensions'     => __( 'Image dimensions', 'woocommerce-product-addon' ),
				'editorSectionMedia'               => __( 'Media & layout', 'woocommerce-product-addon' ),
				'editorSectionVariationLayout'     => __( 'Layout & quantity limits', 'woocommerce-product-addon' ),
				'editorSectionMore'                => __( 'More options', 'woocommerce-product-addon' ),
				'selectOptionsTitle'               => __( 'Options', 'woocommerce-product-addon' ),
				'cfrSectionTitle'                  => __( 'Conditional Repeater', 'woocommerce-product-addon' ),
				'cfrLockedBody'                    => __( 'Repeat fields based on another field’s value (e.g. quantity). Upgrade to PPOM Pro Plus or higher to enable Conditional Repeater.', 'woocommerce-product-addon' ),
				'cfrEnableLabel'                   => __( 'Enable Conditional Repeat', 'woocommerce-product-addon' ),
				'cfrOriginLabel'                   => __( 'Origin', 'woocommerce-product-addon' ),
				'cfrOriginPlaceholder'             => __( 'Select origin field…', 'woocommerce-product-addon' ),
				'cfrOriginNone'                    => __( 'None', 'woocommerce-product-addon' ),
				'cfrOriginHelp'                    => __( 'Only Number, Variation Quantity, and Quantity Pack fields can be the origin.', 'woocommerce-product-addon' ),
				'cfrMagicTagsHeading'              => __( 'Magic tags', 'woocommerce-product-addon' ),
				'cfrMagicTagsDescription'          => __( 'Magic tags can be used in the Field Title (under the Fields tab).', 'woocommerce-product-addon' ),
				'cfrLearnMore'                     => __( 'Learn more', 'woocommerce-product-addon' ),
				'cfrDocsUrl'                       => 'https://docs.themeisle.com/article/1700-personalized-product-meta-manager#conditional-repeater',
				'cfrCopied'                        => __( 'Copied', 'woocommerce-product-addon' ),
				'cfrCopyFallback'                  => __( 'Copy this value:', 'woocommerce-product-addon' ),
				'cfrUpgradeCta'                    => __( 'Upgrade to Pro', 'woocommerce-product-addon' ),
				'cfrUpgradeUrl'                    => esc_url( tsdk_utmify( tsdk_translate_link( PPOM_UPGRADE_URL ), 'react-field-modal', 'cfr' ) ),
				'cfrViewDemoLabel'                 => __( 'View demo', 'woocommerce-product-addon' ),
				'cfrViewDemoUrl'                   => 'https://demo-ppom-lite.vertisite.cloud/product/personalized-caps-using-conditional-repeater/',
				'pairedOptionsAddRow'              => __( 'Add option', 'woocommerce-product-addon' ),
				'pairedOptionsRemove'              => __( 'Remove', 'woocommerce-product-addon' ),
				'pairedOptionsMoveUp'              => __( 'Move up', 'woocommerce-product-addon' ),
				'pairedOptionsMoveDown'            => __( 'Move down', 'woocommerce-product-addon' ),
				'pairedOptionLabel'                => __( 'Option', 'woocommerce-product-addon' ),
				'pairedOptionPrice'                => __( 'Price', 'woocommerce-product-addon' ),
				'pairedOptionWeight'               => __( 'Weight', 'woocommerce-product-addon' ),
				'pairedOptionStock'                => __( 'Stock', 'woocommerce-product-addon' ),
				'pairedOptionImageId'              => __( 'Image ID', 'woocommerce-product-addon' ),
				'pairedOptionDiscount'             => __( 'Discount', 'woocommerce-product-addon' ),
				'pairedOptionTooltip'              => __( 'Tooltip', 'woocommerce-product-addon' ),
				'pairedMatrixOption'               => __( 'Option', 'woocommerce-product-addon' ),
				'pairedMatrixPrice'                => __( 'Price', 'woocommerce-product-addon' ),
				'pairedMatrixLabel'              => __( 'Label', 'woocommerce-product-addon' ),
				'pairedMatrixOptionId'           => __( 'Unique option ID', 'woocommerce-product-addon' ),
				'pairedMatrixFixed'              => __( 'Fixed', 'woocommerce-product-addon' ),
				'palettesOptionsTitle'             => __( 'Add colors', 'woocommerce-product-addon' ),
				'priceMatrixOptionsTitle'          => __( 'Price matrix', 'woocommerce-product-addon' ),
				'bulkQuantityOptionsTitle'         => __( 'Bulk quantity', 'woocommerce-product-addon' ),
				'bulkQtyColumnQuantityRange'      => __( 'Quantity Range', 'woocommerce-product-addon' ),
				'bulkQtyColumnBasePrice'           => __( 'Base Price', 'woocommerce-product-addon' ),
				'bulkQtyAddRow'                    => __( 'Add quantity range', 'woocommerce-product-addon' ),
				'bulkQtyAddVariation'              => __( 'Add variation column', 'woocommerce-product-addon' ),
				'bulkQtyNewVariationPrompt'        => __( 'Variation column name', 'woocommerce-product-addon' ),
				'bulkQtyRangePlaceholder'          => __( '1-10', 'woocommerce-product-addon' ),
				'bulkQtyRangeHint'                 => __( 'Quantity range format: start-end (e.g. 1-10).', 'woocommerce-product-addon' ),
				'fixedPriceOptionsTitle'           => __( 'Quantity', 'woocommerce-product-addon' ),
				'fixedPriceQtyPlaceholder'         => __( 'Quantity e.g 1000', 'woocommerce-product-addon' ),
				'fixedPricePricePlaceholder'       => __( 'Fixed Price', 'woocommerce-product-addon' ),
				'selectQtyOptionsTitle'            => __( 'Add options', 'woocommerce-product-addon' ),
				'emojisOptionsTitle'               => __( 'Add colors', 'woocommerce-product-addon' ),
				'cropperViewportAddRow'            => __( 'Add viewport', 'woocommerce-product-addon' ),
				'cropperViewportLabel'             => __( 'Label', 'woocommerce-product-addon' ),
				'cropperViewportWidth'             => __( 'Width', 'woocommerce-product-addon' ),
				'cropperViewportHeight'            => __( 'Height', 'woocommerce-product-addon' ),
				'cropperViewportPrice'             => __( 'Price (optional)', 'woocommerce-product-addon' ),
				'quantityPairedAddRow'             => __( 'Add option', 'woocommerce-product-addon' ),
				'quantityPairedOptionPlaceholder'  => __( 'Option', 'woocommerce-product-addon' ),
				'quantityPairedPricePlaceholder'   => __( 'Price (if any)', 'woocommerce-product-addon' ),
				'quantityPairedWeightPlaceholder'  => __( 'Weight (if any)', 'woocommerce-product-addon' ),
				'quantityPairedDefaultPlaceholder' => __( 'Default qty', 'woocommerce-product-addon' ),
				'quantityPairedMinPlaceholder'     => __( 'Min qty', 'woocommerce-product-addon' ),
				'quantityPairedMaxPlaceholder'     => __( 'Max qty', 'woocommerce-product-addon' ),
				'quantityPairedStockPlaceholder'   => __( 'Stock', 'woocommerce-product-addon' ),
				'cancelFieldPicker'                => __( 'Cancel', 'woocommerce-product-addon' ),
				'backToFieldTypes'                 => __( 'Back to field types', 'woocommerce-product-addon' ),
				'condShow'                         => __( 'Show', 'woocommerce-product-addon' ),
				'condHide'                         => __( 'Hide', 'woocommerce-product-addon' ),
				'condAll'                          => __( 'All', 'woocommerce-product-addon' ),
				'condAny'                          => __( 'Any', 'woocommerce-product-addon' ),
				'condOnlyIf'                       => __( 'only if', 'woocommerce-product-addon' ),
				'condFollowingMatches'             => __( 'of the following matches', 'woocommerce-product-addon' ),
				'condRule'                         => __( 'Rule', 'woocommerce-product-addon' ),
				'condGroupValue'                   => __( 'Value Comparison', 'woocommerce-product-addon' ),
				'condGroupText'                    => __( 'Text Matching', 'woocommerce-product-addon' ),
				'condGroupNumeric'                 => __( 'Numeric Comparison', 'woocommerce-product-addon' ),
				'condOpIs'                         => __( 'is', 'woocommerce-product-addon' ),
				'condOpNot'                        => __( 'is not', 'woocommerce-product-addon' ),
				'condOpEmpty'                      => __( 'is empty', 'woocommerce-product-addon' ),
				'condOpAny'                        => __( 'has any value', 'woocommerce-product-addon' ),
				'condOpContains'                   => __( 'contains', 'woocommerce-product-addon' ),
				'condOpNotContains'                => __( 'does not contain', 'woocommerce-product-addon' ),
				'condOpRegex'                      => __( 'matches RegEx', 'woocommerce-product-addon' ),
				'condOpGreater'                    => __( 'greater than', 'woocommerce-product-addon' ),
				'condOpLess'                       => __( 'less than', 'woocommerce-product-addon' ),
				'condOpBetween'                    => __( 'is between', 'woocommerce-product-addon' ),
				'condOpMultiple'                   => __( 'is multiple of', 'woocommerce-product-addon' ),
				'condOpEven'                       => __( 'is even', 'woocommerce-product-addon' ),
				'condOpOdd'                        => __( 'is odd', 'woocommerce-product-addon' ),
				'condAnd'                          => __( 'and', 'woocommerce-product-addon' ),
				'condAddRule'                      => __( 'Add rule', 'woocommerce-product-addon' ),
				'condRemoveRule'                   => __( 'Remove rule', 'woocommerce-product-addon' ),
				'condTargetField'                  => __( 'Field', 'woocommerce-product-addon' ),
				'condOperator'                     => __( 'Operator', 'woocommerce-product-addon' ),
				'condValue'                        => __( 'Value', 'woocommerce-product-addon' ),
				'condSelectField'                  => __( 'Select a field…', 'woocommerce-product-addon' ),
				'condSelectValue'                  => __( 'Select value…', 'woocommerce-product-addon' ),
				'condShowHide'                     => __( 'Visibility', 'woocommerce-product-addon' ),
				'condAllAny'                       => __( 'Match mode', 'woocommerce-product-addon' ),
				'condEnableLogicHint'              => __( 'Turn on "Use conditional logic" above to apply these rules on the product page.', 'woocommerce-product-addon' ),
				'conditionUpgradeCta'              => __( 'Upgrade to Unlock', 'woocommerce-product-addon' ),
				'conditionUpgradeUrl'              => esc_url( tsdk_utmify( tsdk_translate_link( PPOM_UPGRADE_URL ), 'react-field-modal', 'condition' ) ),
				'imagesSelectUpload'               => __( 'Select/Upload Image', 'woocommerce-product-addon' ),
				'imagesMediaTitle'                 => __( 'Choose Images', 'woocommerce-product-addon' ),
				'imagesMediaButton'                => __( 'Select', 'woocommerce-product-addon' ),
				'imagesTitle'                      => __( 'Title', 'woocommerce-product-addon' ),
				'imagesPrice'                      => __( 'Price', 'woocommerce-product-addon' ),
				'imagesPricePlaceholder'           => __( 'Price (fix or %)', 'woocommerce-product-addon' ),
				'imagesStock'                      => __( 'Stock', 'woocommerce-product-addon' ),
				'imagesUrl'                        => __( 'URL', 'woocommerce-product-addon' ),
				'imagesRemove'                     => __( 'Remove', 'woocommerce-product-addon' ),
				'imagesMoveUp'                     => __( 'Move up', 'woocommerce-product-addon' ),
				'imagesMoveDown'                   => __( 'Move down', 'woocommerce-product-addon' ),
				'imagesEmptyState'                 => __( 'No images selected. Click the button above to add images.', 'woocommerce-product-addon' ),
				'addImagesSectionTitle'            => __( 'Images', 'woocommerce-product-addon' ),
				'editorSectionImageSettings'       => __( 'Image Settings', 'woocommerce-product-addon' ),
				'addAudioVideoSectionTitle'        => __( 'Audio / Video', 'woocommerce-product-addon' ),
				'audioMediaTitle'                  => __( 'Choose audio or video', 'woocommerce-product-addon' ),
				'audioMediaButton'                 => __( 'Select', 'woocommerce-product-addon' ),
				'audioSelectUpload'                => __( 'Select Audio/Video', 'woocommerce-product-addon' ),
				'audioEmptyState'                  => __( 'No audio or video selected. Use the button above to add files from the media library.', 'woocommerce-product-addon' ),
				'audioPricePlaceholder'            => __( 'Price (fix or %)', 'woocommerce-product-addon' ),
				'audioTitlePlaceholder'            => __( 'Title', 'woocommerce-product-addon' ),
				'audioMoveUp'                      => __( 'Move up', 'woocommerce-product-addon' ),
				'audioMoveDown'                    => __( 'Move down', 'woocommerce-product-addon' ),
				'audioRemove'                      => __( 'Remove', 'woocommerce-product-addon' ),
			),
		);

		/**
		 * Augment or replace boot payload for the React field modal.
		 *
		 * @param array<string, mixed> $payload        Boot payload.
		 * @param int                  $productmeta_id Group id or 0 for new.
		 *
		 * @phpstan-param BootPayload $payload
		 */
		$payload = apply_filters( 'ppom_admin_field_modal_boot_payload', $payload, $productmeta_id );

		return rest_ensure_response( $payload );
	}

	/**
	 * POST create field group.
	 *
	 * @param WP_REST_Request $request Request with JSON body: group, fields.
	 * @return WP_REST_Response|WP_Error
	 *
	 * @phpstan-return WP_REST_Response|WP_Error
	 */
	public function create_group( WP_REST_Request $request ) {
		$params = json_decode( $request->get_body(), true );
		if ( ! is_array( $params ) ) {
			return new WP_Error( 'ppom_invalid_json', __( 'Invalid JSON body.', 'woocommerce-product-addon' ), array( 'status' => 400 ) );
		}

		$group  = isset( $params['group'] ) && is_array( $params['group'] ) ? $params['group'] : array();
		$fields = isset( $params['fields'] ) && is_array( $params['fields'] ) ? array_values( $params['fields'] ) : array();

		$result = $this->persistence->create_group( $group, $fields );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return rest_ensure_response(
			array(
				'success'        => true,
				'message'        => $result['message'],
				'productmeta_id' => $result['productmeta_id'],
				'redirect_to'    => $result['redirect_to'],
				'fields'         => $result['fields'],
			)
		);
	}

	/**
	 * PUT update field group.
	 *
	 * @param WP_REST_Request $request Request with JSON body: group, fields; URL id param.
	 * @return WP_REST_Response|WP_Error
	 *
	 * @phpstan-return WP_REST_Response|WP_Error
	 */
	public function update_group( WP_REST_Request $request ) {
		$id     = (int) $request->get_param( 'id' );
		$params = json_decode( $request->get_body(), true );
		if ( ! is_array( $params ) ) {
			return new WP_Error( 'ppom_invalid_json', __( 'Invalid JSON body.', 'woocommerce-product-addon' ), array( 'status' => 400 ) );
		}

		$group  = isset( $params['group'] ) && is_array( $params['group'] ) ? $params['group'] : array();
		$fields = isset( $params['fields'] ) && is_array( $params['fields'] ) ? array_values( $params['fields'] ) : array();

		$result = $this->persistence->update_group( $id, $group, $fields );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return rest_ensure_response(
			array(
				'success'        => true,
				'message'        => $result['message'],
				'productmeta_id' => $result['productmeta_id'],
				'redirect_to'    => $result['redirect_to'],
				'fields'         => $result['fields'],
			)
		);
	}
}
