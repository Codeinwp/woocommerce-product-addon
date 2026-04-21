import { Box, Image as ChakraImage, Text } from '@chakra-ui/react';

const FALLBACK_SVG =
	"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24'%3E%3Cpath fill='%23ccc' d='M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z'/%3E%3C/svg%3E";

export interface ImagePreviewProps {
	src: string;
	alt: string;
}

export function ImagePreview( { src, alt }: ImagePreviewProps ) {
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
			{ src ? (
				<ChakraImage
					src={ src }
					alt={ alt }
					fallbackSrc={ FALLBACK_SVG }
					boxSize="36px"
					objectFit="cover"
					borderRadius="sm"
				/>
			) : (
				<Text fontSize="xs" color="gray.400" textAlign="center">
					No img
				</Text>
			) }
		</Box>
	);
}
