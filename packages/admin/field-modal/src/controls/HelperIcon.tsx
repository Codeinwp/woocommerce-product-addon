import { Tooltip } from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';

export interface HelperIconProps {
	description: string;
	allowHtml?: boolean;
}

export function HelperIcon( {
	description,
	allowHtml = false,
}: HelperIconProps ): JSX.Element | null {
	if ( ! description ) {
		return null;
	}

	return (
		<Tooltip.Root
			openDelay={ 200 }
			closeDelay={ 300 }
			positioning={ { placement: 'top', offset: { mainAxis: 8 } } }
		>
			<Tooltip.Trigger asChild>
				<span
					className="ppom-helper-icon"
					tabIndex={ 0 }
					role="button"
					aria-label={ __(
						'More info',
						'woocommerce-product-addon'
					) }
				>
					<i className="dashicons dashicons-editor-help" />
				</span>
			</Tooltip.Trigger>
			<Tooltip.Positioner>
				<Tooltip.Content maxW="340px">
					{ allowHtml ? (
						<span
							dangerouslySetInnerHTML={ {
								__html: description,
							} }
						/>
					) : (
						description
					) }
				</Tooltip.Content>
			</Tooltip.Positioner>
		</Tooltip.Root>
	);
}
