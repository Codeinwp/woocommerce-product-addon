<?php
/**
 * Coordinates PPOM admin pages, attach flows, and settings bridges.
 *
 * @package PPOM
 * @subpackage Admin
 */

/*
**========== Direct access not allowed ============
*/
if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not Allowed' );
}

/**
 * Coordinates the PPOM admin UI, settings flows, and field-group attachment workflows.
 *
 * Registers the PPOM menu pages, AJAX attach handlers, legacy WooCommerce
 * settings tab, and admin initialization hooks that prepare the PPOM schema.
 *
 * @phpstan-import-type PPOM_Meta_Group_Categories_Tags_Columns from PPOM_Meta_Repository
 * @phpstan-type PPOM_Attach_Select_Option array{value: string, selected: bool, label: string, disabled?: bool}
 * @phpstan-type PPOM_Attach_Select_Data array{options: list<PPOM_Attach_Select_Option>, is_used: bool}
 */
class NM_PersonalizedProduct_Admin extends NM_PersonalizedProduct {


	var $menu_pages, $plugin_scripts_admin, $plugin_settings;

	/**
	 * Plugin meta data.
	 *
	 * @var string $plugin_meta
	 */
	public $plugin_meta = array();

	/**
	 * Registers PPOM admin menus, AJAX handlers, and settings hooks.
	 *
	 * @return void
	 */
	public function __construct() {

		// setting plugin meta saved in config.php
		$this->plugin_meta = ppom_get_plugin_meta();

		// getting saved settings
		$this->plugin_settings = get_option( $this->plugin_meta['shortname'] . '_settings' );

		// populating $inputs with NM_Inputs object
		// $this -> inputs = self::get_all_inputs ();

		/*
		 * [1] TODO: change this for plugin admin pages
		*/
		add_action( 'init', array( $this, 'menu_page_options' ) );

		add_action(
			'admin_menu',
			array(
				$this,
				'add_menu_pages',
			)
		);

		add_action( 'init', array( 'NM_PersonalizedProduct', 'set_ppom_menu_permission' ) );

		// Getting products list
		add_action( 'wp_ajax_ppom_get_products', array( $this, 'get_products' ) );
		add_action( 'wp_ajax_ppom_search_products', array( $this, 'search_products' ) );
		add_action( 'wp_ajax_ppom_search_variations', array( $this, 'search_variations' ) );
		add_action( 'wp_ajax_ppom_search_categories', array( $this, 'search_categories' ) );
		add_action( 'wp_ajax_ppom_search_tags', array( $this, 'search_tags' ) );
		add_action( 'wp_ajax_ppom_attach_ppoms', array( $this, 'ppom_attach_ppoms' ) );

		// Adding setting tab in WooCommerce
		if ( ! ppom_settings_migrated() ) {
			add_filter( 'woocommerce_settings_tabs_array', __CLASS__ . '::add_settings_tab', 50 );
			// Display settings
			add_action( 'woocommerce_settings_tabs_ppom_settings', array( $this, 'settings_tab' ) );
			// Save settings
			add_action( 'woocommerce_update_options_ppom_settings', array( $this, 'save_settings' ) );
		}

		// adding wpml support for PPOM Settings
		add_filter( 'woocommerce_admin_settings_sanitize_option', array( $this, 'ppom_setting_wpml' ), 10, 3 );

		add_action( 'admin_head', array( $this, 'ppom_tabs_custom_style' ) );

		add_action(
			'woocommerce_admin_field_ppom_multi_select',
			array(
				$this,
				'ppom_multi_select_role_setting',
			),
			2,
			10
		);

		add_action( 'admin_init', array( $this, 'set_legacy_user' ) );
		add_action( 'admin_init', array( $this, 'ppom_create_db_tables' ) );
	}

	// Admin page registration.

	/**
	 * Menu page options.
	 */
	public function menu_page_options() {
		$this->menu_pages = array(
			array(
				'page_title'  => __( 'PPOM', 'woocommerce-product-addon' ),
				'menu_title'  => __( 'PPOM', 'woocommerce-product-addon' ),
				'cap'         => 'manage_options',
				'slug'        => 'ppom',
				'callback'    => 'product_meta',
				'parent_slug' => 'woocommerce',
			),
		);
	}

	/**
	 * Add upgrade to pro plugin action link.
	 *
	 * @param array  $actions Plugin actions.
	 * @param string $plugin_file Path to the plugin file relative to the plugins directory.
	 *
	 * @return array
	 */
	public function upgrade_to_pro_plugin_action( $actions, $plugin_file ) {
		if ( apply_filters( 'themeisle_sdk_is_black_friday_sale', false ) ) {
			return $actions;
		}

		if ( apply_filters( 'product_ppom_license_status', '' ) === 'valid' || apply_filters( 'product_ppom_license_status', '' ) === 'active_expired' ) {
			return $actions;
		}

		return array_merge(
			array(
				'upgrade_link' => '<a href="' . add_query_arg(
					array(
						'utm_source'   => 'wpadmin',
						'utm_medium'   => 'plugins',
						'utm_campaign' => 'rowaction',
					),
					tsdk_translate_link( PPOM_UPGRADE_URL )
				) . '" title="' . __( 'More Features', 'woocommerce-product-addon' ) . '"  target="_blank" rel="noopener noreferrer" style="color: #009E29; font-weight: 700;" onmouseover="this.style.color=\'#008a20\';" onmouseout="this.style.color=\'#009528\';" >' . __( 'Get Pro', 'woocommerce-product-addon' ) . '</a>',
			),
			$actions
		);
	}

	/**
	 * Registers the PPOM admin menu entries and page-load callbacks.
	 *
	 * @return string|void
	 */
	public function add_menu_pages() {

		if ( ! $this->menu_pages ) {
			return '';
		}

		foreach ( $this->menu_pages as $page ) {

			$cap = apply_filters( 'ppom_menu_capability', $page ['cap'] );

			if ( $page ['parent_slug'] == '' ) {

				$menu = add_options_page(
					__( 'PPOM Fields', 'woocommerce-product-addon' ),
					__( 'PPOM Fields', 'woocommerce-product-addon' ),
					$cap,
					$page ['slug'],
					array(
						$this,
						$page ['callback'],
					),
					$this->plugin_meta ['logo'],
					$this->plugin_meta ['menu_position']
				);
			} else {

				$menu = add_submenu_page(
					$page ['parent_slug'],
					$page ['page_title'],
					__( 'PPOM Fields', 'woocommerce-product-addon' ),
					$cap,
					$page ['slug'],
					array(
						$this,
						$page ['callback'],
					)
				);
			}

			if ( ! current_user_can( 'administrator' ) ) {
				$cap = 'ppom_options_page';
				// Menu page for roles set by PPOM Permission Settings
				$menu = add_menu_page(
					$page ['page_title'],
					__( 'PPOM Fields', 'woocommerce-product-addon' ),
					$cap,
					$page ['slug'],
					array(
						$this,
						$page ['callback'],
					)
				);
			}

			add_action( "load-$menu", array( $this, 'load_admin_menu' ) );
		}
	}

	// Field-group pages and attach UI.

	/**
	 * Renders the main PPOM field-group management screen.
	 *
	 * Routes between the field-group list, field editor, clone flow, addons
	 * listing, and changelog views based on the current admin request.
	 *
	 * @return void
	 *
	 * @see PPOM_Fields_Meta::render_field_settings()
	 * @see ppom_admin_save_form_meta()
	 * @see ppom_admin_update_form_meta()
	 */
	public function product_meta() {
		// hide this on PPOM page since is conflicting with floating widget.
		add_filter( 'update_footer', '__return_empty_string' );
		echo '<div id="ppom-pre-loading"></div>';

		echo '<div class="ppom-admin-wrap woocommerce ppom-wrapper" style="display:none">';

		$action            = ( isset( $_REQUEST ['action'] ) ? sanitize_text_field( $_REQUEST ['action'] ) : '' );
		$do_meta           = ( isset( $_REQUEST ['do_meta'] ) ? sanitize_text_field( $_REQUEST ['do_meta'] ) : '' );
		$view              = ( isset( $_REQUEST ['view'] ) ? sanitize_text_field( $_REQUEST ['view'] ) : '' );
		$ppom_settings_url = admin_url( 'admin.php?page=wc-settings&tab=ppom_settings' );
		$addons            = add_query_arg( array( 'view' => 'addons' ) );
		$changelog_url     = add_query_arg( array( 'view' => 'changelog' ) );

		if ( $action != 'new' && $do_meta != 'edit' && $do_meta != 'clone' && $view != 'addons' && $view != 'changelog' ) {
			?>
			<div class="ppom-manage-fields-topbar d-flex">
				<h1 class="ppom-heading-style"><?php esc_html_e( 'PPOM Field Groups', 'woocommerce-product-addon' ); ?></h1>
				<div class="ppom-top-nav">
					<a id="ppom-all-addons" class="mr-3" href="<?php echo esc_url( $addons ); ?>">+ <?php esc_html_e( 'All Addons', 'woocommerce-product-addon' ); ?></a>
					<a id="ppom-all-addons" class="mr-3" href="<?php echo esc_url( $changelog_url ); ?>"><?php esc_html_e( 'Changelog', 'woocommerce-product-addon' ); ?></a>
					<a  class="mr-3" href="<?php echo esc_url( admin_url( '/admin.php?page=ti-about-woocommerce_product_addon' ) ); ?>"><?php esc_html_e( 'About Us', 'woocommerce-product-addon' ); ?></a>
					<a href="<?php echo esc_url( $ppom_settings_url ); ?>"><?php esc_html_e( 'General Settings', 'woocommerce-product-addon' ); ?></a>
					<?php if ( ppom_pro_is_installed() && class_exists( 'PPOM_Pro\Addons\Texter\Texter' ) ) : ?>
						<a class="ml-3" href="<?php echo esc_url( add_query_arg( 'post_type', 'nm_ppom_texter', admin_url( 'edit.php' ) ) ); ?>"><?php esc_html_e( 'Manage Personalization Previews', 'woocommerce-product-addon' ); ?></a>
					<?php endif; ?>
				</div>
			</div>
			<div id="tsdk_banner" class="ppom-banner"></div>
			<?php
			echo '<p>' . __( 'You can create different meta groups for different products.', 'woocommerce-product-addon' ) . '</p>';
		}

		if ( ( isset( $_REQUEST ['productmeta_id'] ) && $_REQUEST ['do_meta'] == 'edit' ) || $action == 'new' ) {
			ppom_load_template( 'admin/ppom-fields.php' );
		} elseif ( isset( $_REQUEST ['do_meta'] ) && $_REQUEST ['do_meta'] == 'clone' ) {

			$this->clone_product_meta( intval( $_REQUEST ['productmeta_id'] ) );
		} elseif ( isset( $_REQUEST ['page'] ) && $_REQUEST ['page'] == 'ppom' && $view === 'addons' ) {

			ppom_load_template( 'admin/addons-list.php' );
		} elseif ( isset( $_REQUEST ['page'] ) && $_REQUEST ['page'] == 'ppom' && $view === 'changelog' ) {

			require_once PPOM_PATH . '/backend/changelog_handler.php';
			ppom_load_template( 'admin/changelog.php' );
		} else {
			do_action( 'ppom_pdf_setting_action' );
			do_action( 'ppom_enquiryform_setting_action' );


		}

		// existing meta group tables show only ppom main page
		if ( $action != 'new' && $do_meta != 'edit' && $view != 'addons' && $view != 'changelog' ) {
			ppom_load_template( 'admin/existing-meta.php' );

			// NOTE: Allow only for Tier 1 Plan or lower if license is present.
			$should_load_banner = NM_PersonalizedProduct::LICENSE_PLAN_1 >= NM_PersonalizedProduct::get_license_category( intval( apply_filters( 'product_ppom_license_plan', 0 ) ) );

			if ( $should_load_banner ) {
				do_action( 'themeisle_sdk_load_banner', 'ppom' );
			}       
		}

		echo '</div>';
	}

	/**
	 * Renders the attach-to-products popup content for a field group.
	 *
	 * Loads the product, category, and tag selectors used to attach or detach a
	 * PPOM field group from WooCommerce catalog objects.
	 *
	 * @return void
	 */
	public function get_products() {

		if ( ! ppom_security_role() ) {
			_e( 'Sorry, you are not allowed to perform this action', 'woocommerce-product-addon' );
			die( 0 );
		}

		if ( ! isset( $_GET['ppom_id'] ) ) {
			_e( 'Input Field is missing.', 'woocommerce-product-addon' );
			die( 0 );
		}

		$ppom_id             = intval( $_GET['ppom_id'] );
		$license_status      = apply_filters( 'product_ppom_license_status', '' );
		$current_saved_value = $this->get_db_field( $ppom_id );
		$pro_multiple_fields = ! ppom_pro_is_installed() || 'valid' !== $license_status
			? '</br><i style="font-size: 90%">' . sprintf(
				// translators: %1$s: the link to the store with label 'upgrade'.
				__( 'Your current plan supports adding one group of fields per product. To add multiple groups to the same product, please %1$s your plan!', 'woocommerce-product-addon' ),
				sprintf(
					'<a href="%1$s" target="_blank">%2$s</a>',
					esc_url( tsdk_utmify( tsdk_translate_link( PPOM_UPGRADE_URL ), 'multiple-fields' ) ),
					__( 'Upgrade to the Pro', 'woocommerce-product-addon' )
				)
			) . '</i>'
			: '';
		$select_products_id_component = ( new \PPOM\Attach\SelectComponent() )
			->set_id( 'attach-to-products' )
			->set_title( __( 'Display on Specific Products', 'woocommerce-product-addon' ) )
			->set_description( __( 'Select the product(s) where you want these product fields to appear.', 'woocommerce-product-addon' ) . $pro_multiple_fields )
			->set_select(
				array_merge(
					array(
						'label'               => __( 'Choose Products:', 'woocommerce-product-addon' ),
						'name'                => 'ppom-attach-to-products[]',
						'multiple'            => true,
						'is_used'             => true,
						'render_empty_option' => false,
					),
					$this->get_wc_products( $ppom_id )
				)
			);

		$attach_to_categories_component = ( new \PPOM\Attach\SelectComponent() )
			->set_id( 'attach-to-categories' )
			->set_title( __( 'Display in Specific Product Categories', 'woocommerce-product-addon' ) )
			->set_description( __( 'Select the product categories where you want these product fields to appear.', 'woocommerce-product-addon' ) )
			->set_select(
				array_merge(
					array(
						'label'               => __( 'Choose Categories:', 'woocommerce-product-addon' ),
						'name'                => 'ppom-attach-to-categories[]',
						'multiple'            => true,
						'render_empty_option' => false,
					),
					$this->get_wc_categories( $current_saved_value )
				)
			);


		$popup_components = array(
			new \PPOM\Attach\ContainerItem( $select_products_id_component->get_id(), $select_products_id_component ),
			new \PPOM\Attach\ContainerItem( $attach_to_categories_component->get_id(), $attach_to_categories_component ),
		);

		$attach_to_tags_component = ( new \PPOM\Attach\SelectComponent() )
			->set_status( $license_status )
			->set_id( 'attach-to-tags' )
			->set_title( __( 'Display in Specific Product Tags', 'woocommerce-product-addon' ) )
			->set_description( __( 'Select the product tags where you want these product fields to appear.', 'woocommerce-product-addon' ) )
			->set_select(
				array_merge(
					array(
						'label'               => __( 'Choose tags:', 'woocommerce-product-addon' ),
						'name'                => 'ppom-attach-to-tags[]',
						'multiple'            => true,
						'render_empty_option' => false,
					),
					$this->get_wc_tags( $current_saved_value )
				)
			);
		if ( 'valid' !== $license_status ) {
			$attach_to_tags_component = $attach_to_tags_component->set_status( 'invalid' );
		}
		$popup_components[] =
			new \PPOM\Attach\ContainerItem( $attach_to_tags_component->get_id(), $attach_to_tags_component );

		$select_variations_component = ( new \PPOM\Attach\SelectComponent() )
			->set_status( $license_status )
			->set_id( 'attach-to-variations' )
			->set_title( __( 'Display on Specific Variations', 'woocommerce-product-addon' ) )
			->set_description( __( 'Select the WooCommerce variations where these product fields should appear. Selecting a variation also attaches this group to its parent product.', 'woocommerce-product-addon' ) )
			->set_select(
				array_merge(
					array(
						'label'               => __( 'Choose Variations:', 'woocommerce-product-addon' ),
						'name'                => 'ppom-attach-to-variations[]',
						'multiple'            => true,
						'is_used'             => true,
						'render_empty_option' => false,
					),
					$this->get_wc_variations( $ppom_id )
				)
			);
		if ( 'valid' !== $license_status ) {
			$select_variations_component = $select_variations_component->set_status( 'invalid' );
		}
		$popup_components[] =
			new \PPOM\Attach\ContainerItem( $select_variations_component->get_id(), $select_variations_component );


		$popup_components = apply_filters( 'pppom_popup_components', $popup_components, $ppom_id );

		add_filter(
			'ppom_render_attach_popup',
			function ( $html_content ) use ( $popup_components ) {
				if ( empty( $popup_components ) || ! is_array( $popup_components ) ) {
					return $html_content;
				}

				foreach ( $popup_components as $component ) {
					if ( ! $component instanceof \PPOM\Attach\ContainerItem ) {
						continue;
					}
					$html_content .= $component->get_renderer()->render();
				}

				return $html_content;
			} 
		);

		ob_start();
		$vars = array(
			'ppom_id' => $ppom_id,
		);
		ppom_load_template( 'admin/products-list.php', $vars );

		$list_html = ob_get_clean();

		echo $list_html;

		die( 0 );
	}

	/**
	 * Returns paginated product matches for the attach modal Select2 control.
	 *
	 * @return void
	 */
	public function search_products() {
		$nonce = isset( $_GET['ppom_attached_nonce'] ) ? sanitize_text_field( wp_unslash( $_GET['ppom_attached_nonce'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, 'ppom_attached_nonce_action' ) || ! ppom_security_role() ) {
			wp_send_json_error(
				array(
					'message' => __( 'Sorry, you are not allowed to perform this action please try again', 'woocommerce-product-addon' ),
				),
				403
			);
		}

		$search_term = '';
		if ( isset( $_GET['q'] ) ) {
			$search_term = sanitize_text_field( wp_unslash( $_GET['q'] ) );
		} elseif ( isset( $_GET['term'] ) ) {
			$search_term = sanitize_text_field( wp_unslash( $_GET['term'] ) );
		}

		$page       = isset( $_GET['page'] ) ? max( 1, absint( $_GET['page'] ) ) : 1;
		$per_page   = 20;
		$query_args = array(
			'post_type'              => 'product',
			'post_status'            => 'publish',
			'posts_per_page'         => $per_page,
			'paged'                  => $page,
			'orderby'                => 'title',
			'order'                  => 'ASC',
			'fields'                 => 'ids',
			'no_found_rows'          => false,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		);

		if ( '' !== $search_term ) {
			$query_args['s'] = $search_term;
		}

		$query   = new WP_Query( $query_args );
		$results = array_map(
			function ( $product_id ) {
				$product_id = $product_id instanceof WP_Post ? (int) $product_id->ID : absint( $product_id );

				return array(
					'id'   => (string) $product_id,
					'text' => get_the_title( $product_id ),
				);
			},
			$query->posts
		);

		wp_send_json(
			array(
				'results'    => $results,
				'pagination' => array(
					'more' => $page < (int) $query->max_num_pages,
				),
			)
		);
	}

	/**
	 * Returns paginated variation matches for the attach modal Select2 control.
	 *
	 * @return void
	 */
	public function search_variations() {
		$nonce = isset( $_GET['ppom_attached_nonce'] ) ? sanitize_text_field( wp_unslash( $_GET['ppom_attached_nonce'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, 'ppom_attached_nonce_action' ) || ! ppom_security_role() ) {
			wp_send_json_error(
				array(
					'message' => __( 'Sorry, you are not allowed to perform this action please try again', 'woocommerce-product-addon' ),
				),
				403
			);
		}

		$search_term = '';
		if ( isset( $_GET['q'] ) ) {
			$search_term = sanitize_text_field( wp_unslash( $_GET['q'] ) );
		} elseif ( isset( $_GET['term'] ) ) {
			$search_term = sanitize_text_field( wp_unslash( $_GET['term'] ) );
		}

		$page     = isset( $_GET['page'] ) ? max( 1, absint( $_GET['page'] ) ) : 1;
		$per_page = 20;

		$query_args = array(
			'post_type'              => 'product_variation',
			'post_status'            => array( 'publish', 'private' ),
			'posts_per_page'         => $per_page,
			'paged'                  => $page,
			'orderby'                => 'title',
			'order'                  => 'ASC',
			'fields'                 => 'ids',
			'no_found_rows'          => false,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		);

		if ( '' !== $search_term ) {
			$query_args['s'] = $search_term;
		}

		$query         = new WP_Query( $query_args );
		$variation_ids = array_map(
			static function ( $post ) {
				return absint( $post );
			},
			$query->posts
		);

		$parent_products_query = null;
		if ( '' !== $search_term ) {
			$parent_products_query = new WP_Query(
				array(
					'post_type'              => 'product',
					'post_status'            => 'publish',
					'posts_per_page'         => $per_page,
					'paged'                  => $page,
					's'                      => $search_term,
					'fields'                 => 'ids',
					'no_found_rows'          => false,
					'update_post_meta_cache' => false,
					'update_post_term_cache' => false,
				)
			);

			if ( ! empty( $parent_products_query->posts ) ) {
				$parent_product_ids = array_map(
					static function ( $post ) {
						return absint( $post );
					},
					$parent_products_query->posts
				);
				$parent_variations  = get_posts(
					array(
						'post_type'              => 'product_variation',
						'post_status'            => array( 'publish', 'private' ),
						'post_parent__in'        => $parent_product_ids,
						'posts_per_page'         => $per_page,
						'fields'                 => 'ids',
						'no_found_rows'          => true,
						'update_post_meta_cache' => false,
						'update_post_term_cache' => false,
					)
				);
				$variation_ids      = array_merge( $variation_ids, array_map( 'absint', $parent_variations ) );
			}
		}

		$merged_unique = array_values( array_unique( array_filter( $variation_ids ) ) );
		$variation_ids = array_slice( $merged_unique, 0, $per_page );
		$results       = array();
		foreach ( $variation_ids as $variation_id ) {
			$results[] = array(
				'id'   => (string) $variation_id,
				'text' => self::get_variation_label( $variation_id ),
			);
		}

		$has_more = $page < (int) $query->max_num_pages
			|| ( $parent_products_query && $page < (int) $parent_products_query->max_num_pages )
			|| count( $merged_unique ) > $per_page;

		wp_send_json(
			array(
				'results'    => $results,
				'pagination' => array(
					'more' => $has_more,
				),
			)
		);
	}

	/**
	 * Returns product IDs currently attached to a PPOM field group.
	 *
	 * Supports both the current serialized-array storage and the older scalar
	 * storage used by {@see PPOM_PRODUCT_META_KEY}.
	 *
	 * @param int $ppom_id PPOM field-group ID.
	 * @return int[]
	 */
	public static function get_attached_product_ids( $ppom_id ) {
		$ppom_id = absint( $ppom_id );
		if ( 0 === $ppom_id ) {
			return array();
		}

		$ppom_id_string = (string) $ppom_id;
		$product_ids    = get_posts(
			array(
				'post_type'              => 'product',
				'post_status'            => 'publish',
				// Intentionally load all matched attachments, not the full catalog.
				// The modal must pre-render every currently attached product as a
				// selected option so Select2 shows the existing state correctly.
				'posts_per_page'         => -1,
				'fields'                 => 'ids',
				'orderby'                => 'title',
				'order'                  => 'ASC',
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Attachments are stored in post meta and this helper needs every selected product ID for modal preloading.
				'meta_query'             => array(
					'relation' => 'OR',
					array(
						'key'     => PPOM_PRODUCT_META_KEY,
						'value'   => $ppom_id_string,
						'compare' => '=',
					),
					array(
						'key'     => PPOM_PRODUCT_META_KEY,
						'value'   => sprintf( 'i:%d;', $ppom_id ),
						'compare' => 'LIKE',
					),
					array(
						'key'     => PPOM_PRODUCT_META_KEY,
						'value'   => sprintf( 's:%d:"%s";', strlen( $ppom_id_string ), $ppom_id_string ),
						'compare' => 'LIKE',
					),
				),
			)
		);

		$verified = array();
		foreach ( $product_ids as $candidate_id ) {
			$candidate_id  = absint( $candidate_id );
			$attached_meta = get_post_meta( $candidate_id, PPOM_PRODUCT_META_KEY, true );

			if ( is_array( $attached_meta ) && in_array( $ppom_id, array_map( 'absint', $attached_meta ), true ) ) {
				$verified[] = $candidate_id;
			} elseif ( is_numeric( $attached_meta ) && absint( $attached_meta ) === $ppom_id ) {
				$verified[] = $candidate_id;
			}
		}

		return array_values( array_unique( $verified ) );
	}

	/**
	 * Returns product selector options for the attach popup.
	 *
	 * Marks which products already reference the current PPOM ID through
	 * {@see PPOM_PRODUCT_META_KEY}.
	 *
	 * @param int $ppom_id PPOM field-group ID being attached.
	 *
	 * @return array
	 */
	public function get_wc_products( $ppom_id ) {
		$result = array(
			'options' => array(),
			'is_used' => true,
		);

		foreach ( self::get_attached_product_ids( $ppom_id ) as $product_id ) {
			$result['options'][] = array(
				'value'    => (string) $product_id,
				'selected' => true,
				'label'    => get_the_title( $product_id ),
			);
		}

		return $result;
	}

	/**
	 * Returns variation selector options for the attach popup.
	 *
	 * @param int $ppom_id PPOM field-group ID.
	 *
	 * @return array
	 * @phpstan-return PPOM_Attach_Select_Data
	 */
	public function get_wc_variations( $ppom_id ) {
		return self::get_wc_variations_static( $ppom_id );
	}

	/**
	 * Returns only the currently-attached variation IDs for a PPOM field group.
	 *
	 * @param int $ppom_id PPOM field-group ID.
	 * @return int[]
	 */
	public static function get_attached_variation_ids( $ppom_id ) {
		$ppom_id = absint( $ppom_id );
		if ( 0 === $ppom_id ) {
			return array();
		}

		// The rule map is stored as `[ ppom_group_id => [ variation_id, ... ] ]`,
		// so the requested group always serializes as `i:<ppom_id>;a:` (integer key
		// followed by the start of an array value). Inner array elements are always
		// integers, never arrays, so this pattern only matches top-level keys.
		$serialized_key_anchor = sprintf( 'i:%d;a:', $ppom_id );

		$product_ids = get_posts(
			array(
				'post_type'              => 'product',
				'post_status'            => 'publish',
				'posts_per_page'         => -1,
				'fields'                 => 'ids',
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Narrowed by serialized-key LIKE so we don't scan every product with any variation rule.
				'meta_query'             => array(
					array(
						'key'     => PPOM_VARIATION_META_KEY,
						'value'   => $serialized_key_anchor,
						'compare' => 'LIKE',
					),
				),
			)
		);

		$variation_ids = array();
		foreach ( $product_ids as $product_id ) {
			$variation_ids = array_merge(
				$variation_ids,
				\PPOM\Support\Helpers::get_variation_ids_for_group( $product_id, $ppom_id )
			);
		}

		return array_values( array_unique( array_filter( array_map( 'absint', $variation_ids ) ) ) );
	}

	/**
	 * Builds a readable variation label for Select2 options.
	 *
	 * @param int $variation_id Variation ID.
	 * @return string
	 */
	public static function get_variation_label( $variation_id ) {
		$variation = wc_get_product( $variation_id );
		if ( ! $variation || ! $variation->is_type( 'variation' ) ) {
			return get_the_title( $variation_id );
		}

		$parent = wc_get_product( $variation->get_parent_id() );
		$label  = $parent ? $parent->get_name() : get_the_title( $variation->get_parent_id() );

		$attributes = array();
		foreach ( $variation->get_attributes() as $attribute_name => $attribute_value ) {
			if ( '' === $attribute_value ) {
				continue;
			}

			$attribute_key = str_replace( 'attribute_', '', $attribute_name );
			$value_label   = $attribute_value;
			if ( taxonomy_exists( $attribute_key ) ) {
				$term = get_term_by( 'slug', $attribute_value, $attribute_key );
				if ( $term ) {
					$value_label = $term->name;
				}
			}

			$attributes[] = sprintf(
				'%1$s: %2$s',
				wc_attribute_label( $attribute_key, $parent ? $parent : null ),
				$value_label
			);
		}

		if ( ! empty( $attributes ) ) {
			$label .= ' - ' . implode( ', ', $attributes );
		}

		return sprintf( '#%1$d %2$s', absint( $variation_id ), $label );
	}

	/**
	 * Returns paginated category matches for the attach modal Select2 control.
	 *
	 * @return void
	 */
	public function search_categories() {
		$this->search_taxonomy_terms( 'product_cat' );
	}

	/**
	 * Returns paginated tag matches for the attach modal Select2 control.
	 *
	 * @return void
	 */
	public function search_tags() {
		$this->search_taxonomy_terms( 'product_tag' );
	}

	/**
	 * Shared AJAX handler for searching taxonomy terms with pagination.
	 *
	 * @param string $taxonomy The taxonomy to search.
	 * @return void
	 */
	private function search_taxonomy_terms( $taxonomy ) {
		$nonce = isset( $_GET['ppom_attached_nonce'] ) ? sanitize_text_field( wp_unslash( $_GET['ppom_attached_nonce'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, 'ppom_attached_nonce_action' ) || ! ppom_security_role() ) {
			wp_send_json_error(
				array(
					'message' => __( 'Sorry, you are not allowed to perform this action please try again', 'woocommerce-product-addon' ),
				),
				403
			);
		}

		$search_term = '';
		if ( isset( $_GET['q'] ) ) {
			$search_term = sanitize_text_field( wp_unslash( $_GET['q'] ) );
		} elseif ( isset( $_GET['term'] ) ) {
			$search_term = sanitize_text_field( wp_unslash( $_GET['term'] ) );
		}

		$page     = isset( $_GET['page'] ) ? max( 1, absint( $_GET['page'] ) ) : 1;
		$per_page = 20;
		$offset   = ( $page - 1 ) * $per_page;

		$term_args = array(
			'taxonomy'   => $taxonomy,
			'orderby'    => 'name',
			'order'      => 'ASC',
			'hide_empty' => false,
			'number'     => $per_page,
			'offset'     => $offset,
		);

		if ( '' !== $search_term ) {
			$term_args['search'] = $search_term;
		}

		$terms = get_terms( $term_args );

		if ( is_wp_error( $terms ) ) {
			$terms = array();
		}

		$results = array_map(
			function ( $term ) {
				return array(
					'id'   => $term->slug,
					'text' => $term->name,
				);
			},
			$terms
		);

		// Check if there are more results beyond this page.
		$count_args           = $term_args;
		$count_args['fields'] = 'count';
		unset( $count_args['number'], $count_args['offset'] );
		$total_count_result = get_terms( $count_args );
		$total_count        = is_wp_error( $total_count_result ) ? 0 : (int) $total_count_result;
		$has_more           = ( $offset + $per_page ) < $total_count;

		wp_send_json(
			array(
				'results'    => $results,
				'pagination' => array(
					'more' => $has_more,
				),
			)
		);
	}

	/**
	 * Returns only the currently-attached categories for a PPOM field group.
	 *
	 * @param array $current_values The current values containing product meta categories.
	 * @return array An array containing the pre-selected category options.
	 * @phpstan-param PPOM_Meta_Group_Categories_Tags_Columns $current_values
	 * @phpstan-return PPOM_Attach_Select_Data
	 */
	public function get_wc_categories( $current_values ) {
		$result = array(
			'options' => array(),
			'is_used' => false,
		);

		$used_slugs = array();
		if ( ! empty( $current_values['productmeta_categories'] ) ) {
			$used_slugs = preg_split( '/\r\n|\n/', $current_values['productmeta_categories'] );
			if ( false === $used_slugs ) {
				$used_slugs = array();
			}
			$used_slugs = array_filter( array_map( 'trim', $used_slugs ) );
		}

		if ( empty( $used_slugs ) ) {
			return $result;
		}

		$terms = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'slug'       => $used_slugs,
				'orderby'    => 'name',
				'order'      => 'ASC',
				'hide_empty' => false,
			)
		);

		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			return $result;
		}

		$result['is_used'] = true;
		foreach ( $terms as $term ) {
			$result['options'][] = array(
				'value'    => $term->slug,
				'selected' => true,
				'label'    => $term->name,
			);
		}

		return $result;
	}

	/**
	 * Returns only the currently-attached tags for a PPOM field group.
	 *
	 * @param array $current_values The current values to check against.
	 *
	 * @return array An array containing the pre-selected tag options.
	 * @phpstan-param PPOM_Meta_Group_Categories_Tags_Columns $current_values
	 * @phpstan-return PPOM_Attach_Select_Data
	 */
	public function get_wc_tags( $current_values ) {
		$result = array(
			'options' => array(),
			'is_used' => false,
		);

		$used_slugs = array();
		if ( ! empty( $current_values['productmeta_tags'] ) ) {
			$used_slugs = maybe_unserialize( $current_values['productmeta_tags'] );
			if ( ! is_array( $used_slugs ) ) {
				$used_slugs = array();
			}
			$used_slugs = array_filter( array_map( 'trim', $used_slugs ) );
		}

		if ( empty( $used_slugs ) ) {
			return $result;
		}

		$terms = get_terms(
			array(
				'taxonomy'   => 'product_tag',
				'slug'       => $used_slugs,
				'orderby'    => 'name',
				'order'      => 'ASC',
				'hide_empty' => false,
			)
		);

		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			return $result;
		}

		$result['is_used'] = true;
		foreach ( $terms as $term ) {
			$result['options'][] = array(
				'value'    => $term->slug,
				'selected' => true,
				'label'    => $term->name,
			);
		}

		return $result;
	}

	/**
	 * Retrieves the database fields for a given PPOM field ID.
	 *
	 * This function queries the database to fetch the `productmeta_categories` and `productmeta_tags`
	 * for the specified PPOM field ID from the PPOM meta table.
	 *
	 * @param int $ppom_field_id The ID of the PPOM field to retrieve data for.
	 * @return array
	 * @phpstan-return PPOM_Meta_Group_Categories_Tags_Columns
	 */
	public function get_db_field( $ppom_field_id ) {
		$rows = ppom_meta_repository()->get_categories_and_tags_columns( (int) $ppom_field_id );

		return ! empty( $rows ) ? $rows : array();
	}

	/**
	 * Renders the inline attach-to-products/categories/tags selects for the field group editor.
	 *
	 * Returns the same Select2 markup produced by get_products() but as a string
	 * suitable for embedding directly in the editor form instead of a modal.
	 *
	 * @param int $ppom_id PPOM field-group ID.
	 *
	 * @return string HTML markup for the inline selects.
	 *
	 * @see get_products()
	 */
	public static function render_inline_attach_selects( $ppom_id ) {
		$license_status      = apply_filters( 'product_ppom_license_status', '' );
		$current_saved_value = self::get_db_field_static( $ppom_id );
		$pro_multiple_fields = ! ppom_pro_is_installed() || 'valid' !== $license_status
			? '</br><i style="font-size: 90%">' . sprintf(
				// translators: %1$s: the link to the store with label 'upgrade'.
				__( 'Your current plan supports adding one group of fields per product. To add multiple groups to the same product, please %1$s your plan!', 'woocommerce-product-addon' ),
				sprintf(
					'<a href="%1$s" target="_blank">%2$s</a>',
					esc_url( tsdk_utmify( tsdk_translate_link( PPOM_UPGRADE_URL ), 'multiple-fields' ) ),
					__( 'Upgrade to the Pro', 'woocommerce-product-addon' )
				)
			) . '</i>'
			: '';

		$select_products_id_component = ( new \PPOM\Attach\SelectComponent() )
			->set_inline()
			->set_id( 'attach-to-products' )
			->set_title( __( 'Display on Specific Products', 'woocommerce-product-addon' ) )
			->set_description( __( 'Select the product(s) where you want these product fields to appear.', 'woocommerce-product-addon' ) . $pro_multiple_fields )
			->set_select(
				array_merge(
					array(
						'label'               => __( 'Choose Products:', 'woocommerce-product-addon' ),
						'name'                => 'ppom-attach-to-products[]',
						'multiple'            => true,
						'is_used'             => true,
						'render_empty_option' => false,
					),
					self::get_wc_products_static( $ppom_id )
				)
			);

		$attach_to_categories_component = ( new \PPOM\Attach\SelectComponent() )
			->set_inline()
			->set_id( 'attach-to-categories' )
			->set_title( __( 'Display in Specific Product Categories', 'woocommerce-product-addon' ) )
			->set_description( __( 'Select the product categories where you want these product fields to appear.', 'woocommerce-product-addon' ) )
			->set_select(
				array_merge(
					array(
						'label'               => __( 'Choose Categories:', 'woocommerce-product-addon' ),
						'name'                => 'ppom-attach-to-categories[]',
						'multiple'            => true,
						'render_empty_option' => false,
					),
					self::get_wc_categories_static( $current_saved_value )
				)
			);

		$popup_components = array(
			new \PPOM\Attach\ContainerItem( $select_products_id_component->get_id(), $select_products_id_component ),
			new \PPOM\Attach\ContainerItem( $attach_to_categories_component->get_id(), $attach_to_categories_component ),
		);

		$attach_to_tags_component = ( new \PPOM\Attach\SelectComponent() )
			->set_inline()
			->set_status( $license_status )
			->set_id( 'attach-to-tags' )
			->set_title( __( 'Display in Specific Product Tags', 'woocommerce-product-addon' ) )
			->set_description( __( 'Select the product tags where you want these product fields to appear.', 'woocommerce-product-addon' ) )
			->set_select(
				array_merge(
					array(
						'label'               => __( 'Choose tags:', 'woocommerce-product-addon' ),
						'name'                => 'ppom-attach-to-tags[]',
						'multiple'            => true,
						'render_empty_option' => false,
					),
					self::get_wc_tags_static( $current_saved_value )
				)
			);
		if ( 'valid' !== $license_status ) {
			$attach_to_tags_component = $attach_to_tags_component->set_status( 'invalid' );
		}
		$popup_components[] =
			new \PPOM\Attach\ContainerItem( $attach_to_tags_component->get_id(), $attach_to_tags_component );

		$select_variations_component = ( new \PPOM\Attach\SelectComponent() )
			->set_inline()
			->set_status( $license_status )
			->set_id( 'attach-to-variations' )
			->set_title( __( 'Display on Specific Variations', 'woocommerce-product-addon' ) )
			->set_description( __( 'Select the WooCommerce variations where these product fields should appear. Selecting a variation also attaches this group to its parent product.', 'woocommerce-product-addon' ) )
			->set_select(
				array_merge(
					array(
						'label'               => __( 'Choose Variations:', 'woocommerce-product-addon' ),
						'name'                => 'ppom-attach-to-variations[]',
						'multiple'            => true,
						'is_used'             => true,
						'render_empty_option' => false,
					),
					self::get_wc_variations_static( $ppom_id )
				)
			);
		if ( 'valid' !== $license_status ) {
			$select_variations_component = $select_variations_component->set_status( 'invalid' );
		}
		$popup_components[] =
			new \PPOM\Attach\ContainerItem( $select_variations_component->get_id(), $select_variations_component );

		$popup_components = apply_filters( 'pppom_popup_components', $popup_components, $ppom_id );

		$rendered = array();
		foreach ( $popup_components as $component ) {
			if ( ! $component instanceof \PPOM\Attach\ContainerItem ) {
				continue;
			}
			$rendered[] = $component->get_renderer()->render();
		}

		ob_start();
		wp_nonce_field( 'ppom_attached_nonce_action', 'ppom_attached_nonce' );
		$nonce_html = ob_get_clean();

		$html = '<div class="ppom-inline-attach-container" data-ppom-id="' . esc_attr( (string) $ppom_id ) . '">'
			. $nonce_html;

		$chunks = array_chunk( $rendered, 2 );
		foreach ( $chunks as $pair ) {
			$html .= '<div class="row">';
			foreach ( $pair as $item ) {
				$html .= '<div class="col-md-6 col-sm-12">' . $item . '</div>';
			}
			$html .= '</div>';
		}

		$html .= '<div class="ppom-inline-attach-notice" style="display:none; padding:6px 10px; margin-top:6px; border-left:4px solid #008c00; background:#f0f8f0;"></div>'
			. '</div>';

		return $html;
	}

	/**
	 * Static wrapper for get_db_field() to use in static contexts.
	 *
	 * @param int $ppom_field_id The ID of the PPOM field.
	 * @return array
	 * @phpstan-return PPOM_Meta_Group_Categories_Tags_Columns
	 */
	private static function get_db_field_static( $ppom_field_id ) {
		$rows = ppom_meta_repository()->get_categories_and_tags_columns( (int) $ppom_field_id );
		return ! empty( $rows ) ? $rows : array();
	}

	/**
	 * Static wrapper for get_wc_products() to use in static contexts.
	 *
	 * @param int $ppom_id PPOM field-group ID.
	 * @return array
	 * @phpstan-return PPOM_Attach_Select_Data
	 */
	private static function get_wc_products_static( $ppom_id ) {
		$result = array(
			'options' => array(),
			'is_used' => true,
		);

		foreach ( self::get_attached_product_ids( $ppom_id ) as $product_id ) {
			$result['options'][] = array(
				'value'    => (string) $product_id,
				'selected' => true,
				'label'    => get_the_title( $product_id ),
			);
		}

		return $result;
	}

	/**
	 * Static wrapper for get_wc_variations() to use in static contexts.
	 *
	 * @param int $ppom_id PPOM field-group ID.
	 * @return array
	 * @phpstan-return PPOM_Attach_Select_Data
	 */
	private static function get_wc_variations_static( $ppom_id ) {
		$result = array(
			'options' => array(),
			'is_used' => true,
		);

		foreach ( self::get_attached_variation_ids( $ppom_id ) as $variation_id ) {
			$result['options'][] = array(
				'value'    => (string) $variation_id,
				'selected' => true,
				'label'    => self::get_variation_label( $variation_id ),
			);
		}

		return $result;
	}

	/**
	 * Static wrapper for get_wc_categories() to use in static contexts.
	 *
	 * @param array $current_values The current saved values.
	 * @return array
	 * @phpstan-param PPOM_Meta_Group_Categories_Tags_Columns $current_values
	 * @phpstan-return PPOM_Attach_Select_Data
	 */
	private static function get_wc_categories_static( $current_values ) {
		$admin = new self();
		return $admin->get_wc_categories( $current_values );
	}

	/**
	 * Static wrapper for get_wc_tags() to use in static contexts.
	 *
	 * @param array $current_values The current saved values.
	 * @return array
	 * @phpstan-param PPOM_Meta_Group_Categories_Tags_Columns $current_values
	 * @phpstan-return PPOM_Attach_Select_Data
	 */
	private static function get_wc_tags_static( $current_values ) {
		$admin = new self();
		return $admin->get_wc_tags( $current_values );
	}

	/**
	 * Whether the current request contains inline attach selectors.
	 *
	 * @return bool
	 */
	public static function has_attach_selections_in_request() {
		return isset( $_POST['ppom_attached_nonce'] )
			|| isset( $_POST['ppom-attach-to-products-initial'] )
			|| isset( $_POST['ppom-attach-to-categories-initial'] )
			|| isset( $_POST['ppom-attach-to-tags-initial'] )
			|| isset( $_POST['ppom-attach-to-variations-initial'] );
	}

	/**
	 * Reconciles product, variation, category, and tag attachment selections from $_POST.
	 *
	 * @param int       $ppom_id     PPOM field-group ID.
	 * @param bool|null $is_pro_user Whether multi-group product attachment is allowed.
	 * @return array{products:int,categories:int,tags:int,variations:int}
	 */
	public static function save_attach_selections_from_request( $ppom_id, $is_pro_user = null ) {
		$ppom_id = absint( $ppom_id );
		if ( $ppom_id <= 0 ) {
			return array(
				'products'   => 0,
				'categories' => 0,
				'tags'       => 0,
				'variations' => 0,
			);
		}

		if ( null === $is_pro_user ) {
			$is_pro_user = 'valid' === apply_filters( 'product_ppom_license_status', '' );
		}

		$products_to_attach         = self::posted_absint_list( 'ppom-attach-to-products' );
		$products_to_attach_initial = self::posted_initial_absint_list( 'ppom-attach-to-products-initial' );
		$updated_products           = self::save_product_attachments( $ppom_id, $products_to_attach, $products_to_attach_initial, (bool) $is_pro_user );

		$variations_to_attach         = self::posted_absint_list( 'ppom-attach-to-variations' );
		$variations_to_attach_initial = self::posted_initial_absint_list( 'ppom-attach-to-variations-initial' );
		$variation_updates            = self::save_variation_attachments( $ppom_id, $variations_to_attach, $variations_to_attach_initial, (bool) $is_pro_user );

		$categories_to_attach = self::posted_slug_list( 'ppom-attach-to-categories' );
		$updated_cat          = count( $categories_to_attach );

		$tags_to_attach = self::posted_slug_list( 'ppom-attach-to-tags' );
		$updated_tags   = count( $tags_to_attach );

		self::save_categories_and_tags( $ppom_id, $categories_to_attach, $tags_to_attach );

		return array(
			'products'   => $updated_products + (int) $variation_updates['attached_products'],
			'categories' => $updated_cat,
			'tags'       => $updated_tags,
			'variations' => (int) $variation_updates['updated_variations'],
		);
	}

	/**
	 * Reconciles direct product attachments for a PPOM field group.
	 *
	 * @param int   $ppom_id          PPOM field-group ID.
	 * @param int[] $products         Submitted product IDs.
	 * @param int[] $initial_products Product IDs loaded when the form opened.
	 * @param bool  $is_pro_user      Whether multi-group product attachment is allowed.
	 * @return int Number of added/removed product assignments.
	 */
	private static function save_product_attachments( $ppom_id, array $products, array $initial_products, $is_pro_user ) {
		$products         = array_values( array_unique( array_filter( array_map( 'absint', $products ) ) ) );
		$initial_products = array_values( array_unique( array_filter( array_map( 'absint', $initial_products ) ) ) );

		$products_to_add = array_diff( $products, $initial_products );
		foreach ( $products_to_add as $product_to_add ) {
			self::attach_group_to_product( $product_to_add, $ppom_id, $is_pro_user );
		}

		$products_to_remove = array_diff( $initial_products, $products );
		foreach ( $products_to_remove as $product_to_remove ) {
			self::detach_group_from_product( $product_to_remove, $ppom_id );
		}

		return count( $products_to_add ) + count( $products_to_remove );
	}

	/**
	 * Stores variation restrictions and auto-attaches parent products.
	 *
	 * @param int   $ppom_id            PPOM field-group ID.
	 * @param int[] $variation_ids      Submitted variation IDs.
	 * @param int[] $initial_variations Variation IDs loaded when the form opened.
	 * @param bool  $is_pro_user        Whether multi-group product attachment is allowed.
	 * @return array{updated_variations:int,attached_products:int}
	 */
	public static function save_variation_attachments( $ppom_id, array $variation_ids, array $initial_variations = array(), $is_pro_user = false ) {
		$ppom_id            = absint( $ppom_id );
		$variation_ids      = array_values( array_unique( array_filter( array_map( 'absint', $variation_ids ) ) ) );
		$initial_variations = array_values( array_unique( array_filter( array_map( 'absint', $initial_variations ) ) ) );

		if ( $ppom_id <= 0 ) {
			return array(
				'updated_variations' => 0,
				'attached_products'  => 0,
			);
		}

		$current_by_parent = self::group_variations_by_parent_product( $variation_ids );
		$initial_by_parent = self::group_variations_by_parent_product( $initial_variations );
		$parent_ids        = array_values( array_unique( array_merge( array_keys( $current_by_parent ), array_keys( $initial_by_parent ) ) ) );

		foreach ( $parent_ids as $parent_id ) {
			$rules = \PPOM\Support\Helpers::get_variation_rule_map( $parent_id );
			if ( empty( $current_by_parent[ $parent_id ] ) ) {
				unset( $rules[ $ppom_id ] );
			} else {
				$rules[ $ppom_id ] = $current_by_parent[ $parent_id ];
			}
			\PPOM\Support\Helpers::update_variation_rule_map( $parent_id, $rules );
		}

		$attached_products = 0;
		foreach ( array_keys( $current_by_parent ) as $parent_id ) {
			if ( self::attach_group_to_product( $parent_id, $ppom_id, $is_pro_user ) ) {
				++$attached_products;
			}
		}

		return array(
			'updated_variations' => count( array_diff( $variation_ids, $initial_variations ) ) + count( array_diff( $initial_variations, $variation_ids ) ),
			'attached_products'  => $attached_products,
		);
	}

	/**
	 * Groups valid variation IDs by parent product.
	 *
	 * @param int[] $variation_ids Variation IDs.
	 * @return array<int, int[]>
	 */
	private static function group_variations_by_parent_product( array $variation_ids ) {
		$grouped = array();
		foreach ( $variation_ids as $variation_id ) {
			$variation = wc_get_product( $variation_id );
			if ( ! $variation || ! $variation->is_type( 'variation' ) ) {
				continue;
			}

			$parent_id = absint( $variation->get_parent_id() );
			if ( $parent_id <= 0 ) {
				continue;
			}

			if ( ! isset( $grouped[ $parent_id ] ) ) {
				$grouped[ $parent_id ] = array();
			}
			$grouped[ $parent_id ][] = absint( $variation_id );
		}

		foreach ( $grouped as $parent_id => $ids ) {
			$grouped[ $parent_id ] = array_values( array_unique( $ids ) );
		}

		return $grouped;
	}

	/**
	 * Attaches a PPOM group to a product using the existing product workflow.
	 *
	 * @param int  $product_id   Product ID.
	 * @param int  $ppom_id      PPOM field-group ID.
	 * @param bool $is_pro_user  Whether multi-group product attachment is allowed.
	 * @return bool True when a new attachment was added or the product was updated.
	 */
	private static function attach_group_to_product( $product_id, $ppom_id, $is_pro_user ) {
		$product_id = absint( $product_id );
		$ppom_id    = absint( $ppom_id );
		if ( $product_id <= 0 || $ppom_id <= 0 ) {
			return false;
		}

		$current_attached_fields = get_post_meta( $product_id, PPOM_PRODUCT_META_KEY, true );
		$already_attached        = is_array( $current_attached_fields )
			? in_array( $ppom_id, array_map( 'absint', $current_attached_fields ), true )
			: ( is_numeric( $current_attached_fields ) && absint( $current_attached_fields ) === $ppom_id );

		if ( $is_pro_user ) {
			if ( is_array( $current_attached_fields ) ) {
				$current_attached_fields[] = $ppom_id;
				$current_attached_fields   = array_unique( $current_attached_fields );
			} elseif ( is_numeric( $current_attached_fields ) ) {
				$current_attached_fields = array( absint( $current_attached_fields ), $ppom_id );
			} else {
				$current_attached_fields = array( $ppom_id );
			}

			$current_attached_fields = array_values( array_filter( array_map( 'absint', $current_attached_fields ) ) );
			update_post_meta( $product_id, PPOM_PRODUCT_META_KEY, $current_attached_fields );
		} else {
			update_post_meta( $product_id, PPOM_PRODUCT_META_KEY, array( $ppom_id ) );
		}

		return ! $already_attached;
	}

	/**
	 * Detaches a PPOM group from a product.
	 *
	 * @param int $product_id Product ID.
	 * @param int $ppom_id    PPOM field-group ID.
	 * @return void
	 */
	private static function detach_group_from_product( $product_id, $ppom_id ) {
		$should_delete           = true;
		$current_attached_fields = get_post_meta( $product_id, PPOM_PRODUCT_META_KEY, true );
		if ( is_array( $current_attached_fields ) ) {
			$key = array_search( absint( $ppom_id ), array_map( 'absint', $current_attached_fields ), true );
			if ( false !== $key ) {
				unset( $current_attached_fields[ $key ] );
				if ( ! empty( $current_attached_fields ) ) {
					$should_delete = false;
					update_post_meta( $product_id, PPOM_PRODUCT_META_KEY, array_values( $current_attached_fields ) );
				}
			}
		}

		if ( $should_delete ) {
			delete_post_meta( $product_id, PPOM_PRODUCT_META_KEY );
		}

		$variation_rules = \PPOM\Support\Helpers::get_variation_rule_map( $product_id );
		if ( isset( $variation_rules[ $ppom_id ] ) ) {
			unset( $variation_rules[ $ppom_id ] );
			\PPOM\Support\Helpers::update_variation_rule_map( $product_id, $variation_rules );
		}
	}

	/**
	 * Reads an absint array from $_POST.
	 *
	 * @param string $key Request key.
	 * @return int[]
	 */
	private static function posted_absint_list( $key ) {
		if ( ! isset( $_POST[ $key ] ) || ! is_array( $_POST[ $key ] ) ) {
			return array();
		}

		return array_values( array_unique( array_filter( array_map( 'absint', wp_unslash( $_POST[ $key ] ) ) ) ) );
	}

	/**
	 * Reads a comma-separated absint list from $_POST.
	 *
	 * @param string $key Request key.
	 * @return int[]
	 */
	private static function posted_initial_absint_list( $key ) {
		$value = isset( $_POST[ $key ] ) && is_string( $_POST[ $key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) : '';
		if ( '' === $value ) {
			return array();
		}

		return array_values( array_unique( array_filter( array_map( 'absint', explode( ',', $value ) ) ) ) );
	}

	/**
	 * Reads a sanitize_key array from $_POST.
	 *
	 * @param string $key Request key.
	 * @return string[]
	 */
	private static function posted_slug_list( $key ) {
		if ( ! isset( $_POST[ $key ] ) || ! is_array( $_POST[ $key ] ) ) {
			return array();
		}

		return array_values( array_unique( array_filter( array_map( 'sanitize_key', wp_unslash( $_POST[ $key ] ) ) ) ) );
	}

	/**
	 * AJAX handler for attaching products, categories, and tags to a PPOM field group.
	 *
	 * Reconciles the submitted attach selections against the stored
	 * {@see PPOM_PRODUCT_META_KEY} product assignments and then stores the
	 * category and tag rules on the PPOM custom-table row.
	 *
	 * Product edit screen: the same meta key is saved from the product metabox via
	 * ppom_admin_process_product_meta(); this handler does not call that function.
	 *
	 * @return void
	 *
	 * @see ppom_admin_process_product_meta()
	 */
	public function ppom_attach_ppoms() {
		$attached_nonce = isset( $_POST['ppom_attached_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['ppom_attached_nonce'] ) ) : '';
		if ( '' === $attached_nonce
			|| ! wp_verify_nonce( $attached_nonce, 'ppom_attached_nonce_action' )
			|| ! ppom_security_role()
		) {
			$response = array(
				'status'  => 'error',
				'message' => __( 'Sorry, you are not allowed to perform this action please try again', 'woocommerce-product-addon' ),
			);

			wp_send_json( $response );
		}

		$ppom_id = isset( $_POST['ppom_id'] ) ? absint( $_POST['ppom_id'] ) : 0;
		$counts  = self::save_attach_selections_from_request( $ppom_id );

		$response = array(
			'message' => sprintf(
				// translators: %1$d: number of products, %2$d: number of variations, %3$d: number of categories, %4$d: number of tags.
				__( 'PPOM updated for %1$d Products, %2$d Variations, %3$d Categories and %4$d Tags.', 'woocommerce-product-addon' ),
				$counts['products'],
				$counts['variations'],
				$counts['categories'],
				$counts['tags']
			),
			'status'  => 'success',
		);

		wp_send_json( $response );
	}

	/**
	 * Stores category and tag attachment rules on a PPOM field-group row.
	 *
	 * @param int         $ppom_id    PPOM field-group ID.
	 * @param array       $categories Category slugs attached to the field group.
	 * @param array|false $tags      Serialized tag slugs, an empty array to clear tags, or false to leave tags unchanged.
	 *
	 * @return void
	 */
	public static function save_categories_and_tags( $ppom_id, $categories, $tags ) {
		ppom_meta_repository()->save_categories_and_tags( (int) $ppom_id, $categories, $tags );
	}

	// Legacy settings bridge and setup.

	/**
	 * Renders the legacy PPOM plugin-validation screen.
	 *
	 * @return void
	 */
	public function validate_plugin() {

		echo '<div class="wrap">';
		echo '<h2>' . __( 'Provide API key below:', 'woocommerce-product-addon' ) . '</h2>';
		echo '<form onsubmit="return validate_api_wooproduct(this)">';
		echo '<p><label id="plugin_api_key">' . __( 'Enter API key', 'woocommerce-product-addon' ) . ':</label><br /><input type="text" name="plugin_api_key" id="plugin_api_key" /></p>';
		wp_nonce_field();
		echo '<p><input type="submit" class="button-primary button" name="plugin_api_key" /></p>';
		echo '<p id="nm-sending-api"></p>';
		echo '</form>';

		echo '</div>';
	}

	public static function add_settings_tab( $settings_tabs ) {

		if ( current_user_can( 'manage_options' ) ) {
			$settings_tabs['ppom_settings'] = __( 'PPOM Settings', 'woocommerce-product-addon' );
		}

		return $settings_tabs;
	}

	/**
	 * Renders the legacy WooCommerce settings tab for PPOM.
	 *
	 * @return void
	 *
	 * @see ppom_array_settings()
	 */
	public function settings_tab() {

		if ( current_user_can( 'manage_options' ) ) {
			woocommerce_admin_fields( ppom_array_settings() );
		}
	}

	/**
	 * Saves the legacy WooCommerce settings tab for PPOM.
	 *
	 * @return void
	 *
	 * @see ppom_array_settings()
	 */
	public function save_settings() {

		if ( current_user_can( 'manage_options' ) ) {
			woocommerce_update_options( ppom_array_settings() );
		}
	}

	/**
	 * Translates text settings through WPML during WooCommerce option saves.
	 *
	 * @param mixed $value Sanitized setting value.
	 * @param array $option WooCommerce setting definition.
	 * @param mixed $raw_value Raw submitted setting value.
	 *
	 * @return mixed
	 */
	public function ppom_setting_wpml( $value, $option, $raw_value ) {

		if ( isset( $option['type'] ) && 'text' === $option['type'] ) {
			$value = ppom_wpml_translate( $value, 'PPOM' );
		}

		return $value;
	}

	/**
	 * Prints admin CSS overrides used by the PPOM metabox and list screens.
	 *
	 * @return void
	 */
	public function ppom_tabs_custom_style() {
		?>
		<style>
			#woocommerce-product-data .ppom_extra_options_panel label {
				margin: 0 !important;
			}

			/* PPOM Meta in column */
			th.column-ppom_meta {
				width: 10% !important;
			}

			/* PPOM File Upload uploaded files display */
			td.ppom-files-display {
				display: flex;
				flex-direction: column;
				gap: 3px;
			}

			td.ppom-files-display a.button {
				text-align: center;
			}

			.ppom-settings-container {
				display: flex;
				flex-direction: column;
				gap: 15px;
				margin: 10px 15px;
			}

			.ppom-settings-container-item {
				display: flex;
				align-items: center;
				gap: 10px;
			}

			label.ppom-settings-container-item {
				width: 100%;
				max-width: 600px;
				margin: unset;
			}

			.ppom-settings-container .ppom-upsell-link {
				display: inline-flex;
				align-items: center;
				padding: 0.5rem 1rem;
				font-size: 0.875rem;
				font-weight: 500;
				color: #2563eb;
				background-color: #eff6ff;
				border: 1px solid #bfdbfe;
				border-radius: 0.375rem;
				text-decoration: none;
				transition: all 150ms ease-in-out;
			}

			.ppom-settings-container .ppom-upsell-link:hover {
				background-color: #dbeafe;
				color: #1d4ed8;
			}

			.ppom-settings-container .ppom-disabled-text {
				color: #8d8d8d;
			}
		</style>
		<?php
	}

	/**
	 * Renders the multi-select field used for PPOM role permissions.
	 *
	 * @param array $value WooCommerce setting definition.
	 *
	 * @return void
	 */
	public function ppom_multi_select_role_setting( $value ) {
		$selections = get_option( $value['id'] ) ? get_option( $value['id'] ) : 'administrator';


		if ( ! empty( $value['options'] ) ) {
			$selected_roles = $value['options'];
		} else {
			$selected_roles = array( 'administrator' => 'Administrator' );
		}

		asort( $selected_roles );


		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?> <span
							class="woocommerce-help-tip" data-tip="<?php echo $value['desc']; ?>"></span></label>
			</th>
			<td class="forminp">
				<select multiple="multiple" name="<?php echo esc_attr( $value['id'] ); ?>[]" style="width:350px"
						data-placeholder="<?php esc_attr_e( 'Choose Roles', 'woocommerce-product-addon' ); ?>"
						aria-label="<?php esc_attr_e( 'Roles', 'woocommerce-product-addon' ); ?>"
						class="wc-enhanced-select">
					<?php
					if ( ! empty( $selected_roles ) ) {
						foreach ( $selected_roles as $key => $val ) {
							echo '<option value="' . esc_attr( $key ) . '"' . wc_selected( $key, $selections ) . '>' . esc_html( $val ) . '</option>'; // WPCS: XSS ok.
						}
					}
					?>
				</select> <br/><a class="select_all button"
									href="#"><?php esc_html_e( 'Select all', 'woocommerce-product-addon' ); ?></a> <a
						class="select_none button"
						href="#"><?php esc_html_e( 'Select none', 'woocommerce-product-addon' ); ?></a>
			</td>
		</tr>
		<?php
	}

	/**
	 * Load admin menu page.
	 */
	public function load_admin_menu() {
		// Call survey class.
		include_once PPOM_PATH . '/classes/survey.class.php';
		PPOM_Survey::get_instance()->init();
	}


	/**
	 * Set legacy user flag.
	 */
	public function set_legacy_user() {
		if ( ! empty( get_option( 'ppom_legacy_user', '' ) ) ) {
			return;
		}

		$res = ppom_meta_repository()->get_rows_with_non_empty_style_or_js();
		update_option( 'ppom_legacy_user', ! empty( $res ) ? 'yes' : 'no' );
	}


	/**
	 * Create database tables.
	 */
	public function ppom_create_db_tables() {
		$used_db_version = get_option( 'personalizedproduct_db_version', false );

		if (
			! empty( $used_db_version ) &&
			is_string( $used_db_version ) &&
			version_compare( PPOM_DB_VERSION, $used_db_version, '<=' )
		) {
			return;
		}

		NM_PersonalizedProduct::activate_plugin();
	}
}
