/**
 * Calm Pro feature upsell card for locked modal sections.
 */
import { Box, Flex, HStack, Icon, Link, Text, VStack } from '@chakra-ui/react';
import { LuLock } from 'react-icons/lu';

const ORANGE = {
	accent: '#ff5a00',
	accentHover: '#e65000',
	surface: '#fffaf6',
	border: '#ffd9bf',
	soft: '#ffe3d1',
};

export interface ProFeatureUpsellCardProps {
	title: string;
	description: string;
	primaryLabel: string;
	primaryUrl?: string;
	secondaryLabel: string;
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
			bg={ ORANGE.surface }
			borderWidth="1px"
			borderColor={ ORANGE.border }
			borderRadius="md"
			color="gray.900"
		>
			<HStack align="flex-start" gap={ 3 }>
				<Flex
					w={ 10 }
					h={ 10 }
					borderRadius="md"
					bg={ ORANGE.soft }
					color={ ORANGE.accent }
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
							bg={ ORANGE.accent }
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
					<Box h="1px" bg={ ORANGE.border } />
					<HStack align="stretch" gap={ 2 } flexWrap="wrap">
						{ primaryUrl ? (
							<Link
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
								bg={ ORANGE.accent }
								color="white"
								fontWeight="semibold"
								fontSize="sm"
								textDecoration="none"
								whiteSpace="nowrap"
								w={ { base: 'full', sm: 'auto' } }
								_hover={ {
									bg: ORANGE.accentHover,
									color: 'white',
									textDecoration: 'none',
								} }
								css={ {
									'&&:hover, &&:focus, &&:active, &&:visited':
										{
											color: 'white',
											textDecoration: 'none',
										},
								} }
							>
								{ primaryLabel }
							</Link>
						) : null }
						{ secondaryUrl ? (
							<Link
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
								color={ ORANGE.accentHover }
								borderWidth="1px"
								borderColor={ ORANGE.border }
								fontWeight="semibold"
								fontSize="sm"
								textDecoration="none"
								whiteSpace="nowrap"
								w={ { base: 'full', sm: 'auto' } }
								_hover={ {
									bg: ORANGE.soft,
									color: ORANGE.accentHover,
									textDecoration: 'none',
								} }
								css={ {
									'&&:hover, &&:focus, &&:active, &&:visited':
										{
											color: ORANGE.accentHover,
											textDecoration: 'none',
										},
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
