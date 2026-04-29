<?php
/**
 * PPOM Quick Setup template-library wizard.
 *
 * Renders a modal listing curated field-group templates. Free templates first,
 * then a divider, then Pro templates (greyed for users without an active Pro
 * license). Picking a template POSTs to wp_ajax_ppom_import_template and
 * redirects to the field-group editor on success.
 *
 * Styles: css/ppom-admin.css ("Quick Setup template-library wizard" section).
 * Behavior: js/admin/ppom-admin.js (PPOM_Template_Wizard IIFE).
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
