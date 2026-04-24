import { Text } from '@chakra-ui/react';
import type { I18nDict } from '../../types/fieldModal';

export interface ConditionsHeaderProps {
	title: string;
	desc: string;
	logicOn: boolean;
	i18n: I18nDict;
}

export function ConditionsHeader( {
	title,
	desc,
	logicOn,
	i18n,
}: ConditionsHeaderProps ) {
	return (
		<>
			<Text fontWeight="semibold" fontSize="sm" color="gray.800">
				{ title }
			</Text>
			{ desc ? (
				<Text fontSize="sm" color="gray.600" mt={ 1 } lineHeight="1.5">
					{ desc }
				</Text>
			) : null }
			{ ! logicOn ? (
				<Text mt={ 3 } fontSize="xs" color="gray.600" lineHeight="1.5">
					{ i18n.condEnableLogicHint ||
						'Turn on “Use conditional logic” above to apply these rules on the product page.' }
				</Text>
			) : null }
		</>
	);
}
