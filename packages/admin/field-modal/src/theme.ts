/**
 * Chakra theme tuned for wp-admin coexistence.
 */
import { extendTheme } from '@chakra-ui/react';

export const fieldModalTheme = extendTheme( {
	config: {
		cssVarPrefix: 'ppom-fm',
	},
	/**
	 * Must stay **below** the WordPress media modal (~160000 for `.media-modal` /
	 * `.media-modal-backdrop`); otherwise `wp.media()` opened from inside this modal
	 * is covered by our overlay. Still well above wp-admin bar (~99999) and typical UI.
	 */
	zIndices: {
		modal: 159000,
	},
	fonts: {
		body: 'inherit',
		heading: 'inherit',
	},
	// Align primary blue with wp-admin buttons (~#2271b1).
	colors: {
		blue: {
			500: '#2271b1',
			600: '#135e96',
			700: '#0a4b78',
		},
	},
	components: {
		Modal: {
			baseStyle: {
				overlay: { zIndex: 159000 },
				dialogContainer: { zIndex: 159000 },
				dialog: { borderRadius: 'md' },
			},
			variants: {
				/**
				 * PPOM field modal: legacy .ppom-modal-box–inspired shell.
				 * Shadow softened vs 0 3px 9px rgba(0,0,0,0.5) for wp-admin context.
				 */
				ppom: {
					dialog: {
						borderRadius: '8px',
						borderWidth: '1px',
						borderColor: 'rgba(0, 0, 0, 0.1)',
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
						/* Match .ppom-modal-box footer padding. */
						px: '1em',
						py: '1em',
					},
				},
			},
		},
		Tabs: {
			variants: {
				'soft-rounded': {
					tab: {
						fontWeight: '600',
					},
				},
			},
		},
	},
	styles: {
		global: {
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
	},
} );
