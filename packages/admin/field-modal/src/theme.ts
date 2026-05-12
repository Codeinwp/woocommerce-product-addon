/**
 * Chakra theme tuned for wp-admin coexistence.
 */
import { createSystem, defaultConfig, defineConfig } from '@chakra-ui/react';

/** Below WP media modal (~160000); above wp-admin bar and typical UI. */
const PPOM_FIELD_MODAL_Z_INDEX = 159000;

export const fieldModalTheme = createSystem(
	defaultConfig,
	defineConfig( {
		globalCss: {
			/**
			 * Chakra Select / NativeSelect draws one chevron via its indicator slot;
			 * the underlying `<select>` arrow must stay hidden. wp-admin ships
			 * `.wp-core-ui select { background: #fff url(<chevron svg>) ... !important }`
			 * which paints a *second* chevron as a background image and ignores
			 * `appearance: none`. `!important` is required here to beat that rule.
			 * Rules live at the top level (not under `#ppom-field-modal-root`) because
			 * Chakra Dialog content is portaled to `document.body`, outside the root —
			 * a scoped selector would never match. Targeting Chakra-emitted slot
			 * classes keeps the override contained to this plugin's components.
			 */
			'.chakra-select, .chakra-native-select__field': {
				WebkitAppearance: 'none !important',
				MozAppearance: 'none !important',
				appearance: 'none !important',
				backgroundImage: 'none !important',
			},
			'.chakra-select::-ms-expand, .chakra-native-select__field::-ms-expand':
				{
					display: 'none',
				},
			/**
			 * Match wp-admin's native input border (`1px solid #8c8f94`) on the
			 * Chakra Textarea. wp-admin already paints inputs this way through
			 * `.wp-core-ui input` (specificity 0,1,1), so the Input control inherits
			 * it for free; the Textarea needs an explicit override at equal
			 * specificity (tying wp-admin and winning on source order). `!important`
			 * is defensive against future wp-admin rule changes.
			 */
			'textarea.chakra-textarea': {
				border: '1px solid #8c8f94 !important',
			},
			'textarea.chakra-textarea:focus, textarea.chakra-textarea:focus-visible':
				{
					borderColor: '#2271b1 !important',
					boxShadow: '0 0 0 1px #2271b1 !important',
				},
			'#ppom-field-modal-root': {
				fontSize: '13px',
				lineHeight: '1.4',
				/**
				 * wp-admin sets `#wpwrap a:hover/:focus { color: #135e96 }` (specificity 1,1,1),
				 * which would otherwise repaint the upsell CTA text blue. The ID-scoped class
				 * selectors below land at (1,2,1) and win without `!important`.
				 */
				'& .ppom-upsell-cta--primary, & .ppom-upsell-cta--primary:hover, & .ppom-upsell-cta--primary:focus, & .ppom-upsell-cta--primary:focus-visible, & .ppom-upsell-cta--primary:active':
					{
						color: '#fff',
					},
				'& .ppom-upsell-cta--secondary, & .ppom-upsell-cta--secondary:hover, & .ppom-upsell-cta--secondary:focus, & .ppom-upsell-cta--secondary:focus-visible, & .ppom-upsell-cta--secondary:active':
					{
						color: '#e65000',
					},
			},
		},

		theme: {
			tokens: {
				/**
				 * Must stay **below** the WordPress media modal (~160000 for `.media-modal` /
				 * `.media-modal-backdrop`); otherwise `wp.media()` opened from inside this modal
				 * is covered by our overlay. Still well above wp-admin bar (~99999) and typical UI.
				 */
				zIndices: {
					modal: {
						value: PPOM_FIELD_MODAL_Z_INDEX,
					},
					/**
					 * Chakra default tooltip z-index (1800) sits below our modal token
					 * (159000), so tile tooltips inside the field modal render behind the
					 * Dialog overlay. Bump above the modal so they portal on top.
					 */
					tooltip: {
						value: PPOM_FIELD_MODAL_Z_INDEX + 10,
					},
				},

				fonts: {
					body: {
						value: 'inherit',
					},
					heading: {
						value: 'inherit',
					},
				},

				// Align primary blue with wp-admin buttons (~#2271b1).
				colors: {
					blue: {
						500: {
							value: '#2271b1',
						},
						600: {
							value: '#135e96',
						},
						700: {
							value: '#0a4b78',
						},
					},
					// Pro-feature upsell accent (used by ProFeatureUpsellCard).
					orange: {
						50: {
							value: '#fffaf6',
						},
						100: {
							value: '#ffe3d1',
						},
						200: {
							value: '#ffd9bf',
						},
						500: {
							value: '#ff5a00',
						},
						600: {
							value: '#e65000',
						},
					},
				},
			},

			/**
			 * Chakra v3 uses `Dialog` + the `dialog` slot recipe. Legacy `components.Modal`
			 * overrides do not apply. `Dialog.Root variant="ppom"` maps to this recipe axis.
			 */
			slotRecipes: {
				dialog: {
					variants: {
						variant: {
							default: {},
							/**
							 * PPOM field modal: legacy .ppom-modal-box–inspired shell.
							 * Shadow softened vs 0 3px 9px rgba(0,0,0,0.5) for wp-admin context.
							 */
							ppom: {
								backdrop: {
									zIndex: PPOM_FIELD_MODAL_Z_INDEX,
								},
								positioner: {
									zIndex: PPOM_FIELD_MODAL_Z_INDEX,
								},
								content: {
									borderRadius: '8px',
									borderWidth: '1px',
									borderColor: 'rgba(0, 0, 0, 0.1)',
									overflow: 'hidden',
									boxShadow:
										'0 4px 16px rgba(0, 0, 0, 0.12), 0 0 1px rgba(0, 0, 0, 0.08)',
								},
								header: {
									borderBottomWidth: '1px',
									borderBottomColor: '#EFEFEF',
									fontWeight: '600',
									fontSize: '1.05rem',
									color: '#1d2327',
									pt: '0.75em',
									pb: '0.75em',
									px: '1em',
								},
								footer: {
									bg: '#FAFAFA',
									borderTopWidth: '1px',
									borderTopColor: '#EFEFEF',
									borderBottomRadius: '8px',
									/* Match .ppom-modal-box footer padding. */
									px: '1em',
									py: '1em',
								},
							},
						},
					},
					defaultVariants: {
						variant: 'default',
					},
				},
			},
		},
	} )
);
