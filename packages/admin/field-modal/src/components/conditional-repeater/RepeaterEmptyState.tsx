import { Button, Center, Flex, Icon, Text, VStack } from '@chakra-ui/react';
import { LuRepeat } from 'react-icons/lu';
import type { I18nDict } from '../../types/fieldModal';

export interface RepeaterEmptyStateProps {
	i18n: I18nDict;
	onEnable: () => void;
}

export function RepeaterEmptyState( {
	i18n,
	onEnable,
}: RepeaterEmptyStateProps ) {
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
					<Icon as={ LuRepeat } boxSize={ 5 } />
				</Flex>
				<VStack gap={ 1 }>
					<Text
						fontSize="sm"
						fontWeight="semibold"
						color="gray.800"
						textAlign="center"
					>
						{ i18n.cfrDisabledTitle ||
							'Conditional repeater is disabled' }
					</Text>
					<Text
						fontSize="xs"
						color="gray.600"
						textAlign="center"
						maxW="380px"
					>
						{ i18n.cfrDisabledHint ||
							'Turn it on to repeat this field based on another field’s value.' }
					</Text>
				</VStack>
				<Button
					size="sm"
					variant="outline"
					colorPalette="gray"
					bg="white"
					onClick={ onEnable }
				>
					{ i18n.cfrEnableButton || 'Enable conditional repeater' }
				</Button>
			</VStack>
		</Center>
	);
}
