
<!-- Product Modal -->
<div id="ppom-product-modal" class="ppom-modal-box" style="display: none;">
	<form id="ppom-product-form">
		<input type="hidden" name="action" value="ppom_attach_ppoms"/>
		<input type="hidden" name="ppom_id" id="ppom_id">

		<header>
			<h3><?php _e( 'WooCommerce Products', 'woocommerce-product-addon' ); ?></h3>
		</header>

		<div class="ppom-modal-body">

		</div>

		<footer>
			<button type="button"
			        class="btn btn-default close-model ppom-js-modal-close"><?php _e( 'Close', 'woocommerce-product-addon' ); ?></button>
			<button type="submit" class="btn btn-success"><?php _e( 'Save', 'woocommerce-product-addon' ); ?></button>
		</footer>
	</form>
</div>