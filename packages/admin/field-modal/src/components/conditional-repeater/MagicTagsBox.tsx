import { Box, Button, Text, VStack } from '@chakra-ui/react';
import type { I18nDict } from '../../types/fieldModal';

export interface MagicTagsBoxProps {
	showOptionTitleTag: boolean;
	copiedHint: string;
	i18n: I18nDict;
	onCopy: ( tag: string ) => void;
}

export function MagicTagsBox( {
	showOptionTitleTag,
	copiedHint,
	i18n,
	onCopy,
}: MagicTagsBoxProps ) {
	return (
		<Box
			borderWidth="1px"
			borderColor="gray.200"
			borderRadius="md"
			p={ 3 }
			bg="white"
		>
			<Text fontWeight="semibold" fontSize="sm" mb={ 1 }>
				{ i18n.cfrMagicTagsHeading || 'Magic tags' }
			</Text>
			<Text fontSize="xs" color="gray.600" mb={ 3 } lineHeight="1.5">
				{ i18n.cfrMagicTagsDescription ||
					'Use these in the Field Title (Fields tab).' }
			</Text>
			<VStack align="stretch" gap={ 2 }>
				<Button
					size="sm"
					variant="outline"
					onClick={ () => onCopy( '{repeatNumber}' ) }
				>
					{ `{repeatNumber}` }
				</Button>
				{ showOptionTitleTag ? (
					<Button
						size="sm"
						variant="outline"
						onClick={ () => onCopy( '{optionTitle}' ) }
					>
						{ `{optionTitle}` }
					</Button>
				) : null }
				{ copiedHint ? (
					<Text fontSize="xs" color="green.600">
						{ ( i18n.cfrCopied || 'Copied' ) + ': ' + copiedHint }
					</Text>
				) : null }
			</VStack>
		</Box>
	);
}
