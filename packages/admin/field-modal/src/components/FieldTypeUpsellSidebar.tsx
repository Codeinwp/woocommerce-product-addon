/**
 * Pro upsell column beside the field type catalog.
 */
import { Box, Button, Flex, HStack, Text, VStack } from '@chakra-ui/react';
import type { ModalUpsellPayload } from '../types/fieldModal';

export interface FieldTypeUpsellSidebarProps {
	upsell: ModalUpsellPayload;
}

export function FieldTypeUpsellSidebar( { upsell }: FieldTypeUpsellSidebarProps ) {
	return (
		<Box
			flex="0 0 280px"
			w={ { base: '100%', lg: '280px' } }
			borderLeftWidth={ { base: 0, lg: '1px' } }
			borderTopWidth={ { base: '1px', lg: 0 } }
			borderColor="gray.200"
			pl={ { base: 0, lg: 4 } }
			pt={ { base: 4, lg: 0 } }
			mt={ { base: 3, lg: 0 } }
		>
			<VStack
				align="center"
				spacing={ 3 }
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
				<VStack align="stretch" spacing={ 2 } w="full" fontSize="sm">
					{ ( upsell.features || [] ).map( ( line: string, i: number ) => (
						<HStack key={ i } align="flex-start">
							<Text color="green.500" fontWeight="bold">
								✓
							</Text>
							<Text>{ line }</Text>
						</HStack>
					) ) }
				</VStack>
				<Button
					as="a"
					href={ upsell.cta_url }
					target="_blank"
					rel="noopener noreferrer"
					width="full"
					colorScheme="green"
					size="md"
					borderRadius="md"
				>
					{ upsell.cta_label }
				</Button>
			</VStack>
		</Box>
	);
}
