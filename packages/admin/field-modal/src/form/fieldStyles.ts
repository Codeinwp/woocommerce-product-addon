/**
 * Shared Chakra control styling aligned with schemaSettingControl.tsx
 */
export const ppomControlSurface = {
	bg: 'white',
	borderColor: 'gray.200',
	borderRadius: 'md',
	_hover: { borderColor: 'gray.300' },
	_focus: {
		borderColor: 'blue.500',
		boxShadow: '0 0 0 1px #2271b1',
	},
} as const;

export const ppomFieldLabelProps = {
	fontSize: '13px',
	fontWeight: '600',
	color: 'gray.800',
	mb: 0.5,
} as const;
