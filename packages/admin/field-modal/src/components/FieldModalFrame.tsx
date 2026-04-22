/**
 * Chakra modal shell: overlay, content sizing, header, close control.
 */
import type { ReactNode } from 'react';
import { useEffect } from '@wordpress/element';
import { Box, Button, Dialog, HStack, Portal, Text } from '@chakra-ui/react';
import { LuArrowLeft } from 'react-icons/lu';
import { useMediaFrameLocked } from '../utils/mediaLock';

const mediaPersistentElements = [
	() => document.querySelector< HTMLElement >( '.media-modal' ),
	() => document.querySelector< HTMLElement >( '.media-modal-backdrop' ),
];

export interface FieldModalFrameProps {
	isOpen: boolean;
	onClose: () => void;
	saving: boolean;
	title: string;
	onBack?: () => void;
	backLabel?: string;
	children: ReactNode;
}

export function FieldModalFrame( {
	isOpen,
	onClose,
	saving,
	title,
	onBack,
	backLabel,
	children,
}: FieldModalFrameProps ) {
	// While a wp.media frame is open on top of us, disable outside-click and
	// Esc dismissal entirely. We also have to drop out of "modal" mode, because
	// Ark/Zag otherwise keeps the rest of the page inert (`body[data-inert]`),
	// which leaves the wp.media frame visible but non-interactable.
	const mediaLocked = useMediaFrameLocked();
	const dismissible = ! saving && ! mediaLocked;
	const modal = ! mediaLocked;

	useEffect( () => {
		if ( ! onBack ) return;
		const handler = ( e: KeyboardEvent ) => {
			if ( e.key !== 'Backspace' ) return;
			const target = e.target as HTMLElement | null;
			if ( ! target ) return;
			const tag = target.tagName;
			if (
				tag === 'INPUT' ||
				tag === 'TEXTAREA' ||
				tag === 'SELECT' ||
				target.isContentEditable
			) {
				return;
			}
			e.preventDefault();
			onBack();
		};
		window.addEventListener( 'keydown', handler );
		return () => window.removeEventListener( 'keydown', handler );
	}, [ onBack ] );

	return (
        <Dialog.Root
			open={ isOpen }
			variant="ppom"
			scrollBehavior="inside"
			modal={ modal }
			trapFocus={ modal }
			preventScroll={ modal }
			persistentElements={ mediaPersistentElements }
			closeOnInteractOutside={ dismissible }
			closeOnEscape={ dismissible }
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
							<Dialog.Header flexShrink={ 0 }>
								{ onBack ? (
									<HStack w="full" justify="space-between" align="center" gap={ 4 }>
										<Button
											variant="ghost"
											size="sm"
											onClick={ onBack }
											colorPalette="gray"
											pl={ 0 }
										>
											<LuArrowLeft />
											{ backLabel || 'Back' }
										</Button>
										<Text as="span" truncate>
											{ title }
										</Text>
									</HStack>
								) : (
									title
								) }
							</Dialog.Header>
							<Dialog.CloseTrigger disabled={ saving } />
							{ children }
						</Box>
					</Dialog.Content>
				</Dialog.Positioner>

            </Portal>
        </Dialog.Root>
    );
}
