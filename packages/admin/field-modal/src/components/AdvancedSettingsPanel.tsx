/**
 * Disclosure button that reveals additional field sections (gear + label + chevron).
 */
import { useId, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { Box, chakra, Text, VStack } from '@chakra-ui/react';
import { LuChevronDown, LuSettings } from 'react-icons/lu';

/**
 * Builds the helper text shown under the disclosure button from the
 * advanced section labels. Lowercases items so they read as a sentence
 * fragment ("Configure validation, display & layout, and behavior.").
 */
export function formatAdvancedDescription( labels: string[] ): string {
	if ( labels.length === 0 ) {
		return '';
	}
	const lowercased = labels.map( ( s ) => s.toLowerCase() );
	const list = new Intl.ListFormat( undefined, {
		style: 'long',
		type: 'conjunction',
	} ).format( lowercased );
	return sprintf(
		/* translators: %s is a comma-separated list of advanced section names. */
		__( 'Configure %s.', 'woocommerce-product-addon' ),
		list
	);
}

export interface AdvancedSettingsPanelProps {
	/** Label shown on the disclosure button (e.g. “Advanced settings”). */
	label: string;
	/** Optional helper text rendered below the button when collapsed and expanded. */
	description?: string;
	children: React.ReactNode;
}

export function AdvancedSettingsPanel( {
	label,
	description,
	children,
}: AdvancedSettingsPanelProps ) {
	const [ open, setOpen ] = useState( false );
	const contentId = useId();

	return (
		<VStack align="stretch" gap={ 2 } width="100%">
			<chakra.button
				type="button"
				appearance="none"
				alignSelf="flex-start"
				display="inline-flex"
				alignItems="center"
				gap={ 2 }
				height="auto"
				py={ 1.5 }
				px={ 3 }
				bg="#ffffff"
				borderRadius="md"
				borderWidth="1px"
				borderColor="#d7dce3"
				cursor="pointer"
				textAlign="left"
				fontSize="sm"
				fontWeight="medium"
				lineHeight="1.4"
				color="#1f2937"
				transition="background-color 0.15s ease, border-color 0.15s ease, box-shadow 0.15s ease"
				onClick={ () => setOpen( ( prev ) => ! prev ) }
				_hover={ {
					bg: '#f8fafc',
					borderColor: '#bfc7d3',
					boxShadow: '0 0 0 3px rgba(99, 102, 241, 0.12)',
				} }
				_focusVisible={ {
					outline: '2px solid #6366f1',
					outlineOffset: '1px',
				} }
				aria-expanded={ open }
				aria-controls={ contentId }
			>
				<Box
					as="span"
					display="inline-flex"
					alignItems="center"
					justifyContent="center"
					flexShrink={ 0 }
					width="14px"
					height="14px"
					color="#6b7280"
					fontSize="14px"
				>
					<LuSettings aria-hidden="true" />
				</Box>
				<chakra.span>{ label }</chakra.span>
				<Box
					as="span"
					display="inline-flex"
					alignItems="center"
					justifyContent="center"
					flexShrink={ 0 }
					width="14px"
					height="14px"
					color="#6b7280"
					fontSize="14px"
					transition="transform 0.2s ease"
					transform={ `rotate(${ open ? 180 : 0 }deg)` }
				>
					<LuChevronDown aria-hidden="true" />
				</Box>
			</chakra.button>
			{ description ? (
				<Text
					m={ 0 }
					fontSize="xs"
					color="#6b7280"
					lineHeight="1.4"
				>
					{ description }
				</Text>
			) : null }
			{ open ? (
				<VStack
					id={ contentId }
					align="stretch"
					gap={ 3 }
					pt={ 1 }
					width="100%"
				>
					{ children }
				</VStack>
			) : null }
		</VStack>
	);
}
