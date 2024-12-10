<?php
/*
 * working behind the seen
 */

/*
**========== Direct access not allowed ============
*/
if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not Allowed' );
}

class NM_PersonalizedProduct_Admin extends NM_PersonalizedProduct {


	var $menu_pages, $plugin_scripts_admin, $plugin_settings;

	/**
	 * Plugin meta data.
	 *
	 * @var string $plugin_meta
	 */
	public $plugin_meta = array();

	function __construct() {

		// setting plugin meta saved in config.php
		$this->plugin_meta = ppom_get_plugin_meta();

		// getting saved settings
		$this->plugin_settings = get_option( $this->plugin_meta['shortname'] . '_settings' );

		// populating $inputs with NM_Inputs object
		// $this -> inputs = self::get_all_inputs ();

		/*
		 * [1] TODO: change this for plugin admin pages
		*/
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

	/**
	 * Add upgrade to pro plugin action link.
	 *
	 * @param array  $actions Plugin actions.
	 * @param string $plugin_file Path to the plugin file relative to the plugins directory.
	 *
	 * @return array
	 */
	public function upgrade_to_pro_plugin_action( $actions, $plugin_file ) {
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

	/*
	 * creating menu page for this plugin
	*/
	function add_menu_pages() {

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


	/*
	 * CALLBACKS
	*/
	function product_meta() {
		//hide this on PPOM page since is conflicting with floating widget.
		add_filter( 'update_footer', '__return_empty_string' );
		echo '<div id="ppom-pre-loading"></div>';

		echo '<div class="ppom-admin-wrap woocommerce ppom-wrapper" style="display:none">';

		$action  = ( isset( $_REQUEST ['action'] ) ? sanitize_text_field( $_REQUEST ['action'] ) : '' );
		$do_meta = ( isset( $_REQUEST ['do_meta'] ) ? sanitize_text_field( $_REQUEST ['do_meta'] ) : '' );
		$view    = ( isset( $_REQUEST ['view'] ) ? sanitize_text_field( $_REQUEST ['view'] ) : '' );
		$ppom_settings_url = admin_url( "admin.php?page=wc-settings&tab=ppom_settings" );
		$addons           = add_query_arg( array( 'view' => 'addons' ) );
		$changelog_url           = add_query_arg( array( 'view' => 'changelog' ) );

		if ( $action != 'new' && $do_meta != 'edit' && $do_meta != 'clone' && $view != 'addons' && $view != 'changelog' ) {
			?>
			<div class="ppom-manage-fields-topbar d-flex">
				<h1 class="ppom-heading-style"><?php esc_html_e('PPOM Field Groups', 'woocommerce-product-addon'); ?></h1>
				<div class="ppom-top-nav">
					<a id="ppom-all-addons" class="mr-3" href="<?php echo esc_url($addons); ?>">+ <?php esc_html_e( 'All Addons', 'woocommerce-product-addon' ); ?></a>
					<a id="ppom-all-addons" class="mr-3" href="<?php echo esc_url($changelog_url); ?>"><?php esc_html_e( 'Changelog', 'woocommerce-product-addon' ); ?></a>
                    <a  class="mr-3" href="<?php echo esc_url( admin_url( '/admin.php?page=ti-about-woocommerce_product_addon' ) ); ?>"><?php esc_html_e('About Us', 'woocommerce-product-addon'); ?></a>
					<a href="<?php echo esc_url($ppom_settings_url); ?>"><?php esc_html_e('General Settings', 'woocommerce-product-addon'); ?></a>
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

	/*
	 * Get Products
	*/
	function get_products() {

		if ( ! ppom_security_role() ) {
			_e( 'Sorry, you are not allowed to perform this action', 'woocommerce-product-addon' );
			die( 0 );
		}

		if ( ! isset( $_GET['ppom_id'] ) ) {
			_e( 'Input Field is missing.', 'woocommerce-product-addon' );
			die( 0 );
		}

		$ppom_id                      = intval( $_GET['ppom_id'] );
		$license_status               = apply_filters( 'product_ppom_license_status', '' );
		$current_saved_value          = $this->get_db_field( $ppom_id );
		$pro_multiple_fields          = ! ppom_pro_is_installed() || 'valid' !== $license_status
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
						'label'    => __( 'Choose Products:', 'woocommerce-product-addon' ),
						'name'     => 'ppom-attach-to-products[]',
						'multiple' => true,
						'is_used'  => true
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
						'label'    => __( 'Choose Categories:', 'woocommerce-product-addon' ),
						'name'     => 'ppom-attach-to-categories[]',
						'multiple' => true
					),
					$this->get_wc_categories( $current_saved_value )
				)
			);


		$popup_components = array(
			new \PPOM\Attach\ContainerItem( $select_products_id_component->get_id(), $select_products_id_component ),
			new \PPOM\Attach\ContainerItem( $attach_to_categories_component->get_id(), $attach_to_categories_component )
		);

		$tags = $this->get_wc_tags( $current_saved_value );
		if ( ! empty( $tags['options'] ) ) {
			$attach_to_tags_component = ( new \PPOM\Attach\SelectComponent() )
				->set_status( $license_status )
				->set_id( 'attach-to-tags' )
				->set_title( __( 'Display in Specific Product Tags', 'woocommerce-product-addon' ) )
				->set_description( __( 'Select the product tags where you want these product fields to appear.', 'woocommerce-product-addon' ) )
				->set_select(
					array_merge(
						array(
							'label'    => __( 'Choose tags:', 'woocommerce-product-addon' ),
							'name'     => 'ppom-attach-to-tags[]',
							'multiple' => true,
						),
						$this->get_wc_tags( $current_saved_value )
					)
				);
			if('valid' !== $license_status ){
				$attach_to_tags_component = $attach_to_tags_component->set_status( 'invalid' );
			}
			$popup_components[]       =
				new \PPOM\Attach\ContainerItem( $attach_to_tags_component->get_id(), $attach_to_tags_component );
		}


		$popup_components = apply_filters( 'pppom_popup_components', $popup_components, $ppom_id );

		add_filter( 'ppom_render_attach_popup', function ( $html_content ) use ( $popup_components ) {
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
		} );

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
	 * Retrieves WooCommerce products and checks if they are associated with a given PPOM ID.
	 *
	 * @param int $ppom_id The PPOM ID to check against the products.
	 * @return array An array containing the product options and a flag indicating if any product is used.
	 */
	function get_wc_products( $ppom_id ) {
		$result = array(
			'options' => array(),
			'is_used' => true
		);

		if ( 'valid' === apply_filters( 'product_ppom_license_status', '' ) ) {
			$result['options'][]= array(
				'value'    => '-1',
				'selected' => false,
				'label'    => __( 'Select a product', 'woocommerce-product-addon'),
				'disabled' => true
			);
		}

		$query = new WP_Query(
			array(
				'post_type'      => 'product',
				'posts_per_page' => -1, // Get all products
				'post_status'    => 'publish'
			)
		);

		if ( ! $query->have_posts() ) {
			return $result;
		}

		while ( $query->have_posts() ) {
			$query->the_post();

			$is_used       = false;
			$product_id    = get_the_ID();
			$attached_meta = get_post_meta( $product_id, PPOM_PRODUCT_META_KEY, true );

			if ( is_array( $attached_meta ) ) {
				$is_used = in_array( $ppom_id, $attached_meta );
			} else if ( is_numeric( $attached_meta ) ) {
				$is_used = $product_id === $attached_meta; // Note: Legacy format.
			}

			if ( $is_used ) {
				$result['is_used'] = true;
			}

			$result['options'][]= array(
				'value'    => $product_id,
				'selected' => $is_used,
				'label'    => get_the_title()
			);
		}

		wp_reset_postdata();
		return $result;
	}

	/**
	 * Retrieves WooCommerce product categories and checks if they are used in the current values.
	 *
	 * @param array $current_values The current values containing product meta categories.
	 * @return array An array containing the options of product categories and a flag indicating if any category is used.
	 */
	public function get_wc_categories( $current_values ) {
		$result = array(
			'options' => array(),
			'is_used' => false
		);

		$product_categories = get_terms(
			array(
				'taxonomy'   => 'product_cat', // WooCommerce product categories
				'orderby'    => 'name',
				'order'      => 'ASC',
				'hide_empty' => false, // Show all categories, even if empty
			)
		);

		// Check if there are categories
		if ( empty( $product_categories ) || is_wp_error( $product_categories ) ) {
			return $result;
		}

		$used_categories = array();
		if ( ! empty( $current_values['productmeta_categories'] ) ) {
			$used_categories = preg_split('/\r\n|\n/', $current_values['productmeta_categories'] );
		}

		foreach ( $product_categories as $category ) {
			$category_slug = $category->slug;
			$is_used = in_array( $category_slug, $used_categories );

			if( $is_used ) {
				$result['is_used'] = true;
			}

			$result['options'][]= array(
				'value'    => $category_slug,
				'selected' => $is_used,
				'label'    => $category->name
			);
		}

		return $result;
	}

	/**
	 * Retrieves WooCommerce product tags and checks if they are used in the current values.
	 *
	 * @param array $current_values The current values to check against.
	 *
	 * @return array An array containing the options of product tags and a flag indicating if any category is used.
	 */
	function get_wc_tags( $current_values ) {
		$result = array(
			'options' => array(),
			'is_used' => false
		);

		$product_tags = get_terms(
			array(
				'taxonomy'   => 'product_tag',
				'orderby'    => 'name',
				'order'      => 'ASC',
				'hide_empty' => false, // Show all tags, even if empty
			)
		);

		// Check if there are tags
		if ( empty( $product_tags ) || is_wp_error( $product_tags ) ) {
			return $result;
		}

		$used_tags = array();

		if ( ! empty( $current_values['productmeta_tags'] ) ) {
			$used_tags = maybe_unserialize( $current_values['productmeta_tags'] );
			if ( ! is_array( $used_tags ) ) {
				$used_tags = array();
			}
		}

		foreach ( $product_tags as $tag ) {
			$tag_slug = $tag->slug;
			$is_used = in_array( $tag_slug, $used_tags );

			if ( $is_used ) {
				$result['is_used'] = true;
			}

			$result['options'][]= array(
				'value'    => $tag_slug,
				'selected' => $is_used,
				'label'    => $tag->name
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
	 */
	function get_db_field( $ppom_field_id ) {
		global $wpdb;
		$ppom_table = $wpdb->prefix . PPOM_TABLE_META;
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT productmeta_categories, productmeta_tags FROM $ppom_table WHERE productmeta_id = %d",
				$ppom_field_id
			),
			ARRAY_A
		);

		return 0 < count( $rows ) && ! empty( $rows[0] ) ? $rows[0] : array();
	}

	/**
	 * Ajax handler for "Attach to Product" feature.
	 * Attaches products to the PPOM Field Group.
	 * Removes prodducts from the PPOM Field Group.
	 *
	 * @return void
	 */
	function ppom_attach_ppoms() {
		if ( ! isset( $_POST['ppom_attached_nonce'] )
			 || ! wp_verify_nonce( $_POST['ppom_attached_nonce'], 'ppom_attached_nonce_action' )
			 || ! ppom_security_role()
		) {
			$response = array(
				'status'  => 'error',
				'message' => __( 'Sorry, you are not allowed to perform this action please try again', 'woocommerce-product-addon' ),
			);

			wp_send_json( $response );
		}

		$ppom_id          = intval( $_POST['ppom_id'] );
		$is_pro_user      = 'valid' === apply_filters( 'product_ppom_license_status', '' );

		// +----- Attach Field to Product -----+
		$products_to_attach         = isset( $_POST['ppom-attach-to-products'] ) && is_array( $_POST['ppom-attach-to-products'] ) ? array_map( 'sanitize_key', $_POST['ppom-attach-to-products'] ) : array();
		$products_to_attach_initial = isset( $_POST['ppom-attach-to-products-initial'] ) && is_string( $_POST['ppom-attach-to-products-initial'] ) ? sanitize_text_field( $_POST['ppom-attach-to-products-initial'] ) : '';
		$products_to_attach_initial = array_filter( explode( ',' , $products_to_attach_initial ) );

		$products_to_add = array_diff( $products_to_attach, $products_to_attach_initial );
		foreach ( $products_to_add as $product_to_add ) {

			// NOTE: PRO users can add the field to multiple products.
			if ( $is_pro_user ) {
				$current_attached_fields = get_post_meta( $product_to_add, PPOM_PRODUCT_META_KEY, true );

				if ( is_array( $current_attached_fields ) ) {
					$current_attached_fields[]= $ppom_id;
					$current_attached_fields  = array_unique( $current_attached_fields );
				} else if ( is_numeric( $current_attached_fields ) ) {
					// NOTE: Backward compatibility.
					$current_attached_fields = array( $current_attached_fields, $ppom_id );
				} else {
					$current_attached_fields = array( $ppom_id );
				}

				$current_attached_fields = array_filter( $current_attached_fields, 'is_numeric' );
				update_post_meta( $product_to_add , PPOM_PRODUCT_META_KEY, $current_attached_fields );
			} else {
				update_post_meta( $product_to_add , PPOM_PRODUCT_META_KEY, array( $ppom_id ) );
			}
		}

		$products_to_remove = array_diff( $products_to_attach_initial, $products_to_attach );
		foreach ( $products_to_remove as $product_to_remove ) {
			$should_delete           = true;
			$current_attached_fields = get_post_meta( $product_to_remove, PPOM_PRODUCT_META_KEY, true );
			if ( is_array( $current_attached_fields ) ) {
				$key = array_search($ppom_id, $current_attached_fields);
				if ( false !== $key ) {
					unset( $current_attached_fields[$key] );
					if ( ! empty( $current_attached_fields ) ) {
						$should_delete = false;
						update_post_meta( $product_to_remove , PPOM_PRODUCT_META_KEY, $current_attached_fields );
					}
				}
			}

			if ( $should_delete ) {
				delete_post_meta( $product_to_remove, PPOM_PRODUCT_META_KEY );
			}
		}

		$updated_products = count( $products_to_add ) + count( $products_to_remove );

		// +----- Attach Field to Categories -----+
		$categories_to_attach = isset( $_POST['ppom-attach-to-categories'] ) && is_array( $_POST['ppom-attach-to-categories'] ) ? array_map( 'sanitize_key', $_POST['ppom-attach-to-categories'] ) : array();
		$updated_cat          = count( $categories_to_attach );

		// +----- Attach Field to Tags -----+
		$tags_to_attach = isset( $_POST['ppom-attach-to-tags'] ) && is_array( $_POST['ppom-attach-to-tags'] ) ? array_map( 'sanitize_key', $_POST['ppom-attach-to-tags'] ) : false;
		$updated_tags   = is_array( $tags_to_attach) ? count( $tags_to_attach ) : 0;

		self::save_categories_and_tags( $ppom_id, $categories_to_attach, $tags_to_attach );

		$response = array(
			'message' => sprintf(
				// translators: %1$d: number of products, %2$d: number of categories, %3$d: number of tags.
				__('PPOM updated for %1$d Products, %2$d Categories and %3$d Tags.', 'woocommerce-product-addon'),
				$updated_products,
				$updated_cat,
				$updated_tags
			),
			'status'  => 'success',
		);

		wp_send_json( $response );
	}

	/**
	 * Save the categories and tags to the given PPOM field.
	 *
	 * @param int        $ppom_id    The ID of the PPOM field.
	 * @param array      $categories An array of categories to save.
	 * @param array|bool $tags       An array of tags to save.
	 *
	 * @global wpdb $wpdb       WordPress database abstraction object.
	 *
	 */
	public static function save_categories_and_tags( $ppom_id, $categories, $tags ) {
		global $wpdb;
		$ppom_table = $wpdb->prefix . PPOM_TABLE_META;

		$data_to_update = array(
			'productmeta_categories' => implode( "\r\n", $categories ), // NOTE: Keep the backward compatible format.
		);

		if ( is_array( $tags ) ) {
			$data_to_update['productmeta_tags'] = serialize( $tags );
		}

		$wpdb->update(
			$ppom_table,
			$data_to_update,
			array( 'productmeta_id' => $ppom_id ), // Where clause
			array( '%s' ), // Data format
			array( '%d' )  // Where format
		);
	}

	/*
	 * Plugin Validation
	*/
	function validate_plugin() {

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
			$settings_tabs['ppom_settings'] = __('PPOM Settings', 'woocommerce-product-addon');
		}

		return $settings_tabs;
	}

	function settings_tab() {

		if ( current_user_can( 'manage_options' ) ) {
			woocommerce_admin_fields( ppom_array_settings() );
		}

	}

	function save_settings() {

		if ( current_user_can( 'manage_options' ) ) {
			woocommerce_update_options( ppom_array_settings() );
		}
	}

	function ppom_setting_wpml( $value, $option, $raw_value ) {

		if ( isset( $option['type'] ) && isset( $option['type'] ) == 'text' ) {
			$value = ppom_wpml_translate( $value, 'PPOM' );
		}

		return $value;
	}

	function ppom_tabs_custom_style() {
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

	function ppom_multi_select_role_setting( $value ) {
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

		global $wpdb;
		$ppom_meta_table = $wpdb->prefix . PPOM_TABLE_META;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$res = $wpdb->get_results( "SELECT * FROM `$ppom_meta_table` WHERE `productmeta_js` != '' OR `productmeta_style` != ''" );
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
