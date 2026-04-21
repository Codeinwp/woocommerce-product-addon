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

export function FieldTypeUpsellSidebar( { upsell }: FieldTypeUpsellSidebarProps ) {
	return (
            <VStack
				align="center"
				gap={ 3 }
				p={ 3 }
				bg="gray.50"
				borderRadius="lg"
			>
				<Flex
					w={ 14 }
					h={ 14 }
					borderRadius="full"
					bg="blue.500"
					align="center"
					justify="center"
					color="white"
				>
					<span
						className="dashicons dashicons-lock"
						style={ { fontSize: 28, width: 28, height: 28 } }
						aria-hidden
					/>
				</Flex>
				<Text fontWeight="bold" fontSize="lg" textAlign="center">
					{ upsell.title }
				</Text>
				<Text fontSize="sm" color="gray.700" textAlign="center">
					{ upsell.intro }
				</Text>
				<VStack align="stretch" gap={ 2 } w="full" fontSize="sm">
					{ ( upsell.features || [] ).map( ( line: string, i: number ) => (
						<HStack key={ i } align="flex-start">
							<Text color="green.500" fontWeight="bold">
								✓
							</Text>
							<Text>{ line }</Text>
						</HStack>
					) ) }
				</VStack>
				<Link
					href={ upsell.cta_url }
					target="_blank"
					rel="noopener noreferrer"
					display="inline-flex"
					width="full"
					justifyContent="center"
					alignItems="center"
					minH="40px"
					px={ 4 }
					py={ 2 }
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
