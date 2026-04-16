/**
 * Chakra modal shell: overlay, content sizing, header, close control.
 */
import type { ReactNode } from 'react';
import { Box, Dialog, Portal } from '@chakra-ui/react';

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
        <Dialog.Root
			open={ isOpen }
			variant="ppom"
			scrollBehavior="inside"
			closeOnInteractOutside={ ! saving }
			closeOnEscape={ ! saving }
			motionPreset="slideInBottom"
			onOpenChange={e => {
                if (!e.open) {
                    onClose();
                }
            }}
		>
            <Portal>

                <Dialog.Backdrop bg="blackAlpha.600" backdropFilter="blur(2px)" />
				<Dialog.Positioner>
					{/*
					 * `Dialog.Content` is `forwardAsChild` in Chakra v3 — multiple direct
					 * children (header + close + body) break the slot merge → React #130.
					 */}
					<Dialog.Content
						maxW={ { base: '96vw', md: 'min(92vw, 58rem)', lg: '62rem' } }
						w="full"
						maxH="min(90vh, 56rem)"
						my={ 4 }
					>
						<Box
							display="flex"
							flexDirection="column"
							flex="1"
							minH={ 0 }
							w="full"
						>
							<Dialog.Header flexShrink={ 0 }>{ title }</Dialog.Header>
							<Dialog.CloseTrigger disabled={ saving } />
							{ children }
						</Box>
					</Dialog.Content>
				</Dialog.Positioner>

            </Portal>
        </Dialog.Root>
    );
}
