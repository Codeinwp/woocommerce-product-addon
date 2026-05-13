import { Box } from '@chakra-ui/react';
import { useState, type SyntheticEvent } from 'react';
import { LuImage } from 'react-icons/lu';

export interface ImagePreviewProps {
	src: string;
	alt: string;
	/** Rendered square side in px. Defaults to 64 (mini media-manager card). */
	size?: number;
}

export function ImagePreview( { src, alt, size = 48 }: ImagePreviewProps ) {
	const box = `${ size }px`;
	const [ broken, setBroken ] = useState( false );
	const showImage = Boolean( src ) && ! broken;

	const handleError = ( _e: SyntheticEvent< HTMLImageElement > ) => {
		setBroken( true );
	};

	return (
		<Box
			flexShrink={ 0 }
			w={ box }
			h={ box }
			borderRadius="md"
			overflow="hidden"
			bg="gray.100"
			borderWidth="1px"
			borderColor="gray.200"
			display="flex"
			alignItems="center"
			justifyContent="center"
			color="gray.400"
		>
			{ showImage ? (
				<img
					src={ src }
					alt={ alt }
					onError={ handleError }
					style={ {
						width: '100%',
						height: '100%',
						objectFit: 'cover',
						display: 'block',
					} }
				/>
			) : (
				<LuImage size={ Math.round( size * 0.4 ) } />
			) }
		</Box>
	);
}
