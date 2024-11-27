<?php
/*
** N-Media More Plugins Here...
*/

/*
**========== Direct access not allowed ===========
*/
if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not Allowed' );
}

$addons_list = ppom_array_get_addons_details();
?>
<style type="text/css">

	#wpcontent {
		padding-left: 0 !important;
		position: relative;
	}

	.ppom-admin-wrap {
		margin: 0 !important;
	}
</style>
<div class="ppom-admin-addons-wrapper">

	<div class="ppom-admin-addons-header">
		<div class="ppom-admin-addons-header-logo">
			<h3><?php _e( 'PPOM PRO Features', 'woocommerce-product-addon' ); ?></h3>
		</div>
	</div>

	<ul class="ppom_addons_model_cards" id="myUL">
			<?php
			foreach ( $addons_list as $meta ) {
				$types = [
					'field'=>esc_html__('Field', 'woocommerce-product-addon'),
					'feature'=>esc_html__('Feature', 'woocommerce-product-addon'),
				];

				$addon_title = isset( $meta['title'] ) ? $meta['title'] : '';
				$addon_desc  = isset( $meta['desc'] ) ? $meta['desc'] : '';
				$actions     = isset( $meta['actions'] ) ? $meta['actions'] : '';

				?>
				<li class="ppom_addons_model_cards_item">
					<div class="ppom_addons_model_card">
						<div class="ppom_addons_model_card_content">
							<div class="ppom_addons_model_heading_button_display">
								<h4 class="ppom_addons_model_card_title"><?php echo $addon_title; ?></h4>
								<?php if ( ! ppom_pro_is_installed() ) : ?>
									<span class="badge badge-secondary">
										<?php
										// translators: it marks if a feature is locked under a paid plan. 
										esc_html_e('PRO', 'woocommerce-product-addon');
										?>
									</span>
								<?php endif; ?>
							</div>
							<hr>
							<p class="ppom_addons_model_card_text"><?php echo $addon_desc; ?></p>
							<hr>
							<div class="ppom-admin-addons-actions">
								<?php if ( ! ppom_pro_is_installed() ) : ?>
									<a class="ppom_addons_model_card_btn" href="<?php echo tsdk_utmify( tsdk_translate_link( PPOM_UPGRADE_URL ), sanitize_key( $addon_title ), 'alladdonspage' ); ?>" target="_blank"><?php esc_html_e('Get Started', 'woocommerce-product-addon'); ?></a>
								<?php endif; ?>

								<?php
									foreach ( $actions as $action ) {
										$btn_title = isset( $action['title'] ) ? $action['title'] : '';
										$btn_link  = isset( $action['link'] ) ? $action['link'] : '';
										if ( $btn_link != '' ) {
										?>
											<a class="ppom_addons_model_card_btn" href="<?php echo esc_url( $btn_link ) ?>" target="_blank"><?php echo $btn_title; ?></a>
											<?php
										}
									}
								?>
							</div>
						</div>
					</div>
				</li>
			<?php
		}
		?>
	</ul>
</div>
<script>
	function myFunction() {
		var input, filter, ul, li, a, i, txtValue;
		input = document.getElementById("myInput");
		filter = input.value.toUpperCase();
		ul = document.getElementById("myUL");
		li = ul.getElementsByTagName("li");
		for (i = 0; i < li.length; i++) {
			a = li[i].getElementsByTagName("h4")[0];
			console.log(a);
			txtValue = a.textContent || a.innerText;

			if (txtValue.toUpperCase().indexOf(filter) > -1) {
				li[i].style.display = "";
			} else {
				li[i].style.display = "none";
			}
		}
	}
</script>
