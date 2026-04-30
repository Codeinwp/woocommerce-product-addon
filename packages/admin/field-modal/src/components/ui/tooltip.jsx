import { Box, Portal, Tooltip as ChakraTooltip } from '@chakra-ui/react';
import * as React from 'react';

export const Tooltip = React.forwardRef( function Tooltip( props, ref ) {
	const {
		showArrow,
		children,
		disabled,
		portalled = true,
		content,
		contentProps,
		portalRef,
		...rest
	} = props;

	if ( disabled ) {
		return children;
	}

	return (
		<ChakraTooltip.Root { ...rest }>
			<ChakraTooltip.Trigger asChild>{ children }</ChakraTooltip.Trigger>
			<Portal disabled={ ! portalled } container={ portalRef }>
				<ChakraTooltip.Positioner>
					{ /*
					 * Chakra v3 `Tooltip.Content` uses forwardAsChild; multiple direct children
					 * break the slot merge and can trigger React #130 (invalid element type: object).
					 */ }
					<ChakraTooltip.Content ref={ ref } { ...contentProps }>
						<Box as="span" display="block">
							{ showArrow && (
								<ChakraTooltip.Arrow>
									<ChakraTooltip.ArrowTip />
								</ChakraTooltip.Arrow>
							) }
							{ content }
						</Box>
					</ChakraTooltip.Content>
				</ChakraTooltip.Positioner>
			</Portal>
		</ChakraTooltip.Root>
	);
} );
