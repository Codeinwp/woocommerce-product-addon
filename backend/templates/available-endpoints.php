<?php
/**
 * Available Endpoints Template.
 *
 * @package WooCommerce Product Addon
 **/

/**
 * ========== Block direct access ===========
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Title of the section.
 *
 * @var string $title
 */

$ppom_api_guide_url  = PPOM_URL . '/PPOM API Guide.pdf';
$ppom_rest_endpoints = array(
	'products' => array(
		'label' => __( 'PRODUCTS', 'woocommerce-product-addon' ),
		'items' => array(
			array(
				'method' => 'GET',
				'path'   => '/get/product/?product_id={id}',
				'desc'   => __( 'Get field groups for a product', 'woocommerce-product-addon' ),
				'auth'   => false,
			),
			array(
				'method' => 'GET',
				'path'   => '/get/id/{id}',
				'desc'   => __( 'Get a field group by numeric ID', 'woocommerce-product-addon' ),
				'auth'   => false,
			),
			array(
				'method' => 'POST',
				'path'   => '/set/product/',
				'desc'   => __( 'Save or update a field group', 'woocommerce-product-addon' ),
				'auth'   => true,
			),
			array(
				'method' => 'POST',
				'path'   => '/delete/product/',
				'desc'   => __( 'Delete a product field group', 'woocommerce-product-addon' ),
				'auth'   => true,
			),
		),
	),
	'orders'   => array(
		'label' => __( 'ORDERS', 'woocommerce-product-addon' ),
		'items' => array(
			array(
				'method' => 'GET',
				'path'   => '/get/order/?order_id={id}',
				'desc'   => __( 'Get order item meta', 'woocommerce-product-addon' ),
				'auth'   => false,
			),
			array(
				'method' => 'POST',
				'path'   => '/set/order/',
				'desc'   => __( 'Update order item meta', 'woocommerce-product-addon' ),
				'auth'   => true,
			),
			array(
				'method' => 'POST',
				'path'   => '/delete/order/',
				'desc'   => __( 'Delete order item meta', 'woocommerce-product-addon' ),
				'auth'   => true,
			),
		),
	),
);
?>

<tr class="ppom-available-endpoints-row">
	<th>
		<label><?php echo esc_html( $title ); ?></label>
		<?php if ( ! empty( $desc ) ) : ?>
			<span class="nmsf-field-desc"><?php echo esc_html( $desc ); ?></span>
		<?php endif; ?>
	</th>
	<td>
		<div class="ppom-endpoints-panel">
			<div class="ppom-endpoints-panel-header">
				<a href="<?php echo esc_url( $ppom_api_guide_url ); ?>" target="_blank" class="ppom-api-guide-link" rel="noopener noreferrer">
					<?php esc_html_e( 'Full API Guide', 'woocommerce-product-addon' ); ?>
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="13" height="13" role="img" aria-hidden="true" focusable="false" fill="currentColor"><path d="M18.2 17c0 .7-.6 1.2-1.2 1.2H7c-.7 0-1.2-.6-1.2-1.2V7c0-.7.6-1.2 1.2-1.2h3.2V4.2H7C5.5 4.2 4.2 5.5 4.2 7v10c0 1.5 1.2 2.8 2.8 2.8h10c1.5 0 2.8-1.2 2.8-2.8v-3.6h-1.5V17zM14.9 3v1.5h3.7l-6.4 6.4 1.1 1.1 6.4-6.4v3.7h1.5V3h-6.3z"></path></svg>
				</a>
			</div>
			<?php foreach ( $ppom_rest_endpoints as $endpoints ) : ?>
				<div class="ppom-endpoint-group">
					<span class="ppom-endpoint-group-label"><?php echo esc_html( $endpoints['label'] ); ?></span>
					<?php foreach ( $endpoints['items'] as $ep ) : ?>
						<div class="ppom-endpoint-row">
							<div class="ppom-endpoint-left">
								<span class="ppom-method-badge ppom-method-<?php echo esc_attr( strtolower( $ep['method'] ) ); ?>"><?php echo esc_html( $ep['method'] ); ?></span>
								<code class="ppom-endpoint-path"><?php echo esc_html( $ep['path'] ); ?></code>
							</div>
							<div class="ppom-endpoint-right">
								<span class="ppom-endpoint-desc"><?php echo esc_html( $ep['desc'] ); ?></span>
								<?php if ( $ep['auth'] ) : ?>
									<span class="ppom-endpoint-auth dashicons dashicons-lock" title="<?php esc_attr_e( 'Requires secret key', 'woocommerce-product-addon' ); ?>"></span>
								<?php endif; ?>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endforeach; ?>
		</div>
	</td>
</tr>
