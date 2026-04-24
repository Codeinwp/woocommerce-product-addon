/**
 * Header controls for the field type picker step.
 */
import { Box, HStack, Input, Text } from '@chakra-ui/react';
import { LuSearch } from 'react-icons/lu';
import type { I18nDict } from '../types/fieldModal';

export interface FieldPickerHeaderProps {
	i18n: I18nDict;
	query: string;
	onQueryChange: ( q: string ) => void;
}

export function FieldPickerHeader( {
	i18n,
	query,
	onQueryChange,
}: FieldPickerHeaderProps ) {
	return (
		<HStack w="full" flexWrap="wrap" gap={ 3 } justify="space-between">
			<Text
				as="span"
				fontWeight="bold"
				fontSize="lg"
				lineHeight="1.2"
				whiteSpace="nowrap"
			>
				{ i18n.selectFieldType }
			</Text>
			<Box position="relative" w="300px" maxW="100%">
				<Box
					as="span"
					position="absolute"
					left={ 3 }
					top="50%"
					transform="translateY(-50%)"
					color="gray.500"
					pointerEvents="none"
					zIndex={ 1 }
				>
					<LuSearch size={ 16 } aria-hidden="true" />
				</Box>
				<Input
					w="full"
					size="md"
					bg="white"
					fontWeight="400"
					ps={ 9 }
					placeholder={ i18n.searchFieldTypes }
					value={ query }
					onChange={ ( e ) => onQueryChange( e.currentTarget.value ) }
				/>
			</Box>
		</HStack>
	);
}
