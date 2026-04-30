import { Box, HStack, Switch, Text, VStack } from '@chakra-ui/react';
import { useId } from '@wordpress/element';
import type { I18nDict } from '../../types/fieldModal';

export interface ConditionsHeaderProps {
	title: string;
	desc: string;
	logicOn: boolean;
	i18n: I18nDict;
	onToggle: ( next: boolean ) => void;
	withDivider?: boolean;
}

export function ConditionsHeader( {
	title,
	desc,
	logicOn,
	i18n,
	onToggle,
	withDivider = true,
}: ConditionsHeaderProps ) {
	const switchId = useId();
	const stateLabel = logicOn
		? i18n.condEnabled || 'Enabled'
		: i18n.condDisabled || 'Disabled';

	return (
		<HStack
			align="flex-start"
			justify="space-between"
			gap={ 4 }
			pb={ withDivider ? 3 : 0 }
			mb={ withDivider ? 1 : 0 }
			borderBottomWidth={ withDivider ? '1px' : 0 }
			borderBottomColor="gray.100"
		>
			<VStack align="stretch" gap={ 0.5 } minW={ 0 } flex="1">
				<Text fontWeight="semibold" fontSize="sm" color="gray.900">
					{ title }
				</Text>
				{ desc ? (
					<Text
						fontSize="xs"
						color="gray.600"
						lineHeight="1.4"
						m={ 0 }
					>
						{ desc }
					</Text>
				) : null }
			</VStack>
			<HStack as="label" htmlFor={ switchId } gap={ 2 } cursor="pointer">
				<Switch.Root
					id={ switchId }
					colorPalette="green"
					size="md"
					checked={ logicOn }
					onCheckedChange={ ( { checked } ) => onToggle( checked ) }
				>
					<Switch.HiddenInput />
					<Switch.Control />
				</Switch.Root>
				<Box
					fontSize="sm"
					fontWeight="semibold"
					color={ logicOn ? 'green.600' : 'gray.500' }
					minW="60px"
				>
					{ stateLabel }
				</Box>
			</HStack>
		</HStack>
	);
}
