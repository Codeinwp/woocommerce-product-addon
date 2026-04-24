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
			'#ppom-field-modal-root': {
				fontSize: '13px',
				lineHeight: '1.4',
				/**
				 * Chakra Select draws one chevron via `.chakra-select__icon`; the native
				 * `<select>` arrow must stay hidden (`appearance: none`). wp-admin / other
				 * global styles often reset `appearance` on `select`, which stacks a second
				 * arrow (looks like a “double” chevron).
				 */
				'& .chakra-select': {
					WebkitAppearance: 'none',
					MozAppearance: 'none',
					appearance: 'none',
				},
				'& .chakra-select::-ms-expand': {
					display: 'none',
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
									/* Match .ppom-modal-box header (ppom-admin.css). */
									pt: '1.25em',
									pb: '1.25em',
									px: '1.5em',
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
