import { Box, Text } from '@chakra-ui/react';

export function AudioIconBox() {
	return (
		<Box
			flexShrink={ 0 }
			w="40px"
			h="40px"
			borderRadius="sm"
			overflow="hidden"
			bg="gray.50"
			borderWidth="1px"
			borderColor="gray.200"
			display="flex"
			alignItems="center"
			justifyContent="center"
		>
			<Text fontSize="lg" aria-hidden="true">
				&#9835;
			</Text>
		</Box>
	);
}
