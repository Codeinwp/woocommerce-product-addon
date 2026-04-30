import { Box, HStack, Text } from '@chakra-ui/react';
import type { I18nDict } from '../../types/fieldModal';

export interface ConditionsRuleHeaderProps {
	i18n: I18nDict;
}

export function ConditionsRuleHeader( { i18n }: ConditionsRuleHeaderProps ) {
	const labelStyle = {
		fontSize: 'xs' as const,
		fontWeight: 'semibold' as const,
		color: 'gray.600',
	};

	return (
		<HStack align="center" gap={ 2 } w="full" px={ 0 }>
			{ /* drag handle spacer */ }
			<Box w="24px" flexShrink={ 0 } />
			<Text flex="1 1 0" minW={ 0 } { ...labelStyle }>
				{ i18n.condFieldHeader || 'Field' }
			</Text>
			<Text flex="1 1 0" minW={ 0 } { ...labelStyle }>
				{ i18n.condOperatorHeader || 'Operator' }
			</Text>
			<Text flex="1 1 0" minW={ 0 } { ...labelStyle }>
				{ i18n.condValueHeader || 'Value' }
			</Text>
			{ /* trash spacer */ }
			<Box w="24px" flexShrink={ 0 } />
		</HStack>
	);
}
