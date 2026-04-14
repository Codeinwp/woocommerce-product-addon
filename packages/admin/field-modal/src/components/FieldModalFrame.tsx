/**
 * Chakra modal shell: overlay, content sizing, header, close control.
 */
import type { ReactNode } from 'react';
import {
	Modal,
	ModalOverlay,
	ModalContent,
	ModalHeader,
	ModalCloseButton,
} from '@chakra-ui/react';

export interface FieldModalFrameProps {
	isOpen: boolean;
	onClose: () => void;
	saving: boolean;
	title: string;
	children: ReactNode;
}

export function FieldModalFrame( {
	isOpen,
	onClose,
	saving,
	title,
	children,
}: FieldModalFrameProps ) {
	return (
		<Modal
			isOpen={ isOpen }
			onClose={ onClose }
			variant="ppom"
			scrollBehavior="inside"
			closeOnOverlayClick={ ! saving }
			closeOnEsc={ ! saving }
			motionPreset="slideInBottom"
		>
			<ModalOverlay bg="blackAlpha.600" backdropFilter="blur(2px)" />
			<ModalContent
				maxW={ { base: '96vw', md: 'min(92vw, 58rem)', lg: '62rem' } }
				w="full"
				maxH="min(90vh, 56rem)"
				my={ 4 }
				display="flex"
				flexDirection="column"
			>
				<ModalHeader flexShrink={ 0 }>{ title }</ModalHeader>
				<ModalCloseButton isDisabled={ saving } />
				{ children }
			</ModalContent>
		</Modal>
	);
}
