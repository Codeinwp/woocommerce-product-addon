/**
 * Calm Pro feature upsell card for locked modal sections.
 */
import { Box, Flex, HStack, Icon, Link, Text, VStack } from '@chakra-ui/react';
import { LuLock } from 'react-icons/lu';

export interface ProFeatureUpsellCardProps {
	title: string;
	description: string;
	primaryLabel: string;
	primaryUrl?: string;
	secondaryLabel?: string;
	secondaryUrl?: string;
	badgeLabel?: string;
}

export function ProFeatureUpsellCard( {
	title,
	description,
	primaryLabel,
	primaryUrl,
	secondaryLabel,
	secondaryUrl,
	badgeLabel = 'PRO',
}: ProFeatureUpsellCardProps ) {
	const hasActions = Boolean( primaryUrl || secondaryUrl );

	return (
		<VStack
			align="stretch"
			gap={ 4 }
			p={ { base: 4, md: 5 } }
			bg="transparent"
			borderWidth="1px"
			borderColor="gray.200"
			borderRadius="md"
			color="gray.900"
		>
			<HStack align="flex-start" gap={ 3 }>
				<Flex
					w={ 10 }
					h={ 10 }
					borderRadius="md"
					bg="gray.100"
					color="gray.500"
					align="center"
					justify="center"
					flexShrink={ 0 }
				>
					<Icon as={ LuLock } boxSize={ 5 } aria-hidden />
				</Flex>
				<Box flex="1" minW={ 0 }>
					<HStack gap={ 2 } mb={ 1 } flexWrap="wrap">
						<Text
							fontWeight="semibold"
							fontSize="sm"
							lineHeight="1.3"
							m={ 0 }
						>
							{ title }
						</Text>
						<Box
							as="span"
							px={ 2 }
							py="2px"
							borderRadius="sm"
							bg="#28A745"
							color="white"
							fontSize="10px"
							fontWeight="700"
							lineHeight="1.2"
						>
							{ badgeLabel }
						</Box>
					</HStack>
					<Text
						fontSize="sm"
						lineHeight="1.5"
						color="gray.700"
						m={ 0 }
					>
						{ description }
					</Text>
				</Box>
			</HStack>

			{ hasActions ? (
				<>
					<Box h="1px" bg="gray.200" />
					<HStack align="stretch" gap={ 2 } flexWrap="wrap">
						{ primaryUrl ? (
							<Link
								className="ppom-upsell-cta ppom-upsell-cta--primary"
								href={ primaryUrl }
								target="_blank"
								rel="noopener noreferrer"
								display="inline-flex"
								alignItems="center"
								justifyContent="center"
								minH="36px"
								px={ 4 }
								py={ 1.5 }
								borderRadius="md"
								bg="#28A745"
								color="white"
								fontWeight="semibold"
								fontSize="sm"
								textDecoration="none"
								whiteSpace="nowrap"
								w={ { base: 'full', sm: 'auto' } }
								_hover={ { bg: '#218838', color: 'white' } }
								_focus={ { bg: '#218838', color: 'white' } }
								_focusVisible={ {
									bg: '#218838',
									color: 'white',
								} }
								_active={ { bg: '#218838', color: 'white' } }
							>
								{ primaryLabel }
							</Link>
						) : null }
						{ secondaryUrl && secondaryLabel ? (
							<Link
								className="ppom-upsell-cta ppom-upsell-cta--secondary"
								href={ secondaryUrl }
								target="_blank"
								rel="noopener noreferrer"
								display="inline-flex"
								alignItems="center"
								justifyContent="center"
								minH="36px"
								px={ 4 }
								py={ 1.5 }
								borderRadius="md"
								bg="white"
								color="#28A745"
								borderWidth="1px"
								borderColor="#28A745"
								fontWeight="semibold"
								fontSize="sm"
								textDecoration="none"
								whiteSpace="nowrap"
								w={ { base: 'full', sm: 'auto' } }
								_hover={ { bg: '#E6F4EA', color: '#218838' } }
								_focus={ { bg: '#E6F4EA', color: '#218838' } }
								_focusVisible={ {
									bg: '#E6F4EA',
									color: '#218838',
								} }
								_active={ {
									bg: '#E6F4EA',
									color: '#218838',
								} }
							>
								{ secondaryLabel }
							</Link>
						) : null }
					</HStack>
				</>
			) : null }
		</VStack>
	);
}
