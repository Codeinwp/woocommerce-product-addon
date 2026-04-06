<?php
/**
 * PPOM Onboarding Wizard / "Configure for me" flow
 * Accessible from the PPOM admin page via a button.
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not Allowed' );
}

class PPOM_Onboarding_Wizard {

	private static $ins;

	public static function get_instance() {
		is_null( self::$ins ) && self::$ins = new self();
		return self::$ins;
	}

	function __construct() {
		add_action( 'admin_footer', array( $this, 'render_wizard_modal' ) );
		add_action( 'wp_ajax_ppom_dismiss_wizard', array( $this, 'ajax_dismiss_wizard' ) );
		add_action( 'wp_ajax_ppom_reset_wizard', array( $this, 'ajax_reset_wizard' ) );
	}

	/**
	 * Dismiss the auto-open wizard.
	 */
	public function ajax_dismiss_wizard() {
		update_option( 'ppom_wizard_dismissed', true );
		wp_send_json_success();
	}

	/**
	 * Reset the wizard dismissed state (for testing).
	 */
	public function ajax_reset_wizard() {
		if ( ! ppom_security_role() ) {
			wp_send_json_error();
		}
		delete_option( 'ppom_wizard_dismissed' );
		wp_send_json_success( array( 'message' => 'Wizard reset. It will auto-open on next visit to PPOM page if no field groups exist.' ) );
	}

	/**
	 * Render the wizard modal in the admin footer.
	 */
	public function render_wizard_modal() {
		$screen = get_current_screen();
		if ( ! $screen || ! isset( $_GET['page'] ) || $_GET['page'] !== 'ppom' ) {
			return;
		}

		// Only show on the main listing page (not edit/new screens)
		$action  = isset( $_GET['action'] ) ? $_GET['action'] : '';
		$do_meta = isset( $_GET['do_meta'] ) ? $_GET['do_meta'] : '';
		$view    = isset( $_GET['view'] ) ? $_GET['view'] : '';
		if ( ! empty( $action ) || ! empty( $do_meta ) || ! empty( $view ) ) {
			return;
		}

		$is_pro     = ppom_pro_is_valid_license();
		$has_ai_key = PPOM_AI_Service::is_available();
		$ai_ready   = $is_pro && $has_ai_key;
		$templates    = PPOM_Template_Library::get_templates();
		$nonce        = wp_create_nonce( 'ppom_ai_nonce' );
		$upgrade_url  = tsdk_utmify( tsdk_translate_link( PPOM_UPGRADE_URL ), 'wizard', 'ppompage' );

		// Check if this is a first-time user (no field groups exist)
		$all_forms      = PPOM()->get_product_meta_all();
		$auto_open      = empty( $all_forms );
		$has_dismissed   = get_option( 'ppom_wizard_dismissed', false );
		?>
		<div id="ppom-wizard-modal" class="ppom-modal-box ppom-wizard-modal" style="display:none;">
			<header class="ppom-modal-header">
				<h3><?php _e( 'Set Up Product Fields', 'woocommerce-product-addon' ); ?></h3>
			</header>
			<div class="ppom-modal-body">
				<div class="ppom-wizard-steps">
					<!-- Step 1: Choose method -->
					<div class="ppom-wizard-step ppom-wizard-step-active" data-step="1">
						<?php if ( $auto_open && ! $has_dismissed ) : ?>
						<div class="ppom-wizard-welcome">
							<p><?php _e( 'Welcome to PPOM! Add custom product fields like dropdowns, text inputs, file uploads, and pricing options to your WooCommerce products.', 'woocommerce-product-addon' ); ?></p>
						</div>
						<?php endif; ?>
						<h4><?php _e( 'How would you like to get started?', 'woocommerce-product-addon' ); ?></h4>
						<p class="ppom-wizard-subtitle"><?php _e( 'Choose a starting point for your product customisation fields.', 'woocommerce-product-addon' ); ?></p>

						<div class="ppom-wizard-options">
							<button type="button" class="ppom-wizard-option" data-method="templates">
								<span class="dashicons dashicons-layout"></span>
								<strong><?php _e( 'Start from a Template', 'woocommerce-product-addon' ); ?></strong>
								<span><?php _e( 'Choose from ready-made field groups for common use cases', 'woocommerce-product-addon' ); ?></span>
							</button>

							<button type="button" class="ppom-wizard-option" data-method="ai">
								<span class="dashicons dashicons-welcome-learn-more"></span>
								<strong>
									<?php _e( 'Describe & Auto-Generate', 'woocommerce-product-addon' ); ?>
									<?php if ( ! $is_pro ) : ?>
										<span class="ppom-wizard-pro-badge">PRO</span>
									<?php endif; ?>
								</strong>
								<span><?php _e( 'Describe your product in plain language and AI will create the fields for you', 'woocommerce-product-addon' ); ?></span>
							</button>

							<button type="button" class="ppom-wizard-option" data-method="blank">
								<span class="dashicons dashicons-plus-alt2"></span>
								<strong><?php _e( 'Start from Scratch', 'woocommerce-product-addon' ); ?></strong>
								<span><?php _e( 'Create an empty field group and add fields manually', 'woocommerce-product-addon' ); ?></span>
							</button>
						</div>
					</div>

					<!-- Step 2a: Template selection -->
					<div class="ppom-wizard-step" data-step="2a">
						<div class="ppom-wizard-step-header">
							<button type="button" class="ppom-wizard-back">&larr; <?php _e( 'Back', 'woocommerce-product-addon' ); ?></button>
							<h4><?php _e( 'Choose a Template', 'woocommerce-product-addon' ); ?></h4>
						</div>
						<div class="ppom-wizard-templates-grid">
							<?php
							// Sort: free templates first, then Pro
							$free_templates = array();
							$pro_templates  = array();
							foreach ( $templates as $id => $template ) {
								if ( ! empty( $template['pro'] ) && ! $is_pro ) {
									$pro_templates[ $id ] = $template;
								} else {
									$free_templates[ $id ] = $template;
								}
							}
							$sorted_templates = $free_templates + $pro_templates;
							$shown_pro_divider = false;
							?>
							<?php foreach ( $sorted_templates as $id => $template ) :
								$tpl_is_pro    = ! empty( $template['pro'] );
								$tpl_locked    = $tpl_is_pro && ! $is_pro;
								$pro_features  = isset( $template['pro_features'] ) ? $template['pro_features'] : '';

								if ( $tpl_locked && ! $shown_pro_divider ) {
									$shown_pro_divider = true;
									?>
									<div class="ppom-wizard-templates-divider">
										<span><?php _e( 'Available with Pro', 'woocommerce-product-addon' ); ?></span>
									</div>
									<?php
								}
							?>
								<div class="ppom-wizard-template-card <?php echo $tpl_locked ? 'ppom-wizard-template-locked' : ''; ?>" data-template-id="<?php echo esc_attr( $id ); ?>" <?php echo $tpl_locked ? 'data-locked="1"' : ''; ?>>
									<div class="ppom-wizard-template-icon">
										<span class="dashicons <?php echo esc_attr( $template['icon'] ); ?>"></span>
									</div>
									<strong>
										<?php echo esc_html( $template['name'] ); ?>
										<?php if ( $tpl_locked ) : ?>
											<span class="ppom-wizard-pro-badge">PRO</span>
										<?php endif; ?>
									</strong>
									<p><?php echo esc_html( $template['description'] ); ?></p>
									<?php if ( $tpl_locked ) : ?>
										<span class="ppom-wizard-template-pro-note"><?php echo esc_html( $pro_features ); ?></span>
										<a href="<?php echo esc_url( $upgrade_url ); ?>" target="_blank" class="ppom-wizard-upgrade-link"><?php _e( 'Upgrade to Pro', 'woocommerce-product-addon' ); ?> &rarr;</a>
									<?php else : ?>
										<span class="ppom-wizard-field-count">
											<?php echo count( $template['fields'] ); ?> <?php _e( 'fields', 'woocommerce-product-addon' ); ?>
										</span>
									<?php endif; ?>
								</div>
							<?php endforeach; ?>
						</div>
					</div>

					<!-- Step 2b: AI description -->
					<div class="ppom-wizard-step" data-step="2b">
						<div class="ppom-wizard-step-header">
							<button type="button" class="ppom-wizard-back">&larr; <?php _e( 'Back', 'woocommerce-product-addon' ); ?></button>
							<h4><?php _e( 'Describe Your Product', 'woocommerce-product-addon' ); ?></h4>
						</div>
						<div class="ppom-wizard-ai-form">
							<?php if ( ! $is_pro ) : ?>
							<div style="background:#f0f6fc; border-left:4px solid #2271b1; padding:16px; margin-bottom:14px; border-radius:0 4px 4px 0; text-align:center;">
								<span class="dashicons dashicons-lock" style="font-size:24px; width:24px; height:24px; color:#2271b1; margin-bottom:6px;"></span><br>
								<strong style="font-size:14px;"><?php _e( 'Pro Feature', 'woocommerce-product-addon' ); ?></strong><br>
								<span style="font-size:13px; color:#646970;"><?php _e( 'Describe your product in plain language and AI will create the fields for you.', 'woocommerce-product-addon' ); ?></span><br><br>
								<a href="<?php echo esc_url( $upgrade_url ); ?>" target="_blank" class="btn btn-primary btn-sm"><?php _e( 'Upgrade to Pro', 'woocommerce-product-addon' ); ?></a>
							</div>
							<?php elseif ( ! $has_ai_key ) : ?>
							<div style="background:#fff8e5; border-left:4px solid #dba617; padding:12px 16px; margin-bottom:14px; border-radius:0 4px 4px 0;">
								<strong><?php _e( 'API key required', 'woocommerce-product-addon' ); ?></strong><br>
								<span style="font-size:13px;"><?php _e( 'To use AI features, add your OpenAI or Anthropic API key in', 'woocommerce-product-addon' ); ?> <a href="<?php echo esc_url( admin_url( 'admin.php?page=ppom&view=settings' ) ); ?>"><?php _e( 'PPOM Settings', 'woocommerce-product-addon' ); ?></a>.</span>
							</div>
							<?php endif; ?>
							<label for="ppom-wizard-ai-description"><?php _e( 'What product are you selling and what options should customers be able to choose? You can also describe how you want the fields to look.', 'woocommerce-product-addon' ); ?></label>
							<textarea id="ppom-wizard-ai-description" rows="5" placeholder="<?php echo esc_attr__( 'Describe your product, the options customers should see, and optionally the styling you want...', 'woocommerce-product-addon' ); ?>" <?php echo ! $ai_ready ? 'disabled' : ''; ?>></textarea>

							<div class="ppom-wizard-example-prompts" style="margin-top:8px;">
								<span style="font-size:12px; color:#646970; font-weight:500;"><?php _e( 'Try an example:', 'woocommerce-product-addon' ); ?></span>
								<div style="display:flex; flex-wrap:wrap; gap:4px; margin-top:4px;">
									<button type="button" class="ppom-wizard-example-prompt btn btn-sm" style="font-size:11px; padding:3px 8px; background:#f0f6fc; border:1px solid #c3c4c7; border-radius:12px; cursor:pointer; color:#2271b1;" data-prompt="I sell custom t-shirts. Customers should pick a size (S to 3XL, larger sizes cost more), choose a colour, enter personalisation text (max 30 characters, costs 5 extra), and optionally upload their own design. Style the form with a clean modern look — rounded inputs, light background, and clear labels."><?php _e( 'Custom T-Shirt', 'woocommerce-product-addon' ); ?></button>
									<button type="button" class="ppom-wizard-example-prompt btn btn-sm" style="font-size:11px; padding:3px 8px; background:#f0f6fc; border:1px solid #c3c4c7; border-radius:12px; cursor:pointer; color:#2271b1;" data-prompt="I run a pizza delivery shop. Customers choose a size (small, medium, large, family — each adds to price), crust type, and toppings (checkboxes, each has its own price). They also need to pick a delivery date and can add a gift message. Make the fields feel warm and inviting — soft shadows, slightly rounded, easy to scan."><?php _e( 'Pizza Builder', 'woocommerce-product-addon' ); ?></button>
									<button type="button" class="ppom-wizard-example-prompt btn btn-sm" style="font-size:11px; padding:3px 8px; background:#f0f6fc; border:1px solid #c3c4c7; border-radius:12px; cursor:pointer; color:#2271b1;" data-prompt="I sell jewellery. Customers choose metal type (silver, gold plated, 9ct gold, 18ct gold — each has a different price), ring size, and can optionally add engraving (max 25 characters, costs 8 extra) with a font choice. Offer a luxury gift box add-on. Style it elegantly — minimal, refined, with serif-style labels."><?php _e( 'Jewellery Engraving', 'woocommerce-product-addon' ); ?></button>
									<button type="button" class="ppom-wizard-example-prompt btn btn-sm" style="font-size:11px; padding:3px 8px; background:#f0f6fc; border:1px solid #c3c4c7; border-radius:12px; cursor:pointer; color:#2271b1;" data-prompt="I sell made-to-measure curtains. Customers enter width and height in centimetres, price is per square metre. They choose fabric type (cotton, linen, velvet, silk — each costs more), and can add blackout lining or thermal lining for a flat fee. Also offer a professional fitting service as a one-time charge. Keep the styling clean and professional."><?php _e( 'Made-to-Measure', 'woocommerce-product-addon' ); ?></button>
									<button type="button" class="ppom-wizard-example-prompt btn btn-sm" style="font-size:11px; padding:3px 8px; background:#f0f6fc; border:1px solid #c3c4c7; border-radius:12px; cursor:pointer; color:#2271b1;" data-prompt="I offer a cleaning service. Customers pick service type (standard, premium, VIP), preferred date, time slot (morning, afternoon, evening — evening costs extra), number of hours, and can tick a box for on-site service which shows an address field. Contact phone is required. No special styling needed."><?php _e( 'Service Booking', 'woocommerce-product-addon' ); ?></button>
								</div>
							</div>

							<button type="button" class="btn btn-primary ppom-wizard-ai-generate" id="ppom-wizard-generate-btn" style="margin-top:10px;" <?php echo ! $ai_ready ? 'disabled' : ''; ?>>
								<?php _e( 'Generate Fields', 'woocommerce-product-addon' ); ?>
							</button>
							<div class="ppom-wizard-ai-loading" style="display:none;">
								<span class="spinner is-active" style="float:none;"></span>
								<?php _e( 'AI is generating your fields...', 'woocommerce-product-addon' ); ?>
							</div>
							<div class="ppom-wizard-ai-error" style="display:none;"></div>
						</div>
					</div>

					<!-- Step 3: Review & Import -->
					<div class="ppom-wizard-step" data-step="3">
						<div class="ppom-wizard-step-header">
							<button type="button" class="ppom-wizard-back">&larr; <?php _e( 'Back', 'woocommerce-product-addon' ); ?></button>
							<h4><?php _e( 'Review & Import', 'woocommerce-product-addon' ); ?></h4>
						</div>
						<div class="ppom-wizard-review">
							<div class="ppom-wizard-group-name-wrap">
								<label for="ppom-wizard-group-name"><?php _e( 'Field Group Name:', 'woocommerce-product-addon' ); ?></label>
								<input type="text" id="ppom-wizard-group-name" class="form-control" placeholder="<?php echo esc_attr__( 'My Custom Fields', 'woocommerce-product-addon' ); ?>">
							</div>
							<div class="ppom-wizard-ai-explanation" style="display:none;">
								<div class="ppom-wizard-explanation-text"></div>
							</div>
							<div class="ppom-wizard-css-note" style="display:none; background:#f0f6fc; border-left:4px solid #2271b1; padding:8px 14px; margin-bottom:12px; border-radius:0 4px 4px 0; font-size:13px;">
								<span class="dashicons dashicons-art" style="font-size:14px; width:14px; height:14px; margin-right:4px; color:#2271b1;"></span>
								<?php _e( 'Custom styling will be applied automatically.', 'woocommerce-product-addon' ); ?>
							</div>
							<div class="ppom-wizard-fields-preview">
								<h5><?php _e( 'Fields to be created:', 'woocommerce-product-addon' ); ?></h5>
								<table class="table table-striped ppom-wizard-fields-table">
									<thead>
										<tr>
											<th><?php _e( 'Type', 'woocommerce-product-addon' ); ?></th>
											<th><?php _e( 'Title', 'woocommerce-product-addon' ); ?></th>
											<th><?php _e( 'Data Name', 'woocommerce-product-addon' ); ?></th>
											<th><?php _e( 'Required', 'woocommerce-product-addon' ); ?></th>
											<th><?php _e( 'Price', 'woocommerce-product-addon' ); ?></th>
										</tr>
									</thead>
									<tbody id="ppom-wizard-fields-tbody"></tbody>
								</table>
							</div>
						</div>
					</div>

					<!-- Step 4: Done -->
					<div class="ppom-wizard-step" data-step="4">
						<div class="ppom-wizard-done">
							<span class="dashicons dashicons-yes-alt ppom-wizard-done-icon"></span>
							<h4 id="ppom-wizard-done-message"><?php _e( 'Field group created!', 'woocommerce-product-addon' ); ?></h4>
							<p><?php _e( 'You can now edit the fields, attach it to products, and customise as needed.', 'woocommerce-product-addon' ); ?></p>
							<a id="ppom-wizard-edit-link" href="#" class="btn btn-primary">
								<?php _e( 'Edit Field Group', 'woocommerce-product-addon' ); ?>
							</a>
							<button type="button" class="btn btn-default ppom-js-modal-close" style="margin-left:8px;">
								<?php _e( 'Close', 'woocommerce-product-addon' ); ?>
							</button>
						</div>
					</div>
				</div>
			</div>
			<footer>
				<div class="ppom-wizard-footer-actions">
					<button type="button" class="btn btn-default ppom-js-modal-close"><?php _e( 'Cancel', 'woocommerce-product-addon' ); ?></button>
					<button type="button" class="btn btn-primary ppom-wizard-import-btn" style="display:none;" id="ppom-wizard-import-btn">
						<?php _e( 'Create Field Group', 'woocommerce-product-addon' ); ?>
					</button>
				</div>
			</footer>
		</div>

		<style>
			.ppom-wizard-modal {
				max-width: 780px;
			}
			.ppom-wizard-modal .ppom-modal-body {
				padding: 25px;
				max-height: 70vh;
				overflow-y: auto;
			}
			.ppom-wizard-step { display: none; }
			.ppom-wizard-step-active { display: block !important; }
			.ppom-wizard-welcome {
				background: #f0f6fc;
				border-left: 4px solid #2271b1;
				padding: 12px 16px;
				margin-bottom: 16px;
				border-radius: 0 4px 4px 0;
			}
			.ppom-wizard-welcome p {
				margin: 0;
				color: #1d2327;
			}
			.ppom-wizard-subtitle {
				color: #646970;
				margin: -5px 0 20px;
			}
			.ppom-wizard-options {
				display: flex;
				flex-direction: column;
				gap: 12px;
			}
			.ppom-wizard-option {
				display: flex;
				flex-direction: column;
				align-items: flex-start;
				gap: 4px;
				padding: 16px 20px;
				border: 2px solid #e0e0e0;
				border-radius: 8px;
				background: #fff;
				cursor: pointer;
				text-align: left;
				transition: all 0.15s;
			}
			.ppom-wizard-option:hover {
				border-color: #2271b1;
				background: #f0f6fc;
			}
			.ppom-wizard-option .dashicons {
				font-size: 24px;
				width: 24px;
				height: 24px;
				color: #2271b1;
				margin-bottom: 4px;
			}
			.ppom-wizard-option strong { font-size: 14px; }
			.ppom-wizard-option > span:last-child { color: #646970; font-size: 13px; }
			.ppom-wizard-option-disabled {
				opacity: 0.5;
				cursor: not-allowed !important;
				border-color: #e0e0e0 !important;
				background: #f6f7f7 !important;
			}
			.ppom-wizard-step-header {
				display: flex;
				align-items: center;
				gap: 12px;
				margin-bottom: 16px;
			}
			.ppom-wizard-step-header h4 { margin: 0; }
			.ppom-wizard-back {
				background: none;
				border: 1px solid #c3c4c7;
				border-radius: 4px;
				padding: 4px 10px;
				cursor: pointer;
				color: #50575e;
				font-size: 13px;
			}
			.ppom-wizard-back:hover { background: #f0f0f1; }

			/* Templates grid */
			.ppom-wizard-templates-grid {
				display: grid;
				grid-template-columns: repeat(2, 1fr);
				gap: 12px;
			}
			.ppom-wizard-template-card {
				border: 2px solid #e0e0e0;
				border-radius: 8px;
				padding: 16px;
				cursor: pointer;
				transition: all 0.15s;
			}
			.ppom-wizard-template-card:hover {
				border-color: #2271b1;
				background: #f0f6fc;
			}
			.ppom-wizard-template-icon .dashicons {
				font-size: 28px;
				width: 28px;
				height: 28px;
				color: #2271b1;
				margin-bottom: 8px;
			}
			.ppom-wizard-template-card strong { display: block; margin-bottom: 4px; font-size: 13px; }
			.ppom-wizard-template-card p { color: #646970; font-size: 12px; margin: 0 0 6px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
			.ppom-wizard-field-count {
				font-size: 11px;
				color: #8c8f94;
				background: #f0f0f1;
				padding: 2px 8px;
				border-radius: 10px;
			}
			.ppom-wizard-pro-badge {
				display: inline-block;
				background: #2271b1;
				color: #fff;
				font-size: 10px;
				font-weight: 600;
				padding: 1px 6px;
				border-radius: 3px;
				vertical-align: middle;
				margin-left: 4px;
				letter-spacing: 0.5px;
			}
			.ppom-wizard-template-locked {
				border-color: #e0e0e0 !important;
				cursor: default !important;
				position: relative;
				background: #f9f9f9 !important;
				opacity: 0.7;
			}
			.ppom-wizard-template-locked:hover {
				border-color: #c3c4c7 !important;
				background: #f6f7f7 !important;
				opacity: 1;
			}
			.ppom-wizard-template-locked .ppom-wizard-template-icon .dashicons {
				color: #a7aaad;
			}
			.ppom-wizard-template-locked strong {
				color: #646970;
			}
			.ppom-wizard-templates-divider {
				grid-column: 1 / -1;
				text-align: center;
				position: relative;
				margin: 4px 0;
			}
			.ppom-wizard-templates-divider span {
				background: #fff;
				padding: 0 12px;
				font-size: 11px;
				font-weight: 600;
				color: #8c8f94;
				text-transform: uppercase;
				letter-spacing: 0.5px;
				position: relative;
				z-index: 1;
			}
			.ppom-wizard-templates-divider::before {
				content: '';
				position: absolute;
				top: 50%;
				left: 0;
				right: 0;
				height: 1px;
				background: #e0e0e0;
			}
			.ppom-wizard-template-pro-note {
				font-size: 11px;
				color: #8c8f94;
				display: block;
				margin-bottom: 4px;
			}
			.ppom-wizard-upgrade-link {
				font-size: 12px;
				color: #2271b1;
				text-decoration: none;
				font-weight: 500;
			}
			.ppom-wizard-upgrade-link:hover {
				text-decoration: underline;
			}

			/* AI form */
			.ppom-wizard-ai-form label { display: block; margin-bottom: 8px; font-weight: 500; }
			.ppom-wizard-ai-form textarea {
				width: 100%;
				padding: 10px;
				border: 1px solid #8c8f94;
				border-radius: 4px;
				font-size: 13px;
				resize: vertical;
			}
			.ppom-wizard-ai-generate {
				margin-top: 12px;
				display: inline-flex !important;
				align-items: center;
				gap: 4px;
			}
			.ppom-wizard-ai-generate .dashicons { font-size: 16px; width: 16px; height: 16px; line-height: 16px; }
			.ppom-wizard-ai-loading { margin-top: 12px; color: #646970; }
			.ppom-wizard-ai-error { margin-top: 12px; color: #d63638; padding: 10px; background: #fcf0f1; border-radius: 4px; }

			/* Review */
			.ppom-wizard-group-name-wrap { margin-bottom: 16px; }
			.ppom-wizard-group-name-wrap label { display: block; margin-bottom: 4px; font-weight: 500; }
			.ppom-wizard-ai-explanation {
				background: #f0f6fc;
				border-left: 4px solid #2271b1;
				padding: 12px 16px;
				margin-bottom: 16px;
				border-radius: 0 4px 4px 0;
			}
			.ppom-wizard-fields-table { font-size: 13px; }

			/* Done */
			.ppom-wizard-done { text-align: center; padding: 30px 0; }
			.ppom-wizard-done-icon { font-size: 48px !important; width: 48px !important; height: 48px !important; color: #00a32a; margin-bottom: 12px; }
			.ppom-wizard-done h4 { font-size: 18px; margin: 8px 0; }

			.ppom-wizard-footer-actions { display: flex; justify-content: flex-end; gap: 8px; }
			.ppom-wizard-modal .btn {
				display: inline-block;
				padding: 6px 16px;
				font-size: 13px;
				font-weight: 500;
				line-height: 1.5;
				border-radius: 4px;
				border: 1px solid #c3c4c7;
				cursor: pointer;
				text-decoration: none;
				text-align: center;
			}
			.ppom-wizard-modal .btn-default {
				background: #f6f7f7;
				color: #50575e;
			}
			.ppom-wizard-modal .btn-default:hover { background: #e0e0e0; }
			.ppom-wizard-modal .btn-primary {
				background: #2271b1;
				border-color: #2271b1;
				color: #fff;
			}
			.ppom-wizard-modal .btn-primary:hover { background: #135e96; }
			.ppom-wizard-modal .btn-primary:disabled {
				background: #a7aaad;
				border-color: #a7aaad;
				cursor: not-allowed;
			}
		</style>

		<script>
		jQuery(function($) {
			var currentStep = '1';
			var selectedTemplate = null;
			var generatedFields = null;
			var generatedName = '';
			var generatedCSS = '';
			var nonce = '<?php echo esc_js( $nonce ); ?>';

			// Open wizard modal
			$(document).on('click', '.ppom-wizard-trigger', function(e) {
				e.preventDefault();
				resetWizard();
				$('body').append('<div class="ppom-modal-overlay ppom-js-modal-close"></div>');
				$('#ppom-wizard-modal').fadeIn();
			});

			<?php if ( $auto_open && ! $has_dismissed ) : ?>
			// Auto-open for first-time users
			setTimeout(function() {
				resetWizard();
				$('body').append('<div class="ppom-modal-overlay ppom-js-modal-close"></div>');
				$('#ppom-wizard-modal').fadeIn();
				// Mark as shown so it doesn't auto-open again
				$.post(ajaxurl, { action: 'ppom_dismiss_wizard' });
			}, 500);
			<?php endif; ?>

			function resetWizard() {
				currentStep = '1';
				selectedTemplate = null;
				generatedFields = null;
				generatedName = '';
				$('.ppom-wizard-step').removeClass('ppom-wizard-step-active');
				$('.ppom-wizard-step[data-step="1"]').addClass('ppom-wizard-step-active');
				$('#ppom-wizard-import-btn').hide();
				$('#ppom-wizard-ai-description').val('');
				$('.ppom-wizard-ai-error').hide().text('');
			}

			function goToStep(step) {
				$('.ppom-wizard-step').removeClass('ppom-wizard-step-active');
				$('.ppom-wizard-step[data-step="' + step + '"]').addClass('ppom-wizard-step-active');
				currentStep = step;

				$('#ppom-wizard-import-btn').toggle(step === '3');
			}

			// Step 1: Choose method
			$(document).on('click', '.ppom-wizard-option:not(.ppom-wizard-option-disabled)', function() {
				var method = $(this).data('method');
				if (method === 'templates') {
					goToStep('2a');
				} else if (method === 'ai') {
					goToStep('2b');
				} else if (method === 'blank') {
					$('.ppom-modal-overlay').remove();
					$('#ppom-wizard-modal').hide();
					window.location.href = '<?php echo esc_js( admin_url( 'admin.php?page=ppom&action=new' ) ); ?>';
				}
			});

			// Back button
			$(document).on('click', '.ppom-wizard-back', function() {
				if (currentStep === '3' && generatedFields && !selectedTemplate) {
					goToStep('2b');
				} else if (currentStep === '3') {
					goToStep('2a');
				} else {
					goToStep('1');
				}
			});

			// Step 2a: Select template
			$(document).on('click', '.ppom-wizard-template-card', function() {
				if ($(this).data('locked')) return; // Pro-locked
				var templateId = $(this).data('template-id');
				selectedTemplate = templateId;

				var templates = <?php echo wp_json_encode( $templates ); ?>;
				var tpl = templates[templateId];

				generatedFields = tpl.fields;
				generatedName = tpl.name;

				showReview(tpl.name, tpl.fields, null);
				goToStep('3');
			});

			// Step 2b: Example prompt click — fill textarea
			$(document).on('click', '.ppom-wizard-example-prompt', function(e) {
				e.preventDefault();
				$('#ppom-wizard-ai-description').val($(this).data('prompt')).focus();
			});

			// Step 2b: AI generate
			$(document).on('click', '#ppom-wizard-generate-btn', function() {
				var description = $('#ppom-wizard-ai-description').val().trim();
				if (!description) {
					$('.ppom-wizard-ai-error').text('<?php echo esc_js( __( 'Please describe your product and customisation needs.', 'woocommerce-product-addon' ) ); ?>').show();
					return;
				}

				$('.ppom-wizard-ai-error').hide();
				$('.ppom-wizard-ai-loading').show();
				$('#ppom-wizard-generate-btn').prop('disabled', true);

				$.post(ajaxurl, {
					action: 'ppom_ai_generate',
					nonce: nonce,
					description: description
				}, function(response) {
					$('.ppom-wizard-ai-loading').hide();
					$('#ppom-wizard-generate-btn').prop('disabled', false);

					if (response.success) {
						generatedFields = response.data.fields;
						generatedName = response.data.name || '';
						generatedCSS = response.data.css || '';
						selectedTemplate = null;

						showReview(generatedName, generatedFields, response.data.explanation || null);
						goToStep('3');
					} else {
						$('.ppom-wizard-ai-error').text(response.data || '<?php echo esc_js( __( 'Something went wrong. Please try again.', 'woocommerce-product-addon' ) ); ?>').show();
					}
				}).fail(function() {
					$('.ppom-wizard-ai-loading').hide();
					$('#ppom-wizard-generate-btn').prop('disabled', false);
					$('.ppom-wizard-ai-error').text('<?php echo esc_js( __( 'Request failed. Check your connection and try again.', 'woocommerce-product-addon' ) ); ?>').show();
				});
			});

			function showReview(name, fields, explanation) {
				$('#ppom-wizard-group-name').val(name);

				// Show explanation if AI-generated
				if (explanation) {
					$('.ppom-wizard-explanation-text').text(explanation);
					$('.ppom-wizard-ai-explanation').show();
				} else {
					$('.ppom-wizard-ai-explanation').hide();
				}
				// Show CSS note if styling was generated
				$('.ppom-wizard-css-note').toggle(!!generatedCSS);

				// Build fields preview table
				var tbody = '';
				$.each(fields, function(i, field) {
					var price = field.price || '';
					if (field.options && field.options.length) {
						var prices = [];
						$.each(field.options, function(j, opt) {
							if (opt.price) prices.push(opt.price);
						});
						if (prices.length) price = prices.join(', ');
					}
					tbody += '<tr>';
					tbody += '<td><code>' + (field.type || '') + '</code></td>';
					tbody += '<td>' + (field.title || '') + '</td>';
					tbody += '<td><code>' + (field.data_name || '') + '</code></td>';
					tbody += '<td>' + (field.required === 'on' ? '<?php echo esc_js( __( 'Yes', 'woocommerce-product-addon' ) ); ?>' : '<?php echo esc_js( __( 'No', 'woocommerce-product-addon' ) ); ?>') + '</td>';
					tbody += '<td>' + (price || '—') + '</td>';
					tbody += '</tr>';
				});
				$('#ppom-wizard-fields-tbody').html(tbody);
			}

			// Step 3: Import
			$(document).on('click', '#ppom-wizard-import-btn', function() {
				var $btn = $(this);
				$btn.prop('disabled', true).text('<?php echo esc_js( __( 'Creating...', 'woocommerce-product-addon' ) ); ?>');

				var postData = {
					action: 'ppom_import_template',
					nonce: nonce,
					group_name: $('#ppom-wizard-group-name').val()
				};

				if (selectedTemplate) {
					postData.template_id = selectedTemplate;
				} else {
					postData.fields = JSON.stringify(generatedFields);
					if (generatedCSS) {
						postData.css = generatedCSS;
					}
				}

				$.post(ajaxurl, postData, function(response) {
					$btn.prop('disabled', false).text('<?php echo esc_js( __( 'Create Field Group', 'woocommerce-product-addon' ) ); ?>');

					if (response.success) {
						$('#ppom-wizard-done-message').text(response.data.message);
						$('#ppom-wizard-edit-link').attr('href', response.data.edit_url);
						goToStep('4');
					} else {
						alert(response.data || '<?php echo esc_js( __( 'Import failed.', 'woocommerce-product-addon' ) ); ?>');
					}
				}).fail(function() {
					$btn.prop('disabled', false).text('<?php echo esc_js( __( 'Create Field Group', 'woocommerce-product-addon' ) ); ?>');
					alert('<?php echo esc_js( __( 'Request failed.', 'woocommerce-product-addon' ) ); ?>');
				});
			});
		});
		</script>
		<?php
	}
}

PPOM_Onboarding_Wizard::get_instance();
