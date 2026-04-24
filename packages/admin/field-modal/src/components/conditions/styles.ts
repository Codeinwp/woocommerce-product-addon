/**
 * Shared visual tokens for the conditional logic editor pieces.
 * Co-located here so RuleRow, ConditionsBoundControl, and RuleValueInput stay visually identical.
 */
export const controlSurface = {
	bg: 'white',
	borderColor: 'gray.200',
	borderRadius: 'md',
	_hover: { borderColor: 'gray.300' },
	_focus: {
		borderColor: 'blue.500',
		boxShadow: '0 0 0 1px #2271b1',
	},
};

export const labelProps = {
	fontSize: '13px',
	fontWeight: '600',
	color: 'gray.800',
	mb: 1,
};
