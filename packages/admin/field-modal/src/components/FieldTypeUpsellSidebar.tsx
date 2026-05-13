/**
 * Pro upsell card shown in the field type picker's right column. Stacked inside
 * a shared sidebar container (see FieldTypePicker) so column sizing and dividers
 * live on the parent, not here.
 */
import { Flex, HStack, Link, Text, VStack } from '@chakra-ui/react';
import type { ModalUpsellPayload } from '../types/fieldModal';

export interface FieldTypeUpsellSidebarProps {
	upsell: ModalUpsellPayload;
}

export function FieldTypeUpsellSidebar( {
	upsell,
}: FieldTypeUpsellSidebarProps ) {
	return (
		<VStack
			align="center"
			gap={ 2 }
			p={ 2.5 }
			bg="gray.50"
			borderRadius="lg"
		>
			<Flex
				w={ 11 }
				h={ 11 }
				borderRadius="full"
				bg="blue.500"
				align="center"
				justify="center"
				color="white"
			>
				<span
					className="dashicons dashicons-lock"
					style={ { fontSize: 22, width: 22, height: 22 } }
					aria-hidden
				/>
			</Flex>
			<Text
				fontWeight="bold"
				fontSize="md"
				lineHeight="1.25"
				m={ 0 }
				textAlign="center"
			>
				{ upsell.title }
			</Text>
			<Text
				fontSize="sm"
				color="gray.700"
				lineHeight="1.4"
				m={ 0 }
				textAlign="center"
			>
				{ upsell.intro }
			</Text>
			<VStack align="stretch" gap={ 1.5 } w="full" fontSize="sm">
				{ ( upsell.features || [] ).map(
					( line: string, i: number ) => (
						<HStack key={ i } align="flex-start" gap={ 2 }>
							<Text color="green.500" fontWeight="bold" m={ 0 }>
								✓
							</Text>
							<Text lineHeight="1.35" m={ 0 }>
								{ line }
							</Text>
						</HStack>
					)
				) }
			</VStack>
			<Link
				href={ upsell.cta_url }
				target="_blank"
				rel="noopener noreferrer"
				display="inline-flex"
				width="full"
				justifyContent="center"
				alignItems="center"
				minH="36px"
				px={ 4 }
				py={ 1.5 }
				borderRadius="md"
				bg="green.500"
				color="white"
				fontWeight="semibold"
				fontSize="sm"
				textDecoration="none"
				_hover={ { bg: 'green.600' } }
			>
				{ upsell.cta_label }
			</Link>
		</VStack>
	);
}
