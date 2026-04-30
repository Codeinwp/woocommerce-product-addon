import { Box, HStack, Link, Switch, Text, VStack } from '@chakra-ui/react';
import { useId } from '@wordpress/element';
import type { I18nDict } from '../../types/fieldModal';

export interface EnableSwitchProps {
	enabled: boolean;
	i18n: I18nDict;
	docsUrl: string;
	onToggle: ( on: boolean ) => void;
	withDivider?: boolean;
}

export function EnableSwitch( {
	enabled,
	i18n,
	docsUrl,
	onToggle,
	withDivider = true,
}: EnableSwitchProps ) {
	const switchId = useId();
	const title = i18n.cfrEditorTitle || 'Conditional Repeater';
	const desc =
		i18n.cfrEditorDesc ||
		'Repeat this field based on the value of another field.';
	const stateLabel = enabled
		? i18n.cfrEnabled || 'Enabled'
		: i18n.cfrDisabled || 'Disabled';

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
				<HStack gap={ 2 } align="center" flexWrap="wrap">
					<Text
						fontWeight="semibold"
						fontSize="sm"
						color="gray.900"
					>
						{ title }
					</Text>
					{ docsUrl ? (
						<Link
							href={ docsUrl }
							fontSize="xs"
							color="blue.700"
							target="_blank"
							rel="noopener noreferrer"
						>
							{ i18n.cfrLearnMore || 'Learn more' }
						</Link>
					) : null }
				</HStack>
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
					checked={ Boolean( enabled ) }
					onCheckedChange={ ( { checked: next } ) => onToggle( next ) }
				>
					<Switch.HiddenInput />
					<Switch.Control />
				</Switch.Root>
				<Box
					fontSize="sm"
					fontWeight="semibold"
					color={ enabled ? 'green.600' : 'gray.500' }
					minW="60px"
				>
					{ stateLabel }
				</Box>
			</HStack>
		</HStack>
	);
}
