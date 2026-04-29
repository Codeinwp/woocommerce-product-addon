<?php
/**
 * PPOM Quick Setup template-library wizard.
 *
 * Renders a modal listing curated field-group templates. Free templates first,
 * then a divider, then Pro templates (greyed for users without an active Pro
 * license). Picking a template POSTs to wp_ajax_ppom_import_template and
 * redirects to the field-group editor on success.
 *
 * @package PPOM
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$plan_category    = NM_PersonalizedProduct::LICENSE_PLAN_FREE;
$is_pro_installed = function_exists( 'ppom_pro_is_installed' ) ? ppom_pro_is_installed() : false;
$license_data     = get_option( 'ppom_pro_license_data', array() );
$license_status   = $is_pro_installed && is_object( $license_data ) && ! empty( $license_data->license ) ? $license_data->license : 'invalid';

if ( $is_pro_installed && is_object( $license_data ) && isset( $license_data->plan ) && is_numeric( $license_data->plan ) ) {
	$plan_category = NM_PersonalizedProduct::get_license_category( intval( $license_data->plan ) );
}

$user_has_pro = ( 'valid' === $license_status ) && ( $plan_category >= NM_PersonalizedProduct::LICENSE_PLAN_1 );

$templates = PPOM_Template_Library::get_templates();

$free_templates = array();
$pro_templates  = array();

foreach ( $templates as $template ) {
	$plan = PPOM_Template_Library::derive_template_plan( $template );
	if ( NM_PersonalizedProduct::LICENSE_PLAN_FREE === $plan ) {
		$free_templates[] = $template;
	} else {
		$pro_templates[] = $template;
	}
}

$start_from_scratch_url = add_query_arg( array( 'action' => 'new' ) );
$upgrade_url            = tsdk_utmify( tsdk_translate_link( PPOM_UPGRADE_URL ), 'template-library', 'ppompage' );
?>

<div id="ppom-template-wizard-modal" class="ppom-modal-box ppom-template-wizard" style="display: none;" role="dialog" aria-modal="true" aria-labelledby="ppom-template-wizard-title">
	<header class="ppom-template-wizard-header">
		<h2 id="ppom-template-wizard-title" class="ppom-template-wizard-title">
			<?php esc_html_e( 'Set Up Product Fields', 'woocommerce-product-addon' ); ?>
		</h2>
	</header>

	<div class="ppom-modal-body ppom-template-wizard-body">

		<?php wp_nonce_field( 'ppom_import_template_action', 'ppom_import_template_nonce' ); ?>

		<div class="ppom-template-grid">
			<div class="ppom-template-card ppom-template-card--free ppom-template-card--scratch"
				data-href="<?php echo esc_url( $start_from_scratch_url ); ?>">
				<button type="button" class="ppom-template-card__inner">
					<div class="ppom-template-card__header">
						<i class="fa fa-plus ppom-template-card__icon" aria-hidden="true"></i>
					</div>
					<h3 class="ppom-template-card__title"><?php esc_html_e( 'Start from scratch', 'woocommerce-product-addon' ); ?></h3>
					<p class="ppom-template-card__description">
						<?php esc_html_e( 'Build your own field group with the fields you need.', 'woocommerce-product-addon' ); ?>
					</p>
				</button>
			</div>

			<?php foreach ( $free_templates as $template ) : ?>
				<div class="ppom-template-card ppom-template-card--free">
					<button type="button"
						class="ppom-template-card__inner ppom-template-tile"
						data-template="<?php echo esc_attr( $template['slug'] ); ?>">
						<div class="ppom-template-card__header">
							<i class="fa <?php echo esc_attr( $template['icon'] ); ?> ppom-template-card__icon" aria-hidden="true"></i>
						</div>
						<h3 class="ppom-template-card__title"><?php echo esc_html( $template['title'] ); ?></h3>
						<p class="ppom-template-card__description">
							<?php echo esc_html( $template['description'] ); ?>
						</p>
					</button>
				</div>
			<?php endforeach; ?>
		</div>

		<?php if ( ! empty( $pro_templates ) ) : ?>
			<div class="ppom-template-divider">
				<span class="ppom-template-divider__label">
					<?php esc_html_e( 'Pro templates', 'woocommerce-product-addon' ); ?>
				</span>
			</div>

			<div class="ppom-template-grid">
				<?php foreach ( $pro_templates as $template ) : ?>
					<?php $is_locked = ! $user_has_pro; ?>
					<div class="ppom-template-card ppom-template-card--pro <?php echo $is_locked ? 'ppom-template-card--locked' : ''; ?>">
						<button type="button"
							class="ppom-template-card__inner ppom-template-tile <?php echo $is_locked ? 'ppom-template-locked' : ''; ?>"
							data-template="<?php echo esc_attr( $template['slug'] ); ?>">
							<div class="ppom-template-card__header">
								<i class="fa <?php echo esc_attr( $template['icon'] ); ?> ppom-template-card__icon" aria-hidden="true"></i>
								<span class="ppom-template-card__pro-badge"><?php esc_html_e( 'PRO', 'woocommerce-product-addon' ); ?></span>
							</div>
							<h3 class="ppom-template-card__title"><?php echo esc_html( $template['title'] ); ?></h3>
							<p class="ppom-template-card__description">
								<?php echo esc_html( $template['description'] ); ?>
							</p>
						</button>
						<?php if ( $is_locked ) : ?>
							<div class="ppom-template-card__upgrade-row">
								<?php if ( ! empty( $template['uses_feature'] ) ) : ?>
									<p class="ppom-template-card__uses">
										<?php echo esc_html( $template['uses_feature'] ); ?>
									</p>
								<?php endif; ?>
								<a class="ppom-template-card__upgrade" target="_blank" rel="noopener" href="<?php echo esc_url( $upgrade_url ); ?>">
									<?php esc_html_e( 'Upgrade to Pro', 'woocommerce-product-addon' ); ?> <span aria-hidden="true">&rarr;</span>
								</a>
							</div>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

	</div>

	<footer class="ppom-template-wizard-footer">
		<button type="button" class="button ppom-js-modal-close">
			<?php esc_html_e( 'Cancel', 'woocommerce-product-addon' ); ?>
		</button>
	</footer>
</div>

<style>
.ppom-template-wizard {
	max-width: 880px;
	width: 90vw;
	max-height: 88vh;
	background: #fff;
	border-radius: 10px;
	overflow: hidden;
	display: flex;
	flex-direction: column;
}
.ppom-template-wizard-header {
	padding: 16px 20px 12px;
	border-bottom: 1px solid #e2e8f0;
}
.ppom-template-wizard-title {
	margin: 0;
	font-size: 17px;
	font-weight: 600;
	color: #0f172a;
	line-height: 1.3;
}
.ppom-modal-box.ppom-template-wizard .ppom-modal-body.ppom-template-wizard-body {
	padding: 1em 1.5em;
	overflow-y: auto;
	flex: 1 1 auto;
}
.ppom-template-grid {
	display: grid;
	grid-template-columns: repeat(2, minmax(0, 1fr));
	grid-auto-rows: 1fr;
	gap: 15px;
	margin: 0;
}
.ppom-template-wizard .ppom-template-card {
	display: flex;
	flex-direction: column;
	background: #fff;
	border: 1px solid #e2e8f0;
	border-radius: 8px;
	padding: 0;
	min-height: 120px;
	overflow: hidden;
	transition: border-color 0.15s ease, box-shadow 0.15s ease;
	box-shadow: none;
}
.ppom-template-wizard .ppom-template-card:is(:hover, :focus-within) {
	border-color: #2271b1;
	box-shadow: 0 1px 4px rgba(15, 23, 42, 0.06);
}
.ppom-template-card.is-busy {
	opacity: 0.6;
	pointer-events: none;
}
.ppom-template-card__inner {
	display: flex;
	flex-direction: column;
	background: transparent;
	border: 0;
	border-radius: 0;
	padding: 12px 14px;
	text-align: left;
	cursor: pointer;
	font: inherit;
	color: inherit;
	width: 100%;
	flex: 1 1 auto;
	box-shadow: none;
	-webkit-appearance: none;
	appearance: none;
}
.ppom-template-card__inner:focus {
	outline: none;
}
.ppom-template-card__header {
	display: flex;
	align-items: center;
	width: 100%;
	margin: 0 0 8px;
}
.ppom-template-card__icon {
	font-size: 18px;
	color: #2271b1;
	line-height: 1;
	flex: 0 0 auto;
}
.ppom-template-card__header .ppom-template-card__pro-badge {
	margin-left: auto;
	flex: 0 0 auto;
}
.ppom-template-wizard :is(h3.ppom-template-card__title, .ppom-template-card__title) {
	margin: 0 0 6px;
	font-size: 0.95rem;
	font-weight: 600;
	color: #0f172a;
	line-height: 1.3;
}
.ppom-template-wizard .ppom-template-card__description {
	margin: 0 0 0.5rem;
	font-size: 12px;
	line-height: 1.4;
	color: #475569;
}
.ppom-template-card--pro .ppom-template-card__icon {
	color: #94a3b8;
}
.ppom-template-wizard .ppom-template-card--scratch {
	background: #f8fafc;
	border-color: #cbd5e1;
}
.ppom-template-wizard .ppom-template-card--scratch:is(:hover, :focus-within) {
	background: #f1f5f9;
	border-color: #2271b1;
}
.ppom-template-card--locked {
	background: #f8fafc;
}
.ppom-template-wizard .ppom-template-card--locked .ppom-template-card__inner {
	cursor: not-allowed;
}
.ppom-template-wizard .ppom-template-card--locked:is(:hover, :focus-within) {
	border-color: #cbd5e1;
	box-shadow: none;
}
.ppom-template-wizard .ppom-template-card--locked .ppom-template-card__title {
	color: #475569;
}
.ppom-template-wizard .ppom-template-card--locked .ppom-template-card__description {
	color: #64748b;
}
.ppom-template-card__pro-badge {
	display: inline-flex;
	align-items: center;
	background: #2271b1;
	color: #fff;
	font-size: 9px;
	font-weight: 700;
	letter-spacing: 0.4px;
	padding: 1px 6px;
	border-radius: 3px;
	text-transform: uppercase;
	line-height: 1.4;
}
.ppom-template-card__upgrade-row {
	padding: 0 14px 10px;
}
.ppom-template-wizard .ppom-template-card__uses {
	margin: 0 0 6px;
	font-size: 11px;
	color: #94a3b8;
	font-style: italic;
	line-height: 1.4;
}
.ppom-template-card__upgrade {
	display: inline-block;
	margin: 0;
	font-size: 12px;
	font-weight: 600;
	color: #2271b1;
	text-decoration: none;
}
.ppom-template-card__upgrade:is(:hover, :focus) {
	color: #135e96;
	text-decoration: underline;
}
.ppom-template-divider {
	display: flex;
	align-items: center;
	margin: 14px 0 10px;
	color: #94a3b8;
}
.ppom-template-divider::before,
.ppom-template-divider::after {
	content: "";
	flex: 1;
	height: 1px;
	background: #e2e8f0;
}
.ppom-template-divider__label {
	padding: 0 10px;
	font-size: 10px;
	font-weight: 600;
	letter-spacing: 1px;
	text-transform: uppercase;
}
.ppom-template-wizard .ppom-template-wizard-footer {
	display: flex;
	justify-content: flex-end;
	align-items: center;
	gap: 12px;
	padding: 10px 20px;
	border-top: 1px solid #e2e8f0;
	background: #fff;
	text-align: right;
	box-shadow: none;
}
@media (max-width: 720px) {
	.ppom-template-grid {
		grid-template-columns: 1fr;
	}
	:is(.ppom-template-wizard-header, .ppom-template-wizard-body, .ppom-template-wizard-footer) {
		padding-left: 14px;
		padding-right: 14px;
	}
}
</style>

<script type="text/javascript">
( function ( $ ) {
	'use strict';

	var $modal = $( '#ppom-template-wizard-modal' );

	if ( ! $modal.length ) {
		return;
	}

	var defaultErrorMsg = '<?php echo esc_js( __( 'Could not import the template. Please try again.', 'woocommerce-product-addon' ) ); ?>';

	$modal.on( 'click', '.ppom-template-card--scratch', function () {
		var href = $( this ).data( 'href' );
		if ( href ) {
			window.location.href = href;
		}
	} );

	$modal.on( 'click', '.ppom-template-tile:not(.ppom-template-locked)', function ( e ) {
		e.preventDefault();

		var $btn  = $( this );
		var $card = $btn.closest( '.ppom-template-card' ).length ? $btn.closest( '.ppom-template-card' ) : $btn;
		var slug  = $btn.data( 'template' );
		var nonce = $modal.find( '[name="ppom_import_template_nonce"]' ).val();

		if ( ! slug || $card.hasClass( 'is-busy' ) ) {
			return;
		}

		$card.addClass( 'is-busy' );

		$.post( ajaxurl, {
			action: 'ppom_import_template',
			template: slug,
			ppom_import_template_nonce: nonce,
		} ).done( function ( response ) {
			if ( response && 'success' === response.status && response.redirect_to ) {
				window.location.href = response.redirect_to;
				return;
			}
			$card.removeClass( 'is-busy' );
			window.alert( response && response.message ? response.message : defaultErrorMsg );
		} ).fail( function () {
			$card.removeClass( 'is-busy' );
			window.alert( defaultErrorMsg );
		} );
	} );
} )( jQuery );
</script>
