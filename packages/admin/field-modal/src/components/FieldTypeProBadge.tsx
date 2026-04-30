/**
 * Compact PRO badge for locked field type tiles.
 */
import { Box } from '@chakra-ui/react';

export interface FieldTypeProBadgeProps {
	label: string;
}

export function FieldTypeProBadge( { label }: FieldTypeProBadgeProps ) {
	return (
		<Box
			as="span"
			className="upsell-btn-wrapper"
			aria-hidden
			/**
			 * Modal content is portaled outside `#ppom-field-modal-root`, so
			 * theme global `.upsell-btn-wrapper` rules do not apply. Keep
			 * styles here to match the compact classic modal chip.
			 */
			css={ {
				position: 'absolute',
				top: 0,
				right: 0,
				zIndex: 2,
				background: '#28A745',
				p: '2px 5px',
				borderRadius: '3px',
				lineHeight: 1.2,
				display: 'inline-flex',
				alignItems: 'center',
				columnGap: '4px',
				color: 'white',
				fontSize: '9px',

				'& & .fa': {
					color: 'white',
					fontSize: '10px',
				},
			} }
		>
			<i className="fa fa-lock" aria-hidden="true" />
			{ label }
		</Box>
	);
}
