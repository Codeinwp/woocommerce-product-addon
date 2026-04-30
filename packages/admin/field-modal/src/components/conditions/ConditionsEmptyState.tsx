import { Button, Center, Flex, Icon, Text, VStack } from '@chakra-ui/react';
import { LuFilterX } from 'react-icons/lu';
import type { I18nDict } from '../../types/fieldModal';

export interface ConditionsEmptyStateProps {
	i18n: I18nDict;
	onEnable: () => void;
}

export function ConditionsEmptyState( {
	i18n,
	onEnable,
}: ConditionsEmptyStateProps ) {
	return (
		<Center
			py={ 10 }
			px={ 4 }
			bg="gray.50"
			borderWidth="1px"
			borderColor="gray.100"
			borderStyle="dashed"
			borderRadius="md"
			mt={ 3 }
		>
			<VStack gap={ 3 }>
				<Flex
					align="center"
					justify="center"
					boxSize="48px"
					borderRadius="full"
					bg="gray.100"
					color="gray.500"
				>
					<Icon as={ LuFilterX } boxSize={ 5 } />
				</Flex>
				<VStack gap={ 1 }>
					<Text
						fontSize="sm"
						fontWeight="semibold"
						color="gray.800"
						textAlign="center"
					>
						{ i18n.condDisabledTitle ||
							'Conditional logic is disabled' }
					</Text>
					<Text
						fontSize="xs"
						color="gray.600"
						textAlign="center"
						maxW="380px"
					>
						{ i18n.condDisabledHint ||
							'Turn it on to create rules for showing or hiding this field.' }
					</Text>
				</VStack>
				<Button
					size="sm"
					variant="outline"
					colorPalette="gray"
					bg="white"
					onClick={ onEnable }
				>
					{ i18n.condEnableButton || 'Enable conditional logic' }
				</Button>
			</VStack>
		</Center>
	);
}
